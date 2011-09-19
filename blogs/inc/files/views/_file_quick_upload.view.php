<?php
/**
 * This file implements the UI for quick file upload.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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

global $ads_list_path, $fm_FileRoot;

$this->disp_payload_begin();

$Form = new Form( NULL, 'fm_upload_checkchanges', 'post' );
$Form->begin_form( 'fform' );
$Form->add_crumb( 'file' );
$Form->hidden_ctrl();
$Form->hidden( 'tab3', 'quick' );

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
	echo '<div id="upload_queue"></div>';
	echo '<input id="quickupload" type="file" multiple="multiple" />';
	echo '<input id="saveBtn" type="hidden" value="'.T_('Save modified files'),'" class="ActionButton" />';

	$root_and_path = $fm_FileRoot->ID.'::';
	$quick_upload_url = $htsrv_url.'quick_upload.php?upload=true';
	?>
	<script type="text/javascript">

		var url = <?php echo '"'.$quick_upload_url.'&root_and_path='.$root_and_path.'&'.url_crumb( 'file' ).'"'; ?>;
		var uploading_text = <?php echo '"'.T_( 'Uploading' ).'"'; ?>;
		var incompatible_browser = <?php echo '"'.T_( 'Your browser does not support XMLHttpRequest technology! Please use the standard upload instead.' ).'"'; ?>;
		var maxsize = <?php echo $Settings->get( 'upload_maxkb' )*1024; ?>;
		var size_error = <?php echo '"<span class=\"result_error\">'.T_('The file is too large: %1 but the maximum allowed is %2.').'</span>"'; ?>;
		var ok_text =  <?php echo '"'.T_( 'OK' ).'"'; ?>;

		jQuery( '#fm_dirtree input[type=radio]' ).click( function()
		{
			url = "<?php echo $quick_upload_url; ?>"+"&root_and_path="+this.value+"&"+"<?php echo url_crumb( 'file' ); ?>";
		} );
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

/*
 * $Log$
 * Revision 1.5  2011/09/19 22:16:00  fplanque
 * Minot/i18n
 *
 * Revision 1.4  2011/09/06 20:23:54  sam2kb
 * MFB
 *
 * Revision 1.3  2011/09/04 22:13:16  fplanque
 * copyright 2011
 *
 * Revision 1.2  2011/05/06 07:04:46  efy-asimo
 * multiupload ui update
 *
 * Revision 1.1  2011/04/28 14:07:58  efy-asimo
 * multiple file upload
 *
 */
?>
	