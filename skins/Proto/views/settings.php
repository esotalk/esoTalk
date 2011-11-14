<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays the settings form for the Proto skin.
 * 
 * @package esoTalk
 */

$form = $data["skinSettingsForm"];
?>

<?php echo $form->open(); ?>

<ul class='form'>

<li class='sep'></li>

<li id='headerColor'>
<label>Header color</label> 
<?php echo $form->input("headerColor", "text", array("class" => "color")); ?> <a href='#' class='reset'>Reset</a>
</li>


<li id='bodyColor'>
<label>Background color</label>
<?php echo $form->input("bodyColor", "text", array("class" => "color")); ?> <a href='#' class='reset'>Reset</a>
</li>

<li class='sep'></li>

<li id='bodyImage'>
<label>Background image</label>
<div class='checkboxGroup'>
<label class='checkbox'><?php echo $form->checkbox("bodyImage"); ?> Use a background image</label>
<div class='indent'>
<?php echo $form->input("bodyImageFile", "file", array("class" => "text")); ?>
<label class='checkbox'><?php echo $form->checkbox("noRepeat"); ?> Don't repeat</label>
</div>
</div>
</li>

<li class='sep'></li>

<li><?php echo $form->saveButton(); ?></li>
</ul>

<?php echo $form->close(); ?>

<script>
$(function() {

	// Turn a normal text input into a color picker, and run a callback when the color is changed.
	function colorPicker(id, callback) {

		// Create the color picker container.
		var picker = $("<div id='"+id+"-colorPicker'></div>").appendTo("body").addClass("popup").hide();

		// When the input is focussed upon, show the color picker.
		$("#"+id+" input").focus(function() {
			picker.css({position: "absolute", top: $(this).offset().top - picker.outerHeight(), left: $(this).offset().left}).show();
		})

		// When focus is lost, hide the color picker.
		.blur(function() {
			picker.hide();
		})

		// Add a color swatch before the input.
		.before("<span class='colorSwatch'></span>");

		// Create a handler function for when the color is changed to update the input and swatch, and call 
		// the custom callback function.
		var handler = function(color) {
			callback(color, picker);
			$("#"+id+" input").val(color.toUpperCase());
			$("#"+id+" .colorSwatch").css("backgroundColor", color);
			$("#"+id+" .reset").toggle(!!color);
		}

		// Set up a farbtastic instance inside the picker we've created.
		$.farbtastic(picker, function(color) {
			handler(color);
		}).setColor($("#"+id+" input").val());

		// When the "reset" link is clicked, reset the color.
		$("#"+id+" .reset").click(function(e) {
			e.preventDefault();
			handler("");
		}).toggle(!!$("#"+id+" input").val());
		
	}

	// Turn the "header color" field into a color picker.
	colorPicker("headerColor", function(color, picker) {

		// If no color is selected, use the default one.
		color = color ? color : "#333333";

		// Change the header's background color.
		$("#hdr").css("backgroundColor", color);

		// Unpack this color and convert it to HSL. If the lightness is > 0.5, set the "lightHdr" class on the body.
		var rgb = $.farbtastic(picker).unpack(color);
		var hsl = $.farbtastic(picker).RGBToHSL(rgb);
		$("body").toggleClass("lightHdr", hsl[2] > 0.5);

	});

	// Turn the "body color" field into a color picker.
	colorPicker("bodyColor", function(color, picker) {

		// If no color is selected, use the default one.
		color = color ? color : "#f4f4f4";

		// Change the body's background color.
		$("body").attr("style", "background-color:"+color+" !important");

		// Unpack this color and convert it to HSL. If the lightness is < 0.5, set the "darkBody" class on the body.
		var rgb = $.farbtastic(picker).unpack(color);
		var hsl = $.farbtastic(picker).RGBToHSL(rgb);
		$("body").toggleClass("darkBody", hsl[2] < 0.5);

		// Slightly darken the body color and set it as the border color for the body content area.
		hsl[2] = Math.max(0, hsl[2] - 0.1);
		hsl[1] = Math.min(hsl[1], 0.5);
		var b = $.farbtastic(picker).pack($.farbtastic(picker).HSLToRGB(hsl));
		$("#body-content").css("borderColor", b);

	});

	// Add handlers to the background image checkbox.
	$("#bodyImage input[name=bodyImage]").change(function(e) {
		$("#bodyImage .indent").toggle($(this).prop("checked"));
		if (!$(this).prop("checked")) {
			$("body").css("backgroundImage", "none");
		}
	}).change();
	
});
</script>