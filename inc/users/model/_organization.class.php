<?php
/**
 * This file implements the Organization class, which manages user organizations.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
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
	 * Owner ID
	 * @var integer
	 */
	var $owner_user_ID;

	/**
	 * Accept level
	 * @var string: 'yes', 'owner', 'no'
	 */
	var $accept = 'owner';

	/**
	 * Edit Role
	 * @var string: 'owner and member', 'owner'
	 */
	var $perm_role = 'owner and member';

	/**
	 * Constructor
	 *
	 * @param object DB row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_users__organization', 'org_', 'org_ID' );

		if( $db_row != NULL )
		{ // Loading an object from DB:
			$this->ID            = $db_row->org_ID;
			$this->owner_user_ID = $db_row->org_owner_user_ID;
			$this->name          = $db_row->org_name;
			$this->url           = $db_row->org_url;
			$this->accept        = $db_row->org_accept;
			$this->perm_role     = $db_row->org_perm_role;
		}
		else
		{	// Set default organization data for new object:
			if( is_logged_in() )
			{
				global $current_User;
				$this->set( 'owner_user_ID', $current_User->ID );
			}
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
		global $current_User;

		// Owner:
		if( $current_User->check_perm( 'orgs', 'edit' ) )
		{	// Update the owner if current user has a permission to edit all organizations:
			$pqst_owner_login = param( 'org_owner_login', 'string', NULL );
			param_check_not_empty( 'org_owner_login', T_('Please enter the owner\'s login.') );
			if( ! empty( $pqst_owner_login ) )
			{	// If the login is entered:
				$UserCache = & get_UserCache();
				$owner_User = & $UserCache->get_by_login( $pqst_owner_login );
				if( empty( $owner_User ) )
				{	// Wrong entered login:
					param_error( 'org_owner_login', sprintf( T_('User &laquo;%s&raquo; does not exist!'), $pqst_owner_login ) );
				}
				else
				{	// Set new login:
					$this->set( 'owner_user_ID', $owner_User->ID );
					$this->owner_User = & $owner_User;
				}
			}
		}
		elseif( empty( $this->ID ) )
		{	// Set onwer user ID on creating new poll:
			$this->set( 'owner_user_ID', $current_User->ID );
			$this->owner_User = & $current_User;
		}

		// Name
		param( 'org_name', 'string' );
		param_check_not_empty( 'org_name', T_('You must provide a name!') );
		$this->set_from_Request( 'name', 'org_name' );

		// Url
		param( 'org_url', 'string' );
		param_check_url( 'org_url', 'commenting' );
		$this->set_from_Request( 'url', 'org_url', true );

		// Accept level:
		param( 'org_accept', 'string' );
		$this->set_from_Request( 'accept' );
		
		// Edit Role Permission:
		param( 'org_perm_role', 'string' );
		$this->set_from_Request( 'perm_role' );

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
	function get_users( $order_by = 'user_id', $accepted_only = false )
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
		if( $accepted_only )
		{
			$users_SQL->WHERE_and( 'uorg_accepted = 1' );
		}
		
		switch( $order_by )
		{
			case 'user_level':
				$users_SQL->ORDER_BY( 'user_level DESC, user_ID ASC' );
				break;
			case 'org_role':
				$users_SQL->ORDER_BY( 'uorg_role ASC, user_ID ASC' );
				break;
			case 'username':
				$users_SQL->ORDER_BY( 'user_login ASC, user_ID ASC' );
				break;
			case 'lastname':
				$users_SQL->ORDER_BY( 'user_lastname ASC, user_ID ASC' );
				break;
			case 'firstname':
				$users_SQL->ORDER_BY( 'user_firstname ASC, user_ID ASC' );
				break;
			default:
				$users_SQL->ORDER_BY( 'user_id ASC' );
		}
		$user_IDs = $DB->get_col( $users_SQL->get() );

		$UserCache = & get_UserCache();

		foreach( $user_IDs as $user_ID )
		{
			$users[] = & $UserCache->get_by_ID( $user_ID, false, false );
		}

		return $users;
	}


	/**
	 * Get user object of this organization owner
	 *
	 * @return object User
	 */
	function & get_owner_User()
	{
		if( ! isset( $this->owner_User ) )
		{	// Get the owner User only first time:
			$UserCache = & get_UserCache();
			$this->owner_User = & $UserCache->get_by_ID( $this->owner_user_ID );
		}

		return $this->owner_User;
	}


	/**
	 * Check if this organization can be auto accepted
	 *
	 * @return boolean
	 */
	function can_be_autoaccepted()
	{
		if( $this->get( 'accept' ) == 'yes' )
		{	// This organization should be accepted immediately:
			return true;
		}
		else
		{	// Check permission:
			global $current_User;
			if( is_logged_in() && $current_User->check_perm( 'orgs', 'edit', false, $this ) )
			{	// If current user has a perm to edit this organization then also allow to auto accept it:
				return true;
			}
		}

		// No reason to auto accept this organization for current user:
		return false;
	}
}

?>