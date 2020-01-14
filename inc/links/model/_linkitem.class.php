<?php
/**
 * This file implements the LinkItem class, which is a wrapper class for Item class to handle linked files.
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
			'Cannot delete Link from $xxx$.' => NT_( 'Cannot delete Link from &laquo;item&raquo;.' ),
		);
	}

	/**
	 * Check current User has an access to work with attachments of the link Item
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
				debug_die( 'You have no permission for item attachments!' );
			}
			return false;
		}

		if( $permlevel == 'add' )
		{	// Check permission to add/upload new files:
			return $current_User->check_perm( 'files', $permlevel, $assert, $FileRoot );
		}

		if( $this->is_temp() )
		{	// Check permission for new creating item:
			return $current_User->check_perm( 'blog_post_statuses', $permlevel, $assert, $this->get_blog_ID() );
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
		elseif( $File->is_video() || $File->is_audio() )
		{	// If file is video or audio then always use "aftermore":
			return 'aftermore';
		}
		else
		{	// All other file types must use "attachment" position by default:
			return 'attachment';
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
				$this->Links = $LinkCache->get_by_item_ID( $this->get_ID() );
			}
		}
	}

	/**
	 * Add new link to owner Item
	 *
	 * @param integer file ID
	 * @param integer link position ( 'teaser', 'teaserperm', 'teaserlink', 'aftermore', 'inline', 'fallback' )
	 * @param integer Order of the link, Use 0 to set autoincremented order
	 * @param boolean true to update owner last touched timestamp after link was created, false otherwise
	 * @return integer|boolean Link ID on success, false otherwise
	 */
	function add_link( $file_ID, $position = NULL, $order = 1, $update_owner = true )
	{
		global $DB, $localtimenow;

		if( ! $this->Item->check_proposed_change_restriction( 'error' ) )
		{	// If the Link's Item cannot be updated because of proposed change:
			return false;
		}

		if( is_null( $position ) )
		{ // Use default link position
			$position = $this->get_default_position( $file_ID );
		}

		$edited_Link = new Link();
		$edited_Link->set( $this->get_ID_field_name(), $this->get_ID() );
		$edited_Link->set( 'file_ID', $file_ID );
		$edited_Link->set( 'position', $position );
		if( $order > 0 && $order <= $this->get_last_order() )
		{	// Don't allow order which may be already used:
			$order = $this->get_last_order() + 1;
			// Update last order for next adding:
			$this->last_order = $order;
		}
		$edited_Link->set( 'order', $order );

		if( ( $localtimenow - strtotime( $this->Item->last_touched_ts ) ) > 90 )
		{
			$this->Item->create_revision();
		}

		if( $edited_Link->dbinsert() )
		{
			if( ! $this->is_temp() )
			{	// New link was added to the item, invalidate blog's media BlockCache:
				BlockCache::invalidate_key( 'media_coll_ID', $this->Item->get_blog_ID() );
			}

			$FileCache = & get_FileCache();
			$File = $FileCache->get_by_ID( $file_ID, false, false );
			$file_name = empty( $File ) ? '' : $File->get_name();
			$file_dir = $File->dir_or_file( 'Directory', 'File' );
			syslog_insert( sprintf( '%s %s was linked to %s with ID=%s', $file_dir, '[['.$file_name.']]', $this->type, $this->get_ID() ), 'info', 'file', $file_ID );

			if( ! $this->is_temp() && $update_owner )
			{	// Update last touched date and content last updated date of the Item:
				$this->update_last_touched_date();
				$this->update_contents_last_updated_ts();
			}

			// Reset the Links
			$this->Links = NULL;
			$this->load_Links();

			return $edited_Link->ID;
		}

		return false;
	}


	/**
	 * Remove link from the owner
	 *
	 * @param object Link
	 * @param boolean TRUE to force a removing
	 * @return boolean true on success
	 */
	function remove_link( & $Link, $force = false )
	{
		global $DB, $localtimenow;

		if( ! $force && ! $this->Item->check_proposed_change_restriction( 'error' ) )
		{	// If the Link's Item cannot be updated because of proposed change:
			return false;
		}

		$this->load_Links();

		$previous_Revision = $this->Item->get_revision( 'last_archived' );

		if( ! empty( $previous_Revision ) &&  ( $localtimenow - strtotime( $this->Item->last_touched_ts ) ) < 90 )
		{ // Check if we can remove the link from the previous revision
			$last_revision_ID = ( int ) $previous_Revision->iver_ID;
			if( $last_revision_ID > 1 )
			{ // Check if the file attachment exists in the previous revision
				$sql = new SQL();
				$sql->SELECT( '*' );
				$sql->FROM( 'T_items__version_link' );
				$sql->WHERE( 'ivl_iver_ID = '.$previous_Revision->iver_ID );
				$sql->WHERE_and( 'ivl_iver_itm_ID = '.$previous_Revision->iver_itm_ID );
				$sql->WHERE_and( 'ivl_link_ID = '.$Link->ID );
				$revision_links = $DB->get_results( $sql->get() );

				if( empty( $revision_links ) )
				{ // Link is not in previous history, we need to create a new revision
					$this->Item->create_revision();
				}
			}
			else
			{ // Link has no attachment history so we'll have to create a new one
				$this->Item->create_revision();
			}
		}
		else
		{
			$this->Item->create_revision();
		}

		$index = array_search( $Link, $this->Links );
		if( $index !== false )
		{
			unset( $this->Links[ $index ] );
		}
		$LinkCache = & get_LinkCache();
		$LinkCache->remove( $Link );

		if( $Link->dbdelete() )
		{
			if( ! $this->is_temp() )
			{	// Update last touched date and content last updated date of the Item:
				$this->update_last_touched_date();
				$this->update_contents_last_updated_ts();
			}

			return true;
		}

		return false;
	}


	/**
	 * Load collection of the onwer Item
	 */
	function load_Blog()
	{
		if( $this->Blog === NULL )
		{	// Try to get Item from DB and store in cache to next requests:
			if( $this->is_temp() )
			{	// If new Comment is creating
				$BlogCache = & get_BlogCache();
				$this->Blog = & $BlogCache->get_by_ID( $this->link_Object->tmp_coll_ID, false, false );
			}
			else
			{	// If existing Item is editing
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
	 * @param string Delimiter to use for multiple params (typically '&amp;' or '&')
	 * @param string URL type: 'frontoffice', 'backoffice'
	 * @return string URL
	 */
	function get_edit_url( $glue = '&amp;', $url_type = NULL )
	{
		if( $url_type == 'backoffice' || ( $url_type === NULL  && is_admin_page() ) )
		{	// Back-office:
			global $admin_url;
			if( $this->is_temp() )
			{	// New creating Item:
				return $admin_url.'?ctrl=items'.$glue.'blog='.$this->get_blog_ID().$glue.'action=new';
			}
			else
			{	// The edited Item:
				return $admin_url.'?ctrl=items'.$glue.'blog='.$this->get_blog_ID().$glue.'action=edit'.$glue.'p='.$this->get_ID();
			}
		}
		else
		{	// Front-office:
			$item_Blog = & $this->get_Blog();
			if( $this->is_temp() )
			{	// New creating Item:
				return url_add_param( $item_Blog->get( 'url', array( 'glue' => $glue ) ), 'disp=edit', $glue );
			}
			else
			{	// The editing Item:
				return url_add_param( $item_Blog->get( 'url', array( 'glue' => $glue ) ), 'disp=edit'.$glue.'p='.$this->get_ID(), $glue );
			}
		}
	}


	/**
	 * Get Item view url
	 *
	 * @param string Delimiter to use for multiple params (typically '&amp;' or '&')
	 * @param string URL type: 'frontoffice', 'backoffice'
	 * @return string URL
	 */
	function get_view_url( $glue = '&amp;', $url_type = NULL )
	{
		if( $url_type == 'backoffice' || ( $url_type === NULL  && is_admin_page() ) )
		{	// Back-office:
			global $admin_url;
			if( $this->is_temp() )
			{	// New creating Item:
				return $admin_url.'?ctrl=items'.$glue.'blog='.$this->get_blog_ID().$glue.'action=new';
			}
			else
			{	// The editing Item:
				return $admin_url.'?ctrl=items'.$glue.'blog='.$this->get_blog_ID().$glue.'p='.$this->get_ID();
			}
		}
		else
		{	// Front-office:
			if( $this->is_temp() )
			{	// New creating Item:
				$item_Blog = & $this->get_Blog();
				return url_add_param( $item_Blog->get( 'url', array( 'glue' => $glue ) ), 'disp=edit', $glue );
			}
			else
			{	// The editing Item:
				return $this->Item->get_permanent_url( '', '', $glue );
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
			$this->Item->update_last_touched_date( true, true, false, true );
		}
	}


	/**
	 * Update field contents_last_updated_ts of Item
	 */
	function update_contents_last_updated_ts()
	{
		if( ! empty( $this->Item ) && ! $this->is_temp() )
		{	// Update Item if it exists:
			$this->Item->update_last_touched_date( true, false, true );
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
		{	// Update last touched date and content last updated date of the Item:
			$this->update_last_touched_date();
			$this->update_contents_last_updated_ts();
		}
	}


	/**
	 * Get list of attached Links
	 *
	 * @param integer Limit max result
	 * @param string Restrict to files/images linked to a specific position.
	 *               Position can be 'teaser'|'aftermore'|'inline'
	 *               Use comma as separator
	 * @param string File type: 'image', 'audio', 'other'; NULL - to select all
	 * @param array Params
	 * @return DataObjectList2 on success or NULL if no linked files found
	 */
	function get_attachment_LinkList( $limit = 1000, $position = NULL, $file_type = NULL, $params = array() )
	{
		if( $this->Item->is_revision() )
		{	// Get Links of current active revision:
			if( ! isset( $GLOBALS['files_Module'] ) )
			{
				return NULL;
			}

			$params = array_merge( array(
					'sql_select_add' => '', // Additional fields for SELECT clause
					'sql_order_by'   => 'link_order', // ORDER BY clause
				), $params );

			foreach( $params as $param_key => $param_value )
			{
				if( strpos( $param_key, 'sql_' ) !== false )
				{	// Replace column names to revision table name in external SQL:
					$params[ $param_key ] = str_replace(
						array( 'link_ID', 'link_file_ID', 'link_position', 'link_prder', 'link_itm_ID' ),
						array( 'ivl_link_ID', 'ivl_file_ID', 'ivl_position', 'ivl_order', 'ivl_iver_itm_ID' ),
						$param_value );
				}
			}

			$Revision = $this->Item->get_revision();

			global $DB;

			load_class( '_core/model/dataobjects/_dataobjectlist2.class.php', 'DataObjectList2' );

			$LinkCache = & get_LinkCache();

			$LinkList = new DataObjectList2( $LinkCache ); // IN FUNC

			$SQL = new SQL();
			$SQL->SELECT( 'ivl_link_ID AS link_ID, ivl_iver_itm_ID AS link_itm_ID, ivl_file_ID AS link_file_ID, ivl_position AS link_position, ivl_order AS link_order' );
			$SQL->SELECT_add( ', NULL AS link_datecreated, NULL AS link_datemodified, NULL AS link_creator_user_ID, NULL AS link_lastedit_user_ID, NULL AS link_cmt_ID, NULL AS link_usr_ID, NULL AS link_ecmp_ID, NULL AS link_msg_ID, NULL AS link_tmp_ID' );
			$SQL->SELECT_add( $params['sql_select_add'] );
			$SQL->FROM( 'T_items__version_link' );
			$SQL->WHERE( 'ivl_iver_itm_ID = '.$this->get_ID() );
			$SQL->WHERE_and( 'ivl_iver_ID = '.$Revision->iver_ID );
			$SQL->WHERE_and( 'ivl_iver_type = '.$DB->quote( $Revision->iver_type ) );
			if( ! empty( $position ) )
			{
				$position = explode( ',', $position );
				$SQL->WHERE_and( 'ivl_position IN ( '.$DB->quote( $position ).' )' );
			}
			$SQL->ORDER_BY( $params['sql_order_by'] );
			$SQL->LIMIT( $limit );

			if( ! is_null( $file_type ) )
			{	// Restrict the Links by File type:
				$SQL->FROM_add( 'LEFT JOIN T_files ON ivl_file_ID = file_ID' );
				$SQL->WHERE_and( 'file_type = '.$DB->quote( $file_type ).' OR file_type IS NULL' );
			}

			$LinkList->sql = $SQL->get();

			$LinkList->run_query( false, false, false, 'get_attachment_LinkList' );

			if( $LinkList->result_num_rows == 0 )
			{	// Nothing found
				$LinkList = NULL;
			}

			return $LinkList;
		}
		else
		{	// Get Links of current Item:
			return parent::get_attachment_LinkList( $limit, $position, $file_type, $params );
		}
	}
}

?>