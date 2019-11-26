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

global $it_blog_IDs, $admin_url;

$Form = new Form();

$Form->begin_form( 'fform', TB_('Item Type importer') );

$Form->begin_fieldset( TB_('Report of the import') );

	// Get data to import from wordpress XML file:
	$it_file = get_param( 'import_file' );
	$itxml_import_data = wpxml_get_import_data( $it_file );

	echo '<p>';

	// XML file:
	echo '<b>'.T_('Source XML').':</b> <code>'.$it_file.'</code><br />';

	$it_collections = array();
	if( is_array( $it_blog_IDs ) && ! empty( $it_blog_IDs ) )
	{
		$BlogCache = & get_BlogCache();
		$BlogCache->load_list( $it_blog_IDs );
		foreach( $it_blog_IDs as $it => $it_blog_ID )
		{
			if( $it_Blog = & $BlogCache->get_by_ID( $it_blog_ID, false, false ) )
			{
				$it_collections[] = $it_Blog->get_extended_name();
			}
			else
			{	// Exclude wrong collection:
				unset( $it_blog_IDs[ $it_blog_ID ] );
			}
		}
	}
	if( ! empty( $it_collections ) )
	{
		echo '<b>'.TB_('Enable for collections').':</b> "'.implode( '", "', $it_collections ).'"';
	}
	else
	{
		echo '<b>'.TB_('Don\'t enable for collections').'.</b>';
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
		array( 'button', 'button', TB_('Go to Item Types').' >>', 'SaveButton', 'onclick' => 'location.href=\''.$admin_url.'?ctrl=itemtypes&amp;tab=settings&amp;tab3=types\'' ),
	) );

$Form->end_form();

?>