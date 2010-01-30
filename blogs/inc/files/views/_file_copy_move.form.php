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
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
									T_('New name'), $loop_src_File->dget('title'), 128 );

		$Form->end_fieldset();
	}

$Form->end_form( array( array( 'submit', 'submit', $fm_mode == 'file_copy' ? T_('Copy') : T_('Move'), 'SaveButton' ),
												array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

echo '<p class="notes"><strong>'.T_('You are in copy/move mode.')
				.'</strong> '.T_('Please navigate to the desired target location.').'</p>';

/*
 * $Log$
 * Revision 1.5  2010/01/30 18:55:26  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.4  2010/01/03 13:45:36  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.3  2009/03/08 23:57:43  fplanque
 * 2009
 *
 * Revision 1.2  2008/01/21 09:35:29  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 10:59:58  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.7  2007/04/26 00:11:10  fplanque
 * (c) 2007
 *
 * Revision 1.6  2007/01/24 03:45:29  fplanque
 * decrap / removed a lot of bloat...
 *
 * Revision 1.5  2006/11/24 18:27:25  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 */
?>