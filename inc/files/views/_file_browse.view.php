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
 * @var FileRootCache
 */
global $FileRootCache;
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

global $UserSettings;

global $fm_hide_dirtree, $create_name, $ads_list_path, $rsc_url;

global $fm_FileRoot, $path, $ajax_request;

/**
 * @var Link Owner
 */
global $LinkOwner;

global $edited_User;

global $blog;

if( isset( $edited_User ) )
{	// Display a help notice for setting a new avatar:
	printf( '<div>'.T_( 'Click on a link %s icon below to select the image to use as your profile picture.' )
				   .'</div>', get_icon( 'link', 'imgtag', array( 'class'=>'top' ) ) );
}
?>

<!-- FILE BROWSER -->

<?php
	$Widget = new Widget( 'file_browser' );

	if( ! $ajax_request && $current_User->check_perm( 'files', 'add', false, $fm_FileRoot ) )
	{
		$Widget->global_icon( /* TRANS: verb */ T_('Advanced Upload...'), '', regenerate_url( 'ctrl', 'ctrl=upload' ), /* TRANS: verb */ T_('Advanced Upload').' &raquo;', 1, 5 );
	}

	$close_link_params = array();
	if( $ajax_request )
	{ // Initialize JavaScript functions to work with modal window
		echo '<script type="text/javascript">';
		echo_modalwindow_js();
		echo '</script>';
		$close_link_params['onclick'] = 'return closeModalWindow( window.parent.document )';
	}

	global $mode, $AdminUI;

	if( $mode != 'upload' || $AdminUI->skin_name != 'bootstrap' )
	{ // Don't display a close icon, because it is already displayed on bootstrap modal window header
		if( ! empty( $LinkOwner ) )
		{ // Get an url to return to owner(post/comment) editing
			$icon_close_url = $LinkOwner->get_edit_url();
		}
		elseif( $mode == 'import' )
		{ // Get an url to return to WordPress Import page
			global $admin_url;
			$icon_close_url = $admin_url.'?ctrl=wpimportxml';
		}
		else
		{ // Unknown case, leave empty url
			$icon_close_url = '';
		}

		if( ! empty( $icon_close_url ) || ! empty( $close_link_params ) )
		{ // Display a link to close file browser
			$Widget->global_icon( T_('Close file manager'), 'close', $icon_close_url, '', 3, 2, $close_link_params );
		}
	}

	$Widget->title = T_('File browser').get_manual_link('file_browser');
	$Widget->disp_template_replaced( 'block_start' );
?>

