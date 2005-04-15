<?php
/**
 * This file implements the UI for file browsing.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: François PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


// Begin payload block:
$AdminUI->dispPayloadBegin();


?>

<!-- FLAT MODE: -->

<form action="files.php#FM_anchor" id="fmbar_flatmode" class="toolbaritem">
	<?php
	echo $Fileman->getFormHiddenInputs();
	echo $Fileman->getFormHiddenSelectedFiles();
	?>
	<input name="actionArray[<?php echo $Fileman->flatmode ? 'noflatmode' : 'flatmode' ?>]"
		class="ActionButton"
		type="submit"
		title="<?php
			echo format_to_output( $Fileman->flatmode ?
															T_('Normal mode') :
															T_('All files and folders, including subdirectories'), 'formvalue' );
			?>"
		onclick="this.form.action='files.php#FM_anchor';"
		value="<?php
			echo format_to_output( $Fileman->flatmode ?
															T_('Normal mode') :
															T_('Flat mode'), 'formvalue' ); ?>" />
</form>


<!-- FILTER BOX: -->

<?php
// Title for checkbox and its label
$titleRegExp = format_to_output( T_('Filter is a regular expression'), 'formvalue' );
?>

<form action="files.php#FM_anchor" id="fmbar_filter" class="toolbaritem">
	<?php
	echo $Fileman->getFormHiddenInputs();
	echo $Fileman->getFormHiddenSelectedFiles();
	?>
	<label for="filterString" id="filterString" class="tooltitle"><?php echo T_('Filter') ?>:</label>
	<input type="text"
		name="filterString"
		value="<?php echo format_to_output( $Fileman->getFilter( false ), 'formvalue' ) ?>"
		size="7"
		accesskey="f" />
	<input type="checkbox" name="filterIsRegexp" id="filterIsRegexp" title="<?php echo $titleRegExp; ?>"
		value="1"<?php if( $Fileman->filterIsRegexp ) echo ' checked="checked"' ?> />
	<label for="filterIsRegexp" title="<?php echo $titleRegExp; ?>"><?php
		echo /* TRANS: short for "is regular expression" */ T_('RegExp'); ?></label>

	<input name="actionArray[filter]"
		class="ActionButton"
		type="submit"
		value="<?php echo format_to_output( T_('Apply'), 'formvalue' ) ?>" />

	<?php
	if( $Fileman->isFiltering() )
	{ // "reset filter" form
		?>
		<input title="<?php echo T_('Unset filter'); ?>"
			type="image"
			name="actionArray[filter_unset]"
			class="ActionButton"
			src="<?php echo getIcon( 'delete', 'url' ) ?>" />
		<?php
	}
	?>
</form>


<?php /* Not implemented yet:

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

<form action="files.php#FM_anchor" name="FilesForm" id="FilesForm" method="post">
<input type="hidden" name="confirmed" value="0" />
<input type="hidden" name="md5_filelist" value="<?php echo $Fileman->toMD5() ?>" />
<input type="hidden" name="md5_cwd" value="<?php echo md5($Fileman->getCwd()) ?>" />
<?php echo $Fileman->getFormHiddenInputs(); ?>


<table class="grouped clear" cellspacing="0">

<?php
/**
 * @global integer Number of cols for the files table, 8 by default
 */
$filetable_cols = 8;

?>

<thead>
<tr>
	<td colspan="<?php echo $filetable_cols ?>" class="firstcol lastcol">

		<?php
		$rootlist = $Fileman->getRootList();

		if( count($rootlist) > 1 )
		{ // provide list of roots
			?>

			<!-- ROOT LISTS -->

			<div id="fmbar_roots">
				<select name="rootIDAndPath" onchange="this.form.submit()">
				<?php
				foreach( $rootlist as $lroot )
				{
					echo '<option value="'.format_to_output( serialize( array( 'id' => $lroot['id'], 'path' => '' ) ), 'formvalue' ).'"';

					if( $Fileman->root == $lroot['id'] || ($Fileman->root === NULL && $lroot['id'] == 'user') )
					{
						echo ' selected="selected"';
					}

					echo '>'.format_to_output( $lroot['name'] )."</option>\n";
				}
				?>

				</select>

				<input class="ActionButton" type="submit" value="<?php echo T_('Change root') ?>" />

			</div>

			<?php
		}
		?>


		<div id="fmbar_cwd">
			<?php
			// -----------------------------------------------
			// Display table header: directory location info:
			// -----------------------------------------------

			// Display current dir:
			echo T_('Current dir').': <strong class="currentdir">'.$Fileman->getCwdClickable().'</strong>';


			// Display current filter:
			if( $Fileman->isFiltering() )
			{
				echo '[<em class="filter">'.$Fileman->getFilter().'</em>]';
				// TODO: maybe clicking on the filter should open a JS popup saying "Remove filter [...]? Yes|No"
			}


			// The hidden reload button
			?>

			<span style="display:none;" id="fm_reloadhint">
				<a href="<?php echo $Fileman->getCurUrl() ?>"
					title="<?php echo T_('A popup has discovered that the displayed content of this window is not up to date. Click to reload.'); ?>">
					<?php echo getIcon( 'reload' ) ?>
				</a>
			</span>

			<?php
			// Display filecounts:
			?>

			<span class="fm_filecounts" title="<?php printf( T_('%s bytes'), number_format($Fileman->countBytes()) ); ?>"> (<?php
			disp_cond( $Fileman->countDirs(), T_('One directory'), T_('%d directories'), T_('No directories') );
			echo ', ';
			disp_cond( $Fileman->countFiles(), T_('One file'), T_('%d files'), T_('No files' ) );
			echo ', '.bytesreadable( $Fileman->countBytes() );
			?>
			)</span>
		</div>
	</td>

