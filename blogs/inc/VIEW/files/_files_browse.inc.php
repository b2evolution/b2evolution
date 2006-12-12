<?php
/**
 * This file implements the UI for file browsing.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
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
 * @var FileRootCache
 */
global $FileRootCache;
/**
 * @var string
 */
global $fm_mode;
/**
 * @var string
 */
global $fm_flatmode;
/**
 * @var User
 */
global $current_User;
/**
 * @var GeneralSettings
 */
global $Settings;
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

global $disp_fm_browser_toggle, $fm_hide_dirtree, $create_name;


// Begin payload block:
$this->disp_payload_begin();

echo '<div id="filemanmain">';


if( $disp_fm_browser_toggle )
{ // File browser interface can be toggled, link to hide
	echo '<span class="toolbaritem">'.action_icon( T_('Hide Filemanager'), 'close', regenerate_url( 'fm_disp_browser', 'fm_disp_browser=0' ) ).'</span>';
}
?>

<!-- FLAT MODE: -->

<?php
$Form = & new Form( NULL, 'fmbar_flatmode', 'post', 'none' );
$Form->begin_form( 'toolbaritem' );
	$Form->hidden_ctrl();
	$Form->hiddens_by_key( get_memorized('fm_flatmode') );
	if( ! $fm_flatmode )
	{
		$Form->hidden( 'fm_flatmode', 1 );
	}
	$Form->button( array( 'class' => 'ActionButton',
			'title' => ( $fm_flatmode ? T_('Normal mode') : T_('All files and folders, including subdirectories') ),
			'value' => ( $fm_flatmode ? T_('Normal mode') : T_('Flat mode') ) ) );
$Form->end_form();
?>


<!-- FILTER BOX: -->

<?php
// Title for checkbox and its label
$titleRegExp = format_to_output( T_('Filter is a regular expression'), 'formvalue' );

$Form = & new Form( NULL, 'fmbar_filter_checkchanges', 'post', 'none' );
$Form->begin_form( 'toolbaritem' );
	$Form->hidden_ctrl();
	$Form->hiddens_by_key( get_memorized(), array('fm_filter', 'fm_filter_regex') );
	?>
	<label for="fm_filter" class="tooltitle"><?php echo T_('Filter') ?>:</label>
	<input type="text" name="fm_filter" id="fm_filter"
		value="<?php echo format_to_output( $fm_Filelist->get_filter( false ), 'formvalue' ) ?>"
		size="7" accesskey="f" />

	<input type="checkbox" class="checkbox" name="fm_filter_regex" id="fm_filter_regex" title="<?php echo $titleRegExp; ?>"
		value="1"<?php if( $fm_Filelist->is_filter_regexp() ) echo ' checked="checked"' ?> />
	<label for="fm_filter_regex" title="<?php echo $titleRegExp; ?>"><?php
		echo /* TRANS: short for "is regular expression" */ T_('RegExp'); ?></label>

	<input type="submit" name="actionArray[filter]" class="ActionButton"
		value="<?php echo format_to_output( T_('Apply'), 'formvalue' ) ?>" />

	<?php
	if( $fm_Filelist->is_filtering() )
	{ // "reset filter" form
		?>
		<input type="image" name="actionArray[filter_unset]" value="<?php echo T_('Unset filter'); ?>"
			title="<?php echo T_('Unset filter'); ?>" src="<?php echo get_icon( 'delete', 'url' ) ?>" class="ActionButton" />
		<?php
	}
$Form->end_form();


/* Not implemented yet:

<!-- SEARCH BOX: -->

<form class="toolbaritem" id="fmbar_search">
	<input type="text" name="searchfor" value="--todo--" size="20" />
	<input type="image"
		class="ActionButton"
		title="<?php echo format_to_output( T_('Search'), 'formvalue' ) ?>"
		src="<glasses>" />
</form>

*/ ?>


<!-- THE MAIN FORM -->

<?php
	$Form = & new Form( NULL, 'FilesForm', 'post', 'none' );
	$Form->begin_form();
		$Form->hidden_ctrl();

		$Form->hidden( 'confirmed', '0' );
		$Form->hidden( 'md5_filelist', $fm_Filelist->md5_checksum() );
		$Form->hidden( 'md5_cwd', md5($fm_Filelist->get_ads_list_path()) );
		$Form->hiddens_by_key( get_memorized('fm_selected') ); // 'fm_selected' gets provided by the form itself
?>