<table id="fm_browser" cellspacing="0" cellpadding="0" class="table table-striped table-bordered table-hover table-condensed">
	<thead>
		<tr>
			<td colspan="2" id="fm_bar">
			<?php
				if( $UserSettings->get( 'fm_allowfiltering' ) != 'no' )
				{
					// Title for checkbox and its label
					$titleRegExp = format_to_output( T_('Filter is a regular expression'), 'formvalue' );

					echo '<div class="toolbaritem">';

					$Form = new Form( NULL, 'fmbar_filter_checkchanges', 'get', 'none' );
					$Form->begin_form();
					$Form->hidden_ctrl();
					$Form->hiddens_by_key( get_memorized(), array('fm_filter', 'fm_filter_regex') );
					?>

					<label for="fm_filter" class="tooltitle"><?php echo T_('Filter') ?>:</label>
					<input type="text" name="fm_filter" id="fm_filter"
						value="<?php echo format_to_output( $fm_Filelist->get_filter( false ), 'formvalue' ) ?>"
						size="7" accesskey="f" class="form-control input-sm" />

					<?php
						if( $UserSettings->get( 'fm_allowfiltering' ) == 'regexp' )
						{
							?>
							<input type="checkbox" class="checkbox" name="fm_filter_regex" id="fm_filter_regex" title="<?php echo $titleRegExp; ?>"
								value="1"<?php if( $fm_Filelist->is_filter_regexp() ) echo ' checked="checked"' ?> />
							<label for="fm_filter_regex" title="<?php echo $titleRegExp; ?>"><?php
								echo /* TRANS: short for "is regular expression" */ T_('RegExp').'</label>';
						}
					?>

					<input type="submit" name="actionArray[filter]" class="SmallButton btn btn-warning btn-sm"
						value="<?php echo format_to_output( T_('Apply'), 'formvalue' ) ?>" />

					<?php
					if( $fm_Filelist->is_filtering() )
					{ // "reset filter" form
						?>
						<button type="submit" name="actionArray[filter_unset]" value="<?php echo T_('Unset filter'); ?>"
							title="<?php echo T_('Unset filter'); ?>" class="ActionButton" style="background:none;border:none;padding:0;cursor:pointer;"><?php echo get_icon( 'delete' ) ?></button>
						<?php
					}
				$Form->end_form();

				echo '</div>';
			}
			?>

			<!-- ROOTS SELECT -->

			<?php
				$Form = new Form( NULL, 'fmbar_roots', 'post', 'none' );
				$Form->begin_form();
				// $Form->hidden_ctrl();
				$Form->hiddens_by_key( get_memorized() );

				$rootlist = $FileRootCache->get_available_FileRoots( get_param( 'root' ) );
				if( count($rootlist) > 1 )
				{ // provide list of roots to choose from
					?>
					<select name="new_root" onchange="this.form.submit();">
					<?php
					$optgroup = '';
					foreach( $rootlist as $l_FileRoot )
					{
						if( ! $current_User->check_perm( 'files', 'view', false, $l_FileRoot ) )
						{
							continue;
						}
						if( ($typegroupname = $l_FileRoot->get_typegroupname()) != $optgroup )
						{ // We're entering a new group:
							if( ! empty($optgroup) )
							{
								echo '</optgroup>';
							}
							echo '<optgroup label="'.T_($typegroupname).'">';
							$optgroup = $typegroupname;
						}
						echo '<option value="'.$l_FileRoot->ID.'"';

						if( $fm_Filelist->_FileRoot && $fm_Filelist->_FileRoot->ID == $l_FileRoot->ID )
						{
							echo ' selected="selected"';
						}

						echo '>'.format_to_output( $l_FileRoot->name )."</option>\n";
					}
					if( ! empty($optgroup) )
					{
						echo '</optgroup>';
					}
					?>
					</select>
					<script type="text/javascript">
						<!--
						// Just to have noscript tag below (which has to know what type it is not for).
						// -->
					</script>
					<noscript>
						<input class="ActionButton" type="submit" value="<?php echo T_('Change root'); ?>" />
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

				/*
				 * Flat mode
				 */
				echo ' - ';
				if( $fm_flatmode )
				{
					echo ' <a href="'.regenerate_url('fm_flatmode', 'fm_flatmode=0').'" title="'
								.T_('View one folder per page').'">'.T_('Folder mode').'</a>';
				}
				else
				{
					echo ' <a href="'.regenerate_url('fm_flatmode', 'fm_flatmode=1').'" title="'
								.T_('View all files and subfolders on a single page').'">'.T_('Flat mode').'</a>';
				}

				/*
				 * Settings:
				 */
				echo ' - <a href="'.regenerate_url('', 'action=edit_settings').'" title="'
								.T_('Edit display settings').'">'.T_('Display settings').'</a>';

				$Form->end_form();


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
				<?php echo get_icon( 'refresh' ) ?>
			</a>
		</span>

		<?php
		// Display number of directories/files:
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

					// Version with all roots displayed
					//echo get_directory_tree( NULL, NULL, $ads_list_path );

					// Version with only the current root displayed:
					echo get_directory_tree( $fm_Filelist->_FileRoot, $fm_Filelist->_FileRoot->ads_path, $ads_list_path );

					echo '</td>';
				}

				echo '<td id="fm_files">';


				// ______________________________ Files ______________________________
				require dirname(__FILE__).'/_file_list.inc.php';


				// ______________________________ Toolbar ______________________________
				echo '<div class="fileman_toolbars_bottom">';

				/*
				 * CREATE FILE/FOLDER TOOLBAR:
				 */
				if( ($Settings->get( 'fm_enable_create_dir' ) || $Settings->get( 'fm_enable_create_file' ))
							&& $current_User->check_perm( 'files', 'add', false, $fm_FileRoot ) )
				{ // dir or file creation is enabled and we're allowed to add files:
					global $create_type;

					echo '<div class="toolbaritem">';
					$Form = new Form( NULL, '', 'post', 'none' );
					$Form->begin_form();
						$Form->hidden( 'action', 'createnew' );
						$Form->add_crumb( 'file' );
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
							echo '<select name="create_type" class="form-control">';
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
						} ?>" size="15" class="form-control" />
					<input class="ActionButton btn btn-default" type="submit" value="<?php echo format_to_output( T_('Create!'), 'formvalue' ) ?>" />
					<?php
					$Form->end_form();
					echo '</div>';
				}


				/*
				 * UPLOAD:
				 */
				if( $Settings->get('upload_enabled') && $current_User->check_perm( 'files', 'add', false, $fm_FileRoot ) )
				{	// Upload is enabled and we have permission to use it...
					echo "<!-- QUICK UPLOAD: -->\n";
					echo '<div class="toolbaritem">';
					$Form = new Form( NULL, '', 'post', 'none', 'multipart/form-data' );
					$Form->begin_form();
						$Form->add_crumb( 'file' );
						$Form->hidden( 'ctrl', 'upload' );
						$Form->hidden( 'upload_quickmode', 1 );
						// The following is mainly a hint to the browser.
						$Form->hidden( 'MAX_FILE_SIZE', $Settings->get( 'upload_maxkb' )*1024 );
						$Form->hiddens_by_key( get_memorized('ctrl') );
						echo '<div>';
						echo '<span class="btn btn-default btn-file">';
						echo T_('Choose File').'<input name="uploadfile[]" type="file" size="10" />';
						echo '</span> ';
						echo '<span>'.T_('No file selected').'</span> &nbsp; ';
						echo '<input class="ActionButton btn btn-default" type="submit" value="&gt; '.T_('Quick upload!').'" />';
						echo '</div>';
					$Form->end_form();
					echo '</div>';
				}

				echo '<div class="clear"></div>';
				echo '</div>';

				echo '</td>'
			?>
		</tr>
	</tbody>
</table>
<script type="text/javascript">
if( typeof file_uploader_note_text != 'undefined' )
{
	document.write( '<p class="note center">' + file_uploader_note_text + '</p>' );
}
</script>
<?php
	$Widget->disp_template_raw( 'block_end' );

	// Print JS function to allow edit file properties on modal window
	echo_file_properties();
?>