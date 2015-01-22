<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a login sheet/form.
 *
 * @package esoTalk
 */

$form = $data["form"];
?>

<div id='loginSheet' class='sheet'>
<div class='sheetContent'>

<h3><?php echo T("Log In"); ?></h3>

<?php echo $form->open(); ?>

<div class='sheetBody'>

<?php if (!empty($data["message"])): ?>
<div class='section help'>
<?php echo $data["message"]; ?>
</div>
<?php endif; ?>

<div class='section'>

<ul class='form'>

<?php
// Loop through the form sections (eg. "avatar", "notifications").
foreach ($form->getSections() as $k => $v): ?>

<li><label><?php echo $v; ?></label> <div class='fieldGroup'>
<?php
// Loop through each of the fields in this section and output it.
foreach ($form->getFieldsInSection($k) as $field): ?>

<?php echo $field; ?>

<?php endforeach; ?>
</div></li>

<?php endforeach; ?>

</ul>

</div>

</div>

<div class='buttons'>
<small><?php printf(T("Don't have an account? <a href='%s' class='link-join'>Sign up!</a>"), URL("user/join")); ?></small>
<?php
echo $form->button("login", T("Log In"), array("class" => "big submit"));
echo $form->cancelButton();
?>
</div>

<?php echo $form->close(); ?>

<script>
$(function() {
	$('input[name=username]').focus();
})
</script>

</div>
</div>
