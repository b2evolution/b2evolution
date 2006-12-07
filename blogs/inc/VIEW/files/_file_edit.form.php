<?php
/**
 * This file implements the file editing form.
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
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var File
 */
global $edit_File;

// Begin payload block:
$this->disp_payload_begin();

$Form = & new Form( NULL, 'file_edit' );

$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url('fm_mode') );

$Form->begin_form( 'fform', T_('Editing:').' '.$edit_File->get_rdfs_rel_path() );
	$Form->hidden_ctrl();
	$Form->hidden( 'action', 'update_file' );
	$Form->hiddens_by_key( get_memorized() );

 	$Form->switch_layout( 'none' );
	echo '<div class="center">';

	$Form->textarea_input( 'file_content', $edit_File->content, 25, '', array( 'class'=>'large', 'cols' => '80' ) );

	$Form->buttons( array( array( 'submit', '', T_('Save!'), 'SaveButton' ),
												array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

	echo '</div>';
 	$Form->switch_layout( NULL );

$Form->end_form();

// End payload block:
$this->disp_payload_end();

/*
 * $Log$
 * Revision 1.1  2006/12/07 20:03:32  fplanque
 * Woohoo! File editing... means all skin editing.
 *
 * Revision 1.5  2006/11/24 18:27:25  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.4  2006/04/19 20:13:51  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
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
 * Revision 1.13  2006/02/11 21:19:29  fplanque
 * added bozo validator to FM
 *
 * Revision 1.12  2005/12/12 19:21:20  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.11  2005/10/31 23:20:45  fplanque
 * keeping things straight...
 *
 * Revision 1.10  2005/10/28 20:08:46  blueyed
 * Normalized AdminUI
 *
 * Revision 1.9  2005/09/06 17:13:53  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.8  2005/08/22 18:42:25  fplanque
 * minor
 *
 * Revision 1.7  2005/07/29 19:43:53  blueyed
 * minor: forceFM is a user setting!; typo in comment.
 *
 * Revision 1.6  2005/05/09 16:09:31  fplanque
 * implemented file manager permissions through Groups
 *
 * Revision 1.5  2005/04/28 20:44:18  fplanque
 * normalizing, doc
 *
 * Revision 1.4  2005/04/27 19:05:43  fplanque
 * normalizing, cleanup, documentaion
 *
 * Revision 1.3  2005/04/15 18:02:58  fplanque
 * finished implementation of properties/meta data editor
 * started implementation of files to items linking
 *
 * Revision 1.2  2005/04/14 19:57:51  fplanque
 * filemanager refactoring & cleanup
 * started implementation of properties/meta data editor
 * note: the whole fm_mode thing is not really desireable...
 *
 * Revision 1.1  2005/04/14 18:34:02  fplanque
 * filemanager refactoring
 *
 */
?>