<?php
/**
 * This file implements the Template class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );


/**
 * Menu Class
 *
 * @package evocore
 */
class Template extends DataObject
{
	var $name;
	var $code;
	var $translates_tpl_ID;
	var $locale;
	var $template_code;
	var $context;
	var $owner_grp_ID;

	/**
	 * @var integer Translated template count
	 */
	var $count_translated_templates = NULL;

	/**
	 * @var array Localized child templates
	 */
	var $localized_templates = NULL;

	/**
	 * Constructor
	 *
	 * @param object table Database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_templates', 'tpl_', 'tpl_ID' );

		if( $db_row != NULL )
		{	// Get menu data from DB:
			$this->ID = $db_row->tpl_ID;
			$this->name = $db_row->tpl_name;
			$this->code = $db_row->tpl_code;
			$this->translates_tpl_ID = $db_row->tpl_translates_tpl_ID;
			$this->locale = $db_row->tpl_locale;
			$this->template_code = $db_row->tpl_template_code;
			$this->context = $db_row->tpl_context;
			$this->owner_grp_ID = $db_row->tpl_owner_grp_ID;
		}
	}


	/**
	 * Get delete cascade settings
	 *
	 * @return array
	 */
	static function get_delete_cascades()
	{
		return array(
				array( 'table' => 'T_templates', 'fk' => 'tpl_translates_tpl_ID', 'msg' => T_('%d child templates') ),
			);
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// Name:
		param( 'tpl_name', 'string' );
		param_check_not_empty( 'tpl_name', T_('Please enter a name for the template.') );
		$this->set_from_Request( 'name' );

		// Code:
		param( 'tpl_code', 'string', NULL );
		$this->set_from_Request( 'code' );

		// Parent Menu:
		$tpl_parent_ID = param( 'tpl_translates_tpl_ID', 'integer', NULL );
		if( isset( $tpl_translates_tpl_ID ) && $this->has_translated_templates() )
		{
			global $Messages;
			$Messages->add( sprintf( T_('This template cannot become a child of another because it has %d children itself.'), $this->count_translated_templates ) );
		}
		$this->set_from_Request( 'translates_tpl_ID' );

		// Locale:
		param( 'tpl_locale', 'string' );
		$this->set_from_Request( 'locale' );

		// Template Code:
		param( 'tpl_template_code', 'html' );
		param_check_not_empty( 'tpl_template_code' );
		param_check_html( 'tpl_template_code', T_('Invalid template code content.'), '#', 'quick_template' );
		$this->set( 'template_code', get_param( 'tpl_template_code' ) );

		// Context:
		param( 'tpl_context', 'string', 'custom' );
		$this->set_from_Request( 'context' );

		// Owner Group:
		param( 'tpl_owner_grp_ID', 'integer', NULL );
		param_check_not_empty( 'tpl_owner_grp_ID', T_('Please select an owner group for the template.') );
		$this->set_from_Request( 'owner_grp_ID' );

		return ! param_errors_detected();
	}


	/**
	 * Insert object into DB based on previously recorded changes.
	 *
	 * @return boolean true on success
	 */
	function dbinsert()
	{
		global $DB, $Messages;

		$DB->begin();

		if( empty( $this->code ) )
		{	// No code specified, create one from name:
			$tpl_code = param( 'tpl_name', 'string', true );
			$tpl_code = unique_template_code( $tpl_code );
			$this->set( 'code', $tpl_code );
		}
		else
		{
			$original_code = $this->code;
			$this->set( 'code', unique_template_code( $this->code ) );
			if( $original_code != $this->code )
			{
				$Messages->add_to_group( sprintf( T_('Template code has been changed to &laquo;%s&raquo;.'), $this->code ), 'note', T_('Warning: Template code changed:' ) );
			}
		}

		$result = parent::dbinsert();
		if( $result )
		{	
			$DB->commit();
		}
		else
		{
			$DB->rollback();
		}

		return $result;
	}


