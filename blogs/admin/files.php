<?php
/**
 * This file implements the UI controller for file management. {{{
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
 * @version $Id$ }}}
 *
 * @todo: thumbnail view
 * @todo: PHPInfo (special permission)
 * @todo: directly run PHP-code (eval)
 *
 */

require_once dirname(__FILE__).'/_header.php';
require_once dirname(__FILE__).'/'.$admin_dirout.$core_subdir.'_class_filemanager.php';

$admin_tab = 'files';
$admin_pagetitle = T_('File Manager').' (alpha)';

param( 'path', 'string', '' );       // the path relative to the root dir
param( 'action', 'string', '' );     // 3.. 2.. 1.. action :)
param( 'selaction', 'string', '' );  // action for selected files/dirs

param( 'file', 'string', '' );       // selected file
param( 'filter', '', NULL );
param( 'filter_regexp', 'integer', NULL );
param( 'order', 'string', NULL );
param( 'asc', '', NULL );

param( 'root', 'string', NULL );    // the root directory from the dropdown box (user_X or blog_X; X is ID - 'user' for current user (default))


if( $current_User->login != 'demouser' && $current_User->level < 10 )
{ // allow demouser, but noone else below level 10
	exit( 'This is alpha. You need user level 10 to play with this.' );
}

if( $action == 'update_settings' )
{ // updating user settings
	$UserSettings->set( 'fm_dirsattop',        param( 'option_dirsattop',        'integer', 0 ) );
	$UserSettings->set( 'fm_permlikelsl',      param( 'option_permlikelsl',      'integer', 0 ) );
	$UserSettings->set( 'fm_recursivedirsize', param( 'option_recursivedirsize', 'integer', 0 ) );
	$UserSettings->set( 'fm_showhidden',       param( 'option_showhidden',       'integer', 0 ) );

	if( $UserSettings->updateDB() )
	{
		$Messages->add( T_('Your user settings have been updated.'), 'note' );
	}
}


// instanciate Filemanager object
$Fileman = new FileManager( $current_User, 'files.php', $root, $path, $filter, $filter_regexp, $order, $asc );

if( !empty($file) )
{ // a file is given as parameter
	$curFile =& $Fileman->getFileByFilename( $file );

	if( !$curFile )
	{
		$Fileman->Messages->add( sprintf( T_('File [%s] could not be accessed!'), $file ) );
	}
}


