<?php
/**
 * This file implements the UI for the form to create ZIP archive.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $zipname, $exclude_sd, $delete_files, $selected_Filelist;

$Form = new Form( NULL, 'fm_download_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Cancel download!'), 'close', regenerate_url() );

$Form->begin_form( 'fform', T_('Create ZIP archive') );
	$Form->hidden_ctrl();
	$Form->hidden( 'action', 'create_zip' );
	$Form->hidden( 'action_invoked', 1 );
	$Form->hiddens_by_key( get_memorized() );

	$Form->text_input( 'zipname', $zipname, 30, T_('Archive filename'), T_('End with .zip'),  array( 'maxlength' => '' ) );

	if( $selected_Filelist->count_dirs() )
	{	// Allow to exclude dirs:
		$Form->checkbox( 'exclude_sd', $exclude_sd, T_('Exclude subdirectories'), T_('This will exclude subdirectories of selected directories.') );
	}

	$Form->info( T_('Files to include'), '<ul>'
		.'<li>'.implode( "</li>\n<li>", $selected_Filelist->get_array( 'get_prefixed_name' ) )."</li>\n"
		.'</ul>' );

	// Allow to delete the files after archive:
	$Form->checkbox( 'delete_files', $delete_files, '', T_('Delete these files upon successful archive creation') );

$Form->end_form( array(
		array( 'submit', 'submit', T_('Create'), 'btn-primary' ),
		array( 'button', 'button', T_('Cancel'), 'btn-default', 'location.href=\''.regenerate_url( '', '', '', '&' ).'\'' ),
	) );

?>