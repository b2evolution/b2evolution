<?php
/**
 * This file implements the UI for file download.
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
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id: _file_download.form.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $zipname, $exclude_sd, $selected_Filelist;

$Form = new Form( NULL, 'fm_download_checkchanges' );

$Form->global_icon( T_('Cancel download!'), 'close', regenerate_url() );

$Form->begin_form( 'fform', T_('Download files in archive') );
	$Form->hidden_ctrl();
	$Form->hidden( 'action', 'download' );
	$Form->hidden( 'action_invoked', 1 );
	$Form->hiddens_by_key( get_memorized() );

	$Form->text_input( 'zipname', $zipname, 30, T_('Archive filename'), T_('This is the name of the file which will get sent to you.'),  array( 'maxlength' => '' ) );

	if( $selected_Filelist->count_dirs() )
	{ // Allow to exclude dirs:
		$Form->checkbox( 'exclude_sd', $exclude_sd, T_('Exclude subdirectories'), T_('This will exclude subdirectories of selected directories.') );
	}

	$Form->begin_fieldset( T_('Files to download') );
	echo '<ul>'
		.'<li>'.implode( "</li>\n<li>", $selected_Filelist->get_array( 'get_prefixed_name' ) )."</li>\n"
		.'</ul>';

	$Form->end_fieldset();

$Form->end_form( array(
		array( 'submit', 'submit', T_('Download'), 'DeleteButton' ) ) );

?>