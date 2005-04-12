<?php
/**
 * This file implements the UI controller for file management.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005 by The University of North Carolina at Charlotte
 * as contributed by Jason Edgecombe {@link http://tst.uncc.edu/team/members/jason_bio.php}.
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
 *
 * The University of North Carolina at Charlotte grants François PLANQUE the right to license
 * Jason EDGECOMBE's contributions to this file and the b2evolution project
 * under the GNU General Public License (http://www.opensource.org/licenses/gpl-license.php)
 * and the Mozilla Public License (http://www.opensource.org/licenses/mozilla1.1.php).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: François PLANQUE.
 * @author jwedgeco: Jason EDGECOMBE (for hire by UNC-Charlotte)
 * @author edgester: Jason EDGECOMBE (personal contributions, not for hire)
 *
 * @version $Id$
 *
 * @todo thumbnail view
 * @todo PHPInfo (special permission)
 * @todo directly run PHP-code (eval)
 */

/**
 * Load config, init and get the {@link $mode mode param}
 */
require_once dirname(__FILE__).'/_header.php';

$AdminUI->setPath( 'files' );


if( !$Settings->get( 'fm_enabled' ) )
{
	require dirname(__FILE__).'/_menutop.php';
	Log::display( '', '', T_('The filemanager is disabled.') );
	require( dirname(__FILE__). '/_footer.php' );
	return;
}


/**
 * Load FileManager class
 */
require_once dirname(__FILE__).'/'.$admin_dirout.$core_subdir.'_filemanager.class.php';


if( param( 'rootIDAndPath', 'string', '' ) )
{ // root and path together: decode and override
	$rootIDAndPath = unserialize( $rootIDAndPath );
	$root = $rootIDAndPath['id'];
	$path = $rootIDAndPath['path'];
}
else
{
	param( 'root', 'string', NULL ); // the root directory from the dropdown box (user_X or blog_X; X is ID - 'user' for current user (default))
	param( 'path', 'string', '/' );  // the path relative to the root dir
}

param( 'order', 'string', NULL );
param( 'orderasc', '', NULL );
param( 'filterString', '', NULL );
param( 'filterIsRegexp', 'integer', NULL );
param( 'flatmode', '', NULL );

param( 'action', 'string', '' );     // 3.. 2.. 1.. action :)
if( empty($action) )
{ // check f*cking IE syntax, which send input[image] submits without value, only name.x and name.y
	$action = array_pop( array_keys( param( 'actionArray', 'array', array() ) ) );
}

if( $current_User->level < 10 )
{ // allow demouser, but noone else below level 10
	echo 'The filemanager is still beta. You need user level 10 to play with this.';
	return;
}

if( $action == 'update_settings' )
{ // Updating user settings from options list
	$UserSettings->set( 'fm_dirsnotattop',   1-param( 'option_dirsattop',        'integer', 0 ) );
	$UserSettings->set( 'fm_permlikelsl',      param( 'option_permlikelsl',      'integer', 0 ) );
	$UserSettings->set( 'fm_getimagesizes',    param( 'option_getimagesizes',    'integer', 0 ) );
	$UserSettings->set( 'fm_recursivedirsize', param( 'option_recursivedirsize', 'integer', 0 ) );
	$UserSettings->set( 'fm_showhidden',       param( 'option_showhidden',       'integer', 0 ) );
	$UserSettings->set( 'fm_forceFM',          param( 'option_forceFM',          'integer', 0 ) );

	if( $UserSettings->updateDB() )
	{
		$Messages->add( T_('Your user settings have been updated.'), 'note' );
	}

	$action = '';
}


/**
 * Filemanager object to work with
 */
$Fileman = new FileManager( $current_User, 'files.php', $root, $path, $order, $orderasc,
														$filterString, $filterIsRegexp, $flatmode );
if( !empty($action) )
{
	$oldAction = $action;
	$action = '';

	switch( $oldAction )
	{
		case 'filter_unset':
			$Fileman->setFilter( false, false );
			break;

		case 'noflatmode':
			$Fileman->flatmode = false;
			break;

		case 'flatmode':
			$Fileman->flatmode = true;
			break;

		default:
			$action = $oldAction;
	}
}

