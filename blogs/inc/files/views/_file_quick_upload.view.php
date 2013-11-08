<?php
/**
 * This file implements the UI for quick file upload.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
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
 * EVO FACTORY grants Francois PLANQUE the right to license
 * EVO FACTORY contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-asimo: Evo Factory / Attila Simo
 *
 * @version $Id$
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Settings
 */
global $Settings;

global $htsrv_url;

global $ads_list_path, $fm_FileRoot, $tab3;

global $Hit, $Messages;

$this->disp_payload_begin();

$Form = new Form( NULL, 'fm_upload_checkchanges', 'post' );
$Form->begin_form( 'fform' );
$Form->add_crumb( 'file' );
$Form->hidden_ctrl();
$Form->hidden( 'tab3_onsubmit', $tab3 );

$Widget = new Widget( 'file_browser' );
$Widget->global_icon( T_('Quit upload mode!'), 'close', regenerate_url( 'ctrl,fm_mode', 'ctrl=files' ) );
$Widget->title = T_('File upload').get_manual_link('upload_multiple');
$Widget->disp_template_replaced( 'block_start' );

echo '<table id="fm_browser" cellspacing="0" cellpadding="0">';
echo '<tbody>';
	echo '<tr>';

	// Display directory tree
	echo '<td id="fm_dirtree">';
	// Version with all roots displayed
	echo get_directory_tree( NULL, NULL, $ads_list_path, true, NULL, false, 'add' );
	echo '</td>';

	// Display quick upload
	echo '<td id="fm_files">';

	?>
	<div id="file-uploader" style="width: 100%;">
		<noscript>
			<p>Please enable JavaScript to use file uploader.</p>
		</noscript>
	</div>
	<?php
	echo '<input id="saveBtn" type="submit" style="display: none;" name="saveBtn" value="'.T_('Save modified files'),'" class="ActionButton" />';

	$root_and_path = $fm_FileRoot->ID.'::';
	$quick_upload_url = $htsrv_url.'quick_upload.php?upload=true';

	?>
	<script type="text/javascript">
		if( 'draggable' in document.createElement('span') )
		{
			var button_text = '<?php echo TS_('Drag & Drop files to upload here <br /><span>or click to manually select files...</span>') ?>';
			var note_text = '<?php echo TS_('Your browser supports full upload functionality.') ?>';
		}
		else
		{
			var button_text = '<?php echo TS_('Click to manually select files...') ?>';
			var note_text = '<?php echo TS_('Your browser does not support full upload functionality: You can only upload files one by one and you cannot use Drag & Drop.') ?>';
		}

		var url = <?php echo '"'.$quick_upload_url.'&'.url_crumb( 'file' ).'"'; ?>;
		var root_and_path = '<?php echo $root_and_path ?>';

		jQuery( '#fm_dirtree input[type=radio]' ).click( function()
		{
			url = "<?php echo $quick_upload_url; ?>"+"&root_and_path="+this.value+"&"+"<?php echo url_crumb( 'file' ); ?>";
			root_and_path = this.value;
			uploader.setParams({root_and_path: root_and_path});
		} );

		jQuery(document).ready( function()
		{
			uploader = new qq.FileUploader(
			{
				element: document.getElementById('file-uploader'),
				action: url,
				debug: true,
				//sizeLimit: maxsize,
				onComplete: function(id, fileName, responseJSON)
				{
					var container = jQuery(uploader._getItemByFileId(id));

					var text =  base64_decode(responseJSON.success.text);
					if (responseJSON.success.specialchars == 1)
					{
						text = htmlspecialchars_decode(text);
					}

					if (responseJSON.success.status != undefined && responseJSON.success.status == 'rename')
					{
						jQuery('#saveBtn').show();
					}

					if( responseJSON.success.warning != undefined && responseJSON.success.warning != '' )
					{
						text += '<div class="orange">' + responseJSON.success.warning + '</div>';
					}
					container.append(text);
				},
				onCancel: function(id, fileName){},
				messages: {
					typeError: "{file} has invalid extension. Only {extensions} are allowed.",
					sizeError: "{file} is too large, maximum file size is {sizeLimit}.",
					minSizeError: "{file} is too small, minimum file size is {minSizeLimit}.",
					emptyError: "{file} is empty, please select files again without it.",
					onLeave: "The files are being uploaded, if you leave now the upload will be cancelled."
				},
				showMessage: function(message)
				{
					jQuery('.qq-upload-list').append('<li class=" qq-upload-success"><span class="qq-upload-file"></span><span class="qq-upload-size" style="display: inline;"></span><span class="qq-upload-failed-text">Failed</span><span class="result_error">'+message+'</span></li>')
				},
				template: '<div class="qq-uploader">' +
					'<div class="qq-upload-drop-area"><span>Drop files here to upload</span></div>' +
					'<div class="qq-upload-button">'+ button_text +'</div>' +
					'<ul class="qq-upload-list"></ul>' +
				'</div>',
				params: { root_and_path: jQuery( '#fm_dirtree input[type=radio]:checked' ).val() }
			});
		});

		document.write( '<p class="note">' + note_text + '</p>' );
	</script>
	<?php
	echo '</td>';
	echo '</tr>';
echo '</tbody>';
echo '</table>';

$Widget->disp_template_raw( 'block_end' );

$Form->end_form();

// End payload block:
$this->disp_payload_end();

?>