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
 *
 * @version $Id$
 *
 * @todo thumbnail view
 * @todo PHPInfo (special permission)
 * @todo directly run PHP-code (eval)
 */

/**
 * Load config, init and get {@link $mode mode param}
 */
require_once dirname(__FILE__).'/_header.php';
/**
 * Load FileManager class
 */
require_once dirname(__FILE__).'/'.$admin_dirout.$core_subdir.'_filemanager.class.php';

$admin_tab = 'files';
$admin_pagetitle = T_('Filemanager').' (beta)';

param( 'path', 'string', '' );       // the path relative to the root dir
param( 'action', 'string', '' );     // 3.. 2.. 1.. action :)
param( 'selaction', 'string', '' );  // action for selected files/dirs

param( 'file', 'string', '' );       // selected file
param( 'order', 'string', NULL );
param( 'asc', '', NULL );
param( 'filterString', '', NULL );
param( 'filterIsRegexp', 'integer', NULL );
param( 'flatmode', '', NULL );

param( 'root', 'string', NULL );     // the root directory from the dropdown box (user_X or blog_X; X is ID - 'user' for current user (default))


if( $current_User->login != 'demouser' && $current_User->level < 10 )
{ // allow demouser, but noone else below level 10
	echo 'The filemanager is still beta. You need user level 10 to play with this.';
	return;
}

if( $action == 'update_settings' )
{ // updating user settings
	$UserSettings->set( 'fm_dirsnotattop',   1-param( 'option_dirsattop',        'integer', 0 ) );
	$UserSettings->set( 'fm_permlikelsl',      param( 'option_permlikelsl',      'integer', 0 ) );
	$UserSettings->set( 'fm_getimagesizes',    param( 'option_getimagesizes',    'integer', 0 ) );
	$UserSettings->set( 'fm_recursivedirsize', param( 'option_recursivedirsize', 'integer', 0 ) );
	$UserSettings->set( 'fm_showhidden',       param( 'option_showhidden',       'integer', 0 ) );

	if( $UserSettings->updateDB() )
	{
		$Messages->add( T_('Your user settings have been updated.'), 'note' );
	}
}


/**
 * Filemanager object to work with
 */
$Fileman = new FileManager( $current_User, 'files.php', $root, $path, $order, $asc,
														$filterString, $filterIsRegexp, $flatmode );


if( !empty($file) )
{ // a file is given as parameter
	$curFile =& $Fileman->getFileByFilename( $file );

	if( !$curFile )
	{
		$Fileman->Messages->add( sprintf( T_('File [%s] could not be accessed!'), $file ) );
	}
}
else
{
	$curFile = false;
}


