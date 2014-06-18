<?php
/**
 * This file implements the UI for file upload.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
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
 * @version $Id: _file_upload.view.php 6411 2014-04-07 15:17:33Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Settings
 */
global $Settings;

global $upload_quickmode, $failedFiles, $ads_list_path, $renamedMessages, $renamedFiles;

global $fm_FileRoot;


/* TODO: dh> move JS to external file. */
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
		var id = inputName.replace(/\[(\d+)\]/, '_$1');
		// LABEL:
		var fileLabel = jQuery(appendTo).append(jQuery('<label for="'+id+'">'+labelText+'</label>'));

		// Dow we want a BR after the label:
		if( labelBr )
		{ // We want a BR after the label:
			appendTo.appendChild( document.createElement('br') );
		}
		else
		{
			appendTo.appendChild( document.createTextNode( ' ' ) );
		}

		// INPUT:
		var fileInput = document.createElement( inputOrTextarea );
		fileInput.name = inputName;
		fileInput.id = id;
		if( inputOrTextarea == "input" )
		{
			fileInput.type = typeof( inputType ) !== 'undefined' ? inputType : "text";
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
		closeLink.innerHTML = '<?php echo get_icon( 'close' ); ?>';
		var closeImage = jQuery( closeLink ).children( 'span' );

		uploadfiles.appendChild( newLI );
		newLI.appendChild( closeLink );
		newLI.className = "clear";

		<?php
		if( get_icon( 'close', 'rollover' ) )
		{ // handle rollover images ('close' by default is one).
			?>
			closeLink.className = 'rollover_sprite'; // dh> use "+=" to append class?
			<?php
		}
		// add handler to image to close the parent LI and add css to float right.
		?>
		jQuery(closeImage)
			.click( function() {jQuery(this).closest("li").remove()} )
			.css('float', 'right');

		evo_upload_fields_count++;
		// first radio
		var radioFile = document.createElement('input');
		radioFile.type = "radio";
		radioFile.name = "uploadfile_source["+ evo_upload_fields_count +"]";
		radioFile.value = "file";

		// second radio
		var radioURL = radioFile.cloneNode(true);
		radioURL.value = "upload";

		radioFile.checked = true;

		newLI.appendChild( radioFile );
		appendLabelAndInputElements( newLI, '<?php echo TS_('Choose a file'); ?>:', false, 'input', 'uploadfile['+evo_upload_fields_count+']', '70', '0', 'file', 'upload_file' );
		newLI.appendChild( radioURL );
		appendLabelAndInputElements( newLI, '<?php echo TS_('Upload by URL'); ?>:', false, 'input', 'uploadfile_url['+evo_upload_fields_count+']', '70', '0', 'text', 'upload_file' );
		appendLabelAndInputElements( newLI, '<?php echo TS_('Filename on server (optional)'); ?>:', false, 'input', 'uploadfile_name[]', '50', '80', 'text', '' );
		appendLabelAndInputElements( newLI, '<?php echo TS_('Long title'); ?>:', true, 'input', 'uploadfile_title[]', '50', '255', 'text', 'large' );
		appendLabelAndInputElements( newLI, '<?php echo TS_('Alternative text (useful for images)'); ?>:', true, 'input', 'uploadfile_alt[]', '50', '255', 'text', 'large' );
		appendLabelAndInputElements( newLI, '<?php echo TS_('Caption/Description of the file'); ?>:', true, 'textarea', 'uploadfile_desc[]', '38', '3', '', 'large' );
	}
	// -->
</script>


<?php
	// Begin payload block:
	$this->disp_payload_begin();

	$Form = new Form( NULL, 'fm_upload_checkchanges', 'post', 'none', 'multipart/form-data' );
	$Form->begin_form( 'fform' );
	$Form->add_crumb( 'file' );
	$Form->hidden_ctrl();
	$Form->hidden( 'MAX_FILE_SIZE', $Settings->get( 'upload_maxkb' )*1024 ); // Just a hint for the browser.
	$Form->hidden( 'upload_quickmode', $upload_quickmode );
	$Form->hiddens_by_key( get_memorized() );

	$Widget = new Widget( 'file_browser' );
	$Widget->global_icon( T_('Quit upload mode!'), 'close', regenerate_url( 'ctrl,fm_mode', 'ctrl=files' ) );
	$Widget->title = T_('File upload').get_manual_link('upload_multiple');
	$Widget->disp_template_replaced( 'block_start' );
?>

