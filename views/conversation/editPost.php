<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Shows a page to edit a single post.
 *
 * @package esoTalk
 */

$form = $data["form"];
$post = $data["post"];
?>
<div class='standalone'>
<?php echo $form->open(); ?>

<?php

// Using the provided form object, construct a textarea and buttons.
$body = $form->input("content", "textarea", array("cols" => "200", "rows" => "20"))."
	<div id='p".$post["postId"]."-preview' class='preview'></div>
	<div class='editButtons'>".
	$form->saveButton()." ".
	$form->cancelButton()."</div>";

// Construct an array for use in the conversation/post view.
$post = array(
	"id" => "p".$post["postId"],
	"title" => name($post["username"]),
	"controls" => $data["controls"],
	"class" => "edit",
	"body" => $body,
	"avatar" => avatar($post)
);

$this->trigger("renderEditBox", array(&$post));

$this->renderView("conversation/post", array("post" => $post));

?>

<?php echo $form->close(); ?>
</div>