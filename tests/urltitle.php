<?php
/**
 * XML-RPC Tests
 *
 * b2evolution - {@link http://b2evolution.net/}
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package tests
 */
require_once(dirname(__FILE__).'/../blogs/conf/_config.php');
require_once(dirname(__FILE__).'/../blogs/'.$core_subdir.'_main.inc.php');

$test = " :: çà c'est \"VRAIMENT\" tôa! ";
echo '<p>[',$test,'] => [', urltitle_validate( '  ', $test ), ']</p>';

$test = "La subtile différence entre acronym et abbr..._452";
echo '<p>[',$test,'] => [', urltitle_validate( '  ', $test ), ']</p>';


?>