<table id="fm_browser" cellspacing="0" cellpadding="0">
	<thead>
		<tr>
			<td colspan="2" id="fm_bar">
		<?php
		echo '<div id="fmbar_roots">';

		/*
		 * -----------------------------------------------
		 * Display ROOTs list:
		 * -----------------------------------------------
		 */
		$rootlist = $FileRootCache->get_available_FileRoots();
		if( count($rootlist) > 1 )
		{ // provide list of roots to choose from
			?>
			<select name="new_root" onchange="this.form.submit();">

			<?php
			foreach( $rootlist as $l_FileRoot )
			{
				echo '<option value="'.$l_FileRoot->ID.'"';

				if( $fm_Filelist->_FileRoot && $fm_Filelist->_FileRoot->ID == $l_FileRoot->ID )
				{
					echo ' selected="selected"';
				}

				echo '>'.format_to_output( $l_FileRoot->name )."</option>\n";
			}
			?>

			</select>
			<script type="text/javascript">
				<!--
				// Just to have noscript tag below (which has to know what type it is not for).
				// -->
			</script>
			<noscript>
				<input class="ActionButton" type="submit" value="'.T_('Change root').'" />
			</noscript>

			<?php
		}

		/*
		 * Display link to display directory tree:
		 */
		if( $fm_hide_dirtree )
		{
			echo ' <a href="'.regenerate_url('fm_hide_dirtree', 'fm_hide_dirtree=0').'">'.T_('Display directory tree').'</a>';
		}
		else
		{
			echo ' <a href="'.regenerate_url('fm_hide_dirtree', 'fm_hide_dirtree=1').'">'.T_('Hide directory tree').'</a>';
		}

		echo '</div> ';

		// -----------------------------------------------
		// Display table header: directory location info:
		// -----------------------------------------------
		echo '<div id="fmbar_cwd">';
		// Display current dir:
		echo T_('Current dir').': <strong class="currentdir">'.$fm_Filelist->get_cwd_clickable().'</strong>';
		echo '</div> ';

		// Display current filter:
		if( $fm_Filelist->is_filtering() )
		{
			echo '<div id="fmbar_filter">';
			echo '[<em class="filter">'.$fm_Filelist->get_filter().'</em>]';
			// TODO: maybe clicking on the filter should open a JS popup saying "Remove filter [...]? Yes|No"
			echo '</div> ';
		}


		// The hidden reload button, which gets displayed if a popup detects that the displayed files have changed
		?>
		<span style="display:none;" id="fm_reloadhint">
			<a href="<?php echo regenerate_url() ?>"
				title="<?php echo T_('A popup has discovered that the displayed content of this window is not up to date. Click to reload.'); ?>">
				<?php echo get_icon( 'reload' ) ?>
			</a>
		</span>

		<?php
		// Display filecounts:
		?>

		<div id="fmbar_filecounts" title="<?php printf( T_('%s bytes'), number_format($fm_Filelist->count_bytes()) ); ?>"> (<?php
			disp_cond( $fm_Filelist->count_dirs(), T_('One directory'), T_('%d directories'), T_('No directories') );
			echo ', ';
			disp_cond( $fm_Filelist->count_files(), T_('One file'), T_('%d files'), T_('No files' ) );
			echo ', '.bytesreadable( $fm_Filelist->count_bytes() );
			?>
			)
		</div>

			</td>
		</tr>
	</thead>

	<tbody>
		<tr>
<?php

	// ______________________________ Directory tree ______________________________
	if( ! $fm_hide_dirtree )
	{
		echo '<td id="fm_dirtree">';

		echo get_directory_tree();

		echo '</td>';
	}


	// ______________________________ Files ______________________________
?>
	<td id="fm_files">
		<table class="filelist">
			<thead>
<?php
	/*****************  Col headers  ****************/

	echo '<tr>';

	if( $UserSettings->get( 'fm_imglistpreview' ) )
	{ // Image file preview:
		echo '<th class="nowrap">'./* TRANS: Image file preview */ T_('Preview').'</th>';;
	}

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

	echo '<th>'.$fm_Filelist->get_sort_link( 'type', '' ).'</th>';
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
	echo '<th class="nowrap">'.$fm_Filelist->get_sort_link( 'lastmod', /* TRANS: file's last change / timestamp */ T_('Last change') ).'</th>';

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

			<tbody>
<?php
param( 'checkall', 'integer', 0 );  // Non-Javascript-CheckAll

