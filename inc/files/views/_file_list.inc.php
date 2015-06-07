<?php
/**
 * This file implements the UI for file browsing.
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

/**
 * @var Filelist
 */
global $fm_Filelist;
/**
 * fp> Temporary. I need this for NuSphere debugging.
 * @var File
 */
global $lFile;
/**
 * @var string
 */
global $fm_flatmode;
/**
 * @var User
 */
global $current_User;
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

global $Blog, $blog;

global $fm_mode, $fm_hide_dirtree, $create_name, $ads_list_path, $mode;

// Abstract data we want to pass through:
global $linkctrl, $linkdata;

// Name of the iframe we want some actions to come back to:
global $iframe_name;

$Form = new Form( NULL, 'FilesForm', 'post', 'none' );
$Form->begin_form();
	$Form->hidden_ctrl();

	$Form->hidden( 'confirmed', '0' );
	$Form->hidden( 'md5_filelist', $fm_Filelist->md5_checksum() );
	$Form->hidden( 'md5_cwd', md5($fm_Filelist->get_ads_list_path()) );
	$Form->hiddens_by_key( get_memorized('fm_selected') ); // 'fm_selected' gets provided by the form itself
?>
<table class="filelist table table-striped table-bordered table-hover table-condensed">
	<thead>
	<?php
		/*****************  Col headers  ****************/

		echo '<tr>';

		// "Go to parent" icon
		echo '<th class="firstcol">';
		if( empty($fm_Filelist->_rds_list_path) )
		{ // cannot go higher
			echo '&nbsp;';	// for IE
		}
		else
		{
			echo action_icon( T_('Go to parent folder'), 'folder_parent', regenerate_url( 'path', 'path='.$fm_Filelist->_rds_list_path.'..' ) );
		}
		echo '</th>';

		echo '<th class="nowrap">';
		if( $UserSettings->get( 'fm_imglistpreview' ) )
		{ // Image file preview:
			$col_title = T_('Icon/Type');
		}
		else
		{
			$col_title = /* TRANS: short for (file)Type */ T_('T ');		// Not to be confused with T for Tuesday
		}
		echo $fm_Filelist->get_sort_link( 'type', $col_title );
		echo '</th>';

		if( $fm_flatmode )
		{
			echo '<th>'.$fm_Filelist->get_sort_link( 'path', /* TRANS: file/directory path */ T_('Path') ).'</th>';
		}

		echo '<th class="nowrap">'.$fm_Filelist->get_sort_link( 'name', /* TRANS: file name */ T_('Name') ).'</th>';

		if( $UserSettings->get('fm_showtypes') )
		{ // Show file types column
			echo '<th class="nowrap">'.$fm_Filelist->get_sort_link( 'type', /* TRANS: file type */ T_('Type') ).'</th>';
		}

		echo '<th class="nowrap">'.$fm_Filelist->get_sort_link( 'size', /* TRANS: file size */ T_('Size') ).'</th>';

		if( $UserSettings->get('fm_showdate') != 'no' )
		{ // Show last mod column
			echo '<th class="nowrap">'.$fm_Filelist->get_sort_link( 'lastmod', /* TRANS: file's last change / timestamp */ T_('Last change') ).'</th>';
		}

		if( $UserSettings->get('fm_showfsperms') )
		{ // Show file perms column
			echo '<th class="nowrap">'.$fm_Filelist->get_sort_link( 'perms', /* TRANS: file's permissions (short) */ T_('Perms') ).'</th>';
		}

		if( $UserSettings->get('fm_showfsowner') )
		{ // Show file owner column
			echo '<th class="nowrap">'.$fm_Filelist->get_sort_link( 'fsowner', /* TRANS: file owner */ T_('Owner') ).'</th>';
		}

		if( $UserSettings->get('fm_showfsgroup') )
		{ // Show file group column
			echo '<th class="nowrap">'.$fm_Filelist->get_sort_link( 'fsgroup', /* TRANS: file group */ T_('Group') ).'</th>';
		}

		echo '<th class="lastcol nowrap">'. /* TRANS: file actions; edit, rename, copy, .. */ T_('Actions').'</th>';
		echo '</tr>';
	?>
	</thead>

	<tbody id="filelist_tbody">
	<?php
	$checkall = param( 'checkall', 'integer', 0 );  // Non-Javascript-CheckAll
	$fm_highlight = param( 'fm_highlight', 'string', NULL );

	// Set FileList perms
	$all_perm = $current_User->check_perm( 'files', 'all', false );
	$edit_perm = $current_User->check_perm( 'files', 'edit', false, $fm_Filelist->get_FileRoot() );

	/***********************************************************/
	/*                    MAIN FILE LIST:                      */
	/***********************************************************/
	$countFiles = 0;
	while( $lFile = & $fm_Filelist->get_next() )
	{ // Loop through all Files:
		echo '<tr class="'.($countFiles%2 ? 'odd' : 'even').'"';

		if( isset($fm_highlight) && $lFile->get_name() == $fm_highlight )
		{ // We want a specific file to be highlighted (user clicked on "locate"/target icon
			echo ' id="fm_highlighted"'; // could be a class, too..
		}
		echo '>';


		/********************    Checkbox:    *******************/

		echo '<td class="checkbox firstcol">';
		echo '<span name="surround_check" class="checkbox_surround_init">';
		echo '<input title="'.T_('Select this file').'" type="checkbox" class="checkbox"
					name="fm_selected[]" value="'.rawurlencode($lFile->get_rdfp_rel_path()).'" id="cb_filename_'.$countFiles.'"';
		if( $checkall || $selected_Filelist->contains( $lFile ) )
		{
			echo ' checked="checked"';
		}
		echo ' />';
		echo '</span>';

		/***********  Hidden info used by Javascript:  ***********/

		if( $mode == 'upload' )
		{	// This mode allows to insert img tags into the post...
			// Hidden info used by Javascript:
			echo '<input type="hidden" name="img_tag_'.$countFiles.'" id="img_tag_'.$countFiles
			    .'" value="'.format_to_output( $lFile->get_tag(), 'formvalue' ).'" />';
		}

		echo '</td>';


		/********************  Icon / File type:  *******************/

		echo '<td class="icon_type text-nowrap">';
		if( $UserSettings->get( 'fm_imglistpreview' ) )
		{	// Image preview OR full type:
			if( $lFile->is_dir() )
			{ // Navigate into Directory
				echo '<a href="'.$lFile->get_view_url().'" title="'.T_('Change into this directory').'">'.$lFile->get_icon().' '.T_('Directory').'</a>';
			}
			else
			{
				echo $lFile->get_preview_thumb( 'fulltype', array( 'init' => true ) );
			}
		}
		else
		{	// No image preview, small type:
			if( $lFile->is_dir() )
			{ // Navigate into Directory
				echo '<a href="'.$lFile->get_view_url().'" title="'.T_('Change into this directory').'">'.$lFile->get_icon().'</a>';
			}
			else
			{ // File
				echo $lFile->get_view_link( $lFile->get_icon(), NULL, $lFile->get_icon() );
			}
		}
		echo '</td>';

		/*******************  Path (flatmode): ******************/

		if( $fm_flatmode )
		{
			echo '<td class="filepath">';
			echo dirname( $lFile->get_rdfs_rel_path() ).'/';
			echo '</td>';
		}

		/*******************  File name: ******************/
		if( ! $fm_flatmode ||
		    ( $selected_Filelist->get_rds_list_path() === false && dirname( $lFile->get_rdfs_rel_path() ) == '.' ) ||
		    ( $selected_Filelist->get_rds_list_path() == dirname( $lFile->get_rdfs_rel_path() ).'/' ) )
		{ // Use a hidden field only for current folder and not for subfolders
		  // It is used to detect a duplicate file on quick upload
			$filename_hidden_field = '<input type="hidden" value="'.$lFile->get_root_and_rel_path().'" />';
		}
		else
		{ // Don't use the hidden field for this file because it is from another folder
			$filename_hidden_field = '';
		}
		echo '<td class="fm_filename">'
			.$filename_hidden_field;

			/*************  Invalid filename warning:  *************/

			if( !$lFile->is_dir() )
			{
				if( $error_filename = validate_filename( $lFile->get_name() ) )
				{ // TODO: Warning icon with hint
					echo get_icon( 'warning', 'imgtag', array( 'class' => 'filenameIcon', 'title' => $error_filename ) );
					syslog_insert( sprintf( 'The unrecognized extension is detected for file %s', '<b>'.$lFile->get_name().'</b>' ), 'warning', 'file', $lFile->ID );
				}
			}
			elseif( $error_dirname = validate_dirname( $lFile->get_name() ) )
			{ // TODO: Warning icon with hint
				echo get_icon( 'warning', 'imgtag', array( 'class' => 'filenameIcon', 'title' => $error_dirname ) );
				syslog_insert( sprintf( 'Invalid name is detected for folder %s', '<b>'.$lFile->get_name().'</b>' ), 'warning', 'file', $lFile->ID );
			}

			/****  Open in a new window  (only directories)  ****/

			if( $lFile->is_dir() )
			{ // Directory
				$browse_dir_url = $lFile->get_view_url();
				$popup_url = url_add_param( $browse_dir_url, 'mode=popup' );
				$target = 'evo_fm_'.$lFile->get_md5_ID();

				echo '<a href="'.$browse_dir_url.'" target="'.$target.' " class="pull-right"
							title="'.T_('Open in a new window').'" onclick="'
							."return pop_up_window( '$popup_url', '$target' )"
							.'">'.get_icon( 'window_new' ).'</a>';
			}

			/***************  Link ("chain") icon:  **************/

			// if( ! $lFile->is_dir() )	// fp> OK but you need to include an else:placeholder, otherwise the display is ugly
			{	// Only provide link/"chain" icons for files.
				// TODO: dh> provide support for direcories (display included files).

				// fp> here might not be the best place to put the perm check
				if( isset( $LinkOwner ) && $LinkOwner->check_perm( 'edit' ) )
				{	// Offer option to link the file to an Item (or anything else):
					$link_attribs = array();
					$link_action = 'link';
					if( $mode == 'upload' )
					{	// We want the action to happen in the post attachments iframe:
						$link_attribs['target'] = $iframe_name;
						$link_attribs['class'] = 'action_icon link_file';
						$link_action = 'link_inpost';
					}
					echo action_icon( T_('Link this file!'), 'link',
								regenerate_url( 'fm_selected', 'action='.$link_action.'&amp;fm_selected[]='.rawurlencode($lFile->get_rdfp_rel_path()).'&amp;'.url_crumb('file') ),
								NULL, NULL, NULL, $link_attribs );
					echo ' ';
				}

				if( isset($edited_User) ) // fp> Perm already checked in controller
				{	// Offer option to link the file to an Item (or anything else):
					if( $lFile->is_image() )
					{
						echo action_icon( T_('Use this as my profile picture!'), 'link',
									regenerate_url( 'fm_selected', 'action=link_user&amp;fm_selected[]='.rawurlencode($lFile->get_rdfp_rel_path()).'&amp;'.url_crumb('file') ),
									NULL, NULL, NULL, array() );
						echo ' ';
					}
				}
				elseif( !$lFile->is_dir() && ! empty( $linkctrl ) && ! empty( $linkdata ) )
				{
					echo action_icon( T_('Link this file!'), 'link',
								regenerate_url( 'fm_selected', 'action=link_data&amp;fm_selected[]='.rawurlencode($lFile->get_rdfp_rel_path()).'&amp;'.url_crumb('file') ),
								NULL, NULL, NULL, array() );

					echo ' ';
				}
			}

			/********************  Filename  ********************/

			if( $lFile->is_dir() )
			{ // Directory
				// Link to open the directory in the curent window
				echo '<a href="'.$browse_dir_url.'">'.$lFile->dget('name').'</a>';
			}
			else
			{ // File
				if( $view_link = $lFile->get_view_link( '<span class="fname">'.$lFile->get_name().'</span>', NULL, NULL ) )
				{
					echo $view_link;
				}
				else
				{ // File extension unrecognized
					echo $lFile->dget('name');
				}
			}

			/***************  File meta data:  **************/

			echo '<span class="filemeta">';
			// Optionally display IMAGE pixel size:
			if( $UserSettings->get( 'fm_getimagesizes' ) )
			{
				echo ' ('.$lFile->get_image_size( 'widthxheight' ).')';
			}
			// Optionally display meta data title:
			if( $lFile->meta == 'loaded' )
			{	// We have loaded meta data for this file:
				echo ' - '.$lFile->title;
			}
			echo '</span>';

		echo '</td>';

		/*******************  File type  ******************/

		if( $UserSettings->get('fm_showtypes') )
		{ // Show file types
			echo '<td class="type">'.$lFile->get_type().'</td>';
		}

		/*******************  File size  ******************/

		echo '<td class="size">'.$fm_Filelist->get_File_size_formatted($lFile).'</td>';

		/****************  File time stamp  ***************/

		if( $UserSettings->get('fm_showdate') != 'no' )
		{ // Show last modified datetime (always full in title attribute)
			$lastmod_date = $lFile->get_lastmod_formatted( 'date' );
			$lastmod_time = $lFile->get_lastmod_formatted( 'time' );
			echo '<td class="timestamp" title="'.$lastmod_date.' '.$lastmod_time.'">';
			if( $UserSettings->get('fm_showdate') == 'long' )
			{
				echo '<span class="date">'.$lastmod_date.'</span> ';
				echo '<span class="time">'.$lastmod_time.'</span>';
			}
			else
			{	// Compact format
				echo $lFile->get_lastmod_formatted( 'compact' );
			}
			echo '</td>';
		}

		/****************  File pemissions  ***************/

		if( $UserSettings->get('fm_showfsperms') )
		{ // Show file perms
			echo '<td class="perms">';
			$fm_permlikelsl = $UserSettings->param_Request( 'fm_permlikelsl', 'fm_permlikelsl', 'integer', 0 );

			if( $current_User->check_perm( 'files', 'edit', false, $blog ? $blog : NULL ) )
			{ // User can edit:
				echo '<a title="'.T_('Edit permissions').'" href="'.regenerate_url( 'fm_selected,action', 'action=edit_perms&amp;fm_selected[]='.rawurlencode($lFile->get_rdfp_rel_path()) ).'">'
							.$lFile->get_perms( $fm_permlikelsl ? 'lsl' : '' ).'</a>';
			}
			else
			{
				echo $lFile->get_perms( $fm_permlikelsl ? 'lsl' : '' );
			}
			echo '</td>';
		}

		/****************  File owner  ********************/

		if( $UserSettings->get('fm_showfsowner') )
		{ // Show file owner
			echo '<td class="fsowner">';
			echo $lFile->get_fsowner_name();
			echo '</td>';
		}

		/****************  File group *********************/

		if( $UserSettings->get('fm_showfsgroup') )
		{ // Show file owner
			echo '<td class="fsgroup">';
			echo $lFile->get_fsgroup_name();
			echo '</td>';
		}

		/*****************  Action icons  ****************/

		echo '<td class="actions lastcol text-nowrap">';

		if( $edit_perm )
		{ // User can edit:
			if( $lFile->is_editable( $all_perm ) )
			{
				echo action_icon( T_('Edit file...'), 'edit', regenerate_url( 'fm_selected', 'action=edit_file&amp;'.url_crumb('file').'&amp;fm_selected[]='.rawurlencode($lFile->get_rdfp_rel_path()) ) );
			}
			else
			{
				echo get_icon( 'edit', 'noimg' );
			}
		}

		if( $edit_perm )
		{ // User can edit:
			echo action_icon( T_('Edit properties...'), 'properties', regenerate_url( 'fm_selected', 'action=edit_properties&amp;fm_selected[]='.rawurlencode( $lFile->get_rdfp_rel_path() ).'&amp;'.url_crumb('file') ), NULL, NULL, NULL,
							array( 'onclick' => 'return file_properties( \''.get_param( 'root' ).'\', \''.get_param( 'path' ).'\', \''.rawurlencode( $lFile->get_rdfp_rel_path() ).'\' )' ) );
			echo action_icon( T_('Move'), 'file_move', regenerate_url( 'fm_mode,fm_sources,fm_sources_root', 'fm_mode=file_move&amp;fm_sources[]='.rawurlencode( $lFile->get_rdfp_rel_path() ).'&amp;fm_sources_root='.$fm_Filelist->_FileRoot->ID ) );
			echo action_icon( T_('Copy'), 'file_copy', regenerate_url( 'fm_mode,fm_sources,fm_sources_root', 'fm_mode=file_copy&amp;fm_sources[]='.rawurlencode( $lFile->get_rdfp_rel_path() ).'&amp;fm_sources_root='.$fm_Filelist->_FileRoot->ID ) );
			echo action_icon( T_('Delete'), 'file_delete', regenerate_url( 'fm_selected', 'action=delete&amp;fm_selected[]='.rawurlencode( $lFile->get_rdfp_rel_path() ).'&amp;'.url_crumb('file') ) );
		}
		echo '</td>';

		echo '</tr>';

		$countFiles++;
	}
	// / End of file list..


	/**
	 * @global integer Number of cols for the files table, 6 is minimum.
	 */
	$filetable_cols = 5
		+ (int)$fm_flatmode
		+ (int)$UserSettings->get('fm_showtypes')
		+ (int)($UserSettings->get('fm_showdate') != 'no')
		+ (int)$UserSettings->get('fm_showfsperms')
		+ (int)$UserSettings->get('fm_showfsowner')
		+ (int)$UserSettings->get('fm_showfsgroup')
		+ (int)$UserSettings->get('fm_imglistpreview');


	if( $countFiles == 0 )
	{ // Filelist errors or "directory is empty"
		?>

		<tr class="noresults">
			<td class="firstcol">&nbsp;</td> <?php /* blueyed> This empty column is needed so that the defaut width:100% style of the main column below makes the column go over the whole screen */ ?>
			<td class="lastcol" colspan="<?php echo $filetable_cols - 1 ?>" id="fileman_error">
				<?php
					if( ! $Messages->has_errors() )
					{ // no Filelist errors, the directory must be empty
						$Messages->clear();
						$Messages->add( T_('No files found.')
							.( $fm_Filelist->is_filtering() ? '<br />'.T_('Filter').': &laquo;'.$fm_Filelist->get_filter().'&raquo;' : '' ), 'error' );
						$Messages->display( '', '' );
					}
				?>
			</td>
		</tr>

		<?php
	}

	echo '</tbody>';

	echo '<tfoot>';

	// -------------
	// Quick upload with drag&drop button:
	// --------------
	if( $Settings->get( 'upload_enabled' ) && $current_User->check_perm( 'files', 'add', false, $fm_FileRoot ) )
	{ // Upload is enabled and we have permission to use it...
	?>
		<tr id="fileuploader_form" class="listfooter firstcol lastcol">
			<td colspan="<?php echo $filetable_cols ?>">
			<?php
			if( isset( $LinkOwner ) && $LinkOwner->check_perm( 'edit' ) )
			{ // Offer option to link the file to an Item (or anything else):
				$link_attribs = array();
				$link_action = 'link';
				if( $mode == 'upload' )
				{ // We want the action to happen in the post attachments iframe:
					$link_attribs['target'] = $iframe_name;
					$link_attribs['class'] = 'action_icon link_file';
					$link_action = 'link_inpost';
				}
				$icon_to_link_files = action_icon( T_('Link this file!'), 'link',
							regenerate_url( 'fm_selected', 'action='.$link_action.'&amp;fm_selected[]=$file_path$&amp;'.url_crumb('file') ),
							NULL, NULL, NULL, $link_attribs ).' ';
			}
			else
			{ // No icon to link files
				$icon_to_link_files = '';
			}

			$template_filerow = '<table><tr>'
				.'<td class="checkbox firstcol">&nbsp;</td>'
				.'<td class="icon_type qq-upload-image"><span class="qq-upload-spinner">&nbsp;</span></td>';
			if( $fm_flatmode )
			{
				$template_filerow .= '<td class="filepath">'.( empty( $path ) ? './' : $path ).'</td>';
			}
			$template_filerow .= '<td class="fm_filename qq-upload-file">&nbsp;</td>';
			if( $UserSettings->get('fm_showtypes') )
			{
				$template_filerow .= '<td class="type">&nbsp;</td>';
			}
			$template_filerow .= '<td class="size"><span class="qq-upload-size">&nbsp;</span><span class="qq-upload-spinner">&nbsp;</span></td>';
			if( $UserSettings->get('fm_showdate') != 'no' )
			{
				$template_filerow .= '<td class="qq-upload-status timestamp">'.TS_('Uploading...').'</td>';
			}
			if( $UserSettings->get('fm_showfsperms') )
			{
				$template_filerow .= '<td class="perms">&nbsp;</td>';
			}
			if( $UserSettings->get('fm_showfsowner') )
			{
				$template_filerow .= '<td class="fsowner">&nbsp;</td>';
			}
			if( $UserSettings->get('fm_showfsgroup') )
			{
				$template_filerow .= '<td class="fsgroup">&nbsp;</td>';
			}
			$template_filerow .= '<td class="actions lastcol">';
			if( $UserSettings->get('fm_showdate') == 'no' )
			{ // Display status in the last column if column with datetime is hidden
				$template_filerow .= '<span class="qq-upload-status">'.TS_('Uploading...').'</span> ';
			}
			$template_filerow .= '<a class="qq-upload-cancel" href="#">'.TS_('Cancel').'</a>'
				.'</td>'
			.'</tr></table>';
			// Display a button to quick upload the files by drag&drop method
			display_dragdrop_upload_button( array(
					'fileroot_ID'         => $fm_FileRoot->ID,
					'path'                => $path,
					'list_style'          => 'table',
					'template_filerow'    => $template_filerow,
					'display_support_msg' => false,
					'additional_dropzone' => '#filelist_tbody',
					'filename_before'     => $icon_to_link_files,
				) );
			?>
			</td>
		</tr>
	<?php
	}


	if( $countFiles > 0 )
	{
		// -------------
		// Footer with "check all", "with selected: ..":
		// --------------
		?>
		<tr class="listfooter firstcol lastcol file_selector">
			<td colspan="<?php echo $filetable_cols ?>">

			<?php
			echo $Form->check_all();
			$Form->add_crumb( 'file' );

			$field_options = array();

			/*
			 * TODO: fp> the following is a good idea but in case we also have $mode == 'upload' it currently doesn't refresh the list of attachements
			 * which makes it seem like this feature is broken. Please add necessary javascript for this.
			 */
			if( $fm_mode == 'link_object' && $mode != 'upload' )
			{	// We are linking to an object...
				$field_options['link'] = $LinkOwner->translate( 'Link files to current xxx' );
			}

			if( ( $fm_Filelist->get_root_type() == 'collection' || ( ! empty( $Blog )
						&& $current_User->check_perm( 'blog_post_statuses', 'edit', false, $Blog->ID ) ) )
				&& $mode != 'upload'
				&& $current_User->check_perm( 'admin', 'normal' ) )
			{ // We are browsing files for a collection:
				// User must have access to admin permission
				// fp> TODO: use current as default but let user choose into which blog he wants to post
				$field_options['make_post'] = T_('Make one post (including all images)');
				$field_options['make_posts_pre'] = T_('Make multiple posts (1 per image)');
			}

			if( $mode == 'upload' )
			{	// We are uploading in a popup opened by an edit screen
				$field_options['img_tag'] = T_('Insert IMG/link into post');
			}

			if( $edit_perm )
			{ // User can edit:
				$field_options['rename'] = T_('Rename files...');
				$field_options['delete'] = T_('Delete files...');
				// NOTE: No delete confirmation by javascript, we need to check DB integrity!
			}

			// BROKEN ?
			$field_options['download'] = T_('Download files as ZIP archive...');

			/* Not fully functional:
			$field_options['file_copy'] = T_('Copy the selected files...');
			$field_options['file_move'] = T_('Move the selected files...');

			// This is too geeky! Default perms radio options and unchecked radio groups! NO WAY!
			// If you want this feature to be usable by average users you must only have one line per file OR one file for all. You can't mix both.
			// The only way to have both is to have 2 spearate forms: 1 titled "change perms for all files simultaneously"-> submit  and another 1 title "change perms for each file individually" -> another submit
			// fplanque>> second thought: changing perms for multiple files at once is useful. BUT assigning different perms to several files with ONE form is trying to solve a problem that not even geeks can face once in a lifetime.
			// This has to be simplified to ONE single set of permissions for all selected files. (If you need different perms, click again)
			$field_options['file_perms'] = T_('Change permissions for the selected files...');
			*/

			$Form->switch_layout( 'none' );
			$Form->select_input_array( 'group_action', $action, $field_options, ' &mdash; <strong>'.T_('With selected files').'</strong>' );
			$Form->submit_input( array( 'name'=>'actionArray[group_action]', 'value'=>T_('Go!'), 'onclick'=>'return js_act_on_selected();' ) );
			$Form->switch_layout( NULL );

			/* fp> the following has been integrated into the select.
			if( $mode == 'upload' )
			{	// We are uploading in a popup opened by an edit screen
				?>
				&mdash;
				<input class="ActionButton"
					title="<?php echo T_('Insert IMG or link tags for the selected files, directly into the post text'); ?>"
					name="actionArray[img_tag]"
					value="<?php echo T_('Insert IMG/link into post') ?>"
					type="submit"
					onclick="insert_tag_for_selected_files(); return false;" />
				<?php
			}
			*/
			?>
			</td>
		</tr>
		<?php
	}
	?>
	</tfoot>
