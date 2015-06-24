<?php
/**
 * This file implements the Post Type class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * ItemType Class
 *
 * @package evocore
 */
class ItemType extends DataObject
{
	var $name;
	var $description;
	var $backoffice_tab;
	var $template_name;
	var $use_title = 'required';
	var $use_url = 'optional';
	var $use_text = 'optional';
	var $allow_html = 1;
	var $allow_attachments = 1;
	var $use_excerpt = 'optional';
	var $use_title_tag = 'optional';
	var $use_meta_desc = 'optional';
	var $use_meta_keywds = 'optional';
	var $use_tags = 'optional';
	var $allow_featured = 1;
	var $use_country = 'never';
	var $use_region = 'never';
	var $use_sub_region = 'never';
	var $use_city = 'never';
	var $use_coordinates = 'never';
	var $use_custom_fields = 1;
	var $use_comments = 1;
	var $allow_closing_comments = 1;
	var $allow_disabling_comments = 0;
	var $use_comment_expiration = 'optional';

	/**
	 * Custom fields
	 *
	 * @var array
	 */
	var $custom_fields = NULL;

	/**
	 * What fields should be updated/inserted/deleted
	 *
	 * @var array
	 */
	var $update_custom_fields = NULL;
	var $insert_custom_fields = NULL;
	var $delete_custom_fields = NULL;


	/**
	 * Constructor
	 *
	 *
	 * @param table Database row
	 */
	function ItemType( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_items__type', 'ityp_', 'ityp_ID' );

		// Allow inseting specific IDs
		$this->allow_ID_insert = true;

		if( $db_row != NULL )
		{
			$this->ID   = $db_row->ityp_ID;
			$this->name = $db_row->ityp_name;
			$this->description = $db_row->ityp_description;
			$this->backoffice_tab = $db_row->ityp_backoffice_tab;
			$this->template_name = $db_row->ityp_template_name;
			$this->use_title = $db_row->ityp_use_title;
			$this->use_url = $db_row->ityp_use_url;
			$this->use_text = $db_row->ityp_use_text;
			$this->allow_html = $db_row->ityp_allow_html;
			$this->allow_attachments = $db_row->ityp_allow_attachments;
			$this->use_excerpt = $db_row->ityp_use_excerpt;
			$this->use_title_tag = $db_row->ityp_use_title_tag;
			$this->use_meta_desc = $db_row->ityp_use_meta_desc;
			$this->use_meta_keywds = $db_row->ityp_use_meta_keywds;
			$this->use_tags = $db_row->ityp_use_tags;
			$this->allow_featured = $db_row->ityp_allow_featured;
			$this->use_country = $db_row->ityp_use_country;
			$this->use_region = $db_row->ityp_use_region;
			$this->use_sub_region = $db_row->ityp_use_sub_region;
			$this->use_city = $db_row->ityp_use_city;
			$this->use_coordinates = $db_row->ityp_use_coordinates;
			$this->use_custom_fields = $db_row->ityp_use_custom_fields;
			$this->use_comments = $db_row->ityp_use_comments;
			$this->allow_closing_comments = $db_row->ityp_allow_closing_comments;
			$this->allow_disabling_comments = $db_row->ityp_allow_disabling_comments;
			$this->use_comment_expiration = $db_row->ityp_use_comment_expiration;
		}
	}


