<?php
/**
 * This file implements the UI for browsing attached files.
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

/**
 * @var Linklist
 */
global $ea_Linklist;
/**
 * @var Filelist
 */
global $fm_Filelist;
/**
 * @var File
 */
global $lFile;
/**
 * @var string
 */
global $fm_flatmode;
/**
 * @var UserSettings
 */
global $UserSettings;
/**
 * @var Log
 */
global $Messages;
/**
 * @var Filelist
 */
global $selected_Filelist;
/**
 * @var Link Owner
 */
global $LinkOwner;

global $edited_User;

global $Collection, $Blog, $blog;

global $fm_mode, $fm_hide_dirtree, $create_name, $ads_list_path, $mode;

// Abstract data we want to pass through:
global $linkctrl, $linkdata;

// Name of the iframe we want some actions to come back to:
global $iframe_name, $field_name, $file_type;

?>
<table class="filelist table table-bordered table-hover table-condensed">
	<?php
	ob_start();
	?>
	<thead>
	<?php
		/*****************  Col headers  ****************/

		echo '<tr>';

		echo '<th class="firstcol nowrap">';
		if( $UserSettings->get( 'fm_imglistpreview' ) )
		{	// Image file preview:
			$col_title = T_('Icon/Type');
		}
		else
		{
			$col_title = /* TRANS: short for (file)Type */ T_('T ');		// Not to be confused with T for Tuesday
		}
		echo $col_title;
		echo '</th>';

		if( $fm_flatmode )
		{
			echo '<th>'./* TRANS: file/directory path */ T_('Path').'</th>';
		}

		echo '<th class="nowrap">'./* TRANS: file name */ T_('Name').'</th>';

		if( $UserSettings->get('fm_showtypes') )
		{	// Show file types column
			echo '<th class="nowrap">'./* TRANS: file type */ T_('Type').'</th>';
		}

		if( $UserSettings->get('fm_showcreator') )
		{	// Show file creator
			echo '<th class="nowrap">'./* TRANS: added by */ T_('Added by').'</th>';
		}

		if( $UserSettings->get('fm_showdownload') )
		{	// Show download count column
			echo '<th class="nowrap">'./* TRANS: download count */ T_('Downloads').'</th>';
		}

		echo '<th class="nowrap">'./* TRANS: creation date of source Item or Comment */ T_('Date created').'</th>';

		echo '<th class="nowrap">'./* TRANS: file size */ T_('Size').'</th>';

		if( $UserSettings->get('fm_showdate') != 'no' )
		{	// Show last mod column
			echo '<th class="nowrap">'./* TRANS: file's last change / timestamp */ T_('Last change').'</th>';
		}

		if( $UserSettings->get('fm_showfsperms') )
		{	// Show file perms column
			echo '<th class="nowrap">'./* TRANS: file's permissions (short) */ T_('Perms').'</th>';
		}

		if( $UserSettings->get('fm_showfsowner') )
		{	// Show file owner column
			echo '<th class="nowrap">'./* TRANS: file owner */ T_('Owner').'</th>';
		}

		if( $UserSettings->get('fm_showfsgroup') )
		{	// Show file group column
			echo '<th class="nowrap">'./* TRANS: file group */ T_('Group').'</th>';
		}

		echo '<th class="lastcol nowrap">'. /* TRANS: file actions; edit, rename, copy, .. */ T_('Actions').'</th>';
		echo '</tr>';
	?>
	</thead>
	<?php
	$table_headers = ob_get_clean();
	if( $ea_Linklist->get_total_rows() > 0 )
	{	// Display table headers only when at least file is found in the selected folder and filter:
		echo $table_headers;
	}
	?>
	<tbody class="filelist_tbody">
	<?php
	$fm_highlight = param( 'fm_highlight', 'string', NULL );

	/***********************************************************/
	/*                    MAIN FILE LIST:                      */
	/***********************************************************/
	$countFiles = 0;
	while( $lLink = & $ea_Linklist->get_next() )
	{	// Loop through all Files:
		$lFile = & $lLink->get_File();

		if( $lFile->is_dir() )
		{	// Skip directories:
			continue;
		}

		$lLinkOwner = & $lLink->get_LinkOwner();
		$row_class = array();
		if( ( get_class( $lLinkOwner->link_Object ) == 'Comment' ) && $lLinkOwner->link_Object->is_meta() )
		{	// Show different background color for internal comments:
			$row_class[] = 'bg-info';
		}
		echo '<tr class="'.implode( ' ', $row_class ).'"';

		if( isset($fm_highlight) && $lFile->get_name() == $fm_highlight )
		{	// We want a specific file to be highlighted (user clicked on "locate"/target icon
			echo ' id="fm_highlighted"'; // could be a class, too..
		}
		echo '>';


		/********************  Icon / File type:  *******************/

		echo '<td class="firstcol icon_type text-nowrap">';
		if( $UserSettings->get( 'fm_imglistpreview' ) )
		{	// Image preview OR full type:
			echo $lFile->get_preview_thumb( 'fulltype', array( 'init' => true ) );
		}
		else
		{	// No image preview, small type:
			echo $lFile->get_view_link( $lFile->get_icon(), NULL, $lFile->get_icon() );
		}
		echo '</td>';
		evo_flush();

		/*******************  Path (flatmode): ******************/

		if( $fm_flatmode )
		{
			echo '<td class="filepath">';
			echo dirname( $lFile->get_rdfs_rel_path() ).'/';
			echo '</td>';
			evo_flush();
		}

		/*******************  File name: ******************/
		if( ! $fm_flatmode ||
		    ( $selected_Filelist->get_rds_list_path() === false && dirname( $lFile->get_rdfs_rel_path() ) == '.' ) ||
		    ( $selected_Filelist->get_rds_list_path() == dirname( $lFile->get_rdfs_rel_path() ).'/' ) )
		{	// Use a hidden field only for current folder and not for subfolders
			// It is used to detect a duplicate file on quick upload
			$filename_hidden_field = '<input type="hidden" value="'.$lFile->get_root_and_rel_path().'" />';
		}
		else
		{	// Don't use the hidden field for this file because it is from another folder
			$filename_hidden_field = '';
		}
		echo '<td class="fm_filename">'
			.$filename_hidden_field;

			/*************  Invalid filename warning:  *************/

			if( !$lFile->is_dir() )
			{
				if( $error_filename = validate_filename( $lFile->get_name() ) )
				{	// TODO: Warning icon with hint
					echo get_icon( 'warning', 'imgtag', array( 'class' => 'filenameIcon', 'title' => strip_tags( $error_filename ), 'data-toggle' => 'tooltip' ) ).'&nbsp;';
					syslog_insert( sprintf( 'The unrecognized extension is detected for file %s', '[['.$lFile->get_name().']]' ), 'warning', 'file', $lFile->ID );
				}
			}

			/***************  Link ("chain") icon:  **************/

			// Only provide link/"chain" icons for files.

			// fp> here might not be the best place to put the perm check
			if( isset( $LinkOwner ) && $LinkOwner->check_perm( 'edit' ) )
			{	// Offer option to link the file to an Item (or anything else):
				$link_attribs = array( 'class' => 'action_icon link_file btn btn-primary btn-xs' );
				$link_action = 'link';
				if( $mode == 'upload' )
				{	// We want the action to happen in the post attachments iframe:
					$link_attribs['target'] = $iframe_name;
					$link_attribs['onclick'] = 'return evo_link_attach( \''.$LinkOwner->type.'\', '.$LinkOwner->get_ID()
							.', \''.FileRoot::gen_ID( $fm_Filelist->get_root_type(), $fm_Filelist->get_root_ID() )
							.'\', \''.$lFile->get_rdfp_rel_path().'\', \''.param( 'prefix', 'string' ).'\' )';
					$link_action = 'link_inpost';
				}
				echo action_icon( T_('Link this file!'), 'link',
							regenerate_url( 'fm_selected', 'action='.$link_action.'&amp;fm_selected[]='.rawurlencode($lFile->get_rdfp_rel_path()).'&amp;'.url_crumb('file') ),
							' '.T_('Attach'), NULL, 5, $link_attribs );
				echo ' ';
			}

			/******************** File name + meta data ********************/
			echo file_td_name( $lFile );

		echo '</td>';
		evo_flush();

		/*******************  File type  ******************/

		if( $UserSettings->get( 'fm_showtypes' ) )
		{	// Show file types
			echo '<td class="type">'.$lFile->get_type().'</td>';
			evo_flush();
		}

		/*******************  Added by  *******************/

		if( $UserSettings->get( 'fm_showcreator' ) )
		{
			if( $creator = $lFile->get_creator() )
			{
				echo '<td class="center">'.$creator->get( 'login' ).'</td>';
			}
			else
			{
				echo '<td class="center">unknown</td>';
			}
			evo_flush();
		}

		/****************  Download Count  ****************/

		if( $UserSettings->get( 'fm_showdownload' ) )
		{	// Show download count
			// erhsatingin> Can't seem to find proper .less file to add the 'download' class, using class 'center' instead
			echo '<td class="center">'.$lFile->get_download_count().'</td>';
			evo_flush();
		}

		/*******************  Link / Item date  ******************/

		$owner_date = '';
		switch( get_class( $lLinkOwner->link_Object ) )
		{
			case 'Comment':
			case 'Item':
			case 'EmailCampaign':
				$owner_date = $lLinkOwner->link_Object->get_creation_time();
				break;
		}
		echo '<td class="timestamp">'.$owner_date.'</td>';

		/*******************  File size  ******************/

		echo '<td class="size">'.$fm_Filelist->get_File_size_formatted( $lFile ).'</td>';

		/****************  File time stamp  ***************/

		if( $UserSettings->get( 'fm_showdate' ) != 'no' )
		{	// Show last modified datetime (always full in title attribute)
			$lastmod_date = $lFile->get_lastmod_formatted( 'date' );
			$lastmod_time = $lFile->get_lastmod_formatted( 'time' );
			echo '<td class="timestamp" title="'.format_to_output( $lastmod_date.' '.$lastmod_time, 'htmlattr' ).'">';
			echo file_td_lastmod( $lFile );
			echo '</td>';
			evo_flush();
		}

		/****************  File pemissions  ***************/

		if( $UserSettings->get( 'fm_showfsperms' ) )
		{	// Show file perms
			echo '<td class="perms">';
			$fm_permlikelsl = $UserSettings->param_Request( 'fm_permlikelsl', 'fm_permlikelsl', 'integer', 0 );
			echo $lFile->get_perms( $fm_permlikelsl ? 'lsl' : '' );
			echo '</td>';
			evo_flush();
		}

		/****************  File owner  ********************/

		if( $UserSettings->get( 'fm_showfsowner' ) )
		{	// Show file owner
			echo '<td class="fsowner">';
			echo $lFile->get_fsowner_name();
			echo '</td>';
			evo_flush();
		}

		/****************  File group *********************/

		if( $UserSettings->get( 'fm_showfsgroup' ) )
		{	// Show file owner
			echo '<td class="fsgroup">';
			echo $lFile->get_fsgroup_name();
			echo '</td>';
			evo_flush();
		}

		/*****************  Action icons  ****************/

		echo '<td class="actions lastcol text-nowrap">';
		echo file_td_actions( $lFile, array( 'move', 'copy', 'delete' ) );
		echo '</td>';
		evo_flush();

		echo '</tr>';
		evo_flush();


		$countFiles++;
	}
	// End of file list..


	/**
	 * @global integer Number of cols for the files table, 5 is minimum.
	 */
	$filetable_cols = 5
		+ ( int ) $fm_flatmode
		+ ( int ) $UserSettings->get( 'fm_showcreator' )
		+ ( int ) $UserSettings->get( 'fm_showtypes' )
		+ ( int ) ( $UserSettings->get( 'fm_showdate' ) != 'no' )
		+ ( int ) $UserSettings->get( 'fm_showfsperms' )
		+ ( int ) $UserSettings->get( 'fm_showfsowner' )
		+ ( int ) $UserSettings->get( 'fm_showfsgroup' )
		+ ( int ) $UserSettings->get( 'fm_showdownloads' )
		+ ( int ) $UserSettings->get( 'fm_imglistpreview' );

	$noresults = '';
	if( $countFiles == 0 )
	{	// Filelist errors or "directory is empty":
		$noresults = '<tr class="noresults">
			<td class="lastcol text-danger" colspan="'.$filetable_cols.'" id="fileman_error">'
				.T_('No files found.')
				.( $fm_Filelist->is_filtering() ? '<br />'.T_('Filter').': &laquo;'.$fm_Filelist->get_filter().'&raquo;' : '' )
			.'</td>
		</tr>';
		// Note: this var is also used for display_dragdrop_upload_button() below:
		echo $noresults;
	}

	echo '</tbody>';
	?>
</table>
<!-- End of detailed file list -->
