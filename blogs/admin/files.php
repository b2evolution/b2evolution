<?php
/**
 * This file implements the UI controller for file management.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 *
 * @todo thumbnail view
 * @todo PHPInfo (special permission)
 * @todo directly run PHP-code (eval)
 */

/**
 * Load config, init and get the {@link $mode mode param}
 */
require_once dirname(__FILE__).'/_header.php';

$admin_tab = 'files';
$admin_pagetitle = T_('Filemanager').' (beta)';


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

if( $current_User->login != 'demouser' && $current_User->level < 10 )
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
						.form_text( 'zipname', '', 20, T_('Archive filename'), T_("This is the file's name that will get sent to you."), 80, '', 'text', false )."\n"
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
				require( dirname(__FILE__).'/'.$admin_dirout.$lib_subdir.'_zip_archives.php' );

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


// Adjust page title
switch( $Fileman->getMode() )
{
	case 'file_upload':
		$admin_pagetitle = T_('Upload').$admin_path_seprator.$admin_pagetitle;
		break;
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
		$allowedFileExtensions = trim( $Settings->get( 'upload_allowedext' ) );
		$allowedMimeTypes = trim( $Settings->get( 'upload_allowedmime' ) );

		$failedFiles = array();


		if( isset($_FILES) && count( $_FILES ) )
		{{{ // Process uploaded files
			param( 'uploadfile_alt', 'array', array() );
			param( 'uploadfile_desc', 'array', array() );
			param( 'uploadfile_name', 'array', array() );

			foreach( $_FILES['uploadfile']['name'] as $lKey => $lName )
			{
				if( empty($lName) )
				{ // no name
					continue;
				}

				// pop it again if we succeeded
				$failedFiles[] = $lKey;

				if( ( $Settings->get( 'upload_maxkb' ) && $_FILES['uploadfile']['size'][$lKey] > $Settings->get( 'upload_maxkb' )*1024 )
						|| $_FILES['uploadfile']['error'][$lKey] == UPLOAD_ERR_FORM_SIZE ) // The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form.
				{ // bigger than defined by blog
					$LogUpload->add( sprintf( T_('The file &laquo;%s&raquo; is too big and has not been accepted.'), $lName ) );
					continue;
				}
				elseif( $_FILES['uploadfile']['error'][$lKey] )
				{
					switch( $_FILES['uploadfile']['error'][$lKey] )
					{
						case UPLOAD_ERR_INI_SIZE: // bigger than allowed in php.ini
							$LogUpload->add( sprintf( T_('The uploaded file &laquo;%s&raquo; exceeds the upload_max_filesize directive in php.ini.'), $lName ) );
							continue 2;

						case UPLOAD_ERR_PARTIAL:
							$LogUpload->add( sprintf( T_('The uploaded file &laquo;%s&raquo; was only partially uploaded.'), $lName ) );
							continue 2;

						case UPLOAD_ERR_NO_FILE:
							$LogUpload->add( sprintf( T_('No file was uploaded (%s).'), $lName ) );
							continue 2;
					}

					$LogUpload->add( sprintf( T_('Unknown error with file &laquo;%s&raquo;.'), $lName ) );
					continue;
				}
				elseif( !is_uploaded_file( $_FILES['uploadfile']['tmp_name'][$lKey] ) )
				{
					$LogUpload->add( sprintf( T_('The file &laquo;%s&raquo; does not seem to be a valid upload!'), $lName ) );
					continue;
				}


				$newName = !empty( $uploadfile_name[ $lKey ] ) ? $uploadfile_name[ $lKey ] : $lName;

				if( !isFilename( $newName ) )
				{
					$LogUpload->add( sprintf( T_('&laquo;%s&raquo; is not a valid filename.'), $newName ) );
					continue;
				}
				elseif( !empty($allowedFileExtensions)
								&& !preg_match( '#\.'.preg_replace( array( '#\s+#', '/#/' ), array( '|', '\#' ), $allowedFileExtensions ).'$#', $newName  ) )
				{
					$LogUpload->add( sprintf( T_('The file extension of &laquo;%s&raquo; is not allowed.'), $newName ) );
					continue;
				}
				elseif( !empty($allowedMimeTypes)
								&& !empty( $_FILES['uploadfile']['type'][$lKey] ) // browser provided type
								&& !preg_match( '#\.'.preg_replace( array( '#\s+#', '/#/' ), array( '|', '\#' ), $allowedMimeTypes ).'$#', $newName  ) )
				{
					$LogUpload->add( sprintf( T_('The file type (MIME) &laquo;%s&raquo; of &laquo;%s&raquo; is not allowed.'), $_FILES['uploadfile']['type'][$lKey], $newName ) );
					continue;
				}


				$newFile =& getFile( $newName, $Fileman->getCwd() );

				if( $newFile->exists() )
				{
					// TODO: Rename/Overwriting
					$LogUpload->add( sprintf( T_('The file &laquo;%s&raquo; already exists.'), $newFile->getName() ) );
					continue;
				}
				elseif( move_uploaded_file( $_FILES['uploadfile']['tmp_name'][$lKey], $newFile->getPath() ) )
				{
					$LogUpload->add( sprintf( T_('The file &laquo;%s&raquo; has been successfully uploaded.'), $newFile->getName() ), 'note' );

					array_pop( $failedFiles );

					$newFile->refresh();
					$Fileman->addFile( $newFile );
					continue;
				}
			}
		}}}


		// Upload dialog {{{
		?>

		<script type="text/javascript">
			/**
			 * Mighty cool function to append an input or textarea element onto another element.
			 *
			 * @usedby addAnotherFileInput()
			 *
			 * @author proud daniel hahler :)
			 */
			function appendLabelAndInputElements( appendTo, labelText, inputOrTextarea, inputName, inputSizeOrCols, inputMaxLengthOrRows, inputType )
			{
				/*var fileDivLabel = document.createElement("div");
				fileDivLabel.className = "label";*/
				var fileLabel = document.createElement("label");
				var fileLabelText = document.createTextNode( labelText );
				fileLabel.appendChild( fileLabelText );
				/*fileDivLabel.appendChild( fileLabel );*/
				appendTo.appendChild( fileLabel );
				appendTo.appendChild( document.createElement("br") );

				/*var fileDivInput = document.createElement("div");
				fileDivInput.className = "input";*/
				var fileInput = document.createElement( inputOrTextarea );
				fileInput.name = inputName;
				if( inputOrTextarea == "input" )
				{
					fileInput.type = typeof( inputType ) !== 'undefined' ?
														inputType :
														"text";
					fileInput.size = inputSizeOrCols;
					if( typeof( inputMaxLengthOrRows ) != 'undefined' )
					{
						fileInput.maxlength = inputMaxLengthOrRows;
					}
				}
				else
				{
					fileInput.cols = inputSizeOrCols;
					fileInput.rows = inputMaxLengthOrRows;
				}
				/*fileDivInput.appendChild( fileInput );*/
				appendTo.appendChild( fileInput );
				appendTo.appendChild( document.createElement("br") );
			}

			/**
			 * Add a new fileinput area to the upload form.
			 *
			 * @author proud daniel hahler :)
			 */
			function addAnotherFileInput()
			{
				var newLI = document.createElement("li");
				newLI.className = "clear";
				uploadfiles = document.getElementById("uploadfileinputs");

				uploadfiles.appendChild( newLI );


				appendLabelAndInputElements( newLI, "<?php echo T_('Choose a file'); ?>:", "input", "uploadfile[]", "40", "0", "file" );
				appendLabelAndInputElements( newLI, "<?php echo T_('Alternative text'); ?>:", "input", "uploadfile_alt[]", "40", "80", "text" );
				appendLabelAndInputElements( newLI, "<?php echo T_('Description of the file'); ?>:", "textarea", "uploadfile_desc[]", "40", "3" );
				appendLabelAndInputElements( newLI, "<?php echo T_('New filename (without path)'); ?>:", "input", "uploadfile_name[]", "40", "80", "text" );
			}
		</script>


		<div class="panelblock">
			<form enctype="multipart/form-data" action="files.php" method="post" class="fform">
				<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $Settings->get( 'upload_maxkb' )*1024 ?>" />
				<?php
				// we'll use $rootIDAndPath only
				echo $Fileman->getFormHiddenInputs( array( 'root' => false, 'path' => false ) );
				form_hidden( 'rootIDAndPath', serialize( array( 'id' => $Fileman->root, 'path' => $Fileman->getPath() ) ) );
				?>

				<h1><?php echo T_('File upload'); ?></h1> <?php /* TODO: We need a good (smaller) default h1! */ ?>

				<?php $LogUpload->display( '', '', true, 'all' ); ?>


				<fieldset style="float:left;width:60%;">
					<legend><?php echo T_('Files to upload') ?></legend>

					<p>
						<?php
						$restrictNotes = array();

						if( $allowedFileExtensions )
						{
							$restrictNotes[] = T_('Allowed file extensions').': '.str_replace( ' ', ', ', $allowedFileExtensions );
						}
						if( $allowedMimeTypes )
						{
							$restrictNotes[] = T_('Allowed MIME types').': '.str_replace( ' ', ', ', $allowedMimeTypes );
						}
						if( $Settings->get( 'upload_maxkb' ) )
						{
							$restrictNotes[] = sprintf( T_('Maximum allowed file size: %s'), bytesreadable( $Settings->get( 'upload_maxkb' )*1024 ) );
						}

						if( $restrictNotes )
						{
							echo implode( '<br />', $restrictNotes ).'<br />';
						}

						?>
					</p>

					<ul id="uploadfileinputs">
						<?php
						$failedFiles[] = NULL; // display at least one


						foreach( $failedFiles as $lKey )
						{
							?><li<?php
								if( $lKey !== NULL )
								{
									echo ' class="invalid" title="'./* TRANS: will be displayed as title for failed file uploads */ T_('Invalid submission.').'"';
								} ?>>
								<label><?php echo T_('Choose a file'); ?>:</label><br />
								<input name="uploadfile[]" type="file" size="37" /><br />

								<label><?php echo T_('Alternative text'); ?></label>:<br />
								<input name="uploadfile_alt[]" type="text" size="50" maxlength="80"
									value="<?php echo ( isset( $uploadfile_alt[$lKey] ) ? format_to_output( $uploadfile_alt[$lKey], 'formvalue' ) : '' );
									?>" /><br />

								<label><?php echo T_('Description of the file'); /* TODO: maxlength (DB) */ ?></label>:<br />
								<textarea name="uploadfile_desc[]" rows="3" cols="37"><?php
									echo ( isset( $uploadfile_desc[$lKey] ) ? $uploadfile_desc[$lKey] : '' )
								?></textarea><br />

								<label><?php echo T_('New filename (without path)'); ?></label>:<br />
								<input name="uploadfile_name[]" type="text" size="50" maxlength="80"
									value="<?php echo ( isset( $uploadfile_name[$lKey] ) ? format_to_output( $uploadfile_name[$lKey], 'formvalue' ) : '' ) ?>" /><br />
							</li><?php // no text after </li> or JS will bite you!
						}


						?></ul>

				</fieldset>

				<fieldset>
					<legend><?php echo T_('Upload files into:'); ?></legend>
					<?php
					#echo 'Choose the directory you want to upload the files into. la la la la la la la';
					?>

					<?php
					echo $Fileman->getDirectoryTreeRadio();
					?>
				</fieldset>

				<div class="clear"></div>
				<fieldset class="center">
					<input class="ActionButton" type="submit" value="<?php echo T_('Upload !') ?>" />
					<input class="ActionButton" type="button" value="<?php echo T_('Add another file') ?>" onclick="addAnotherFileInput();" />
				</fieldset>
			</form>

		</div>

		<div class="clear"></div>


		<?php
		// }}}

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

$toggleButtons = array();

if( $Fileman->getMode() )
{
	if( !$UserSettings->get('fm_forceFM') )
	{ // FM is not forced - link to hide/display
		$toggleButtons[] =
			'<a class="ActionButton"'
			.' href="'.$Fileman->getCurUrl( array( 'forceFM' => Filemanager::getToggled( $Fileman->forceFM ) ) ).'">'
			.( $Fileman->forceFM !== 0
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


if( $Fileman->getMode() && ( !$Fileman->forceFM ) )
{ // We're in a mode and don't force the FM
	?>
	</div>
	<?php

	require( dirname(__FILE__). '/_footer.php' );
	return;
}

// -------------------
// Browsing interface:
// -------------------
require( dirname(__FILE__). '/_files_browse.inc.php' );


require( dirname(__FILE__). '/_footer.php' );


/*
 * $Log$
 * Revision 1.70  2005/01/26 23:44:40  blueyed
 * no message
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