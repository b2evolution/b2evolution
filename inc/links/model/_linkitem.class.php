<?php
/**
 * This file implements the LinkItem class, which is a wrapper class for Item class to handle linked files.
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
 * LinkItem Class
 *
 * @package evocore
 */
class LinkItem extends LinkOwner
{
	/**
	 * @var Item
	 */
	var $Item;

	/**
	 * Constructor
	 *
	 * @param object Item
	 * @param integer ID of temporary object from table T_temporary_ID (used for uploads on new items)
	 */
	function __construct( $Item, $tmp_ID = NULL )
	{
		// call parent contsructor
		parent::__construct( $Item, 'item', 'itm_ID', $tmp_ID );
		$this->Item = & $this->link_Object;

		$this->_trans = array(
			'Link this image to your xxx' => NT_( 'Link this image to your item.' ),
			'Link this file to your xxx' => NT_( 'Link this file to your item.'),
			'The file will be linked for download at the end of the xxx' => NT_( 'The file will be appended for linked at the end of the item.' ),
			'Insert the following code snippet into your xxx' => NT_( 'Insert the following code snippet into your item.' ),
			'View this xxx...' => NT_( 'View this item...' ),
			'Edit this xxx...' => NT_( 'Edit this item...' ),
			'Link files to current xxx' => NT_( 'Link files to current item' ),
			'Selected files have been linked to xxx.' => NT_( 'Selected files have been linked to item.' ),
			'Link has been deleted from $xxx$.' => NT_( 'Link has been deleted from &laquo;item&raquo;.' ),
		);
	}

	/**
	 * Check current User Item permission
	 *
	 * @param string permission level
	 * @param boolean true to assert if user dosn't have the required permission
	 */
	function check_perm( $permlevel, $assert = false )
	{
		global $current_User;

		if( $this->is_temp() )
		{	// Check permission for new creating item:
			return $current_User->check_perm( 'blog_post_statuses', 'edit', false, $this->link_Object->tmp_coll_ID );
		}
		else
		{	// Check permission for existing item in DB:
			return $current_User->check_perm( 'item_post!CURSTATUS', $permlevel, $assert, $this->Item );
		}
	}

	/**
	 * Get all positions ( key, display ) pairs where link can be displayed
	 *
	 * @param integer File ID
	 * @return array
	 */
	function get_positions( $file_ID = NULL )
	{
		$positions = array();

		$FileCache = & get_FileCache();
		$File = $FileCache->get_by_ID( $file_ID, false, false );
		if( $File && $File->is_image() )
		{ // Only images can have this position
			// TRANS: Noun - we're talking about a cover image i-e: an image that used as cover for a post
			$positions['cover'] = T_('Cover');
		}

		$positions = array_merge( $positions, array(
				// TRANS: Noun - we're talking about a teaser image i-e: an image that appears before content
				'teaser'     => T_('Teaser'),
				// TRANS: Noun - we're talking about a teaser image i-e: an image that appears before content and with image url linked to permalink
				'teaserperm' => T_('Teaser-Permalink'),
				// TRANS: Noun - we're talking about a teaser image i-e: an image that appears before content and with image url linked to external link
				'teaserlink' => T_('Teaser-Ext Link'),
				// TRANS: Noun - we're talking about a footer image i-e: an image that appears after "more" content separator
				'aftermore'  => T_('After "more"'),
				// TRANS: noun - we're talking about an inline image i-e: an image that appears in the middle of some text
				'inline'     => T_('Inline')
			) );

		if( $File && $File->is_image() )
		{ // Only images can have this position
			// TRANS: Noun - we're talking about a fallback image i-e: an image that used as fallback for video file
			$positions['fallback'] = T_('Fallback');
		}

		$positions['attachment'] = T_('Attachment');

		return $positions;
	}

	/**
	 * Get default position for a new link
	 *
	 * @param integer File ID
	 * @return string Position
	 */
	function get_default_position( $file_ID )
	{
		$FileCache = & get_FileCache();
		$File = & $FileCache->get_by_ID( $file_ID, false, false );
		if( empty( $File ) )
		{ // If file is broken then get simple default position as "aftermore"
			return 'aftermore';
		}

		if( $File->is_image() )
		{ // If file is image then get position depending on order
			$this->load_Links();

			if( $this->Links )
			{ // There's only one file attached yet, the second becomes "aftermore"
				return 'aftermore';
			}
			else
			{ // No attachment yet
				return 'teaser';
			}
		}
		else
		{ // If file is not image then always use "aftermore"
			return 'aftermore';
		}
	}

