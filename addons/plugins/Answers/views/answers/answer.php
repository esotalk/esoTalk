<?php
// Copyright 2013 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

$conversation = $data["conversation"];
$post = $data["answer"];
?>
<div class='answer thing hasControls'>
	<div class='postHeader'>
		<div class='info'>
			<h3><i class="icon-ok-sign"></i> <?php printf(T("Answered by %s"), memberLink($post["memberId"], $post["username"])); ?></h3>
			<a href='<?php echo URL(postURL($post["postId"])); ?>' rel='post' data-id='<?php echo $post["postId"]; ?>'><?php echo T("See post in context"); ?></a>
		</div>
		<div class='controls'>
			<?php if ($conversation["startMemberId"] == ET::$session->userId): ?>
			<a href='<?php echo URL("conversation/unanswer/".$conversation["conversationId"]."?token=".ET::$session->token); ?>' title='<?php echo T("Remove answer"); ?>' class='control-unanswer'><i class='icon-remove'></i></a>
			<?php endif; ?>
		</div>
	</div>

	<div class='postBody'>
		<?php
		$words = ET::$session->get("highlight");
		echo ET::formatter()->init($post["content"])->highlight($words)->format()->get();
		?>
	</div>
</div>