/***********************************************************/
/*                    MAIN FILE LIST:                      */
/***********************************************************/
$countFiles = 0;
while( $lFile = & $fm_Filelist->get_next() )
{ // Loop through all Files:
	echo '<tr class="'.( $countFiles%2 ? 'odd' : 'even' ).'">';

	/***************** Image file preview *******************/
	if( $UserSettings->get( 'fm_imglistpreview' ) )
	{
		echo '<td class="fm_preview_list">';
		if( $lFile->is_image() )
		{
			$img = '<img src="'.$lFile->_FileRoot->ads_url.$lFile->_rdfp_rel_path.'" alt="" width="80" />';

			// Get link to view the file (fallback to no view link - just the img):
			$link = $lFile->get_view_link( $img );
			if( ! $link )
			{ // no view link available:
				$link = $img;
			}

			echo $link;
		}
		echo '</td>';
	}


	/********************    Checkbox:    *******************/

	echo '<td class="checkbox firstcol">';
	echo '<span name="surround_check" class="checkbox_surround_init">';
	echo '<input title="'.T_('Select this file').'" type="checkbox" class="checkbox"
				name="fm_selected[]" value="'.rawurlencode($lFile->get_rdfp_rel_path()).'" id="cb_filename_'.$countFiles.'"';
	global $checkall;
	if( $checkall || $selected_Filelist->contains( $lFile ) )
	{
		echo ' checked="checked"';
	}
	echo ' />';
	echo '</span>';

	/***********  Hidden info used by Javascript:  ***********/

	global $mode;
	if( $mode == 'upload' )
	{	// This mode allows to insert img tags into the post...
		// Hidden info used by Javascript:
		echo '<input type="hidden" name="img_tag_'.$countFiles.'" id="img_tag_'.$countFiles
		    .'" value="'.format_to_output( $lFile->get_tag(), 'formvalue' ).'" />';
	}

	echo '</td>';


	/********************  File type Icon:  *******************/

	echo '<td class="icon">';
	if( $lFile->is_dir() )
	{ // Directory
		echo '<a href="'.$lFile->get_view_url().'" title="'.T_('Change into this directory').'">'.$lFile->get_icon().'</a>';
	}
	else
	{ // File
		if( $view_link = $lFile->get_view_link( $lFile->get_icon(), NULL, NULL ) )
		{
			echo $view_link;
		}
		else
		{ // File extension unrecognized
			echo $lFile->get_icon();
		}
	}
	echo '</td>';

	/*******************  Path (flatmode): ******************/

	if( $fm_flatmode )
	{
		echo '<td class="filepath">';
		echo dirname($lFile->get_rdfs_rel_path()).'/';
		echo '</td>';
	}


	echo '<td class="fm_filename">';

	/*************  Invalid filename warning:  *************/

	if( !$lFile->is_dir() )
	{
		if( $error_filename = validate_filename( $lFile->get_name() ) )
		{ // TODO: Warning icon with hint
			echo get_icon( 'warning', 'imgtag', array( 'class' => 'filenameIcon', 'title' => $error_filename ) );
		}
	}
	elseif( $error_dirname = validate_dirname( $lFile->get_name() ) )
	{ // TODO: Warning icon with hint
		echo get_icon( 'warning', 'imgtag', array( 'class' => 'filenameIcon', 'title' => $error_dirname ) );
	}

	/****  Open in a new window  (only directories)  ****/

	if( $lFile->is_dir() )
	{ // Directory
		$browse_dir_url = $lFile->get_view_url();
		$target = 'evo_fm_'.$lFile->get_md5_ID();

		echo '<a href="'.$browse_dir_url.'" target="'.$target.' " class="filenameIcon"
					title="'.T_('Open in a new window').'" onclick="'

					."pop_up_window( '$browse_dir_url', '$target', '"
					.'width=800,height=800,'
					."scrollbars=yes,status=yes,resizable=yes' ); return false;"

					.'">'.get_icon( 'window_new' ).'</a>';
	}

	/***************  Link ("chain") icon:  **************/

	if( strpos( $fm_mode, 'link_' ) === 0 )
	{	// Offer option to link the file to an Item (or anything else):
		echo action_icon( T_('Link this file!'), 'link', regenerate_url( 'fm_selected', 'action=link&amp;fm_selected[]='.rawurlencode($lFile->get_rdfp_rel_path()) ) );
		echo ' ';
	}

	/********************  Filename  ********************/

	if( $lFile->is_dir() )
	{ // Directory
		// Link to open the directory in the curent window
		echo '<a href="'.$browse_dir_url.'">'.$lFile->get_name().'</a>';
	}
	else
	{ // File
		if( $view_link = $lFile->get_view_link( $lFile->get_name(), NULL, NULL ) )
		{
			echo $view_link;
		}
		else
		{ // File extension unrecognized
			echo $lFile->get_name();
		}
	}

	/***************  File meta data:  **************/

	echo '<span class="filemeta">';
	// Optionally display IMAGE pixel size:
	if( $UserSettings->get( 'fm_getimagesizes' ) )
	{
		echo ' ('.$lFile->get_image_size( 'widthxheight' ).')';
	}
	// Optionnaly display meta data title:
	if( $lFile->meta == 'loaded' )
	{	// We have loaded meta data for this file:
		echo ' - '.$lFile->title;
	}
	echo '</span>';

	/*
	 * Directory in flat mode:
	 *
	if( $fm_flatmode && $fm_Filelist->get_sort_order() == 'name' )
	{
		?>
		<div class="path" title="<?php echo T_('The directory of the file') ?>"><?php
		$subPath = $lFile->get_rdfs_rel_path();
		if( empty( $subPath ) )
		{
			$subPath = './';
		}
		echo $subPath;
		?>
		</div>
		<?php
	}
	*/
	echo '</td>';

	/*******************  File type  ******************/

	if( $UserSettings->get('fm_showtypes') )
	{ // Show file types
		echo '<td class="type">'.$lFile->get_type().'</td>';
	}

	/*******************  File size  ******************/

	echo '<td class="size">'.$lFile->get_size_formatted().'</td>';

	/****************  File time stamp  ***************/

	echo '<td class="timestamp">';
	echo '<span class="date">'.$lFile->get_lastmod_formatted( 'date' ).'</span> ';
	echo '<span class="time">'.$lFile->get_lastmod_formatted( 'time' ).'</span>';
	echo '</td>';

	/****************  File pemissions  ***************/

	if( $UserSettings->get('fm_showfsperms') )
	{ // Show file perms
		echo '<td class="perms">';
		$fm_permlikelsl = $UserSettings->param_Request( 'fm_permlikelsl', 'fm_permlikelsl', 'integer', 0 );

		if( $current_User->check_perm( 'files', 'edit' ) )
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

	echo '<td class="actions lastcol">';

	if( $current_User->check_perm( 'files', 'edit' ) )
	{ // User can edit:
		if( $lFile->is_editable( $current_User->check_perm( 'files', 'all' ) ) )
		{
			echo action_icon( T_('Edit file...'), 'edit', regenerate_url( 'fm_selected', 'action=edit&amp;fm_selected[]='.rawurlencode($lFile->get_rdfp_rel_path()) ) );
		}
		else
		{
			echo get_icon( 'no_edit', 'imgtag', array( 'class' => 'action_icon' ) );
		}
	}

	echo action_icon( T_('Edit properties...'), 'properties', regenerate_url( 'fm_selected', 'action=edit_properties&amp;fm_selected[]='.rawurlencode($lFile->get_rdfp_rel_path()) ) );

	if( $current_User->check_perm( 'files', 'edit' ) )
	{ // User can edit:
		echo action_icon( T_('Rename'), 'file_rename', regenerate_url( 'fm_selected', 'action=rename&amp;fm_selected[]='.rawurlencode($lFile->get_rdfp_rel_path()) ) );
		echo action_icon( T_('Move'), 'file_move', regenerate_url( 'fm_mode,fm_sources,fm_sources_root', 'fm_mode=file_move&amp;fm_sources[]='.rawurlencode( $lFile->get_rdfp_rel_path() ).'&amp;fm_sources_root='.$fm_Filelist->_FileRoot->ID ) );
		echo action_icon( T_('Copy'), 'file_copy', regenerate_url( 'fm_mode,fm_sources,fm_sources_root', 'fm_mode=file_copy&amp;fm_sources[]='.rawurlencode( $lFile->get_rdfp_rel_path() ).'&amp;fm_sources_root='.$fm_Filelist->_FileRoot->ID ) );
		echo action_icon( T_('Delete'), 'file_delete', regenerate_url( 'fm_selected', 'action=delete&amp;fm_selected[]='.rawurlencode( $lFile->get_rdfp_rel_path() ) ) );
	}
	echo '</td>';

	echo '</tr>';

	$countFiles++;
}
// / End of file list..


/**
 * @global integer Number of cols for the files table, 6 is minimum.
 */
$filetable_cols = 6
	+ (int)$fm_flatmode
	+ (int)$UserSettings->get('fm_showtypes')
	+ (int)$UserSettings->get('fm_showfsperms')
	+ (int)$UserSettings->get('fm_showfsowner')
	+ (int)$UserSettings->get('fm_showfsgroup')
	+ (int)$UserSettings->get('fm_imglistpreview');


if( $countFiles == 0 )
{ // Filelist errors or "directory is empty"
	?>

	<tr>
		<td class="firstcol">&nbsp;</td> <?php /* blueyed> This empty column is needed so that the defaut width:100% style of the main column below makes the column go over the whole screen */ ?>
		<td class="lastcol" colspan="<?php echo $filetable_cols - 1 ?>" id="fileman_error">
			<?php
				if( ! $Messages->count('fl_error') )
				{ // no Filelist errors, the directory must be empty
					$Messages->add( T_('No files found.')
						.( $fm_Filelist->is_filtering() ? '<br />'.T_('Filter').': &laquo;'.$fm_Filelist->get_filter().'&raquo;' : '' ), 'fl_error' );
				}
				$Messages->display( '', '', true, 'fl_error', 'log_error' );
			?>
		</td>
	</tr>

	<?php
}
else
{
	// -------------
	// Footer with "check all", "with selected: ..":
	// --------------
	?>
	<tr class="listfooter firstcol lastcol">
		<td colspan="<?php echo $filetable_cols ?>">

		<?php	echo $Form->check_all(); ?>
			&mdash; <strong><?php echo T_('With selected files:') ?> </strong>

		<?php
		if( $mode == 'upload' )
		{	// We are uploading in a popup opened by an edit screen
			?>
			<input class="ActionButton"
				title="<?php echo T_('Insert IMG tags for selected files'); ?>"
				name="actionArray[img_tag]"
				value="img"
				type="submit"
				onclick="insert_tag_for_selected_files(); return false;" />
			<?php
		}


		if( $current_User->check_perm( 'files', 'edit' ) )
		{ // User can edit:
			?>
			<input class="ActionButton" type="image" name="actionArray[rename]"
				title="<?php echo T_('Rename the selected files'); ?>"
				src="<?php echo get_icon( 'file_rename', 'url' ); ?>"
				onclick="return check_if_selected_files();" />

			<input class="DeleteButton" type="image" name="actionArray[delete]"
				title="<?php echo T_('Delete the selected files') ?>"
				src="<?php echo get_icon( 'file_delete', 'url' ) ?>"
				onclick="return check_if_selected_files();" />
			<?php
			// NOTE: No delete confirmation by javascript, we need to check DB integrity!

		}
		?>

		<input class="ActionButton"
			title="<?php echo T_('Download the selected files as archive') ?>"
			name="actionArray[download]"
			value="download"
			type="image"
			src="<?php echo get_icon( 'download', 'url' ) ?>"
			onclick="return check_if_selected_files();" />

			<!-- Not implemented yet: input class="ActionButton" type="submit"
				name="actionArray[sendbymail]" value="<?php echo T_('Send by mail') ?>" onclick="return check_if_selected_files();" / -->

		<?php
		/* Not fully functional:
		<input class="ActionButton" type="image" name="actionArray[file_copy]"
			title="<?php echo T_('Copy the selected files'); ?>"
			onclick="return check_if_selected_files();"
			src="<?php echo get_icon( 'file_copy', 'url' ); ?>" />

		<input class="ActionButton" type="image" name="actionArray[file_move]"
			title="<?php echo T_('Move the selected files'); ?>"
			onclick="return check_if_selected_files();"
			src="<?php echo get_icon( 'file_move', 'url' ); ?>" />
// This is too geeky! Default perms radio options and unchecked radio groups! NO WAY!
// If you want this feature to be usable by average users you must only have one line per file OR one file for all. You can't mix both.
// The only way to have both is to have 2 spearate forms: 1 titled "change perms for all files simultaneously"-> submit  and another 1 title "change perms for each file individually" -> another submit
// POST PHOENIX
// fplanque>> second thought: changing perms for multiple files at once is useful. BUT assigning different perms to several files with ONE form is trying to solve a problem that not even geeks can face once in a lifetime.
// This has to be simplified to ONE single set of permissions for all selected files. (If you need different perms, click again)
			<input class="ActionButton" type="image" name="actionArray[edit_perms]"
			onclick="return check_if_selected_files();"
			title="<?php echo T_('Change permissions for the selected files'); ?>"
			src="<?php echo get_icon( 'file_perms', 'url' ); ?>" />
	*/ ?>

		</td>
	</tr>
	<?php
}
?>
					</tbody>
				</table>
				<!-- End of detailed file list -->

			</td>
		</tr>
	</tbody>
</table>

<?php $Form->end_form() ?>


<div id="fileman_toolbars_bottom">

<?php
if( $countFiles )
{{{ // include JS
	// TODO: remove these javascript functions to an external .js file and include them through $this->add_headline()
	?>
	<script type="text/javascript">
		<!--
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
		 * Insert IMG tags into parent window for selected files:
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
					snippet += img_tag_info_field.value + ' ';
				}
			}
			if( ! snippet.length )
			{
				alert( '<?php echo TS_('You must select at least one file!') ?>' );
				return false;
			}
			else
			{
				if (! (window.focus && window.opener))
				{
					return true;
				}
				window.opener.focus();
				textarea_replace_selection( window.opener.document.getElementById("itemform_post_content"), snippet, window.opener.document );
				return true;
			}
		}
		// -->
	</script>
	<?php
}}}


