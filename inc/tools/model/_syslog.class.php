<?php
/**
 * This file implements the Syslog class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2013 by Francois PLANQUE - {@link http://fplanque.com/}
 *
 * @package evocore
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * Country Class
 */
class Syslog extends DataObject
{
	var $timestamp;
	var $user_ID;
	var $type;
	var $origin; // Origin type: 'core', 'plugin'
	var $origin_ID;
	var $object; // Object type: 'comment', 'item', 'user'
	var $object_ID;
	var $message;

	/**
	 * Constructor
	 *
	 * @param object database row
	 */
	function Syslog( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_syslog', 'slg_', 'slg_ID' );

		if( $db_row )
		{
			$this->ID = $db_row->slg_ID;
			$this->timestamp = $db_row->slg_timestamp;
			$this->user_ID = $db_row->slg_user_ID;
			$this->type = $db_row->slg_type;
			$this->origin = $db_row->slg_origin;
			$this->origin_ID = $db_row->slg_origin_ID;
			$this->object = $db_row->slg_object;
			$this->object_ID = $db_row->slg_object_ID;
			$this->message = $db_row->slg_message;
		}
	}


	/**
	 * Insert object into DB based on previously recorded changes.
	 *
	 * @return boolean true
	 */
	function dbinsert()
	{
		global $DB, $localtimenow;

		$DB->begin();

		$this->set_param( 'timestamp', 'date', date2mysql( $localtimenow ) );

		$result = parent::dbinsert();

		if( $result )
		{ // Commit current transaction
			$DB->commit();
		}
		else
		{ // Rollback current transaction
			$DB->rollback();
		}

		return $result;
	}


	/**
	 * Set origin
	 *
	 * @param string Origin type: 'core', 'plugin'
	 * @param integer Origin ID
	 */
	function set_origin( $origin, $origin_ID = NULL )
	{
		$this->set( 'origin', $origin );
		if( ! empty( $origin_ID ) )
		{
			$this->set( 'origin_ID', $origin_ID );
		}
	}


	/**
	 * Set object
	 *
	 * @param string Object type: 'comment', 'item', 'user'
	 * @param integer Object ID
	 */
	function set_object( $object, $object_ID )
	{
		$this->set( 'object', $object );
		if( ! empty( $object_ID ) )
		{
			$this->set( 'object_ID', $object_ID );
		}
	}


	/**
	 * Set user
	 *
	 * @param integer User ID
	 */
	function set_user( $user_ID = NULL )
	{
		if( is_null( $user_ID ) && is_logged_in() )
		{
			global $current_User;
			$user_ID = $current_User->ID;
		}

		if( ! empty( $user_ID ) )
		{
			$this->set( 'user_ID', $user_ID );
		}
	}


	/**
	 * Set message
	 *
	 * @param string Message text
	 */
	function set_message( $message )
	{
		// Limit message by 255 chars
		$this->set( 'message', utf8_substr( $message, 0, 255 ) );
	}
}

?>