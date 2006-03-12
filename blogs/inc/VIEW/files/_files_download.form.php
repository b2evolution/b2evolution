<?php
/**
 * This file implements the UI for file download.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link https://thequod.de/}.
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
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $zipname, $exclude_sd, $selected_Filelist;

$Form = & new Form( NULL, 'fm_download_checkchanges' );

$Form->global_icon( T_('Cancel download!'), 'close', regenerate_url() );

$Form->begin_form( 'fform', T_('Download files in archive') );
	$Form->hidden_ctrl();
	$Form->hidden( 'action', 'download' );
	$Form->hidden( 'action_invoked', 1 );
	$Form->hiddens_by_key( get_memorized() );

	$Form->text_input( 'zipname', $zipname, 30, T_('Archive filename'), array( 'note' => T_('This is the name of the file which will get sent to you.'), 'maxlength' => '' ) );

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


/* {{{ Revision log:
 * $Log$
 * Revision 1.4  2006/03/12 23:09:01  fplanque
 * doc cleanup
 *
 * Revision 1.3  2006/03/12 03:03:33  blueyed
 * Fixed and cleaned up "filemanager".
 *
 * Revision 1.2  2006/02/28 18:21:38  blueyed
 * maxlength fixed
 *
 * Revision 1.1  2006/02/23 21:12:17  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.2  2006/02/11 21:19:29  fplanque
 * added bozo validator to FM
 *
 * Revision 1.1  2006/01/24 23:42:17  blueyed
 * Added download form.
 *
 * }}}
 */
?>