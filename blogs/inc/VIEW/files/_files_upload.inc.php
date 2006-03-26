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
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Settings
 */
global $Settings;

global $upload_quickmode, $failedFiles;

?>

<script type="text/javascript">
	<!--
	/**
	 * Mighty cool function to append an input or textarea element onto another element.
	 *
	 * @usedby addAnotherFileInput()
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
		{ // We want a BR after the label:
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
	 */
	function addAnotherFileInput()
	{
		var uploadfiles = document.getElementById("uploadfileinputs");
		var newLI = document.createElement("li");
		var closeLink = document.createElement("a");
		var closeImage = document.createElement("img");

		uploadfiles.appendChild( newLI );
		newLI.appendChild( closeLink );
		closeLink.appendChild( closeImage );


		newLI.className = "clear";

		closeImage.src = "<?php echo get_icon( 'close', 'url' ) ?>";
		closeImage.alt = "<?php echo get_icon( 'close', 'alt' ) ?>";

		<?php
		$icon_class = get_icon( 'close', 'class' );
		if( $icon_class )
		{
			?>
			closeImage.className = '<?php echo $icon_class ?>';
			<?php
		}

		if( get_icon( 'close', 'rollover' ) )
		{ // handle rollover images ('close' by default is one).
			?>
			closeLink.className = 'rollover';
			if( typeof setupRollovers == 'function' ) { setupRollovers(); }
			<?php
		}
		?>
		closeImage.setAttribute( 'onclick', "document.getElementById('uploadfileinputs').removeChild(this.parentNode.parentNode);" ); // TODO: setting onclick this way may not work in IE. (try attachEvent then)
		closeLink.style.cssFloat = 'right';

		appendLabelAndInputElements( newLI, '<?php echo TS_('Choose a file'); ?>:', false, 'input', 'uploadfile[]', '20', '0', 'file', '' );
		appendLabelAndInputElements( newLI, '<?php echo TS_('Filename on server (optional)'); ?>:', false, 'input', 'uploadfile_name[]', '20', '80', 'text', '' );
		appendLabelAndInputElements( newLI, '<?php echo TS_('Long title'); ?>:', true, 'input', 'uploadfile_title[]', '50', '255', 'text', 'large' );
		appendLabelAndInputElements( newLI, '<?php echo TS_('Alternative text (useful for images)'); ?>:', true, 'input', 'uploadfile_alt[]', '50', '255', 'text', 'large' );
		appendLabelAndInputElements( newLI, '<?php echo TS_('Caption/Description of the file'); ?>:', true, 'textarea', 'uploadfile_desc[]', '38', '3', '', 'large' );
	}
	// -->
</script>

<?php
	// Begin payload block:
	$this->disp_payload_begin();

	$Form = & new Form( NULL, 'fm_upload_checkchanges', 'post', 'fieldset', 'multipart/form-data' );

	$Form->global_icon( T_('Quit upload mode!'), 'close', regenerate_url( 'fm_mode' ) );

	$Form->begin_form( 'fform', T_('File upload') );

		$Form->hidden_ctrl();
		$Form->hidden( 'MAX_FILE_SIZE', $Settings->get( 'upload_maxkb' )*1024 ); // Just a hint for the browser.
		$Form->hidden( 'upload_quickmode', $upload_quickmode );
		$Form->hiddens_by_key( get_memorized() );

		if( count( $failedFiles ) )
		{
			Log::display( '', '', T_('Some file uploads failed. Please check the errors below.'), 'error' );
		}
		?>

		<?php /* DIV to prevent the "Upload into" fieldset from wrapping below the "Files to upload" box (on any browser), because padding/margin of the fieldset does not affect the width of the both boxes */ ?>
		<div class="box_files_to_upload">
		<fieldset class="files_to_upload">
			<legend><?php echo T_('Files to upload') ?></legend>

			<p>
				<?php
				$restrictNotes = array();

				if( $Settings->get( 'upload_allowedext' ) )
				{ // We want to restrict on file extensions:
					$restrictNotes[] = '<strong>'.T_('Allowed file extensions').'</strong>: '.$Settings->get( 'upload_allowedext' );
				}
				if( $Settings->get( 'upload_maxkb' ) )
				{ // We want to restrict on file size:
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
					{ // No failed failes, display one empty input block:
						$displayFiles[] = NULL;
					}
					else
					{ // Display failed files:
						$displayFiles = & $failedFiles;
					}

					foreach( $displayFiles as $lKey => $lMessage )
					{ // For each file upload block to display:

						if( $lMessage !== NULL )
						{ // This is a failed upload:
							echo '<li class="invalid" title="'
											./* TRANS: will be displayed as title for failed file uploads */ T_('Invalid submission.').'">';
							Log::display( '', '', $lMessage, 'error' );
						}
						else
						{ // Not a failed upload, display normal block:
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

			<p class="uploadfileinputs"><a href="#" onclick="addAnotherFileInput(); return false;"><?php echo T_('Add another file'); ?></a></p>
		</fieldset>
		</div>

		<div class="box_upload_into">
		<fieldset class="upload_into">
			<legend><?php echo T_('Upload files into:'); ?></legend>
			<?php
				echo get_directory_tree( NULL, NULL, array('disp_radios'=>true) );
			?>
		</fieldset>
		</div>

		<div class="clear"></div>

<?php

	$Form->end_form( array( array( 'submit', '', T_('Upload'), 'ActionButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

	// End payload block:
	$this->disp_payload_end();

/*
 * $Log$
 * Revision 1.4  2006/03/26 02:37:57  blueyed
 * Directory tree next to files list.
 *
 * Revision 1.3  2006/03/12 23:09:01  fplanque
 * doc cleanup
 *
 * Revision 1.2  2006/03/12 03:03:33  blueyed
 * Fixed and cleaned up "filemanager".
 *
 * Revision 1.1  2006/02/23 21:12:17  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.21  2006/02/11 21:19:29  fplanque
 * added bozo validator to FM
 *
 * Revision 1.20  2006/01/20 00:39:17  blueyed
 * Refactorisation/enhancements to filemanager.
 *
 * Revision 1.19  2005/12/12 19:21:20  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.18  2005/12/10 03:02:50  blueyed
 * Quick upload mode merged from post-phoenix
 *
 * Revision 1.17  2005/12/05 21:34:29  blueyed
 * doc
 *
 * Revision 1.15  2005/11/27 06:17:52  blueyed
 * Layout fixes to not cause the "Upload into" fieldset wrap below the "Files to upload" box.
 *
 * Revision 1.12  2005/10/28 20:08:46  blueyed
 * Normalized AdminUI
 *
 * Revision 1.11  2005/09/06 17:13:53  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.10  2005/06/22 17:44:52  blueyed
 * Fix onclick for "Add another file input"
 *
 * Revision 1.9  2005/05/17 19:26:06  fplanque
 * FM: copy / move debugging
 *
 * Revision 1.8  2005/05/11 15:58:30  fplanque
 * cleanup
 *
 * Revision 1.7  2005/05/06 20:04:47  fplanque
 * added contribs
 * fixed filemanager settings
 *
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