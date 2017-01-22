<?php
/**
 * Chars Tests
 *
 * b2evolution - {@link http://b2evolution.net/}
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package tests
 */
require_once(dirname(__FILE__).'/../conf/_config.php');
require_once(dirname(__FILE__).'/../'.$core_subdir.'_main.inc.php');

$test = "<strong>H� 'man\"!</strong> � &lt;fake & &wrong &eacute;&gt;";
echo '[',$test,"] => \n";
echo 'html: [', convert_chars( $test, 'html' ), "]\n";
echo 'xml: [', convert_chars( $test, 'xml' ), "]\n";

$php_trans = array_flip( get_html_translation_table(HTML_ENTITIES) );

// pre_dump( $b2_htmltrans );
// Chech that we do at least all translations PHP would do
foreach( $php_trans as $entity => $uref )
{
	if( !isset( $b2_htmltrans[$entity] ) )
	{
		echo 'Not set: ', $entity, '->',  $uref, "<br />\n";
	}
}

pre_dump( $b2_htmltrans );

?>