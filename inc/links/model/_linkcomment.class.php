<?php
/**
 * This file implements the LinkComment class, which is a wrapper class for Comment class to handle linked files.
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

/**
 * LinkComment Class
 *
 * @package evocore
 */
class LinkComment extends LinkOwner
{
	/**
	 * @var Comment
	 */
	var $Comment;

	/**
	 * Parent Item of the Comment
	 * @var object
	 */
	var $Item = NULL;

	/**
	 * Constructor
	 *
	 * @param object Comment
	 * @param integer ID of temporary object from table T_temporary_ID (used for uploads on new comments)
	 */
	function __construct( $Comment, $tmp_ID = NULL )
	{
		parent::__construct( $Comment, 'comment', 'cmt_ID', $tmp_ID );
		$this->Comment = & $this->link_Object;

		$this->_trans = array(
			'Link this image to your xxx' => NT_( 'Link this image to your comment' ),
			'Link this file to your xxx' => NT_( 'Link this file to your comment'),
			'The file will be linked for download at the end of the xxx' => NT_( 'The file will be linked for download at the end of the comment.' ),
			'Insert the following code snippet into your xxx' => NT_( 'Insert the following code snippet into your comment.' ),
			'View this xxx...' => NT_( 'View this comment...' ),
			'Edit this xxx...' => NT_( 'Edit this comment...' ),
			'Link files to current xxx' => NT_( 'Link files to current comment' ),
			'Selected files have been linked to xxx.' => NT_( 'Selected files have been linked to comment.' ),
			'Link has been deleted from $xxx$.' => NT_( 'Link has been deleted from the &laquo;Comment&raquo;.' ),
		);
	}

	/**
	 * Check current User has an access to work with attachments of the link Comment
	 *
	 * @param string permission level
	 * @param boolean true to assert if user dosn't have the required permission
	 * @param object File Root to check permission to add/upload new files
	 * @return boolean
	 */
	function check_perm( $permlevel, $assert = false, $FileRoot = NULL )
	{
		global $current_User;

		$r = false;

		if( $permlevel == 'add' )
		{	// Check permission to add/upload new files:
			$comment_Item = & $this->get_Item();
			$r = $comment_Item->can_attach();
		}
		elseif( $this->is_temp() )
		{	// Check permission for new creating comment:
			$comment_Item = & $this->get_Item();
			$r = $comment_Item->can_comment( NULL );
		}
		else
		{	// Check permission for existing comment in DB:
			$r = is_logged_in() && (
			     ( $this->Comment->is_meta() && $current_User->check_perm( 'meta_comment', $permlevel, $assert, $this->Comment ) ) ||
			     $current_User->check_perm( 'blog_comments', $permlevel, $assert, $this->get_blog_ID() ) );
		}

		if( ! $r && $assert )
		{	// Halt the denied access:
			debug_die( 'You have no permission for comment attachments!' );
		}

		return $r;
	}

	/**
	 * Get all positions ( key, display name ) pairs where link can be displayed
	 *
	 * @return array
	 */
	function get_positions()
	{
		// Should be ordered like the ENUM.
		return array(
			'teaser' => T_( 'Above comment' ),
			'aftermore' => T_( 'Below comment' ),
			);
	}


	/**
	 * Load all links of owner Comment if it was not loaded yet
	 */
	function load_Links()
	{
		if( is_null( $this->Links ) )
		{ // Links have not been loaded yet:
			$LinkCache = & get_LinkCache();
			if( $this->is_temp() )
			{
				$this->Links = $LinkCache->get_by_temporary_ID( $this->get_ID() );
			}
			else
			{
				$this->Links = $LinkCache->get_by_comment_ID( $this->get_ID() );
			}
		}
	}