$Fileman->load();


/**
 * @var Filelist the selected files
 */
$SelectedFiles =& $Fileman->getFilelistSelected();


if( !empty($action) )
{
	switch( $action )
	{
		// catch JS-only actions
		case 'open_in_new_windows':
			$Fileman->Messages->add( T_('You have to enable JavaScript to use this feature.') );
			break;


		case 'createnew':  // create new file/dir
			param( 'createnew', 'string', '' ); // 'file', 'dir'
			param( 'createname', 'string', '' );

			$Fileman->createDirOrFile( $createnew, $createname ); // handles messages
			break;


		case T_('Send by mail'):
			// TODO: implement
			if( !$SelectedFiles->count() )
			{
				$Fileman->Messages->add( T_('Nothing selected.') );
				break;
			}

			echo 'TODO: Send selected by mail, query email address..';
			break;


		case 'download':
			// TODO: provide optional zip formats
			$action_title = T_('Download');

			if( !$SelectedFiles->count() )
			{
				$Fileman->Messages->add( T_('Nothing selected.') );
				break;
			}

			param( 'zipname', 'string', '' );
			param( 'exclude_sd', 'integer', 0 );

			if( empty($zipname) )
			{
				$action_msg =
					"\n"
					.'<div class="panelblock">'
					."\n"
					.T_('You want to download:')
					.'<ul>'
					.'<li>'.implode( "</li>\n<li>", $SelectedFiles->getFilesArray( 'getNameWithType' ) )."</li>\n"
					.'</ul>

					<form action="files.php" class="fform" method="post">
					' // TODO: use Form class
					.$Fileman->getFormHiddenInputs()
					.$Fileman->getFormHiddenSelectedFiles()
					.form_hidden( 'action', 'download', false )
					.'
					<fieldset>
						<legend>'.T_('Download options').'</legend>
						'
						.form_text( 'zipname', '', 20, T_('Archive filename'), T_("This is the name of the file which will get sent to you."), 80, '', 'text', false )."\n"
						.( $SelectedFiles->countDirs() ?
								form_checkbox( 'exclude_sd', $exclude_sd, T_('Exclude subdirectories'), T_('This will exclude subdirectories of selected directories.'), '', false )."\n" :
								'' )
						.'
						<div class="input">
							<input class="ActionButton" type="submit" value="'
						.format_to_output( T_('Download'), 'formvalue' ).'" />
						</div>
					</fieldset>
					</form>
					</div>
					';
			}
			else
			{ // Downloading
				require dirname(__FILE__).'/'.$admin_dirout.$lib_subdir.'_zip_archives.php';

				$arraylist = $SelectedFiles->getFilesArray( 'getname' );

				$options = array (
					'basedir' => $Fileman->getCwd(),
					'inmemory' => 1,
					'recurse' => 1-$exclude_sd,
				);

				$zipfile = new zip_file( $zipname );
				$zipfile->set_options( $options );
				$zipfile->add_files( $arraylist );
				$zipfile->create_archive();
				$zipfile->download_file();
				exit;
				#$Fileman->Messages->add( sprintf(T_('Zipfile &laquo;%s&raquo; sent to you!'), $zipname), 'note' );
			}

			break;


		case 'delete': // delete a file/dir, TODO: checkperm! {{{
			if( !$SelectedFiles->count() )
			{
				$Fileman->Messages->add( T_('Nothing selected.') );
				break;
			}

			param( 'confirmed', 'integer', 0 );
			param( 'delsubdirs', 'array', array() );

			if( !$confirmed )
			{
				$action_msg = '
					<form action="files.php" class="inline">
						<input type="hidden" name="confirmed" value="1" />
						<input type="hidden" name="action" value="delete" />
						'.$Fileman->getFormHiddenSelectedFiles()
						.$Fileman->getFormHiddenInputs()."\n";


				$action_msg .= $SelectedFiles->count() > 1 ?
												T_('Do you really want to delete the following files?') :
												T_('Do you really want to delete the following file?');

				$action_msg .= '
				<ul>
				';

				foreach( $SelectedFiles->entries as $lFile )
				{
					$action_msg .= '<li>'.$lFile->getName();

					if( $lFile->isDir() )
					{
						$action_msg .= '
							<br />
							<input title="'.sprintf( T_('Check to include subdirectories of &laquo;%s&raquo;'), $lFile->getName() ).'"
								type="checkbox"
								name="delsubdirs['.$lFile->getID().']"
								id="delsubdirs_'.$lFile->getID().'"
								value="1" />
								<label for="delsubdirs_'.$lFile->getID().'">'
									.T_( 'Including subdirectories' ).'</label>';
					}

					$action_msg .= '</li>';
				}

				$action_msg .= "</ul>\n";


				$action_msg .= '
					<input type="submit" value="'.T_('I am sure!').'" class="DeleteButton" />
					</form>
					<form action="files.php" class="inline">
						'.$Fileman->getFormHiddenInputs().'
						<input type="submit" value="'.T_('CANCEL').'" class="CancelButton" />
					</form>
					';
			}
			else
			{
				$SelectedFiles->restart();
				while( $lFile =& $SelectedFiles->getNextFile() )
				{
					if( !$Fileman->unlink( $lFile, isset( $delsubdirs[$lFile->getID()] ) ) ) // handles Messages
					{
						// TODO: offer file again, allowing to include subdirs..
					}
				}
			}

			// }}}
			break;


		case 'editperm': // edit permissions {{{
			$action_title = T_('Change permissions');

			if( !$SelectedFiles->count() )
			{
				$Fileman->Messages->add( T_('Nothing selected.') );
				break;
			}

			param( 'perms', 'array', array() );

			if( count( $perms ) )
			{ // Change perms
				$SelectedFiles->restart();
				while( $lFile =& $SelectedFiles->getNextFile() )
				{
					$chmod = $perms[ $lFile->getID() ];

					$oldperms = $lFile->getPerms( 'raw' );
					$newperms = $lFile->chmod( $chmod );

					if( $newperms === false )
					{
						$Fileman->Messages->add( sprintf( T_('Failed to set permissions on &laquo;%s&raquo; to &laquo;%s&raquo;.'), $lFile->getName(), $chmod ) );
					}
					elseif( $newperms === $oldperms )
					{
						$Fileman->Messages->add( sprintf( T_('Permissions for &laquo;%s&raquo; not changed.'), $lFile->getName() ), 'note' );
					}
					else
					{
						$Fileman->Messages->add( sprintf( T_('Permissions for &laquo;%s&raquo; changed to &laquo;%s&raquo;.'), $lFile->getName(), $lFile->getPerms() ), 'note' );
					}
				}
			}
			else
			{
				// TODO: use Form class, finish non-Windows
				$action_msg = '
				<div class="panelblock">
				<form name="form_chmod" action="files.php">
				'.$Fileman->getFormHiddenSelectedFiles()
				.$Fileman->getFormHiddenInputs().'

				<input type="hidden" name="action" value="editperm" />
				';

				if( is_windows() )
				{
					if( $SelectedFiles->count() > 1 )
					{ // more than one file, provide default

					}
					foreach( $SelectedFiles->getFilesArray() as $lFile )
					{
						$action_msg .= "\n".$Fileman->getFileSubpath( $lFile ).':<br />
						<input id="perms_readonly_'.$lFile->getID().'"
							name="perms['.$lFile->getID().']"
							type="radio"
							value="444"'
							.( $lFile->getPerms( 'octal' ) == 444 ?
									' checked="checked"' :
									'' ).' />
						<label for="perms_readonly_'.$lFile->getID().'">'.T_('Read-only').'</label>

						<input id="perms_readwrite_'.$lFile->getID().'"
							name="perms['.$lFile->getID().']"
							type="radio"
							value="666"'
							.( $lFile->getPerms( 'octal' ) == 666 || $lFile->getPerms( 'octal' ) == 777 ?
									'checked="checked"' :
									'' ).' />
						<label for="perms_readwrite_'.$lFile->getID().'">'.T_('Read and write').'</label>
						<br />';
					}
				}
				else
				{
					$action_msg .= '<input type="text" name="chmod" value="'
													.$lFile->getPerms( 'octal' ).'" maxlength="3" size="3" /><br />';
					$js_focus = 'document.form_chmod.chmod';
				}

				$action_msg .= '
				<input type="submit" value="'.format_to_output( T_('Set new permissions'), 'formvalue' ).'" />
				</form>
				</div>
				';
			}

			// }}}
			break;


		case 'file_cmr': // copy/move/rename - we come here from the "with selected" toolbar {{{
			#pre_dump( $SelectedFiles );

			// }}}
			break;


		case 'default':
			// ------------------------
			// default action (view):
			// ------------------------
			if( !$SelectedFiles->count() )
			{
				$Fileman->Messages->add( T_('Nothing selected.') );
				break;
			}

			$selectedFile = & $SelectedFiles->getFileByIndex(0);

			// TODO: check if available

			require dirname(__FILE__).'/_file_view.inc.php';

			exit;


		case 'leaveMode': // leave mode (upload, ..)
			$Fileman->mode = NULL;
			header( 'Location: '.$Fileman->getCurUrl() );
			break;
	}
}



