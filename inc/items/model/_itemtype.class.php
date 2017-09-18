<?php
/**
 * This file implements the Post Type class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
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
	var $usage;
	var $template_name;
	var $front_instruction = 0;
	var $back_instruction = 0;
	var $instruction = '';
	var $use_title = 'required';
	var $use_url = 'optional';
	var $podcast = 0;
	var $use_parent = 'never';
	var $use_text = 'optional';
	var $allow_html = 1;
	var $allow_breaks = 1;
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
	var $comment_form_msg = '';
	var $allow_comment_form_msg = 0;
	var $allow_closing_comments = 1;
	var $allow_disabling_comments = 0;
	var $use_comment_expiration = 'optional';
	var $perm_level = 'standard';

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
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_items__type', 'ityp_', 'ityp_ID' );

		// Allow inseting specific IDs
		$this->allow_ID_insert = true;

		if( $db_row != NULL )
		{
			$this->ID   = $db_row->ityp_ID;
			$this->name = $db_row->ityp_name;
			$this->description = $db_row->ityp_description;
			$this->usage = $db_row->ityp_usage;
			$this->template_name = $db_row->ityp_template_name;
			$this->front_instruction = $db_row->ityp_front_instruction;
			$this->back_instruction = $db_row->ityp_back_instruction;
			$this->instruction = $db_row->ityp_instruction;
			$this->use_title = $db_row->ityp_use_title;
			$this->use_url = $db_row->ityp_use_url;
			$this->podcast = $db_row->ityp_podcast;
			$this->use_parent = $db_row->ityp_use_parent;
			$this->use_text = $db_row->ityp_use_text;
			$this->allow_html = $db_row->ityp_allow_html;
			$this->allow_breaks = $db_row->ityp_allow_breaks;
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
			$this->comment_form_msg = $db_row->ityp_comment_form_msg;
			$this->allow_comment_form_msg = $db_row->ityp_allow_comment_form_msg;
			$this->allow_closing_comments = $db_row->ityp_allow_closing_comments;
			$this->allow_disabling_comments = $db_row->ityp_allow_disabling_comments;
			$this->use_comment_expiration = $db_row->ityp_use_comment_expiration;
			$this->perm_level = $db_row->ityp_perm_level;
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
	 * Get delete cascade settings
	 *
	 * @return array
	 */
	static function get_delete_cascades()
	{
		return array(
				array( 'table' => 'T_items__type_coll', 'fk' => 'itc_ityp_ID', 'msg' => T_('%d Post type associations with collections') ),
				array( 'table' => 'T_items__type_custom_field', 'fk' => 'itcf_ityp_ID', 'msg' => T_('%d Custom field definitions') ),
				array( 'table' => 'T_items__status_type', 'fk' => 'its_ityp_ID', 'msg' => T_('%d Item status associations') )
			);
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		global $admin_url, $current_User;

		// Name
		param_string_not_empty( 'ityp_name', T_('Please enter a name.') );
		$this->set_from_Request( 'name' );

		// Description
		param( 'ityp_description', 'text' );
		$this->set_from_Request( 'description', NULL, true );

		// Permission level
		param( 'ityp_perm_level', 'string' );
		$this->set_from_Request( 'perm_level' );

		// Usage
		param( 'ityp_usage', 'string' );
		$this->set_from_Request( 'usage', NULL, true );

		// Template name
		param( 'ityp_template_name', 'string' );
		$this->set_from_Request( 'template_name', NULL, true );

		// Show instruction in front-office
		param( 'ityp_front_instruction', 'integer' );
		$this->set_from_Request( 'front_instruction' );

		// Show instruction in back-office
		param( 'ityp_back_instruction', 'integer' );
		$this->set_from_Request( 'back_instruction' );

		// Post instruction
		if( param( 'ityp_instruction', 'html', NULL ) !== NULL )
		{
			param_check_html( 'ityp_instruction', T_('Invalid instruction format.').' '.sprintf( T_('You can loosen this restriction in the <a %s>Group settings</a>.'), 'href='.$admin_url.'?ctrl=groups&amp;action=edit&amp;grp_ID='.$current_User->grp_ID ), '#', 'posting' );
			$this->set_from_Request( 'instruction', NULL, true );
		}

		// Use title
		param( 'ityp_use_title', 'string' );
		$this->set_from_Request( 'use_title' );

		// Use URL
		param( 'ityp_use_url', 'string' );
		$this->set_from_Request( 'use_url' );

		// Treat as Podcast Media
		param( 'ityp_podcast', 'integer', 0 );
		$this->set_from_Request( 'podcast' );

		// Use Parent ID
		param( 'ityp_use_parent', 'string' );
		$this->set_from_Request( 'use_parent' );

		// Use text
		param( 'ityp_use_text', 'string' );
		$this->set_from_Request( 'use_text' );

		// Allow HTML
		param( 'ityp_allow_html', 'integer', 0 );
		$this->set_from_Request( 'allow_html' );

		// Allow Teaser and Page breaks
		param( 'ityp_allow_breaks', 'integer', 0 );
		$this->set_from_Request( 'allow_breaks' );

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

		// Message before comment form:
		param( 'ityp_comment_form_msg', 'text' );
		$this->set_from_Request( 'comment_form_msg', NULL, true );

		// Allow custom message for each post
		param( 'ityp_allow_comment_form_msg', 'integer', 0 );
		$this->set_from_Request( 'allow_comment_form_msg' );

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
		$this->delete_custom_fields = trim(
			param( 'deleted_custom_double', 'string', '' ).','.
			param( 'deleted_custom_varchar', 'string', '' ).','.
			param( 'deleted_custom_text', 'string', '' ).','.
			param( 'deleted_custom_html', 'string', '' ).','.
			param( 'deleted_custom_url', 'string', '' ), ',' );
		$this->delete_custom_fields = empty( $this->delete_custom_fields ) ? array() : explode( ',', $this->delete_custom_fields );

		// Field names array is used to check the diplicates
		$field_names = array();

		// Empty and Initialize the custom fields from POST data
		$this->custom_fields = array();

		$types = array( 'double', 'varchar', 'text', 'html', 'url' );
		foreach( $types as $type )
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
				$custom_field_note = param( 'custom_'.$type.'_note'.$i, 'string', NULL );
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
						'note'    => $custom_field_note,
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
						'order' => $custom_field_order,
						'note'  => $custom_field_note,
					);
				}
				else
				{ // Update custom field
					$this->update_custom_fields[ $custom_field_ID ] = array(
						'type'  => $type,
						'name'  => $custom_field_name,
						'label' => $custom_field_label,
						'order' => $custom_field_order,
						'note'  => $custom_field_note,
					);
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
	 * Insert object into DB based on previously recorded changes.
	 */
	function dbinsert()
	{
		global $DB;

		$DB->begin();

		if( parent::dbinsert() )
		{
			global $Collection, $Blog;

			// Update/Insert/Delete custom fields:
			$this->dbsave_custom_fields();

			if( ! empty( $Blog ) )
			{ // Enable this item type only for selected Blog:
				$DB->query( 'INSERT INTO T_items__type_coll
					       ( itc_ityp_ID, itc_coll_ID )
					VALUES ( '.$this->ID.', '.$Blog->ID.' )' );
			}
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
				$DB->query( 'INSERT INTO T_items__type_custom_field ( itcf_ityp_ID, itcf_label, itcf_name, itcf_type, itcf_order, itcf_note )
					VALUES ( '.$DB->quote( $this->ID ).', '
						.$DB->quote( $custom_field['label'] ).', '
						.$DB->quote( $custom_field['name'] ).', '
						.$DB->quote( $custom_field['type'] ).', '
						.( empty( $custom_field['order'] ) ? 'NULL' : $DB->quote( $custom_field['order'] ) ).', '
						.( empty( $custom_field['note'] ) ? 'NULL' : $DB->quote( $custom_field['note'] ) ).' )' );
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
						itcf_order = '.( empty( $custom_field['order'] ) ? 'NULL' : $DB->quote( $custom_field['order'] ) ).',
						itcf_note = '.( empty( $custom_field['note'] ) ? 'NULL' : $DB->quote( $custom_field['note'] ) ).'
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
	 * Check if this post type is used for intro posts
	 *
	 * @return boolean
	 */
	function is_intro()
	{
		return in_array( $this->usage, array( 'intro-front', 'intro-main', 'intro-cat', 'intro-tag', 'intro-sub', 'intro-all' ) );
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
	 * @param string Type of custom field: 'all', 'varchar', 'double', 'text', 'html', 'url'. Use comma separator to get several types
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
				$SQL->SELECT( 'itcf_ID AS ID, itcf_ityp_ID AS ityp_ID, itcf_label AS label, itcf_name AS name, itcf_type AS type, itcf_order AS `order`, itcf_note AS note' );
				$SQL->FROM( 'T_items__type_custom_field' );
				$SQL->WHERE( 'itcf_ityp_ID = '.$DB->quote( $this->ID ) );
				$SQL->ORDER_BY( 'itcf_order, itcf_ID' );
				$this->custom_fields = $DB->get_results( $SQL->get(), ARRAY_A );
			}
		}

		$custom_fields = array();
		foreach( $this->custom_fields as $custom_field )
		{
			if( $type == 'all' || strpos( $type, $custom_field['type'] ) !== false )
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


	/**
	 * Get associated post status
	 *
	 * @return array IDs of associated post status
	 */
	function get_applicable_post_status()
	{
		global $DB;

		$sql = 'SELECT its_pst_ID FROM T_items__status_type WHERE its_ityp_ID = '.$this->ID;
		$item_status_array = $DB->get_col( $sql );
		$item_status_array = array_map( 'intval', $item_status_array );

		return $item_status_array;
	}


	/**
	 * Get post status not associated with current item type
	 *
	 * @return array IDs of post status not valid for current item type
	 */
	function get_ignored_post_status( )
	{
		global $DB;

		$sql = 'SELECT pst_ID FROM T_items__status WHERE pst_ID NOT IN ( SELECT its_pst_ID FROM T_items__status_type WHERE its_ityp_ID = '.$this->ID.' )';
		/*
		$sql = 'SELECT pst_ID
							FROM T_items__status
							JOIN T_items__type
							LEFT JOIN T_items__status_type ON its_ityp_ID = ityp_ID AND its_pst_ID = pst_ID
							WHERE ityp_ID = '.$this->ID.'	AND its_pst_ID IS NULL';
		*/
		$item_status_array = $DB->get_col( $sql );
		$item_status_array = array_map( 'intval', $item_status_array );

		return $item_status_array;
	}


	/**
	 * Update item statuses associated with this item type
	 */
	function update_item_statuses_from_Request()
	{
		global $DB;

		$allowed_values = array();
		$remove_values = array();

		// Item Types
		$item_status_IDs = param( 'item_status_IDs', 'string', true );
		$item_status_IDs = explode( ',', $item_status_IDs );

		foreach( $item_status_IDs as $loop_status_ID )
		{
			$loop_status_ID = intval( $loop_status_ID );
			$item_status = param( 'status_'.$loop_status_ID, 'integer', 0 );

			if( $item_status )
			{
				$allowed_values[] = "( $this->ID, $loop_status_ID )";
			}
			else
			{
				$remove_values[] = $loop_status_ID;
			}
		}

		if( $allowed_values )
		{
			$DB->query( 'REPLACE INTO T_items__status_type( its_ityp_ID, its_pst_ID )
					VALUES '.implode( ', ', $allowed_values ) );
		}

		if( $remove_values )
		{
			$DB->query( 'DELETE FROM T_items__status_type
					WHERE its_ityp_ID = '.$this->ID.'
					AND its_pst_ID IN ('.implode( ',', $remove_values ).')' );
		}
	}
}

?>