</tr>

<tr>
	<th colspan="2" class="firstcol"><?php $Fileman->dispButtonParent(); ?></th>
	<th><?php
		echo $Fileman->getLinkSort( 'name', /* TRANS: file name */ T_('Name') );

		if( $Fileman->flatmode )
		{
			echo ' &ndash; '.$Fileman->getLinkSort( 'path', /* TRANS: file/directory path */ T_('Path') );
		}
	?></th>
	<th><?php echo $Fileman->getLinkSort( 'type', /* TRANS: file type */ T_('Type') ) ?></th>
	<th><?php echo $Fileman->getLinkSort( 'size', /* TRANS: file size */ T_('Size') ) ?></th>
	<th><?php echo $Fileman->getLinkSort( 'lastmod', /* TRANS: file's last change / timestamp */ T_('Last change') ) ?></th>
	<th><?php echo $Fileman->getLinkSort( 'perms', /* TRANS: file's permissions (short) */ T_('Perms') ) ?></th>
	<th class="lastcol"><?php echo /* TRANS: file actions; edit, rename, copy, .. */ T_('Actions') ?></th>
</tr>
</thead>


<tbody>

<?php
param( 'checkall', 'integer', 0 );  // Non-Javascript-CheckAll

$Fileman->sort();

$countFiles = 0;
while( $lFile =& $Fileman->getNextFile() )
{ // loop through all Files:
	?>

	<tr<?php
		if( $countFiles%2 ) echo ' class="odd"';
		?> onclick="document.getElementById('cb_filename_<?php echo $countFiles; ?>').click();">
		<td class="checkbox firstcol">
			<input title="<?php echo T_('Select this file') ?>" type="checkbox"
				name="fm_selected[]"
				value="<?php echo $lFile->getID(); ?>"
				id="cb_filename_<?php echo $countFiles ?>"
				onclick="this.click();"<?php
				if( $checkall || $Fileman->isSelected( $lFile ) )
				{
					echo ' checked="checked"';
				}
				?> />
		</td>

		<td class="icon">
			<a href="<?php
				if( $lFile->isDir() )
				{
					echo $Fileman->getLinkFile( $lFile ).'" title="'.T_('Change into this directory');
				}
				else
				{
					echo $Fileman->getFileUrl().'" title="'.T_('Let the browser handle this file');
				}
				?>"
				onclick="document.getElementById('cb_filename_<?php echo $countFiles; ?>').click();"><?php
				echo getIcon( $lFile ) ?></a>
		</td>

		<td class="filename">

			<a href="<?php echo $Fileman->getLinkFile( $lFile ) ?>"
				target="fileman_default"
				title="<?php echo T_('Open in a new window'); ?>"
				onclick="return false;">


			<button class="filenameIcon" type="button"
					id="button_new_<?php echo $countFiles ?>"
					onclick="<?php
						$imgsize = $lFile->getImageSize( 'widthheight' );
						echo $Fileman->getJsPopupCode( NULL,
							"'+( typeof(fm_popup_type) == 'undefined' ? 'fileman_default' : 'fileman_popup_$countFiles')+'",
							($imgsize ? $imgsize[0]+100 : NULL),
							($imgsize ? $imgsize[1]+150 : NULL) );

						// Un-do the td-onclick action on the checkbox:
						?> document.getElementById('cb_filename_<?php echo $countFiles; ?>').click();"
					><?php
					echo getIcon( 'window_new' );
				?></button></a>

			<?php

			if( !isFilename( $lFile->getName() ) )
			{
				// TODO: Warning icon with hint
				echo getIcon( 'warning', 'imgtag', array( 'class' => 'filenameIcon', 'title' => T_('The filename appears to be invalid and may cause problems.') ) );
			}

			if( $Fileman->fm_mode == 'link_item' )
			{	// Offer option to link the file to an Item:
				$Fileman->dispButtonFileLink();
			}
			?>



			<a href="<?php echo $Fileman->getLinkFile( $lFile ) ?>"
				onclick="document.getElementById('cb_filename_<?php echo $countFiles; ?>').click();"><?php
				if( $Fileman->flatmode && $Fileman->getOrder() != 'name' )
				{
					echo './'.$Fileman->getFileSubpath( $lFile );
				}
				else
				{
					echo $lFile->getName();
				}
				disp_cond( $Fileman->getFileImageSize(), ' (%s)' )
				?>
			</a>

			<?php

			if( $Fileman->flatmode && $Fileman->getOrder() == 'name' )
			{
				?>
				<div class="path" title="<?php echo T_('The directory of the file') ?>"><?php
				$subPath = $Fileman->getFileSubpath( $lFile, false );
				if( empty( $subPath ) )
				{
					$subPath = './';
				}
				echo $subPath;
				?>
				</div>
				<?php
			}

			?>
		</td>

		<td class="type"><?php echo $lFile->getType() ?></td>

		<td class="size"><?php echo $lFile->getSizeNice() ?></td>

		<td class="timestamp">
			<span class="date"><?php echo $lFile->getLastMod( 'date' ) ?></span>
			<span class="time"><?php echo $lFile->getLastMod( 'time' ) ?></span>
		</td>

		<td class="perms"><?php $Fileman->dispButtonFileEditPerms() ?></td>
		<td class="actions lastcol"><?php
			// Not implemented yet: $Fileman->dispButtonFileEdit();
			$Fileman->dispButtonFileProperties();
			$Fileman->dispButtonFileRename();
			$Fileman->dispButtonFileCopy();
			$Fileman->dispButtonFileMove();
			$Fileman->dispButtonFileDelete();
			?></td>
	</tr>

	<?php
	$countFiles++;
}


