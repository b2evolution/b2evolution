<?php
/**
 * This file implements the A/B Variation Test class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * VariationTest Class
 *
 * @package evocore
 */
class VariationTest extends DataObject
{
	var $name = '';

	/**
	 * Variations
	 *
	 * @var array ( 'ID' => tvar_ID, 'name' => tvar_name )
	 */
	var $variations = NULL;

	/**
	 * Names of variations to update
	 *
	 * @var array
	 */
	var $update_variations = array();

	/**
	 * IDs of variations to delete
	 *
	 * @var array
	 */
	var $delete_variations = array();


	/**
	 * Constructor
	 *
	 * @param object Database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_vtest__test', 'vtst_', 'vtst_ID' );

		if( $db_row )
		{
			$this->ID   = $db_row->vtst_ID;
			$this->name = $db_row->vtst_name;
			$this->load_variations();
		}
		else
		{ // New variation test
			// Prefill variations:
			$this->variations[0] = array( 'name' => 'A' );
			$this->variations[1] = array( 'name' => 'B' );
		}
		
	}


	/**
	 * Initialize relations for restrict and cascade deletion.
	 */
	function init_relations()
	{
		if( ! is_null( $this->delete_cascades ) || ! is_null( $this->delete_restrictions ) )
		{ // Initialize the relations only once
			return;
		}
	}


	/**
	 * Generate help title text for action
	 *
	 * @param string action code: edit, delete, etc.
	 * @return string translated help string
	 */
	function get_action_title( $action )
	{
		switch( $action )
		{
			case 'edit': return T_('Edit this variation test...');
			case 'delete': return T_('Delete this variation test!');
			default:
				return '';
		}
	}


	/**
	 * Check permission on a persona
	 *
	 * @return boolean true if granted
	 */
	function check_perm( $action = 'view', $assert = true )
	{
		/**
		* @var User
		*/
		global $current_User;

		return $current_User->check_perm( 'stats', $action, $assert );
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// Name
		$this->set_string_from_param( 'name', true );

		// Variations
		$variations = param( 'tvar_name', 'array:string', array() );
		$v = 0;
		foreach( $variations as $variation )
		{
			if( ! empty( $variation ) )
			{ // Update/Insert this variation on dbupdate
				$this->update_variations[] = $variation;
				$this->variations[ $v ]['name'] = $variation;
				$v++;
			}
			else if( ! empty( $this->variations[ $v ]['ID'] ) )
			{ // Delete this variation on dbupdate
				$this->delete_variations[] = $this->variations[ $v ]['ID'];
				$v++;
			}
		}

		if( count( $this->update_variations ) < 2 )
		{
			global $Messages;
			$Messages->add( T_( 'Please enter at least two variations!' ), 'error' );
		}

		return ! param_errors_detected();
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
			// Update/Insert/Delete variations
			$this->dbsave_variations();
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

		// Update/Insert/Delete variations
		$this->dbsave_variations();

		$DB->commit();
	}


	/**
	 * Update the DB based on previously recorded changes
	 */
	function dbdelete()
	{
		global $DB;

		$DB->begin();

		$vtst_ID = $this->ID;

		if( parent::dbdelete() )
		{
			// Delete all custom fields of this item type
			$DB->query( 'DELETE FROM T_vtest__variation
				WHERE tvar_vtst_ID = '.$vtst_ID );
		}

		$DB->commit();
	}


	/**
	 * Update/Insert/Delete variations
	 */
	function dbsave_variations()
	{
		global $DB;

		$this->load_variations( true );

		if( ! empty( $this->update_variations ) )
		{
			$sql_insert_data = array();

			foreach( $this->update_variations as $v => $update_variations_name )
			{
				if( isset( $this->variations[ $v ] ) )
				{ // This variation exists in DB, Update the record
					$DB->query( 'UPDATE T_vtest__variation
						  SET tvar_name = '.$DB->quote( $update_variations_name ).'
						WHERE tvar_ID = '.$this->variations[ $v ]['ID'] );
				}
				else
				{ // This variation doesn't exist in DB, Insert the record
					$sql_insert_data[] = '( '.$DB->quote( $this->ID ).', '.$DB->quote( $update_variations_name ).' )';
				}
			}

			if( ! empty( $sql_insert_data ) )
			{ // Insert new records
				$DB->query( 'INSERT INTO T_vtest__variation ( tvar_vtst_ID, tvar_name )
					VALUES '.implode( ', ', $sql_insert_data ) );
			}
		}

		if( ! empty( $this->delete_variations ) )
		{ // Delete the variations from DB
			$DB->query( 'DELETE FROM T_vtest__variation
				WHERE tvar_ID IN ( '.implode( ', ', $this->delete_variations ).' )' );
		}
	}


	/**
	 * Get name of variation test
	 * 
	 * @return string Name
	 */
	function get_name()
	{
		return $this->name;
	}


	/**
	 * Get variation name
	 *
	 * @param string Variation name
	 */
	function get_variation_name( $index )
	{
		$this->load_variations();

		return isset( $this->variations[ $index ] ) ? $this->variations[ $index ]['name'] : '';
	}


	/**
	 * Load variations
	 */
	function load_variations( $force_load = false )
	{
		if( $force_load || is_null( $this->variations ) )
		{ // Initialize variation names
			if( $this->ID > 0 )
			{ // Load variations from DB
				global $DB;
				$SQL = new SQL();
				$SQL->SELECT( 'tvar_ID AS ID, tvar_name AS name' );
				$SQL->FROM( 'T_vtest__variation' );
				$SQL->WHERE( 'tvar_vtst_ID = '.$this->ID );
				$SQL->ORDER_BY( 'tvar_ID' );
				$this->variations = $DB->get_results( $SQL->get(), ARRAY_A );
			}
			else
			{ // It is new variation test, Init empty array
				$this->variations = array();
			}
		}
	}
}

?>