</table>
<?php
	$Form->end_form();

	if( $countFiles )
	{{{ // include JS
		// TODO: remove these javascript functions to an external .js file and include them through add_headline()
		?>
		<script type="text/javascript">
			<!--
			function js_act_on_selected()
			{
				// There may be an easier selector than below but couldn't make sense of it :(
				selected_value = jQuery('#group_action option:selected').attr('value');
				if( selected_value == 'img_tag' )
				{
					insert_tag_for_selected_files();
					return false;
				}

				// other actions:

				if ( selected_value == 'make_posts_pre' )
				{
					jQuery('#FilesForm').append('<input type="hidden" name="ctrl" value="items" />');
				}
				return true;
			}

			/**
			 * Check if files are selected.
			 *
			 * This should be used as "onclick" handler for "With selected" actions (onclick="return check_if_selected_files();").
			 * @return boolean true, if something is selected, false if not.
			 */
			function check_if_selected_files()
			{
				elems = document.getElementsByName( 'fm_selected[]' );
				var checked = 0;
				for( i = 0; i < elems.length; i++ )
				{
					if( elems[i].checked )
					{
						checked++;
					}
				}
				if( !checked )
				{
					alert( '<?php echo TS_('Nothing selected.') ?>' );
					return false;
				}
				else
				{
					return true;
				}
			}

			/**
			 * Insert IMG tags into parent window for selected files.
			 */
			function insert_tag_for_selected_files()
			{
				var elems = document.getElementsByName( 'fm_selected[]' );
				var snippet = '';
				for( i = 0; i < elems.length; i++ )
				{
					if( elems[i].checked )
					{
						id = elems[i].id.substring( elems[i].id.lastIndexOf('_')+1, elems[i].id.length );
						img_tag_info_field = document.getElementById( 'img_tag_'+id );
						snippet += img_tag_info_field.value + '\n';
					}
				}
				if( ! snippet.length )
				{
					alert( '<?php echo TS_('You must select at least one file!') ?>' );
					return false;
				}
				else
				{
					// Remove last newline from snippet:
					snippet = snippet.substring(0, snippet.length-1);
					if (! (window.focus && window.parent))
					{
						return true;
					}
					window.parent.focus();
					textarea_wrap_selection( window.parent.document.getElementById("itemform_post_content"), snippet, '', 1, window.parent.document );
					return true;
				}
			}

			// Display a message to inform user after file was linked to object
			jQuery( document ).ready( function()
			{
				jQuery( document ).on( 'click', 'a.link_file', function()
				{
					jQuery( this ).parent().append( '<div class="green"><?php echo TS_('The file has been linked.'); ?></div>' );
				} );
			} );
			// -->
		</script>
		<?php

		if( $fm_highlight )
		{ // we want to highlight a file (e.g. via "Locate this file!"), scroll there and do the success fade
			?>

			<script type="text/javascript">
			jQuery( function() {
				var fm_hl = jQuery("#fm_highlighted");
				if( fm_hl.length ) {
					jQuery.getScript('<?php echo get_require_url( '#scrollto#' ); ?>', function () {
						jQuery.scrollTo( fm_hl,
						{ onAfter: function()
							{
								evoFadeHighlight( fm_hl )
							}
						} );
					});
				}
			} );
			</script>

			<?php
		}

	}}}
?>
<!-- End of detailed file list -->