/**#@+
 * The top menu
 */
require dirname(__FILE__).'/_menutop.php';
/**#@-*/

?>


<div id="filemanmain">
<?php

switch( $Fileman->getMode() )
{ // handle modes {{{

	case 'file_upload': // {{{ upload mode
		// Check permissions:
		if( !$Fileman->perm( 'upload' ) )
		{
			$Fileman->Messages->add( T_('You have no permissions to upload into this directory.') );
			break;
		}

		$LogUpload = new Log( 'error' );
		$allowedFileExtensions = preg_split( '#\s+#', trim( $Settings->get( 'upload_allowedext' ) ), -1, PREG_SPLIT_NO_EMPTY );
		$allowedMimeTypes = preg_split( '#\s+#', trim( $Settings->get( 'upload_allowedmime' ) ), -1, PREG_SPLIT_NO_EMPTY );

		/**
		 * @var array Remember failed files (and the error messages)
		 */
		$failedFiles = array();


		if( isset($_FILES) && count( $_FILES ) )
		{{{ // Process uploaded files
			param( 'uploadfile_alt', 'array', array() );
			param( 'uploadfile_desc', 'array', array() );
			param( 'uploadfile_name', 'array', array() );

			foreach( $_FILES['uploadfile']['name'] as $lKey => $lName )
			{
				if( empty( $lName ) )
				{
					if( !empty( $uploadfile_alt[$lKey] ) || !empty( $uploadfile_desc[$lKey] ) || !empty( $uploadfile_name[$lKey] ) )
					{ // Remember the file as failed when additional info provided.
						$failedFiles[$lKey] = T_( 'Please select a local file to upload.' );
					}
					continue;
				}


				if( ( $Settings->get( 'upload_maxkb' ) && $_FILES['uploadfile']['size'][$lKey] > $Settings->get( 'upload_maxkb' )*1024 )
						|| $_FILES['uploadfile']['error'][$lKey] == UPLOAD_ERR_FORM_SIZE ) // The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form.
				{ // bigger than defined by blog
					$failedFiles[$lKey] = sprintf( /* TRANS: %s will be replaced by the difference */ T_('The file is %s too big.'), bytesreadable( $_FILES['uploadfile']['size'][$lKey] - $Settings->get( 'upload_maxkb' ) ) );
					continue;
				}
				elseif( $_FILES['uploadfile']['error'][$lKey] )
				{
					switch( $_FILES['uploadfile']['error'][$lKey] )
					{
						case UPLOAD_ERR_INI_SIZE: // bigger than allowed in php.ini
							$failedFiles[$lKey] = T_('The file exceeds the upload_max_filesize directive in php.ini.');
							continue 2;

						case UPLOAD_ERR_PARTIAL:
							$failedFiles[$lKey] = T_('The file was only partially uploaded.');
							continue 2;

						case UPLOAD_ERR_NO_FILE:
							// Is probably the same as empty($lName) before.
							$failedFiles[$lKey] = T_('No file was uploaded.');
							continue 2;

						case 6:
						case UPLOAD_ERR_NO_TMP_DIR: // PHP 4.3.10, 5.0.3
							// Missing a temporary folder.
							$failedFiles[$lKey] = T_('Missing a temporary folder (upload_tmp_dir in php.ini).');
							continue 2;

					}

					$failedFiles[$lKey] = T_('Unknown error.').' #'.$_FILES['uploadfile']['error'][$lKey];
					continue;
				}
				elseif( !is_uploaded_file( $_FILES['uploadfile']['tmp_name'][$lKey] ) )
				{
					$failedFiles[$lKey] = T_('The file does not seem to be a valid upload!');
					continue;
				}


				$newName = !empty( $uploadfile_name[ $lKey ] ) ? $uploadfile_name[ $lKey ] : $lName;

				if( !isFilename( $newName ) )
				{
					$failedFiles[$lKey] = sprintf( T_('&laquo;%s&raquo; is not a valid filename.'), $newName );
					continue;
				}

				if( !empty($allowedFileExtensions) )
				{ // check extension
					if( preg_match( '#\.([^.]+)$#', $newName, $match ) )
					{
						$extension = $match[1];

						if( !in_array( $extension, $allowedFileExtensions ) )
						{
							$failedFiles[$lKey] = sprintf( T_('The file extension &laquo;%s&raquo; is not allowed.'), $extension );
							continue;
						}
					}
					// NOTE: Files with no extension are allowed..
				}

				if( !empty($allowedMimeTypes)
						&& !empty( $_FILES['uploadfile']['type'][$lKey] ) // browser provided type
						&& in_array( $_FILES['uploadfile']['type'][$lKey], $allowedMimeTypes )
					)
				{
					$failedFiles[$lKey] = sprintf( T_('The file type (MIME) &laquo;%s&raquo; is not allowed.'), $_FILES['uploadfile']['type'][$lKey] );
					continue;
				}


				$newFile =& getFile( $newName, $Fileman->getCwd() );

				if( $newFile->exists() )
				{
					// TODO: Rename/Overwriting
					$failedFiles[$lKey] = sprintf( T_('The file &laquo;%s&raquo; already exists.'), $newFile->getName() );
					continue;
				}
				elseif( !move_uploaded_file( $_FILES['uploadfile']['tmp_name'][$lKey], $newFile->getPath() ) )
				{
					$failedFiles[$lKey] = T_('An unknown error occurred when moving the uploaded file on the server.');
					continue;
				}
				else
				{
					$LogUpload->add( sprintf( T_('The file &laquo;%s&raquo; has been successfully uploaded.'), $newFile->getName() ), 'note' );

					$newFile->refresh();
					$Fileman->addFile( $newFile );
					continue;
				}
			}
		}}}


		// Upload dialog:
		require dirname(__FILE__).'/_files_upload.inc.php';

		// }}}
		break;


	case 'file_cmr':
		// ------------------------
		// copy/move/rename a file:
		// ------------------------
		// {{{
		$LogCmr = new Log( 'error' );  // Log for copy/move/rename mode

		if( !$Fileman->SourceList->count() )
		{
			$Fileman->Messages->add( sprintf( T_('No source files!') ) );
			$Fileman->mode = NULL;
			break;
		}

		param( 'cmr_keepsource', 'integer', 0 );
		param( 'cmr_newname', 'array', array() );
		param( 'cmr_overwrite', 'array', array() );
		param( 'cmr_doit', 'integer', 0 );


		$Fileman->SourceList->restart();

		if( $cmr_doit )
		{{{ // we want Action!
			while( $lSourceFile =& $Fileman->SourceList->getNextFile() )
			{
				if( !isFilename($newname) )
				{
					$LogCmr->add( sprintf( T_('&laquo;%s&raquo; is not a valid filename.'), $newname ) );
				}
				elseif( ($TargetFile =& getFile( $newname, $Fileman->getCwd() ))
								&& $TargetFile->exists() )
				{ // target filename already given to another file
					if( $TargetFile === $SourceFile )
					{
						$LogCmr->add( T_('Source- and target file are the same. Please choose another name or directory.') );
						$overwrite = false;
					}
					elseif( !$overwrite )
					{
						$LogCmr->add( sprintf( T_('The file &laquo;%s&raquo; already exists.'), $newname ) );
						$overwrite = 'ask';
					}
					else
					{ // unlink existing file
						if( !$Fileman->unlink( $TargetFile ) )
						{
							$LogCmr->add( sprintf( T_('Could not delete &laquo;%s&raquo;.'), $newname ) );
						}
						else
						{
							$Fileman->Messages->add( sprintf( T_('Deleted file &laquo;%s&raquo;.'), $newname ), 'note' );
						}
					}
				}

				if( !$LogCmr->count( 'error' ) )
				{ // no errors, safe for action
					$oldpath = $SourceFile->getPath();

					if( $Fileman->copyFileToFile( $SourceFile, $TargetFile ) )
					{
						if( !$cmr_keepsource )
						{ // move/rename
							if( $Fileman->unlink( $SourceFile ) )
							{
								if( $SourceFile->getDir() == $Fileman->getCwd() )
								{ // successfully renamed
									$Fileman->Messages->add( sprintf( T_('Renamed &laquo;%s&raquo; to &laquo;%s&raquo;.'),
																										basename($oldpath),
																										$TargetFile->getName() ), 'note' );
								}
								else
								{ // successfully moved
									$Fileman->Messages->add( sprintf( T_('Moved &laquo;%s&raquo; to &laquo;%s&raquo;.'),
																										$oldpath,
																										$TargetFile->getName() ), 'note' );

								}
							}
							else
							{
								$LogCmr->add( sprintf( T_('Could not remove &laquo;%s&raquo;, but the file has been copied to &laquo;%s&raquo;.'),
																			($SourceFile->getDir() == $Fileman->getCwd() ?
																				basename($oldpath) :
																				$oldpath ),
																			$TargetFile->getName() ) );
							}
						}
						else
						{ // copy only
							$Fileman->Messages->add( sprintf(
								T_('Copied &laquo;%s&raquo; to &laquo;%s&raquo;.'),
								( $SourceFile->getDir() == $Fileman->getCwd() ?
										$SourceFile->getName() :
										$SourceFile->getPath() ),
								$TargetFile->getName() ), 'note' );
						}
					}
					else
					{
						$LogCmr->add( sprintf( T_('Could not copy &laquo;%s&raquo; to &laquo;%s&raquo;.'),
																		$SourceFile->getPath(),
																		$TargetFile->getPath() ), 'error' );
					}
				}
			}
		}}}


		if( !$cmr_doit || $LogCmr->count( 'all' ) )
		{
			?>

			<div class="panelblock">
				<form action="files.php" class="fform" id="cmr_form">
					<?php echo $Fileman->getFormHiddenInputs() ?>
					<input type="hidden" name="cmr_doit" value="1" />
					<fieldset>
						<legend><?php
						echo T_('Copy / Move / Rename');
						?></legend>

						<div class="notes">
							<?php
							echo '<strong>'.T_('You are in copy-move-rename mode.')
										.'</strong><br />'.T_('Please navigate to the desired location.'); ?>
						</div>

						<?php

						$LogCmr->display( '', '', true, 'all' );

						$sourcesInSameDir = true;

						while( $lSourceFile =& $Fileman->SourceList->getNextFile() )
						{
							if( $sourcesInSameDir && $lSourceFile->getDir() != $Fileman->cwd )
							{
								$sourcesInSameDir = false;
							}
							?>

							<fieldset>
								<legend><?php echo T_('Source').': '.$lSourceFile->getPath();
								?></legend>

								<?php


								if( isset( $cmr_overwrite[$lSourceFile->getID()] )
										&& $cmr_overwrite[$lSourceFile->getID()] === 'ask' )
								{
									form_checkbox( 'overwrite', 0, '<span class="error">'.T_('Overwrite existing file').'</span>',
																	sprintf( T_('The existing file &laquo;%s&raquo; will be replaced with this file.'),
																						$TargetFile->getPath() ) );
								}
								?>

								<div class="label">
									<label for="cmr_keepsource_<?php $lSourceFile->getID(); ?>"><?php echo T_('Keep source file') ?>:</label>
								</div>
								<div class="input">
									<input class="checkbox" type="checkbox" value="1"
										name="cmr_keepsource[<?php echo $lSourceFile->getID(); ?>]"
										id="cmr_keepsource_<?php $lSourceFile->getID(); ?>"
										onclick="setCmrSubmitButtonValue( this.form );"<?php
										if( $cmr_keepsource )
										{
											echo ' checked="checked"';
										} ?> />
									<span class="notes"><?php echo T_('Do not delete the source file.') ?></span>
								</div>
								<div class="clear"></div>


								<div class="label">
									<label for="cmr_newname_<?php $lSourceFile->getID(); ?>">New name:</label>
								</div>
								<div class="input">
									<input type="text" name="cmr_newname[<?php $lSourceFile->getID(); ?>]"
										id="cmr_newname_<?php $lSourceFile->getID(); ?>" value="<?php
										echo isset( $cmr_newname[$lSourceFile->getID()] ) ?
														$cmr_newname[$lSourceFile->getID()] :
														$lSourceFile->getName() ?>" />
								</div>

							</fieldset>

						<?php
						}

						// text and value for JS dynamic fields, when referring to move/rename
						if( $sourcesInSameDir )
						{
							$submitMoveOrRenameText = format_to_output( T_('Rename'), 'formvalue' );
						}
						else
						{
							$submitMoveOrRenameText = format_to_output( T_('Move'), 'formvalue' );
						}

						?>

						<fieldset>
							<div class="input">
								<input id="cmr_submit" type="submit" value="<?php
									if( $cmr_keepsource )
									{
										echo format_to_output( T_('Copy'), 'formvalue' );
									}
									else
									{
										echo $submitMoveOrRenameText;
									} ?>" />
								<input type="reset" value="<?php echo format_to_output( T_('Reset'), 'formvalue' ) ?>" />
							</div>
						</fieldset>
					</fieldset>
				</form>


				<script type="text/javascript">
					<!--
					function setCmrSubmitButtonValue()
					{
						if( document.getElementById( 'cmr_keepsource' ).checked )
						{
							text = '<?php echo format_to_output( T_('Copy'), 'formvalue' ) ?>';
						}
						else
						{
							text = '<?php echo $submitMoveOrRenameText ?>';
						}
						document.getElementById( 'cmr_submit' ).value = text;
					}
					setCmrSubmitButtonValue(); // init call
					// -->
				</script>

			</div>
			<?php
		}
		else
		{ // successfully finished, leave mode
			$Fileman->mode = NULL;
		}

		// }}}
		break;
} // }}}


