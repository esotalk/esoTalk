<?php
// Copyright 2013 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

?>
<div class='attachments'>
    <h4><span>Attachments</span></h4>
    <?php $images = array()?>
    <?php $others = array()?>
    <?php
    foreach ($data["attachments"] as $attachment){
        #add to images if it is one
        if (preg_match('/(.*)\.(jpg|gif|png)+/Ui', $attachment["filename"])){
            $images[$attachment["attachmentId"] ] = $attachment["filename"];
        }else{
            $others[$attachment["attachmentId"] ] = $attachment["filename"];
        }
    }?>
    <?php if(count($images)):?>
        <div class="clearfix">
            <?php foreach($images as $id => $filename):?>
                <a class="fancybox" rel="grouping_attachemants"  href='<?php echo URL("attachment/" . $id . "_" . $filename); ?>'>
                    <img width="50" src="<?php echo URL("attachment/" . $id . "_" . $filename); ?>" title="<?php echo $filename ?>"
                         alt="<?php echo $filename ?>" />
                </a>
            <?php endforeach;?>
        </div>
    <?php endif?>
    <?php if (count($others)):?>
        <ul>
            <?php foreach ($others as $id => $filename): ?>
                <li>
                    <a href='<?php echo URL("attachment/" . $id . "_" . $filename); ?>'>
                        <?php echo $filename; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif;?>
</div>