if( $countFiles == 0 )
{ // Filelist errors or "directory is empty"
	?>

	<tr>
		<td colspan="<?php echo $filetable_cols ?>">
			<?php
			if( !$Fileman->Messages->count( 'fl_error' ) )
			{ // no Filelist errors, the directory must be empty
				$Fileman->Messages->add( T_('No files found.')
					.( $Fileman->isFiltering() ? '<br />'.T_('Filter').': &laquo;'.$Fileman->getFilter().'&raquo;' : '' ), 'fl_error' );
			}
			$Fileman->Messages->display( '', '', true, 'fl_error', 'log_error' );

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
	<tr class="listfooter">
		<td colspan="<?php echo $filetable_cols ?>">
		<a id="checkallspan_0" href="<?php
			echo url_add_param( $Fileman->getCurUrl(), 'checkall='.( $checkall ? '0' : '1' ) );
			?>" onclick="toggleCheckboxes('FilesForm', 'fm_selected[]'); return false;"><?php
			echo ($checkall) ? T_('uncheck all') : T_('check all');
			?></a>
		&mdash; <strong><?php echo T_('With selected files:') ?> </strong>

		<input class="DeleteButton"
			title="<?php echo T_('Delete the selected files') ?>"
			name="actionArray[delete]"
			value="delete"
			type="image"
			src="<?php echo getIcon( 'file_delete', 'url' ) ?>"
			onclick="if( r = openselectedfiles(true) )
								{
									if( confirm('<?php echo TS_('Do you really want to delete the selected files?') ?>') )
									{
										document.getElementById( 'FilesForm' ).confirmed.value = 1;
										return true;
									}
								}; return false;" />

		<!-- Not implemented yet: input class="ActionButton"
			title="<?php echo T_('Download the selected files') ?>"
			name="actionArray[download]"
			value="download"
			type="image"
			src="<?php echo getIcon( 'download', 'url' ) ?>"
			onclick="return openselectedfiles(true);" / -->

		<!-- Not implemented yet: input class="ActionButton" type="submit"
			name="actionArray[sendbymail]" value="<?php echo T_('Send by mail') ?>" onclick="return openselectedfiles(true);" / -->

		<?php

		/*
		TODO: "link these into current post" (that is to say the post that opened the popup window).
					This would create <img> or <a href> tags depending on file types.
		*/

		?>

		<input class="ActionButton"
			title="<?php echo T_('Open in new windows'); ?>"
			name="actionArray[open_in_new_windows]"
			value="open_in_new_windows"
			type="image"
			src="<?php echo getIcon( 'window_new', 'url' ) ?>"
			onclick="openselectedfiles(); return false;" />

	<?php
	/* Not fully functional
		<input class="ActionButton" type="image" name="actionArray[file_cmr]"
			title="<?php echo T_('Rename the selected files'); ?>"
			onclick="return openselectedfiles(true);"
			src="<?php echo getIcon( 'file_rename', 'url' ); ?>" />

		<input class="ActionButton" type="image" name="actionArray[file_cmr]"
			title="<?php echo T_('Copy the selected files'); ?>"
			onclick="return openselectedfiles(true);"
			src="<?php echo getIcon( 'file_copy', 'url' ); ?>" />

		<input class="ActionButton" type="image" name="actionArray[file_cmr]"
			title="<?php echo T_('Move the selected files'); ?>"
			onclick="return openselectedfiles(true);"
			src="<?php echo getIcon( 'file_move', 'url' ); ?>" />

		<input class="ActionButton" type="image" name="actionArray[editperm]"
			onclick="return openselectedfiles(true);"
			title="<?php echo T_('Change permissions for the selected files'); ?>"
			src="<?php echo getIcon( 'file_perms', 'url' ); ?>" />
	*/ ?>

		</td>
	</tr>
	<?php
}
?>
</tbody>