	/**
	 * Load all links of owner Item if it was not loaded yet
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
				$this->Links = $LinkCache->get_by_item_ID( $this->Item->ID );
			}
		}
	}

	/**
	 * Add new link to owner Item
	 *
	 * @param integer file ID
	 * @param integer link position ( 'teaser', 'teaserperm', 'teaserlink', 'aftermore', 'inline', 'fallback' )
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
			if( ! $this->is_temp() )
			{	// New link was added to the item, invalidate blog's media BlockCache:
				BlockCache::invalidate_key( 'media_coll_ID', $this->Item->get_blog_ID() );
			}

			$FileCache = & get_FileCache();
			$File = $FileCache->get_by_ID( $file_ID, false, false );
			$file_name = empty( $File ) ? '' : $File->get_name();
			$file_dir = $File->dir_or_file();
			syslog_insert( sprintf( '%s %s was linked to %s with ID=%s',  ucfirst( $file_dir ), '[['.$file_name.']]', $this->type, $this->get_ID() ), 'info', 'file', $file_ID );

			if( ! $this->is_temp() && $update_owner )
			{	// Update last touched date of the Item:
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
	 * Set Blog
	 */
	function load_Blog()
	{
		if( is_null( $this->Blog ) )
		{
			$Item = $this->Item;
			if( $Item->ID == 0 )
			{	// This is a request of new creating Item (for example, preview mode),
				// We should use current collection, because new Item has no category ID yet here to load Collection:
				global $Blog;
				$this->Blog = $Blog;
			}
			else
			{	// Use Collection of the existing Item:
				$this->Blog = & $this->Item->get_Blog();
			}
		}
	}


	/**
	 * Get Item parameter
	 *
	 * @param string parameter name to get
	 */
	function get( $parname )
	{
		switch( $parname )
		{
			case 'name':
				return 'post';
		}
		return parent::get( $parname );
	}

	/**
	 * Get Item edit url
	 *
	 * @return string URL
	 */
	function get_edit_url()
	{
		if( is_admin_page() )
		{	// Back-office:
			global $admin_url;
			if( $this->is_temp() )
			{	// New creating Item:
				return $admin_url.'?ctrl=items&amp;blog='.$this->link_Object->tmp_coll_ID.'&amp;action=new';
			}
			else
			{	// The edited Item:
				$this->load_Blog();
				return $admin_url.'?ctrl=items&amp;blog='.$this->Blog->ID.'&amp;action=edit&amp;p='.$this->Item->ID;
			}
		}
		else
		{	// Front-office:
			global $Blog;
			if( $this->is_temp() )
			{	// New creating Item:
				return url_add_param( $Blog->get( 'url' ), 'disp=edit' );
			}
			else
			{	// The edited Item:
				return url_add_param( $Blog->get( 'url' ), 'disp=edit&amp;p='.$this->Item->ID );
			}
		}
	}

	/**
	 * Get Item view url
	 */
	function get_view_url()
	{
		if( is_admin_page() )
		{	// Back-office:
			global $admin_url;
			if( $this->is_temp() )
			{	// New creating Item:
				return $admin_url.'?ctrl=items&amp;blog='.$this->link_Object->tmp_coll_ID.'&amp;action=new';
			}
			else
			{	// The edited Item:
				$this->load_Blog();
				return $admin_url.'?ctrl=items&amp;blog='.$this->Blog->ID.'&amp;p='.$this->Item->ID;
			}
		}
		else
		{	// Front-office:
			global $Blog;
			if( $this->is_temp() )
			{	// New creating Item:
				return url_add_param( $Blog->get( 'url' ), 'disp=edit' );
			}
			else
			{	// The edited Item:
				return url_add_param( $Blog->get( 'url' ), 'disp=edit&amp;p='.$this->Item->ID );
			}
		}
	}


	/**
	 * Update field last_touched_ts of Item
	 */
	function update_last_touched_date()
	{
		if( ! empty( $this->Item ) && ! $this->is_temp() )
		{	// Update Item if it exists
			$this->Item->update_last_touched_date();
		}
	}

	/**
	 * This function is called after when some file was unlinked from item
	 *
	 * @param integer Link ID
	 */
	function after_unlink_action( $link_ID = 0 )
	{
		if( empty( $this->Item ) )
		{ // No existing Item, Exit here
			return;
		}

		if( ! empty( $link_ID ) )
		{ // Find inline image placeholders if link ID is defined
			preg_match_all( '/\[(image|file|inline|video|audio|thumbnail):'.$link_ID.':?[^\]]*\]/i', $this->Item->content, $inline_images );
			if( ! empty( $inline_images[0] ) )
			{ // There are inline image placeholders in the post content
				$this->Item->set( 'content', str_replace( $inline_images[0], '', $this->Item->content ) );
				$this->Item->dbupdate();
				return;
			}
		}

		if( ! $this->is_temp() )
		{	// Update last touched date of the Item
			$this->update_last_touched_date();
		}
	}
}

?>