// Display reload-icon in the opener window if we're a popup in the same CWD and the
// Filemanager content differs.
?>

<script type="text/javascript">
	<!--
	if( opener
			&& opener.document.FilesForm
			&& typeof(opener.document.FilesForm.md5_filelist.value) != 'undefined'
			&& typeof(opener.document.FilesForm.md5_cwd.value) != 'undefined'
			&& opener.document.FilesForm.md5_cwd.value == '<?php echo md5($Fileman->getCwd()); ?>'
		)
	{
		opener.document.getElementById( 'fm_reloadhint' ).style.display =
			opener.document.FilesForm.md5_filelist.value == '<?php echo $Fileman->toMD5(); ?>'
			? 'none'
			: 'inline';
	}
	// -->
</script>


<?php

// "Display/hide Filemanager" and "Leave mode" buttons

$showFilemanager = !$Fileman->getMode()
										|| ( $UserSettings->get('fm_forceFM') || $Fileman->forceFM );

$toggleButtons = array();

if( $Fileman->getMode() )
{
	if( !$UserSettings->get('fm_forceFM') )
	{ // FM is not forced - link to hide/display
		$toggleButtons[] =
			'<a class="ActionButton"'
			.' href="'.$Fileman->getCurUrl( array( 'forceFM' => 1-$Fileman->forceFM ) ).'">'
			.( $showFilemanager
					? T_('Hide Filemanager')
					: T_('Display Filemanager') ).'</a>';
	}

	if( $Fileman->getMode() == 'file_upload' )
	{
		$toggleButtons[] =
			'<a class="ActionButton"'
			.' href="'.$Fileman->getCurUrl( array( 'fm_mode' => false, 'forceFM' => 1 ) ).'">'
			. /* TRANS: Button to leave the upload mode */ T_('Leave upload mode').'</a>';
	}
}

