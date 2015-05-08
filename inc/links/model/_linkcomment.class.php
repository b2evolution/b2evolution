<?php
/**
 * This file implements the LinkComment class, which is a wrapper class for Comment class to handle linked files.
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
	 * Constructor
	 */
	function LinkComment( $edited_Comment )
	{
		parent::LinkOwner( $edited_Comment, 'comment' );
		$this->Comment = & $this->link_Object;

		$this->_trans = array(
			'Link this image to your xxx' => NT_( 'Link this image to your comment' ),
			'Link this file to your xxx' => NT_( 'Link this file to your comment'),
			'The file will be linked for download at the end of the xxx' => NT_( 'The file will be linked for download at the end of the comment.' ),
			'Insert the following code snippet into your xxx' => NT_( 'Insert the following code snippet into your comment.' ),
			'View this xxx...' => NT_( 'View this comment...' ),
			'Edit this xxx...' => NT_( 'Edit this comment...' ),
			'Click on link %s icons below to link additional files to $xxx$.' => NT_( 'Click on link %s icons below to link additional files to <strong>Comment</strong>.' ),
			'Link files to current xxx' => NT_( 'Link files to current comment' ),
			'Selected files have been linked to xxx.' => NT_( 'Selected files have been linked to comment.' ),
			'Link has been deleted from $xxx$.' => NT_( 'Link has been deleted from the &laquo;Comment&raquo;.' ),
		);
	}

	/**
	 * Check current User Comment permissions
	 *
	 * @param string permission level
	 * @param boolean true to assert if user dosn't have the required permission
	 */
	function check_perm( $permlevel, $assert = false )
	{
		global $current_User;

		$this->load_Blog();
		$comment_Item = $this->Comment->get_Item();

		return ( $this->Comment->is_meta() && $current_User->check_perm( 'meta_comment', $permlevel, $assert, $this->Comment ) )
			|| $current_User->check_perm( 'blog_comments', $permlevel, $assert, $this->Blog->ID );
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
			$this->Links = $LinkCache->get_by_comment_ID( $this->Comment->ID );
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
		$edited_Link->set( 'cmt_ID', $this->Comment->ID );
		$edited_Link->set( 'file_ID', $file_ID );
		$edited_Link->set( 'position', $position );
		$edited_Link->set( 'order', $order );
		if( $edited_Link->dbinsert() )
		{
			$FileCache = & get_FileCache();
			$File = $FileCache->get_by_ID( $file_ID, false, false );
			$file_name = empty( $File ) ? '' : $File->get_name();
			syslog_insert( sprintf( 'File %s was linked to %s with ID=%s', '<b>'.$file_name.'</b>', $this->type, $this->link_Object->ID ), 'info', 'file', $file_ID );

			if( $update_owner )
			{ // Update last touched date of the Comment & Item
				$this->update_last_touched_date();
			}

			return $edited_Link->ID;
		}

		return false;
	}

	/**
	 * Set Blog
	 */
	function load_Blog()
	{
		if( is_null( $this->Blog ) )
		{
			$comment_Item = $this->Comment->get_Item();
			$this->Blog = & $comment_Item->get_Blog();
		}
	}

	/**
	 * Get where condition for select query to get Comment links
	 */
	function get_where_condition() {
		return 'link_cmt_ID = '.$this->Comment->ID;
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
	 */
	function get_edit_url()
	{
		$this->load_Blog();
		return '?ctrl=comments&amp;blog='.$this->Blog->ID.'&amp;action=edit&amp;comment_ID='.$this->Comment->ID;
	}

	/**
	 * Get Comment view url
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