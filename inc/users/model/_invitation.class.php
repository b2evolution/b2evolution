<?php
/**
 * This file implements the Group class, which manages user invitations.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * User Invitation Code
 */
class Invitation extends DataObject
{
	/**
	 * Code
	 *
	 * Please use get/set functions to read or write this param
	 *
	 * @var string
	 */
	var $code;

	var $expire_ts;
	var $source;

	/**
	 * Group ID
	 *
	 * @var integer
	 */
	var $grp_ID;

	/**
	 * Constructor
	 *
	 * @param object DB row
	 */
	function Invitation( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_users__invitation_code', 'ivc_', 'ivc_ID' );

		if( $db_row != NULL )
		{ // Loading an object from DB:
			$this->ID        = $db_row->ivc_ID;
			$this->code      = $db_row->ivc_code;
			$this->expire_ts = strtotime( $db_row->ivc_expire_ts );
			$this->source    = $db_row->ivc_source;
			$this->grp_ID    = $db_row->ivc_grp_ID;
		}
		else
		{ // New object:
			global $localtimenow, $Settings;
			$this->expire_ts = $localtimenow;
			$this->grp_ID = $Settings->get('newusers_grp_ID');
		}
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		global $Messages, $localtimenow;

		// Group ID
		param( 'ivc_grp_ID', 'integer' );
		param_check_not_empty( 'ivc_grp_ID', T_('Please select a group') );
		$this->set_from_Request( 'grp_ID', 'ivc_grp_ID', true );

		// Code
		param( 'ivc_code', 'string' );
		param_check_not_empty( 'ivc_code', T_('You must provide an invitation code!') );
		param_check_regexp( 'ivc_code', '#^[A-Za-z0-9\-_]{3,32}$#', T_('Invitation code must be from 3 to 32 letters, digits or signs "-", "_".') );
		$this->set_from_Request( 'code', 'ivc_code' );

		// Expire date
		if( param_date( 'ivc_expire_date', T_('Please enter a valid date.'), true ) && ( param_time( 'ivc_expire_time' ) ) )
		{ // If date and time were both correct we may set the 'expire_ts' value
			$this->set( 'expire_ts', form_date( get_param( 'ivc_expire_date' ), get_param( 'ivc_expire_time' ) ) );
		}

		// Source
		param( 'ivc_source', 'string' );
		$this->set_from_Request( 'source', 'ivc_source', true );

		if( mysql2timestamp( $this->get( 'expire_ts' ) ) < $localtimenow )
		{ // Display a warning if date is expired
			$Messages->add( $this->ID == 0 ?
				T_('Note: The newly created invitation code is already expired') :
				T_('Note: The updated invitation code is already expired'), 'warning' );
		}

		return ! param_errors_detected();
	}
}

?>