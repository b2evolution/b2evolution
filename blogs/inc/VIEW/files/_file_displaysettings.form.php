<?php
/**
 * This file implements the UI for file display settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var UserSettings
 */
global $UserSettings;

$Form = & new Form( NULL, 'file_displaysettings_checkchanges' );

$Form->global_icon( T_('Quit settings mode!'), 'close', regenerate_url('fm_mode') );

$Form->begin_form( 'fform', T_('Display settings') );

	$Form->hidden_ctrl();
	$Form->hiddens_by_key( get_memorized() );

	$Form->begin_fieldset( T_('Images') );
		$Form->checkbox( 'option_imglistpreview', $UserSettings->get('fm_imglistpreview'), T_('Thumbnails'), T_('Check to display thumbnails instead of icons for image files') );
		$Form->checkbox( 'option_getimagesizes', $UserSettings->get('fm_getimagesizes'), T_('Dimensions'), T_('Check to display the pixel dimensions of image files') );
	$Form->end_fieldset();

	$Form->begin_fieldset( T_('Columns') );
		$Form->checkbox( 'option_showtypes', $UserSettings->get('fm_showtypes'), T_('File type'), T_('Based on file extension') );
		$Form->checkbox( 'option_showfsperms', $UserSettings->get('fm_showfsperms'), T_('File permissions'), T_('Unix file permissions') );
		$Form->checkbox( 'option_permlikelsl', $UserSettings->get('fm_permlikelsl'), '', T_('Check to display file permissions like "rwxr-xr-x" rather than short form') );
		$Form->checkbox( 'option_showfsowner', $UserSettings->get('fm_showfsowner'), T_('File Owner'), T_('Unix file owner') );
		$Form->checkbox( 'option_showfsgroup', $UserSettings->get('fm_showfsgroup'), T_('File Group'), T_('Unix file group') );
	$Form->end_fieldset();

	$Form->begin_fieldset( T_('Options') );
		$Form->checkbox( 'option_showhidden', $UserSettings->get('fm_showhidden'), T_('Hidden files'), T_('Check to show hidden files') );
		$Form->checkbox( 'option_dirsattop', !$UserSettings->get('fm_dirsnotattop'), T_('Folders first'), T_('Check to always display folders before files') );
		$Form->checkbox( 'option_recursivedirsize', $UserSettings->get('fm_recursivedirsize'), T_('Folder sizes'), T_('Check to compute recursive size of folders') );
	$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'actionArray[update_settings]', T_('Update !'), 'ActionButton'),
												array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

/*
 * $Log$
 * Revision 1.1  2007/01/24 05:57:55  fplanque
 * cleanup / settings
 *
 */
?>