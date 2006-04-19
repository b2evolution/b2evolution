<?php
/**
 * This file implements the UI for file rename
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
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
 * @global Filelist
 */
global $selected_Filelist;

/**
 * @global string
 */
global $new_names;


$Form = & new Form( NULL, 'fm_rename_checkchanges' );

$Form->global_icon( T_('Cancel rename!'), 'close', regenerate_url() );

$Form->begin_form( 'fform', T_('Rename') );

	$Form->hidden_ctrl();
	$Form->hiddens_by_key( get_memorized() );
	$Form->hidden( 'action', 'rename' );
	$Form->hidden( 'confirmed', 1 );

	$selected_Filelist->restart();
	while( $loop_src_File = & $selected_Filelist->get_next() )
	{
		$Form->begin_fieldset( T_('File').': '.$loop_src_File->get_name() );

		$Form->text( 'new_names['.$loop_src_File->get_md5_ID().']', $new_names[$loop_src_File->get_md5_ID()], 32,
									T_('New name'), $loop_src_File->dget('title'), 128 );

		$Form->end_fieldset();
	}


$Form->end_form( array( array( 'submit', 'submit', T_('Rename'), 'SaveButton' ),
												array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

/*
 * $Log$
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
 * Revision 1.8  2006/02/11 21:19:29  fplanque
 * added bozo validator to FM
 *
 * Revision 1.7  2006/01/20 00:39:17  blueyed
 * Refactorisation/enhancements to filemanager.
 *
 * Revision 1.6  2005/12/12 19:21:20  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.5  2005/11/21 18:33:19  fplanque
 * Too many undiscussed changes all around: Massive rollback! :((
 * As said before, I am only taking CLEARLY labelled bugfixes.
 *
 * Revision 1.3  2005/09/06 17:13:53  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.2  2005/08/22 18:42:25  fplanque
 * minor
 *
 * Revision 1.1  2005/05/17 19:26:06  fplanque
 * FM: copy / move debugging
 *
 */
?>