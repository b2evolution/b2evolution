<?php
/**
 * This file display the 2nd step of WordPress XML importer
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $wp_blog_ID, $dispatcher;

$Form = new Form( NULL, '', 'post', NULL, 'multipart/form-data' );

$Form->begin_form( 'fform', T_('WordPress XML Importer') );

$Form->begin_fieldset( T_('Report of the import') );

	$BlogCache = & get_BlogCache();
	$Blog = & $BlogCache->get_by_ID( $wp_blog_ID );
	$Form->info( T_('Blog'), $Blog->get_name() );

	// Import the data and display a report on the screen
	wpxml_import();

$Form->end_fieldset();

$Form->buttons( array(
		array( 'button', 'button', T_('Go to Blog'), 'SaveButton', 'onclick' => 'location.href="'.$Blog->get( 'url' ).'"' ),
		array( 'button', 'button', T_('Back'), 'SaveButton', 'onclick' => 'location.href="'.$dispatcher.'?ctrl=wpimportxml"' )
	) );

$Form->end_form();

?>