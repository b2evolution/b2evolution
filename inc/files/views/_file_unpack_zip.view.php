<?php
/**
 * This file implements the UI for page to unpack ZIP archive.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $fm_Filelist, $selected_Filelist;

$Form = new Form( NULL, 'fm_download_checkchanges', 'post', 'compact' );

$Form->begin_form( 'fform', T_('Unpack ZIP archives') );

	$unpack_is_done = false;
	echo '<ul>';
	foreach( $selected_Filelist->get_array() as $selected_File )
	{
		echo '<li>'.sprintf( T_('Unpacking %s').'... ', '<code>'.$selected_File->get_name().'</code>' );
		evo_flush();

		if( ! is_file( $selected_File->get_full_path() ) ||
		    ! preg_match( '/\.zip$/i', $selected_File->get_full_path() ) )
		{	// Not ZIP archive was selected:
			echo '<span class="text-danger">'.T_('Skipped because this is not ZIP archive!').'</span>';
		}
		else
		{	// Try to unpack:
			$zip_info = pathinfo( $selected_File->get_full_path() );
			$unpack_folder = $zip_info['dirname'].'/'.$zip_info['filename'];
			$folder_suffix = '';
			while( file_exists( $unpack_folder.$folder_suffix ) )
			{	// Find free(not existing) folder to unpack:
				$folder_suffix++;
			}
			$unpack_folder .= $folder_suffix;
			$unpack_result = unpack_archive( $selected_File->get_full_path(), $unpack_folder, true, $zip_info['basename'], false );
			if( $unpack_result === true )
			{	// Display success result:
				printf( T_('Unpacked into the folder %s.'), '<code>'.basename( $unpack_folder ).'</code>' );
				$unpack_is_done = true;
			}
			else
			{	// Display errors:
				echo $unpack_result;
			}
		}
		echo '</li>';
	}
	echo '</ul>';

	if( $unpack_is_done && ! empty( $fm_Filelist ) )
	{	// Reload files list to display new folders after at least one unpacking:
		global $fm_Filelist;
		$fm_Filelist->load();
	}

$Form->end_form();

?>