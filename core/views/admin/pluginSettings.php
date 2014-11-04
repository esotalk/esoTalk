<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Displays a sheet which includes a plugin's settings view.
 *
 * @package esoTalk
 */

$plugin = $data["plugin"];
$view = $data["view"];
?>
<div class='sheet' id='pluginSettingsSheet'>
<div class='sheetContent'>

<h3><?php printf(T("%s Settings"), $plugin["info"]["name"]); ?></h3>

<?php $this->renderView($view, $data); ?>

</div>
</div>