if( isset($toggleButtons[0]) )
{
	?>

	<div class="toggleModeAndFM" id="FM_anchor">

		&mdash;

		<?php echo implode( ' ', $toggleButtons ); ?>

		&mdash;

	</div>

	<?php
}

if( isset($action_title) )
{
	echo "\n<h2>$action_title</h2>\n";
}

// Output errors, notes and action messages {{{
if( $Fileman->Messages->count( array( 'error', 'note' ) ) || $Messages->count( 'all' ) )
{
	?>

	<div class="panelinfo">
		<?php
		$Messages->display( '', '', true, 'all' );
		$Fileman->Messages->display( '', '', true, array( 'error', 'note' ) );
		?>
	</div>

	<?php
}

if( isset($action_msg) )
{
	echo $action_msg;

	if( isset( $js_focus ) )
	{ // we want to auto-focus a field
		echo '
		<script type="text/javascript">
			<!--
			'.$js_focus.'.focus();
			// -->
		</script>';
	}
}

// }}}


if( !$showFilemanager )
{ // We're in a mode and don't force the FM
	?>
	</div>
	<?php

	require dirname(__FILE__). '/_footer.php';
	return;
}

// -------------------
// Browsing interface:
// -------------------
require dirname(__FILE__).'/_files_browse.inc.php';