</table>

</form>


<?php
if( $countFiles )
{
	?>
	<script type="text/javascript">
		<!--
		function openselectedfiles( checkonly )
		{
			elems = document.getElementsByName( 'fm_selected[]' );
			fm_popup_type = 'selected';
			var opened = 0;
			for( i = 0; i < elems.length; i++ )
			{
				if( elems[i].checked )
				{
					if( !checkonly )
					{
						id = elems[i].id.substring( elems[i].id.lastIndexOf('_')+1, elems[i].id.length );
						document.getElementById( 'button_new_'+id ).click();
					}
					opened++;
				}
			}
			if( !opened )
			{
				alert( '<?php echo TS_('Nothing selected.') ?>' );
				return false;
			}
			else
			{
				return true;
			}
		}
		// -->
	</script>
	<?php
}
?>


<!-- CREATE: -->

<form action="files.php#FM_anchor" class="toolbaritem">
	<?php echo $Fileman->getFormHiddenInputs();
	echo T_('New'); ?>
	<select name="createnew">
		<?php
			echo '<option value="dir"';
			if( isset($createnew) &&  $createnew == 'dir' )
			{
				echo ' selected="selected"';
			}
			echo '>'.T_('directory').'</option>';

			echo '<option value="file"';
			if( isset($createnew) && $createnew == 'file' )
			{
				echo ' selected="selected"';
			}
			echo '>'.T_('file').'</option>';
		?>
	</select>:
	<input type="text" name="createname" value="<?php
		if( isset( $createname ) )
		{
			echo $createname;
		} ?>" size="15" />
	<input class="ActionButton" type="submit" value="<?php echo format_to_output( T_('Create!'), 'formvalue' ) ?>" />
	<input type="hidden" name="action" value="createnew" />
</form>


<!-- UPLOAD: -->

<form action="files.php" method="post" class="toolbaritem">
	<div>
		<?php echo $Fileman->getFormHiddenInputs( array( 'fm_mode' => 'file_upload' ) ); ?>
		<input class="ActionButton" type="submit" value="<?php echo T_('Upload file...'); ?>" />
	</div>
</form>

<form enctype="multipart/form-data" action="files.php" method="post" class="toolbaritem">
	<!-- The following is mainly a hint to the browser. -->
	<?php form_hidden( 'MAX_FILE_SIZE', $Settings->get( 'upload_maxkb' )*1024 ); ?>

	<?php echo $Fileman->getFormHiddenInputs( array( 'fm_mode' => 'file_upload' ) ); ?>

	<div>
		<input name="uploadfile[]" type="file" size="10" />
		<input class="ActionButton" type="submit" value="<?php echo T_('Quick Upload!'); ?>" />
	</div>
</form>

<div class="clear"></div>

