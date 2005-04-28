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

/**
 * Load FileManager class
 */
require_once dirname(__FILE__).'/'.$admin_dirout.$core_subdir.'_filemanager.class.php';

$AdminUI->setPath( 'files' );


/**
 * Check global access permissions:
 */
if( !$Settings->get( 'fm_enabled' ) )
{
	require dirname(__FILE__).'/_menutop.php';
	Log::display( '', '', T_('The filemanager is disabled.') );
	require( dirname(__FILE__). '/_footer.php' );
	return;
}

if( $current_User->level < 7 )
{
	echo 'The filemanager is still beta. You need user level 7 to play with this.';
	return;
}

if( param( 'rootIDAndPath', 'string', '', true ) )
{ // root and path together: decode and override
	$rootIDAndPath = unserialize( $rootIDAndPath );
	$root = $rootIDAndPath['id'];
	$path = $rootIDAndPath['path'];
}
else
{
	param( 'root', 'string', NULL, true ); // the root directory from the dropdown box (user_X or blog_X; X is ID - 'user' for current user (default))
	param( 'path', 'string', '/', true );  // the path relative to the root dir
}

param( 'order', 'string', NULL, true );
param( 'orderasc', '', NULL, true );
param( 'filterString', '', NULL, true );
param( 'filterIsRegexp', 'integer', NULL, true );
param( 'flatmode', '', NULL, true );
param( 'action', 'string', '', true );     // 3.. 2.. 1.. action :)
if( empty($action) )
{ // check f*cking IE syntax, which send input[image] submits without value, only name.x and name.y
	$action = array_pop( array_keys( param( 'actionArray', 'array', array(), true ) ) );
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


/*
 * Load editable objects:
 */
if( param( 'link_ID', 'integer', NULL, false, false, false ) )
{
	if( ($edited_Link = $LinkCache->get_by_ID( $link_ID, false )) === false )
	{	// We could not find the linke to edit:
		$Messages->head = T_('Cannot edit link!');
		$Messages->add( T_('Requested link does not exist any longer.'), 'error' );
		unset( $edited_Link );
		unset( $link_ID );
	}
}


/*
 * Load linkable objects:
 */
if( param( 'item_ID', 'integer', NULL, true, false, false ) )
{ // Load Requested iem:
	if( ($edited_Item = $ItemCache->get_by_ID( $item_ID, false )) === false )
	{	// We could not find the contact to link:
		$Messages->head = T_('Cannot link Item!');
		$Messages->add( T_('Requested item does not exist any longer.'), 'error' );
		unset( $edited_Item );
		unset( $item_ID );
	}
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
		case 'open_in_new_windows':
			// catch JS-only actions
			$Fileman->Messages->add( T_('You have to enable JavaScript to use this feature.') );
			break;


		case 'createnew':
			// create new file/dir
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
					.'<li>'.implode( "</li>\n<li>", $SelectedFiles->getFilesArray( 'get_typed_name' ) )."</li>\n"
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
					'basedir' => $Fileman->cwd,
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


		case 'delete':
			// delete a file/dir, TODO: checkperm! {{{
			if( !$SelectedFiles->count() )
			{
				$Fileman->Messages->add( T_('Nothing selected.') );
				break;
			}

			param( 'confirmed', 'integer', 0 );
			param( 'delsubdirs', 'array', array() );

			if( !$confirmed )
			{
				$action_msg = '<div class="panelinfo">';
				$action_msg .= '<h2>'.T_('Delete file(s)?').'</h2>';

				$action_msg .= '
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

				// So far there is no problem with confirming...
				$can_confirm = true;

				// make sure we have loaded metas for all files in selection!
				$SelectedFiles->load_meta();

				foreach( $SelectedFiles->_entries as $lFile )
				{
					$action_msg .= '<li>'.$lFile->get_typed_name();

					/* fplanque>> We cannot actually offer to delete subdirs since we cannot pre-check DB integrity for these...
					if( $lFile->is_dir() )
					{	// This is a directory
							$action_msg .= '
							<br />
							<input title="'.sprintf( T_('Check to include subdirectories of &laquo;%s&raquo;'), $lFile->get_name() ).'"
								type="checkbox"
								name="delsubdirs['.$lFile->get_md5_ID().']"
								id="delsubdirs_'.$lFile->get_md5_ID().'"
								value="1" />
								<label for="delsubdirs_'.$lFile->get_md5_ID().'">'
									.T_( 'Including subdirectories' ).'</label>';
					}
					*/

					// Check if there are delete restrictions on this file:
					$lFile->check_relations( 'delete_restrictions' );

					if( $Messages->count('restrict') )
					{	// There are restrictions:
						$action_msg .= ': <strong>'.T_('cannot be deleted because of the following relations').'</strong> :'; 
						$action_msg .= $Messages->display( NULL, NULL, false, 'restrict', '', 'ul', false );
						$Messages->clear( 'restrict' );
						// We won't be able to continue with deletion...
						$can_confirm = false;
					}

					$action_msg .= '</li>';
				}

				$action_msg .= "</ul>\n";


				if( $can_confirm )
				{	// No integrity problem detected...
					$action_msg .= '
						<input type="submit" value="'.T_('I am sure!').'" class="DeleteButton" />
						</form>
						<form action="files.php" class="inline">
							'.$Fileman->getFormHiddenInputs().'
							<input type="submit" value="'.T_('CANCEL').'" class="CancelButton" />
						</form>
						';
				}

				$action_msg .= '</div>';

			}
			else
			{
				$SelectedFiles->restart();
				while( $lFile =& $SelectedFiles->getNextFile() )
				{
					if( !$Fileman->unlink( $lFile, isset( $delsubdirs[$lFile->get_md5_ID()] ) ) ) // handles Messages
					{
						// TODO: offer file again, allowing to include subdirs..
					}
				}
			}

			// }}}
			break;


		case 'edit_properties':
			// Edit File properties (Meta Data):

			$selectedFile = & $SelectedFiles->getFileByIndex(0);
			// Load meta data:
			$selectedFile->load_meta();

			$Fileman->fm_mode = 'File_properties';
			break;


		case 'update_properties':
			// Update File properties (Meta Data):

			$selectedFile = & $SelectedFiles->getFileByIndex(0);
			// Load meta data:
			$selectedFile->load_meta();

			$selectedFile->set( 'title', param( 'title', 'string', '' ) );
			$selectedFile->set( 'alt', param( 'alt', 'string', '' ) );
			$selectedFile->set( 'desc', param( 'desc', 'string', '' ) );

			// Store File object into DB:
			$selectedFile->dbsave();

			// Leave special display mode:
 			$Fileman->fm_mode = 'NULL';
			break;


		case 'link':
			// Link File to Item:
			if( !$SelectedFiles->count() )
			{
				$Fileman->Messages->add( T_('Nothing selected.') );
				break;
			}

			if( !isset($edited_Item) )
			{	// No Item to link to...
				$Fileman->fm_mode = NULL;
				break;
			}

			// TODO: check item EDIT permissions!

			$selectedFile = & $SelectedFiles->getFileByIndex(0);

			$DB->begin();

			// Load meta data AND MAKE SURE IT IS CREATED IN DB:
			$selectedFile->load_meta( true );

			// Let's make the link!
			$edited_Link = & new Link();
			$edited_Link->set( 'item_ID', $edited_Item->ID );
			$edited_Link->set( 'file_ID', $selectedFile->ID );
			$edited_Link->dbinsert();

			$DB->commit();

			$Messages->add( T_('Selected file has been linked to item.'), 'note' );
			break;


		case 'unlink':
			// Unlink File from Item:
			if( !isset( $edited_Link ) )
			{
				$Fileman->Messages->add( T_('Nothing selected.') );
				break;
			}

			// TODO: get Item from Link to check perm

			// TODO: check item EDIT permissions!

			// Delete from DB:
			$msg = sprintf( T_('Link from &laquo;%s&raquo; deleted.'), $edited_Link->Item->dget('title') );
			$edited_Link->dbdelete( true );
			unset($edited_Link);
			$Messages->add( $msg, 'note' );
			break;


		case 'editperm':
			// edit permissions {{{
			// fplanque>> TODO: as long as we use fm_modes this thing should at least work like a mode or at the bare minimun, turn off any active mode.
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
					$chmod = $perms[ $lFile->get_md5_ID() ];

					$oldperms = $lFile->get_perms( 'raw' );
					$newperms = $lFile->chmod( $chmod );

					if( $newperms === false )
					{
						$Fileman->Messages->add( sprintf( T_('Failed to set permissions on &laquo;%s&raquo; to &laquo;%s&raquo;.'), $lFile->get_name(), $chmod ) );
					}
					elseif( $newperms === $oldperms )
					{
						$Fileman->Messages->add( sprintf( T_('Permissions for &laquo;%s&raquo; not changed.'), $lFile->get_name() ), 'note' );
					}
					else
					{
						$Fileman->Messages->add( sprintf( T_('Permissions for &laquo;%s&raquo; changed to &laquo;%s&raquo;.'), $lFile->get_name(), $lFile->get_perms() ), 'note' );
					}
				}
			}
			else
			{	// Display dialog:
				// TODO: use Form class, finish non-Windows
				// TODO: move to a file called _file_permissions.form.php
				$action_msg = '
				<div class="panelblock">
				<form name="form_chmod" action="files.php">
				'.$Fileman->getFormHiddenSelectedFiles()
				.$Fileman->getFormHiddenInputs().'

				<input type="hidden" name="action" value="editperm" />
				';

				if( is_windows() )
				{ // WINDOWS read/write permissons:
					if( $SelectedFiles->count() > 1 )
					{ // more than one file, provide default

					}
					foreach( $SelectedFiles->getFilesArray() as $lFile )
					{
						$action_msg .= "\n".$Fileman->getFileSubpath( $lFile ).':<br />
						<input id="perms_readonly_'.$lFile->get_md5_ID().'"
							name="perms['.$lFile->get_md5_ID().']"
							type="radio"
							value="444"'
							.( $lFile->get_perms( 'octal' ) == 444 ?
									' checked="checked"' :
									'' ).' />
						<label for="perms_readonly_'.$lFile->get_md5_ID().'">'.T_('Read-only').'</label>

						<input id="perms_readwrite_'.$lFile->get_md5_ID().'"
							name="perms['.$lFile->get_md5_ID().']"
							type="radio"
							value="666"'
							.( $lFile->get_perms( 'octal' ) == 666 || $lFile->get_perms( 'octal' ) == 777 ?
									'checked="checked"' :
									'' ).' />
						<label for="perms_readwrite_'.$lFile->get_md5_ID().'">'.T_('Read and write').'</label>
						<br />';
					}
				}
				else
				{	// UNIX permissions:
					$action_msg .= '<input type="text" name="chmod" value="'
													.$lFile->get_perms( 'octal' ).'" maxlength="3" size="3" /><br />';
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


		case 'file_cmr':
			// copy/move/rename - we come here from the "with selected" toolbar {{{
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

			// Load meta data:
			$selectedFile->load_meta();

			// TODO: check if available

			require dirname(__FILE__).'/_file_view.inc.php';

			exit;


		case 'leaveMode':
			// leave mode (upload, ..)
			$Fileman->fm_mode = NULL;
			header( 'Location: '.$Fileman->getCurUrl() );
			break;
	}
}



