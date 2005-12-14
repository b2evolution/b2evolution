<?php
/**
 * This file implements the UI controller for file management.
 *
 * NOTE: $Fileman->fm_mode gets used for modes, that allow browsing to some other place or
 *       take other actions. A good example is "upload" - you can delete other files while
 *       in upload mode.
 *       "edit_perms" for example is not a mode, but a action.
 *
 * fplanque>> NOTES:
 * 1) There should be no $Fileman object!
 * 2) The mode as well as other context params should be handled by param() / memorize() / regenerate_url()
 * 3) There should be no modes. Only geeks can understand them. And not all geeks might actually ever find an opportunity to want to use them. All we need is a dir selection tree inside of upload and move.
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
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * The University of North Carolina at Charlotte grants Francois PLANQUE the right to license
 * Jason EDGECOMBE's contributions to this file and the b2evolution project
 * under the GNU General Public License (http://www.opensource.org/licenses/gpl-license.php)
 * and the Mozilla Public License (http://www.opensource.org/licenses/mozilla1.1.php).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
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

if( false )
{	/**
	 * This is ugly, sorry, but I temporarily need this until NuSphere fixes their CodeInsight :'(
	 */
	include('_header.php');
}

/**
 * Load FileManager class
 */
require_once dirname(__FILE__).'/'.$admin_dirout.$core_subdir.'_filemanager.class.php';

$AdminUI->set_path( 'files' );


/**
 * Check global access permissions:
 */
if( !$Settings->get( 'fm_enabled' ) )
{
	die( 'The filemanager is disabled.' );
}
// Check permission:
if( ! $current_User->check_perm( 'files', 'view' ) )
{
	$Messages->add( T_('You do not have permission to view files.') );
	require dirname(__FILE__).'/_menutop.php';
	require dirname(__FILE__).'/_footer.php';
	return;
}


// INIT params:
if( param( 'root_and_path', 'string', '', true ) )
{ // root and path together: decode and override (overriding is especially used in root switch select)
	$root_and_path = unserialize( $root_and_path );
	$root = $root_and_path['root'];
	$path = $root_and_path['path'];
}
else
{
	param( 'root', 'string', NULL, true ); // the root directory from the dropdown box (user_X or blog_X; X is ID - 'user' for current user (default))
	param( 'path', 'string', '/', true );  // the path relative to the root dir
	if( param( 'new_root', 'string', '' )
		&& $new_root != $root )
	{ // We have changed root in the select list
		$root = $new_root;
		$path = '';
	}
}

param( 'order', 'string', NULL, true );
param( 'orderasc', '', NULL, true );
param( 'filterString', '', NULL, true );
param( 'filterIsRegexp', 'integer', NULL, true );
param( 'flatmode', '', NULL, true );
$action = $Request->param_action();


if( $action == 'update_settings' )
{ // Updating user settings from options list
	$UserSettings->set( 'fm_dirsnotattop',   1-param( 'option_dirsattop',        'integer', 0 ) );
	$UserSettings->set( 'fm_permlikelsl',      param( 'option_permlikelsl',      'integer', 0 ) );
	$UserSettings->set( 'fm_getimagesizes',    param( 'option_getimagesizes',    'integer', 0 ) );
	$UserSettings->set( 'fm_recursivedirsize', param( 'option_recursivedirsize', 'integer', 0 ) );
	$UserSettings->set( 'fm_showtypes',        param( 'option_showtypes',        'integer', 0 ) );
	$UserSettings->set( 'fm_showfsperms',      param( 'option_showfsperms',      'integer', 0 ) );
	$UserSettings->set( 'fm_showhidden',       param( 'option_showhidden',       'integer', 0 ) );
	$UserSettings->set( 'fm_forceFM',          param( 'option_forceFM',          'integer', 0 ) );

	if( $UserSettings->dbupdate() )
	{
		$Messages->add( T_('Your user settings have been updated.'), 'success' );
	}

	$action = '';
}


/*
 * Load editable objects:
 */
