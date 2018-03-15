<?php
/**
 * This file implements the Link class, which manages extra links on items.
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

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * Item Link
 *
 * @package evocore
 */
class Link extends DataObject
{
	var $ltype_ID = 0;
	var $file_ID = 0;
	var $position;
	var $order;
	/**
	 * @var Link owner object
	 */
	var $LinkOwner;
	/**
	 * @access protected
	 * @see get_File()
	 */
	var $File;


	/**
	 * Constructor
	 *
	 * @param table Database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_links', 'link_', 'link_ID',
													'datecreated', 'datemodified', 'creator_user_ID', 'lastedit_user_ID' );

		if( $db_row != NULL )
		{
			$this->ID       = $db_row->link_ID;
			$this->ltype_ID = $db_row->link_ltype_ID;

			// source of link:
			if( $db_row->link_itm_ID != NULL )
			{	// Item:
				$this->LinkOwner = & get_link_owner( 'item', $db_row->link_itm_ID );
			}
			elseif( $db_row->link_cmt_ID != NULL )
			{	// Comment:
				$this->LinkOwner = & get_link_owner( 'comment', $db_row->link_cmt_ID );
			}
			elseif( $db_row->link_usr_ID != NULL )
			{	// User:
				$this->LinkOwner = & get_link_owner( 'user', $db_row->link_usr_ID );
			}
			elseif( $db_row->link_ecmp_ID != NULL )
			{	// Email Campaign:
				$this->LinkOwner = & get_link_owner( 'emailcampaign', $db_row->link_ecmp_ID );
			}
			elseif( $db_row->link_msg_ID != NULL )
			{	// Message:
				$this->LinkOwner = & get_link_owner( 'message', $db_row->link_msg_ID );
			}
			elseif( $db_row->link_tmp_ID != NULL )
			{	// Temporary ID:
				$this->LinkOwner = & get_link_owner( 'temporary', $db_row->link_tmp_ID );
			}
			else
			{
				debug_die( 'Wrong link object' );
			}

			$this->file_ID = $db_row->link_file_ID;

			// TODO: dh> deprecated, check where it's used, and fix it.
			$this->File = & $this->get_File();

			$this->position = $db_row->link_position;
			$this->order = $db_row->link_order;
		}
		else
		{	// New object:

		}
	}


	/**
	 * Get this class db table config params
	 *
	 * @return array
	 */
	static function get_class_db_config()
	{
		static $link_db_config;

		if( !isset( $link_db_config ) )
		{
			$link_db_config = array_merge( parent::get_class_db_config(),
				array(
					'dbtablename'        => 'T_links',
					'dbprefix'           => 'link_',
					'dbIDname'           => 'link_ID',
				)
			);
		}

		return $link_db_config;
	}


	/**
	 * Get delete cascade settings
	 *
	 * @return array
	 */
	static function get_delete_cascades()
	{
		return array(
				array( 'table'=>'T_links__vote', 'fk'=>'lvot_link_ID', 'msg'=>T_('%d votes') ),
			);
	}


	/**
	 * Get (@link LinkOwner) of the link
	 *
	 * @return LinkOwner
	 */
	function & get_LinkOwner()
	{
		return $this->LinkOwner;
	}


	/**
	 * Get {@link File} of the link.
	 *
	 * @return File
	 */
	function & get_File()
	{
		if( ! isset($this->File) )
		{
			if( isset($GLOBALS['files_Module']) )
			{
				$FileCache = & get_FileCache();
				// fp> do not halt on error. For some reason (ahem bug) a file can disappear and if we fail here then we won't be
				// able to delete the link
				$this->File = & $FileCache->get_by_ID( $this->file_ID, false, false );
			}
			else
			{
				$this->File = NULL;
			}
		}
		return $this->File;
	}


	/**
	 * Return type of target for this Link:
	 *
	 * @todo incomplete
	 */
	function target_type()
	{
 		if( !is_null($this->File) )
		{
			return 'file';
		}

		return 'unkown';
	}