/*
 * CREATE TOOLBAR:
 */
if( ($Settings->get( 'fm_enable_create_dir' ) || $Settings->get( 'fm_enable_create_file' ))
			&& $current_User->check_perm( 'files', 'add' ) )
{ // dir or file creation is enabled and we're allowed to add files:
	global $create_type;

	$Form = & new Form( NULL, 'fmbar_create_checkchanges', 'post', 'none' );
	$Form->begin_form( 'toolbaritem' );
		$Form->hidden( 'action', 'createnew' );
		$Form->hidden_ctrl();
		$Form->hiddens_by_key( get_memorized() );
		if( ! $Settings->get( 'fm_enable_create_dir' ) )
		{	// We can create files only:
			echo '<label for="fm_createname" class="tooltitle">'.T_('New file:').'</label>';
			echo '<input type="hidden" name="create_type" value="file" />';
		}
		elseif( ! $Settings->get( 'fm_enable_create_file' ) )
		{	// We can create directories only:
			echo '<label for="fm_createname" class="tooltitle">'.T_('New folder:').'</label>';
			echo '<input type="hidden" name="create_type" value="dir" />';
		}
		else
		{	// We can create both files and directories:
			echo T_('New').': ';
			echo '<select name="create_type">';
			echo '<option value="dir"';
			if( isset($create_type) &&  $create_type == 'dir' )
			{
				echo ' selected="selected"';
			}
			echo '>'.T_('folder').'</option>';

			echo '<option value="file"';
			if( isset($create_type) && $create_type == 'file' )
			{
				echo ' selected="selected"';
			}
			echo '>'.T_('file').'</option>';
			echo '</select>:';
		}
	?>
	<input type="text" name="create_name" id="fm_createname" value="<?php
		if( isset( $create_name ) )
		{
			echo $create_name;
		} ?>" size="15" />
	<input class="ActionButton" type="submit" value="<?php echo format_to_output( T_('Create!'), 'formvalue' ) ?>" />
	<?php
	$Form->end_form();
}