<table id="fm_browser" cellspacing="0" cellpadding="0" class="table table-striped table-bordered table-hover table-condensed">
	<tbody>
		<tr>
			<?php
			echo '<td id="fm_dirtree">';

			// Version with all roots displayed
			echo get_directory_tree( NULL, NULL, $ads_list_path, true, NULL, false, 'add' );

			// Version with only the current root displayed:
			// echo get_directory_tree( $fm_FileRoot, $fm_FileRoot->ads_path, $ads_list_path, true );

			echo '</td>';

			echo '<td id="fm_files">';


		if( count( $failedFiles ) )
		{
			echo '<p class="error">'.T_('Some file uploads failed. Please check the errors below.').'</p>';
		}
		if( count( $renamedMessages) )
		{
			echo '<p class="error">'.T_('Some uploaded files have been renamed due to conflicting file names. Please check the messages below:').'</p>';
		}
		?>

			<div class="upload_title"><?php echo T_('Files to upload') ?></div>

			<ul id="uploadfileinputs">
				<?php
					if( empty($failedFiles) && empty($renamedMessages) )
					{ // No failed files, no renamed files display 5 empty input blocks:
						$displayFiles = array( NULL, NULL, NULL, NULL, NULL );
					}
					elseif ( ! empty($failedFiles) )
					{ // Display failed files:
						$displayFiles = & $failedFiles;
						if( ! empty($renamedMessages) )
						{ // There are renamed files
							foreach( $renamedMessages as $lKey => $data )
							{
								if( $displayFiles[$lKey] == null )
								{
									$displayFiles[$lKey] = $data['message'];
								}
							}
						}
					}
					else
					{ // Display renamed files:
						foreach( $renamedMessages as $lKey => $data )
						{
							$displayFiles[$lKey] = $data['message'];
						}
					}

					global $uploadfile_alt, $uploadfile_desc, $uploadfile_name, $uploadfile_title;
					global $uploadfile_url, $uploadfile_source;
					foreach( $displayFiles as $lKey => $lMessage )
					{ // For each file upload block to display:

						if( ($lMessage !== NULL) )
						{
							if( ! array_key_exists( $lKey, $renamedMessages ) )
							{ // This is a failed upload:
								echo '<li class="invalid" title="'
												./* TRANS: will be displayed as title for failed file uploads */ T_('Invalid submission.').'">';
							}
							else
							{ // This filename arlready exists:
								echo '<li class="invalid" title="'
												./* TRANS: will be displayed as title in case of renamed file uploads */ T_('File name changed.').'">';
							}
							echo '<p class="error">'.$lMessage.'</p>';
						}
						else
						{ // Not a failed upload, display normal block:
							echo '<li>';
						}

						// fp> TODO: would be cool to add a close icon starting at the 2nd <li>
						// dh> TODO: it may be useful to add the "accept" attrib to the INPUT elements to give the browser a hint about the accepted MIME types

						if( ! array_key_exists( $lKey, $renamedMessages ) )
						{
							?>

							<input type="radio" name="uploadfile_source[<?php echo $lKey ?>]" value="file"
								<?php echo ! isset($uploadfile_source[$lKey]) || $uploadfile_source[$lKey] == 'file' ? ' checked="checked"' : '' ?> />
							<label for="uploadfile_<?php echo $lKey ?>"><?php echo T_('Choose a file'); ?>:</label>
							<input name="uploadfile[]" id="uploadfile_<?php echo $lKey ?>" size="70" type="file" class="upload_file" /><br />

							<input type="radio" name="uploadfile_source[<?php echo $lKey ?>]" value="upload"
								<?php echo isset($uploadfile_source[$lKey]) && $uploadfile_source[$lKey] == 'upload' ? ' checked="checked"' : '' ?> />
							<label for="uploadfile_url_<?php echo $lKey ?>"><?php echo T_('Get from URL'); ?>:</label>
							<input name="uploadfile_url[]" id="uploadfile_url_<?php echo $lKey ?>" size="70" type="text" class="upload_file"
									value="<?php echo ( isset( $uploadfile_url[$lKey] ) ? format_to_output( $uploadfile_url[$lKey], 'formvalue' ) : '' );
									?>" /><br />

							<?php
						}
						else
						{
							?>
							<input type="radio" name="<?php echo 'Renamed_'.$lKey ?>" value="Yes" id=" <?php echo 'Yes_'.$lKey ?>"/>
							<label for="<?php echo 'Yes_'.$lKey ?>">
							<?php echo sprintf( T_("Replace the old version %s with the new version %s and keep old version as %s."), $renamedMessages[$lKey]['oldThumb'], $renamedMessages[$lKey]['newThumb'], $renamedFiles[$lKey]['newName'] ) ?></label><br />
							<input type="radio" name="<?php echo 'Renamed_'.$lKey ?>" value="No" id=" <?php echo 'No_'.$lKey ?>"  checked="checked"/>
							<label for="<?php echo 'Yes_'.$lKey ?>"><?php echo sprintf( T_("Don't touch the old version and keep the new version as %s."), $renamedFiles[$lKey]['newName'] ) ?> </label><br />
							<?php
						}
						// We want file properties on the upload form:
						?>
						<label><?php echo T_('Filename on server (optional)'); ?>:</label>
						<input name="uploadfile_name[]" type="text" size="50" maxlength="80"
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
						<textarea name="uploadfile_desc[]" rows="3" cols="38" class="form_textarea_input"><?php
							echo ( isset( $uploadfile_desc[$lKey] ) ? $uploadfile_desc[$lKey] : '' )
						?></textarea><br />
						<?php
						echo '</li>';
						// no text after </li> or JS will bite you! (This is where additional blocks get inserted)
					}

				?>
			</ul>

			<script type="text/javascript">
				(function()
				{
					var handler = function()
					{
						jQuery(this).prevAll("[type=radio]").eq(0).attr("checked", "checked")
					}
					jQuery( document ).on("click", ".upload_file", handler);
					jQuery( document ).on("keyup", ".upload_file", handler);
					jQuery( document ).on("change", ".upload_file", handler);
				})();
				var evo_upload_fields_count = jQuery("#uploadfileinputs li").length-1;
			</script>

			<p class="uploadfileinputs"><a href="#" onclick="addAnotherFileInput(); return false;" class="small"><?php echo T_('Add another file'); ?></a></p>

			<div class="upload_foot">
				<input type="submit" value="<?php echo format_to_output( T_('Upload to server now'), 'formvalue' ); ?>" class="ActionButton btn btn-default" >

				<p class="note">
					<?php
					echo get_upload_restriction();
					?>
				</p>
			</div>

		</td>
		</tr>
	</tbody>
</table>


<?php
$Widget->disp_template_raw( 'block_end' );

$Form->end_form();

// End payload block:
$this->disp_payload_end();

?>