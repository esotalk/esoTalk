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
<h1><?php echo T("Specify Setup Information"); ?></h1>

<?php echo $form->open(); ?>

<?php printf(T("message.installerWelcome"), "http://esotalk.org/forum"); ?>
<hr/>

<ul class='form'>
<li><label><?php echo T("Forum title"); ?></label> <?php echo $form->input("forumTitle", "text", array("placeholder" => "Simon's Great Forum")); ?></li>
</ul>

<hr/>

<p class='msg plain'><?php echo T("message.installerMySQLHelp"); ?></p>


<ul class='form'>
<li><label><?php echo T("MySQL host address"); ?></label> <?php echo $form->input("mysqlHost", "text", array("placeholder" => "localhost")); ?></li>
<li><label><?php echo T("MySQL username"); ?></label> <?php echo $form->input("mysqlUser", "text", array("placeholder" => "simon")); ?></li>
<li><label><?php echo T("MySQL password"); ?></label> <?php echo $form->input("mysqlPass", "password"); ?></li>
<li><label><?php echo T("MySQL database"); ?></label> <?php echo $form->input("mysqlDB", "text", array("placeholder" => "esotalk")); ?></li>
<li><?php echo $form->getError("mysql"); ?></li>
</ul>

<hr/>

<p class='msg plain'><?php echo T("message.installerAdminHelp"); ?></p>

<ul class='form'>
<li><label><?php echo T("Administrator username"); ?></label> <?php echo $form->input("adminUser", "text", array("placeholder" => "Simon")); ?></li>
<li><label><?php echo T("Administrator email"); ?></label> <?php echo $form->input("adminEmail", "text", array("placeholder" => "simon@example.com")); ?></li>
<li><label><?php echo T("Administrator password"); ?></label> <?php echo $form->input("adminPass", "password"); ?></li>
<li><label><?php echo T("Confirm password"); ?></label> <?php echo $form->input("adminConfirm", "password"); ?></li>
</ul>

<br/>

<a href='#advanced' id='advancedLink'><?php echo T("Advanced options"); ?></a>

<hr/>

<div id='advanced'>

<ul class='form'>
<li><label><?php echo T("MySQL table prefix"); ?></label> <?php echo $form->input("tablePrefix"); ?></li>
<li><label><?php echo T("Base URL"); ?></label> <?php echo $form->input("baseURL"); ?></li>
<li><label><?php echo T("Use friendly URLs"); ?></label> <?php echo $form->checkbox("friendlyURLs"); ?></li>
</ul>

<hr/>

</div>

<script>
$(function() {
	$("#advancedLink").click(function(e) {
		e.preventDefault();
		$("#advanced").slideToggle("fast");
	});
	<?php if (empty($form->errors["tablePrefix"])): ?>$("#advanced").hide();<?php endif; ?>
});
</script>

<p><?php echo $form->button("submit", T("Install My Forum")." &#155;", array("class" => "big")); ?></p>

<?php echo $form->close(); ?>