/**
 * The top menu
 */
require dirname(__FILE__).'/_menutop.php';

?>


<?php
switch( $Fileman->fm_mode )
{ // handle modes {{{

	case 'file_upload':
		// {{{ upload mode
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

		// Process uploaded files:
		if( isset($_FILES) && count( $_FILES ) )
		{{{	// Some files have been uploaded:

			param( 'uploadfile_title', 'array', array() );
			param( 'uploadfile_alt', 'array', array() );
			param( 'uploadfile_desc', 'array', array() );
			param( 'uploadfile_name', 'array', array() );

			foreach( $_FILES['uploadfile']['name'] as $lKey => $lName )
			{
				if( empty( $lName ) )
				{	// No file name

					if( !empty( $uploadfile_title[$lKey] )
						|| !empty( $uploadfile_alt[$lKey] )
						|| !empty( $uploadfile_desc[$lKey] )
						|| !empty( $uploadfile_name[$lKey] ) )
					{	// User specified params but NO file!!!
						// Remember the file as failed when additional info provided.
						$failedFiles[$lKey] = T_( 'Please select a local file to upload.' );
					}
					// Abort upload for this file:
					continue;
				}

				if( $Settings->get( 'upload_maxkb' ) && ( $_FILES['uploadfile']['size'][$lKey] > $Settings->get( 'upload_maxkb' )*1024 ) )
				{ // bigger than defined by blog
					$failedFiles[$lKey] = sprintf( /* TRANS: %s will be replaced by the difference */ T_('The file is %s too large.'),
															 bytesreadable( $_FILES['uploadfile']['size'][$lKey] - $Settings->get( 'upload_maxkb' ) ) );
					// Abort upload for this file:
					continue;
				}

				if( $_FILES['uploadfile']['error'][$lKey] )
				{	// PHP has detected an error!:
					switch( $_FILES['uploadfile']['error'][$lKey] )
					{
						case UPLOAD_ERR_FORM_SIZE:
							// The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form.
							$failedFiles[$lKey] = sprintf( T_('The file is too large. Maximum allowed is: %s.'),
																		bytesreadable( $Settings->get( 'upload_maxkb' )*1024 ) );
							break;

						case UPLOAD_ERR_INI_SIZE: // bigger than allowed in php.ini
							$failedFiles[$lKey] = T_('The file exceeds the upload_max_filesize directive in php.ini.');
							break;

						case UPLOAD_ERR_PARTIAL:
							$failedFiles[$lKey] = T_('The file was only partially uploaded.');
							break;

						case UPLOAD_ERR_NO_FILE:
							// Is probably the same as empty($lName) before.
							$failedFiles[$lKey] = T_('No file was uploaded.');
							break;

						case 6: // numerical value of UPLOAD_ERR_NO_TMP_DIR
						# (min_php: 4.3.10, 5.0.3) case UPLOAD_ERR_NO_TMP_DIR:
							// Missing a temporary folder.
							$failedFiles[$lKey] = T_('Missing a temporary folder (upload_tmp_dir in php.ini).');
							break;

						default:
							$failedFiles[$lKey] = T_('Unknown error.').' #'.$_FILES['uploadfile']['error'][$lKey];
					}
					// Abort upload for this file:
					continue;
				}

				if( !is_uploaded_file( $_FILES['uploadfile']['tmp_name'][$lKey] ) )
				{	// Ensure that a malicious user hasn't tried to trick the script into working on files upon which it should not be working.
					$failedFiles[$lKey] = T_('The file does not seem to be a valid upload!');
					// Abort upload for this file:
					continue;
				}

				// Use new name on server if specified:
				$newName = !empty( $uploadfile_name[ $lKey ] ) ? $uploadfile_name[ $lKey ] : $lName;

				if( !isFilename( $newName ) )
				{
					$failedFiles[$lKey] = sprintf( T_('&laquo;%s&raquo; cannot be used as a valid filename on this server.'), $newName );
					// Abort upload for this file:
					continue;
				}

				// Check file extension:
				if( !empty($allowedFileExtensions) )
				{ // check extension
					if( preg_match( '#\.([^.]+)$#', $newName, $match ) )
					{
						$extension = $match[1];

						if( !in_array( $extension, $allowedFileExtensions ) )
						{
							$failedFiles[$lKey] = sprintf( T_('The file extension &laquo;%s&raquo; is not allowed.'), $extension );
							// Abort upload for this file:
							continue;
						}
					}
					// NOTE: Files with no extension are allowed..
				}

				// Check file MIME type:
				// fplanque>>blueyed: I think that checking against a browser provided type is a bad idea,
				// people will think it's a secure way to prevent upload of scripts... but it's not :(
				if( !empty($allowedMimeTypes)
						&& !empty( $_FILES['uploadfile']['type'][$lKey] ) // browser provided type
						&& in_array( $_FILES['uploadfile']['type'][$lKey], $allowedMimeTypes )
					)
				{
					$failedFiles[$lKey] = sprintf( T_('The file type (MIME) &laquo;%s&raquo; is not allowed.'), $_FILES['uploadfile']['type'][$lKey] );
					// Abort upload for this file:
					continue;
				}

				// Get File object for requested target location:
				$newFile = & $FileCache->get_by_path( $Fileman->cwd.$newName, true );

				if( $newFile->exists() )
				{	// The file already exists in the target location!
					// TODO: Rename/Overwriting
					$failedFiles[$lKey] = sprintf( T_('The file &laquo;%s&raquo; already exists.'), $newFile->get_name() );
					// Abort upload for this file:
					continue;
				}

				// Attempt to move the uploaded file to the requested target location:
				if( !move_uploaded_file( $_FILES['uploadfile']['tmp_name'][$lKey], $newFile->get_full_path() ) )
				{
					$failedFiles[$lKey] = T_('An unknown error occurred when moving the uploaded file on the server.');
					// Abort upload for this file:
					continue;
				}

				$LogUpload->add( sprintf( T_('The file &laquo;%s&raquo; has been successfully uploaded.'), $newFile->get_name() ), 'note' );

				// Refreshes file properties (type, size, perms...)
				$newFile->load_properties();

				// Store extra info about the file into File Object:
				if( isset( $uploadfile_title[$lKey] ) )
				{	// If a title text has been passed... (does not happen in quick upload mode)
					$newFile->set( 'title', trim( strip_tags($uploadfile_title[$lKey])) );
				}
				if( isset( $uploadfile_alt[$lKey] ) )
				{	// If an alt text has been passed... (does not happen in quick upload mode)
					$newFile->set( 'alt', trim( strip_tags($uploadfile_alt[$lKey])) );
				}
				if( isset( $uploadfile_desc[$lKey] ) )
				{	// If a desc text has been passed... (does not happen in quick upload mode)
					$newFile->set( 'desc', trim( strip_tags($uploadfile_desc[$lKey])) );
				}

				// Store File object into DB:
				$newFile->dbsave();

				// Tell the filamanager about the new file:
				$Fileman->addFile( $newFile );
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
		/*
		 * fplanque>> This whole thing is flawed:
		 * 1) only geeks can possibly like to use the same interface for renaming, moving and copying
		 * 2) even the geeky unix commands won't pretend copying and moving are the same thing. They are not!
		 *    Only moving and renaming are similar, and again FOR GEEKS ONLY.
		 * 3) The way this works it breaks the File meta data (I'm working on it).
		 * 4) For Move and Copy, this should use a "destination directory tree" on the right (same as for upload)
		 * 5) Given all the reasons above copy, move and rename should be clearly separated into 3 different interfaces.
		 */
		// {{{
		$LogCmr = new Log( 'error' );  // Log for copy/move/rename mode

		if( !$Fileman->SourceList->count() )
		{
			$Fileman->Messages->add( sprintf( T_('No source files!') ) );
			$Fileman->fm_mode = NULL;
			break;
		}

		param( 'cmr_keepsource', 'integer', 0 );
		param( 'cmr_newname', 'array', array() );
		param( 'cmr_overwrite', 'array', array() );
		param( 'cmr_doit', 'integer', 0 );


		$Fileman->SourceList->restart();

		$file_count = 0;
		if( $cmr_doit )
		{{{ // we want Action!
			while( $SourceFile = & $Fileman->SourceList->getNextFile() )
			{
				$newname = trim(strip_tags($cmr_newname[$file_count]));
				if( !isFilename($newname) )
				{
					$LogCmr->add( sprintf( T_('&laquo;%s&raquo; is not a valid filename.'), $newname ) );
				}
				elseif( ($TargetFile = & $FileCache->get_by_path( $Fileman->cwd.$newname ))
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
					$oldpath = $SourceFile->get_full_path();

					if( $Fileman->copyFileToFile( $SourceFile, $TargetFile ) )
					{
						if( !$cmr_keepsource )
						{ // move/rename
							if( $Fileman->unlink( $SourceFile ) )
							{
								if( $SourceFile->get_dir() == $Fileman->cwd )
								{ // successfully renamed
									$Fileman->Messages->add( sprintf( T_('Renamed &laquo;%s&raquo; to &laquo;%s&raquo;.'),
																										basename($oldpath),
																										$TargetFile->get_name() ), 'note' );
								}
								else
								{ // successfully moved
									$Fileman->Messages->add( sprintf( T_('Moved &laquo;%s&raquo; to &laquo;%s&raquo;.'),
																										$oldpath,
																										$TargetFile->get_name() ), 'note' );

								}
							}
							else
							{
								$LogCmr->add( sprintf( T_('Could not remove &laquo;%s&raquo;, but the file has been copied to &laquo;%s&raquo;.'),
																			($SourceFile->get_dir() == $Fileman->cwd ?
																				basename($oldpath) :
																				$oldpath ),
																			$TargetFile->get_name() ) );
							}
						}
						else
						{ // copy only
							$Fileman->Messages->add( sprintf(
								T_('Copied &laquo;%s&raquo; to &laquo;%s&raquo;.'),
								( $SourceFile->get_dir() == $Fileman->cwd ?
										$SourceFile->get_name() :
										$SourceFile->get_full_path() ),
								$TargetFile->get_name() ), 'note' );
						}
					}
					else
					{
						$LogCmr->add( sprintf( T_('Could not copy &laquo;%s&raquo; to &laquo;%s&raquo;.'),
																		$SourceFile->get_full_path(),
																		$TargetFile->get_full_path() ), 'error' );
					}
				}
			}
			$file_count++;
		}}}


		if( !$cmr_doit || $LogCmr->count( 'all' ) )
		{
			// CMR dialog:
			require dirname(__FILE__).'/_files_cmr.inc.php';
		}
		else
		{ // successfully finished, leave mode
			$Fileman->fm_mode = NULL;
		}

		// }}}
		break;


	case 'File_properties':
		// File properties (Meta data) dialog:
		require dirname(__FILE__).'/_file_properties.inc.php';
		break;


	case 'link_item':
		// We want to link file(s) to an item:

		if( !isset($edited_Item) )
		{	// No Item to link to...
			$Fileman->fm_mode = NULL;
			break;
		}

		// TODO: check EDIT permissions!

		// Begin payload block:
		$AdminUI->dispPayloadBegin();

		$Form = & new Form( 'files.php', '', 'post', 'fieldset' );

		$Form->global_icon( T_('Quit link mode!'), 'close',	$Fileman->getCurUrl( array( 'fm_mode' => false, 'forceFM' => 1 ) ) );

		$Form->begin_form( 'fform', sprintf( T_('Link files to &laquo;%s&raquo;...'), $edited_Item->dget( 'title') ) );

		$edited_Item->edit_link( '<p>', '</p>', T_('Edit this post') );

		$Results = & new Results(
							'SELECT link_ID, link_ltype_ID, T_files.*
								 FROM T_links INNER JOIN T_files ON link_file_ID = file_ID
								WHERE link_item_ID = '.$edited_Item->ID,
								20, 'link_' );

		$Results->title = T_('Existing links');

		/*
		$Results->cols[] = array(
								'th' => T_('Link ID'),
								'order' => 'link_ID',
								'td' => '$link_ID$',
							);

		$Results->cols[] = array(
								'th' => T_('Type'),
								'order' => 'link_ltype_ID',
								'td' => '$link_ltype_ID$',
							);

 		$Results->cols[] = array(
								'th' => T_('File ID'),
								'order' => 'file_ID',
								'td' => '$file_ID$',
							);
		*/

 		$Results->cols[] = array(
								'th' => T_('Path'),
								'order' => 'file_path',
								'td_start' => '<td class="firstcol left">',
								'td' => '$file_path$',
							);

 		$Results->cols[] = array(
								'th' => T_('Title'),
								'order' => 'file_title',
								'td_start' => '<td class="left">',
								'td' => '$file_title$',
							);

		function file_exp( & $row )
		{
			// Instantiate a File object for this line:
			$current_File = & new File( $row->file_path );
			// Flow meta data into File object:
			$current_File->load_meta( false, $row );

			// File title
			$r = $current_File->title;

			// File type:
			$r .= ' '.$current_File->get_icon().' '.$current_File->get_type();

			return $r;
		}
 		$Results->cols[] = array(
								'th' => T_('Exp'),
								'td_start' => '<td class="left">',
								'td' => '%file_exp( {row} )%',
							);

	 	$Results->cols[] = array(
								'th' => T_('Unlink'),
								'td_start' => '<td class="lastcol center">',
								'td' => action_icon( T_('Delete this link!'), 'unlink',
	                        '%regenerate_url( \'action\', \'link_ID=$link_ID$&amp;action=unlink\')%' ),
							);

		$Results->display();

		printf( '<p>'.T_('Click on a link icon %s below to link an additional file to this item.').'</p>', get_icon( 'link' ) );

		$Form->end_form( );

		// End payload block:
		$AdminUI->dispPayloadEnd();

		$Fileman->forceFM = 1;
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
			&& opener.document.FilesForm.md5_cwd.value == '<?php echo md5($Fileman->cwd); ?>'
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

// "Display/hide Filemanager"
// TODO: do not display this after a successful rename...

$showFilemanager = !$Fileman->fm_mode || $UserSettings->get('fm_forceFM') || $Fileman->forceFM ;

$toggleButtons = array();

if( $Fileman->fm_mode )
{
	if( !$UserSettings->get('fm_forceFM') )
	{ // FM is not forced - link to hide/display
		echo '<div class="panelinfo" id="FM_anchor">';
		echo '[<a '
				.' href="'.$Fileman->getCurUrl( array( 'forceFM' => 1-$Fileman->forceFM ) ).'">'
				.( $showFilemanager ? T_('Hide Filemanager') : T_('Display Filemanager') ).'</a>]';
		echo '</div>';
	}
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

?><div id="filemanmain"><?php

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
 * Revision 1.98  2005/04/28 20:44:18  fplanque
 * normalizing, doc
 *
 * Revision 1.97  2005/04/27 19:05:44  fplanque
 * normalizing, cleanup, documentaion
 *
 * Revision 1.94  2005/04/21 18:01:29  fplanque
 * CSS styles refactoring
 *
 * Revision 1.93  2005/04/21 12:13:42  blueyed
 * doc
 *
 * Revision 1.92  2005/04/20 20:04:12  fplanque
 * visual cleanup of FM
 *
 * Revision 1.91  2005/04/19 16:23:01  fplanque
 * cleanup
 * added FileCache
 * improved meta data handling
 *
 * Revision 1.89  2005/04/15 18:02:58  fplanque
 * finished implementation of properties/meta data editor
 * started implementation of files to items linking
 *
 * Revision 1.88  2005/04/14 19:57:52  fplanque
 * filemanager refactoring & cleanup
 * started implementation of properties/meta data editor
 * note: the whole fm_mode thing is not really desireable...
 *
 * Revision 1.87  2005/04/14 18:34:03  fplanque
 * filemanager refactoring
 *
 * Revision 1.86  2005/04/14 13:14:03  fplanque
 * copy / move / rename is such a bad thing :'(
 *
 * Revision 1.85  2005/04/13 18:31:27  fplanque
 * tried to make copy/move/rename work ...
 *
 * Revision 1.84  2005/04/13 17:48:21  fplanque
 * File manager refactoring
 * storing of file meta data through upload
 * displaying or metadate in previews
 *
 * Revision 1.83  2005/04/12 19:36:30  fplanque
 * File manager cosmetics
 *
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