	/**
	 * Get delete restriction settings
	 *
	 * @return array
	 */
	static function get_delete_restrictions()
	{
		return array(
				array( 'table'=>'T_items__item', 'fk'=>'post_ityp_ID', 'msg'=>T_('%d related items') ), // "Lignes de visit reports"
			);
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// get new ID
		if( param( 'new_ityp_ID', 'string', NULL ) !== NULL )
		{
			param_check_number( 'new_ityp_ID', T_('ID must be a number.'), true );
			$this->set_from_Request( 'ID', 'new_ityp_ID' );
		}

		// Name
		if( ! $this->is_special() )
		{ // Update the name only of not special post types
			param_string_not_empty( 'ityp_name', T_('Please enter a name.') );
			$this->set_from_Request( 'name' );
		}

		// Description
		param( 'ityp_description', 'text' );
		$this->set_from_Request( 'description', NULL, true );

		// Back-office tab
		param( 'ityp_backoffice_tab', 'string' );
		$this->set_from_Request( 'backoffice_tab', NULL, true );

		// Template name
		param( 'ityp_template_name', 'string' );
		$this->set_from_Request( 'template_name', NULL, true );

		// Use title
		param( 'ityp_use_title', 'string' );
		$this->set_from_Request( 'use_title' );

		// Use URL
		param( 'ityp_use_url', 'string' );
		$this->set_from_Request( 'use_url' );

		// Use text
		param( 'ityp_use_text', 'string' );
		$this->set_from_Request( 'use_text' );

		// Allow HTML
		param( 'ityp_allow_html', 'integer', 0 );
		$this->set_from_Request( 'allow_html' );

		// Allow attachments
		param( 'ityp_allow_attachments', 'integer', 0 );
		$this->set_from_Request( 'allow_attachments' );

		// Use excerpt
		param( 'ityp_use_excerpt', 'string' );
		$this->set_from_Request( 'use_excerpt' );

		// Use title tag
		param( 'ityp_use_title_tag', 'string' );
		$this->set_from_Request( 'use_title_tag' );

		// Use meta description
		param( 'ityp_use_meta_desc', 'string' );
		$this->set_from_Request( 'use_meta_desc' );

		// Use meta keywords
		param( 'ityp_use_meta_keywds', 'string' );
		$this->set_from_Request( 'use_meta_keywds' );

		// Use tags
		param( 'ityp_use_tags', 'string' );
		$this->set_from_Request( 'use_tags' );

		// Allow featured
		param( 'ityp_allow_featured', 'integer', 0 );
		$this->set_from_Request( 'allow_featured' );

		// Use country, region, sub-region, city:
		$use_country = param( 'ityp_use_country', 'string', 'never' );
		$use_region = param( 'ityp_use_region', 'string', 'never' );
		$use_sub_region = param( 'ityp_use_sub_region', 'string', 'never' );
		$use_city = param( 'ityp_use_city', 'string', 'never' );
		if( $use_city == 'required' )
		{ // If city is required - all location fields also are required
			$use_country = $use_region = $use_sub_region = 'required';
		}
		else if( $use_sub_region == 'required' )
		{ // If subregion is required - country & region fields also are required
			$use_country = $use_region = 'required';
		}
		else if( $use_region == 'required' )
		{ // If region is required - country field also is required
			$use_country = 'required';
		}
		$this->set( 'use_country', $use_country );
		$this->set( 'use_region', $use_region );
		$this->set( 'use_sub_region', $use_sub_region );
		$this->set( 'use_city', $use_city );

		// Use coordinates
		param( 'ityp_use_coordinates', 'string' );
		$this->set_from_Request( 'use_coordinates' );

		// Use custom fields
		param( 'ityp_use_custom_fields', 'integer', 0 );
		$this->set_from_Request( 'use_custom_fields' );

		// Use comments
		param( 'ityp_use_comments', 'integer', 0 );
		$this->set_from_Request( 'use_comments' );

		// Allow closing comments
		param( 'ityp_allow_closing_comments', 'integer', 0 );
		$this->set_from_Request( 'allow_closing_comments' );

		// Allow disabling comments
		param( 'ityp_allow_disabling_comments', 'integer', 0 );
		$this->set_from_Request( 'allow_disabling_comments' );

		// Use comment expiration
		param( 'ityp_use_comment_expiration', 'string' );
		$this->set_from_Request( 'use_comment_expiration' );

		// Load custom fields from request
		$this->load_custom_fields_from_Request();

		return ! param_errors_detected();
	}


