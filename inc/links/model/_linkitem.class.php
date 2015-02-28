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
 * @version $Id: _linkitem.class.php 7752 2014-12-04 12:44:33Z yura $
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
			'Link this image to your xxx' => NT_( 'Link this image to your item.' ),
			'Link this file to your xxx' => NT_( 'Link this file to your item.'),
			'The file will be linked for download at the end of the xxx' => NT_( 'The file will be appended for linked at the end of the item.' ),
			'Insert the following code snippet into your xxx' => NT_( 'Insert the following code snippet into your item.' ),
			'View this xxx...' => NT_( 'View this item...' ),
			'Edit this xxx...' => NT_( 'Edit this item...' ),
			'Click on link %s icons below to link additional files to $xxx$.' => NT_( 'Click on link %s icons below to link additional files to <strong>item</strong>.' ),
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
				'teaser'     => T_( 'Teaser' ),
				'teaserperm' => T_( 'Teaser-Permalink' ),
				'teaserlink' => T_( 'Teaser-Ext Link' ),
				'aftermore'  => T_( 'After "more"' ),
				'inline'     => T_( 'Inline' ),
				'fallback'   => T_( 'Fallback' ),
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
	 * @param integer link position ( 'teaser', 'teaserperm', 'teaserlink', 'aftermore', 'inline', 'fallback' )
	 * @param int order of the link
	 * @param boolean true to update owner last touched timestamp after link was created, false otherwise
	 * @return integer|boolean Link ID on success, false otherwise
	 */
	function add_link( $file_ID, $position, $order = 1, $update_owner = true )
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

			$FileCache = & get_FileCache();
			$File = $FileCache->get_by_ID( $file_ID, false, false );
			$file_name = empty( $File ) ? '' : $File->get_name();
			syslog_insert( sprintf( 'File %s was linked to %s with ID=%s', '<b>'.$file_name.'</b>', $this->type, $this->link_Object->ID ), 'info', 'file', $file_ID );

			if( $update_owner )
			{ // Update last touched date of the Item
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
			preg_match_all( '/\[(image|file|inline):'.$link_ID.':?[^\]]*\]/i', $this->Item->content, $inline_images );
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