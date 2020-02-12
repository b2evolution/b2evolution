<?php
/**
 * This file implements the Post Type class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
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
	var $schema = '';
	var $add_aggregate_rating = 1;
	var $back_instruction = 0;
	var $instruction = '';
	var $text_template;
	var $use_short_title = 'never';
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
	var $allow_switchable = 1;
	var $use_country = 'never';
	var $use_region = 'never';
	var $use_sub_region = 'never';
	var $use_city = 'never';
	var $use_coordinates = 'never';
	var $use_comments = 1;
	var $comment_form_msg = '';
	var $allow_comment_form_msg = 0;
	var $allow_closing_comments = 1;
	var $allow_disabling_comments = 0;
	var $use_comment_expiration = 'optional';
	var $perm_level = 'standard';
	var $evobar_link_text = NULL;
	var $skin_btn_text = NULL;
	var $short_title_maxlen = 30;
	var $title_maxlen = 100;
	var $front_order_title       = 10;
	var $front_order_short_title = NULL;
	var $front_order_instruction = NULL;
	var $front_order_attachments = 30;
	var $front_order_text        = 80;
	var $front_order_tags        = NULL;
	var $front_order_excerpt     = NULL;
	var $front_order_url         = NULL;
	var $front_order_location    = NULL;

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
			$this->schema = isset( $db_row->ityp_schema ) ? $db_row->ityp_schema : $this->schema;
			$this->add_aggregate_rating = isset( $db_row->ityp_add_aggregate_rating ) ? $db_row->ityp_add_aggregate_rating : $this->add_aggregate_rating;
			$this->back_instruction = $db_row->ityp_back_instruction;
			$this->instruction = $db_row->ityp_instruction;
			$this->text_template = isset( $db_row->ityp_text_template ) ? $db_row->ityp_text_template : $this->text_template;
			$this->use_short_title = isset( $db_row->ityp_use_short_title ) ? $db_row->ityp_use_short_title : $this->use_short_title;
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
			$this->allow_switchable = $db_row->ityp_allow_switchable;
			$this->use_country = $db_row->ityp_use_country;
			$this->use_region = $db_row->ityp_use_region;
			$this->use_sub_region = $db_row->ityp_use_sub_region;
			$this->use_city = $db_row->ityp_use_city;
			$this->use_coordinates = $db_row->ityp_use_coordinates;
			$this->use_comments = $db_row->ityp_use_comments;
			$this->comment_form_msg = $db_row->ityp_comment_form_msg;
			$this->allow_comment_form_msg = $db_row->ityp_allow_comment_form_msg;
			$this->allow_closing_comments = $db_row->ityp_allow_closing_comments;
			$this->allow_disabling_comments = $db_row->ityp_allow_disabling_comments;
			$this->use_comment_expiration = $db_row->ityp_use_comment_expiration;
			$this->perm_level = $db_row->ityp_perm_level;
			$this->evobar_link_text = isset( $db_row->ityp_evobar_link_text ) ? $db_row->ityp_evobar_link_text : $this->evobar_link_text;
			$this->skin_btn_text = isset( $db_row->ityp_skin_btn_text ) ? $db_row->ityp_skin_btn_text : $this->skin_btn_text;
			$this->short_title_maxlen = isset( $db_row->ityp_short_title_maxlen ) ? $db_row->ityp_short_title_maxlen : $this->short_title_maxlen;
			$this->title_maxlen = isset( $db_row->ityp_title_maxlen ) ? $db_row->ityp_title_maxlen : $this->title_maxlen;
			$this->front_order_title = isset( $db_row->ityp_front_order_title ) ? $db_row->ityp_front_order_title : NULL;
			$this->front_order_short_title = isset( $db_row->ityp_front_order_short_title ) ? $db_row->ityp_front_order_short_title : NULL;
			$this->front_order_instruction = isset( $db_row->ityp_front_order_instruction ) ? $db_row->ityp_front_order_instruction : NULL;
			$this->front_order_attachments = isset( $db_row->ityp_front_order_attachments ) ? $db_row->ityp_front_order_attachments : NULL;
			$this->front_order_text = isset( $db_row->ityp_front_order_text ) ? $db_row->ityp_front_order_text : NULL;
			$this->front_order_tags = isset( $db_row->ityp_front_order_tags ) ? $db_row->ityp_front_order_tags : NULL;
			$this->front_order_excerpt = isset( $db_row->ityp_front_order_excerpt ) ? $db_row->ityp_front_order_excerpt : NULL;
			$this->front_order_url = isset( $db_row->ityp_front_order_url ) ? $db_row->ityp_front_order_url : NULL;
			$this->front_order_location = isset( $db_row->ityp_front_order_location ) ? $db_row->ityp_front_order_location : NULL;
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
				array( 'table'=>'T_categories', 'fk'=>'cat_ityp_ID', 'msg'=>T_('%d related categories') ),
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

		// Schema
		param( 'ityp_schema', 'string' );
		$this->set_from_Request( 'schema', NULL, true );

		// Add aggregateRating
		param( 'ityp_add_aggregate_rating', 'integer', 0 );
		$this->set_from_Request( 'add_aggregate_rating' );

		// New item link in evobar text
		param( 'ityp_evobar_link_text', 'string' );
		$this->set_from_Request( 'evobar_link_text' );

		// New item button in skin text
		param( 'ityp_skin_btn_text', 'string' );
		$this->set_from_Request( 'skin_btn_text' );

		// Show instruction in back-office
		param( 'ityp_back_instruction', 'integer' );
		$this->set_from_Request( 'back_instruction' );

		// Instructions:
		param( 'ityp_instruction', 'html', NULL );
		param_check_html( 'ityp_instruction', T_('Invalid instruction format.').' '.sprintf( T_('You can loosen this restriction in the <a %s>Group settings</a>.'), 'href='.$admin_url.'?ctrl=groups&amp;action=edit&amp;grp_ID='.$current_User->grp_ID ), '#', 'posting' );
		$this->set_from_Request( 'instruction', NULL, true );

		// Front-Office Order (Instructions)
		param( 'ityp_front_order_instruction', 'integer', NULL );
		$this->set_from_Request( 'front_order_instruction' );

		// Template:
		param( 'ityp_text_template', 'html', NULL );
		param_check_html( 'ityp_text_template', T_('Invalid template.') );
		$this->set_from_Request( 'text_template', NULL, true );

		// Use short title
		param( 'ityp_use_short_title', 'string' );
		$this->set_from_Request( 'use_short_title' );

		// Short title max length
		param( 'ityp_short_title_maxlen', 'integer' );
		$this->set_from_Request( 'short_title_maxlen' );

		// Front-Office Order (Short title)
		param( 'ityp_front_order_short_title', 'integer', NULL );
		$this->set_from_Request( 'front_order_short_title' );

		// Use title
		param( 'ityp_use_title', 'string' );
		$this->set_from_Request( 'use_title' );

		// Title max length
		param( 'ityp_title_maxlen', 'integer' );
		$this->set_from_Request( 'title_maxlen' );

		// Front-Office Order (Title)
		param( 'ityp_front_order_title', 'integer', NULL );
		$this->set_from_Request( 'front_order_title' );

		// Use URL
		param( 'ityp_use_url', 'string' );
		$this->set_from_Request( 'use_url' );

		// Front-Office Order (URL)
		param( 'ityp_front_order_url', 'integer', NULL );
		$this->set_from_Request( 'front_order_url' );

		// Treat as Podcast Media
		param( 'ityp_podcast', 'integer', 0 );
		$this->set_from_Request( 'podcast' );

		// Use Parent ID
		param( 'ityp_use_parent', 'string' );
		$this->set_from_Request( 'use_parent' );

		// Use text
		param( 'ityp_use_text', 'string' );
		$this->set_from_Request( 'use_text' );

		// Front-Office Order (Text)
		param( 'ityp_front_order_text', 'integer', NULL );
		$this->set_from_Request( 'front_order_text' );

		// Allow HTML
		param( 'ityp_allow_html', 'integer', 0 );
		$this->set_from_Request( 'allow_html' );

		// Allow Teaser and Page breaks
		param( 'ityp_allow_breaks', 'integer', 0 );
		$this->set_from_Request( 'allow_breaks' );

		// Allow attachments
		param( 'ityp_allow_attachments', 'integer', 0 );
		$this->set_from_Request( 'allow_attachments' );

		// Front-Office Order (Attachments)
		param( 'ityp_front_order_attachments', 'integer', NULL );
		$this->set_from_Request( 'front_order_attachments' );

		// Use excerpt
		param( 'ityp_use_excerpt', 'string' );
		$this->set_from_Request( 'use_excerpt' );

		// Front-Office Order (Excerpt)
		param( 'ityp_front_order_excerpt', 'integer', NULL );
		$this->set_from_Request( 'front_order_excerpt' );

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

		// Front-Office Order (Tags)
		param( 'ityp_front_order_tags', 'integer', NULL );
		$this->set_from_Request( 'front_order_tags' );

		// Allow featured
		param( 'ityp_allow_featured', 'integer', 0 );
		$this->set_from_Request( 'allow_featured' );

		// Allow switchable
		param( 'ityp_allow_switchable', 'integer', 0 );
		$this->set_from_Request( 'allow_switchable' );

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

		// Front-Office Order (Location:Country/Region/Sub-region/City)
		param( 'ityp_front_order_location', 'integer', NULL );
		$this->set_from_Request( 'front_order_location' );

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
		$this->delete_custom_fields = trim( param( 'deleted_custom_fields', 'string', '' ), ', ' );
		$this->delete_custom_fields = empty( $this->delete_custom_fields ) ? array() : explode( ',', $this->delete_custom_fields );

		// Field names array is used to check the diplicates
		$field_names = array();

		// Get previous computed custom fields:
		$old_computed_custom_fields = $this->get_custom_fields( 'computed' );

		// Empty and Initialize the custom fields from POST data
		$this->custom_fields = array();

		$empty_title_error = false; // use this to display empty title fields error message only ones
		$custom_field_count = param( 'count_custom_fields', 'integer', 0 ); // all custom fields count ( contains even deleted fields )

		if( empty( $custom_field_count ) )
		{	// No custom fields to insert/update:
			return;
		}

		// Decode data of all custom fields which were posted as single JSON encoded hidden input by JavaScript:
		$custom_fields_data = json_decode( param( 'custom_fields_data', 'string' ) );
		set_param( 'custom_fields_data', $custom_fields_data );

		$inputs = array(
			'ID'              => '/^[a-z0-9\-_]+$/',
			'new'             => array( 'integer', 0 ),
			'label'           => 'string',
			'name'            => '/^[a-z0-9\-_]+$/',
			'schema_prop'     => 'string',
			'type'            => 'string',
			'order'           => 'integer',
			'note'            => 'string',
			'required'        => array( 'integer', 0 ),
			'meta'            => array( 'integer', 0 ),
			'public'          => array( 'integer', 0 ),
			'format'          => 'string',
			'formula'         => 'string',
			'disp_condition'  => 'string',
			'header_class'    => 'string',
			'cell_class'      => 'string',
			'link'            => array( 'string', 'nolink' ),
			'link_nofollow'   => 'integer',
			'link_class'      => 'string',
			'line_highlight'  => array( 'string', 'never' ),
			'green_highlight' => array( 'string', 'never' ),
			'red_highlight'   => array( 'string', 'never' ),
			'description'     => 'html',
			'merge'           => array( 'integer', 0 ),
		);

		// Flag to inform user once about changed formula:
		$formula_was_changed = false;

		for( $i = 1 ; $i <= $custom_field_count; $i++ )
		{
			$custom_field_data = array();
			foreach( $inputs as $input_name => $input_data )
			{
				// Get input data:
				$input_type = is_array( $input_data ) ? $input_data[0] : $input_data;
				$input_default_value = is_array( $input_data )  ? $input_data[1] : NULL;
				if( isset( $custom_fields_data->{$input_name.$i} ) )
				{
					if( $input_type == 'string' &&
					    $input_default_value !== NULL &&
					    $custom_fields_data->{$input_name.$i} === '' )
					{	// Use default value for empty string:
						$input_value = $input_default_value;
					}
					else
					{	// Use submitted value:
						$input_value = $custom_fields_data->{$input_name.$i};
					}
				}
				else
				{	// Use default value:
					$input_value = $input_default_value;
				}

				if( $input_value !== $input_default_value )
				{	// Format input value to requested type only when it is not default value:
					if( substr( $input_type, 0, 1 ) == '/' )
					{	// Check value by regexp:
						if( ! empty( $input_value ) && ! preg_match( $input_type, $input_value ) )
						{	// Don't allow wrong value:
							bad_request_die( sprintf( T_('Illegal value received for parameter &laquo;%s&raquo;!'), $input_name ) );
						}
						$input_type = 'string';
					}
					$input_value = param_format( $input_value, $input_type );
				}

				switch( $input_name )
				{
					case 'ID':
						$custom_field_ID = $input_value;
						break;
					case 'new':
						$custom_field_is_new = $input_value;
						break;
					default:
						$custom_field_data[ $input_name ] = $input_value;
						break;
				}
			}

			// Note: this param contains ID of existing custom field from DB
			//       or random value like d63d5d53-df3d-5299-8c85-35f69b77 for new creating field:
			if( empty( $custom_field_ID ) || in_array( $custom_field_ID, $this->delete_custom_fields ) )
			{ // This field was deleted, don't neeed to update
				continue;
			}

			// Add each new/existing custom field in this array
			// in order to see all them on the form when post type is not updated because some errors
			$this->custom_fields[] = array(
					'temp_i'          => $i, // Used only on submit form to know the number of the field on the form
					'ID'              => $custom_field_ID,
					'ityp_ID'         => $this->ID,
				) + $custom_field_data;

			if( empty( $custom_field_data['label'] ) )
			{ // Field title can't be emtpy
				if( ! $empty_title_error )
				{ // This message was not displayed yet
					$Messages->add( T_('Custom field titles can\'t be empty!') );
					$empty_title_error = true;
				}
			}
			elseif( empty( $custom_field_data['name'] ) )
			{ // Field identical name can't be emtpy
				$Messages->add( sprintf( T_('Please enter name for custom field "%s"'), $custom_field_data['label'] ) );
			}
			elseif( in_array( $custom_field_data['name'], $field_names ) )
			{ // Field name must be identical
				$Messages->add( sprintf( T_('The field name "%s" is used more than once. Each field name must be unique.'), $custom_field_data['name'] ) );
			}
			else
			{
				$field_names[] = $custom_field_data['name'];
			}

			// Checks for computed custom fields:
			if( $custom_field_data['type'] == 'computed' )
			{
				if( $custom_field_data['formula'] === '' )
				{	// Formula must be not empty:
					$Messages->add( sprintf( TB_('Please enter formula for computed custom field "%s".'), $custom_field_data['label'] ) );
				}

				if( ! $formula_was_changed &&
				    ! $custom_field_is_new &&
				    isset( $old_computed_custom_fields[ $custom_field_data['name'] ] ) &&
				    $old_computed_custom_fields[ $custom_field_data['name'] ]['formula'] != $custom_field_data['formula'] )
				{	// Inform once user about changed formula:
					$Messages->add( TB_('You changed one or several formulas. All posts need to be re-saved to update the results of these formulas.'), 'warning' );
					$formula_was_changed = true;
				}
			}

			if( $custom_field_is_new )
			{ // Insert custom field
				$this->insert_custom_fields[ $custom_field_ID ] = $custom_field_data;
			}
			else
			{ // Update custom field
				$this->update_custom_fields[ $custom_field_ID ] = $custom_field_data;
			}
		}

		// Check formulas of computed fields:
		$custom_fields = $this->get_custom_fields();
		foreach( $custom_fields as $custom_field )
		{
			if( $custom_field['type'] != 'computed' )
			{	// Skip not computed field:
				continue;
			}

			$formula_has_wrong_field = false;
			if( preg_match_all( '#\$(.+?)\$#', $custom_field['formula'], $formula_fields ) )
			{	// If formula has at least one field:
				foreach( $formula_fields[1] as $formula_field )
				{
					if( ! isset( $custom_fields[ $formula_field ] ) )
					{	// Not found field:
						$Messages->add( sprintf( TB_('The field name %s is not recognized (in the formula %s of the field "%s".'),
								'<code>'.$formula_field.'</code>',
								'<code>'.$custom_field['formula'].'</code>',
								$custom_field['label']
							), 'warning' );
						$formula_has_wrong_field = true;
					}
					elseif( ! in_array( $custom_fields[ $formula_field ]['type'], array( 'double', 'computed' ) ) )
					{	// Field with wrong type is used in formula:
						$Messages->add( sprintf( TB_('Only numeric or computed fields can be used in formulas. Please remove the field %s from the formula %s of the field "%s".'),
								'<code>'.$formula_field.'</code>',
								'<code>'.$custom_field['formula'].'</code>', 
								$custom_field['label']
							), 'warning' );
						$formula_has_wrong_field = true;
					}
				}
			}

			if( ! $formula_has_wrong_field && $custom_field['formula'] !== '' )
			{	// Check for correct formula:
				$test_formula = preg_replace( '#\$(.+?)\$#', '1', $custom_field['formula'] );
				try
				{	// Compute value:
					ob_start();
					$test_value = eval( "return $test_formula;" );
					$formula_code_output = ob_get_clean();
					if( ( $formula_code_output !== '' && $formula_code_output !== false ) ||
							! is_numeric( $test_value ) )
					{	// If output buffer contains some text it means there is some error;
						// Don't allow to use not numeric value for the "computed" custom field:
						$test_value = NULL;
					}
				}
				catch( Error $e )
				{	// Set NULL value for wrong formula:
					$test_value = NULL;
				}
				catch( ParseError $e )
				{	// Set NULL value for wrong formula:
					$test_value = NULL;
				}

				if( $test_value === NULL )
				{	// Display warning when formula cannot be executed properly:
					$Messages->add( sprintf( TB_('Please check formula %s of the field "%s" because it cannot be evaluated properly.'),
							'<code>'.$custom_field['formula'].'</code>',
							$custom_field['label']
						), 'warning' );
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
		else
		{
			$DB->rollback();
			return false;
		}

		$DB->commit();

		return true;
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

		// BLOCK CACHE INVALIDATION:
		BlockCache::invalidate_key( 'item_type_'.$this->ID, 1 ); // Item Type has changed (useful for compare widget which needs to check several item_IDs, including from different collections)

		return true;
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

		if( ! empty( $this->delete_custom_fields ) )
		{	// Delete custom fields:
			$sql_data = array();
			foreach( $this->delete_custom_fields as $itcf_ID )
			{
				$sql_data[] = '( itcf_ityp_ID = '.$DB->quote( $this->ID ).' AND itcf_ID = '.$DB->quote( $itcf_ID ).' )';
			}
			$DB->query( 'DELETE FROM T_items__type_custom_field
				WHERE '.implode( ' OR ', $sql_data ) );
		}

		if( ! empty( $this->insert_custom_fields ) )
		{	// Insert new custom fields:
			$sql_data = array();
			foreach( $this->insert_custom_fields as $itcf_ID => $custom_field )
			{
				$sql_data[] = '( '.$DB->quote( $this->ID ).', '
						.$DB->quote( $custom_field['label'] ).', '
						.$DB->quote( $custom_field['name'] ).', '
						.$DB->quote( $custom_field['schema_prop'] ).', '
						.$DB->quote( $custom_field['type'] ).', '
						.( empty( $custom_field['order'] ) ? 'NULL' : $DB->quote( $custom_field['order'] ) ).', '
						.( empty( $custom_field['note'] ) ? 'NULL' : $DB->quote( $custom_field['note'] ) ).', '
						.$DB->quote( $custom_field['required'] ).', '
						.$DB->quote( $custom_field['meta'] ).', '
						.$DB->quote( $custom_field['public'] ).', '
						.$DB->quote( $custom_field['format'] ).', '
						.$DB->quote( $custom_field['formula'] ).', '
						.$DB->quote( $custom_field['disp_condition'] ).', '
						.$DB->quote( $custom_field['header_class'] ).', '
						.$DB->quote( $custom_field['cell_class'] ).', '
						.$DB->quote( $custom_field['link'] ).', '
						.$DB->quote( $custom_field['link_nofollow'] ).', '
						.$DB->quote( $custom_field['link_class'] ).', '
						.$DB->quote( $custom_field['line_highlight'] ).', '
						.$DB->quote( $custom_field['green_highlight'] ).', '
						.$DB->quote( $custom_field['red_highlight'] ).', '
						.( empty( $custom_field['description'] ) ? 'NULL' : $DB->quote( $custom_field['description'] ) ).', '
						.$DB->quote( $custom_field['merge'] ).' )';
			}
			$DB->query( 'INSERT INTO T_items__type_custom_field ( itcf_ityp_ID, itcf_label, itcf_name, itcf_schema_prop, itcf_type, itcf_order, itcf_note, itcf_required, itcf_meta, itcf_public, itcf_format, itcf_formula, itcf_disp_condition, itcf_header_class, itcf_cell_class, itcf_link, itcf_link_nofollow, itcf_link_class, itcf_line_highlight, itcf_green_highlight, itcf_red_highlight, itcf_description, itcf_merge )
					VALUES '.implode( ', ', $sql_data ) );
		}

		if( ! empty( $this->update_custom_fields ) )
		{	// Update custom fields:
			unset( $this->custom_fields );
			$old_custom_fields = $this->get_custom_fields( 'all', 'ID' );
			foreach( $this->update_custom_fields as $itcf_ID => $custom_field )
			{
				$DB->query( 'UPDATE T_items__type_custom_field
					SET
						itcf_label = '.$DB->quote( $custom_field['label'] ).',
						itcf_name = '.$DB->quote( $custom_field['name'] ).',
						itcf_schema_prop = '.$DB->quote( $custom_field['schema_prop'] ).',
						itcf_order = '.( empty( $custom_field['order'] ) ? 'NULL' : $DB->quote( $custom_field['order'] ) ).',
						itcf_note = '.( empty( $custom_field['note'] ) ? 'NULL' : $DB->quote( $custom_field['note'] ) ).',
						itcf_required = '.$DB->quote( $custom_field['required'] ).',
						itcf_meta = '.$DB->quote( $custom_field['meta'] ).',
						itcf_public = '.$DB->quote( $custom_field['public'] ).',
						itcf_format = '.$DB->quote( $custom_field['format'] ).',
						itcf_formula = '.$DB->quote( $custom_field['formula'] ).',
						itcf_cell_class = '.$DB->quote( $custom_field['cell_class'] ).',
						itcf_disp_condition = '.$DB->quote( $custom_field['disp_condition'] ).',
						itcf_header_class = '.$DB->quote( $custom_field['header_class'] ).',
						itcf_link = '.$DB->quote( $custom_field['link'] ).',
						itcf_link_nofollow = '.$DB->quote( $custom_field['link_nofollow'] ).',
						itcf_link_class = '.$DB->quote( $custom_field['link_class'] ).',
						itcf_line_highlight = '.$DB->quote( $custom_field['line_highlight'] ).',
						itcf_green_highlight = '.$DB->quote( $custom_field['green_highlight'] ).',
						itcf_red_highlight = '.$DB->quote( $custom_field['red_highlight'] ).',
						itcf_description = '.( empty( $custom_field['description'] ) ? 'NULL' : $DB->quote( $custom_field['description'] ) ).',
						itcf_merge = '.$DB->quote( $custom_field['merge'] ).'
					WHERE itcf_ityp_ID = '.$DB->quote( $this->ID ).'
						AND itcf_ID = '.$DB->quote( $itcf_ID ).'
						AND itcf_type = '.$DB->quote( $custom_field['type'] ) );
				if( isset( $old_custom_fields[ $itcf_ID ] ) &&
				    $this->ID > 0 &&
				    $custom_field['name'] != $old_custom_fields[ $itcf_ID ]['name'] )
				{	// Update item setting names of custom field to use new field name:
					$DB->query( 'UPDATE T_items__item_custom_field
						INNER JOIN T_items__item ON post_ID = icfv_item_ID AND post_ityp_ID = '.$DB->quote( $this->ID ).'
						  SET icfv_itcf_name = '.$DB->quote( $custom_field['name'] ).'
						WHERE icfv_itcf_name = '.$DB->quote( $old_custom_fields[ $itcf_ID ]['name'] ) );
				}
			}
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
	 * Get the custom fields
	 *
	 * @param string Type of custom field: 'all', 'varchar', 'double', 'text', 'html', 'url', 'image', 'computed', 'separator'. Use comma separator to get several types
	 * @param string Field name that is used as key of array: 'ID', 'ityp_ID', 'label', 'name', 'type', 'order', 'public'
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
				$SQL = new SQL( 'Load all custom fields definitions of Item Type #'.$this->ID );
				$SQL->SELECT( '*' );
				$SQL->FROM( 'T_items__type_custom_field' );
				$SQL->WHERE( 'itcf_ityp_ID = '.$DB->quote( $this->ID ) );
				$SQL->ORDER_BY( 'itcf_order, itcf_ID' );
				$custom_fields = $DB->get_results( $SQL, ARRAY_A );
				$this->custom_fields = array();
				foreach( $custom_fields as $c => $custom_field )
				{
					$this->custom_fields[ $c ] = array();
					foreach( $custom_field as $custom_field_key => $custom_field_value )
					{
						$this->custom_fields[ $c ][ substr( $custom_field_key, 5 ) ] = $custom_field_value;
					}
				}
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


	/**
	 * Check if this Item Type is enabled for requested collection
	 *
	 * @param integer Collection ID
	 * @return boolean
	 */
	function is_enabled( $coll_ID )
	{
		if( empty( $this->ID ) )
		{	// Item Type is not inserted in DB yet:
			return false;
		}

		if( ! isset( $this->enabled_colls ) )
		{	// Load into cache where this Item Type is enabled for all collections:
			global $DB;
			$SQL = new SQL( 'Load all collections IDs where Item Type #'.$this->ID.' is enabled' );
			$SQL->SELECT( 'itc_coll_ID' );
			$SQL->FROM( 'T_items__type_coll' );
			$SQL->WHERE( 'itc_ityp_ID = '.$this->ID );
			$this->enabled_colls = $DB->get_col( $SQL );
		}

		return in_array( $coll_ID, $this->enabled_colls );
	}


	/**
	 * Get item denomination
	 *
	 * @param string Position where denomination will be used, can be one of the following: 'evobar_new', 'inskin_new_btn', 'title_new', 'title_update'
	 * @param string Default denomination, e.g. when no current collection
	 * @return string Item denomination
	 */
	function get_item_denomination( $position = 'evobar_new', $default_denomination = NULL )
	{
		switch( $position )
		{
			case 'evobar_new':
				if( ! empty( $this->evobar_link_text ) )
				{
					return $this->evobar_link_text;
				}
				break;

			case 'inskin_new_btn':
				if( ! empty( $this->skin_btn_text ) )
				{
					return $this->skin_btn_text;
				}
				break;
		}

		global $Collection, $Blog;

		if( ! empty( $Blog  ) )
		{
			return $Blog->get_item_denomination( $position );
		}

		return $default_denomination;
	}
}

?>