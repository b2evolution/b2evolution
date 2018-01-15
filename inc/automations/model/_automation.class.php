<?php
/**
 * This file implements the automation class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );


/**
 * Automation Class
 *
 * @package evocore
 */
class Automation extends DataObject
{
	var $name;
	var $status;
	var $first_step_ID;

	/**
	 * Constructor
	 *
	 * @param object table Database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_automation__automation', 'autm_', 'autm_ID' );

		if( $db_row !== NULL )
		{
			$this->ID = $db_row->autm_ID;
			$this->name = $db_row->autm_name;
			$this->status = $db_row->autm_status;
			$this->first_step_ID = $db_row->autm_first_step_ID;
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
				array( 'table' => 'T_automation__step', 'fk' => 'step_autm_ID', 'msg' => T_('%d steps') ),
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
		param_string_not_empty( 'autm_name', T_('Please enter an automation name.') );
		$this->set_from_Request( 'name' );

		// Status:
		param_string_not_empty( 'autm_status', T_('Please select an automation status.') );
		$this->set_from_Request( 'status' );

		return ! param_errors_detected();
	}


	/**
	 * Insert object into DB based on previously recorded changes.
	 *
	 * @return boolean true on success
	 */
	function dbinsert()
	{
		global $DB;

		$DB->begin( 'SERIALIZABLE' );

		if( parent::dbinsert() )
		{	// If the automation has been inserted successful:

			// Insert the first step automatically:
			if( $DB->query( 'INSERT INTO T_automation__step ( step_autm_ID ) VALUES ( '.$this->ID.' )' ) )
			{	// If first step has been inserted successfully:

				// Set first step ID for new inserted automation:
				$this->set( 'first_step_ID', $DB->insert_id );
				$this->dbupdate();

				$DB->commit();
				return true;
			}
		}

		// Could not insert the automation object:
		$DB->rollback();
		return false;
	}


	/**
	 * Get name of automation
	 *
	 * @return string Name of automation
	 */
	function get_name()
	{
		return $this->get( 'name' );
	}
}

?>