<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays the esoTalk installation form.
 *
 * @package esoTalk
 */

$form = $data["form"];
?>
<h1><?php echo T("Welcome to esoTalk"); ?></h1>
<h2><?php printf(T("message.installerWelcome"), "http://esotalk.org/docs/debug"); ?></h2>

<?php echo $form->open(); ?>

<div class='details'>

	<ul class='form'>
		<li><?php echo $form->input("forumTitle", "text", array("placeholder" => T("Forum title"))); ?></li>
		<li class='advanced'><?php echo $form->input("baseURL", "text", array("placeholder" => T("Base URL"))); ?></li>
		<li class='advanced'><label><?php echo $form->checkbox("friendlyURLs"); ?> <?php echo T("Use friendly URLs"); ?></label></li>
	</ul>

	<br>

	<ul class='form'>
		<li class='half'><?php echo $form->input("mysqlHost", "text", array("placeholder" => T("MySQL Host"))); ?></li>
		<li class='half'><?php echo $form->input("mysqlUser", "text", array("placeholder" => T("MySQL Username"))); ?></li>
		<li class='half clear'><?php echo $form->input("mysqlPass", "password", array("placeholder" => T("MySQL Password"))); ?></li>
		<li class='half'><?php echo $form->input("mysqlDB", "text", array("placeholder" => T("MySQL Database"))); ?></li>
		<li class='advanced clear'><?php echo $form->input("tablePrefix", "text", array("placeholder" => T("MySQL Table Prefix"))); ?></li>
		<li class='clear'><?php echo $form->getError("mysql"); ?></li>
	</ul>

	<br>

	<ul class='form'>
		<li class='half'><?php echo $form->input("adminUser", "text", array("placeholder" => T("Admin Username"))); ?></li>
		<li class='half'><?php echo $form->input("adminEmail", "text", array("placeholder" => T("Admin Email"))); ?></li>
		<li class='half clear'><?php echo $form->input("adminPass", "password", array("placeholder" => T("Admin Password"))); ?></li>
		<li class='half'><?php echo $form->input("adminConfirm", "password", array("placeholder" => T("Confirm Password"))); ?></li>
	</ul>

	<br>

	<ul class='form' style='text-align:center'>
		<li><?php echo $form->button("submit", T("Install esoTalk")." &#155;", array("class" => "submit")); ?></li>
		<li><a href='#advanced' id='advancedLink'><?php echo T("Advanced Options"); ?></a></li>
	</ul>

	<script>
	$(function() {
		$("#advancedLink").click(function(e) {
			e.preventDefault();
			$(".advanced").slideToggle("fast");
		});
		<?php if (empty($form->errors["tablePrefix"])): ?>$(".advanced").hide();<?php endif; ?>
	});
	</script>

	<?php echo $form->close(); ?>
</div>
