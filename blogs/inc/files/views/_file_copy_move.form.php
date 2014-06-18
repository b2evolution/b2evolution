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
 * @version $Id: _file_copy_move.form.php 6135 2014-03-08 07:54:05Z manuel $
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

global $filename_max_length;


$Form = new Form( NULL, 'fm_copymove_checkchanges' );

$Form->global_icon( T_('Quit copy/move mode!'), 'close', regenerate_url('fm_sources,fm_mode') );

$Form->begin_form( 'fform', $fm_mode == 'file_copy' ? T_('Copy') : T_('Move') );

	$Form->add_crumb( 'file' );
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
									T_('New name'), $loop_src_File->dget('title'), $filename_max_length );

		$Form->end_fieldset();
	}

$Form->end_form( array( array( 'submit', 'submit', $fm_mode == 'file_copy' ? T_('Copy') : T_('Move'), 'SaveButton' ) ) );

echo '<p class="notes"><strong>'.T_('You are in copy/move mode.')
				.'</strong> '.T_('Please navigate to the desired target location.').'</p>';

?>