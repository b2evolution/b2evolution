<?php
/**
 * spellcheck.php
 *
 * Copyright, Moxiecode Systems AB
 * Released under LGPL License.
 *
 * License: http://www.tinymce.com/license
 * Contributing: http://www.tinymce.com/contributing
 */

require('./includes/Engine.php');
require('./includes/EnchantEngine.php');
require('./includes/PSpellEngine.php');

$tinymceSpellCheckerConfig = array(
	"engine" => "enchant", // enchant, pspell

	// Enchant options
	"enchant_dicts_path" => "./dicts",

	// PSpell options
	"pspell.mode" => "fast",
	"pspell.spelling" => "",
	"pspell.jargon" => "",
	"pspell.encoding" => ""
);

TinyMCE_Spellchecker_Engine::processRequest($tinymceSpellCheckerConfig);
?>