if( param( 'link_ID', 'integer', NULL, false, false, false ) )
{
	if( ($edited_Link = & $LinkCache->get_by_ID( $link_ID, false )) === false )
	{	// We could not find the link to edit:
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
	if( ($edited_Item = & $ItemCache->get_by_ID( $item_ID, false )) === false )
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
$Fileman = new FileManager( $current_User, 'files.php', $root, $path, $order, $orderasc, $filterString, $filterIsRegexp, $flatmode );

if( ! $Fileman->_ads_list_path )
{ // We have no Root / list path, there was an error. Unset any action or mode.
	$action = '';
	$Fileman->fm_mode = '';
}

// Check actions that toggle Filelist properties:
if( !empty($action) )
{
	switch( $action )
	{
		case 'filter_unset':
			$Fileman->setFilter( false, false );
			$action = '';
			break;

		case 'noflatmode':
			$Fileman->flatmode = false;
			$action = '';
			break;

		case 'flatmode':
			$Fileman->flatmode = true;
			$action = '';
			break;
	}
}

// Load Filelist (with meta data):
$Fileman->load();


/**
 * @var Filelist the selected files
 */
$selected_Filelist = & $Fileman->getFilelistSelected();


switch( $action )
{
	case 'open_in_new_windows':
		// catch JS-only actions (happens when Javascript is disabled on the browser)
		$Messages->add( T_('You have to enable JavaScript to use this feature.'), 'error' );
		break;


	case 'createnew':
		// Check permission:
		$current_User->check_perm( 'files', 'add', true );

		// create new file/dir
		param( 'createnew', 'string', '' ); // 'file', 'dir'
		param( 'createname', 'string', '' );

		$Fileman->createDirOrFile( $createnew, $createname ); // handles messages
		break;


	/*
	case 'send_by_mail':
	{{{ not implemented yet
		// TODO: implement
		if( !$selected_Filelist->count() )
		{
			$Messages->add( T_('Nothing selected.'), 'error' );
			break;
		}

		echo 'TODO: Send selected by mail, query email address..';
		break;
	}}}
	*/


	/*
	case 'download':
	{{{ not implemented yet
		// TODO: provide optional zip formats
		$action_title = T_('Download');

		if( !$selected_Filelist->count() )
		{
			$Messages->add( T_('Nothing selected.'), 'error' );
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
				.'<li>'.implode( "</li>\n<li>", $selected_Filelist->get_array( 'get_prefixed_name' ) )."</li>\n"
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
					.( $selected_Filelist->count_dirs() ?
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

			$arraylist = $selected_Filelist->get_array( 'getname' );

			$options = array (
				'basedir' => $Fileman->get_ads_list_path(),
				'inmemory' => 1,
				'recurse' => 1-$exclude_sd,
			);

			$zipfile = new zip_file( $zipname );
			$zipfile->set_options( $options );
			$zipfile->add_files( $arraylist );
			$zipfile->create_archive();
			$zipfile->download_file();
			exit;
			#$Messages->add( sprintf(T_('Zipfile &laquo;%s&raquo; sent to you!'), $zipname), 'success' );
		}

		break;
	}}}*/


	case 'rename':
		// Rename a file:
		// This will not allow to overwrite existing files, the same way Windows and MacOS do not allow it. Adding an option will only clutter the interface and satisfy geeks only.
		if( ! $current_User->check_perm( 'files', 'edit' ) )
		{ // We do not have permission to edit files
			$Messages->add( T_('You have no permission to edit/modify files.'), 'error' );
			$action = 'list';
			break;
		}

		if( ! $selected_Filelist->count() )
		{ // There is nothing to rename
			$Messages->add( T_('Nothing selected.'), 'error' );
			$action = 'list';
			break;
		}

		param( 'confirm', 'integer', 0 );
		param( 'new_names', 'array', array() );

		// Check params for each file to rename:
		while( $loop_src_File = & $selected_Filelist->get_next() )
		{
			if( ! isset( $new_names[$loop_src_File->get_md5_ID()] ) )
			{ // We have not yet provided a name to rename to...
				$confirm = 0;
				$new_names[$loop_src_File->get_md5_ID()] = $loop_src_File->get_name();
				continue;
			}

			// Check if provided name is okay:
			$new_names[$loop_src_File->get_md5_ID()] = trim(strip_tags($new_names[$loop_src_File->get_md5_ID()]));
			
			if( !$loop_src_File->is_dir() )
			{
				if( $error_filename = validate_filename( $new_names[$loop_src_File->get_md5_ID()] ) ) 
				{ // Not a file name or not an allowed extension
					$confirm = 0;
					$Messages->add( $error_filename , 'error' );
					continue;
				}
			}
			elseif( $error_dirname = validate_dirname( $new_names[$loop_src_File->get_md5_ID()] ) ) 
			{ // Not a directory name
				$confirm = 0;
				$Messages->add( $error_dirname, 'error' );
				continue;
			}
		}

		if( $confirm )
		{ // Rename is confirmed, let's proceed:
			$selected_Filelist->restart();
			while( $loop_src_File = & $selected_Filelist->get_next() )
			{
				$old_name = $loop_src_File->get_name();
				$new_name = $new_names[$loop_src_File->get_md5_ID()];

				if( $new_name == $old_name )
				{ // Name has not changed...
					$Messages->add( sprintf( T_('&laquo;%s&raquo; has not been renamed'), $old_name ), 'note' );
					continue;
				}
				// Perform rename:
				if( ! $loop_src_File->rename_to( $new_name ) )
				{
					$Messages->add( sprintf( T_('&laquo;%s&raquo; could not be renamed to &laquo;%s&raquo;'),
													$old_name, $new_name ), 'error' );
					continue;
				}

				$Messages->add( sprintf( T_('&laquo;%s&raquo; has been successfully renamed to &laquo;%s&raquo;'),
													$old_name, $new_name ), 'success' );
			}

			$action = 'list';
		}

		break;


	case 'delete':
		// Check permission:
		$current_User->check_perm( 'files', 'edit', true );

		// delete a file/dir
		if( ! $selected_Filelist->count() )
		{
			$Messages->add( T_('Nothing selected.'), 'error' );
			$action = 'list';
			break;
		}

		param( 'confirmed', 'integer', 0 );
		param( 'delsubdirs', 'array', array() );

		if( ! $confirmed )
		{
			$action_msg = '<div class="panelinfo">';
			$action_msg .= '<h2>'.T_('Delete file(s)?').'</h2>';

			$action_msg .= '
				<form action="files.php" class="inline">
					<input type="hidden" name="confirmed" value="1" />
					<input type="hidden" name="action" value="delete" />
					'.$Fileman->getFormHiddenSelectedFiles()
					.$Fileman->getFormHiddenInputs()."\n";


			$action_msg .= $selected_Filelist->count() > 1 ?
											T_('Do you really want to delete the following files?') :
											T_('Do you really want to delete the following file?');

			$action_msg .= '
			<ul>
			';

			// So far there is no problem with confirming...
			$can_confirm = true;

			// make sure we have loaded metas for all files in selection!
			$selected_Filelist->load_meta();

			foreach( $selected_Filelist->_entries as $lFile )
			{
				$action_msg .= '<li>'.$lFile->get_prefixed_name();

				/* fplanque>> We cannot actually offer to delete subdirs since we cannot pre-check DB integrity for these...
				if( $lFile->is_dir() )
				{ // This is a directory
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
				{ // There are restrictions:
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
			{ // No integrity problem detected...
				$action_msg .= '
					<input type="submit" value="'.T_('I am sure!').'" class="DeleteButton" />
					</form>';
			}
			else
			{	// Integrity problem detected. Close form without offering to submit:
				$action_msg .= '</form>';
			}

			// Offer to cancel:
			$action_msg .= '<form action="files.php" class="inline">
						'.$Fileman->getFormHiddenInputs().'
						<input type="submit" value="'.T_('CANCEL').'" class="CancelButton" />
					</form>
				</div>';

		}
		else
		{
			$selected_Filelist->restart();
			while( $lFile =& $selected_Filelist->get_next() )
			{
				if( !$Fileman->unlink( $lFile, isset( $delsubdirs[$lFile->get_md5_ID()] ) ) ) // handles Messages
				{
					// TODO: offer file again, allowing to include subdirs..
				}
			}
		}
		break;


	case 'edit_properties':
		// Edit File properties (Meta Data); this starts the File_properties mode: {{{

// fp>> isn't there a global permission check on file:view that applies to the whole page already?
		if( ! $current_User->check_perm( 'files', 'view' ) )
		{ // We do not have permission to edit files
			$Messages->add( T_('You have no permission to view files.'), 'error' );
			$action = 'list';
			break;
		}

		$selectedFile = & $selected_Filelist->getFileByIndex(0);
		// Load meta data:
		$selectedFile->load_meta();

		$Fileman->fm_mode = 'File_properties';
		// }}}
		break;


	case 'update_properties':
		// Update File properties (Meta Data); on success this ends the File_properties mode: {{{

		if( ! $current_User->check_perm( 'files', 'edit' ) )
		{ // We do not have permission to edit files
			$Messages->add( T_('You have no permission to edit/modify files.'), 'error' );
			$action = 'list';
			break;
		}

		$selectedFile = & $selected_Filelist->getFileByIndex(0);
		// Load meta data:
		$selectedFile->load_meta();

		$selectedFile->set( 'title', param( 'title', 'string', '' ) );
		$selectedFile->set( 'alt', param( 'alt', 'string', '' ) );
		$selectedFile->set( 'desc', param( 'desc', 'string', '' ) );

		// Store File object into DB:
		if( $selectedFile->dbsave() )
		{
			$Messages->add( sprintf( T_( 'File properties for &laquo;%s&raquo; have been updated.' ), $selectedFile->get_name() ), 'success' );
		}
		else
		{
			$Messages->add( sprintf( T_( 'File properties for &laquo;%s&raquo; have not changed.' ), $selectedFile->get_name() ), 'note' );
		}

		// Leave special display mode:
		$Fileman->fm_mode = NULL;
		// }}}
		break;


	case 'link':
		// Link File to Item (or other object if extended below):

		// Note: we are not modifying any file here, we're just linking it
		// we only need read perm on file, but we'll need write perm on destination object (to be checked below)

		if( !$selected_Filelist->count() )
		{
			$Messages->add( T_('Nothing selected.'), 'error' );
			break;
		}

		$selectedFile = & $selected_Filelist->getFileByIndex(0);

		if( isset($edited_Item) )
		{
			// TODO: check item EDIT permissions!
			$DB->begin();

			// Load meta data AND MAKE SURE IT IS CREATED IN DB:
			$selectedFile->load_meta( true );

			// Let's make the link!
			$edited_Link = & new Link();
			$edited_Link->set( 'item_ID', $edited_Item->ID );
			$edited_Link->set( 'file_ID', $selectedFile->ID );
			$edited_Link->dbinsert();

			$DB->commit();

			$Messages->add( T_('Selected file has been linked to item.'), 'success' );
		}
		// Plug extensions here!
		// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
		// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
		else
		{	// No Item to link to - end link_item mode.
			$Fileman->fm_mode = NULL;
		}
		break;


	case 'unlink':
		// Unlink File from Item (or other object if extended):

		// Note: we are not modifying any file here, we're just linking it
		// we only need read perm on file, but we'll need write perm on destination object (to be checked below)

		if( !isset( $edited_Link ) )
		{
			break;
		}

		// TODO: get Item (or other object) from Link to check perm

		// TODO: check item/object EDIT permissions!
		// Check that we have permission to edit item:
		// $current_User->check_perm( $perm_name, 'edit', true, $edited_Item->ID );

		// Delete from DB:
		$msg = sprintf( T_('Link from &laquo;%s&raquo; deleted.'), $edited_Link->Item->dget('title') );
		$edited_Link->dbdelete( true );
		unset( $edited_Link );
		$Messages->add( $msg, 'success' );
		break;


	case 'edit_perms':
		// Edit file or directory permissions: {{{
		if( ! $current_User->check_perm( 'files', 'edit' ) )
		{ // We do not have permission to edit files
			$Messages->add( T_('You have no permission to edit/modify files.'), 'error' );
			$action = 'list';
			break;
		}

		if( ! $selected_Filelist->count() )
		{
			$Messages->add( T_('Nothing selected.'), 'error' );
			$action = 'list';
			break;
		}


		param( 'perms', 'array', array() );
		param( 'edit_perms_default' ); // default value when multiple files are selected
		param( 'use_default_perms', 'array', array() ); // array of file IDs that should be set to default

		if( count( $use_default_perms ) && $edit_perms_default === '' )
		{
			$Request->param_error( 'edit_perms_default', T_('You have to give a default permission!') );
			break;
		}

		// form params
		$perms_read_readonly = is_windows();
		$field_options_read_readonly = array(
				array( 'value' => 444, 'label' => T_('Read-only') ),
				array( 'value' => 666, 'label' => T_('Read and write') ) );
		$more_than_one_selected_file = ( $selected_Filelist->count() > 1 );

		if( count( $perms ) || count( $use_default_perms ) )
		{ // New permissions given, change them
			$selected_Filelist->restart();
			while( $l_File = & $selected_Filelist->get_next() )
			{
				if( in_array( $l_File->get_md5_ID(), $use_default_perms ) )
				{ // use default
					$chmod = $edit_perms_default;
				}
				elseif( !isset($perms[ $l_File->get_md5_ID() ]) )
				{ // happens for an empty text input or when no radio option is selected
					$Messages->add( sprintf( T_('Permissions for &laquo;%s&raquo; have not been changed.'), $l_File->get_name() ), 'note' );
					continue;
				}
				else
				{ // provided for this file
					$chmod = $perms[ $l_File->get_md5_ID() ];
				}

				$oldperms = $l_File->get_perms( 'raw' );
				$newperms = $l_File->chmod( $chmod );

				if( $newperms === false )
				{
					$Messages->add( sprintf( T_('Failed to set permissions on &laquo;%s&raquo; to &laquo;%s&raquo;.'), $l_File->get_name(), $chmod ), 'error' );
				}
				else
				{
					// Success, remove the file from the list of selected files:
					$selected_Filelist->remove( $l_File );

					if( $newperms === $oldperms )
					{
						$Messages->add( sprintf( T_('Permissions for &laquo;%s&raquo; have not been changed.'), $l_File->get_name() ), 'note' );
					}
					else
					{
						$Messages->add( sprintf( T_('Permissions for &laquo;%s&raquo; changed to &laquo;%s&raquo;.'), $l_File->get_name(), $l_File->get_perms() ), 'success' );
					}
				}
			}
		}

		if( !$selected_Filelist->count() )
		{ // No file left selected... (everything worked fine)
			$action = 'list';
		}
		// }}}
		break;


	case 'default':
		// ------------------------
		// default action (view):
		// ------------------------
		if( !$selected_Filelist->count() )
		{
			$Messages->add( T_('Nothing selected.'), 'error' );
			break;
		}

		$selectedFile = & $selected_Filelist->getFileByIndex(0);

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



/**
 * The top menu
 */
require dirname(__FILE__).'/_menutop.php';

// Temporary fix:
// Messages have already bben displayed:
$Messages->clear( 'all' );


// echo 'fm mode:'.$Fileman->fm_mode;

switch( $Fileman->fm_mode )
{ // handle modes {{{

	case 'file_upload':
		// {{{
		/*
		 * upload mode
		 */
		// Check permissions:
		if( ! $Settings->get('upload_enabled') )
		{ // Upload is globally disabled
			$Messages->add( T_('Upload is disabled.'), 'error' );
			$Fileman->fm_mode = NULL;
			break;
		}

		if( ! $current_User->check_perm( 'files', 'add' ) )
		{ // We do not have permission to add files
			$Messages->add( T_('You have no permission to add/upload files.'), 'error' );
			$Fileman->fm_mode = NULL;
			break;
		}

		// Quick mode means "just upload and leave mode when successful"
		$Request->param( 'upload_quickmode', 'integer', 0 );

		$LogUpload = new Log( 'error' );

		/**
		 * @var array Remember failed files (and the error messages)
		 */
		$failedFiles = array();

		// Process uploaded files:
		if( isset($_FILES) && count( $_FILES ) )
		{ // Some files have been uploaded:
			param( 'uploadfile_title', 'array', array() );
			param( 'uploadfile_alt', 'array', array() );
			param( 'uploadfile_desc', 'array', array() );
			param( 'uploadfile_name', 'array', array() );

			foreach( $_FILES['uploadfile']['name'] as $lKey => $lName )
			{
				if( empty( $lName ) )
				{ // No file name
					if( $upload_quickmode )
					{
						$Messages->add( T_( 'Please select a local file to upload.' ) );
					}
					elseif( !empty( $uploadfile_title[$lKey] )
					     || !empty( $uploadfile_alt[$lKey] )
					     || !empty( $uploadfile_desc[$lKey] )
					     || !empty( $uploadfile_name[$lKey] ) )
					{ // User specified params but NO file!!!
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
				{ // PHP has detected an error!:
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
				{ // Ensure that a malicious user hasn't tried to trick the script into working on files upon which it should not be working.
					$failedFiles[$lKey] = T_('The file does not seem to be a valid upload!');
					// Abort upload for this file:
					continue;
				}

				// Use new name on server if specified:
				$newName = !empty( $uploadfile_name[ $lKey ] ) ? $uploadfile_name[ $lKey ] : $lName;

				if( $error_filename = validate_filename( $newName ) )
				{ // Not a file name or not an allowed extension
					$confirm = 0;
					$Messages->add( $error_filename , 'error' );
					continue;
				}

				// Get File object for requested target location:
				$newFile = & $FileCache->get_by_root_and_path( $Fileman->get_root_type(), $Fileman->get_root_ID(), $Fileman->get_rds_list_path().$newName, true );

				if( $newFile->exists() )
				{ // The file already exists in the target location!
					// TODO: Rename/Overwriting (save as filename_<numeric_extension> and provide interface to confirm, rename or overwrite)
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

				// change to default chmod settings
				if( $newFile->chmod( NULL ) === false )
				{ // add a note, this is no error!
					$Messages->add( sprintf( T_('Could not change permissions of &laquo;%s&raquo; to default chmod setting.'), $newFile->get_name() ), 'note' );
				}

				$success_msg = sprintf( T_('The file &laquo;%s&raquo; has been successfully uploaded.'), $newFile->get_name() );
				if( $mode == 'upload' )
				{
					// TODO: Add plugin hook to allow generating JS insert code(s)
					$img_tag = format_to_output( $newFile->get_tag(), 'formvalue' );
					$success_msg .=
						'<ul>'
							.'<li>'.T_("Here's the code to display it:").' <input type="text" value="'.$img_tag.'" /></li>'
							.'<li><a href="#" onclick="if( window.focus && window.opener ){ window.opener.focus(); textarea_replace_selection( window.opener.document.post.content, \''.format_to_output( $newFile->get_tag(), 'formvalue' ).'\' ); } return false;">'.T_('Add the code to your post !').'</a></li>'
						.'</ul>';
				}

				$LogUpload->add( $success_msg, 'success' );

				// Refreshes file properties (type, size, perms...)
				$newFile->load_properties();

				// Store extra info about the file into File Object:
				if( isset( $uploadfile_title[$lKey] ) )
				{ // If a title text has been passed... (does not happen in quick upload mode)
					$newFile->set( 'title', trim( strip_tags($uploadfile_title[$lKey])) );
				}
				if( isset( $uploadfile_alt[$lKey] ) )
				{ // If an alt text has been passed... (does not happen in quick upload mode)
					$newFile->set( 'alt', trim( strip_tags($uploadfile_alt[$lKey])) );
				}
				if( isset( $uploadfile_desc[$lKey] ) )
				{ // If a desc text has been passed... (does not happen in quick upload mode)
					$newFile->set( 'desc', trim( strip_tags($uploadfile_desc[$lKey])) );
				}

				// Store File object into DB:
				$newFile->dbsave();

				// Tell the filamanager about the new file:
				$Fileman->add( $newFile );
			}

			if( $upload_quickmode && !$failedFiles )
			{ // we're quick uploading and have no failed files, leave the mode
				$Fileman->fm_mode = NULL;
			}
		}

		// Upload dialog:
		if( ! is_null($Fileman->fm_mode) )
		{	// we haven't just exited the upload mode...
			require dirname(__FILE__).'/_files_upload.inc.php';
		}

		// }}}
		break;


	case 'File_properties':			// TODO: make all lowercase
		if( empty($selectedFile) )
		{
			$Fileman->fm_mode = NULL;
		}

		// File properties (Meta data) dialog:
		require dirname(__FILE__).'/_file_properties.inc.php';
		break;


	case 'file_copy':
	case 'file_move':
		// ------------------------
		// copy/move a file:
		// ------------------------
		/*
		 * fplanque>> This whole thing is flawed:
		 * 1) only geeks can possibly like to use the same interface for renaming, moving and copying
		 * 2) even the geeky unix commands won't pretend copying and moving are the same thing. They are not!
		 *    Only moving and renaming are similar, and again FOR GEEKS ONLY.
		 * 3) The way this works it breaks the File meta data (I'm working on it).
		 * 4) For Move and Copy, this should use a "destination directory tree" on the right (same as for upload)
		 * 5) Given all the reasons above copy, move and rename should be clearly separated into 3 different interfaces.
		 *
		 * blueyed>> it was never meant to only use a single interface. The original mode
		 *   'file_cmr' was just a mode to handle it internally easier/more central.
		 *   'copy' is just 'move and keep the source', while 'rename' is 'move in the same dir'
		 *
		 */

		/*
		TODO: On error notes use prefixed names, if the roots differ.
		      Something like $Fileman->get_names_realtive_to( $a_File, $b_File, $root_type, $root_ID, $rel_path )
		      that returns an array containing the name of $a_File and $b_File relative to the Root path given as
		      param 3, 4, 5.
		      This would allow to say "Copied «users/admin/test_me.jpg» to «test_me.jpg»." rather than just
		      "Copied «test_me.jpg» to «test_me.jpg».".
			// fp>> I don't really understand this (probably missing a verb) but I do think that extending the Fileman object is not the right direction to go on the long term
			// blueyed>> Tried to make it clearer. If it wasn't a Filemanager method, it has to be a function or
			//   a method of the File class. IMHO it should be a method of the (to be killed) Filemanager object.
			// fp>> Okay. It should *definitely* be a method of the File object and we should ask for ONE file at a time. Any question about 'where is the file?' (what/where/when/who, etc) should be asked to the File object itself.
		*/

		if( ! $current_User->check_perm( 'files', 'edit' ) )
		{ // We do not have permission to edit files
			$Messages->add( T_('You have no permission to edit/modify files.'), 'error' );
			$Fileman->fm_mode = NULL;
			break;
		}

		if( !$Fileman->SourceList->count() )
		{
			$Messages->add( T_('No source files!'), 'error' );
			$Fileman->fm_mode = NULL;
			break;
		}

		param( 'confirm', 'integer', 0 );
		param( 'new_names', 'array', array() );
		param( 'overwrite', 'array', array() );

		// Check params for each file to rename:
		while( $loop_src_File = & $Fileman->SourceList->get_next() )
		{
			if( ! $loop_src_File->exists() )
			{ // this can happen on reloading the page
				$Fileman->SourceList->remove($loop_src_File);
				continue;
			}
			if( ! isset( $new_names[$loop_src_File->get_md5_ID()] ) )
			{ // We have not yet provided a name to rename to...
				$confirm = 0;
				$new_names[$loop_src_File->get_md5_ID()] = $loop_src_File->get_name();
				continue;
			}

			// Check if provided name is okay:
			$new_names[$loop_src_File->get_md5_ID()] = trim(strip_tags($new_names[$loop_src_File->get_md5_ID()]));
			
			if( !$loop_src_File->is_dir() )
			{
				if( $error_filename = validate_filename( $new_names[$loop_src_File->get_md5_ID()] ) ) 
				{ // Not a file name or not an allowed extension
					$confirm = 0;
					$Messages->add( $error_filename , 'error' );
					continue;
				}
			}
			elseif( $error_dirname = validate_dirname( $new_names[$loop_src_File->get_md5_ID()] ) )
			{ // Not a directory name
				$confirm = 0;
				$Messages->add( $error_dirname, 'error' );
				continue;
			}

			// Check if destination file exists:
			if( ($dest_File = & $FileCache->get_by_root_and_path( $Fileman->get_root_type(), $Fileman->get_root_ID(), $Fileman->get_rds_list_path().$new_names[$loop_src_File->get_md5_ID()] ))
							&& $dest_File->exists() )
			{ // Target exists
				if( $dest_File === $loop_src_File )
				{
					$Request->param_error( 'new_names['.$loop_src_File->get_md5_ID().']', T_('Source and target files are the same. Please choose another name or directory.') );
					$confirm = 0;
					continue;
				}

				if( ! isset( $overwrite[$loop_src_File->get_md5_ID()] ) )
				{ // We have not yet asked to overwrite:
					$Request->param_error( 'new_names['.$loop_src_File->get_md5_ID().']', sprintf( T_('The file &laquo;%s&raquo; already exists.'), $dest_File->get_rdfp_rel_path() ) );
					$overwrite[$loop_src_File->get_md5_ID()] = 0;
					$confirm = 0;
					continue;
				}

				// We have asked to overwite...
				if( $Fileman->fm_mode == 'file_copy' )
				{ // We are making a copy: no problem, we'll recycle the file ID anyway.
					continue;
				}

				// We are moving, we'll need to unlink the target file and drop it's meta data:
				// Check if there are delete restrictions on this file:
				$dest_File->check_relations( 'delete_restrictions' );

				if( $Messages->count('restrict') )
				{ // There are restrictions:
					// TODO: work on a better output display here...
					$Request->param_error( 'new_names['.$loop_src_File->get_md5_ID().']', sprintf( T_('Cannot overwrite the file &laquo;%s&raquo; because of the following relations'), $dest_File->get_rdfp_rel_path() ) );

					$confirm = 0;
					break;	// stop whole file list processing
				}
			}
		}

		if( $confirm && $Fileman->SourceList->count() )
		{ // Copy/move is confirmed (and we still have files to copy/move), let's proceed:

			// Loop through files:
			$Fileman->SourceList->restart();
			while( $loop_src_File = & $Fileman->SourceList->get_next() )
			{
				// Get a pointer on dest file
				$dest_File = & $FileCache->get_by_root_and_path( $Fileman->get_root_type(), $Fileman->get_root_ID(), $Fileman->get_rds_list_path().$new_names[$loop_src_File->get_md5_ID()] );

				if( $Fileman->fm_mode == 'file_copy' )
				{ // COPY

					// Do the copy
					if( $Fileman->copy_File( $loop_src_File, $dest_File ) )
					{ // Success:
						$Messages->add( sprintf( T_('Copied &laquo;%s&raquo; to &laquo;%s&raquo;.'),
																		$loop_src_File->get_rdfp_rel_path(), $dest_File->get_rdfp_rel_path() ), 'success' );
						$Fileman->SourceList->remove( $loop_src_File );
					}
					else
					{ // Failure:
						$Request->param_error( 'new_names['.$loop_src_File->get_md5_ID().']', sprintf( T_('Could not copy &laquo;%s&raquo; to &laquo;%s&raquo;.'),
																		$loop_src_File->get_rdfp_rel_path(), $dest_File->get_rdfp_rel_path() ) );
					}
				}
				elseif( $Fileman->fm_mode == 'file_move' )
				{ // MOVE
					// NOTE: DB integrity is handled by the File object itself
					$DB->begin();

					if( isset( $overwrite[$loop_src_File->get_md5_ID()] )
							&& $overwrite[$loop_src_File->get_md5_ID()] )
					{ // We want to overwrite, let's unlink the old file:
						if( ! $Fileman->unlink( $dest_File, false ) )	// Will NOT delete recursively
						{ // Unlink failed:
							$DB->rollback();
							// Move on to next file:
							continue;
						}
					}

					// Do the move:
					$rdfp_oldpath = $loop_src_File->get_rdfp_rel_path();
					$rdfp_newpath = $Fileman->get_rds_list_path().$new_names[$loop_src_File->get_md5_ID()];

					if( $Fileman->move_File( $loop_src_File, $Fileman->get_root_type(), $Fileman->get_root_ID(), $rdfp_newpath ) )
					{ // successfully moved
						$Messages->add( sprintf( T_('Moved &laquo;%s&raquo; to &laquo;%s&raquo;.'), $rdfp_oldpath, $rdfp_newpath ), 'success' );

						// We remove the file from the source list, after refreshing the cache
						$Fileman->SourceList->update_caches();
						$Fileman->SourceList->remove( $loop_src_File );
					}
					else
					{ // move failed
						$Request->param_error( 'new_names['.$loop_src_File->get_md5_ID().']', sprintf( T_('Could not move &laquo;%s&raquo; to &laquo;%s&raquo;.'), $rdfp_oldpath, $rdfp_newpath ) );
						// Note: we do not rollback, since unlinking is already done on disk :'(
					}

					$DB->commit();
				}
				else debug_die( 'Unhandled file copy/move mode' );
			}
		}

		if( $Fileman->SourceList->count() )
		{ // There are still uncopied/unmoved files, we want the file manager in this mode:
			$Fileman->forceFM = 1;
		}
		else
		{
			// Leave mode:
			$Fileman->fm_mode = NULL;
		}
		break;


	case 'link_item':
		// We want to link file(s) to an item:

		// TODO: check perms. ??

		if( !isset($edited_Item) )
		{ // No Item to link to...
			$Fileman->fm_mode = NULL;
			break;
		}

		// TODO: check EDIT permissions!

		// Links dialog:
		$AdminUI->disp_payload_begin();
		require dirname(__FILE__).'/_files_links.inc.php';
		$AdminUI->disp_payload_end();

		// we want the file manager in this mode:
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
			&& opener.document.FilesForm.md5_cwd.value == '<?php echo md5($Fileman->get_ads_list_path()); ?>'
		)
	{
		opener.document.getElementById( 'fm_reloadhint' ).style.display =
			opener.document.FilesForm.md5_filelist.value == '<?php echo $Fileman->md5_checksum(); ?>'
			? 'none'
			: 'inline';
	}
	// -->
</script>


<?php


// TODO: remove this!
// Output errors, notes and action messages
if( $Messages->count( 'all' ) )
{
	$Messages->display( '', '', true, 'all' );
}


/*
 * Display payload:
 */
switch( $action )
{
	case 'rename':
		// Rename files dialog:
		$AdminUI->disp_payload_begin();
		require dirname(__FILE__).'/_files_rename.form.php';
		$AdminUI->disp_payload_end();
		break;


	case 'edit_perms':
		// Filesystem permissions for specific files
		$AdminUI->disp_payload_begin();
		require dirname(__FILE__).'/_files_permissions.form.php';
		$AdminUI->disp_payload_end();
		break;

	default:
		// Deferred action message:
		if( isset($action_title) )
		{
			echo "\n<h2>$action_title</h2>\n";
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
}

// FM modes displays:
switch( $Fileman->fm_mode )
{
	case 'file_copy':
	case 'file_move':
		// CMR dialog:
		require dirname(__FILE__).'/_files_cmr.inc.php';
		break;
}


// "Display/hide Filemanager"
// TODO: do not display this after a successful rename...
$showFilemanager = !$Fileman->fm_mode || $UserSettings->get('fm_forceFM') || $Fileman->forceFM ;

$toggleButtons = array();

if( $Fileman->fm_mode )
{
	if( ! $Fileman->forceFM )
	{ // FM is not forced (by user or by special function) - link to hide/display
		echo '<div class="panelinfo" id="FM_anchor">';
		echo '[<a '
				.' href="'.$Fileman->getCurUrl( array( 'forceFM' => 1-$Fileman->forceFM ) ).'">'
				.( $showFilemanager ? T_('Hide Filemanager') : T_('Display Filemanager') ).'</a>]';
		echo '</div>';
	}
}

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
 * {{{ Revision log:
 * $Log$
 * Revision 1.149  2005/12/14 19:36:15  fplanque
 * Enhanced file management
 *
 * Revision 1.148  2005/12/12 19:44:09  fplanque
 * Use cached objects by reference instead of copying them!!
 *
 * Revision 1.147  2005/12/12 17:57:22  fplanque
 * fixed bug with delete (when some links prevent deletion)
 *
 * Revision 1.146  2005/12/12 16:40:17  fplanque
 * fixed quick upload
 *
 * Revision 1.145  2005/12/10 03:02:50  blueyed
 * Quick upload mode merged from post-phoenix
 *
 * Revision 1.144  2005/12/06 00:01:56  blueyed
 * Unset action/fm_mode when no FileRoot available.
 *
 * Revision 1.142  2005/12/04 15:49:20  blueyed
 * More descriptive error message when no perms for 'files'/'view'.
 *
 * Revision 1.141  2005/11/27 08:48:41  blueyed
 * fix editing file properties (there were two 'case's)..
 *
 * Revision 1.140  2005/11/27 06:36:01  blueyed
 * Use deprecated LogUpload for phoenix, because it's not 1-2-3-4 scheme here.
 *
 * Revision 1.139  2005/11/27 06:16:02  blueyed
 * Echo code to display uploaded file in 'upload' mode and allow to insert it through JS (0.9.1 behaviour).
 *
 * Revision 1.138  2005/11/24 18:32:10  blueyed
 * Use $Fileman to get (deprecated) chmod defaults, not $Filemanager, which does not exist.. :/
 *
 * Revision 1.137  2005/11/24 17:56:20  blueyed
 * chmod() the uploaded file
 *
 * Revision 1.135  2005/11/24 13:23:57  blueyed
 * debug_die() for invalid action (only if actionArray given [and invalid])
 *
 * Revision 1.134  2005/11/24 08:54:42  blueyed
 * debug_die() for invalid action; doc
 *
 * Revision 1.131  2005/11/22 04:41:38  blueyed
 * Fix permissions editing again
 *
 * Revision 1.130  2005/11/21 18:33:19  fplanque
 * Too many undiscussed changes all around: Massive rollback! :((
 * As said before, I am only taking CLEARLY labelled bugfixes.
 *
 * Revision 1.121  2005/11/14 16:43:43  blueyed
 * FGix actionArray
 *
 * Revision 1.120  2005/10/28 20:08:46  blueyed
 * Normalized AdminUI
 *
 * Revision 1.119  2005/10/28 02:37:37  blueyed
 * Normalized AbstractSettings API
 *
 * Revision 1.117  2005/09/22 21:35:26  blueyed
 * Fixed another "Only variables can be passed by reference" notice (php4) / fatal error (php5)
 *
 * Revision 1.116  2005/08/12 17:37:14  fplanque
 * cleanup
 *
 * Revision 1.115  2005/07/26 18:56:21  fplanque
 * minor
 *
 * Revision 1.114  2005/07/26 18:50:48  fplanque
 * enhanced attached file handling
 *
 * Revision 1.113  2005/06/03 20:14:38  fplanque
 * started input validation framework
 *
 * Revision 1.112  2005/06/03 15:12:32  fplanque
 * error/info message cleanup
 *
 * Revision 1.110  2005/05/24 15:26:51  fplanque
 * cleanup
 *
 * Revision 1.109  2005/05/17 19:26:06  fplanque
 * FM: copy / move debugging
 *
 * Revision 1.108  2005/05/16 15:17:12  fplanque
 * minor
 *
 * Revision 1.107  2005/05/13 18:41:28  fplanque
 * made file links clickable... finally ! :P
 *
 * Revision 1.106  2005/05/13 16:49:17  fplanque
 * Finished handling of multiple roots in storing file data.
 * Also removed many full paths passed through URL requests.
 * No full path should ever be seen by the user (only the admins).
 *
 * Revision 1.105  2005/05/12 18:39:24  fplanque
 * storing multi homed/relative pathnames for file meta data
 *
 * Revision 1.104  2005/05/11 15:58:30  fplanque
 * cleanup
 *
 * Revision 1.103  2005/05/10 18:38:15  fplanque
 * cleaned up log message display (part 1)
 *
 * Revision 1.102  2005/05/09 16:09:38  fplanque
 * implemented file manager permissions through Groups
 *
 * Revision 1.101  2005/05/06 20:04:47  fplanque
 * added contribs
 * fixed filemanager settings
 * Removed checking against browser provided mime types (very unsecure!)
 *
 * Revision 1.100  2005/05/04 19:40:40  fplanque
 * cleaned up file settings a little bit
 *
 * Revision 1.99  2005/04/29 18:49:32  fplanque
 * Normalizing, doc, cleanup
 *
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
 * }}}
 */
?>
