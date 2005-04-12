<?php
/**
 * This file implements the UI controller for file upload.
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
		<!-- The following is mainly a hint to the browser. -->
		<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $Settings->get( 'upload_maxkb' )*1024 ?>" />
		<?php
		// we'll use $rootIDAndPath only
		echo $Fileman->getFormHiddenInputs( array( 'root' => false, 'path' => false ) );
		form_hidden( 'rootIDAndPath', serialize( array( 'id' => $Fileman->root, 'path' => $Fileman->getPath() ) ) );

		echo '<h2>'.T_('File upload').'</h2>';

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
				$failedFiles[] = NULL; // display at least one upload div
				foreach( $failedFiles as $lKey => $lMessage )
				{
					?><li<?php
						if( $lMessage !== NULL )
						{
							echo ' class="invalid" title="'./* TRANS: will be displayed as title for failed file uploads */ T_('Invalid submission.').'"';
						} ?>>

						<?php
						if( $lMessage !== NULL )
						{
							Log::display( '', '', $lMessage, 'error' );
						}
						?>

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

			<a href="#" onclick="addAnotherFileInput();"><?php echo T_('Add another file'); ?></a>

		</fieldset>

		<fieldset class="upload_into">
			<legend><?php echo T_('Upload files into:'); ?></legend>
			<?php
				echo $Fileman->getDirectoryTreeRadio();
			?>
		</fieldset>

		<div class="clear"></div>

		<fieldset class="upload_submit">
			<input class="ActionButton" type="submit" value="<?php echo T_('Upload !') ?>" />
		</fieldset>
	</form>

</div>

<div class="clear"></div>

<?php
/*
 * $Log$
 * Revision 1.1  2005/04/12 19:00:22  fplanque
 * File manager cosmetics
 *
 *
 * This file was extracted from _files.php
 */
?>