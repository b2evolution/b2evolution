<?php
/**
 * This file implements the UI for file download.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $zipname, $exclude_sd, $selected_Filelist;

$Form = new Form( NULL, 'fm_download_checkchanges' );

$Form->global_icon( T_('Cancel download!'), 'close', regenerate_url() );

$Form->begin_form( 'fform', T_('Download files in archive') );
	$Form->hidden_ctrl();
	$Form->hidden( 'action', 'download' );
	$Form->hidden( 'action_invoked', 1 );
	$Form->hiddens_by_key( get_memorized() );

	$Form->text_input( 'zipname', $zipname, 30, T_('Archive filename'), T_('This is the name of the file which will get sent to you.'),  array( 'maxlength' => '' ) );

	if( $selected_Filelist->count_dirs() )
	{ // Allow to exclude dirs:
		$Form->checkbox( 'exclude_sd', $exclude_sd, T_('Exclude subdirectories'), T_('This will exclude subdirectories of selected directories.') );
	}

	$Form->begin_fieldset( T_('Files to download') );
	echo '<ul>'
		.'<li>'.implode( "</li>\n<li>", $selected_Filelist->get_array( 'get_prefixed_name' ) )."</li>\n"
		.'</ul>';

	$Form->end_fieldset();

$Form->end_form( array(
		array( 'submit', 'submit', T_('Download'), 'btn-primary' ) ) );

?>