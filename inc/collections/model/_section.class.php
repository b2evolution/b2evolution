<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * Section Class
 *
 * @package evocore
 */
class Section extends DataObject
{
	var $name = '';
	var $order = '';
	var $owner_user_ID = 0;
	var $owner_User = NULL;

	/**
	 * @var array Array of "Blog" objects, @see Section::get_blogs()
	 */
	var $blogs;

	/**
	 * Constructor
	 *
	 * @param object Database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_section', 'sec_', 'sec_ID' );

		if( $db_row != NULL )
		{
			$this->ID            = $db_row->sec_ID;
			$this->name          = $db_row->sec_name;
			$this->order         = $db_row->sec_order;
			$this->owner_user_ID = $db_row->sec_owner_user_ID;
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
				array( 'table' => 'T_blogs', 'fk' => 'blog_sec_ID', 'msg' => T_('%d collections in this group') ),
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
		{	// Only if not first default section:
			param_string_not_empty( 'sec_name', T_('Please enter a section name.') );
			$this->set_from_Request( 'name' );
		}

		// Owner:
		$sec_owner_login = param( 'sec_owner_login', 'string', '' );
		$UserCache = & get_UserCache();
		$owner_User = & $UserCache->get_by_login( $sec_owner_login );
		if( empty( $owner_User ) )
		{
			param_error( 'owner_login', sprintf( T_('User &laquo;%s&raquo; does not exist!'), $sec_owner_login ) );
		}
		else
		{
			$this->set( 'owner_user_ID', $owner_User->ID );
			$this->owner_User = & $owner_User;
		}

		// Order:
		if( param( 'sec_order', 'integer' ) !== 0 ) // Allow zero value
		{
			param_check_not_empty( 'sec_order', T_('Please enter an order number.') );
		}
		$this->set( 'order', param( 'sec_order', 'integer' ) );

		return ! param_errors_detected();
	}


	/**
	 * Get section name.
	 *
	 * @return string section name
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


	/**
	 * Get collections of this section
	 *
	 * @return array Array of "Blog" objects
	 */
	function get_blogs()
	{
		if( empty( $this->ID ) )
		{	// New section creating has no collections yet, Return an empty array:
			return array();
		}

		if( ! isset( $this->blogs ) )
		{	// Load collections of this section only on first request and cache the result:
			$BlogCache = & get_BlogCache();
			$BlogCache->clear();
			$BlogCache->load_where( 'blog_sec_ID = '.$this->ID );
			$this->blogs = $BlogCache->cache;
		}

		return $this->blogs;
	}
}
?>