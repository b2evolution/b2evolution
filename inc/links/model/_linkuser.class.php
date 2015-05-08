<?php
/**
 * This file implements the LinkUser class, which is a wrapper class for User class to handle linked files.
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

/**
 * LinkUser Class
 *
 * @package evocore
 */
class LinkUser extends LinkOwner
{
	/**
	 * @var User
	 */
	var $User;

	/**
	 * Constructor
	 */
	function LinkUser( $User )
	{
		// call parent contsructor
		parent::LinkOwner( $User, 'user' );
		$this->User = & $this->link_Object;

		$this->_trans = array(
			'Link this image to your xxx' => NT_( 'Link this image to the user.' ),
			'Link this file to your xxx' => NT_( 'Link this file to the user.'),
			'View this xxx...' => NT_( 'View this user...' ),
			'Edit this xxx...' => NT_( 'Edit this user...' ),
			'Click on link %s icons below to link additional files to $xxx$.' => NT_( 'Click on link %s icons below to link additional files to <strong>user</strong>.' ),
			'Link files to current xxx' => NT_( 'Link files to current user' ),
			'Link has been deleted from $xxx$.' => NT_( 'Link has been deleted from &laquo;user&raquo;.' ),
		);
	}

	/**
	 * Check current User users permission
	 *
	 * @param string permission level
	 * @param boolean true to assert if user dosn't have the required permission
	 */
	function check_perm( $permlevel, $assert = false )
	{
		global $current_User;
		return $current_User->ID == $this->User->ID || $current_User->check_perm( 'users', $permlevel, $assert );
	}

	/**
	 * Get all positions ( key, display ) pairs where link can be displayed
	 *
	 * @return array
	 */
	function get_positions()
	{
		return array();
	}

	/**
	 * Load all links of owner User if it was not loaded yet
	 */
	function load_Links()
	{
		if( is_null( $this->Links ) )
		{ // Links have not been loaded yet:
			$LinkCache = & get_LinkCache();
			$this->Links = $LinkCache->get_by_user_ID( $this->User->ID );
		}
	}

	/**
	 * Clear all links of owner User
	 */
	function clear_Links()
	{
		if( ! is_null( $this->Links ) )
		{ // Links have been loaded:
			$this->Links = NULL;
			$LinkCache = & get_LinkCache();
			$LinkCache->clear( false, 'user', $this->User->ID );
		}
	}

	/**
	 * Add new link to owner User
	 *
	 * @param integer file ID
	 * @param integer link position ( 'teaser', 'aftermore' )
	 * @param int order of the link
	 * @return integer|boolean Link ID on success, false otherwise
	 */
	function add_link( $file_ID, $position = NULL, $order = 1 )
	{
		global $current_User;

		if( is_null( $position ) )
		{ // Use default link position
			$position = $this->get_default_position( $file_ID );
		}

		$edited_Link = new Link();
		$edited_Link->set( 'usr_ID', $this->User->ID );
		$edited_Link->set( 'file_ID', $file_ID );
		$edited_Link->set( 'position', $position );
		$edited_Link->set( 'order', $order );

		if( empty( $current_User ) )
		{ // Current User not is set because probably we are creating links from upgrade script. Set the owner as creator and last editor.
			$edited_Link->set( 'creator_user_ID', $this->User->ID );
			$edited_Link->set( 'lastedit_user_ID', $this->User->ID );
		}
		if( $edited_Link->dbinsert() )
		{
			if( ! is_null( $this->Links ) )
			{ // If user Links were already loaded update its content 
				$this->Links[$edited_Link->ID] = & $edited_Link;
			}
			$FileCache = & get_FileCache();
			$File = $FileCache->get_by_ID( $file_ID, false, false );
			$file_name = empty( $File ) ? '' : $File->get_name();
			syslog_insert( sprintf( 'File %s was linked to %s with ID=%s', '<b>'.$file_name.'</b>', $this->type, $this->link_Object->ID ), 'info', 'file', $file_ID );

			return $edited_Link->ID;
		}

		return false;
	}

	/**
	 * Set Blog
	 */
	function load_Blog()
	{
		// User has no blog
	}

	/**
	 * Get where condition for select query to get User links
	 */
	function get_where_condition() {
		return 'link_usr_ID = '.$this->User->ID;
	}

	/**
	 * Get User parameter
	 *
	 * @param string parameter name to get
	 */
	function get( $parname )
	{
		switch( $parname )
		{
			case 'name':
				return 'user';
			case 'title':
				return $this->User->login;
		}
		return parent::get( $parname );
	}

	/**
	 * Get User edit url
	 */
	function get_edit_url()
	{
		return '?ctrl=user&amp;user_tab=avatar&amp;user_ID='.$this->User->ID;
	}

	/**
	 * Get User view url
	 */
	function get_view_url()
	{
		return '?ctrl=user&amp;user_tab=profile&amp;user_ID='.$this->User->ID;
	}
}

?>