<?php
/**
 * This file implements the UI for item links in the filemanager.
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
 * @var LinkOwner
 */
global $LinkOwner;

global $mode;

if( $mode != 'upload' )
{	// If not opearting in a popup opened from post edit screen:

	$Form = new Form( NULL, 'fm_links', 'post', 'fieldset' );


	$Form->begin_form( 'fform' );

	$Form->hidden_ctrl();

	$SQL = $LinkOwner->get_SQL();

	$Results = new Results( $SQL->get(), 'link_' );

	$view_link_title = $LinkOwner->translate( 'View this xxx...' );
	$Results->title = sprintf( T_('Files linked to &laquo;%s&raquo;'),
					'<a href="'.$LinkOwner->get_view_url().'" title="'.$view_link_title.'">'.$LinkOwner->get( 'title' ).'</a>' );

	if( $LinkOwner->check_perm( 'edit', false ) )
	{
		$Results->global_icon( $LinkOwner->translate( 'Edit this xxx...' ), 'edit', $LinkOwner->get_edit_url(), T_('Edit') );
	}

	// Close link mode and continue in File Manager (remember the Item_ID though):
	$Results->global_icon( T_('Quit link mode!'), 'close', regenerate_url( 'fm_mode' ) );


	// TYPE COLUMN:
	function file_type( & $row )
	{
		global $LinkOwner, $current_File;

		$Link = $LinkOwner->get_link_by_link_ID( $row->link_ID );
		// Instantiate a File object for this line
		$current_File = $Link->get_File();
		// Return Link tag
		return $Link->get_preview_thumb();
	}
	$Results->cols[] = array(
							'th' => T_('File'),
							'order' => 'link_ID',
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => '%file_type( {row} )%',
						);


	// PATH COLUMN:
	function file_path()
	{
		/**
		 * @global File
		 */
		global $current_File, $current_User;
		global $LinkOwner;

		$r = T_( 'You don\'t have permission to access this file root' );
		if( $current_User->check_perm( 'files', 'view', false, $current_File->get_FileRoot() ) )
		{
			// File relative path & name:
			$r = $current_File->get_linkedit_link( $LinkOwner->type, $LinkOwner->get_ID() );
		}
		return $r;
	}
	$Results->cols[] = array(
							'th' => T_('Path'),
							'order' => 'file_path',
							'td_class' => 'left',
							'td' => '%file_path()%',
						);


	// TITLE COLUMN:
	$Results->cols[] = array(
							'th' => T_('Title'),
							'order' => 'file_title',
							'td_class' => 'left',
							'td' => '$file_title$',
						);

	// ACTIONS COLUMN:
	$Results->cols[] = array(
							'th' => T_('Actions'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => '%link_actions( #link_ID#, {ROW_IDX_TYPE} )%',
						);

	// POSITION COLUMN:
	$Results->cols[] = array(
						'th' => T_('Position'),
						'td_class' => 'shrinkwrap',
						'td' => '%display_link_position( {row} )%',
					);

	$Results->display();

	// Print out JavaScript to change a link position
	echo_link_position_js();

	$Form->end_form( );
}
?>