if( $action == '' && $file != '' && $curFile )
{ // a file is selected/clicked, default action
	{{{ // do the default action

		?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
		<head>
			<title><?php echo $file.' :: '.$app_name.' '.T_('Filemanager') ?></title>
			<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
			<link href="variation.css" rel="stylesheet" type="text/css" title="Variation" />
			<link href="desert.css" rel="alternate stylesheet" type="text/css" title="Desert" />
			<link href="legacy.css" rel="alternate stylesheet" type="text/css" title="Legacy" />
			<?php if( is_file( dirname(__FILE__).'/custom.css' ) ) { ?>
			<link href="custom.css" rel="alternate stylesheet" type="text/css" title="Custom" />
			<?php } ?>
			<script type="text/javascript" src="styleswitcher.js"></script>
			<link href="fileman.css" rel="stylesheet" type="text/css" />
		</head>

		<body><!-- onclick="javascript:window.close()" title="<?php echo T_('Click anywhere in this window to close it.') ?>">-->

		<?php
			if( isImage( $file ) )
			{ // display image file
				?>
				<div class="center">
					<img alt="<?php echo T_('The selected image') ?>" class="framed" src="<?php echo $Fileman->getFileUrl( $curFile ) ?>" <?php echo $curFile->getImageSize( 'string' ) ?> />
				</div>
				<?php
			}
			elseif( ($buffer = @file( $curFile->getPath( true ) )) !== false )
			{{{ // display raw file
				param( 'showlinenrs', 'integer', 0 );

				// TODO: check if new window was opened and provide close X in case
				/*<a href="javascript:window.close()"><img class="center" src="<?php echo $admin_url.'img/xross.gif' ?>" width="13" height="13" alt="[X]" title="<?php echo T_('Close this window') ?>" /></a>*/

				echo '<div class="fileheader">';
				echo T_('File').': '.$file.'<br />';

				if( !count($buffer) )
				{
					echo '</div> ** '.T_('empty file').' ** ';
				}
				else
				{
					printf( T_('%d lines'), count($buffer) ).'<br />';
					$linenr_width = strlen( count($buffer)+1 );

					?>
					<noscript type="text/javascript">
						<a href="<?php echo $Fileman->getCurUrl().'&amp;file='.$file.'&amp;showlinenrs='.(1-$showlinenrs).'">'
							.( $showlinenrs ? T_('hide line numbers') : T_('show line numbers') ).'</a>';
					?>
					</noscript>
					<script type="text/javascript">
					<!--
					document.write('<a id="togglelinenrs" href="javascript:toggle_linenrs()">toggle</a>');
					//-->
					</script>

					</div>
					<pre class="rawcontent"><?php
					foreach( $buffer as $linenr => $line )
					{
						echo '<span name="linenr" class="linenr">';
						if( $showlinenrs ) echo ' '.str_pad($linenr+1, $linenr_width, ' ', STR_PAD_LEFT).' ';
						echo '</span>'.htmlspecialchars( str_replace( "\t", '  ', $line ) );  // TODO: customize tab-width
					}

					?>

					<script type="text/javascript">
					<!--
					showlinenrs = true;
					toggle_linenrs();
					function toggle_linenrs()
					{
						if( showlinenrs )
						{
							var replace = document.createTextNode('<?php echo /* TRANS: Warning this is a javascript string */ T_('show line numbers') ?>');
							showlinenrs = false;
							var text = document.createTextNode( '' );
							for( var i = 0; i<document.getElementsByTagName("span").length; i++ )
							{
								if( document.getElementsByTagName("span")[i].hasChildNodes() )
									document.getElementsByTagName("span")[i].firstChild.data = '';
								else
								{
									document.getElementsByTagName("span")[i].appendChild( text );
								}
							}
						}
						else
						{
							var replace = document.createTextNode('<?php echo /* TRANS: Warning this is a javascript string */ T_('hide line numbers') ?>');
							showlinenrs = true;
							for( var i = 0; i<document.getElementsByTagName("span").length; i++ )
							{
								var text = String(i+1);
								var upto = <?php echo $linenr_width ?>-text.length;
								for( var j=0; j<upto; j++ ){ text = ' '+text; }
								if( document.getElementsByTagName("span")[i].hasChildNodes() )
									document.getElementsByTagName("span")[i].firstChild.data = ' '+text+' ';
								else
									document.getElementsByTagName("span")[i].appendChild( document.createTextNode( ' '+text+' ' ) );
							}
						}

						document.getElementById('togglelinenrs').replaceChild(replace, document.getElementById( 'togglelinenrs' ).firstChild);
					}
					-->
					</script>
					<?php

				}
				?></pre>

				<?php
			}}}
			else
			{
				printf( '<p class="error">'.T_('File [%s] could not be accessed!').'</p>', $file );
			}
			?>
		</body>
	</html>
	<?php
	exit;
	}}}
}


if( $selaction != '' )
{{{ // Actions for selected files
	param( 'selectedfiles', 'array', array() );

	if( !count( $selectedfiles ) )
	{
		$Fileman->Messages->add( T_('Nothing selected.') );
	}
	else
	{
		$curFiles = array();
		foreach( $selectedfiles as $file )
		{
			$selectedFiles[] = $Fileman->getFileByFilename( $file );
		}

		switch( $selaction )
		{
			case T_('Send by mail'):
				echo 'todo: Send selected by mail, query email address..';
				break;

			case T_('Download'):
				// TODO: provide optional zip formats
				param( 'zipname', 'string', '' );
				param( 'exclude_sd', 'integer', 0 );

				if( empty($zipname) )
				{
					$msg_action = '
					<p>
					'.T_('You want to download:').'<ul>';

					$atLeastOneDir = false;
					foreach( $selectedFiles as $lFile )
					{
						if( $lFile->isDir() )
						{
							$msg_action .= sprintf('<li>'.T_('Directory [%s]')."</li>\n", $lFile->getName());
							$atLeastOneDir = true;
						}
						else $msg_action .= sprintf('<li>'.T_('File [%s]')."</li>\n", $lFile->getName());
					}

					$msg_action .= '
					</p>
					</div>
					<div class="panelblock">
					<form action="files.php" class="fform" method="post">
					<fieldset>
						<legend>'.T_('Download options').'</legend>';

						foreach( $selectedfiles as $file )
						{
							$msg_action .= '<input type="hidden" name="selectedfiles[]" value="'.format_to_output( $file, 'formvalue' )."\" />\n";
						}

						$msg_action .= $Fileman->getFormHiddenInputs()."\n"
												.form_text( 'zipname', '', 20, T_('Archive filename'), T_("This is the file's name that will get sent to you."), 80, '', 'text', false )."\n"
												.( $atLeastOneDir ?
														form_checkbox( 'exclude_sd', $exclude_sd, T_('Exclude subdirectories'), T_('This will exclude subdirectories of selected directories.'), '', false )."\n" :
														'' )
												.'<div class="input"><input type="submit" name="selaction" value="'.T_('Download').'" /></div>
					</fieldset>
					</form>';
				}
				else
				{ // Downloading
					require( dirname(__FILE__).'/'.$admin_dirout.$lib_subdir.'_zip_archives.php' );

					$options = array (
						'basedir' => $Fileman->getCwd(),
						'inmemory' => 1,
						'recurse' => 1-$exclude_sd,
					);

					$zipfile = new zip_file( $zipname );
					$zipfile->set_options( $options );

					$arraylist = array();
					foreach( $selectedFiles as $File )
					{
						$arraylist[] = $File->getName();
					}
					$zipfile->add_files( $arraylist );

					$zipfile->create_archive();

					#header('Content-length: ' . filesize($path));
					$zipfile->download_file();
					exit;
					#$Fileman->Messages->add( sprintf(T_('Zipfile [%s] sent to you!'), $selectedfiles[0]), 'note' );

				}

				break;

			case T_('Delete'):
				// TODO: extra confirmation

				foreach( $selectedfiles as $file )
				{
					$Fileman->Messages->add( sprintf('Would delete [%s]', $file).'..', 'note' );

				}

				break;
		}
	}
}}}


