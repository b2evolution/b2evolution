<?php
/**
 * This file implements the UI for file upload.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Settings
 */
global $Settings;

global $failedFiles, $ads_list_path, $renamedMessages, $renamedFiles;

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
	                                      inputSizeOrCols, inputMaxLengthOrRows, inputType, inputClass, fieldClass )
	{
		var id = inputName.replace(/\[(\d+)\]/, '_$1');

		// Field wrapper:
		var div = document.createElement( 'div' );
		div.className = 'form-group' + ( typeof( fieldClass ) == 'undefined' ? '' : ' ' + fieldClass );

		// LABEL:
		var label = document.createElement( 'label' );
		label.innerHTML = labelText;

		// INPUT:
		var fileInput = document.createElement( inputOrTextarea == 'textarea' ? 'textarea' : 'input' );
		fileInput.name = inputName;
		fileInput.id = id;
		if( inputOrTextarea == 'input' || inputOrTextarea == 'radio-input' )
		{
			fileInput.type = typeof( inputType ) !== 'undefined' ? inputType : 'text';
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

		if( inputOrTextarea == 'radio-input' )
		{
			label.appendChild( fileInput );
			div.appendChild( label );
		}
		else
		{
			div.appendChild( label );
			// Dow we want a BR after the label:
			div.appendChild( labelBr ? document.createElement('br') : document.createTextNode( ' ' ) );
			div.appendChild( fileInput );
		}
		appendTo.appendChild( div );
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
		closeLink.innerHTML = '<?php echo format_to_js( get_icon( 'close' ) ); ?>';
		closeLink.className = 'btn btn-default pull-right';
		var closeImage = jQuery( closeLink ).children( 'span' );

		uploadfiles.appendChild( newLI );
		newLI.appendChild( closeLink );
		newLI.className = 'clear';

		<?php
		if( get_icon( 'close', 'rollover' ) )
		{ // handle rollover images ('close' by default is one).
			?>
			closeLink.className = 'rollover_sprite'; // dh> use "+=" to append class?
			<?php
		}
		// add handler to image to close the parent LI and add css to float right.
		?>
		jQuery( closeImage ).click( function() { jQuery( this ).closest( 'li' ).remove() } );

		evo_upload_fields_count++;

		var radioFile = '<input type="radio" name="uploadfile_source[' + evo_upload_fields_count + ']" value="file" checked="checked" />';
		appendLabelAndInputElements( newLI, radioFile + ' <?php echo TS_('Choose a file'); ?>: ', false, 'radio-input', 'uploadfile['+evo_upload_fields_count+']', '70', '0', 'file', 'upload_file', 'radio' );
		var radioURL = '<input type="radio" name="uploadfile_source[' + evo_upload_fields_count + ']" value="upload" checked="checked" />';
		appendLabelAndInputElements( newLI, radioURL + ' <?php echo TS_('Upload by URL'); ?>: ', false, 'radio-input', 'uploadfile_url['+evo_upload_fields_count+']', '70', '0', 'text', 'form-control upload_file', 'radio' );
		appendLabelAndInputElements( newLI, '<?php echo TS_('Filename on server (optional)'); ?>:', false, 'input', 'uploadfile_name[]', '50', '80', 'text', 'form-control' );
		appendLabelAndInputElements( newLI, '<?php echo TS_('Long title'); ?>:', true, 'input', 'uploadfile_title[]', '50', '255', 'text', 'form-control large', 'large' );
		appendLabelAndInputElements( newLI, '<?php echo TS_('Alternative text (useful for images)'); ?>:', true, 'input', 'uploadfile_alt[]', '50', '255', 'text', 'form-control large', 'large' );
		appendLabelAndInputElements( newLI, '<?php echo TS_('Caption/Description of the file'); ?>:', true, 'textarea', 'uploadfile_desc[]', '38', '3', '', 'form-control large', 'large' );
	}
	// -->
</script>


<?php
	// Begin payload block:
	$this->disp_payload_begin();

	$Form = new Form( NULL, 'fm_upload_checkchanges', 'post', 'none', 'multipart/form-data' );
	$Form->formclass = 'form-inline';
	$Form->begin_form( 'fform' );
	$Form->add_crumb( 'file' );
	$Form->hidden_ctrl();
	$Form->hidden( 'MAX_FILE_SIZE', $Settings->get( 'upload_maxkb' )*1024 ); // Just a hint for the browser.
	$Form->hiddens_by_key( get_memorized() );

	$Widget = new Widget( 'file_browser' );
	$Widget->global_icon( T_('Quit upload mode!'), 'close', regenerate_url( 'ctrl,fm_mode', 'ctrl=files' ) );
	$Widget->title = T_('File upload').get_manual_link('upload_multiple');
	$Widget->disp_template_replaced( 'block_start' );
?>

<table id="fm_browser" cellspacing="0" cellpadding="0" class="table table-bordered table-condensed">
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

							<div class="form-group radio">
								<label>
									<input type="radio" name="uploadfile_source[<?php echo $lKey ?>]" value="file"
										<?php echo ! isset($uploadfile_source[$lKey]) || $uploadfile_source[$lKey] == 'file' ? ' checked="checked"' : '' ?> />
									<?php echo T_('Choose a file'); ?>:
									<input name="uploadfile[]" id="uploadfile_<?php echo $lKey ?>" size="70" type="file" class="upload_file" />
								</label>
							</div><br />

							<div class="form-group radio">
								<label>
									<input type="radio" name="uploadfile_source[<?php echo $lKey ?>]" value="upload"
										<?php echo isset($uploadfile_source[$lKey]) && $uploadfile_source[$lKey] == 'upload' ? ' checked="checked"' : '' ?> />
									<?php echo T_('Get from URL'); ?>:
									<input name="uploadfile_url[]" id="uploadfile_url_<?php echo $lKey ?>" size="70" type="text" class="upload_file form-control"
										value="<?php echo ( isset( $uploadfile_url[$lKey] ) ? format_to_output( $uploadfile_url[$lKey], 'formvalue' ) : '' ); ?>" />
								</label>
							</div><br />

							<?php
						}
						else
						{
							?>
							<div class="form-group radio">
								<label>
									<input type="radio" name="<?php echo 'Renamed_'.$lKey ?>" value="Yes" id=" <?php echo 'Yes_'.$lKey ?>"/>
									<?php echo sprintf( T_('Replace the old version %s with the new version %s and keep old version as %s.'), $renamedMessages[$lKey]['oldThumb'], $renamedMessages[$lKey]['newThumb'], $renamedFiles[$lKey]['newName'] ) ?>
								</label><br />
								<label>
									<input type="radio" name="<?php echo 'Renamed_'.$lKey ?>" value="No" id=" <?php echo 'No_'.$lKey ?>"  checked="checked"/>
									<?php echo sprintf( T_('Don\'t touch the old version and keep the new version as %s.'), $renamedFiles[$lKey]['newName'] ) ?>
								</label>
							</div><br />
							<?php
						}
						// We want file properties on the upload form:
						?>
						<div class="form-group">
							<label><?php echo T_('Filename on server (optional)'); ?>:</label>
							<input name="uploadfile_name[]" type="text" size="50" maxlength="80" class="form-control"
								value="<?php echo ( isset( $uploadfile_name[$lKey] ) ? format_to_output( $uploadfile_name[$lKey], 'formvalue' ) : '' ); ?>" />
						</div><br />

						<div class="form-group large">
							<label><?php echo T_('Long title'); ?>:</label><br />
							<input name="uploadfile_title[]" type="text" size="50" maxlength="255" class="form-control large"
								value="<?php echo ( isset( $uploadfile_title[$lKey] ) ? format_to_output( $uploadfile_title[$lKey], 'formvalue' ) : '' ); ?>" />
						</div><br />

						<div class="form-group large">
							<label><?php echo T_('Alternative text (useful for images)'); ?>:</label><br />
							<input name="uploadfile_alt[]" type="text" size="50" maxlength="255" class="form-control large"
								value="<?php echo ( isset( $uploadfile_alt[$lKey] ) ? format_to_output( $uploadfile_alt[$lKey], 'formvalue' ) : '' ); ?>" />
						</div><br />

						<div class="form-group large">
							<label><?php echo T_('Caption/Description of the file'); /* TODO: maxlength (DB) */ ?>:</label><br />
							<textarea name="uploadfile_desc[]" rows="3" cols="38" class="form-control large form_textarea_input"><?php
								echo ( isset( $uploadfile_desc[$lKey] ) ? $uploadfile_desc[$lKey] : '' ); ?></textarea>
						</div><br />
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
				<input type="submit" value="<?php echo format_to_output( T_('Upload to server now'), 'formvalue' ); ?>" class="ActionButton btn btn-primary" >

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