<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @package maintenance
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var action
 */
global $action;

global $updates, $upgrade_path, $admin_url;

$Form = new Form( NULL, 'upgrade_form', 'post', 'compact' );

$Form->begin_form( 'fform', T_( 'Check for updates' ) );

if( empty( $updates ) )
{ // No new updates
	?><div class="action_messages">
		<div class="log_error" style="text-align:center;font-weight:bold"><?php echo T_( 'There are no new updates.' ); ?></div>
	</div><?php

	$Form->end_form();
}
else
{ // Display a form to download new update
	$update = $updates[0];

	$Form->info( T_( 'Update' ), $update['name'] );
	$Form->info( T_( 'Description' ), $update['description'] );
	$Form->info( T_( 'Version' ), $update['version'] );

	$Form->text_input( 'upd_url', ( get_param( 'upd_url' ) != '' ? get_param( 'upd_url' ) : $update['url'] ), 90,
		T_('URL'), '<br/><span class="note">'.T_( 'You <i>might</i> replace this with a different URL in case you want to upgrade to a custom version.' ).'</span>', array( 'maxlength' => 300, 'required' => true ) );

	$Form->add_crumb( 'upgrade_started' );
	$Form->hiddens_by_key( get_memorized( 'action' ) );

	$Form->end_form( array( array( 'submit', 'actionArray[download]', T_( 'Continue' ), 'SaveButton' ) ) );
}

// Display a list of already downloaded packages:
$Table = new Table( 'Results', 'upgrade' );

$Table->title = T_('Already Downloaded').get_manual_link( 'auto-upgrade-already-downloaded' );

$Table->cols = array(
	array( 'th' => T_('Zip archive').'/'.T_('Folder') ),
	array( 'th' => T_('Actions'), 'td_class' => 'shrinkwrap' ),
);

// Find all ZIP files and folders in the _upgrade folder:
$downloaded_files = array();
if( $dir_handle = @opendir( $upgrade_path ) )
{
	while( ( $file = readdir( $dir_handle ) ) !== false )
	{
		if( $file != '.' && $file != '..' &&
		    ( is_dir( $upgrade_path.$file ) || preg_match( '#\.zip$#i', $file ) ) )
		{	// Only folder or ZIP file:
			$downloaded_files[] = $file;
		}
	}
	closedir( $dir_handle );
}

$Table->display_init();

echo $Table->params['before'];

// TITLE:
$Table->display_head();

if( empty( $downloaded_files ) )
{	// No files to import:
	$Table->total_pages = 0;
	$Table->no_results_text = T_('No files found.');

	// BODY START:
	$Table->display_body_start();
	$Table->display_list_start();
	$Table->display_list_end();
	// BODY END:
	$Table->display_body_end();
}
else
{	// Display the files to import in table:

	// TABLE START:
	$Table->display_list_start();

	// COLUMN HEADERS:
	$Table->display_col_headers();
	// BODY START:
	$Table->display_body_start();

	// Sort files:
	natsort( $downloaded_files );
	$downloaded_files = array_reverse( $downloaded_files );

	foreach( $downloaded_files as $file )
	{
		$Table->display_line_start();

		// Zip archive/Folder
		$Table->display_col_start();
		echo $file;
		$Table->display_col_end();

		$use_file_url = $admin_url.'?ctrl=upgrade&amp;action=';
		if( ! is_dir( $upgrade_path.$file ) )
		{
			$use_file_url .= 'unzip&amp;upd_file='.urlencode( $file ).'&amp;'.url_crumb( 'upgrade_downloaded' );
			$confirm_message = TS_('Are you sure want to delete this file?');
		}
		else
		{
			$use_file_url .= 'ready&amp;upd_dir='.urlencode( $file ).'&amp;'.url_crumb( 'upgrade_is_ready' );
			$confirm_message = TS_('Are you sure want to delete this folder?');
		}
		$del_file_url = $admin_url.'?ctrl=upgrade&amp;action=delete&amp;file='.urlencode( $file ).'&amp;'.url_crumb( 'upgrade_delete' );

		// File date
		$Table->display_col_start();
		echo '<a href="'.$use_file_url.'" class="btn btn-warning btn-xs">'.T_('Use this...').'</a> ';
		echo '<a href="'.$del_file_url.'" class="btn btn-danger btn-xs" onclick="return confirm(\''.$confirm_message.'\')">'.T_('Delete').'</a> ';
		$Table->display_col_end();

		$Table->display_line_end();

		evo_flush();
	}

	// BODY END:
	$Table->display_body_end();

	// TABLE END:
	$Table->display_list_end();
}

echo $Table->params['after'];
?>