<?php
/**
 * This file implements the LinkMessage class, which is a wrapper class for Message class to handle linked files.
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
 * LinkMessage Class
 *
 * @package evocore
 */
class LinkMessage extends LinkOwner
{
	/**
	 * @var Message
	 */
	var $Message;

	/**
	 * Constructor
	 *
	 * @param object Message
	 * @param integer ID of temporary object from table T_temporary_ID (Used to link files to new creating message)
	 */
	function __construct( $Message, $tmp_ID = NULL )
	{
		// call parent contsructor
		parent::__construct( $Message, 'message', 'msg_ID', $tmp_ID );
		$this->Message = & $this->link_Object;

		$this->_trans = array(
			'Link this image to your xxx' => NT_('Link this image to your message.'),
			'Link this file to your xxx' => NT_('Link this file to your message.'),
			'The file will be linked for download at the end of the xxx' => NT_('The file will be appended for linked at the end of the message.'),
			'Insert the following code snippet into your xxx' => NT_('Insert the following code snippet into your message.'),
			'View this xxx...' => NT_('View this message...'),
			'Edit this xxx...' => NT_('Edit this message...'),
			'Link files to current xxx' => NT_('Link files to current message'),
			'Selected files have been linked to xxx.' => NT_('Selected files have been linked to message.'),
			'Link has been deleted from $xxx$.' => NT_('Link has been deleted from message.'),
		);
	}

	/**
	 * Check current User Message permission
	 *
	 * @param string permission level
	 * @param boolean true to assert if user dosn't have the required permission
	 */
	function check_perm( $permlevel, $assert = false )
	{
		global $current_User;
		return $current_User->check_perm( 'perm_messaging', 'reply', $assert );
	}


	/**
	 * Get all positions ( key, display ) pairs where link can be displayed
	 *
	 * @param integer File ID
	 * @return array
	 */
	function get_positions( $file_ID = NULL )
	{
		return array( 'inline' => T_('Inline') );
	}


	/**
	 * Get default position for a new link
	 *
	 * @param integer File ID
	 * @return string Position
	 */
	function get_default_position( $file_ID )
	{
		return 'inline';
	}

	/**
	 * Load all links of owner Message if it was not loaded yet
	 */
	function load_Links()
	{
		if( is_null( $this->Links ) )
		{	// Links have not been loaded yet:
			$LinkCache = & get_LinkCache();
			if( $this->is_temp )
			{
				$this->Links = $LinkCache->get_by_temporary_ID( $this->get_ID() );
			}
			else
			{
				$this->Links = $LinkCache->get_by_message_ID( $this->get_ID() );
			}
		}
	}

	/**
	 * Add new link to owner Message
	 *
	 * @param integer file ID
	 * @param integer link position 'inline'
	 * @param int order of the link
	 * @param boolean true to update owner last touched timestamp after link was created, false otherwise
	 * @return integer|boolean Link ID on success, false otherwise
	 */
	function add_link( $file_ID, $position = NULL, $order = 1, $update_owner = true )
	{
		if( is_null( $position ) )
		{	// Use default link position:
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
			syslog_insert( sprintf( '%s %s was linked to %s with ID=%s',  ucfirst( $file_dir ), '[['.$file_name.']]', $this->type, $this->get_ID() ), 'info', 'file', $file_ID );

			// Reset the Links:
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
		// Message has no collection
	}


	/**
	 * Get Message parameter
	 *
	 * @param string parameter name to get
	 */
	function get( $parname )
	{
		switch( $parname )
		{
			case 'name':
				return 'message';
		}
		return parent::get( $parname );
	}


	/**
	 * Get Message edit url
	 */
	function get_edit_url()
	{
		return $this->get_view_url();
	}

	/**
	 * Get Message view url
	 */
	function get_view_url()
	{
		$view_url = '';
		if( ! empty( $this->Message ) && ( $this->Message instanceof Message ) && $Thread = & $this->Message->get_Thread() )
		{
			$view_url = $admin_url.'?ctrl=messages&amp;thrd_ID='.$Thread->ID;
		}

		return $view_url;
	}
}

?>