	/**
	 * Add new link to owner Comment
	 *
	 * @param integer file ID
	 * @param integer link position ( 'teaser', 'aftermore' )
	 * @param int order of the link
	 * @param boolean true to update owner last touched timestamp after link was created, false otherwise
	 * @return integer|boolean Link ID on success, false otherwise
	 */
	function add_link( $file_ID, $position = NULL, $order = 1, $update_owner = true )
	{
		if( is_null( $position ) )
		{ // Use default link position
			$position = $this->get_default_position( $file_ID );
		}

		$edited_Link = new Link();
		$edited_Link->set( $this->get_ID_field_name(), $this->get_ID() );
		$edited_Link->set( 'file_ID', $file_ID );
		$edited_Link->set( 'position', $position );
		$edited_Link->set( 'order', $order );
		if( $edited_Link->dbinsert() )
		{
			$FileCache = & get_FileCache();
			$File = $FileCache->get_by_ID( $file_ID, false, false );
			$file_name = empty( $File ) ? '' : $File->get_name();
			$file_dir = $File->dir_or_file();
			syslog_insert( sprintf( '%s %s was linked to %s with ID=%s', ucfirst( $file_dir ), '[['.$file_name.']]', $this->type, $this->get_ID() ), 'info', 'file', $file_ID );

			if( ! $this->is_temp() && $update_owner )
			{	// Update last touched date of the Comment & Item
				$this->update_last_touched_date();
			}

			// Reset the Links
			$this->Links = NULL;
			$this->load_Links();

			return $edited_Link->ID;
		}

		return false;
	}


	/**
	 * Get Item of the owner Comment
	 */
	function & get_Item()
	{
		if( $this->Item === NULL )
		{	// Try to get Item from DB and store in cache to next requests:
			if( $this->is_temp() )
			{	// If new Comment is creating
				$ItemCache = & get_ItemCache();
				$this->Item = & $ItemCache->get_by_ID( $this->link_Object->tmp_item_ID, false, false );
			}
			else
			{	// If existing Comment is editing
				$this->Item = & $this->Comment->get_Item();
			}
		}

		return $this->Item;
	}


	/**
	 * Load collection of the onwer Comment
	 */
	function load_Blog()
	{
		if( $this->Blog === NULL )
		{	// Load collection of the comment's Item:
			$comment_Item = & $this->get_Item();
			$this->Blog = & $comment_Item->get_Blog();
		}
	}


	/**
	 * Get Comment parameter
	 *
	 * @param string parameter name to get
	 */
	function get( $parname )
	{
		switch( $parname )
		{
			case 'name':
				return 'comment';
			case 'title':
				return T_( 'Comment' );
		}
		return parent::get( $parname );
	}


	/**
	 * Get Comment edit url
	 *
	 * @return string
	 */
	function get_edit_url()
	{
		if( is_admin_page() )
		{	// Back-office:
			global $admin_url;
			if( $this->is_temp() )
			{	// New creating Comment:
				$comment_Item = & $this->get_Item();
				return $admin_url.'?ctrl=items&amp;blog='.$this->get_blog_ID().'&amp;p='.$comment_Item->ID.'#form_p'.$comment_Item->ID;
			}
			else
			{	// The edited Comment:
				return $admin_url.'?ctrl=comments&amp;blog='.$this->get_blog_ID().'&amp;action=edit&amp;comment_ID='.$this->get_ID();
			}
		}
		else
		{	// Front-office:
			if( $this->is_temp() )
			{	// New creating Comment:
				$comment_Item = & $this->get_Item();
				return $comment_Item->get_permanent_url().'#evo_comment_form_id_'.$comment_Item->ID;
			}
			else
			{	// The edited Comment:
				$comment_Blog = & $this->get_Blog();
				return url_add_param( $comment_Blog->get( 'url' ), 'disp=edit_comment&amp;c='.$this->get_ID() );
			}
		}
	}


	/**
	 * Get Comment view url
	 *
	 * @return string
	 */
	function get_view_url()
	{
		return $this->Comment->get_permanent_url();
	}


	/**
	 * Update field last_touched_ts of Comment & Item
	 */
	function update_last_touched_date()
	{
		if( !empty( $this->Comment ) )
		{ // Update Item & Comment if it exist
			$this->Comment->update_last_touched_date();
		}
	}
}

?>