require dirname(__FILE__).'/_footer.php';


/*
 * $Log$
 * Revision 1.82  2005/04/12 19:00:22  fplanque
 * File manager cosmetics
 *
 * Revision 1.81  2005/03/15 19:19:46  fplanque
 * minor, moved/centralized some includes
 *
 * Revision 1.80  2005/03/08 15:23:52  fplanque
 * no message
 *
 * Revision 1.79  2005/03/07 00:06:17  blueyed
 * admin UI refactoring, part three
 *
 * Revision 1.78  2005/02/28 09:06:39  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.77  2005/02/27 20:34:49  blueyed
 * Admin UI refactoring
 *
 * Revision 1.76  2005/02/23 04:06:16  blueyed
 * minor
 *
 * Revision 1.75  2005/02/12 00:58:13  blueyed
 * fixed preg_split() better ($allowedFileExtensions, $allowedMimeTypes)
 *
 * Revision 1.74  2005/02/11 20:30:20  jwedgeco
 * Added copyright and license info. I forgot to add it on the last commit.
 *
 * Revision 1.73  2005/02/11 20:27:51  jwedgeco
 * I added a kludge. For some reason on my setup, the empty() php funtion doesn't work on $allowedFileExtensions when $settings->get(upload_allowedext) is blank.
 *
 * Revision 1.72  2005/02/08 01:06:52  blueyed
 * fileupload reworked, fixed "Display FM" / "Leave mode" buttons
 *
 * Revision 1.71  2005/01/27 13:34:57  fplanque
 * i18n tuning
 *
 * Revision 1.69  2005/01/26 17:55:23  blueyed
 * catching up..
 *
 * Revision 1.68  2005/01/14 17:39:02  blueyed
 * layout
 *
 * Revision 1.67  2005/01/13 20:27:42  blueyed
 * $mode
 *
 * Revision 1.66  2005/01/12 20:22:51  fplanque
 * started file/dataobject linking
 *
 * Revision 1.65  2005/01/12 17:55:51  fplanque
 * extracted browsing interface into separate file to make code more readable
 *
 * Revision 1.63  2005/01/09 05:36:39  blueyed
 * fileupload
 *
 * Revision 1.62  2005/01/08 12:05:50  blueyed
 * small bugfix
 *
 * Revision 1.61  2005/01/08 01:24:19  blueyed
 * filelist refactoring
 *
 * Revision 1.60  2005/01/06 15:45:36  blueyed
 * Fixes..
 *
 * Revision 1.59  2005/01/06 11:31:46  blueyed
 * bugfixes
 *
 * Revision 1.58  2005/01/06 10:15:46  blueyed
 * FM upload and refactoring
 *
 */
?>