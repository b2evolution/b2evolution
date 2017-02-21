<?php
/**
 * This file implements the LinkEmailCampaign class, which is a wrapper class for EmailCampaign class to handle linked files.
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
 * LinkEmailCampaign Class
 *
 * @package evocore
 */
class LinkEmailCampaign extends LinkOwner
{
	/**
	 * @var EmailCampaign
	 */
	var $EmailCampaign;

	/**
	 * Constructor
	 */
	function __construct( $EmailCampaign )
	{
		// call parent contsructor
		parent::__construct( $EmailCampaign, 'emailcampaign', 'ecmp_ID' );
		$this->EmailCampaign = & $this->link_Object;

		$this->_trans = array(
			'Link this image to your xxx' => NT_('Link this image to your email campaign.'),
			'Link this file to your xxx' => NT_('Link this file to your email campaign.'),
			'The file will be linked for download at the end of the xxx' => NT_('The file will be appended for linked at the end of the email campaign.'),
			'Insert the following code snippet into your xxx' => NT_('Insert the following code snippet into your email campaign.'),
			'View this xxx...' => NT_('View this email campaign...'),
			'Edit this xxx...' => NT_('Edit this email campaign...'),
			'Link files to current xxx' => NT_('Link files to current email campaign'),
			'Selected files have been linked to xxx.' => NT_('Selected files have been linked to email campaign.'),
			'Link has been deleted from $xxx$.' => NT_('Link has been deleted from email campaign.'),
		);
	}

	/**
	 * Check current User Email Campaign permission
	 *
	 * @param string permission level
	 * @param boolean true to assert if user dosn't have the required permission
	 */
	function check_perm( $permlevel, $assert = false )
	{
		global $current_User;
		return $current_User->check_perm( 'emails', $permlevel, $assert );
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
	 * Load all links of owner Email Campaign if it was not loaded yet
	 */
	function load_Links()
	{
		if( is_null( $this->Links ) )
		{	// Links have not been loaded yet:
			$LinkCache = & get_LinkCache();
			$this->Links = $LinkCache->get_by_emailcampaign_ID( $this->EmailCampaign->ID );
		}
	}

	/**
	 * Add new link to owner Email Campaign
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
		// Email Campaign has no collection
	}


	/**
	 * Get Email Campaign parameter
	 *
	 * @param string parameter name to get
	 */
	function get( $parname )
	{
		switch( $parname )
		{
			case 'name':
				return 'emailcampaign';
		}
		return parent::get( $parname );
	}


	/**
	 * Get Email Campaign edit url
	 */
	function get_edit_url()
	{
		global $admin_url;

		return $admin_url.'?ctrl=campaigns&amp;action=edit&amp;tab=compose&amp;ecmp_ID='.$this->EmailCampaign->ID;
	}

	/**
	 * Get Email Campaign view url
	 */
	function get_view_url()
	{
		global $admin_url;

		return $admin_url.'?ctrl=campaigns&amp;action=edit&amp;tab=send&amp;ecmp_ID='.$this->EmailCampaign->ID;
	}


	/**
	 * This function is called after when some file was unlinked from Email Campaign
	 *
	 * @param integer Link ID
	 */
	function after_unlink_action( $link_ID = 0 )
	{
		if( empty( $this->EmailCampaign ) )
		{	// No existing Email Campaign, Exit here:
			return;
		}

		if( ! empty( $link_ID ) )
		{	// Find inline image placeholders if link ID is defined:
			preg_match_all( '/\[(image|file|inline|video|audio|thumbnail):'.$link_ID.':?[^\]]*\]/i', $this->EmailCampaign->email_text, $inline_images );
			if( ! empty( $inline_images[0] ) )
			{	// There are inline image placeholders in the email content:
				$this->EmailCampaign->set( 'email_text', str_replace( $inline_images[0], '', $this->EmailCampaign->email_text ) );
				$this->EmailCampaign->dbupdate();
				return;
			}
		}
	}
}

?>