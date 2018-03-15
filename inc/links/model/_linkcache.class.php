<?php
/**
 * This file implements the LinkCache class.
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

load_class( '_core/model/dataobjects/_dataobjectcache.class.php', 'DataObjectCache' );

/**
 * LinkCache Class
 *
 * @package evocore
 */
class LinkCache extends DataObjectCache
{
	/**
	 * Cache for item -> array of object references
	 * @access private
	 * @var array
	 */
	var $cache_item = array();

	/**
	 * @access private
	 * @var array Remember full loads
	 */
	var $loaded_cache_item = array();

	/**
	 * Cache for comment -> array of object references
	 * @access private
	 * @var array
	 */
	var $cache_comment = array();

	/**
	 * @access private
	 * @var array Remember full loads
	 */
	var $loaded_cache_comment = array();

	/**
	 * Cache for user -> array of object references
	 * @access private
	 * @var array
	 */
	var $cache_user = array();

	/**
	 * @access private
	 * @var array Remember full loads
	 */
	var $loaded_cache_user = array();

	/**
	 * Cache for email campaigns -> array of object references
	 * @access private
	 * @var array
	 */
	var $cache_emailcampaign = array();

	/**
	 * @access private
	 * @var array Remember full loads
	 */
	var $loaded_cache_emailcampaign = array();

	/**
	 * Cache for messages -> array of object references
	 * @access private
	 * @var array
	 */
	var $cache_message = array();

	/**
	 * @access private
	 * @var array Remember full loads
	 */
	var $loaded_cache_message = array();

	/**
	 * Cache for temporary objects -> array of object references
	 * @access private
	 * @var array
	 */
	var $cache_temporary = array();

	/**
	 * @access private
	 * @var array Remember full loads
	 */
	var $loaded_cache_temporary = array();

	/**
	 * Cache for file -> array of object references
	 * @access private
	 * @var array
	 */
	var $cache_file = array();

