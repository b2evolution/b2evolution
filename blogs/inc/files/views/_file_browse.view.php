<?php
/**
 * This file implements the UI for file browsing.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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

global $fm_FileRoot, $path;

/**
 * @var Item
 */
global $edited_Item;
?>

<!-- FILE BROWSER -->

<?php
	$Widget = & new Widget( 'file_browser' );
	$Widget->global_icon( T_('Upload...'), '', regenerate_url( 'ctrl', 'ctrl=upload' ), T_('Upload').' &raquo;', 1, 5 );

	if( !empty($edited_Item) )
	{ // Return to post editing:
		$Widget->global_icon( T_('Close file manager'), 'close', '?ctrl=items&amp;p='.$edited_Item->ID );
	}

	$Widget->title = T_('File browser').get_manual_link('file_browser');
	$Widget->disp_template_replaced( 'block_start' );
?>

<table id="fm_browser" cellspacing="0" cellpadding="0">
	<thead>
		<tr>
			<td colspan="2" id="fm_bar">
			<?php
				if( $UserSettings->get( 'fm_allowfiltering' ) != 'no' )
				{
					// Title for checkbox and its label
					$titleRegExp = format_to_output( T_('Filter is a regular expression'), 'formvalue' );

					echo '<div class="toolbaritem">';

					$Form = & new Form( NULL, 'fmbar_filter_checkchanges', 'get', 'none' );
					$Form->begin_form();
					$Form->hidden_ctrl();
					$Form->hiddens_by_key( get_memorized(), array('fm_filter', 'fm_filter_regex') );
					?>

					<label for="fm_filter" class="tooltitle"><?php echo T_('Filter') ?>:</label>
					<input type="text" name="fm_filter" id="fm_filter"
						value="<?php echo format_to_output( $fm_Filelist->get_filter( false ), 'formvalue' ) ?>"
						size="7" accesskey="f" />

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

					<input type="submit" name="actionArray[filter]" class="SmallButton"
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

				echo '</div>';
			}
			?>

			<!-- ROOTS SELECT -->

			<?php
				$Form = & new Form( NULL, 'fmbar_roots', 'post', 'none' );
				$Form->begin_form();
				// $Form->hidden_ctrl();
				$Form->hiddens_by_key( get_memorized() );

				$rootlist = $FileRootCache->get_available_FileRoots();
				if( count($rootlist) > 1 )
				{ // provide list of roots to choose from
					?>
					<select name="new_root" onchange="this.form.submit();">
					<?php
					$optgroup = '';
					foreach( $rootlist as $l_FileRoot )
					{
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
			$count_dirs = $fm_Filelist->count_dirs();
			if( $count_dirs )
			{
				if( $count_dirs > 1 )
					printf( T_('%d directories'), $count_dirs );
				else
					echo T_('One directory');
			}
			else
			{
				echo T_('No directories');
			}
			echo ', ';
			$count_files = $fm_Filelist->count_files();
			if( $count_files )
			{
				if( $count_files > 1 )
					printf( T_('%d files'), $count_files );
				else
					echo T_('One file');
			}
			else
			{
				echo T_('No files');
			}
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

				// ______________________________ Toolbars ______________________________
				echo '<div id="fileman_toolbars_bottom">';

				/*
				 * CREATE FILE/FOLDER TOOLBAR:
				 */
				if( ($Settings->get( 'fm_enable_create_dir' ) || $Settings->get( 'fm_enable_create_file' ))
							&& $current_User->check_perm( 'files', 'add' ) )
				{ // dir or file creation is enabled and we're allowed to add files:
					global $create_type;

					echo '<div class="toolbaritem">';
					$Form = & new Form( NULL, 'fmbar_create_checkchanges', 'post', 'none' );
					$Form->begin_form();
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
					echo '</div>';
				}


				/*
				 * UPLOAD:
				 */
				if( $Settings->get('upload_enabled') && $current_User->check_perm( 'files', 'add' ) )
				{	// Upload is enabled and we have permission to use it...
					echo "<!-- QUICK UPLOAD: -->\n";
					echo '<div class="toolbaritem">';
					$Form = & new Form( NULL, 'fmbar_quick_upload', 'post', 'none', 'multipart/form-data' );
					$Form->begin_form();
						$Form->hidden( 'ctrl', 'upload' );
						$Form->hidden( 'upload_quickmode', 1 );
						// The following is mainly a hint to the browser.
						$Form->hidden( 'MAX_FILE_SIZE', $Settings->get( 'upload_maxkb' )*1024 );
						$Form->hiddens_by_key( get_memorized('ctrl') );
						echo '<div>';
						echo '<input name="uploadfile[]" type="file" size="10" />';
						echo '<input class="ActionButton" type="submit" value="&gt; '.T_('Quick upload!').'" />';
						echo '</div>';
					$Form->end_form();
					echo '</div>';
				}

				echo '</div>';
				echo '<div class="clear"></div>';

				echo '</td>'
			?>
		</tr>
	</tbody>
</table>

<?php
	$Widget->disp_template_raw( 'block_end' );

/*
 * $Log$
 * Revision 1.11  2009/02/26 01:03:56  blueyed
 * Cleanup: remove disp_cond() and expand the code where it has been used only (file browser view)
 *
 * Revision 1.10  2008/02/04 13:57:50  fplanque
 * wording
 *
 * Revision 1.9  2008/01/21 09:35:29  fplanque
 * (c) 2008
 *
 * Revision 1.8  2008/01/05 02:26:06  fplanque
 * doc
 *
 * Revision 1.7  2007/11/22 17:53:39  fplanque
 * filemanager display cleanup, especially in IE (not perfect)
 *
 * Revision 1.6  2007/11/01 04:31:25  fplanque
 * Better root browsing (roots are groupes by type + only one root is shown at a time)
 *
 * Revision 1.5  2007/11/01 01:40:59  fplanque
 * fixed : dir creation was losing item_ID
 *
 * Revision 1.4  2007/09/26 23:32:39  fplanque
 * upload context saving
 *
 * Revision 1.3  2007/09/26 21:53:23  fplanque
 * file manager / file linking enhancements
 *
 * Revision 1.2  2007/09/03 19:36:06  fplanque
 * chicago admin skin
 *
 * Revision 1.1  2007/06/25 10:59:58  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.47  2007/04/26 00:11:10  fplanque
 * (c) 2007
 *
 * Revision 1.46  2007/01/25 03:17:00  fplanque
 * visual cleanup for average users
 * geeky stuff preserved as options
 *
 * Revision 1.45  2007/01/25 02:41:50  fplanque
 * made settings non sticky
 *
 * Revision 1.44  2007/01/24 07:58:59  fplanque
 * integrated toolbars
 *
 * Revision 1.43  2007/01/24 07:18:22  fplanque
 * file split
 *
 * Revision 1.42  2007/01/24 05:57:55  fplanque
 * cleanup / settings
 *
 * Revision 1.41  2007/01/24 03:45:29  fplanque
 * decrap / removed a lot of bloat...
 *
 * Revision 1.40  2007/01/24 02:35:42  fplanque
 * refactoring
 *
 * Revision 1.39  2007/01/24 01:40:14  fplanque
 * Upload tab now stays in context
 *
 * Revision 1.38  2007/01/23 22:30:14  fplanque
 * empty icons cleanup
 *
 * Revision 1.37  2007/01/09 00:55:16  blueyed
 * fixed typo(s)
 *
 * Revision 1.36  2007/01/07 18:42:35  fplanque
 * cleaned up reload/refresh icons & links
 *
 * Revision 1.35  2006/12/24 00:52:57  fplanque
 * Make posts with images - Proof of concept
 *
 * Revision 1.34  2006/12/23 22:53:10  fplanque
 * extra security
 *
 * Revision 1.33  2006/12/22 00:17:05  fplanque
 * got rid of dirty globals
 * some refactoring
 *
 * Revision 1.32  2006/12/14 02:18:23  fplanque
 * fixed navigation
 *
 * Revision 1.31  2006/12/14 01:46:29  fplanque
 * refactoring / factorized image preview display
 *
 * Revision 1.30  2006/12/14 00:33:53  fplanque
 * thumbnails & previews everywhere.
 * this is getting good :D
 *
 * Revision 1.29  2006/12/13 18:10:22  fplanque
 * thumbnail resampling proof of concept
 *
 * Revision 1.28  2006/12/13 03:08:28  fplanque
 * thumbnail implementation design demo
 *
 * Revision 1.27  2006/12/12 19:39:07  fplanque
 * enhanced file links / permissions
 *
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
 */
?>