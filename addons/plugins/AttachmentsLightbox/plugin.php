<?php
// Copyright Marcel Lange
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

ET::$pluginInfo["AttachmentsLightbox"] = array(
	"name" => "AttachmentsLightbox",
	"description" => "Adds Facybox to Attachements",
	"version" => ESOTALK_VERSION,
	"author" => "Marcel Lange",
	"authorEmail" => "info@bravehartk2.de",
	"authorURL" => "http://bravehartk2.de",
	"license" => "GPLv2"
);

class ETPlugin_AttachmentsLightbox extends ETPlugin {

	// Add the attachments/fineuploader JS/CSS to the conversation view.
	public function handler_conversationController_renderBefore($sender)
	{
		$sender->addJSFile($this->getResource("http://code.jquery.com/jquery-latest.min.js"));
		$sender->addJSFile($this->getResource("lib/jquery.mousewheel-3.0.6.pack.js"));
        $sender->addCSSFile($this->getResource("source/jquery.fancybox.css"));

		$sender->addJSFile($this->getResource("source/jquery.fancybox.pack.js"));

        $sender->addCSSFile($this->getResource("source/helpers/jquery.fancybox-buttons.css"));
        $sender->addJSFile($this->getResource("source/helpers/jquery.fancybox-buttons.js"));
        $sender->addJSFile($this->getResource("source/helpers/jquery.fancybox-media.js"));

        $sender->addCSSFile($this->getResource("source/helpers/jquery.fancybox-thumbs.css"));
        $sender->addJSFile($this->getResource("source/helpers/jquery.fancybox-thumbs.js"));

        $sender->addCSSFile($this->getResource("style.css"));

        $sender->addToHead('
            <script type="text/javascript">
                $(document).ready(function() {
                    $(".fancybox").fancybox();
                });
            </script>
        ');

    }

    // Hook onto ConversationController::formatPostForTemplate and add the attachment/list view to the bottom of each post.
    public function handler_conversationController_formatPostForTemplate($sender, &$formatted, $post, $conversation)
    {
        // If the post has been deleted or has no attachments, stop!
        if ($post["deleteMemberId"] or empty($post["attachments"])) return;

        $view = $sender->getViewContents("lbattachments/list", array("attachments" => $post["attachments"]));

        // Rewrites or ordering would be nice! That here is really bad
        $pos1 = strpos($formatted["body"], "<div class='attachments'");
        if (!$pos1) $pos1 = strlen($formatted["body"]);

        $pos2 = strpos($formatted["body"], "<p class='likes");
        $formatted["body"] = substr_replace($formatted["body"], $view, $pos1, $pos2 - $pos1);
    }
}