	/**
	 * @access private
	 * @var array Remember full loads
	 */
	var $loaded_cache_file = array();

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct( 'Link', false, 'T_links', 'link_', 'link_ID' );
	}


	/**
	 * Add a dataobject to the cache
	 *
	 * @param object Link
	 * @param string Type of cached object ( '' - empty value to cache depend on link owner type, 'file' - to cache file )
	 * @return boolean TRUE on success
	 */
	function add( $Obj, $cache_type = '' )
	{
		if( ( !isset($Obj->ID) ) || ( $Obj->ID == 0 ) )
		{ // The object is not cachable, becuase it has no valid ID
			return false;
		}

		// If the object wasn't already cached and is valid:
		$this->cache[$Obj->ID] = $Obj;
		if( $cache_type == 'file' )
		{ // Cache by File ID
			$this->cache_file[$Obj->file_ID][$Obj->ID] = $Obj;
			return true;
		}

		// Cache by Item, Comment or User, we need to get the owner
		$LinkOwner = & $Obj->get_LinkOwner();
		if( $LinkOwner == NULL )
		{ // LinkOwner is not valid
			return false;
		}

		$link_object_ID = $LinkOwner->get_ID();
		switch( $LinkOwner->type )
		{
			case 'item': // cache indexed by Item ID
				$this->cache_item[$link_object_ID][$Obj->ID] = $Obj;
				break;

			case 'comment': // cache indexed by Comment ID
				$this->cache_comment[$link_object_ID][$Obj->ID] = $Obj;
				break;

			case 'user': // cache indexed by User ID
				$this->cache_user[$link_object_ID][$Obj->ID] = $Obj;
				break;

			case 'emailcampaign': // cache indexed by EmailCampaign ID
				$this->cache_emailcampaign[$link_object_ID][$Obj->ID] = & $Obj;
				break;

			case 'message': // cache indexed by Message ID
				$this->cache_message[$link_object_ID][$Obj->ID] = & $Obj;
				break;

			case 'temporary': // cache indexed by Temporary ID
				$this->cache_temporary[$link_object_ID][$Obj->ID] = & $Obj;
				break;

			default:
				return false;
		}
		return true;
	}


	/**
	 * Remove object from the cache
	 *
	 * @param object Link
	 * @param string Type of cached object ( '' - empty value to cache depend on link owner type, 'file' - to cache file )
	 * @return boolean TRUE on success
	 */
	function remove( & $Obj, $cache_type = '' )
	{
		if( ( !isset($Obj->ID) ) || ( $Obj->ID == 0 ) )
		{ // The object is not cachable, so it can't be in the cache
			return false;
		}

		$this->remove_by_ID( $Obj->ID );

		if( ( $cache_type == 'file' ) && ( isset( $this->cache_file[$Obj->file_ID][$Obj->ID] ) ) )
		{ // Cache by File ID
			unset( $this->cache_file[$Obj->file_ID][$Obj->ID] );
			return true;
		}

		// Cache by Item, Comment or User, we need to get the owner
		$LinkOwner = & $Obj->get_LinkOwner();
		if( $LinkOwner == NULL )
		{ // LinkOwner is not valid
			return false;
		}

		$link_object_ID = $LinkOwner->get_ID();
		switch( $LinkOwner->type )
		{
			case 'item':
				if( isset( $this->cache_item[$link_object_ID][$Obj->ID] ) )
				{
					unset( $this->cache_item[$link_object_ID][$Obj->ID] );
					return true;
				}
				break;

			case 'comment':
				if( isset( $this->cache_comment[$link_object_ID][$Obj->ID] ) )
				{
					unset( $this->cache_comment[$link_object_ID][$Obj->ID] );
					return true;
				}
				break;

			case 'user':
				if( isset( $this->cache_user[$link_object_ID][$Obj->ID] ) )
				{
					unset( $this->cache_user[$link_object_ID][$Obj->ID] );
					return true;
				}
				break;

			case 'emailcampaign':
				if( isset( $this->cache_emailcampaign[$link_object_ID][$Obj->ID] ) )
				{
					unset( $this->cache_emailcampaign[$link_object_ID][$Obj->ID] );
					return true;
				}
				break;

			case 'message':
				if( isset( $this->cache_message[$link_object_ID][$Obj->ID] ) )
				{
					unset( $this->cache_message[$link_object_ID][$Obj->ID] );
					return true;
				}
				break;

			case 'temporary':
				if( isset( $this->cache_temporary[$link_object_ID][$Obj->ID] ) )
				{
					unset( $this->cache_temporary[$link_object_ID][$Obj->ID] );
					return true;
				}
				break;

			case 'file':
			default: // Invalid cache type
				break;
		}

		// Object was not in the cache
		return false;
	}


	/**
	 * Returns links for a given Item
	 *
	 * Loads if necessary
	 *
	 * @todo dh> does not get used anywhere (yet)?
	 *
	 * @param integer item ID to load links for
	 * @return array of refs to Link objects
	 */
	function & get_by_item_ID( $item_ID )
	{
		// Make sure links are loaded:
		$this->load_by_item_ID( $item_ID );

		return $this->cache_item[$item_ID];
	}


	/**
	 * Returns links for a given Comment
	 *
	 * Loads if necessary
	 *
	 * @param integer comment ID to load links for
	 * @return array of refs to Link objects
	 */
	function & get_by_comment_ID( $comment_ID )
	{
		// Make sure links are loaded:
		$this->load_by_comment_ID( $comment_ID );

		return $this->cache_comment[$comment_ID];
	}


	/**
	 * Returns links for a given User
	 *
	 * Loads if necessary
	 *
	 * @param integer user ID to load links for
	 * @return array of refs to Link objects
	 */
	function & get_by_user_ID( $user_ID )
	{
		// Make sure links are loaded:
		$this->load_by_user_ID( $user_ID );

		return $this->cache_user[$user_ID];
	}


	/**
	 * Returns links for a given Email Campaign
	 *
	 * Loads if necessary
	 *
	 * @param integer Email campaign ID to load links for
	 * @return array of refs to Link objects
	 */
	function & get_by_emailcampaign_ID( $emailcampaign_ID )
	{
		// Make sure links are loaded:
		$this->load_by_emailcampaign_ID( $emailcampaign_ID );

		return $this->cache_emailcampaign[ $emailcampaign_ID ];
	}


	/**
	 * Returns links for a given Message
	 *
	 * Loads if necessary
	 *
	 * @param integer Message ID to load links for
	 * @return array of refs to Link objects
	 */
	function & get_by_message_ID( $message_ID )
	{
		// Make sure links are loaded:
		$this->load_by_message_ID( $message_ID );

		return $this->cache_message[ $message_ID ];
	}


	/**
	 * Returns links for a given temporary object
	 *
	 * Loads if necessary
	 *
	 * @param integer Temporary ID to load links for
	 * @return array of refs to Link objects
	 */
	function & get_by_temporary_ID( $temporary_ID )
	{
		// Make sure links are loaded:
		$this->load_by_temporary_ID( $temporary_ID );

		return $this->cache_temporary[ $temporary_ID ];
	}


	/**
	 * Returns links for a given File
	 *
	 * Loads if necessary
	 *
	 * @param integer file ID to load links for
	 * @return array of refs to Link objects
	 */
	function & get_by_file_ID( $file_ID )
	{
		// Make sure links are loaded:
		$this->load_by_file_ID( $file_ID );

		return $this->cache_file[$file_ID];
	}


	/**
	 * Load a set of Links by the given link type into the cache.
	 *
	 * @param SQL SQL object
	 * @param string Cache type
	 */
	function load_type_by_sql( $SQL, $cache_type )
	{
		global $DB;

		$links = $DB->get_results( $SQL );

		// Load linked files into the FileCache
		$this->load_linked_files( $links );

		foreach( $links as $row )
		{ // Cache each matching object:
			$this->add( new Link( $row ), $cache_type );
		}
	}


	/**
	 * Load links for a given Item
	 *
	 * Optimization: If the Item happens to be in the current MainList, Links for the whole MainList will be cached.
	 *
	 * @todo cache Link targets before letting the Link constructor handle it
	 *
	 * @param integer item ID to load links for
	 */
	function load_by_item_ID( $item_ID )
	{
		global $DB, $Debuglog, $MainList, $ItemList;

		if( isset( $this->loaded_cache_item[$item_ID] ) )
		{
			$Debuglog->add( "Already loaded <strong>$this->objtype(Item #$item_ID)</strong> into cache", 'dataobjects' );
			return false;
		}
		// Check if this Item is part of the MainList
		if( $MainList || $ItemList )
		{
			$prefetch_IDs = array();
			if( $MainList )
			{
				$prefetch_IDs = array_merge($prefetch_IDs, $MainList->get_page_ID_array());
			}
			if( $ItemList )
			{
				$prefetch_IDs = array_merge($prefetch_IDs, $ItemList->get_page_ID_array());
			}
			$Debuglog->add( "Loading <strong>$this->objtype(Item #$item_ID)</strong> into cache as part of MainList/ItemList...");
			$this->load_by_item_list( $prefetch_IDs );
		}
		else
		{	// NO, load Links for this single Item:

			// Remember this special load:
			$this->cache_item[$item_ID] = array();
			$this->loaded_cache_item[$item_ID] = true;

			$Debuglog->add( "Loading <strong>$this->objtype(Item #$item_ID)</strong> into cache", 'dataobjects' );

			$SQL = new SQL( 'Get the links by item ID #'.$item_ID );
			$SQL->SELECT( '*' );
			$SQL->FROM( 'T_links' );
			$SQL->WHERE( 'link_itm_ID  = '.$DB->quote( $item_ID ) );
			$SQL->ORDER_BY( 'link_ltype_ID, link_file_ID' );
			$SQL->append( 'FOR UPDATE' ); // fp: we specify FOR UPDATE because we need to lock all changes to the link_order column. 
			// fp: Note: FOR UPDATE won't do anything if we're not in a transaction (which is good)

			$this->load_type_by_sql( $SQL, 'item' );
		}

		return true;
	}


	/**
	 * Load links for a given Comment
	 *
	 * @param integer comment ID to load links for
	 */
	function load_by_comment_ID( $comment_ID )
	{
		global $DB, $Debuglog;

		if( isset( $this->loaded_cache_comment[$comment_ID] ) )
		{
			$Debuglog->add( "Already loaded <strong>$this->objtype(Comment #$comment_ID)</strong> into cache", 'dataobjects' );
			return false;
		}

		// Remember this special load:
		$this->cache_comment[$comment_ID] = array();
		$this->loaded_cache_comment[$comment_ID] = true;

		$Debuglog->add( "Loading <strong>$this->objtype(Comment #$comment_ID)</strong> into cache", 'dataobjects' );

		$SQL = new SQL( 'Get the links by comment ID #'.$comment_ID );
		$SQL->SELECT( '*' );
		$SQL->FROM( 'T_links' );
		$SQL->WHERE( 'link_cmt_ID  = '.$DB->quote( $comment_ID ) );
		$SQL->ORDER_BY( 'link_file_ID' );

		$this->load_type_by_sql( $SQL, 'comment' );

		return true;
	}


	/**
	 * Load links for a given User
	 *
	 * @param integer user ID to load links for
	 */
	function load_by_user_ID( $user_ID )
	{
		global $DB, $Debuglog;

		if( isset( $this->loaded_cache_user[$user_ID] ) )
		{
			$Debuglog->add( "Already loaded <strong>$this->objtype(User #$user_ID)</strong> into cache", 'dataobjects' );
			return false;
		}

		// Remember this special load:
		$this->cache_user[$user_ID] = array();
		$this->loaded_cache_user[$user_ID] = true;

		$Debuglog->add( "Loading <strong>$this->objtype(User #$user_ID)</strong> into cache", 'dataobjects' );

		$SQL = new SQL( 'Get the links by user ID #'.$user_ID );
		$SQL->SELECT( '*' );
		$SQL->FROM( 'T_links' );
		$SQL->WHERE( 'link_usr_ID  = '.$DB->quote( $user_ID ) );
		$SQL->ORDER_BY( 'link_file_ID' );

		$this->load_type_by_sql( $SQL, 'user' );

		return true;
	}


	/**
	 * Load links for a given Email Campaign
	 *
	 * @param integer Email campaign ID to load links for
	 */
	function load_by_emailcampaign_ID( $emailcampaign_ID )
	{
		global $DB, $Debuglog;

		if( isset( $this->loaded_cache_emailcampaign[ $emailcampaign_ID ] ) )
		{
			$Debuglog->add( "Already loaded <strong>$this->objtype(EmailCampaign #$emailcampaign_ID)</strong> into cache", 'dataobjects' );
			return false;
		}

		// Remember this special load:
		$this->cache_emailcampaign[ $emailcampaign_ID ] = array();
		$this->loaded_cache_emailcampaign[ $emailcampaign_ID ] = true;

		$Debuglog->add( "Loading <strong>$this->objtype(EmailCampaign #$emailcampaign_ID)</strong> into cache", 'dataobjects' );

		$SQL = new SQL( 'Get the links by email campaign ID #'.$emailcampaign_ID );
		$SQL->SELECT( '*' );
		$SQL->FROM( 'T_links' );
		$SQL->WHERE( 'link_ecmp_ID  = '.$DB->quote( $emailcampaign_ID ) );
		$SQL->ORDER_BY( 'link_file_ID' );

		$this->load_type_by_sql( $SQL, 'emailcampaign' );

		return true;
	}


	/**
	 * Load links for a given Email Campaign
	 *
	 * @param integer Message ID to load links for
	 */
	function load_by_message_ID( $message_ID )
	{
		global $DB, $Debuglog;

		if( isset( $this->loaded_cache_message[ $message_ID ] ) )
		{
			$Debuglog->add( "Already loaded <strong>$this->objtype(Message #$message_ID)</strong> into cache", 'dataobjects' );
			return false;
		}

		// Remember this special load:
		$this->cache_message[ $message_ID ] = array();
		$this->loaded_cache_message[ $message_ID ] = true;

		$Debuglog->add( "Loading <strong>$this->objtype(Message #$message_ID)</strong> into cache", 'dataobjects' );

		$SQL = new SQL( 'Get the links by message ID #'.$message_ID );
		$SQL->SELECT( '*' );
		$SQL->FROM( 'T_links' );
		$SQL->WHERE( 'link_msg_ID  = '.$DB->quote( $message_ID ) );
		$SQL->ORDER_BY( 'link_file_ID' );

		$this->load_type_by_sql( $SQL, 'message' );

		return true;
	}


	/**
	 * Load links for a given Temporary ID object
	 *
	 * @param integer Temporary ID to load links for
	 */
	function load_by_temporary_ID( $temporary_ID )
	{
		global $DB, $Debuglog;

		if( isset( $this->loaded_cache_temporary[ $temporary_ID ] ) )
		{
			$Debuglog->add( "Already loaded <strong>$this->objtype(Temporary #$temporary_ID)</strong> into cache", 'dataobjects' );
			return false;
		}

		// Remember this special load:
		$this->cache_temporary[ $temporary_ID ] = array();
		$this->loaded_cache_temporary[ $temporary_ID ] = true;

		$Debuglog->add( "Loading <strong>$this->objtype(Temporary #$temporary_ID)</strong> into cache", 'dataobjects' );

		$SQL = new SQL( 'Get the links by temporary ID #'.$temporary_ID );
		$SQL->SELECT( '*' );
		$SQL->FROM( 'T_links' );
		$SQL->WHERE( 'link_tmp_ID  = '.$DB->quote( $temporary_ID ) );
		$SQL->ORDER_BY( 'link_file_ID' );

		$this->load_type_by_sql( $SQL, 'temporary' );

		return true;
	}


	/**
	 * Load links for a given Item list
	 *
	 * @todo cache Link targets before letting the Link constructor handle it
	 *
	 * @param array of of item IDs to load links for
	 */
	function load_by_item_list( $itemIDarray )
	{
		global $DB, $Debuglog;

		if( empty( $itemIDarray ) )
		{	// Nothing to load:
			return false;
		}

		$item_list = implode( ',', $itemIDarray );

		$Debuglog->add( "Loading <strong>$this->objtype(Items #$item_list)</strong> into cache", 'dataobjects' );

		// For each item in list...
		foreach( $itemIDarray as $item_ID )
		{ // Remember this special load:
			$this->cache_item[$item_ID] = array();
			$this->loaded_cache_item[$item_ID] = true;
		}

		$SQL = new SQL( 'Get the links by item IDs: '.$item_list );
		$SQL->SELECT( '*' );
		$SQL->FROM( 'T_links' );
		$SQL->WHERE( 'link_itm_ID IN ('.$item_list.')' );
		$SQL->ORDER_BY( 'link_ID' );

		$this->load_type_by_sql( $SQL, 'item' );

		return true;
	}


	/**
	 * Load links for a given Comment list
	 *
	 * @todo cache Link targets before letting the Link constructor handle it
	 *
	 * @param array Comment IDs to load links for
	 */
	function load_by_comment_list( $comment_IDs )
	{
		global $DB, $Debuglog;

		if( empty( $comment_IDs ) )
		{	// Nothing to load:
			return;
		}

		$comment_list = implode( ',', $comment_IDs );

		$Debuglog->add( "Loading <strong>$this->objtype(Comments #$comment_list)</strong> into cache", 'dataobjects' );

		foreach( $comment_IDs as $comment_ID )
		{ // Remember this special load for each comment in list:
			$this->cache_comment[ $comment_ID ] = array();
			$this->loaded_cache_comment[ $comment_ID ] = true;
		}

		$SQL = new SQL( 'Get the links by comment IDs: '.$comment_list );
		$SQL->SELECT( '*' );
		$SQL->FROM( 'T_links' );
		$SQL->WHERE( 'link_cmt_ID IN ( '.$comment_list.' )' );
		$SQL->ORDER_BY( 'link_ID' );

		$this->load_type_by_sql( $SQL, 'comment' );
	}


	/**
	 * Load links for a given File
	 *
	 * @param integer file ID to load links for
	 */
	function load_by_file_ID( $file_ID )
	{
		global $DB, $Debuglog;

		if( isset( $this->loaded_cache_file[$file_ID] ) )
		{
			$Debuglog->add( "Already loaded <strong>$this->objtype(File #$file_ID)</strong> into cache", 'dataobjects' );
			return false;
		}

		// Remember this special load:
		$this->cache_file[$file_ID] = array();
		$this->loaded_cache_file[$file_ID] = true;

		$Debuglog->add( "Loading <strong>$this->objtype(File #$file_ID)</strong> into cache", 'dataobjects' );

		$SQL = new SQL( 'Get the links by file ID #'.$file_ID );
		$SQL->SELECT( '*' );
		$SQL->FROM( 'T_links' );
		$SQL->WHERE( 'link_file_ID = '.$DB->quote( $file_ID ) );
		$SQL->ORDER_BY( 'link_ID' );
		$links = $DB->get_results( $SQL );
		foreach( $links as $row )
		{ // Cache each matching object:
			$this->add( new Link( $row ), 'file' );
		}

		return true;
	}


	/**
	 * Load required Files into the FileCache before Link class constructor will be called.
	 * It's imporatnt to load all Files with one query instead of loading the one by one
	 * 
	 * private function
	 * 
	 * @param array link rows
	 */
	function load_linked_files( & $link_rows )
	{
		if( empty( $link_rows ) )
		{ // There are nothing to load
			return;
		}

		// Collect required file Ids
		$link_file_ids = array();
		foreach( $link_rows as $row )
		{
			$link_file_ids[] = $row->link_file_ID;
		}

		if( !empty( $link_file_ids ) )
		{ // Load required Files into FileCache
			$FileCache = & get_FileCache();
			$FileCache->load_where( 'file_ID IN ( '.implode( ',', $link_file_ids ).' )' );
		}
	}


	/**
	 * Clear the cache **extensively**
	 *
	 * @param boolean Keep copy of cache in case we try to re instantiate previous object
	 * @param string What to clear: 'all', 'user', 'item', 'comment', 'emailcampaign', 'message', 'file'
	 * @param integer ID of the clearing object
	 */
	function clear( $keep_shadow = false, $object = 'all', $object_ID = 0 )
	{
		parent::clear( $keep_shadow );

		switch( $object )
		{
			case 'all':
				// Clear all cached objects
				$this->cache_item = array();
				$this->loaded_cache_item = array();
				$this->cache_comment = array();
				$this->loaded_cache_comment = array();
				$this->cache_user = array();
				$this->loaded_cache_user = array();
				$this->loaded_cache_emailcampaign = array();
				$this->loaded_cache_message = array();
				$this->cache_file = array();
				$this->loaded_cache_file = array();
				break;

			case 'item':
			case 'comment':
			case 'user':
			case 'emailcampaign':
			case 'message':
			case 'temporary':
			case 'file':
				// Clear only the selected type of objects
				if( empty( $object_ID ) )
				{ // Clear all cached objects of this type
					$this->{'cache_'.$object} = array();
					$this->{'loaded_cache_'.$object} = array();
				}
				else
				{ // Clear a cache only one object
					unset( $this->{'cache_'.$object}[ $object_ID ] );
					unset( $this->{'loaded_cache_'.$object}[ $object_ID ] );
				}
				break;
		}
	}
}

?>