	/**
	 * Load custom fields from request
	 */
	function load_custom_fields_from_Request()
	{
		global $Messages;

		// Initialize the arrays
		$this->update_custom_fields = array();
		$this->insert_custom_fields = array();
		$this->delete_custom_fields = trim( param( 'deleted_custom_double', 'string', '' ).','.param( 'deleted_custom_varchar', 'string', '' ), ',' );
		$this->delete_custom_fields = empty( $this->delete_custom_fields ) ? array() : explode( ',', $this->delete_custom_fields );

		// Field names array is used to check the diplicates
		$field_names = array();

		// Empty and Initialize the custom fields from POST data
		$this->custom_fields = array();

		foreach( array( 'double', 'varchar' ) as $type )
		{
			$empty_title_error = false; // use this to display empty title fields error message only ones
			$custom_field_count = param( 'count_custom_'.$type, 'integer', 0 ); // all custom fields count ( contains even deleted fields )

			for( $i = 1 ; $i <= $custom_field_count; $i++ )
			{
				$custom_field_ID = param( 'custom_'.$type.'_ID'.$i, '/^[a-z0-9\-_]+$/', NULL );
				if( empty( $custom_field_ID ) || in_array( $custom_field_ID, $this->delete_custom_fields ) )
				{ // This field was deleted, don't neeed to update
					continue;
				}

				$custom_field_label = param( 'custom_'.$type.'_'.$i, 'string', NULL );
				$custom_field_name = param( 'custom_'.$type.'_fname'.$i, '/^[a-z0-9\-_]+$/', NULL );
				$custom_field_order = param( 'custom_'.$type.'_order'.$i, 'integer', NULL );
				$custom_field_is_new = param( 'custom_'.$type.'_new'.$i, 'integer', 0 );

				// Add each new/existing custom field in this array
				// in order to see all them on the form when post type is not updated because some errors
				$this->custom_fields[] = array(
						'temp_i'  => $i, // Used only on submit form to know the number of the field on the form
						'ID'      => $custom_field_ID,
						'ityp_ID' => $this->ID,
						'label'   => $custom_field_label,
						'name'    => $custom_field_name,
						'type'    => $type,
						'order'   => $custom_field_order,
					);

				if( empty( $custom_field_label ) )
				{ // Field title can't be emtpy
					if( ! $empty_title_error )
					{ // This message was not displayed yet
						$Messages->add( T_('Custom field titles can\'t be empty!') );
						$empty_title_error = true;
					}
				}
				elseif( empty( $custom_field_name ) )
				{ // Field identical name can't be emtpy
					$Messages->add( sprintf( T_('Please enter name for custom field "%s"'), $custom_field_label ) );
				}
				elseif( in_array( $custom_field_name, $field_names ) )
				{ // Field name must be identical
					$Messages->add( sprintf( T_('The field name "%s" is not identical, please use another.'), $custom_field_name ) );
				}
				else
				{
					$field_names[] = $custom_field_name;
				}
				if( $custom_field_is_new )
				{ // Insert custom field
					$this->insert_custom_fields[ $custom_field_ID ] = array(
						'type'  => $type,
						'name'  => $custom_field_name,
						'label' => $custom_field_label,
						'order' => $custom_field_order );
				}
				else
				{ // Update custom field
					$this->update_custom_fields[ $custom_field_ID ] = array(
						'type'  => $type,
						'name'  => $custom_field_name,
						'label' => $custom_field_label,
						'order' => $custom_field_order );
				}
			}
		}
	}

	/**
	 * Get the name of the ItemType
	 * @return string
	 */
	function get_name()
	{
		return $this->name;
	}

	/**
	 * Check existence of specified post type ID in ityp_ID unique field.
	 *
	 * @return int ID if post type exists otherwise NULL/false
	 */
	function dbexists()
	{
		global $DB;

		$sql = "SELECT $this->dbIDname
						  FROM $this->dbtablename
					   WHERE $this->dbIDname = $this->ID";

		return $DB->get_var( $sql );
	}


	/**
	 * Insert object into DB based on previously recorded changes.
	 */
	function dbinsert()
	{
		global $DB;

		$DB->begin();

		if( parent::dbinsert() )
		{
			// Update/Insert/Delete custom fields
			$this->dbsave_custom_fields();
		}

		$DB->commit();
	}


	/**
	 * Update the DB based on previously recorded changes
	 */
	function dbupdate()
	{
		global $DB;

		$DB->begin();

		parent::dbupdate();

		// Update/Insert/Delete custom fields
		$this->dbsave_custom_fields();

		$DB->commit();
	}


