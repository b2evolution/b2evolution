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
/**
 * Load FileManager class
 */
require_once dirname(__FILE__).'/'.$admin_dirout.$core_subdir.'_filemanager.class.php';

param( 'root', 'string', NULL );     // the root directory from the dropdown box (user_X or blog_X; X is ID - 'user' for current user (default))
param( 'path', 'string', '/' );       // the path relative to the root dir
param( 'action', 'string', '' );     // 3.. 2.. 1.. action :)

param( 'order', 'string', NULL );
param( 'orderasc', '', NULL );
param( 'filterString', '', NULL );
param( 'filterIsRegexp', 'integer', NULL );
param( 'flatmode', '', NULL );


if( $current_User->login != 'demouser' && $current_User->level < 10 )
{ // allow demouser, but noone else below level 10
	echo 'The filemanager is still beta. You need user level 10 to play with this.';
	return;
}

if( param( 'rootIDAndPath', 'string', '' ) )
{ // root and path together: decode and override
	$rootIDAndPath = unserialize( $rootIDAndPath );
	$root = $rootIDAndPath['id'];
	$path = $rootIDAndPath['path'];
}

if( $action == 'update_settings' )
{ // updating user settings
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

/**
 * @var Filelist the selected files
 */
$selectedFiles = $Fileman->getFilelistSelected();


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

			$Fileman->createDirOrFile( $createnew, $createname );
			break;


		case T_('Send by mail'):
			if( !$selectedFiles->count() )
			{
				$Fileman->Messages->add( T_('Nothing selected.') );
				break;
			}

			echo 'TODO: Send selected by mail, query email address..';
			break;


		case T_('Download'):
			// TODO: provide optional zip formats
			if( !$selectedFiles->count() )
			{
				$Fileman->Messages->add( T_('Nothing selected.') );
				break;
			}

			param( 'zipname', 'string', '' );
			param( 'exclude_sd', 'integer', 0 );

			if( empty($zipname) )
			{
				$msg_action = '
				<p>
				'.T_('You want to download:').'<ul>';

				$atLeastOneDir = false;

				$selectedFiles->restart();

				while( $lFile =& $selectedFiles->getNextFile() )
				{
					if( $lFile->isDir() )
					{
						$msg_action .= sprintf('<li>'.T_('Directory &laquo;%s&raquo;')."</li>\n", $lFile->getName());
						$atLeastOneDir = true;
					}
					else $msg_action .= sprintf('<li>'.T_('File &laquo;%s&raquo;')."</li>\n", $lFile->getName());
				}

				$msg_action .= '
				</p>
				</div>

				<div class="panelblock">
				<form action="files.php" class="fform" method="post">
				<fieldset>
					<legend>'.T_('Download options').'</legend>';

					foreach( $selectedFiles->entries as $lFile )
					{
						$msg_action .= '<input type="hidden" name="fm_selected[]" value="'
														.$lFile->getID()."\" />\n";
					}

					$msg_action .= $Fileman->getFormHiddenInputs()."\n"
											.form_text( 'zipname', '', 20, T_('Archive filename'), T_("This is the file's name that will get sent to you."), 80, '', 'text', false )."\n"
											.( $atLeastOneDir ?
													form_checkbox( 'exclude_sd', $exclude_sd, T_('Exclude subdirectories'), T_('This will exclude subdirectories of selected directories.'), '', false )."\n" :
													'' )
											.'
											<div class="input"><input type="submit" name="selaction" value="'
											.format_to_output( T_('Download'), 'formvalue' ).'" />
											</div>
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

				foreach( $selectedFiles->entries as $File )
				{
					$arraylist[] = $File->getName();
				}
				$zipfile->add_files( $arraylist );

				$zipfile->create_archive();

				#header('Content-length: ' . filesize($path));
				$zipfile->download_file();
				exit;
				#$Fileman->Messages->add( sprintf(T_('Zipfile &laquo;%s&raquo; sent to you!'), $zipname), 'note' );

			}

			break;


		case 'delete': // delete a file/dir, TODO: checkperm! {{{
			if( !$selectedFiles->count() )
			{
				$Fileman->Messages->add( T_('Nothing selected.') );
				break;
			}

			param( 'confirmed', 'integer', 0 );
			param( 'delsubdirs', 'array', array() );

			if( !$confirmed )
			{
				$msg_action = '
					<form action="" class="inline">
						<input type="hidden" name="confirmed" value="1" />
						<input type="hidden" name="action" value="delete" />
						'.$Fileman->getFormHiddenSelectedFiles()
						.$Fileman->getFormHiddenInputs()."\n";


				$msg_action .= $selectedFiles->count() > 1 ?
												T_('Do you really want to delete the following files?') :
												T_('Do you really want to delete the following file?');

				$msg_action .= '
				<ul>
				';

				foreach( $selectedFiles->entries as $lFile )
				{
					$msg_action .= '<li>'.$lFile->getName();

					if( $lFile->isDir() )
					{
						$msg_action .= '
							<br />
							<input title="'.sprintf( T_('Check to include subdirectories of &laquo;%s&raquo;'), $lFile->getName() ).'"
								type="checkbox"
								name="delsubdirs['.$lFile->getID().']"
								id="delsubdirs_'.$lFile->getID().'"
								value="1" />
								<label for="delsubdirs_'.$lFile->getID().'">'
									.T_( 'Including subdirectories' ).'</label>';
					}

					$msg_action .= '</li>';
				}

				$msg_action .= "</ul>\n";


				$msg_action .= '
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
				$selectedFiles->restart();
				while( $lFile =& $selectedFiles->getNextFile() )
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
			if( !$selectedFiles->count() )
			{
				$Fileman->Messages->add( T_('Nothing selected.') );
				break;
			}

			param( 'perms', 'array', array() );

			if( count( $perms ) )
			{
				$selectedFiles->restart();
				while( $lFile =& $selectedFiles->getNextFile() )
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
				$msg_action = '
				<form name="form_chmod" action="files.php">
				'.$Fileman->getFormHiddenSelectedFiles()
				.$Fileman->getFormHiddenInputs().'

				<input type="hidden" name="action" value="editperm" />
				';

				if( is_windows() )
				{
					if( $selectedFiles->count() > 1 )
					{ // more than one file, provide default

					}
					foreach( $selectedFiles->entries as $lFile )
					{
						$msg_action .= "\n".$Fileman->getFileSubpath( $lFile ).':<br />
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
							.( $lFile->getPerms( 'octal' ) == 666 ?
									'checked="checked"' :
									'' ).' />
						<label for="perms_readwrite_'.$lFile->getID().'">'.T_('Read and write').'</label>
						<br />';
					}
				}
				else
				{
					$msg_action .= '<input type="text" name="chmod" value="'
													.$lFile->getPerms( 'octal' ).'" maxlength="3" size="3" /><br />';
					$js_focus = 'document.form_chmod.chmod';
				}

				$msg_action .= '
				<input type="submit" value="'.format_to_output( T_('Set new permissions'), 'formvalue' ).'" />
				</form>
				';

			}
			// }}}
			break;


		case 'default': // default action (view) {{{
			if( !$selectedFiles->count() )
			{
				$Fileman->Messages->add( T_('Nothing selected.') );
				break;
			}

			$selectedFile =& $selectedFiles->getFileByIndex(0);

			// TODO: check if available

			?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
			<html xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
			<head>
				<title><?php echo $selectedFile->getName().' :: '.$app_name.' '.T_('Filemanager') ?></title>
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
				if( isImage( $selectedFile->getName() ) )
				{ // display image file
					?>
					<div class="center">
						<img alt="<?php echo T_('The selected image') ?>"
							class="framed"
							src="<?php echo $Fileman->getFileUrl( $selectedFile ) ?>"
							<?php echo $selectedFile->getImageSize( 'string' ) ?> />
					</div>
					<?php
				}
				elseif( ($buffer = @file( $selectedFile->getPath() )) !== false )
				{{{ // display raw file
					param( 'showlinenrs', 'integer', 0 );

					$buffer_lines = count( $buffer );

					// TODO: check if new window was opened and provide close X in case
					/*<a href="javascript:window.close()"><img class="center" src="<?php echo $admin_url.'img/xross.gif' ?>" width="13" height="13" alt="[X]" title="<?php echo T_('Close this window') ?>" /></a>*/

					echo '<div class="fileheader">';
					echo T_('File').': '.$selectedFile->getName().'<br />';

					if( !$buffer_lines )
					{
						echo '</div> ** '.T_('empty file').' ** ';
					}
					else
					{
						printf( T_('%d lines'), $buffer_lines ).'<br />';
						$linenr_width = strlen( $buffer_lines+1 );

						?>
						<noscript type="text/javascript">
							<a href="<?php echo $Fileman->getLinkFile( $selectedFile ).'&amp;showlinenrs='.(1-$showlinenrs); ?>">

							<?php echo $showlinenrs ?
													T_('hide line numbers') :
													T_('show line numbers');
							?></a>
						</noscript>
						<script type="text/javascript">
						<!--
						document.write('<a id="togglelinenrs" href="javascript:toggle_linenrs()">toggle</a>');
						//-->
						</script>

						</div>
						<pre class="rawcontent"><?php

						for( $i = 0; $i < $buffer_lines; $i++ )
						{
							echo '<span name="linenr" class="linenr">';
							if( $showlinenrs )
							{
								echo ' '.str_pad($i+1, $linenr_width, ' ', STR_PAD_LEFT).' ';
							}
							echo '</span>'.htmlspecialchars( str_replace( "\t", '  ', $buffer[$i] ) );  // TODO: customize tab-width
						}

						?>

						<script type="text/javascript">
						<!--
						showlinenrs = <?php var_export( !$showlinenrs ); ?>;
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
					Log::display( '', '', sprintf( T_('The file &laquo;%s&raquo; could not be accessed!'),
																					$Fileman->getFileSubpath( $selectedFile ) ), 'error' );
				}
				?>
			</body>
		</html>
		<?php
		// }}}
			exit;


		case 'leaveMode': // leave mode (upload, ..)
			$Fileman->mode = NULL;
			header( 'Location: '.$Fileman->getCurUrl() );
			break;
	}
}


$admin_tab = 'files';
$admin_pagetitle = T_('Filemanager').' (beta)';

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
		$allowedMimeTypes = preg_split( '/\s+/', trim( $Settings->get( 'upload_allowedmime' ) ) );

		if( isset($_FILES) && count( $_FILES ) )
		{{{ // Process uploaded files
			param( 'uploadfile_name', 'array', array() );

			foreach( $_FILES['uploadfile']['name'] as $lKey => $lName )
			{
				if( empty($lName) )
				{ // no name
					continue;
				}

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
							continue;

						case UPLOAD_ERR_PARTIAL:
							$LogUpload->add( sprintf( T_('The uploaded file &laquo;%s&raquo; was only partially uploaded.'), $lName ) );
							continue;

						case UPLOAD_ERR_NO_FILE:
							$LogUpload->add( sprintf( T_('No file was uploaded (%s).'), $lName ) );
							continue;
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
				elseif( $allowedFileExtensions
								&& !preg_match( '#\.'.preg_replace( array( '#\s+#', '/#/' ), array( '|', '\#' ), $allowedFileExtensions ).'$#', $newName  ) )
				{
					$LogUpload->add( sprintf( T_('The file extension of &laquo;%s&raquo; is not allowed.'), $newName ) );
					continue;
				}


				$newFile =& getFile( $lName, $Fileman->getCwd() );

				if( $newFile->exists() )
				{
					// TODO: Rename/Overwriting
					$LogUpload->add( sprintf( T_('The file &laquo;%s&raquo; already exists.'), $newFile->getName() ) );
					continue;
				}
				elseif( move_uploaded_file( $_FILES['uploadfile']['tmp_name'][$lKey], $newFile->getPath() ) )
				{
					$LogUpload->add( sprintf( T_('The file &laquo;%s&raquo; has been successfully uploaded.'), $newFile->getName() ), 'note' );

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
				var fileDivLabel = document.createElement("div");
				fileDivLabel.className = "label";
				var fileLabel = document.createElement("label");
				var fileLabelText = document.createTextNode( labelText );
				fileLabel.appendChild( fileLabelText );
				fileDivLabel.appendChild( fileLabel );
				appendTo.appendChild( fileDivLabel );

				var fileDivInput = document.createElement("div");
				fileDivInput.className = "input";
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
				fileDivInput.appendChild( fileInput );
				appendTo.appendChild( fileDivInput );
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

				newLI.appendChild( document.createElement("hr") );

				appendLabelAndInputElements( newLI, "<?php echo T_('Choose a file'); ?>:", "input", "uploadfile[]", "40", "0", "file" );
				appendLabelAndInputElements( newLI, "<?php echo T_('Alternative text'); ?>:", "input", "uploadfile_alt[]", "40", "80", "text" );
				appendLabelAndInputElements( newLI, "<?php echo T_('Description of the file'); ?>:", "textarea", "uploadfile_desc[]", "40", "3" );
				appendLabelAndInputElements( newLI, "<?php echo T_('New filename (without path)'); ?>:", "input", "uploadfile_name[]", "40", "80", "text" );
			}
		</script>


		<div class="panelblock">
			<form enctype="multipart/form-data" action="files.php" method="post" class="fform">
				<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $Settings->get( 'upload_maxkb' )*1024 ?>" />
				<?php echo $Fileman->getFormHiddenInputs() ?>
				<fieldset>
					<legend><?php echo T_('File upload')  ?></legend>

					<fieldset class="fm_upload_dirselect">
						<legend><?php echo T_('Upload files into:'); ?></legend>

						<?php
						echo $Fileman->getDirectoryTreeRadio();
						?>
					</fieldset>

					<fieldset>
						<p>
							<?php
							$restrictNotes = array();

							if( $allowedFileExtensions )
							{
								$restrictNotes[] = T_('Allowed file extensions').': '.str_replace( ' ', ', ', $allowedFileExtensions );
							}
							if( $allowedMimeTypes )
							{
								$restrictNotes[] = T_('Allowed MIME types').': '.implode(', ', $allowedMimeTypes);
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

						<?php $LogUpload->display( '', '', true, 'all' ); ?>


						<fieldset>
							<legend><?php echo T_('Files to upload') ?></legend>

							<ul id="uploadfileinputs" class="plain">
								<li>
									<div class="label"><label><?php echo T_('Choose a file'); ?>:</label></div>
									<div class="input"><input name="uploadfile[]" type="file" size="40" /></div>

									<div class="label"><label><?php echo T_('Alternative text'); ?></label>:</div>
									<div class="input"><input name="uploadfile_alt[]"  type="text" size="40" maxlength="80" /></div>

									<div class="label"><label><?php echo T_('Description of the file'); /* TODO: maxlength */ ?></label>:</div>
									<div class="input"><textarea name="uploadfile_desc[]" rows="3" cols="40"></textarea></div>

									<div class="label"><label><?php echo T_('New filename (without path)'); ?></label>:</div>
									<div class="input"><input name="uploadfile_name[]" type="text" size="40" maxlength="80" /></div>
								</li></ul> <?php /* no text after </li> or JS will bite you! */ ?>
						</fieldset>

					</fieldset>

					<fieldset class="submit">
						<input class="ActionButton" type="button" value="<?php echo T_('Add another file') ?>" onclick="addAnotherFileInput();" />
						<input class="ActionButton" type="submit" value="<?php echo T_('Upload !') ?>" />
					</fieldset>
				</fieldset>

			</form>

		</div>

		<?php
		// }}}

		// }}}
		break;


	case 'file_cmr': // copy/move/rename a file {{{
		$LogCmr = new Log( 'error' );  // Log for copy/move/rename mode

		if( !$Fileman->SourceList )
		{
			$Fileman->Messages->add( sprintf( T_('No source files!') ) );
			break;
		}

		param( 'cmr_keepsource', 'integer', 0 );
		param( 'cmr_newname', 'array', array() );
		param( 'cmr_overwrite', 'array', array() );
		param( 'cmr_doit', 'integer', 0 );


		if( $cmr_doit )
		{{{ // we want Action!
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
		}}}


		if( !$cmr_doit || $LogCmr->count( 'all' ) )
		{
			$SourceFile =& $Fileman->SourceList->getNextFile();
			$Fileman->SourceList->restart();

			// text and value for JS dynamic fields, when referring to move/rename
			if( $SourceFile->getDir() == $Fileman->getCwd() )
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
						echo T_('Copy / Move / Rename');
						?></legend>

						<div class="notes">
							<?php
							echo '<strong>'.T_('You are in copy-move-rename mode.')
										.'</strong><br />'.T_('Please navigate to the desired location.'); ?>
						</div>

						<?php

						$LogCmr->display( '', '', true, 'all' );

						while( $SourceFile =& $Fileman->SourceList->getNextFile() )
						{
							?>

							<fieldset>
								<legend><?php echo T_('Source').': '.$SourceFile->getPath();
								?></legend>

								<?php


								if( isset( $cmr_overwrite[$SourceFile->getID()] )
										&& $cmr_overwrite[$SourceFile->getID()] === 'ask' )
								{
									form_checkbox( 'overwrite', 0, '<span class="error">'.T_('Overwrite existing file').'</span>',
																	sprintf( T_('The existing file &laquo;%s&raquo; will be replaced with this file.'),
																						$TargetFile->getPath() ) );
								}
								?>

								<div class="label">
									<label for="cmr_keepsource_<?php $SourceFile->getID(); ?>"><?php echo T_('Keep source file') ?>:</label>
								</div>
								<div class="input">
									<input class="checkbox" type="checkbox" value="1"
										name="cmr_keepsource[<?php echo $SourceFile->getID(); ?>]"
										id="cmr_keepsource_<?php $SourceFile->getID(); ?>"
										onclick="setCmrSubmitButtonValue( this.form );"<?php
										if( $cmr_keepsource )
										{
											echo ' checked="checked"';
										} ?> />
									<span class="notes"><?php echo T_('Do not delete the source file.') ?></span>
								</div>
								<div class="clear"></div>


								<div class="label">
									<label for="cmr_newname_<?php $SourceFile->getID(); ?>">New name:</label>
								</div>
								<div class="input">
									<input type="text" name="cmr_newname[<?php $SourceFile->getID(); ?>]"
										id="cmr_newname_<?php $SourceFile->getID(); ?>" value="<?php
										echo isset( $cmr_newname[$SourceFile->getID()] ) ?
														$cmr_newname[$SourceFile->getID()] :
														$SourceFile->getName() ?>" />
								</div>

							</fieldset>

						<?php
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
			opener.document.FilesForm.md5_filelist.value == '<?php echo $Fileman->toMD5(); ?>' ?
				'none' :
				'inline';
	}
	// -->
</script>


<?php
if( $Fileman->getMode() == 'file_upload' ) // TODO: generalize
{
	?>

	<p class="center">

		<?php
		if( $UserSettings->get('fm_forceFM') != 1 )
		{ // FM is not forced anyway
			?>

			<a class="ActionButton"
				href="<?php echo $Fileman->getCurUrl( array( 'forceFM' => !$Fileman->forceFM ) ); ?>">
				<?php echo $Fileman->forceFM ?
										T_('Hide Filemanager') :
										T_('Display Filemanager'); ?></a>

			&middot;

			<?php
		}

		?>

		<a class="ActionButton" href="<?php echo $Fileman->getCurUrl( array( 'mode' => false ) ) ?>">
			<?php echo /* TRANS: Button to leave the upload mode */ T_('Leave upload mode'); ?></a>

	</p>

	<?php

	if( !$Fileman->forceFM )
	{ // what a pity.. ;)
		?>

		</div>

		<?php
		require( dirname(__FILE__). '/_footer.php' );
		return;
	}
}

?>

<div class="panelblock">
<?php
	// ---------------------------------------------------
	// Display main user interface : file list & controls:
	// ---------------------------------------------------

	/* Not implemented yet:

	<!-- SEARCH BOX: -->

	<form action="files.php" name="search" class="toolbaritem">
		<?php echo $Fileman->getFormHiddenInputs() ?>
		<input type="hidden" name="action" value="search" />
		<input type="text" name="searchfor" value="--todo--" size="20" />
		<input class="ActionButton" type="submit" value="<?php echo format_to_output( T_('Search'), 'formvalue' ) ?>" />
	</form>

	*/
	?>

	<!-- FILTER BOX: -->

	<div class="toolbaritem">
		<form action="files.php" name="filter" class="inline">
			<label for="filterString" id="filterString" class="tooltitle"><?php echo T_('Filter') ?>:</label>
			<?php echo $Fileman->getFormHiddenInputs( array( 'filterString' => false, 'filterIsRegexp' => false ) ) ?>
			<input type="text" name="filterString" value="<?php echo format_to_output( $Fileman->getFilter( false ), 'formvalue' ) ?>" size="20" />
			<input type="checkbox" name="filterIsRegexp" id="filterIsRegexp" title="<?php
				echo format_to_output( T_('Filter is regular expression'), 'formvalue' )
				?>" value="1"<?php if( $filterIsRegexp ) echo ' checked="checked"' ?> /><?php
				echo '<label for="filterIsRegexp">'./* TRANS: short for "is regular expression" */ T_('RegExp').'</label>'; ?>

			<input class="ActionButton" type="submit" value="<?php echo format_to_output( T_('Apply'), 'formvalue' ) ?>" />
		</form>

		<?php
		if( $Fileman->isFiltering() )
		{ // "reset filter" form
		?>
		<form action="files.php" name="unfilter" class="inline">
			<?php echo $Fileman->getFormHiddenInputs( array( 'filterString' => false, 'filterIsRegexp' => false ) ) ?>
			<input class="ActionButton" type="submit" value="<?php echo format_to_output( T_('Disable'), 'formvalue' ) ?>" />
		</form>
		<?php
		}
		?>

	</div>


	<!-- FLAT MODE: -->

	<form action="files.php" name="flatmode" class="toolbaritem">
		<?php echo $Fileman->getFormHiddenInputs( array( 'flatmode' => false ) ) ?>
		<input type="hidden" name="flatmode" value="<?php echo $flatmode ? 0 : 1; ?>" />
		<input class="ActionButton" type="submit" title="<?php
			echo format_to_output( $flatmode ?
															T_('Normal mode') :
															T_('All files and folders, including subdirectories'), 'formvalue' );
			?>" value="<?php
			echo format_to_output( $flatmode ?
															T_('Normal mode') :
															T_('Flat mode'), 'formvalue' ); ?>" />
	</form>
</div>

<div class="clear"></div>


<div class="panelblock">

<form name="FilesForm" id="FilesForm" action="files.php" method="get">
<input type="hidden" name="confirmed" value="0" />
<input type="hidden" name="md5_filelist" value="<?php echo $Fileman->toMD5() ?>" />
<input type="hidden" name="md5_cwd" value="<?php echo md5($Fileman->getCwd()) ?>" />
<?php echo $Fileman->getFormHiddenInputs() ?>


<table class="grouped">

<?php
/**
 * @global integer Number of cols for the files table, 8 by default
 */
$filetable_cols = 8;

?>

<thead>
<tr>
	<td colspan="<?php echo $filetable_cols ?>">

	<?php
	$rootlist = $Fileman->getRootList();
	if( count($rootlist) > 1 )
	{ // provide list of roots
	?>
		<!-- ROOT LISTS -->

		<div class="fm_roots">

			<select name="root" class="fm_roots" onchange="this.form.submit()">
			<?php
			foreach( $rootlist as $lroot )
			{
				echo '<option value="'.$lroot['id'].'"';

				if( $root == $lroot['id']
						|| $root === NULL && $lroot['id'] == 'user' )
				{
					echo ' selected="selected"';
				}

				echo '>'.format_to_output( $lroot['name'] )."</option>\n";
			}

			echo '</select>

			<input class="ActionButton" type="submit" value="'.T_('Change root').'" />
		</div>
		';
	}
	?>


	<?php
	// -----------------------------------------------
	// Display table header: directory location info:
	// -----------------------------------------------


	// Quick links to usual homes for user, group and maybe blog...:
	// echo '<a title="'.T_('Go to your home directory').'" class="middle" href="'.$Fileman->getLinkHome().'">'.getIcon( 'folder_home' ).'</a> &nbsp;';
	// TODO: add group home...
	// TODO: add blog home?


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
	</td>
</tr>
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
while( $lFile =& $Fileman->getNextFile() )
{ // loop through all Files:
	?>

	<tr<?php
		if( $countFiles%2 ) echo ' class="odd"';
		?> onclick="document.getElementById('cb_filename_<?php echo $countFiles; ?>').click();">
		<td class="checkbox">
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
				?>"><?php echo getIcon( $lFile ) ?></a>
		</td>

		<td class="filename">
			<a href="<?php echo $Fileman->getLinkFile( $lFile ) ?>"
				target="fileman_default"
				title="<?php echo T_('Open in a new window'); ?>"
				onclick="return false;">

				<button class="filenameIcon" type="button"
					id="button_new_<?php echo $countFiles ?>"
					onclick="document.getElementById('cb_filename_<?php echo $countFiles; ?>').click();
						<?php

						$imgsize = $lFile->getImageSize( 'widthheight' );
						echo $Fileman->getJsPopupCode( NULL,
							"'+( typeof(fm_popup_type) == 'undefined' ? 'fileman_default' : 'fileman_popup_$countFiles')+'",
							($imgsize ? $imgsize[0]+42 : NULL),
							($imgsize ? $imgsize[1]+42 : NULL) );

						?>"
					><?php
					echo getIcon( 'window_new' );
				?></button></a>

			<?php

			if( !isFilename( $lFile->getName() ) )
			{
				// TODO: Warning icon with hint
			}

			?>


			<a href="<?php echo $Fileman->getLinkFile( $lFile ) ?>"><?php
				if( $Fileman->flatmode && $Fileman->getOrder() == 'path' )
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
		<td class="timestamp"><?php echo $lFile->getLastMod() ?></td>
		<td class="perms"><?php $Fileman->dispButtonFileEditPerms() ?></td>
		<td class="actions"><?php
			// Not implemented yet: $Fileman->dispButtonFileEdit();
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
{ // -------------
	// Footer with "check all", "with selected: ..":
	// --------------
?>
<tr class="group">
	<td colspan="<?php echo $filetable_cols ?>">
	<a id="checkallspan_0" href="<?php
		echo url_add_param( $Fileman->getCurUrl(), 'checkall='.( $checkall ? '0' : '1' ) );
		?>" onclick="toggleCheckboxes('FilesForm', 'fm_selected[]'); return false;"><?php
		echo ($checkall) ? T_('uncheck all') : T_('check all');
		?></a>
	&mdash; <strong><?php echo T_('with selected files:') ?> </strong>

	<input class="DeleteButton" type="image"
		title="<?php echo T_('Delete the selected files') ?>"
		name="action"
		value="delete"
		src="<?php echo getIcon( 'file_delete', 'url' ) ?>"
		onclick="if( r = openselectedfiles(true) )
							{
								if( confirm('<?php echo /* TRANS: Warning this is a javascript string */ T_('Do you really want to delete the selected files?') ?>') )
								{
									document.getElementById( 'FilesForm' ).confirmed.value = 1;
									return true;
								}
							}; return false;" />

	<!-- Not implemented yet: input class="ActionButton" type="submit" name="action" value="<?php echo T_('Download') ?>" onclick="return openselectedfiles(true);" / -->
	<!-- Not implemented yet: input class="ActionButton" type="submit" name="action" value="<?php echo T_('Send by mail') ?>" onclick="return openselectedfiles(true);" / -->

	<?php

	/*
	TODO: "link these into current post" (that is to say the post that opened the popup window).
				This would create <img> or <a href> tags depending on file types.
	*/

	?>

	<input class="ActionButton" type="image" name="action"
		title="<?php echo T_('Open in new windows'); ?>"
		value="open_in_new_windows"
		src="<?php echo getIcon( 'window_new', 'url' ) ?>"
		onclick="openselectedfiles(); return false;" />

	<?php
	/* Not fully functional
	<input class="ActionButton" type="image" name="action"
		title="<?php echo T_('Rename the selected files'); ?>"
		value="file_cmr"
		onclick="return openselectedfiles(true);"
		src="<?php echo getIcon( 'file_rename', 'url' ); ?>" />

	<input class="ActionButton" type="image" name="action"
		title="<?php echo T_('Copy the selected files'); ?>"
		value="file_cmr"
		onclick="return openselectedfiles(true);"
		src="<?php echo getIcon( 'file_copy', 'url' ); ?>" />

	<input class="ActionButton" type="image" name="action"
		title="<?php echo T_('Move the selected files'); ?>"
		value="file_cmr"
		onclick="return openselectedfiles(true);"
		src="<?php echo getIcon( 'file_move', 'url' ); ?>" />

	<input class="ActionButton" type="image" name="action" value="editperm"
		onclick="return openselectedfiles(true);"
		title="<?php echo T_('Change permissions'); ?>"
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
?>


<!-- CREATE: -->

<form action="" class="toolbaritem">
	<label class="tooltitle"><?php echo T_('New'); ?></label>
	<select name="createnew">
		<option value="file"><?php echo T_('file') ?></option>
		<option value="dir"<?php
			if( isset($createnew) && $createnew == 'dir' )
			{
				echo ' selected="selected"';
			} ?>><?php echo T_('directory') ?></option>
	</select>
	:
	<input type="text" name="createname" value="<?php
		if( isset( $createname ) )
		{
			echo $createname;
		} ?>" size="20" />
	<input class="ActionButton" type="submit" value="<?php echo format_to_output( T_('Create!'), 'formvalue' ) ?>" />
	<?php echo $Fileman->getFormHiddenInputs() ?>
	<input type="hidden" name="action" value="createnew" />
</form>


<!-- UPLOAD: -->

<form action="" class="toolbaritem">
	<?php $Fileman->dispButtonUploadMode(); ?>
</form>


<div class="clear"></div>

<fieldset class="iconlegend">
	<legend><?php echo T_('Icon legend') ?></legend>
	<ul class="iconlegend">
		<li><?php echo getIcon( 'folder_home' ).' '.T_('Home dir'); ?></li>
		<li><?php echo getIcon( 'folder_parent' ).' '.T_('Up one level'); ?></li>
		<li><?php echo getIcon( 'window_new' ).' '.T_('Open in new window'); ?></li>
		<!-- Not implemented yet: span class="nobr"><?php echo getIcon( 'file_edit' ).' '.T_('Edit file'); ?></span -->
		<li><?php echo getIcon( 'file_copy' ).' '.T_('Copy'); ?></li>
		<li><?php echo getIcon( 'file_move' ).' '.T_('Move'); ?></li>
		<li><?php echo getIcon( 'file_rename' ).' '.T_('Rename'); ?></li>
		<li><?php echo getIcon( 'file_delete' ).' '.T_('Delete'); ?></li>
		<li><?php echo getIcon( 'file_perms' ).' '.T_('Change permissions'); ?></li>
	</ul>
</fieldset>

<fieldset>
<legend><?php echo T_('Information') ?></legend>

<ul>
	<li><?php echo T_("Clicking on a file's name invokes the default action (images get displayed as image, raw content for all other files)."); ?></li>
	<li><?php echo T_("Clicking on a file's icon lets the browser handle the file."); ?></li>
</ul>
</fieldset>
<?php


// ------------------
// Display options:
// Let's keep these at the end since they won't be accessed more than occasionaly
// and only by advanced users
// ------------------
param( 'options_show', 'integer', 0 );
?>
<form id="options_form" action="files.php" method="post">
	<fieldset>
	<legend><a id="options_toggle" href="<?php
	echo url_add_param( $Fileman->getCurUrl(), ( !$options_show ?
																									'options_show=1' :
																									'' ) )
	?>" onclick="return toggle_options();"><?php
	echo $options_show ?
				T_('Hide options') :
				T_('Show options'); ?></a></legend>

	<div id="options_list"<?php if( !$options_show ) echo ' style="display:none"' ?>
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
		<input type="hidden" name="action" value="update_settings" />
		<input type="hidden" name="options_show" value="1" />

		<div class="input">
			<input type="submit" value="<?php echo T_('Update !') ?>" />
		</div>
	</div>
	</fieldset>

	<script type="text/javascript">
	<!--
		showoptions = <?php echo ($options_show) ? 'true' : 'false' ?>;

		function toggle_options()
		{
			if( showoptions )
			{
				var replace = document.createTextNode('<?php echo /* TRANS: Warning this is a javascript string */ T_('Show options') ?>');
				var display_list = 'none';
				showoptions = false;
			}
			else
			{
				var replace = document.createTextNode('<?php echo /* TRANS: Warning this is a javascript string */ T_('Hide options') ?>');
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
</div>

<?php
require( dirname(__FILE__). '/_footer.php' );


/*
 * $Log$
 * Revision 1.59  2005/01/06 11:31:46  blueyed
 * bugfixes
 *
 * Revision 1.58  2005/01/06 10:15:46  blueyed
 * FM upload and refactoring
 *
 */
?>