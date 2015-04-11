<?php
/**
 * This file implements the Organization class, which manages user organizations.
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
class Organization extends DataObject
{
	/**
	 * Name
	 * @var string
	 */
	var $name;

	/**
	 * Url
	 * @var string
	 */
	var $url;

	/**
	 * Constructor
	 *
	 * @param object DB row
	 */
	function Organization( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_users__organization', 'org_', 'org_ID' );

		if( $db_row != NULL )
		{ // Loading an object from DB:
			$this->ID   = $db_row->org_ID;
			$this->name = $db_row->org_name;
			$this->url  = $db_row->org_url;
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
				array( 'table'=>'T_users__user_org', 'fk'=>'uorg_org_ID', 'msg'=>T_('%d users in this organization') ),
			);
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// Name
		param( 'org_name', 'string' );
		param_check_not_empty( 'org_name', T_('You must provide a name!') );
		$this->set_from_Request( 'name', 'org_name' );

		// Url
		param( 'org_url', 'string' );
		param_check_url( 'org_url', 'commenting' );
		$this->set_from_Request( 'url', 'org_url', true );

		return ! param_errors_detected();
	}


	/**
	 * Get organization name.
	 *
	 * @return string organization name
	 */
	function get_name()
	{
		return $this->name;
	}


	/**
	 * Get all users of this organization
	 *
	 * return array User objects
	 */
	function get_users()
	{
		global $DB;

		$users = array();

		if( empty( $this->ID ) )
		{ // Return empty array for new creating organization
			return $users;
		}

		$users_SQL = new SQL();
		$users_SQL->SELECT( 'uorg_user_ID' );
		$users_SQL->FROM( 'T_users__user_org' );
		$users_SQL->FROM_add( 'INNER JOIN T_users ON uorg_user_ID = user_ID' );
		$users_SQL->WHERE( 'uorg_org_ID = '.$DB->quote( $this->ID ) );
		$users_SQL->ORDER_BY( 'user_level DESC, user_lastname ASC, user_firstname ASC' );
		$user_IDs = $DB->get_col( $users_SQL->get() );

		$UserCache = & get_UserCache();

		foreach( $user_IDs as $user_ID )
		{
			$users[] = & $UserCache->get_by_ID( $user_ID, false, false );
		}

		return $users;
	}
}

?>