<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * The ETForm class defines a form which can be rendered as HTML in the view and its input processed in the
 * controller.
 *
 * Many of the functions in this class are for rendering form controls, and are intended to be used in the
 * view. Generally, a controller will instantiate the form, define its default values and its action, check
 * if it has been posted back, carry out any necessary processing and error checking, and then pass the form
 * onto the view. The view will render the form elements individually.
 *
 * However, it also contains functions to define a form's structure and contents in the controller rather than
 * on the view. This is useful as it gives plugins the opportunity to alter forms and add custom fields in
 * any position. See addSection() and addField() for more information on this.
 *
 * @package esoTalk
 */
class ETForm extends ETPluggable {


/**
 * An array of "sections" in the form. Sections merely categorize/segment fields.
 * @var array
 */
public $sections = array();


/**
 * An array of "fields" in the form. Essentially defines the structure of the form.
 * @var array
 */
public $fields = array();


/**
 * An array of errors to show when the form is rendered.
 * @var array
 */
public $errors = array();


/**
 * The "action" attribute of the <form> tag.
 * @var string
 */
public $action = "";


/**
 * An array of default values for the form fields.
 * @var array
 */
public $values = array();


/**
 * An array of hidden inputs to render when the form is opened.
 * @var array
 */
public $hiddenInputs = array();


/**
 * Add a section to the form. Sections can contain multiple fields.
 *
 * @param string $id The name of the section.
 * @param string $title The title of the section.
 * @param mixed $position The position to put this section relative to other sections.
 * @see addToArrayString
 * @return void
 */
public function addSection($id, $title = "", $position = false)
{
	addToArrayString($this->sections, $id, $title, $position);
}


/**
 * Remove a section and all of its fields from a form.
 * 
 * @param string $id The name of the section to remove.
 * @return void
 */
public function removeSection($id)
{
	unset($this->sections[$id]);
	unset($this->fields[$id]);
}


/**
 * Add a field to the form.
 *
 * @param string $section The name of the section to add this field to.
 * @param string $id The name of the field.
 * @param mixed $renderCallback The function to call that will return the field's HTML.
 * @param mixed $processCallback The function to call that will process the field's input.
 * @param mixed $position The position to put this field relative to other fields.
 * @see addArrayToString
 * @return void
 */
public function addField($section, $id, $renderCallback, $processCallback = null, $position = false)
{
	if (!isset($this->fields[$section])) $this->fields[$section] = array();
	addToArrayString($this->fields[$section], $id, array(
		"renderCallback" => $renderCallback,
		"processCallback" => $processCallback
	), $position);
}


/**
 * Remove a field from a form.
 * 
 * @param string $id The name of the section to remove this field from.
 * @param string $id The name of the field.
 * @return void
 */
public function removeField($section, $id)
{
	unset($this->fields[$section][$id]);
}


/**
 * Get the sections defined in the form.
 *
 * @return array An array of section names => titles.
 */
public function getSections()
{
	return $this->sections;
}


/**
 * Get the fields within a certain section to be rendered.
 *
 * @param string $section The section to get fields from.
 * @return array An array of field names => html.
 */
public function getFieldsInSection($section)
{
	$fields = array();
	if (isset($this->fields[$section])) {
		foreach ($this->fields[$section] as $name => $callbacks) {
			$fields[$name] = call_user_func_array($callbacks["renderCallback"], array($this));
		}
	}
	return $fields;
}


/**
 * Run the processing callbacks for all the fields defined in the form.
 *
 * @param mixed $collector A variable which callback functions can add information to.
 * @return void
 */
public function runFieldCallbacks(&$collector = null)
{
	foreach ($this->fields as $fields) {
		foreach ($fields as $k => $callbacks) {
			if ($callbacks["processCallback"] !== null)
				call_user_func_array($callbacks["processCallback"], array($this, $k, &$collector));
		}
	}
}


/**
 * Get the HTML that opens the form. Includes the <form> tag and any hidden inputs (a token one is
 * automatically included.)
 *
 * @return string
 */
public function open()
{
	$this->addHidden("token", ET::$session->token);
	$hidden = "";
	foreach ($this->hiddenInputs as $field)
		$hidden .= "<input type='hidden' name='$field' value='".htmlentities($this->getValue($field), ENT_QUOTES, "UTF-8")."'/>\n";

	return "<form action='".sanitizeHTML($this->action)."' method='post' enctype='multipart/form-data'>\n".$hidden;
}


/**
 * Get the HTML that closes the form.
 *
 * @return string
 */
public function close()
{
	return "</form>";
}


/**
 * Checks if the form has been posted back and if a valid token was posted back with it.
 *
 * @param string $field An optional field to check the existence of.
 * @return bool
 */
public function validPostBack($field = "")
{
	return $this->isPostBack($field) and ET::$session->validateToken(@$_POST["token"]);
}


/**
 * Checks if the form has been posted back. Does not require a valid token to be posted back as well.
 *
 * @param string $field An optional field to check the existence of.
 * @return bool
 */
public function isPostBack($field = "")
{
	return $field ? isset($_POST[$field]) : !empty($_POST);
}


/**
 * Get the HTML code for a field's error.
 *
 * @param string $field The name of the field to get the error of.
 * @return string
 */
public function getError($field)
{
	if (!empty($this->errors[$field]))
		return "<div class='error'>".$this->errors[$field]."</div>";
}


/**
 * Get an array of HTML of all errors that occurred within the form.
 *
 * @return array
 */
public function getErrors()
{
	$errors = array();
	foreach ($this->errors as $k => $v)
		$errors[$k] = $this->getError($k);

	return $errors;
}


/**
 * Get the value of a particular field. If the form has been posted back, this will get the POST value of the
 * field. Otherwise, it will get the value set by setValue(), or use $default if it hasn't been set.
 *
 * @param string $field The name of the field.
 * @param string $default The default value to fall back on if no value is found.
 * @return string
 */
public function getValue($field, $default = "")
{
	if ($this->isPostBack()) {

		// If the field is part of a multi-dimensional array (i.e. its name is like this[that][foo], we'll
		// have to parse the field's name to get the correct value from the $_POST array.
		if (strpos($field, "[") !== false) {

			$parts = explode("[", $field);
			$value = $_POST;

			// Go through each "part" in the field name and drill down into the $_POST array.
			foreach ($parts as $part) {
				$part = rtrim($part, "]");
				$value = @$value[$part];
			}

			return $value;
		}

		// If it's just a normal field name, get the value straight from POST.
		// We don't return the $default value here, because we know this is a postback 
		// and therefore we can assume that whatever data has been posted back is what
		// we want to use. This is important especially for checkboxes ($_POST['checkbox']
		// isn't set, but that implies that its value is empty, not whatever $default is.)
		else return isset($_POST[$field]) ? $_POST[$field] : "";
	}
	else
		return isset($this->values[$field]) ? $this->values[$field] : $default;
}


/**
 * Get the values of all fields posted back.
 *
 * @return array
 */
public function getValues()
{
	return $_POST;
}


/**
 * Set a default value for a particular field, to be used if the form has not been posted back.
 *
 * @param string $field The name of the field.
 * @param string $value The default value to set.
 * @return void
 */
public function setValue($field, $value)
{
	$this->values[$field] = $value;
}


/**
 * Set the default values of multiple fields defined in an array.
 *
 * @param array $values An array of field => value elements.
 * @return void
 */
public function setValues($values)
{
	foreach ($values as $field => $value)
		$this->setValue($field, $value);
}


/**
 * Add a hidden input to be rendered when the form is opened.
 *
 * @param string $name The name of the field.
 * @param string $value The value of the hidden input.
 * @return void
 */
public function addHidden($name, $value)
{
	$this->hiddenInputs[] = $name;
	$this->setValue($name, $value);
}


/**
 * Get the HTML code for an input, with an error appended if there is one.
 *
 * @param string $name The name of the field.
 * @param string $type The type of input. This can be anything that would go in a type='' attribute, or "textarea".
 * @param array $attributes An array of attributes to add to the input tag.
 * @return string
 */
public function input($name, $type = "text", $attributes = array())
{
	$attributes["name"] = $name;

	// If there's an error for this field, add the "error" class.
	if (!empty($this->errors[$name])) $this->addClass($attributes, "error");

	// If a value attribute is not explicitly specified, get what the value should be.
	if (!isset($attributes["value"])) $attributes["value"] = $this->getValue($name);

	// If this is a textarea, make some custom HTML.
	if ($type == "textarea") {
		$value = htmlentities($attributes["value"], ENT_NOQUOTES, "UTF-8");
		unset($attributes["value"]);
		$input = "<textarea".$this->getAttributes($attributes).">$value</textarea>";
	}

	// But any other type of input we can use the <input> tag.
	else {
		$input = "<input type='$type'".$this->getAttributes($attributes)."/>";
	}

	// Append an error if there is one.
	if (!empty($this->errors[$name])) $input .= " ".$this->getError($name);

	return $input;
}


/**
 * Convert an array of attributes into a string which can be inserted into the HTML tag.
 *
 * @param array $attributes The array of attributes.
 * @return string
 */
protected function getAttributes($attributes)
{
	$string = "";
	foreach ($attributes as $k => $v) {
		$string .= " $k='".htmlentities($v, ENT_QUOTES, "UTF-8")."'";
	}
	return $string;
}


/**
 * Add a class to an array of attributes.
 *
 * @param array $attributes The attributes array to add the class to.
 * @param string $class The class name.
 * @return void
 */
protected function addClass(&$attributes, $class)
{
	if (empty($attributes["class"])) $attributes["class"] = $class;
	else $attributes["class"] .= " $class";
}


/**
 * Get the HTML code for a select field and its options.
 *
 * @param string $name The name of the field.
 * @param array $options An array of options with value => contents elements.
 * @param array $attributes An array of attributes to add to the select tag.
 * @return string
 */
public function select($name, $options, $attributes = array())
{
	// Construct the opening select tag.
	$attributes["name"] = $name;
	$select = "<select".$this->getAttributes($attributes).">\n";

	// Get the currently-selected value of this field.
	$values = (array)$this->getValue($name);

	// Loop through the options and add a tag for each one, selecting the appropriate one.
	foreach ($options as $k => $v)
		$select .= "<option value='$k'".(in_array($k, $values) ? " selected='selected'" : "").">$v</option>\n";

	$select .= "</select>";

	// Append an error if there is one.
	if (!empty($this->errors[$name])) $select .= " ".$this->getError($name);

	return $select;
}


/**
 * Get the HTML code for a simple checkbox.
 *
 * @param string $name The name of the field.
 * @param array $attributes An array of attributes to add to the input tag.
 * @return string
 */
public function checkbox($name, $attributes = array())
{
	if (!isset($attributes["value"])) $attributes["value"] = 1;

	// Check (pun intended) if this checkbox should be checked.
	if ($this->getValue($name) == $attributes["value"]) $attributes["checked"] = "checked";

	return $this->input($name, "checkbox", $attributes);
}


/**
 * Get the HTML code for a simple radio button.
 *
 * @param string $name The name of the field.
 * @param string $value The value of this radio button.
 * @param array $attributes An array of attributes to add to the input tag.
 * @return string
 */
public function radio($name, $value, $attributes = array())
{
	// Check if this radio button should be checked.
	$attributes["value"] = $value;
	if ($this->getValue($name) == $attributes["value"]) $attributes["checked"] = "checked";

	return $this->input($name, "radio", $attributes);
}


/**
 * Get the HTML code for a submit button.
 *
 * @param string $name The name of the field.
 * @param string $label The label to put on the button.
 * @param array $attributes An array of attributes to add to the input tag.
 * @return string
 */
public function button($name, $label = "", $attributes = array())
{
	$this->addClass($attributes, "button");
	if (isset($attributes["tag"]) and $attributes["tag"] == "button")
		return "<button type='submit' name='$name'".$this->getAttributes($attributes).">$label</button>";
	else
		return "<input type='submit' name='$name' value='$label'".$this->getAttributes($attributes)."/>";
}


/**
 * Get the HTML code for a "save changes" button, with the name "save".
 *
 * @return string
 */
public function saveButton($name = "save")
{
	return $this->button($name, T("Save Changes"), array("class" => "big submit"));
}


/**
 * Get the HTML code for a "cancel" button, with the name "cancel".
 *
 * @return string
 */
public function cancelButton($name = "cancel")
{
	return $this->button($name, T("Cancel"), array("class" => "big cancel"));
}


/**
 * Set an error on a specific field in the form.
 *
 * @param string $field The name of the field to set the error on.
 * @param string $message The error message.
 * @return void
 */
public function error($field, $message)
{
	$this->errors[$field] = $message;
}


/**
 * Set errors on multiple fields using an array.
 *
 * @param array $errors An array of errors with field name => error message elements.
 * @return void
 */
public function errors($errors)
{
	foreach ($errors as $k => $v) $this->error($k, T("message.$v"));
}


/**
 * Get the number of errors that have occurred in the form.
 *
 * @return int
 */
public function errorCount()
{
	return count($this->errors);
}


/**
 * Validate the contents of a field through a validation callback function.
 *
 * @param string $field The name of the field to validate.
 * @param mixed $callback The validation callback function to use.
 * @return bool Whether or not the field passed validation.
 */
public function validate($field, $callback)
{
	if ($message = call_user_func($callback, $this->getValue($field))) {
		$this->message($field, $message);
		return false;
	}
	return true;
}

}
