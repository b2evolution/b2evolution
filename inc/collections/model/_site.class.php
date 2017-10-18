<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2017 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * Site Class
 *
 * @package evocore
 */
class Site extends DataObject
{
	var $name = '';
	var $order = '';
	var $owner_user_ID = 0;
	var $owner_User = NULL;

	/**
	 * Constructor
	 *
	 * @param object Database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_site', 'site_', 'site_ID' );

		if( $db_row != NULL )
		{
			$this->ID            = $db_row->site_ID;
			$this->name          = $db_row->site_name;
			$this->order         = $db_row->site_order;
			$this->owner_user_ID = $db_row->site_owner_user_ID;
		}
		else
		{
			global $current_User;
			$this->owner_user_ID = $current_User->ID;
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
				array( 'table' => 'T_section', 'fk' => 'sec_site_ID', 'msg' => T_('%d sections in this site') ),
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
		if( $this->ID != 1 )
		{	// Only if not first default site:
			param_string_not_empty( 'site_name', T_('Please enter a site name.') );
			$this->set_from_Request( 'name' );
		}

		// Owner:
		$site_owner_login = param( 'site_owner_login', 'string', '' );
		$UserCache = & get_UserCache();
		$owner_User = & $UserCache->get_by_login( $site_owner_login );
		if( empty( $owner_User ) )
		{
			param_error( 'owner_login', sprintf( T_('User &laquo;%s&raquo; does not exist!'), $site_owner_login ) );
		}
		else
		{
			$this->set( 'owner_user_ID', $owner_User->ID );
			$this->owner_User = & $owner_User;
		}

		// Order:
		if( param( 'site_order', 'integer' ) !== 0 ) // Allow zero value
		{
			param_check_not_empty( 'site_order', T_('Please enter an order number.') );
		}
		$this->set( 'order', param( 'site_order', 'integer' ) );

		return ! param_errors_detected();
	}


	/**
	 * Get site name.
	 *
	 * @return string site name
	 */
	function get_name()
	{
		return $this->name;
	}


	/**
	 * Get owner User
	 *
	 * @return User
	 */
	function & get_owner_User()
	{
		if( ! isset( $this->owner_User ) )
		{
			$UserCache = & get_UserCache();
			$this->owner_User = & $UserCache->get_by_ID( $this->owner_user_ID );
		}

		return $this->owner_User;
	}
}
?>