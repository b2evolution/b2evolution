<?php
/**
 * This file implements the LinkUser class, which is a wrapper class for User class to handle linked files.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
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
	function __construct( $User )
	{
		// call parent contsructor
		parent::__construct( $User, 'user', 'usr_ID' );
		$this->User = & $this->link_Object;

		$this->_trans = array(
			'Link this image to your xxx' => NT_( 'Link this image to the user.' ),
			'Link this file to your xxx' => NT_( 'Link this file to the user.'),
			'View this xxx...' => NT_( 'View this user...' ),
			'Edit this xxx...' => NT_( 'Edit this user...' ),
			'Link files to current xxx' => NT_( 'Link files to current user' ),
			'Link has been deleted from $xxx$.' => NT_( 'Link has been deleted from &laquo;user&raquo;.' ),
			'Cannot delete Link from $xxx$.' => NT_( 'Cannot delete Link from &laquo;user&raquo;.' ),
		);
	}

	/**
	 * Check current User has an access to work with attachments of the link User
	 *
	 * @param string Permission level
	 * @param boolean TRUE to assert if user dosn't have the required permission
	 * @param object File Root to check permission to add/upload new files
	 * @return boolean
	 */
	function check_perm( $permlevel, $assert = false, $FileRoot = NULL )
	{
		global $current_User;

		if( ! is_logged_in() )
		{	// User must be logged in:
			if( $assert )
			{	// Halt the denied access:
				debug_die( 'You have no permission for user attachments!' );
			}
			return false;
		}

		if( $permlevel == 'add' )
		{	// Check permission to add/upload new files:
			return $current_User->check_perm( 'files', $permlevel, $assert, $FileRoot );
		}

		return $current_User->ID == $this->User->ID || $current_User->check_perm( 'users', $permlevel, $assert );
	}

	/**
	 * Get all positions ( key, display ) pairs where link can be displayed
	 *
	 * @param integer File ID
	 * @return array
	 */
	function get_positions( $file_ID = NULL )
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
		$edited_Link->set( $this->get_ID_field_name(), $this->get_ID() );
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
			$file_dir = $File->dir_or_file( 'Directory', 'File' );
			syslog_insert( sprintf( '%s %s was linked to %s with ID=%s', $file_dir, '[['.$file_name.']]', $this->type, $this->get_ID() ), 'info', 'file', $file_ID );

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
				return $this->User->get_username();
		}
		return parent::get( $parname );
	}

	/**
	 * Get User edit url
	 *
	 * @param string Delimiter to use for multiple params (typically '&amp;' or '&')
	 * @param string URL type: 'frontoffice', 'backoffice'
	 * @return string URL
	 */
	function get_edit_url( $glue = '&amp;', $url_type = NULL )
	{
		global $admin_url;

		return $admin_url.'?ctrl=user'.$glue.'user_tab=avatar'.$glue.'user_ID='.$this->User->ID;
	}

	/**
	 * Get User view url
	 *
	 * @param string Delimiter to use for multiple params (typically '&amp;' or '&')
	 * @param string URL type: 'frontoffice', 'backoffice'
	 * @return string URL
	 */
	function get_view_url( $glue = '&amp;', $url_type = NULL )
	{
		global $admin_url;

		return $admin_url.'?ctrl=user'.$glue.'user_tab=profile'.$glue.'user_ID='.$this->User->ID;
	}
}

?>