/*
 * UPLOAD:
 */
if( $Settings->get('upload_enabled') && $current_User->check_perm( 'files', 'add' ) && $fm_mode != 'file_upload' )
{	// Upload is enabled and we have permission to use it...
	echo "<!-- UPLOAD: -->\n";
	$Form = & new Form( NULL, 'fmbar_adv_upload', 'post', 'none' );
	$Form->begin_form( 'toolbaritem' );
		$Form->hidden_ctrl();
		echo '<div>';
		$Form->hiddens_by_key( get_memorized('fm_mode') );
		$Form->hidden( 'fm_mode', 'file_upload' );
		echo '<input class="ActionButton" type="submit" value="'.T_('Advanced upload...').'" />';
		echo '</div>';
	$Form->end_form();

	$Form = & new Form( NULL, 'fmbar_quick_upload', 'post', 'none', 'multipart/form-data' );
	$Form->begin_form( 'toolbaritem' );
		$Form->hidden_ctrl();
		$Form->hidden( 'upload_quickmode', 1 );
		// The following is mainly a hint to the browser.
		$Form->hidden( 'MAX_FILE_SIZE', $Settings->get( 'upload_maxkb' )*1024 );
		$Form->hiddens_by_key( get_memorized('fm_mode') );
		$Form->hidden( 'fm_mode', 'file_upload' );
		echo '<div>';
		echo '<input name="uploadfile[]" type="file" size="10" />';
		echo '<input class="ActionButton" type="submit" value="'.T_('Quick Upload!').'" />';
		echo '</div>';
	$Form->end_form();
}
?>

