<?php
/**
 * This file display the 2nd step of WordPress XML importer
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $wp_blog_ID;

$Form = new Form( NULL, '', 'post', NULL, 'multipart/form-data' );

$Form->begin_form( 'fform', T_('WordPress XML Importer') );

$Form->begin_fieldset( T_('Report of the import') );

	$BlogCache = & get_BlogCache();
	$Blog = & $BlogCache->get_by_ID( $wp_blog_ID );
	$Form->info( T_('Collection'), $Blog->get_name() );

	// Import the data and display a report on the screen
	wpxml_import();

$Form->end_fieldset();

$Form->buttons( array(
		array( 'button', 'button', T_('Go to Blog'), 'SaveButton', 'onclick' => 'location.href=\''.$Blog->get( 'url' ).'\'' ),
	) );

$Form->end_form();

?>