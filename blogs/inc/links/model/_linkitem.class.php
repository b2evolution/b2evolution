<?php
/**
 * This file implements the LinkItem class, which is a wrapper class for Item class to handle linked files.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-asimo: Attila Simo.
 *
 * @version $Id: $
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
	 */
	function LinkItem( $Item )
	{
		// call parent contsructor
		parent::LinkOwner( $Item, 'item' );
		$this->Item = & $this->link_Object;

		$this->_trans = array(
			'Link this image to your owner' => NT_( 'Link this image to your item.' ),
			'Link this file to your owner' => NT_( 'Link this file to your item.'),
			'The file will be linked for download at the end of the owner' => NT_( 'The file will be appended for linked at the end of the item.' ),
			'Insert the following code snippet into your owner' => NT_( 'Insert the following code snippet into your item.' ),
			'View this owner...' => NT_( 'View this item...' ),
			'Edit this owner...' => NT_( 'Edit this item...' ),
			'Click on link %s icons below to link additional files to $ownerTitle$.' => NT_( 'Click on link %s icons below to link additional files to <strong>item</strong>.' ),
			'Link files to current owner' => NT_( 'Link files to current item' ),
			'Selected files have been linked to owner.' => NT_( 'Selected files have been linked to item.' ),
			'Link has been deleted from $ownerTitle$.' => NT_( 'Link has been deleted from &laquo;item&raquo;.' ),
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
		return $current_User->check_perm( 'item_post!CURSTATUS', $permlevel, $assert, $this->Item );
	}

	/**
	 * Get all positions ( key, display ) pairs where link can be displayed
	 *
	 * @param integer File ID
	 * @return array
	 */
	function get_positions( $file_ID = NULL )
	{
		$additional_positions = array();

		if( $this->Item && ( $item_Blog = & $this->Item->get_Blog() ) !== NULL && $item_Blog->get( 'type' ) == 'photo' )
		{ // Only images of the photo blogs can have this position

			$FileCache = & get_FileCache();
			if( ( $File = $FileCache->get_by_ID( $file_ID, false, false ) ) && $File->is_image() )
			{ // Must be image
				$additional_positions['albumart'] = T_('Album Art');
			}
		}

		return array_merge( array(
				'teaser'    => /* TRANS: Noun - we're talking about a teaser image i-e: an image that appears before content */ T_( 'Teaser' ),
				'aftermore' => /* TRANS: Noun - we're talking about a footer image i-e: an image that appears after "more" content separator */ T_( 'After "more"' ), T_( 'After "more"' ),
				'inline'    => /* TRANS: noun - we're talking about an inline image i-e: an image that appears in the middle of some text */ T_( 'Inline' ),
			), $additional_positions );
	}

	/**
	 * Load all links of owner Item if it was not loaded yet
	 */
	function load_Links()
	{
		if( is_null( $this->Links ) )
		{ // Links have not been loaded yet:
			$LinkCache = & get_LinkCache();
			$this->Links = $LinkCache->get_by_item_ID( $this->Item->ID );
		}
	}

	/**
	 * Add new link to owner Item
	 *
	 * @param integer file ID
	 * @param integer link position ( 'teaser', 'aftermore' )
	 * @param int order of the link
	 */
	function add_link( $file_ID, $position, $order = 1 )
	{
		$edited_Link = new Link();
		$edited_Link->set( 'itm_ID', $this->Item->ID );
		$edited_Link->set( 'file_ID', $file_ID );
		$edited_Link->set( 'position', $position );
		$edited_Link->set( 'order', $order );
		if( $edited_Link->dbinsert() )
		{
			// New link was added to the item, invalidate blog's media BlockCache
			BlockCache::invalidate_key( 'media_coll_ID', $this->Item->get_blog_ID() );

			// Update last touched date of the Item
			$this->update_last_touched_date();

			// Reset the Links
			$this->Links = NULL;
			$this->load_Links();

			return true;
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
			$this->Blog = & $this->Item->get_Blog();
		}
	}

	/**
	 * Get where condition for select query to get Item links
	 */
	function get_where_condition() {
		return 'link_itm_ID = '.$this->Item->ID;
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
	 */
	function get_edit_url()
	{
		$this->load_Blog();
		return '?ctrl=items&amp;blog='.$this->Blog->ID.'&amp;action=edit&amp;p='.$this->Item->ID;
	}

	/**
	 * Get Item view url
	 */
	function get_view_url()
	{
		$this->load_Blog();
		return '?ctrl=items&amp;blog='.$this->Blog->ID.'&amp;p='.$this->Item->ID;
	}


	/**
	 * Update field last_touched_ts of Item
	 */
	function update_last_touched_date()
	{
		if( !empty( $this->Item ) )
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
			preg_match_all( '/\[image:'.$link_ID.':?[^\]]*\]/i', $this->Item->content, $inline_images );
			if( ! empty( $inline_images[0] ) )
			{ // There are inline image placeholders in the post content
				$this->Item->set( 'content', str_replace( $inline_images[0], '', $this->Item->content ) );
				$this->Item->dbupdate();
				return;
			}
		}

		// Update last touched date of the Item
		$this->update_last_touched_date();
	}
}

?>