</div>

<div class="clear"></div>

<?php


// ------------------
// Display options:
// Let's keep these at the end since they won't be accessed more than occasionaly
// and only by advanced users
// ------------------
param( 'options_show', 'integer', 0 );

$Form = & new Form( NULL, 'fm_options_checkchanges', 'get', 'none' );
	$Form->label_to_the_left = false;
	$Form->label_suffix = '';
	$Form->fieldend = '<br />';

	$Form->begin_form( 'fform' );
		$Form->hidden_ctrl();
		$Form->hidden( 'options_show', 1 );
		$Form->hiddens_by_key( get_memorized() );

		// Link to toggle the display of the form
		global $options_show;
		$toggle_link = '['.get_link_showhide( 'options_toggle', 'options_list', T_('Hide menu'), T_('Show menu'), !$options_show ).']';

		$Form->begin_fieldset( T_('Options').$toggle_link );

		echo '<div id="options_list" ';
		if( !$options_show ) echo ' style="display:none"';
		echo '>';
			$Form->checkbox( 'option_dirsattop', !$UserSettings->get('fm_dirsnotattop'), T_('Sort directories at top') );
			$Form->checkbox( 'option_showtypes', $UserSettings->get('fm_showtypes'), T_('Show file types') );
			$Form->checkbox( 'option_imglistpreview', $UserSettings->get('fm_imglistpreview'), T_('Display thumbnails for image files') );
			$Form->checkbox( 'option_getimagesizes', $UserSettings->get('fm_getimagesizes'), T_('Display the image size of image files') );
			$Form->checkbox( 'option_showfsperms', $UserSettings->get('fm_showfsperms'), T_('Show file perms') );
			$Form->checkbox( 'option_showfsowner', $UserSettings->get('fm_showfsowner'), T_('Show file owners') );
			$Form->checkbox( 'option_showfsgroup', $UserSettings->get('fm_showfsgroup'), T_('Show file groups') );
			$Form->checkbox( 'option_showhidden', $UserSettings->get('fm_showhidden'), T_('Show hidden files') );
			$Form->checkbox( 'option_permlikelsl', $UserSettings->get('fm_permlikelsl'), T_('Display file permissions like "rwxr-xr-x" rather than short form') );
			$Form->checkbox( 'option_recursivedirsize', $UserSettings->get('fm_recursivedirsize'), T_('Recursive size of directories') );
			$Form->checkbox( 'option_forceFM', $UserSettings->get('fm_forceFM'), T_('Always show the Filemanager'), 'Display the Filemanager also in modes like upload.' );

			$Form->submit( array('actionArray[update_settings]', T_('Update !'), 'ActionButton') );
		echo '</div>';

		$Form->end_fieldset();
	$Form->end_form();
?>

</div>

<?php
// End payload block:
$this->disp_payload_end();