switch( $action ) // {{{ (we catched empty action before)
{
	case 'createnew':  // {{{ create new file/dir
		param( 'createnew', 'string', '' ); // 'file', 'dir'
		param( 'createname', 'string', '' );

		$Fileman->createDirOrFile( $createnew, $createname );
		// }}}
		break;


	case 'delete': // {{{ delete a file/dir, TODO: checkperm!
		if( !$curFile )
		{
			break;
		}
		param( 'confirmed', 'integer', 0 );
		if( !$confirmed )
		{
			$msg_action = '<p>'.sprintf( T_('Do you really want to delete [%s]?'), $file ).'</p>'
			.'
			<form action="" class="inline">
				<input type="hidden" name="confirmed" value="1" />
				<input type="hidden" name="action" value="delete" />
				<input type="hidden" name="file" value="'.format_to_output( $file, 'formvalue' ).'" />
				'.$Fileman->getFormHiddenInputs().'
				<input type="submit" value="'.T_('I am sure!').'" class="DeleteButton" />
			</form>
			<form action="" class="inline">
				'.$Fileman->getFormHiddenInputs().'
				<input type="submit" value="'.T_('CANCEL').'" class="CancelButton" />
			</form>
			';
		}
		else
		{
			$filename = $curFile->getName();
			if( !$Fileman->unlink( $curFile ) )
			{
				$Fileman->Messages->add( sprintf( ( $curFile->isDir() ?
																							T_('Could not delete directory [%s] (not empty?).') :
																							T_('Could not delete [%s].') ),
																							$filename ) );
			}
			else
			{
				$Fileman->Messages->add( sprintf( ( $curFile->isDir() ?
																							T_('Deleted directory [%s].') :
																							T_('Deleted file [%s].') ),
																							$filename ), 'note' );
			}
		}

		// }}}
		break;


	case 'editperm':  // {{{ edit permissions
		if( !$curFile )
		{
			break;
		}
		param( 'chmod', 'string', '' );

		if( !empty($chmod) )
		{
			$oldperms = $curFile->getPerms();
			$newperms = $curFile->chmod( $chmod );
			if( $newperms === false )
			{
				$Fileman->Messages->add( sprintf( T_('Failed to set permissions on [%s] to [%s].'), $curFile->getName(), $chmod ) );
			}
			elseif( $newperms === $oldperms )
			{
				$Fileman->Messages->add( sprintf( T_('Permissions for [%s] not changed.'), $curFile->getName() ), 'note' );
			}
			else
			{
				$Fileman->Messages->add( sprintf( T_('Permissions for [%s] changed to [%s].'), $curFile->getName(), $curFile->getPerms() ), 'note' );
			}
			#pre_dump( $Fileman->cdo_file( $file, 'chmod', $chmod ), 'chmod!');
		}
		else
		{
			$msg_action = '
			<form name="form_chmod" action="files.php">
			'.$Fileman->getFormHiddenInputs().'
			<input type="hidden" name="file" value="'.format_to_output( $file, 'formvalue' ).'" />
			<input type="hidden" name="action" value="editperm" />
			';
			if( is_windows() )
			{
				$msg_action .= '
				<input id="chmod_readonly" name="chmod" type="radio" value="444" '
				.( $curFile->getPerms( 'octal' ) == 444 ? 'checked="checked" ' : '' ).'/>
				<label for="chmod_readonly">'.T_('Read-only').'</label><br />
				<input id="chmod_readwrite" name="chmod" type="radio" value="666" '
				.( $curFile->getPerms( 'octal' ) == 666 ? 'checked="checked" ' : '' ).'/>
				<label for="chmod_readwrite">'.T_('Read and write').'</label><br />';
			}
			else
			{
				$msg_action .= '<input type="text" name="chmod" value="'.$curFile->getPerms( 'octal' ).'" maxlength="3" size="3" /><br />';
				$js_focus = 'document.form_chmod.chmod';
			}
			$msg_action .= '
			<input type="submit" value="'.format_to_output( T_('Set new permissions'), 'formvalue' ).'" />
			</form>
			';

		}
		// }}}
		break;

	// }}}
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
		$allowedftypes = preg_split( '/\s+/', trim( $Settings->get( 'upload_allowedext' ) ) );

		if( isset($_FILES) && count( $_FILES ) )
		{{{ // process uploaded files
			foreach( $_FILES['uploadfile']['name'] as $lkey => $lName )
			{
				if( empty($lName) )
				{ // no name
					continue;
				}

				if( $_FILES['uploadfile']['size'][$lkey] > $fileupload_maxk*1024 )
				{
					$LogUpload->add( sprintf( T_('The file [%s] is too big and has not been accepted.'), $lName ) );
					continue;
				}
				elseif( !is_uploaded_file( $_FILES['uploadfile']['tmp_name'][$lkey] ) )
				{
					$LogUpload->add( sprintf( T_('The file [%s] does not seem to be a valid upload!'), $lName ) );
					continue;
				}

				$newName = $Fileman->getCwd().basename( $lName );
				if( file_exists( $newName ) )
				{
					// TODO: Rename/Overwriting
					$LogUpload->add( sprintf( T_('The file [%s] already exists.'), basename($newName) ) );
					continue;
				}
				elseif( move_uploaded_file( $_FILES['uploadfile']['tmp_name'][$lkey], $newName ) )
				{
					$Fileman->Messages->add( sprintf( T_('The file [%s] has been successfully uploaded.'), basename($newName) ), 'note' );
					$Fileman->addFileByPath( $newName );
					continue;
				}

				$tError = 'An error occured ('.$lName.'):<br />';

				switch( $_FILES['uploadfile']['error'][$lkey] )
				{
					case UPLOAD_ERR_OK:
						$tError .= 'There is no error, the file uploaded with success.';
						break;
					case UPLOAD_ERR_INI_SIZE:
						$tError .= 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
						break;

					case UPLOAD_ERR_FORM_SIZE:
						$tError .= 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form.';
						break;

					case UPLOAD_ERR_PARTIAL:
						$tError .= 'The uploaded file was only partially uploaded.';
						break;

					case UPLOAD_ERR_NO_FILE:
						$tError .= 'No file was uploaded.';
						break;
				}
				$LogUpload->add( $tError.'<br />'.var_export( $_FILES, true ) );
			}
		}}}

		if( !(isset($_FILES) && count( $_FILES )) || $LogUpload->count('all') )
		{{{ // upload dialog
			?>
			<script type="text/javascript">
				<!--
				function addAnotherFileInput()
				{
					var newInput = document.createElement("input");
					newInput.name="uploadfile[]";
					newInput.type="file";
					newInput.size=40;

					uploadfiles = document.getElementById("uploadfileinputs");
					uploadfiles.appendChild( newInput );
					uploadfiles.appendChild( document.createElement("br") );
				}
				// -->
			</script>
			<div class="panelblock">
				<form enctype="multipart/form-data" action="" method="post" class="fform">
					<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $Settings->get( 'upload_maxkb' )*1024 ?>" />
					<?php echo $Fileman->getFormHiddenInputs() ?>
					<fieldset>
					<h2><?php echo T_('File upload')  ?></h2>
					<p><?php echo T_('Navigate to the directory where you want to upload your file(s) into.') ?></p>
					<p><?php echo T_('Allowed file types:').' '.implode(', ', $allowedftypes) ?></p>
					<p><?php printf( T_('Maximum allowed file size: %s'), bytesreadable( $Settings->get( 'upload_maxkb' )*1024 ) ) ?></p>

					<?php $LogUpload->display( '', '', true, 'all' ); ?>


					<fieldset id="uploadfileinputs">
						<legend><?php echo T_('Files to upload') ?></legend>
						<input name="uploadfile[]" type="file" size="40" /><br />
					</fieldset>
					<?php /*'.T_('Description').':	<input type="text" name="imgdesc['.$i.']" size="50" /><br />'; */ ?>

					<fieldset class="submit">
						<input class="ActionButton" type="button" value="<?php echo T_('Add another file') ?>" onclick="addAnotherFileInput();" />
						<input class="ActionButton" type="submit" value="<?php echo T_('Upload !') ?>" />
					</fieldset>
					</fieldset>
				</form>
			</div>

			<?php
		}}}
		else
		{ // successfully finished, leave mode
			$Fileman->mode = NULL;
		}

		// }}}
		break;


	case 'file_cmr': // copy/move/rename a file {{{
		$LogCmr = new Log( 'error' );  // Log for copy/move/rename mode

		if( !($SourceFile =& $Fileman->SourceList->getFileByFilename( basename($Fileman->source) )) )
		{ // source file not in source filelist
			$Fileman->Messages->add( sprintf( T_('Invalid source file [%s].'), $Fileman->source ) );
			break;
		}

		#pre_dump( $SourceFile, 'SourceFile' );

		param( 'newname', 'string', $SourceFile->getName() );
		param( 'keepsource', 'integer', 0 );
		param( 'overwrite', 'integer', 0 );
		param( 'cmr_doit', 'integer', 0 );


		if( $cmr_doit )
		{{{ // we want Action!
			if( !isFilename($newname) )
			{
				$LogCmr->add( sprintf( T_('[%s] is not a valid filename.'), $newname ) );
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
					$LogCmr->add( sprintf( T_('The file [%s] already exists.'), $newname ) );
					$overwrite = 'ask';
				}
				else
				{ // unlink existing file
					if( !$Fileman->unlink( $TargetFile ) )
					{
						$LogCmr->add( sprintf( T_('Could not delete [%s].'), $newname ) );
					}
					else
					{
						$Fileman->Messages->add( sprintf( T_('Deleted file [%s].'), $newname ), 'note' );
					}
				}
			}

			if( !$LogCmr->count( 'error' ) )
			{ // no errors, safe for action
				$oldpath = $SourceFile->getPath(true);

				if( $Fileman->copyFileToFile( $SourceFile, $TargetFile ) )
				{
					if( !$keepsource )
					{ // move/rename
						if( $Fileman->unlink( $SourceFile ) )
						{
							if( $SourceFile->getPath() == $Fileman->getCwd() )
							{ // successfully renamed
								$Fileman->Messages->add( sprintf( T_('Renamed [%s] to [%s].'),
																									basename($oldpath),
																									$TargetFile->getName() ), 'note' );
							}
							else
							{ // successfully moved
								$Fileman->Messages->add( sprintf( T_('Moved [%s] to [%s].'),
																									$oldpath,
																									$TargetFile->getName() ), 'note' );

							}
						}
						else
						{
							$LogCmr->add( sprintf( T_('Could not remove [%s], but the file has been copied to [%s].'),
																		($SourceFile->getPath() == $Fileman->getCwd() ?
																			basename($oldpath) :
																			$oldpath ),
																		$TargetFile->getName() ) );
						}
					}
					else
					{ // copy only
						$Fileman->Messages->add( sprintf(
							T_('Copied [%s] to [%s].'),
							( $SourceFile->getPath() == $Fileman->getCwd() ?
								$SourceFile->getName() :
								$SourceFile->getPath(true) ),
							$TargetFile->getName() ), 'note' );
					}
				}
				else
				{
					$LogCmr->add( sprintf( T_('Could not copy [%s] to [%s].'),
																	$SourceFile->getPath(true),
																	$TargetFile->getPath(true) ), 'error' );
				}
			}
		}}}


		if( !$cmr_doit || $LogCmr->count( 'all' ) )
		{
			// text and value for JS dynamic fields, when referring to move/rename
			if( $SourceFile->getPath() == $Fileman->getCwd() )
			{
				$submitMoveOrRenameText = format_to_output( T_('Rename'), 'formvalue' );
			}
			else
			{
				$submitMoveOrRenameText = format_to_output( T_('Move'), 'formvalue' );
			}
			?>

			<div class="panelblock">
				<form action="" class="fform" id="cmr_form">
					<?php echo $Fileman->getFormHiddenInputs() ?>
					<input type="hidden" name="cmr_doit" value="1" />
					<fieldset>
						<legend><?php
						echo T_('Source').': '.$SourceFile->getPath(true);
						?></legend>

						<div class="notes">
							<?php
							echo '<strong>'.T_('You are in copy-move-rename mode.')
										.'</strong><br />'.T_('Please navigate to the desired location.'); ?>
						</div>

						<?php

						$LogCmr->display( '', '', true, 'all' );


						if( $overwrite === 'ask' )
						{
							form_checkbox( 'overwrite', 0, '<span class="error">'.T_('Overwrite existing file').'</span>',
															sprintf( T_('The existing file [%s] will be replaced with this file.'),
																				$TargetFile->getPath(true) ) );
						}
						?>

						<fieldset>
							<div class="label">
								<label for="fm_keepsource"><?php echo T_('Keep source file') ?>:</label>
							</div>
							<div class="input">
								<input class="checkbox" type="checkbox" value="1" name="keepsource"
									id="fm_keepsource" onclick="setCmrSubmitButtonValue( this.form );"<?php
									if( $keepsource )
									{
										echo ' checked="checked"';
									} ?> />
								<span class="notes"><?php echo T_('Do not delete the source file.') ?></span>
							</div>
						</fieldset>

						<fieldset>
							<div class="label">
								<label for="fm_newname">New name:</label>
							</div>
							<div class="input">
								<input type="text" name="newname" id="fm_newname" value="<?php echo $newname ?>" />
							</div>
						</fieldset>
						<fieldset>
							<div class="input">
								<input id="fm_cmr_submit" type="submit" value="<?php
									if( $keepsource )
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
						if( document.getElementById( 'fm_keepsource' ).checked )
						{
							text = '<?php echo format_to_output( T_('Copy'), 'formvalue' ) ?>';
						}
						else
						{
							text = '<?php echo $submitMoveOrRenameText ?>';
						}
						document.getElementById( 'fm_cmr_submit' ).value = text;
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


// output errors, notes and action messages {{{
if( isset( $msg_action )
		|| $Fileman->Messages->count( array( 'error', 'note' ) )
		|| $Messages->count( 'all' ) )
{
	?>
	<div class="panelinfo">
		<?php
		$Messages->display( '', '', true, 'all' );
		$Fileman->Messages->display( '', '', true, array( 'error', 'note' ) );

		if( isset($msg_action) )
		{
			echo $msg_action;
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
		?>
	</div>
	<?php
} // }}}



// reload opener window, if popup is in the same directory and filelist md5
// differs
?>


<script type="text/javascript">
	<!--
	if( opener
			&& typeof(opener.document.FilesForm.md5_filelist.value) != 'undefined'
			&& typeof(opener.document.FilesForm.md5_cwd.value) != 'undefined'
			&& opener.document.FilesForm.md5_filelist.value != '<?php echo $Fileman->toMD5(); ?>'
			&& opener.document.FilesForm.md5_cwd.value == '<?php echo md5($Fileman->getCwd()); ?>'
			)
	{
		opener.location.reload();
	}
	// -->
</script>


<div class="panelblock">
<?php
	echo '<a title="'.T_('Go to your home directory').'" class="toolbaritem" href="'
				.$Fileman->getLinkHome().'">'.getIcon( 'folder_home' ).'</a>';

	$rootlist = $Fileman->getRootList();
	if( count($rootlist) > 1 )
	{ // provide list of roots
		echo '<form action="files.php" name="roots" class="toolbaritem">'
					.$Fileman->getFormHiddenInputs( array( 'root' => false ) );
		echo '<select name="root" onchange="this.form.submit()">';

		foreach( $rootlist as $lroot )
		{
			$lroot_value = $lroot['type'];
			if( isset($lroot['id']) )
			{
				$lroot_value .= '_'.$lroot['id'];
			}
			echo '<option value="'.$lroot_value.'"';

			if( $root == $lroot_value
					|| $root === NULL && $lroot_value == 'user' )
			{
				echo ' selected="selected"';
			}

			echo '>'.format_to_output( $lroot['name'] ).'</option>';
		}
		echo '</select><input class="ActionButton" type="submit" value="'.T_('Change root').'" />'
					."</form>\n";
	}
	?>

	<form action="files.php" name="flatmode" class="toolbaritem">
		<?php echo $Fileman->getFormHiddenInputs( array( 'flatmode' => false ) ) ?>
		<input type="hidden" name="flatmode" value="<?php echo $flatmode ? 0 : 1; ?>" />
		<input class="ActionButton" type="submit" title="<?php
			echo format_to_output( $flatmode ?
															T_('Normal mode') :
															T_('All files recursive without directories'), 'formvalue' );
			?>" value="<?php
			echo format_to_output( $flatmode ?
															T_('Normal mode') :
															T_('Flat mode'), 'formvalue' ); ?>" />
	</form>

	<form action="files.php" name="search" class="toolbaritem">
		<?php echo $Fileman->getFormHiddenInputs() ?>
		<input type="hidden" name="action" value="search" />
		<input type="text" name="searchfor" value="--todo--" size="20" />
		<input class="ActionButton" type="submit" value="<?php echo format_to_output( T_('Search'), 'formvalue' ) ?>" />
	</form>

	<div class="toolbaritem">
		<form action="files.php" name="filter" class="inline">
			<?php echo $Fileman->getFormHiddenInputs( array( 'filterString' => false, 'filterIsRegexp' => false ) ) ?>
			<input type="text" name="filterString" value="<?php echo format_to_output( $Fileman->getFilter( false ), 'formvalue' ) ?>" size="20" />
			<input type="checkbox" name="filterIsRegexp" id="filterIsRegexp" title="<?php
				echo format_to_output( T_('Filter is regular expression'), 'formvalue' )
				?>" value="1"<?php if( $filterIsRegexp ) echo ' checked="checked"' ?> /><?php
				echo ' <label for="filterIsRegexp">'./* TRANS: short for "is regular expression" */ T_('RegExp').'</label>'; ?>

			<input class="ActionButton" type="submit" value="<?php echo format_to_output( T_('Filter'), 'formvalue' ) ?>" />
		</form>

		<?php
		if( $Fileman->isFiltering() )
		{ // "reset filter" form
		?>
		<form action="files.php" name="unfilter" class="inline">
			<?php echo $Fileman->getFormHiddenInputs( array( 'filterString' => false, 'filterIsRegexp' => false ) ) ?>
			<input class="ActionButton" type="submit" value="<?php echo format_to_output( T_('No filter'), 'formvalue' ) ?>" />
		</form>
		<?php
		}
		?>

	</div>
</div>

<div class="clear"></div>


<div class="panelblock">
<form name="FilesForm" action="files.php" method="post">
<input type="hidden" name="md5_filelist" value="<?php echo $Fileman->toMD5() ?>" />
<input type="hidden" name="md5_cwd" value="<?php echo md5($Fileman->getCwd()) ?>" />

<table class="grouped">
<caption>
<?php
echo T_('Current directory').': '.$Fileman->getCwdClickable();
if( $Fileman->isFiltering() )
{
	echo '<br />'.T_('Filter').': ['.$Fileman->getFilter().']';
}

/**
 * @global integer Number of cols for the files table, 8 by default
 */
$filetable_cols = 8;
?>
</caption>
<thead>
<tr>
	<th colspan="2"><?php $Fileman->dispButtonParent(); ?></th>
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
	<th><?php echo /* TRANS: file actions; edit, rename, copy, .. */ T_('Actions') ?></th>
</tr>
</thead>

<tbody>
<?php
param( 'checkall', 'integer', 0 );  // Non-Javascript-CheckAll

$Fileman->sort();

$countFiles = 0;
while( $lFile = $Fileman->getNextFile() )
{ // loop through all Files
	?>

	<tr<?php
		if( $countFiles%2 ) echo ' class="odd"';
		?> onclick="document.getElementsByName('selectedfiles[]')[<?php echo $countFiles ?>].click();">
		<td class="checkbox">
			<input title="<?php echo T_('Select this file') ?>" type="checkbox"
				name="selectedfiles[]" value="<?php
				echo format_to_output( $lFile->getName(), 'formvalue' ) ?>" id="cb_filename_<?php
				echo $countFiles ?>" onclick="document.getElementsByName('selectedfiles[]')[<?php
				echo $countFiles ?>].click();"<?php if( $checkall ) echo ' checked="checked" '?> />
		</td>

		<td class="icon">
			<a href="<?php
				if( $lFile->isDir() )
				{
					echo $Fileman->getLinkFile( $lFile );
				}
				else
				{
					echo $Fileman->getFileUrl();
				} ?>"><?php echo getIcon( $lFile ) ?></a>
		</td>

		<td class="filename">
			<a href="<?php echo $Fileman->getLinkFile( $lFile ) ?>" target="fileman_default" onclick="return false;">
			<button class="image" type="button" onclick="document.getElementsByName('selectedfiles[]')[<?php
				echo $countFiles ?>].click(); <?php

				$imgsize = $lFile->getImageSize( 'widthheight' );
				echo $Fileman->getJsPopupCode( NULL,
					"'+( typeof(fm_popup_type) == 'undefined' ? 'fileman_default' : 'fileman_popup_$countFiles')+'",
					($imgsize ? $imgsize[0]+42 : NULL),
					($imgsize ? $imgsize[1]+42 : NULL) );

				?>" id="button_new_<?php echo $countFiles ?>" title="Open in a new window">
				<?php echo getIcon( 'window_new' )
			?></button></a>

			<a onclick="clickedonlink=1;" href="<?php echo $Fileman->getLinkFile( $lFile ) ?>">
			<?php
			echo $lFile->getName();
			disp_cond( $Fileman->getFileImageSize(), ' (%s)' )
			?>
			</a>

			<?php
			if( $Fileman->flatmode )
			{
				?><div class="path" title="<?php echo T_('The directory of the file') ?>"><?php
				$path = substr( $lFile->getPath( false ), strlen( $Fileman->cwd ), -1 );
				echo empty( $path ) ?
							' - ' :
							$path;
				?></div><?php
			}
			?>
		</td>

		<td class="type"><?php echo $lFile->getType() ?></td>
		<td class="size"><?php echo $lFile->getSizeNice() ?></td>
		<td class="timestamp"><?php echo $lFile->getLastMod() ?></td>
		<td class="perms"><?php $Fileman->dispButtonFileEditPerms() ?></td>
		<td class="actions"><?php
			$Fileman->dispButtonFileEdit();
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
{{{ // Footer with "check all", "with selected: .."
?>
<tr class="group"><td colspan="<?php echo $filetable_cols ?>">
	<a id="checkallspan_0" href="<?php
		echo url_add_param( $Fileman->getCurUrl(), 'checkall='.( $checkall ? '0' : '1' ) );
		?>" onclick="toggleCheckboxes('FilesForm', 'selectedfiles[]'); return false;"><?php
		echo ($checkall) ? T_('uncheck all') : T_('check all');
		?></a>
	&mdash; <strong><?php echo T_('with selected files:') ?> </strong>
	<?php echo $Fileman->getFormHiddenInputs() ?>
	<input class="DeleteButton" type="submit" name="selaction" value="<?php echo T_('Delete') ?>" onclick="return openselectedfiles(true) ? confirm('<?php echo /* TRANS: Warning this is a javascript string */ T_('Do you really want to delete the selected files?') ?>') : false;" />
	<input class="ActionButton" type="submit" name="selaction" value="<?php echo T_('Download') ?>" onclick="return openselectedfiles(true);" />
	<input class="ActionButton" type="submit" name="selaction" value="<?php echo T_('Send by mail') ?>" onclick="return openselectedfiles(true);" />
	<input class="ActionButton" type="button" name="selaction" value="<?php echo T_('Open in new windows') ?>" onclick="openselectedfiles(); return false;" />

	<div class="small">
	<?php
	disp_cond( $Fileman->countDirs(), T_('One directory'), T_('%d directories'), T_('No directories') );
	echo ', ';
	disp_cond( $Fileman->countFiles(), T_('One file'), T_('%d files'), T_('No files' ) );
	echo ', '.bytesreadable( $Fileman->countBytes() );
	?>
	</div>
</td></tr>
<?php
}}}
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
		elems = document.getElementsByName( 'selectedfiles[]' );
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
			alert( '<?php echo /* TRANS: Warning this is a javascript string */ T_('Nothing selected.') ?>' );
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


// {{{ bottom toolbar
param( 'options_show', 'integer', 0 );
?>
<form class="toolbaritem" action="files.php" method="post">
	<a id="options_toggle" href="<?php echo url_add_param( $Fileman->getCurUrl(), ( !$options_show ? 'options_show=1' : '' ) ) ?>"
		onclick="return toggle_options();"><?php
		echo ( $options_show ) ? T_('Hide options') : T_('Show options') ?></a>

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

		<?php echo $Fileman->getFormHiddenInputs() ?>
		<input type="hidden" name="action" value="update_settings" />
		<input type="hidden" name="options_show" value="1" />
		<div class="input">
		<input type="submit" value="<?php echo T_('Update !') ?>" />
		</div>
	</div>

	<script type="text/javascript">
	<!--
		showoptions = <?php echo ($options_show) ? 'true' : 'false' ?>;

		function toggle_options()
		{
			if( showoptions )
			{
				var replace = document.createTextNode('<?php echo /* TRANS: Warning this is a javascript string */ T_('show options') ?>');
				var display_list = 'none';
				showoptions = false;
			}
			else
			{
				var replace = document.createTextNode('<?php echo /* TRANS: Warning this is a javascript string */ T_('hide options') ?>');
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


<form action="" class="toolbaritem">
	<?php $Fileman->dispButtonUploadMode(); ?>
</form>

<form action="" class="toolbaritem">
	<select name="createnew">
		<option value="file"><?php echo T_('file') ?></option>
		<option value="dir"<?php
			if( isset($createnew) && $createnew == 'dir' )
			{
				echo ' selected="selected"';
			} ?>><?php echo T_('directory') ?></option>
	</select>
	<input type="text" name="createname" value="<?php
		if( isset( $createname ) )
		{
			echo $createname;
		} ?>" size="20" />
	<input class="ActionButton" type="submit" value="<?php echo format_to_output( T_('Create new'), 'formvalue' ) ?>" />
	<?php echo $Fileman->getFormHiddenInputs() ?>
	<input type="hidden" name="action" value="createnew" />
</form>
<?php /* }}} */ ?>


<br class="clear" />
<div class="fform">
	<fieldset>
	<legend><?php echo T_('Icon legend') ?></legend>
		<span class="nobr"><?php echo getIcon( 'folder_parent' ).' '.T_('Go to parent folder'); ?></span>
		<span class="nobr"><?php echo getIcon( 'folder_home' ).' '.T_('Go to your home directory'); ?></span>
		<span class="nobr"><?php echo getIcon( 'window_new' ).' '.T_('Open in a new window'); ?></span>
		<span class="nobr"><?php echo getIcon( 'file_edit' ).' '.T_('Edit the file'); ?></span>
		<span class="nobr"><?php echo getIcon( 'file_copy' ).' '.T_('Copy'); ?></span>
		<span class="nobr"><?php echo getIcon( 'file_move' ).' '.T_('Move'); ?></span>
		<span class="nobr"><?php echo getIcon( 'file_rename' ).' '.T_('Rename'); ?></span>
		<span class="nobr"><?php echo getIcon( 'file_delete' ).' '.T_('Delete'); ?></span>
	</fieldset>
</div>

<div class="fform">
	<fieldset>
	<legend><?php echo T_('Information') ?></legend>

	<ul>
		<li><?php echo T_("Clicking on a file's name invokes the default action (images get displayed as image, raw content for all other files)."); ?></li>
		<li><?php echo T_("Clicking on a file's icon lets the browser handle the file."); ?></li>
		<li><?php printf( T_('The new window icon (%s) opens the file in a popup window.'), getIcon( 'window_new' ) ); ?></li>
	</ul>
	</fieldset>
</div>

</div>
<br class="clear" />
</div>
<?php
require( dirname(__FILE__). '/_footer.php' );
#echo replacePngTags( ob_get_clean(), $img_url);
?>