<fieldset>
	<legend><?php echo T_('Help') ?></legend>
	<ul>
		<li><?php echo T_('Clicking on a file icon lets the browser handle the file.'); ?></li>
		<li><?php echo T_('Clicking on a file name invokes the default action (images get displayed as image, raw content for all other files).'); ?></li>
		<li><?php printf( T_('Clicking on the %s icon invokes the default action in a new window.'), getIcon( 'window_new' ) ); ?></li>
		<li><?php echo T_('Actions'); ?>:
			<ul class="iconlegend">
				<li><?php echo getIcon( 'file_rename' ).' '.T_('Rename'); ?></li>
				<li><?php echo getIcon( 'file_copy' ).' '.T_('Copy'); ?></li>
				<li><?php echo getIcon( 'file_move' ).' '.T_('Move'); ?></li>
				<li><?php echo getIcon( 'file_delete' ).' '.T_('Delete'); ?></li>
			</ul>
		</li>
</fieldset>
<?php


// ------------------
// Display options:
// Let's keep these at the end since they won't be accessed more than occasionaly
// and only by advanced users
// ------------------
param( 'options_show', 'integer', 0 );
?>
<form action="files.php#FM_anchor" id="options_form" method="post">
	<fieldset>
	<legend><?php echo T_('Options') ?>
	[<a id="options_toggle" href="<?php
	echo url_add_param( $Fileman->getCurUrl(), ( !$options_show ?
																									'options_show=1' :
																									'' ) )
	?>" onclick="return toggle_options();"><?php echo $options_show ? T_('Hide') : T_('Show'); ?></a>]</legend>

	<div id="options_list"<?php if( !$options_show ) echo ' style="display:none"' ?>>
		<input type="checkbox" id="option_dirsattop" name="option_dirsattop" value="1"<?php if( !$UserSettings->get('fm_dirsnotattop') ) echo ' checked="checked"' ?> />
		<label for="option_dirsattop"><?php echo T_('Sort directories at top') ?></label>
		<br />
		<input type="checkbox" id="option_showhidden" name="option_showhidden" value="1"<?php if( $UserSettings->get('fm_showhidden') ) echo ' checked="checked"' ?> />
		<label for="option_showhidden"><?php echo T_('Show hidden files') ?></label>
		<br />
		<input type="checkbox" id="option_permlikelsl" name="option_permlikelsl" value="1"<?php if( $UserSettings->get('fm_permlikelsl') ) echo ' checked="checked"' ?> />
		<label for="option_permlikelsl"><?php echo T_('File permissions like &quot;ls -l&quot;') ?></label>
		<br />
		<input type="checkbox" id="option_getimagesizes" name="option_getimagesizes" value="1"<?php if( $UserSettings->get('fm_getimagesizes') ) echo ' checked="checked"' ?> />
		<label for="option_getimagesizes"><?php echo T_('Display the image size of image files') ?></label>
		<br />
		<input type="checkbox" id="option_recursivedirsize" name="option_recursivedirsize" value="1"<?php if( $UserSettings->get('fm_recursivedirsize') ) echo ' checked="checked"' ?> />
		<label for="option_recursivedirsize"><?php echo T_('Recursive size of directories') ?></label>
		<br />
		<input type="checkbox" id="option_forceFM" name="option_forceFM" value="1"<?php if( $UserSettings->get('fm_forceFM') ) echo ' checked="checked"' ?> />
		<label for="option_forceFM"><?php echo T_('Always show the Filemanager (upload mode, ..)') ?></label>
		<br />

		<?php echo $Fileman->getFormHiddenInputs() ?>
		<input type="hidden" name="options_show" value="1" />

		<div class="input">
			<input type="submit" name="actionArray[update_settings]" value="<?php echo T_('Update !') ?>" />
		</div>
	</div>
	</fieldset>

	<script type="text/javascript">
	<!--
		showoptions = <?php echo ($options_show) ? 'true' : 'false' ?>;

		/**
		 * Toggles the display of the filemanager options.
		 */
		function toggle_options()
		{
			if( showoptions )
			{
				var replace = document.createTextNode('<?php echo TS_('Show') ?>');
				var display_list = 'none';
				showoptions = false;
			}
			else
			{
				var replace = document.createTextNode('<?php echo TS_('Hide') ?>');
				var display_list = 'inline';
				showoptions = true;
			}
			document.getElementById('options_list').style.display = display_list;
			document.getElementById('options_toggle').replaceChild(replace, document.getElementById( 'options_toggle' ).firstChild);
			return false;
		}
	// -->
	</script>
</form>



</div>

<div class="clear"></div>

<?php
// End payload block:
$AdminUI->dispPayloadEnd();

/*
 * $Log$
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
 *
 */
?>