if( $action == '' && $file != '' && $curFile )
{ // a file is selected/clicked, default action
	{{{ // do the default action

		?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
		<head>
			<title><?php echo $file.' :: '.T_('b2evolution Filemanager') ?></title>
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
					<img class="framed" src="<?php echo $Fileman->getFileUrl( $curFile ) ?>" <?php echo $curFile->get_imgsize( 'string' ) ?> />
				</div>
				<?php
			}
			elseif( ($buffer = @file( $curFile->getPath( true ) )) !== false )
			{{{ // display raw file
				param( 'showlinenrs', 'integer', 0 );

				// TODO: check if new window was opened and provide close X in case
				/*<a href="javascript:window.close()"><img class="center" src="<?php echo $admin_url.'img/xross.gif' ?>" width="13" height="13" alt="[X]" title="<?php echo T_('Close this window') ?>" /></a>*/

				echo '<div class="fileheader">';
				echo T_('file').': '.$file.'<br />';

				if( !count($buffer) )
				{
					echo '</div> ** '.T_('empty file').' ** ';
				}
				else
				{
					echo count($buffer).' '.T_('lines').'<br />';
					$linenr_width = strlen( count($buffer)+1 );

					?>
					<noscript type="text/javascript">
						<a href="<?php echo $Fileman->curl().'&amp;file='.$file.'&amp;showlinenrs='.(1-$showlinenrs).'">'
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
				param( 'zipname', 'string', '' );
				param( 'exclude_sd', 'integer', 0 );

				if( empty($zipname) )
				{
					$msg_action = '
					<p>
					'.T_('You want to download:').'<ul>';

					foreach( $selectedFiles as $lFile )
					{
						if( $lFile->isDir() )
						{
							$msg_action .= sprintf('<li>'.T_('Directory [%s]'."</li>\n"), $file);
						}
						else $msg_action .= sprintf('<li>'.T_('File [%s]')."</li>\n", $file);
					}

					$msg_action .= '
					</p>
					</div>
					<div class="panelblock">
					<form action="files.php" class="fform" method="post">
					<fieldset>
						<legend>'.T_('Please give a filename and choose zip format:').'</legend>';

						foreach( $selectedfiles as $file )
						{
							$msg_action .= '<input type="hidden" name="selectedfiles[]" value="'.format_to_output( $file, 'formvalue' )."\" />\n";
						}

						$msg_action .= $Fileman->form_hiddeninputs()."\n"
												.form_text( 'zipname', '', 20, T_('Archive filename'), T_('This is the filename that will be send to you.'), 80, '', 'text', false )."\n"
												.form_checkbox( 'exclude_sd', $exclude_sd, T_('Exclude subdirectories'), T_('This will exclude subdirectories of selected directories.'), '', false )."\n"
												.'<div class="input"><input type="submit" name="selaction" value="'.T_('Download').'" class="search" /></div>
					</fielset>
					</form>';
				}
				else
				{ // Downloading
					require( dirname(__FILE__).'/'.$admin_dirout.$core_subdir.'_class_zip.php' );

					$options = array (
						'basedir' => $Fileman->cwd,
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
					$Fileman->Messages->add( sprintf(T_('Would delete [%s]'), $file).'..', 'note' );

				}

				break;
		}
	}
}}}


switch( $action ) // {{{ (we catched empty action before)
{
	case 'createnew':  // create new file/dir
		param( 'createnew', 'string', '' );
		param( 'createname', 'string', '' );

		if( $createnew == 'dir' )
		{
			$Fileman->createDir( $createname );
		}
		elseif( $createnew == 'file' )
		{
			$Fileman->createFile( $createname );
		}
		break;

	case 'delete':
		if( !$curFile )
		{
			break;
		}
		// TODO: checkperm!
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
		break;

	case 'rename':
		if( !$curFile )
		{
			break;
		}
		param( 'newname', 'string', '' );
		param( 'overwrite', 'integer', 0 );
		$dontrename = false;

		if( !empty($newname) )
		{ // rename the file
			if( !isFilename($newname) )
			{
				$Fileman->Messages->add( sprintf( T_('[%s] is not a valid filename.'), $newname ) );
				$dontrename = true;
			}
			elseif( $existingFile = $Fileman->getFileByFilename( $newname ) )
			{ // target filename already given to another file
				if( !$overwrite )
				{
					$Fileman->Messages->add( sprintf( T_('The file [%s] already exists.'), $newname ).'
					<form action="">
					'.$Fileman->form_hiddeninputs().'
					<input type="hidden" name="file" value="'.format_to_output( $file, 'formvalue' ).'" />
					<input type="hidden" name="newname" value="'.format_to_output( $newname, 'formvalue' ).'" />
					<input type="hidden" name="overwrite" value="1" />
					<input type="hidden" name="action" value="rename" />
					<input type="submit" value="'.format_to_output( T_('Overwrite existing file!'), 'formvalue' ).'" />
					</form>
					' );
					$dontrename = true;
				}
				else
				{ // unlink existing file
					if( !$Fileman->unlink( $existingFile ) )
					{
						$Fileman->Messages->add( sprintf( T_('Could not delete [%s].'), $newname ) );
						$dontrename = true;
					}
					else
					{
						$Fileman->Messages->add( sprintf( T_('Deleted file [%s].'), $newname ), 'note' );
					}
				}
			}

			if( !$dontrename )
			{
				if( $curFile->rename( $newname ) )
				{
					$Fileman->Messages->add( sprintf( T_('Renamed [%s] to [%s].'), $file, $newname ), 'note' );
					break;
				}
				else
				{
					$Fileman->Messages->add( sprintf( T_('Could not rename [%s] to [%s].'), $file, $newname ) );
				}
			}
		}

		if( !isset($msg_action) )
		{
			$msg_action = '';
		}
		$msg_action .= '
		<form name="form_rename" action="">
		'.$Fileman->form_hiddeninputs().'
		<input type="hidden" name="file" value="'.format_to_output( $file, 'formvalue' ).'" />
		<input type="hidden" name="action" value="rename" />
		<label for="form_newname">'.sprintf( T_('New name for [%s]:'), $file ).'</label>
		<input type="text" id="form_newname" name="newname" value="'
		.format_to_output( empty($newname) ? $file : $newname, 'formvalue' ).'" maxlength="255" size="30" />
		<input type="submit" value="'.format_to_output( T_('Rename it'), 'formvalue' ).'" />
		</form>';
		$js_focus = 'document.form_rename.form_newname';

		break;

	case 'editperm':
		if( !$curFile )
		{
			break;
		}
		param( 'chmod', 'string', '' );

		if( !empty($chmod) )
		{
			$oldperms = $curFile->get_perms();
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
				$Fileman->Messages->add( sprintf( T_('Permissions for [%s] changed to [%s].'), $curFile->getName(), $curFile->get_perms() ), 'note' );
			}
			#pre_dump( $Fileman->cdo_file( $file, 'chmod', $chmod ), 'chmod!');
		}
		else
		{
			$msg_action = '
			<form name="form_chmod" action="files.php">
			'.$Fileman->form_hiddeninputs().'
			<input type="hidden" name="file" value="'.format_to_output( $file, 'formvalue' ).'" />
			<input type="hidden" name="action" value="editperm" />
			';
			if( is_windows() )
			{
				$msg_action .= '<input id="chmod_readonly" name="chmod" type="radio" value="444" '
				.( $curFile->get_perms( 'octal' ) == 444 ? 'checked="checked" ' : '' ).'/>
				<label for="chmod_readonly">'.T_('Read-only').'</label><br />
				<input id="chmod_readwrite" name="chmod" type="radio" value="666" '
				.( $curFile->get_perms( 'octal' ) == 666 ? 'checked="checked" ' : '' ).'/>
				<label for="chmod_readwrite">'.T_('Read and write').'</label><br />';
			}
			else
			{
				$msg_action .= '<input type="text" name="chmod" value="'.$curFile->get_perms( 'octal' ).'" maxlength="3" size="3" /><br />';
				$js_focus = 'document.form_chmod.chmod';
			}
			$msg_action .= '
			<input type="submit" value="'.format_to_output( T_('Set new permissions'), 'formvalue' ).'" />
			</form>
			';

		}
		break;

	case 'upload':
		// Check permissions:
		if( !$Fileman->perm( 'upload' ) )
		{
			$Fileman->Messages->add( T_('You have no permissions to upload into this directory.') );
		}
		else
		{
			if( isset($_FILES) && count( $_FILES ) )
			{
				foreach( $_FILES['uploadfile']['name'] as $lkey => $lName )
				{
					if( empty($lName) )
					{ // no name
						continue;
					}

					if( $_FILES['uploadfile']['size'][$lkey] > $fileupload_maxk*1024 )
					{
						$Fileman->Messages->add( sprintf( T_('The file [%s] is too big and was not accepted.'), $lName ) );
						continue;
					}
					elseif( !is_uploaded_file( $_FILES['uploadfile']['tmp_name'][$lkey] ) )
					{
						$Fileman->Messages->add( sprintf( T_('The file [%s] does not seem to be a valid upload!'), $lName ) );
						continue;
					}

					$newName = $Fileman->User->getMediaDir().basename( $lName );
					if( file_exists( $newName ) )
					{
						// TODO: Rename/Overwriting
						$Fileman->Messages->add( sprintf( T_('The file [%s] already exists.'), basename($newName) ) );
						continue;
					}
					elseif( move_uploaded_file( $_FILES['uploadfile']['tmp_name'][$lkey], $newName ) )
					{
						$Fileman->Messages->add( sprintf( T_('The file [%s] was successfully uploaded.'), basename($newName) ), 'note' );
						$Fileman->addFile( $newName );
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
					$Fileman->Messages->add( $tError.'<br />'.var_export( $_FILES, true ) );
				}
			}
			else
			{
				$allowedftypes = preg_split( '/\s+/', trim( $fileupload_allowedtypes) );

				$msg_action = '
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
				<p><strong>'.T_('File upload').'</strong></p>
				<p>'.T_('Allowed file types:').' '.implode(', ', $allowedftypes).'</p>
				<p>'.sprintf( T_('Maximum allowed file size: %d KB'), $fileupload_maxk ).'</p>

				<form enctype="multipart/form-data" action="'.$Fileman->curl().'" method="post">
					<input type="hidden" name="MAX_FILE_SIZE" value="'.($fileupload_maxk*1024).'" />
					<input type="hidden" name="action" value="upload" />
					<div id="uploadfileinputs">
					<input name="uploadfile[]" type="file" size="40" /><br />
					</div>';
						//'.T_('Description').':	<input type="text" name="imgdesc['.$i.']" size="50" /><br />';

					$msg_action .= '
					<input type="button" value="'.T_('Add another file').'" onclick="addAnotherFileInput();" />
					<input type="submit" value="'.T_('Upload !').'" onclick="clickedSubmit=1" />
					<br />
					'.sprintf( T_('Maximum upload filesize is %s.'), bytesreadable( $fileupload_maxk*1024 ) ).'
					</form>
					';
			}
		}

		break;
	// }}}
}


// the top menu and header
require dirname(__FILE__).'/_menutop.php';
require dirname(__FILE__).'/_menutop_end.php';


?>
<div id="filemanmain">
<?php

// output errors, notes and action messages
if( isset( $msg_action )
		|| $Fileman->Messages->count( array( 'error', 'note' ) )
		|| $Messages->count( 'all' )
		|| $Fileman->User->Messages->count( 'all' ) )
{
	?>
	<div class="panelinfo">
		<?php
		$Messages->display( '', '', true, 'all' );
		$Fileman->Messages->display( '', '', true, array( 'error', 'note' ) );
		$Fileman->User->Messages->display( '', '', true, 'all' );

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
}

?>
<div class="panelblock">
	<?php
	$rootlist = $Fileman->getRootList();

	// link to user home
	echo '<a class="toolbaritem" href="'.$Fileman->getLinkHome().'">'.$Fileman->getIcon( 'home', 'imgtag' ).'</a>';

	if( count($rootlist) > 1 )
	{ // provide list of roots
		echo '<form action="files.php" name="roots" class="toolbaritem">'
					.$Fileman->form_hiddeninputs( false );
		echo '<select name="root" onchange="this.form.submit()">';

		foreach( $rootlist as $lroot )
		{
			$lroot_value = $lroot['type'];
			if( isset($lroot['id']) )
			{
				$lroot_value .= '_'.$lroot['id'];
			}
			echo '<option value="'.$lroot_value.'"';

			if( $root == $lroot_value )
			{
				echo ' selected="selected"';
			}

			echo '>'.format_to_output( $lroot['name'] ).'</option>';
		}
		echo '</select><input type="submit" value="'.T_('Change root').'" />'
					."</form>\n";
	}
	?>

	<form action="files.php" name="search" class="toolbaritem">
		<?php echo $Fileman->form_hiddeninputs() ?>
		<input type="text" name="searchfor" value="--todo--" size="20" />
		<input type="submit" value="<?php echo format_to_output( T_('Search'), 'formvalue' ) ?>" />
	</form>

	<div class="toolbaritem_group">
		<?php
		if( $Fileman->isFiltering() )
		{ // "reset filter" form
		?>
		<form action="files.php" name="unfilter" class="toolbaritem">
			<?php echo $Fileman->form_hiddeninputs( NULL, NULL, false, false ) ?>
			<input type="submit" value="<?php echo format_to_output( T_('No Filter'), 'formvalue' ) ?>" />
		</form>
		<?php
		}
		?>

		<form action="files.php" name="filter" class="toolbaritem">
			<?php echo $Fileman->form_hiddeninputs( NULL, NULL, false, false ) ?>
			<input type="text" name="filter" value="<?php echo format_to_output( $Fileman->getFilter( false ), 'formvalue' ) ?>" size="20" />
			<input type="checkbox" name="filter_regexp" title="<?php echo format_to_output( T_('Filter is regular expression'), 'formvalue' ) ?>" value="1"<?php if( $filter_regexp ) echo ' checked="checked"' ?> />
			<input type="submit" value="<?php echo format_to_output( T_('Filter'), 'formvalue' ) ?>" />
		</form>
	</div>

	<div class="clear"></div>
</div>


<div class="panelblock">
<form name="FilesForm" action="files.php" method="post">
<table class="grouped">
<caption>
<?php
echo T_('Current directory').': '.$Fileman->getCwdClickable();
if( $Fileman->isFiltering() )
{
	echo '<br />'.T_('Filter').': ['.$Fileman->getFilter().']';
}
?>
</caption>
<thead>
<tr>
	<th colspan="2">
		<?php
		disp_cond( $Fileman->getLinkParent(), '&nbsp;<a href="%s">'.$Fileman->getIcon( 'parent', 'imgtag' ).'</a>' );
		?>
	</th>
	<th><?php echo $Fileman->getLinkSort( 'name', /* TRANS: file name */ T_('Name') ) ?></th>
	<th><?php echo $Fileman->getLinkSort( 'type', /* TRANS: file type */ T_('Type') ) ?></th>
	<th><?php echo $Fileman->getLinkSort( 'size', /* TRANS: file size */ T_('Size') ) ?></th>
	<th><?php echo $Fileman->getLinkSort( 'lastm', /* TRANS: file's last change / timestamp */ T_('Last change') ) ?></th>
	<th><?php echo $Fileman->getLinkSort( 'perms', /* TRANS: file's permissions */ T_('Perms') ) ?></th>
	<th><?php echo /* TRANS: file action, (html) view */ T_('Action') ?></th>
</tr>
</thead>

<tbody>
<?php
param( 'checkall', 'integer', NULL );  // Non-Javascript-CheckAll

$i = 0;
$Fileman->sort();

while( $lFile = $Fileman->getNextFile() )
{ // loop through all Files
	?>

	<tr<?php
		if( $i%2 ) echo ' class="odd"';
		?> onclick="document.getElementsByName('selectedfiles[]')[<?php echo $i ?>].click();">
		<td class="checkbox">
			<input title="<?php echo T_('select this file') ?>" type="checkbox" name="selectedfiles[]" value="<?php echo format_to_output( $lFile->getName(), 'formvalue' ) ?>" id="cb_filename_<?php echo $i ?>" onclick="document.getElementsByName('selectedfiles[]')[<?php echo $i ?>].click();"<?php if( $checkall ) echo ' checked="checked" '?> />
		</td>
		<td class="icon" onclick="window.location.href = '<?php echo $Fileman->getLinkCurfile() ?>'">
			<?php echo $Fileman->getIcon( $lFile, 'imgtag' ) ?>
		</td>
		<td class="filename">
			<a href="<?php echo $Fileman->getLinkCurfile() ?>" target="fileman_default" onclick="return false;">
			<button class="image" type="button" onclick="document.getElementsByName('selectedfiles[]')[<?php
				echo $i ?>].click(); window.open('<?php
				echo $Fileman->getLinkCurfile().( $lFile->isDir() ? '&amp;mode=browseonly' : '' );
				?>', ( typeof(fm_popup_type) == 'undefined' ? 'fileman_default' : 'fileman_popup_<?php
				echo $i ?>'), 'toolbar=0,resizable=yes,<?php
				if( $r = $lFile->get_imgsize( 'widthheight' ) )
				{ // make the popup 42px wider/higher than the image
					echo 'width='.($r[0]+42).',height='.($r[1]+42);
				}
				else
				{ // default popup-size: 800x600
					echo 'width=800,height=600';
				}
				?>');" id="button_new_<?php echo $i ?>" title="Open in new window">
				<?php echo $Fileman->getIcon( 'window_new', 'imgtag' )
			?></button></a>
			<a onclick="clickedonlink=1;" href="<?php echo $Fileman->getLinkCurfile() ?>">
			<?php
			echo $lFile->getName();
			disp_cond( $lFile->get_imgsize(), ' (%s)' )
			?>
			</a>
		</td>
		<td class="type"><?php echo $lFile->getType() ?></td>
		<td class="size"><?php echo $lFile->getSizeNice() ?></td>
		<td class="timestamp"><?php echo $lFile->get_lastmod() ?></td>
		<td class="perms"><?php
			disp_cond( $Fileman->getLinkCurfile_editperm(),
									'<a href="%s">'.$lFile->get_perms( $Fileman->permlikelsl ? 'lsl' : '' ).'</a>' ) ?></td>
		<td class="actions"><?php
			disp_cond( $Fileman->getLinkCurfile_edit(), '<a href="%s">'.$Fileman->getIcon( 'edit', 'imgtag' ).'</a>' );
			disp_cond( $Fileman->getLinkCurfile_copymove(), '<a href="%s">'.$Fileman->getIcon( 'copymove', 'imgtag' ).'</a>' );
			disp_cond( $Fileman->getLinkCurfile_rename(), '<a href="%s">'.$Fileman->getIcon( 'rename', 'imgtag' ).'</a>' );
			disp_cond( $Fileman->getLinkCurfile_delete(), '<a href="%s" onclick="return confirm(\''
				.sprintf( /* TRANS: Warning this is a javascript string */ T_('Do you really want to delete [%s]?'),
				format_to_output( $lFile->getName(), 'formvalue' ) ).'\');">'.$Fileman->getIcon( 'delete', 'imgtag' ).'</a>' );
			?></td>
	</tr>
	<?php
	$i++;
}
if( $i == 0 )
{ // Filelist errors or "directory is empty"
	?>
	<tr>
	<td colspan="8">
	<?php
	if( !$Fileman->Messages->count( 'fl_error' ) )
	{ // no Filelist errors, the directory must be empty
		$Fileman->Messages->add( T_('The directory is empty.')
			.( $Fileman->isFiltering() ? '<br />'.T_('Filter').': ['.$Fileman->getFilter().']' : '' ), 'fl_error' );
	}
	$Fileman->Messages->display( '', '', true, 'fl_error', 'log_error' );

	?>
	</td>
	</tr>
	<?php
}

if( $i != 0 )
{{{ // Footer with "check all", "with selected: .."
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
<tr class="group"><td colspan="8">
	<a id="checkallspan_0" href="<?php
		echo url_add_param( $Fileman->curl(), 'checkall='. ( $checkall ? '0' : '1' ) );
		?>" onclick="toggleCheckboxes('FilesForm', 'selectedfiles[]'); return false;"><?php
		if( !isset($checkall) )
		{
			echo T_('(un)check all');
		}
		else
		{ // Non-JS
			echo ($checkall) ? T_('uncheck all_1') : T_('check all');
		}
		?></a>
	&mdash; <strong><?php echo T_('with selected files:') ?> </strong>
	<?php echo $Fileman->form_hiddeninputs() ?>
	<input type="submit" name="selaction" value="<?php echo T_('Delete') ?>" onclick="return openselectedfiles(true) ? confirm('<?php echo /* TRANS: Warning this is a javascript string */ T_('Do you really want to delete the selected files?') ?>') : false;" />
	<input type="submit" name="selaction" value="<?php echo T_('Download') ?>" onclick="return openselectedfiles(true);" />
	<input type="submit" name="selaction" value="<?php echo T_('Send by mail') ?>" onclick="return openselectedfiles(true);" />
	<input type="button" name="selaction" value="<?php echo T_('Open in new windows') ?>" onclick="openselectedfiles(); return false;" />
	&mdash; <?php
	disp_cond( $Fileman->countDirs(), T_('One directory'), T_('%d directories'), T_('No directories') );
	echo ', ';
	disp_cond( $Fileman->countFiles(), T_('One file'), T_('%d files'), T_('No files' ) );
	echo ', '.bytesreadable( $Fileman->countBytes() );
	?>
</td></tr>
<?php
}}}
?>
</tbody>
</table>
</form>

<div class="toolbar">
	<?php
	param( 'options_show', 'integer', 0 );
	?>
	<form class="toolbaritem" action="files.php" method="post">
		<a id="options_toggle" href="<?php echo url_add_param( $Fileman->curl(), ( !$options_show ? 'options_show=1' : '' ) ) ?>"
			onclick="return toggle_options();"><?php
			echo ( $options_show ) ? T_('hide options') : T_('show options') ?></a>

		<div id="options_list"<?php if( !$options_show ) echo ' style="display:none"' ?>>
			<input type="checkbox" id="option_dirsattop" name="option_dirsattop" value="1"<?php if( $UserSettings->get('fm_dirsattop') ) echo ' checked="checked"' ?> />
			<label for="option_dirsattop"><?php echo T_('Sort directories at top') ?></label>
			<br />
			<input type="checkbox" id="option_showhidden" name="option_showhidden" value="1"<?php if( $UserSettings->get('fm_showhidden') ) echo ' checked="checked"' ?> />
			<label for="option_showhidden"><?php echo T_('Show hidden files') ?></label>
			<br />
			<input type="checkbox" id="option_permlikelsl" name="option_permlikelsl" value="1"<?php if( $UserSettings->get('fm_permlikelsl') ) echo ' checked="checked"' ?> />
			<label for="option_permlikelsl"><?php echo T_('File permissions like &quot;ls -l&quot;') ?></label>
			<br />
			<input type="checkbox" id="option_recursivedirsize" name="option_recursivedirsize" value="1"<?php if( $UserSettings->get('fm_recursivedirsize') ) echo ' checked="checked"' ?> />
			<label for="option_recursivedirsize"><?php echo T_('Recursive size of directories') ?></label>
			<br />

			<?php echo $Fileman->form_hiddeninputs() ?>
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

	<form action="files.php" name="filter" class="toolbaritem">
		<?php echo $Fileman->form_hiddeninputs() ?>
		<input type="hidden" name="action" value="upload" />
		<input type="submit" value="<?php echo format_to_output( T_('Upload a file/image'), 'formvalue' ) ?>" />
	</form>

	<form action="files.php" name="filter" class="toolbaritem">
		<select name="createnew">
			<option value="file"><?php echo T_('file') ?></option>
			<option value="dir"><?php echo T_('directory') ?></option>
		</select>
		<input type="text" name="createname" value="" size="20" />
		<input type="submit" value="<?php echo format_to_output( T_('Create new'), 'formvalue' ) ?>" />
		<?php echo $Fileman->form_hiddeninputs() ?>
		<input type="hidden" name="action" value="createnew" />
	</form>
</div>
</div>
<br class="clear" />
</div>
<?php
require( dirname(__FILE__). '/_footer.php' );
#echo replacePngTags( ob_get_clean(), $img_url);
?>