	/**
	 * Update the DB based on previously recorded changes
	 */
	function dbupdate()
	{
		global $DB, $Messages;

		$DB->begin();

		if( empty( $this->code ) )
		{	// No code specified, create one from name:
			$this->set( 'code', unique_template_code( $this->name, $this->ID ) );
		}
		else
		{
			$original_code = $this->code;
			$this->set( 'code', unique_template_code( $this->code, $this->ID ) );
			if( $original_code != $this->code )
			{
				$Messages->add_to_group( sprintf( T_('Template code has been changed to &laquo;%s&raquo;.'), $this->code ), 'note', T_('Warning: Template code changed:' ) );
			}
		}

		$result = parent::dbupdate();
		if( $result )
		{
			$DB->commit();
		}
		else
		{
			$DB->rollback();
		}

		return $result;
	}


	/**
	 * Duplicate template
	 * 
	 * @return boolean True if duplication was successfull, false otherwise
	 */
	function duplicate()
	{
		global $DB;

		$DB->begin();

		$duplicated_template_ID = $this->ID;
		$this->ID = 0;

		// Fields that should not be duplicated must be included in the array below:
		$skipped_fields = array( 'ID' );

		// Get all fields of the duplicated menu:
		$source_fields_SQL = new SQL( 'Get all fields of the duplicated template #'.$duplicated_template_ID );
		$source_fields_SQL->SELECT( '*' );
		$source_fields_SQL->FROM( 'T_templates' );
		$source_fields_SQL->WHERE( 'tpl_ID = '.$DB->quote( $duplicated_template_ID ) );
		$source_fields = $DB->get_row( $source_fields_SQL, ARRAY_A );

		// Use field values of duplicated template by default:
		foreach( $source_fields as $source_field_name => $source_field_value )
		{
			// Cut prefix "tpl_" of each field:
			$source_field_name = substr( $source_field_name, 4 );
			if( in_array( $source_field_name, $skipped_fields ) )
			{ // Do not duplicate skipped fields
				continue;
			}
			if( isset( $this->$source_field_name ) )
			{	// Unset current value in order to assign new below, especially to update this in array $this->dbchanges:
				unset( $this->$source_field_name );
			}
			$this->set( $source_field_name, $source_field_value );
		}

		// Call this firstly to find all possible errors before inserting:
		// Also to set new values from submitted form:
		if( ! $this->load_from_Request() )
		{	// Error on handle new values from form:
			$this->ID = $duplicated_template_ID;
			$DB->rollback();
			return false;
		}

		// Try insert new collection in DB:
		if( ! $this->dbinsert() )
		{	// Error on insert collection in DB:
			$this->ID = $duplicated_template_ID;
			$DB->rollback();
			return false;
		}

		// Duplication is successful, commit all above changes:
		$DB->commit();

		// Commit changes in cache:
		$TemplateCache = & get_TemplateCache();
		$TemplateCache->add( $this );

		return true;
	}


	/**
	 * Get name of Menu Entry
	 *
	 * @return string Menu Entry
	 */
	function get_name()
	{
		return $this->get( 'name' );
	}


	/**
	 * Get localized child templates
	 * 
	 * @param string Locale
	 * @return array Array of Template objects
	 */
	function get_localized_templates( $locale )
	{
		global $DB;

		if( ! isset( $this->localized_templates[$locale] ) )
		{
			$TemplateCache = & get_TemplateCache();
			$TemplateCache->clear( true );
			$where = 'tpl_translates_tpl_ID = '.$DB->quote( $this->ID ).' AND tpl_locale = '.$DB->quote( $locale );
			$this->localized_templates[$locale] = $TemplateCache->load_where( $where );
		}

		return $this->localized_templates[$locale];
	}


	/**
	 * Check if this template has at least one child
	 *
	 * @return boolean
	 */
	function has_translated_templates()
	{
		global $DB;

		if( $this->ID == 0 )
		{	// New template has no child templates:
			return false;
		}

		if( !isset( $this->count_translated_templates ) )
		{
			$SQL = new SQL( 'Check if template has child templates' );
			$SQL->SELECT( 'COUNT( tpl_translates_tpl_ID )' );
			$SQL->FROM( 'T_templates' );
			$SQL->WHERE( 'tpl_translates_tpl_ID = '.$DB->quote( $this->ID ) );
			$this->count_translated_templates = $DB->get_var( $SQL );
		}

		return ( $this->count_translated_templates > 0 );
	}
}

?>
