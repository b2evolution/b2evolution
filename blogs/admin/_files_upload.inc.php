<?php
/**
 * This file implements the UI for file upload.
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

if( false )
{	/**
	 * This is ugly, sorry, but I temporarily need this until NuSphere fixes their CodeInsight :'(
	 */
	include('_header.php');
	include('files.php');
}
?>

<script type="text/javascript">
	<!--
	/**
	 * Mighty cool function to append an input or textarea element onto another element.
	 *
	 * @usedby addAnotherFileInput()
	 *
	 * @author proud daniel hahler :)
	 */
	function appendLabelAndInputElements( appendTo, labelText, labelBr, inputOrTextarea, inputName,
																				inputSizeOrCols, inputMaxLengthOrRows, inputType, inputClass )
	{
		// LABEL:

		// var fileDivLabel = document.createElement("div");
		// fileDivLabel.className = "label";

		var fileLabel = document.createElement('label');
		var fileLabelText = document.createTextNode( labelText );
		fileLabel.appendChild( fileLabelText );

		// fileDivLabel.appendChild( fileLabel )

		appendTo.appendChild( fileLabel );

		if( labelBr )
		{	// We want a BR after the label:
			appendTo.appendChild( document.createElement('br') );
		}
		else
		{
			appendTo.appendChild( document.createTextNode( ' ' ) );
		}

		// INPUT:

		// var fileDivInput = document.createElement("div");
		// fileDivInput.className = "input";

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

		fileInput.className = inputClass;

		// fileDivInput.appendChild( fileInput );

		appendTo.appendChild( fileInput );
		appendTo.appendChild( document.createElement('br') );
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


		appendLabelAndInputElements( newLI, '<?php echo TS_('Choose a file'); ?>:', false,
																	'input', 'uploadfile[]', '20', '0', 'file', '' );
		appendLabelAndInputElements( newLI, '<?php echo TS_('Filename on server (optional)'); ?>:', false,
																	'input', 'uploadfile_name[]', '20', '80', 'text', '' );
 		appendLabelAndInputElements( newLI, '<?php echo TS_('Long title'); ?>:', true,
 																	'input', 'uploadfile_title[]', '50', '255', 'text', 'large' );
 		appendLabelAndInputElements( newLI, '<?php echo TS_('Alternative text (useful for images)'); ?>:', true,
 																	'input', 'uploadfile_alt[]', '50', '255', 'text', 'large' );
		appendLabelAndInputElements( newLI, '<?php echo TS_('Caption/Description of the file'); ?>:', true,
																	'textarea', 'uploadfile_desc[]', '38', '3', '', 'large' );
	}
	// -->
</script>