	/**
	 * Update the DB based on previously recorded changes
	 */
	function dbdelete()
	{
		global $DB;

		$DB->begin();

		$item_ID = $this->ID;

		if( parent::dbdelete() )
		{
			// Delete all custom fields of this post type
			$DB->query( 'DELETE FROM T_items__type_custom_field
				WHERE itcf_ityp_ID = '.$item_ID );
		}

		$DB->commit();
	}


	/**
	 * Update/Insert/Delete custom fields
	 */
	function dbsave_custom_fields()
	{
		global $DB;

		if( ! empty( $this->insert_custom_fields ) )
		{ // Insert new custom fields
			$sql_data = array();
			foreach( $this->insert_custom_fields as $itcf_ID => $custom_field )
			{
				$DB->query( 'INSERT INTO T_items__type_custom_field ( itcf_ityp_ID, itcf_label, itcf_name, itcf_type, itcf_order )
					VALUES ( '.$DB->quote( $this->ID ).', '
						.$DB->quote( $custom_field['label'] ).', '
						.$DB->quote( $custom_field['name'] ).', '
						.$DB->quote( $custom_field['type'] ).', '
						.( empty( $custom_field['order'] ) ? 'NULL' : $DB->quote( $custom_field['order'] ) ).' )' );
			}
		}

		if( ! empty( $this->update_custom_fields ) )
		{ // Update custom fields
			foreach( $this->update_custom_fields as $itcf_ID => $custom_field )
			{
				$DB->query( 'UPDATE T_items__type_custom_field
					SET
						itcf_label = '.$DB->quote( $custom_field['label'] ).',
						itcf_name = '.$DB->quote( $custom_field['name'] ).',
						itcf_order = '.( empty( $custom_field['order'] ) ? 'NULL' : $DB->quote( $custom_field['order'] ) ).'
					WHERE itcf_ityp_ID = '.$DB->quote( $this->ID ).'
						AND itcf_ID = '.$DB->quote( $itcf_ID ).'
						AND itcf_type = '.$DB->quote( $custom_field['type'] ) );
			}
		}

		if( ! empty( $this->delete_custom_fields ) )
		{ // Delete custom fields
			$sql_data = array();
			foreach( $this->delete_custom_fields as $itcf_ID )
			{
				$sql_data[] = '( itcf_ityp_ID = '.$DB->quote( $this->ID ).' AND itcf_ID = '.$DB->quote( $itcf_ID ).' )';
			}
			$DB->query( 'DELETE FROM T_items__type_custom_field
				WHERE '.implode( ' OR ', $sql_data ) );
		}
	}


	/**
	 *  Returns array, which determinate the lower and upper limit of protected ID's
	 *
	 *  @return array
	 */
	function get_special_range()
	{
		return array( 1000, 5000 );
	}


	/**
	 * Check if this post type is special( reserved in system )
	 *
	 * @param integer Use this param ID of post type when object is not created
	 * @return boolean
	 */
	function is_special( $ID = NULL )
	{
		$special_range = ItemType::get_special_range();

		if( $ID === NULL )
		{ // Get ID of this object
			$ID = $this->ID;
		}

		return $ID >= $special_range[0] && $ID <= $special_range[1];
	}


	/**
	 * Check if this post type is reserved
	 *
	 * @param integer Use this param ID of post type when object is not created
	 * @return boolean
	 */
	function is_reserved( $ID = NULL )
	{
		global $posttypes_reserved_IDs;

		if( $ID === NULL )
		{ // Get ID of this object
			$ID = $this->ID;
		}

		return in_array( $ID, $posttypes_reserved_IDs );
	}


	/**
	 * Returns array, which determinate what post types are defaults of the blogs
	 *
	 * @return array ( key => Blog ID, value => ItemType ID )
	 */
	static function get_default_ids()
	{
		global $DB;

		// Get default value of blog setting "default_post_type"
		load_class( 'collections/model/_collsettings.class.php', 'CollectionSettings' );
		$CollectionSettings = new CollectionSettings();
		$item_types['default'] = $CollectionSettings->get_default( 'default_post_type' );

		// Get default post type of each blog
		$SQL = new SQL();
		$SQL->SELECT( 'cset_coll_ID, cset_value' );
		$SQL->FROM( 'T_coll_settings' );
		$SQL->WHERE( 'cset_name = "default_post_type"' );
		$item_types += $DB->get_assoc( $SQL->get() );

		return $item_types;
	}


	/**
	 * Get the custom feilds
	 *
	 * @param string Type of custom field: 'all', 'varchar', 'double'
	 * @param string Field name that is used as key of array: 'ID', 'ityp_ID', 'label', 'name', 'type', 'order'
	 * @return array Custom fields
	 */
	function get_custom_fields( $type = 'all', $array_key = 'name' )
	{
		if( ! isset( $this->custom_fields ) )
		{ // Initialize an array only first time
			if( empty( $this->ID ) )
			{ // Set an empty array for new creating post type
				$this->custom_fields = array();
			}
			else
			{ // Get the custom fields from DB
				global $DB;
				$SQL = new SQL();
				$SQL->SELECT( 'itcf_ID AS ID, itcf_ityp_ID AS ityp_ID, itcf_label AS label, itcf_name AS name, itcf_type AS type, itcf_order AS `order`' );
				$SQL->FROM( 'T_items__type_custom_field' );
				$SQL->WHERE( 'itcf_ityp_ID = '.$DB->quote( $this->ID ) );
				$SQL->ORDER_BY( 'itcf_order, itcf_ID' );
				$this->custom_fields = $DB->get_results( $SQL->get(), ARRAY_A );
			}
		}

		$custom_fields = array();
		foreach( $this->custom_fields as $custom_field )
		{
			if( $type == 'all' || $type == $custom_field['type'] )
			{
				switch( $array_key )
				{
					case 'name':
						// Use field 'name' as key of array
						if( empty( $custom_field['name'] ) )
						{ // Name can be empty when we are saving it with empty name and page is displayed with error messages
							$field_index = $custom_field['ID'];
						}
						else
						{ // Get field index from name
							$field_index = preg_replace( '/\s+/', '_', strtolower( trim( $custom_field['name'] ) ) );
						}
						break;

					default:
						// Set an array key from other field, or use 'ID' on invalid field name
						$field_index = isset( $custom_field[ $array_key ] ) ? $custom_field[ $array_key ] : $custom_field['ID'];
						break;
				}
				$custom_fields[ $field_index ] = $custom_field;
			}
		}

		return $custom_fields;
	}
}

?>