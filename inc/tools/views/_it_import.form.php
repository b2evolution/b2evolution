<?php
/**
 * This file display the 2nd step of Item Type importer
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $it_blog_ID;

$Form = new Form();

$Form->begin_form( 'fform', TB_('Item Type importer') );

$Form->begin_fieldset( TB_('Report of the import') );

	// Get data to import from wordpress XML file:
	$it_file = get_param( 'import_file' );
	$itxml_import_data = wpxml_get_import_data( $it_file );

	echo '<p>';

	// XML file:
	echo '<b>'.T_('Source XML').':</b> <code>'.$it_file.'</code><br />';

	$BlogCache = & get_BlogCache();
	if( $Blog = & $BlogCache->get_by_ID( $it_blog_ID, false, false ) )
	{
		echo '<b>'.TB_('Enable for collection').':</b> '.$Blog->dget( 'shortname' ).' &ndash; '.$Blog->dget( 'name' );
	}
	else
	{
		echo '<b>'.TB_('Don\'t enable for collection').'.</b>';
	}

	echo '</p>';

	if( $itxml_import_data['errors'] === false )
	{	// Import the data and display a report on the screen:
		itxml_import( $itxml_import_data['XML_file_path'] );
	}
	else
	{	// Display errors if import cannot be done:
		echo $itxml_import_data['errors'];
		echo '<br /><p class="text-danger">'.TB_('Import failed.').'</p>';
	}

$Form->end_fieldset();

$Form->buttons( array(
		array( 'button', 'button', TB_('Go to collection').' >>', 'SaveButton', 'onclick' => 'location.href=\''.$Blog->get( 'url' ).'\'' ),
	) );

$Form->end_form();

?>