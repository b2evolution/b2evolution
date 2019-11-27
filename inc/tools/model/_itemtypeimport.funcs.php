<?php
/**
 * This file implements the functions to work with WordPress importer.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Import data from Item Type XML file into b2evolution database
 *
 * @param string Path of XML file
 */
function itxml_import( $XML_file_path )
{
	global $DB, $admin_url;

	// Get collections IDs from request:
	$it_blog_IDs = param( 'it_blog_IDs', 'array:integer', NULL );
	if( is_array( $it_blog_IDs ) && ! empty( $it_blog_IDs ) )
	{
		$BlogCache = & get_BlogCache();
		$BlogCache->load_list( $it_blog_IDs );
		foreach( $it_blog_IDs as $it => $it_blog_ID )
		{
			if( ! ( $it_Blog = & $BlogCache->get_by_ID( $it_blog_ID, false, false ) ) )
			{	// Exclude wrong collection:
				unset( $it_blog_IDs[ $it_blog_ID ] );
			}
		}
	}

	// The import type ( skip | update )
	$import_type = param( 'import_type', 'string', 'skip' );

	// Parse Item Type XML file into array:
	echo 'Loading & parsing the XML file...'.'<br />';
	evo_flush();
	$xml_data = itxml_parser( $XML_file_path );
	echo '<ul class="list-default">';
		echo '<li>'.'Memory used by XML parsing (difference between free RAM before loading XML and after)'.': <b>'.bytesreadable( $xml_data['memory']['parsing'] ).'</b></li>';
		echo '<li>'.'Memory used by temporary arrays (difference between free RAM after loading XML and after copying all the various data into temporary arrays)'.': <b>'.bytesreadable( $xml_data['memory']['arrays'] ).'</b></li>';
	echo '</ul>';
	evo_flush();

	$DB->begin();

	/* Import authors */
	$authors = array();
	$authors_IDs = array();
	$authors_links = array();
	if( isset( $xml_data['item_types'] ) && count( $xml_data['item_types'] ) > 0 )
	{
		echo '<b>'.'Importing Item Types...'.' </b><br>';
		evo_flush();

		// Get existing item types:
		$SQL = new SQL( 'Get existing item types before import' );
		$SQL->SELECT( 'ityp_name, ityp_ID' );
		$SQL->FROM( 'T_items__type' );
		$existing_item_types = $DB->get_assoc( $SQL );

		$new_item_types_num = 0;
		$update_item_types_num = 0;
		$skipped_item_types_num = 0;
		$failed_item_types_num = 0;
		foreach( $xml_data['item_types'] as $item_type )
		{
			echo sprintf( 'Importing Item Type: %s', '"'.$item_type['name'].'"' ).'...';

			echo '<ul class="list-default">';

			$is_new_item_type = true;
			if( empty( $existing_item_types[ $item_type['name'] ] ) )
			{	// Create new Item Type in order to insert into DB:
				$ItemType = new ItemType();
				$ItemType->set( 'name', $item_type['name'] );
			}
			elseif( $import_type == 'skip' )
			{	// Skip existing Item Type:
				echo '<li class="text-warning"><span class="label label-warning">WARNING</span> '.sprintf( 'Skipped because we already have an Item Type with the same name <a %s>%s</a>.', 'href="'.$admin_url.'?ctrl=itemtypes&amp;action=edit&amp;ityp_ID='.$existing_item_types[ $item_type['name'] ].'"', $item_type['name'] ).'</li>';
				$skipped_item_types_num++;
				echo '</ul>';
				evo_flush();
				continue;
			}
			else
			{	// Get the existing Item Type in order to update it:
				$ItemTypeCache = & get_ItemTypeCache();
				$ItemType = & $ItemTypeCache->get_by_ID( $existing_item_types[ $item_type['name'] ], false, false );
				$is_new_item_type = false;
			}

			foreach( $item_type as $item_type_field_key => $item_type_field_value )
			{
				if( ! in_array( $item_type_field_key, array( 'ID', 'name', 'custom_fields' ) ) &&
				    property_exists( 'ItemType', $item_type_field_key ) )
				{	// Update only proper itme type field:
					$ItemType->set( $item_type_field_key, $item_type_field_value, true );
				}
			}

			$old_custom_fields = $ItemType->get_custom_fields();

			if( ! empty( $item_type['custom_fields'] ) )
			{	// Import custom fields:
				$ItemType->update_custom_fields = array();
				$ItemType->insert_custom_fields = array();
				foreach( $item_type['custom_fields'] as $custom_field_name => $new_custom_field )
				{
					$custom_field_cols = array(
							'type',
							'label',
							'name',
							'schema_prop',
							'order',
							'note',
							'required',
							'meta',
							'public',
							'format',
							'formula',
							'cell_class',
							'disp_condition',
							'header_class',
							'link',
							'link_nofollow',
							'link_class',
							'line_highlight',
							'green_highlight',
							'red_highlight',
							'description',
							'merge',
						);
					foreach( $custom_field_cols as $custom_field_col )
					{	// Check the imported custom field has all required columns:
						if( ! array_key_exists( $custom_field_col, $new_custom_field ) )
						{	// Skip wrong custom field:
							echo '<li class="text-danger"><span class="label label-danger">ERROR</span> '.sprintf( 'Skip custom field %s because no required property %s.', '<code>'.$custom_field_name.'</code>', '<code>'.$custom_field_col.'</code>' ).'</li>';
							evo_flush();
							continue 2;
						}
					}

					if( isset( $old_custom_fields[ $custom_field_name ] ) )
					{	// If Item Type is found:
						if( $old_custom_fields[ $custom_field_name ]['type'] == $new_custom_field['type'] )
						{	// Update existing custom field only if type is the same:
							$ItemType->update_custom_fields[ $old_custom_fields[ $custom_field_name ]['ID'] ] = $new_custom_field;
							echo '<li>'.sprintf( 'Update custom field %s.', '<code>'.$custom_field_name.'</code>' ).'</li>';
							evo_flush();
						}
						else
						{	// Don't allow to update custom field with a different type:
							echo '<li class="text-danger"><span class="label label-danger">ERROR</span> '.sprintf( 'Cannot update custom field %s because it has type %s and we expect type %s', '<code>'.$custom_field_name.'</code>', '<code>'.$new_custom_field['type'].'</code>', '<code>'.$old_custom_fields[ $custom_field_name ]['type'].'</code>' ).'.</li>';
							evo_flush();
						}
					}
					else
					{	// Insert new custom field:
						$ItemType->insert_custom_fields[] = $new_custom_field;
						echo '<li class="text-success">'.sprintf( 'Add new custom field %s.', '<code>'.$custom_field_name.'</code>' ).'</li>';
						evo_flush();
					}
				}
			}

			if( ! empty( $old_custom_fields ) )
			{	// Find and delete old custom fields if they are not present in the imported Item Type:
				$deleted_custom_fields = array();
				$ItemType->delete_custom_fields = array();
				foreach( $old_custom_fields as $old_custom_field_key => $old_custom_field )
				{
					if( ! isset( $item_type['custom_fields'][ $old_custom_field_key ] ) )
					{	// Delete custom field from DB:
						$deleted_custom_fields[] = $old_custom_field_key;
						$ItemType->delete_custom_fields[] = $old_custom_field['ID'];
					}
				}
				if( ! empty( $deleted_custom_fields ) )
				{	// Display what custom fields will be deleted:
					echo '<li class="text-warning"><span class="label label-warning">WARNING</span> '.sprintf( 'Delete custom fields: %s.', '<code>'.implode( '</code>, <code>', $deleted_custom_fields ).'</code>' ).'</li>';
					evo_flush();
				}
			}

			if( $ItemType && $ItemType->dbsave() )
			{	// If Item Type is added/updated successfully:
				if( ! empty( $it_blog_IDs ) )
				{	// Enable the Item Type for the selected Collections:
					$it_colls_insert_sql_values = array();
					foreach( $it_blog_IDs as $it_blog_ID )
					{
						$it_colls_insert_sql_values[] = '( '.$DB->quote( $ItemType->ID ).', '.$DB->quote( $it_blog_ID ).' )';
					}
					$DB->query( 'REPLACE INTO T_items__type_coll
						       ( itc_ityp_ID, itc_coll_ID )
						VALUES '.implode( ', ', $it_colls_insert_sql_values ) );
				}

				// Log success result:
				if( $is_new_item_type )
				{
					$new_item_types_num++;
					echo '<li class="text-success">Item Type added.</li>';
				}
				else
				{
					$update_item_types_num++;
					echo '<li class="text-info">Item Type updated.</li>';
				}
			}
			else
			{	// Log failed result:
				$failed_item_types_num++;
				echo '<li class="text-danger">'.sprintf( 'Item Type %s could not be imported.', '<code>'.$item_type['name'].'</code>' ).'</li>';
			}

			echo '</ul>';
			evo_flush();
		}

		if( $new_item_types_num )
		{
			echo '<b class="text-success">'.sprintf( '%d new Item Types', $new_item_types_num ).'</b><br />';
		}
		if( $update_item_types_num )
		{
			echo '<b class="text-info">'.sprintf( '%d updated Item Types', $update_item_types_num ).'</b><br />';
		}
		if( $skipped_item_types_num )
		{
			echo '<b class="text-warning">'.sprintf( '%d skipped Item Types', $skipped_item_types_num ).'</b><br />';
		}
		if( $failed_item_types_num )
		{
			echo '<b class="text-danger">'.sprintf( '%d Item Types could not be imported', $failed_item_types_num ).'</b>';
		}
	}

	echo '<br><p class="text-success">'.'Import complete.'.'</p>';

	$DB->commit();
}


/**
 * Parse WordPress XML file into array
 *
 * @param string File path
 * @return array XML data:
 *          item_type
 *          base_url
 *          app_version
 *          memory
 */
function itxml_parser( $file )
{
	$item_types = array();
	$memory = array();

	// Register filter to avoid wrong chars in XML content:
	stream_filter_register( 'xmlutf8', 'ValidUTF8XMLFilter' );

	// Start to get amount of memory for parsing:
	$memory_usage = memory_get_usage();

	// Load XML content from file with xmlutf8 filter:
	$xml = simplexml_load_file( 'php://filter/read=xmlutf8/resource='.$file );

	// Store here what memory was used for XML parsing:
	$memory['parsing'] = memory_get_usage() - $memory_usage;

	// Get WXR version:
	$app_version = $xml->xpath( '/rss/channel/evo:app_version' );
	$app_version = isset( $app_version[0] ) ? (string) trim( $app_version[0] ) : '';

	$base_url = $xml->xpath( '/rss/channel/evo:base_site_url' );
	$base_url = isset( $base_url[0] ) ? (string) trim( $base_url[0] ) : '';

	$namespaces = $xml->getDocNamespaces();
	if( !isset( $namespaces['evo'] ) )
	{
		$namespaces['evo'] = 'http://b2evolution.net/export/2.0/';
	}

	// Start to get amount of memory for temporary arrays:
	$memory_usage = memory_get_usage();

	// Get item types:
	foreach( $xml->xpath( '/rss/channel/evo:itemtype' ) as $item_type_data )
	{
		$evo = (array)$item_type_data->children( $namespaces['evo'] );

		$item_type = array();
		foreach( $evo as $evo_key => $evo_data )
		{
			if( is_array( $evo_data ) )
			{	// Skip array data like custom field:
				continue;
			}
			$item_type[ $evo_key ] = (string) $evo_data;
		}

		if( isset( $evo['customfield'] ) )
		{
			$item_type['custom_fields'] = array();
			foreach( $evo['customfield'] as $custom_field_data )
			{	// Get the custom fields:
				$custom_field_data = (array) $custom_field_data;
				foreach( $custom_field_data as $custom_field_key => $custom_field_value )
				{
					$item_type['custom_fields'][ $custom_field_data['name'] ][ $custom_field_key ] = ( string )$custom_field_value;
				}
			}
		}

		$item_types[] = $item_type;
	}

	// Store here what memory was used for temporary arrays:
	$memory['arrays'] = memory_get_usage() - $memory_usage;

	return array(
		'item_types'  => $item_types,
		'base_url'    => $base_url,
		'app_version' => $app_version,
		'memory'      => $memory,
	);
}
?>