<?php
	// Begin payload block:
	$AdminUI->dispPayloadBegin();

	$Form = & new Form( 'files.php', '', 'post', 'fieldset', 'multipart/form-data' );

	$Form->global_icon( T_('Quit upload mode!'), 'close',	$Fileman->getCurUrl( array( 'fm_mode' => false, 'forceFM' => 1 ) ) );

	$Form->begin_form( 'fform', T_('File upload') );

		$Form->hidden( 'MAX_FILE_SIZE', $Settings->get( 'upload_maxkb' )*1024 );

		// we'll use $rootIDAndPath only
		echo $Fileman->getFormHiddenInputs( array( 'root' => false, 'path' => false ) );
		$Form->hidden( 'rootIDAndPath', serialize( array( 'id' => $Fileman->root, 'path' => $Fileman->path ) ) );


		if( count( $failedFiles ) )
		{
			$LogUpload->add( T_('Some file uploads failed. Please check the errors below.'), 'note' );
		}
		$LogUpload->display( '', '', true, 'all' ); ?>


		<fieldset class="files_to_upload">
			<legend><?php echo T_('Files to upload') ?></legend>

			<p>
				<?php
				$restrictNotes = array();

				if( $allowedFileExtensions )
				{	// We want to restrict on file extensions:
					$restrictNotes[] = '<strong>'.T_('Allowed file extensions').'</strong>: '.implode( ', ', $allowedFileExtensions );
				}
				if( $allowedMimeTypes )
				{	// We want to restrict on file MIME types:
					$restrictNotes[] = '<strong>'.T_('Allowed MIME types').'</strong>: '.implode( ', ', $allowedMimeTypes );
				}
				if( $Settings->get( 'upload_maxkb' ) )
				{	// We want to restrict on file size:
					$restrictNotes[] = '<strong>'.T_('Maximum allowed file size').'</strong>: '.bytesreadable( $Settings->get( 'upload_maxkb' )*1024 );
				}

				if( $restrictNotes )
				{
					echo implode( '<br />', $restrictNotes ).'<br />';
				}

				?>
			</p>

			<ul id="uploadfileinputs">
				<?php
					if( empty($failedFiles) )
					{	// No failed failes, display one empty input block:
						$displayFiles[] = NULL;
					}
					else
					{	// Display failed files:
						$displayFiles = & $failedFiles;
					}

					foreach( $displayFiles as $lKey => $lMessage )
					{	// For each file upload block to display:

						if( $lMessage !== NULL )
						{ // This is a failed upload:
							echo '<li class="invalid" title="'
											./* TRANS: will be displayed as title for failed file uploads */ T_('Invalid submission.').'">';
							Log::display( '', '', $lMessage, 'error' );
						}
						else
						{	// Not a failed upload, display normal block:
							echo '<li>';
						}

						?>

						<label><?php echo T_('Choose a file'); ?>:</label>
						<input name="uploadfile[]" size="20" type="file" /><br />

						<label><?php echo T_('Filename on server (optional)'); ?>:</label>
						<input name="uploadfile_name[]" type="text" size="20" maxlength="80"
							value="<?php echo ( isset( $uploadfile_name[$lKey] ) ? format_to_output( $uploadfile_name[$lKey], 'formvalue' ) : '' ) ?>" /><br />

						<label><?php echo T_('Long title'); ?>:</label><br />
						<input name="uploadfile_title[]" type="text" size="50" maxlength="255" class="large"
							value="<?php echo ( isset( $uploadfile_title[$lKey] ) ? format_to_output( $uploadfile_title[$lKey], 'formvalue' ) : '' );
							?>" /><br />

						<label><?php echo T_('Alternative text (useful for images)'); ?>:</label><br />
						<input name="uploadfile_alt[]" type="text" size="50" maxlength="255" class="large"
							value="<?php echo ( isset( $uploadfile_alt[$lKey] ) ? format_to_output( $uploadfile_alt[$lKey], 'formvalue' ) : '' );
							?>" /><br />

						<label><?php echo T_('Caption/Description of the file'); /* TODO: maxlength (DB) */ ?>:</label><br />
						<textarea name="uploadfile_desc[]" rows="3" cols="38" class="large"><?php
							echo ( isset( $uploadfile_desc[$lKey] ) ? $uploadfile_desc[$lKey] : '' )
						?></textarea><br />

						<?php
						echo '</li>';
						// no text after </li> or JS will bite you! (This is where additional blocks get inserted)
					}

				?>
			</ul>

			<p class="uploadfileinputs"><a href="#" onclick="addAnotherFileInput();"><?php echo T_('Add another file'); ?></a></p>

		</fieldset>

		<fieldset class="upload_into">
			<legend><?php echo T_('Upload files into:'); ?></legend>
			<?php
				echo $Fileman->getDirectoryTreeRadio();
			?>
		</fieldset>

		<div class="clear"></div>

<?php

	$Form->end_form( array( array( 'submit', '', T_('Update'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

	// End payload block:
	$AdminUI->dispPayloadEnd();

/*
 * $Log$
 * Revision 1.6  2005/04/19 16:23:01  fplanque
 * cleanup
 * added FileCache
 * improved meta data handling
 *
 * Revision 1.5  2005/04/14 19:57:52  fplanque
 * filemanager refactoring & cleanup
 * started implementation of properties/meta data editor
 * note: the whole fm_mode thing is not really desireable...
 *
 * Revision 1.4  2005/04/14 18:34:03  fplanque
 * filemanager refactoring
 *
 * Revision 1.3  2005/04/13 17:48:21  fplanque
 * File manager refactoring
 * storing of file meta data through upload
 * displaying or metadate in previews
 *
 * Revision 1.2  2005/04/12 19:36:30  fplanque
 * File manager cosmetics
 *
 * Revision 1.1  2005/04/12 19:00:22  fplanque
 * File manager cosmetics
 *
 * This file was extracted from _files.php
 */
?>