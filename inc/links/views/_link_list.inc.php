<?php
/**
 * This file displays the links attached to an Object, which can be an Item, Comment, ... (called within the attachment_frame)
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $Collection, $Blog;

global $blog;

/**
 * Needed by functions
 * @var LinkOwner
 */
global $LinkOwner;

$SQL = $LinkOwner->get_SQL( 'link_ID' );
$Results = new Results( $SQL->get(), 'link_' );

$Results->title = T_('Attachments');

/*
 * TYPE
 */
function display_type( & $row )
{
	if( !empty($row->file_ID) )
	{
		return T_('File');
	}

	return '?';
}
$Results->cols[] = array(
						'th' => T_('Type'),
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap',
						'td' => '%display_type( {row} )%',
					);


/*
 * Sub Type column
 */
function display_subtype( $link_ID )
{
	global $LinkOwner, $current_File;

	$Link = $LinkOwner->get_link_by_link_ID( $link_ID );
	// Instantiate a File object for this line
	$current_File = $Link->get_File();

	return $Link->get_preview_thumb();
}
$Results->cols[] = array(
						'th' => T_('Icon/Type'),
						'td_class' => 'shrinkwrap',
						'td' => '%display_subtype( #link_ID# )%',
					);


/*
 * LINK column
 */
function display_link()
{
	/**
	 * @var File
	 */
	global $current_File;

	if( empty( $current_File ) )
	{
		return '?';
	}

	$r = '';

	// File relative path & name:
	if( $current_File->is_dir() )
	{ // Directory
		$r .= $current_File->dget( '_name' );
	}
	else
	{ // File
		if( $view_link = $current_File->get_view_link() )
		{
			$r .= $view_link;
		}
		else
		{ // File extension unrecognized
			$r .= $current_File->dget( '_name' );
		}
	}

	$title = $current_File->dget('title');
	if( $title !== '' )
	{
		$r .= '<span class="filemeta"> - '.$title.'</span>';
	}

	return $r;
}
$Results->cols[] = array(
						'th' => T_('Destination'),
						'td' => '%display_link()%',
					);


if( $current_User->check_perm( 'files', 'view' ) )
{
	function file_actions( $link_ID )
	{
		/**
		 * @var File
		 */
		global $current_File;
		global $LinkOwner, $current_User;

		$r = '';

		if( isset($current_File) && $current_User->check_perm( 'files', 'view', false, $current_File->get_FileRoot() ) )
		{
			if( $current_File->is_dir() )
				$title = T_('Locate this directory!');
			else
				$title = T_('Locate this file!');
			$r = $current_File->get_linkedit_link( $LinkOwner->type, $LinkOwner->get_ID(), get_icon( 'locate', 'imgtag', array( 'title'=>$title ) ), $title ).' ';
		}

		if( $LinkOwner->check_perm( 'edit', false ) )
		{	// Check that we have permission to edit LinkOwner object:
			$r .= action_icon( T_('Delete this link!'), 'unlink',
			                  regenerate_url( 'ctrl,p,itm_ID,action', 'ctrl=links&amp;link_ID='.$link_ID.'&amp;action=unlink&amp;'.url_crumb('link') ) );
		}

		return $r;
	}
	$Results->cols[] = array(
							'th' => T_('Actions'),
							'td_class' => 'shrinkwrap',
							'td' => '%file_actions( #link_ID# )%',
						);
}

if( $current_User->check_perm( 'files', 'view' )
	&& $LinkOwner->check_perm( 'edit' ) )
{	// Check that we have permission to edit LinkOwner object:
	$Results->global_icon( T_('Link a file...'), 'link', url_add_param( $Blog->get_filemanager_link(),
														'fm_mode=link_object&amp;link_type='.$LinkOwner->type.'&amp;link_object_ID='.$LinkOwner->get_ID() ), T_('Attach files'), 3, 4 );
}

$Results->display();

?>