<?php
/**
 * This file implements the UI for file copy / move
 *
 * fplanque>> This whole thing is flawed:
 * 1) only geeks can possibly like to use the same interface for renaming, moving and copying
 * 2) even the geeky unix commands won't pretend copying and moving are the same thing. They are not!
 *    Only moving and renaming are similar, and again FOR GEEKS ONLY.
 * 3) The way this works it breaks the File meta data (I'm working on it).
 * 4) For Move and Copy, this should use a "destination directory tree" on the right (same as for upload)
 * 5) Given all the reasons above copy, move and rename should be clearly separated into 3 different interfaces.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @global string
 */
global $fm_mode;

/**
 * @global Filelist
 */
global $fm_source_Filelist;

/**
 * @global array
 */
global $new_names;


$Form = & new Form( NULL, 'fm_copymove_checkchanges' );

$Form->global_icon( T_('Quit copy/move mode!'), 'close', regenerate_url('fm_sources,fm_mode') );

$Form->begin_form( 'fform', $fm_mode == 'file_copy' ? T_('Copy') : T_('Move') );

	$Form->hidden_ctrl();
	$Form->hiddens_by_key( get_memorized() );
	$Form->hidden( 'confirm', 1 );

	$fm_source_Filelist->restart();
	while( $loop_src_File = & $fm_source_Filelist->get_next() )
	{
		$Form->begin_fieldset( T_('Source').': '.$loop_src_File->get_rdfp_rel_path() );

		if( isset( $overwrite[$loop_src_File->get_md5_ID()] ) )
		{
			$Form->checkbox( 'overwrite['.$loop_src_File->get_md5_ID().']', $overwrite[$loop_src_File->get_md5_ID()], T_('Overwrite'), T_('Check to overwrite the existing file') );
		}

		$Form->text( 'new_names['.$loop_src_File->get_md5_ID().']', $new_names[$loop_src_File->get_md5_ID()], 32,
									T_('New name'), $loop_src_File->dget('title'), 128 );

		$Form->end_fieldset();
	}

$Form->end_form( array( array( 'submit', 'submit', $fm_mode == 'file_copy' ? T_('Copy') : T_('Move'), 'SaveButton' ),
												array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

echo '<p class="notes"><strong>'.T_('You are in copy/move mode.')
				.'</strong> '.T_('Please navigate to the desired target location.').'</p>';

/*
 * $Log$
 * Revision 1.6  2007/01/24 03:45:29  fplanque
 * decrap / removed a lot of bloat...
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
 * Revision 1.16  2006/02/11 21:19:29  fplanque
 * added bozo validator to FM
 *
 * Revision 1.15  2005/12/12 19:21:20  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.14  2005/12/10 03:05:58  blueyed
 * minor
 *
 * Revision 1.13  2005/11/21 18:33:19  fplanque
 * Too many undiscussed changes all around: Massive rollback! :((
 * As said before, I am only taking CLEARLY labelled bugfixes.
 *
 * Revision 1.11  2005/10/28 20:08:46  blueyed
 * Normalized AdminUI
 *
 * Revision 1.10  2005/09/06 17:13:53  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.9  2005/08/22 18:42:25  fplanque
 * minor
 *
 * Revision 1.8  2005/05/24 15:26:50  fplanque
 * cleanup
 *
 * Revision 1.7  2005/05/17 19:26:06  fplanque
 * FM: copy / move debugging
 *
 * Revision 1.5  2005/05/13 16:49:17  fplanque
 * Finished handling of multiple roots in storing file data.
 * Also removed many full paths passed through URL requests.
 * No full path should ever be seen by the user (only the admins).
 *
 * Revision 1.4  2005/04/29 18:49:32  fplanque
 * Normalizing, doc, cleanup
 *
 * Revision 1.3  2005/04/27 19:05:44  fplanque
 * normalizing, cleanup, documentaion
 *
 * Revision 1.2  2005/04/14 19:57:52  fplanque
 * filemanager refactoring & cleanup
 * started implementation of properties/meta data editor
 * note: the whole fm_mode thing is not really desireable...
 *
 * Revision 1.1  2005/04/14 18:34:03  fplanque
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