/*
 * {{{ Revision log:
 * $Log$
 * Revision 1.26  2006/12/12 18:04:53  fplanque
 * fixed item links
 *
 * Revision 1.25  2006/12/07 20:03:32  fplanque
 * Woohoo! File editing... means all skin editing.
 *
 * Revision 1.24  2006/12/07 15:23:42  fplanque
 * filemanager enhanced, refactored, extended to skins directory
 *
 * Revision 1.23  2006/11/24 18:27:25  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.22  2006/09/14 21:11:57  blueyed
 * HTML fix
 *
 * Revision 1.21  2006/09/10 15:27:53  blueyed
 * doc
 *
 * Revision 1.20  2006/09/10 14:50:48  fplanque
 * minor / doc
 *
 * Revision 1.19  2006/09/08 18:52:56  blueyed
 * Link image preview to File::get_view_link()
 *
 * Revision 1.18  2006/07/28 18:27:10  blueyed
 * Basic image preview for image files in the file list
 *
 * Revision 1.17  2006/07/28 17:30:30  blueyed
 * Refer to itemform_post_content field by ID, as its form has no name anymore
 *
 * Revision 1.16  2006/07/17 01:53:12  blueyed
 * added param to UserSettings::param_Request
 *
 * Revision 1.15  2006/06/13 21:49:15  blueyed
 * Merged from 1.8 branch
 *
 * Revision 1.14.2.1  2006/06/12 20:00:39  fplanque
 * one too many massive syncs...
 *
 * Revision 1.14  2006/04/27 21:25:43  blueyed
 * Do not use own Log object for Filelist (revert)
 *
 * Revision 1.13  2006/04/19 22:54:48  blueyed
 * Use own Log object for Filelist
 *
 * Revision 1.12  2006/04/19 20:13:51  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.11  2006/04/14 19:34:40  fplanque
 * folder tree reorganization
 *
 * Revision 1.10  2006/04/12 19:16:52  fplanque
 * Integrated dirtree in filemanager
 *
 * Revision 1.9  2006/03/29 23:24:01  blueyed
 * Fixed linking of files.
 *
 * Revision 1.8  2006/03/26 20:42:48  blueyed
 * Show Filelist dirtree by default and save it into UserSettings
 *
 * Revision 1.7  2006/03/26 13:44:51  blueyed
 * Sort filelist after creating files/dirs; display $create_name in input box again;
 *
 * Revision 1.6  2006/03/26 02:37:57  blueyed
 * Directory tree next to files list.
 *
 * Revision 1.5  2006/03/13 21:20:53  blueyed
 * fixed UserSettings::param_Request()
 *
 * Revision 1.4  2006/03/13 20:55:26  blueyed
 * Moved Request::param_UserSettings() to UserSettings::param_Request()
 *
 * Revision 1.3  2006/03/12 23:09:01  fplanque
 * doc cleanup
 *
 * Revision 1.2  2006/03/12 03:03:33  blueyed
 * Fixed and cleaned up "filemanager".
 *
 * Revision 1.1  2006/02/23 21:12:17  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.81  2006/02/13 22:03:10  blueyed
 * Fixed conflict with general .filename class.
 *
 * Revision 1.80  2006/02/13 21:40:30  fplanque
 * fixed memorizing of the mode when uploading/inserting IMGs into posts.
 *
 * Revision 1.79  2006/02/13 20:20:09  fplanque
 * minor / cleanup
 *
 * Revision 1.78  2006/02/13 01:05:20  blueyed
 * Fix IDs to the item textarea.
 *
 * Revision 1.76  2006/02/11 21:19:29  fplanque
 * added bozo validator to FM
 *
 * Revision 1.75  2006/02/06 20:05:30  fplanque
 * minor
 *
 * Revision 1.74  2006/02/03 21:58:04  fplanque
 * Too many merges, too little time. I can hardly keep up. I'll try to check/debug/fine tune next week...
 *
 * Revision 1.73  2006/01/20 16:45:43  blueyed
 * whitespace
 *
 * Revision 1.72  2006/01/20 00:39:17  blueyed
 * Refactorisation/enhancements to filemanager.
 *
 * Revision 1.71  2006/01/11 22:09:29  blueyed
 * Reactive "download selected files as zip", also as a "workaround" to always have an icon next to "With selected files:".. ;)
 *
 * Revision 1.70  2006/01/09 22:00:32  blueyed
 * Fixed colspan for first row to be as wide as the number of chosen columns.
 *
 * Revision 1.69  2005/12/30 20:13:39  fplanque
 * UI changes mostly (need to double check sync)
 *
 * Revision 1.68  2005/12/19 22:48:45  blueyed
 * doc
 *
 * Revision 1.67  2005/12/19 16:42:03  fplanque
 * minor
 *
 * Revision 1.66  2005/12/19 04:36:16  blueyed
 * Fix using textarea_replace_selection() for IE from a popup.
 *
 * Revision 1.65  2005/12/16 16:59:11  blueyed
 * (Optional) File owner and group columns in Filemanager.
 *
 * Revision 1.64  2005/12/16 15:03:04  blueyed
 * tooltitle class for "Create new.." labels
 *
 * Revision 1.63  2005/12/16 14:58:24  blueyed
 * Valid target for popup link, fix label for filterString, labels for "Create new .."
 *
 * Revision 1.62  2005/12/16 14:02:50  blueyed
 * Killed "open selected files in popup" feature. Renamed openselected() to check_if_selected_files() (what has been a sub-feature of openselected())
 *
 * Revision 1.61  2005/12/14 19:36:15  fplanque
 * Enhanced file management
 *
 * Revision 1.60  2005/12/12 19:21:20  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.59  2005/12/10 03:02:49  blueyed
 * Quick upload mode merged from post-phoenix
 *
 * Revision 1.58  2005/11/27 06:13:52  blueyed
 * Moved textarea_replace_selection() to functions.js to allow using it everywhere.
 *
 * Revision 1.57  2005/11/25 14:33:35  fplanque
 * no message
 *
 * Revision 1.56  2005/11/24 20:29:01  blueyed
 * minor changes (fixes in commented out code); allow mass-editing of file perms (again)
 *
 * Revision 1.55  2005/11/24 18:33:04  blueyed
 * Konqueror (Safari?) and Firefox fixes
 *
 * Revision 1.54  2005/11/24 08:53:59  blueyed
 * file_cmr is deprecated
 *
 * Revision 1.53  2005/11/22 04:41:38  blueyed
 * Fix permissions editing again
 *
 * Revision 1.52  2005/11/21 18:33:19  fplanque
 * Too many undiscussed changes all around: Massive rollback! :((
 * As said before, I am only taking CLEARLY labelled bugfixes.
 *
 * Revision 1.45  2005/11/14 18:08:14  blueyed
 * Fix fatal error when displaying paths in flat mode.
 *
 * Revision 1.43.2.1  2005/11/14 17:57:18  blueyed
 * The options bug was another bug (actionArray)
 *
 * Revision 1.43  2005/11/06 11:22:10  yabs
 * correcting options bug
 *
 * Revision 1.42  2005/10/30 03:51:24  blueyed
 * Refactored showhide-JS functionality.
 * Moved showhide() from the features form to functions.js, and renamed to toggle_display_by_id();
 * Moved web_help_link() to get_web_help_link() in _misc.funcs.php, doc
 *
 * Revision 1.41  2005/10/28 20:08:46  blueyed
 * Normalized AdminUI
 *
 * Revision 1.40  2005/09/26 23:06:53  blueyed
 * Converted options fieldset to Form class
 *
 * Revision 1.39  2005/09/06 17:13:53  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.38  2005/08/12 17:31:06  fplanque
 * minor
 *
 * Revision 1.37  2005/08/08 18:30:48  fplanque
 * allow inserting of files as IMG or A HREFs from the filemanager
 *
 * Revision 1.36  2005/06/22 17:23:23  blueyed
 * html fix: closing <ul>
 *
 * Revision 1.35  2005/05/26 19:11:09  fplanque
 * no message
 *
 * Revision 1.34  2005/05/17 19:26:05  fplanque
 * FM: copy / move debugging
 *
 * Revision 1.33  2005/05/13 16:49:17  fplanque
 * Finished handling of multiple roots in storing file data.
 * Also removed many full paths passed through URL requests.
 * No full path should ever be seen by the user (only the admins).
 *
 * Revision 1.32  2005/05/12 18:39:24  fplanque
 * storing multi homed/relative pathnames for file meta data
 *
 * Revision 1.31  2005/05/11 15:58:30  fplanque
 * cleanup
 *
 * Revision 1.30  2005/05/09 16:09:37  fplanque
 * implemented file manager permissions through Groups
 *
 * Revision 1.29  2005/05/06 20:04:33  fplanque
 * added contribs
 * fixed filemanager settings
 *
 * Revision 1.28  2005/05/04 19:40:40  fplanque
 * cleaned up file settings a little bit
 *
 * Revision 1.27  2005/04/29 18:49:32  fplanque
 * Normalizing, doc, cleanup
 *
 * Revision 1.26  2005/04/28 20:44:18  fplanque
 * normalizing, doc
 *
 * Revision 1.25  2005/04/27 19:05:44  fplanque
 * normalizing, cleanup, documentaion
 *
 * Revision 1.22  2005/04/19 16:23:00  fplanque
 * cleanup
 * added FileCache
 * improved meta data handling
 *
 * Revision 1.20  2005/04/15 18:02:58  fplanque
 * finished implementation of properties/meta data editor
 * started implementation of files to items linking
 *
 * Revision 1.19  2005/04/14 19:57:52  fplanque
 * filemanager refactoring & cleanup
 * started implementation of properties/meta data editor
 * note: the whole fm_mode thing is not really desireable...
 *
 * Revision 1.18  2005/04/14 18:34:03  fplanque
 * filemanager refactoring
 *
 * Revision 1.17  2005/04/13 18:31:26  fplanque
 * tried to make copy/move/rename work ...
 *
 * Revision 1.16  2005/04/12 19:00:22  fplanque
 * File manager cosmetics
 *
 * Revision 1.14  2005/03/16 19:58:13  fplanque
 * small AdminUI cleanup tasks
 *
 * Revision 1.13  2005/03/04 18:40:26  fplanque
 * added Payload display wrappers to admin skin object
 *
 * Revision 1.12  2005/02/28 09:06:37  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.11  2005/02/27 20:34:48  blueyed
 * Admin UI refactoring
 *
 * Revision 1.10  2005/02/21 00:34:36  blueyed
 * check for defined DB_USER!
 *
 * Revision 1.9  2005/02/08 01:01:39  blueyed
 * moved searchbox, beautified create bar.
 *
 * Revision 1.8  2005/01/27 21:56:07  blueyed
 * layout..
 *
 * Revision 1.7  2005/01/27 20:07:51  blueyed
 * rolled layout back somehow..
 *
 * Revision 1.6  2005/01/27 13:34:56  fplanque
 * i18n tuning
 *
 * Revision 1.4  2005/01/26 17:55:23  blueyed
 * catching up..
 *
 * Revision 1.3  2005/01/25 18:07:42  fplanque
 * CSS/style cleanup
 *
 * Revision 1.2  2005/01/15 20:32:14  blueyed
 * small fix, warning icon
 *
 * Revision 1.1  2005/01/12 17:55:51  fplanque
 * extracted browsing interface into separate file to make code more readable
 * }}}
 */
?>