	/**
	 * Get a complete tag (IMG or A HREF) pointing to the file of this link.
	 *
	 * @param array Params
	 * @return string the file tag if the file exists, empty string otherwise
	 */
	function get_tag( $params = array() )
	{
		$File = & $this->get_File();
		if( !$File )
		{ // No file
			return '';
		}

		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before_image'        => '<div class="image_block">',
				'before_image_legend' => '<div class="image_legend">', // can be NULL
				'after_image_legend'  => '</div>',
				'after_image'         => '</div>',
				'image_size'          => 'original',
				'image_link_to'       => 'original',
				'image_link_title'    => '',	// can be text or #title# or #desc#
				'image_link_rel'      => '',
				'image_class'         => '',
				'image_align'         => '',
				'image_alt'           => '',
				'image_desc'          => '#',
				'image_size_x'        => 1, // Use '2' to build 2x sized thumbnail that can be used for Retina display
				'tag_size'            => NULL,
				'image_style'         => '',
				'add_loadimg'         => true,
			), $params );

		return $File->get_tag( $params['before_image'],
				$params['before_image_legend'],
				$params['after_image_legend'],
				$params['after_image'],
				$params['image_size'],
				$params['image_link_to'],
				$params['image_link_title'],
				$params['image_link_rel'],
				$params['image_class'],
				$params['image_align'],
				$params['image_alt'],
				$params['image_desc'],
				'link_'.$this->ID,
				$params['image_size_x'],
				$params['tag_size'],
				$params['image_style'],
				$params['add_loadimg'] );
	}


	/**
	 * Get the link file preview thumbnail.
	 *
	 * @return string HTML to display
	 */
	function get_preview_thumb()
	{
		$File = & $this->get_File();
		if( !$File )
		{ // No file
			return '';
		}

		return $File->get_preview_thumb( 'fulltype', array(
				'init' => true,
				'lightbox_rel' => 'lightbox[o'.$this->LinkOwner->get_ID().']',  // Mark images from the same onwer
				'link_id' => 'link_'.$this->ID  // Use 'link_' prefix to enable voting ( Note: Voting is enable only for links )
			)
		);
	}


	/**
	 * Get an url to download file
	 *
	 * @param array Params
	 * @return string|boolean URL or FALSE when Link object is broken
	 */
	function get_download_url( $params = array() )
	{
		$params = array_merge( array(
				'glue' => '&amp;', // Glue between url params
				'type' => 'page', // 'page' - url of the download page, 'action' - url to force download
			), $params );

		if( ! ( $File = & $this->get_File() ) ||
		    ! ( $LinkOwner = & $this->get_LinkOwner() ) )
		{ // Broken Link
			return false;
		}

		if( $LinkOwner->type == 'item' && $LinkOwner->Item )
		{ // Use specific url for Item to download
			switch( $params['type'] )
			{
				case 'action':
					// Get URL to froce download a file
					if( $File->get_ext() == 'zip' )
					{ // Provide direct url to ZIP files
					  // NOTE: The same hardcoded place is in the file "htsrv/download.php", lines 56-60
						return $File->get_url();
					}
					else
					{ // For other files use url through special file that forces a download action
						return get_htsrv_url().'download.php?link_ID='.$this->ID;
					}

				case 'page':
				default:
					// Get URL to display a page with info about file and item
					return url_add_param( $LinkOwner->Item->get_permanent_url( '', '', $params['glue'] ), 'download='.$this->ID, $params['glue'] );
			}
		}
		else
		{ // Use standard url for all other types
			return $File->get_view_url( false );
		}
	}


	/**
	 * Check if file of this link can be deleted by current user
	 *
	 * @return boolean
	 */
	function can_be_file_deleted()
	{
		global $current_User, $DB;

		if( ! is_logged_in() )
		{	// Not logged in user
			return false;
		}

		$LinkOwner = & $this->get_LinkOwner();
		if( empty( $LinkOwner ) || ! $LinkOwner->check_perm( 'edit' ) )
		{	// User has no permission to edit current link owner
			return false;
		}

		if( ! ( $File = & $this->get_File() ) ||
		    ! ( $FileRoot = & $File->get_FileRoot() ) ||
		    ! $current_User->check_perm( 'files', 'edit_allowed', false, $FileRoot ) )
		{	// Current user has no permission to edit this file
			return false;
		}

		// Try to find at least one another link by same file ID:
		$SQL = new SQL( 'Try to find at least one another link by same file ID #'.$File->ID );
		$SQL->SELECT( 'link_ID' );
		$SQL->FROM( 'T_links' );
		$SQL->WHERE( 'link_file_ID = '.$DB->quote( $File->ID ) );
		$SQL->WHERE_and( 'link_ID != '.$DB->quote( $this->ID ) );
		$SQL->LIMIT( '1' );
		if( $DB->get_var( $SQL ) )
		{	// We cannot delete the file of this link because it is also linked to another object
			return false;
		}

		// No any restriction, Current User can delete the file of this link from disk and DB completely:
		return true;
	}


	/**
	 * Insert object into DB based on previously recorded changes.
	 *
	 * @return boolean true on success
	 */
	function dbinsert()
	{
		global $DB, $Plugins, $localtimenow, $current_User;

		if( $this->ID != 0 && !$this->allow_ID_insert )
		{
			die( 'Existing object/object with an ID cannot be inserted!' );
		}

		if( !empty($this->datecreated_field) )
		{ // We want to track creation date:
			$this->set_param( $this->datecreated_field, 'date', date('Y-m-d H:i:s',$localtimenow) );
		}
		if( !empty($this->datemodified_field) )
		{ // We want to track modification date:
			$this->set_param( $this->datemodified_field, 'date', date('Y-m-d H:i:s',$localtimenow) );
		}
		if( is_logged_in() )
		{ // Assign user's ID only when user is logged in
			if( !empty($this->creator_field) )
			{ // We want to track creator:
				if( empty($this->creator_user_ID) )
				{ // No creator assigned yet, use current user:
					$this->set_param( $this->creator_field, 'number', $current_User->ID );
				}
			}
			if( !empty($this->lasteditor_field) )
			{ // We want to track last editor:
				if( empty($this->lastedit_user_ID) )
				{ // No editor assigned yet, use current user:
					$this->set_param( $this->lasteditor_field, 'number', $current_User->ID );
				}
			}
		}

		$sql_fields = array();
		$sql_values = array();
		$auto_order = false;
		$link_owner_ID = NULL;
		$link_owner_ID_field = NULL;
		$link_owner_fields = array(	'link_itm_ID', 'link_cmt_ID', 'link_usr_ID', 'link_ecmp_ID', 'link_msg_ID', 'link_tmp_ID' );

		foreach( $this->dbchanges as $loop_dbfieldname => $loop_dbchange )
		{
			// Get changed value (we use eval() to allow constructs like $loop_dbchange['value'] = 'Group->get(\'ID\')'):
			eval( '$loop_value = $this->'. $loop_dbchange['value'].';' );
			// Prepare matching statement:

			if( $loop_dbfieldname == 'link_order' && $loop_value === 0 )
			{
				$auto_order = true;
				continue;
			}
			elseif( in_array( $loop_dbfieldname, $link_owner_fields ) )
			{
				$link_owner_ID_field = $loop_dbfieldname;
				$link_owner_ID = $loop_value;
			}

			$sql_fields[] = $loop_dbfieldname;

			if( is_null($loop_value) )
			{
				$sql_values[] = 'NULL';
			}
			else
			{
				switch( $loop_dbchange['type'] )
				{
					case 'date':
					case 'string':
						$sql_values[] = $DB->quote( $loop_value );
						break;

					default:
						$sql_values[] = $DB->null( $loop_value );
				}
			}
		}

		// Prepare full statement:
		if( $auto_order && ! empty( $link_owner_ID_field ) && ! empty( $link_owner_ID ) )
		{ // Auto generate link_order in DB
			$sql_fields[] = 'link_order';
			$sql_values[] = 'COALESCE(z.max_order, 0) + 1';
			$sql = "INSERT INTO {$this->dbtablename} (". implode( ', ', $sql_fields ). ") SELECT ". implode( ', ', $sql_values )
				." FROM (SELECT MAX(link_order) AS max_order FROM T_links WHERE ".$link_owner_ID_field." = ".$link_owner_ID.") AS z";
		}
		else
		{
			$sql = "INSERT INTO {$this->dbtablename} (". implode( ', ', $sql_fields ). ") VALUES (". implode( ', ', $sql_values ). ")";
		}
		//echo $sql;

		if( ! $DB->query( $sql, 'DataObject::dbinsert()' ) )
		{
			return false;
		}


		if( !( $this->allow_ID_insert && $this->ID ) )
		{// store ID for newly created db record. Do not if allow_ID_insert is true and $this->ID is not 0

			$this->ID = $DB->insert_id;
		}
		// Reset changes in object:
		$this->dbchanges = array();

		if( !empty( $Plugins ) )
		{
			$Plugins->trigger_event( 'AfterObjectInsert', $params = array( 'Object' => & $this, 'type' => get_class($this) ) );
		}

		return true;
	}
}

?>