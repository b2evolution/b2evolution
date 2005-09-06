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
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: François PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( false )
{	/**
	 * This is ugly, sorry, but I temporarily need this until NuSphere fixes their CodeInsight :'(
	 */
	include('_header.php');
	include('files.php');
}

// Begin payload block:
$AdminUI->dispPayloadBegin();

$Form = & new Form( 'files.php' );

$Form->global_icon( T_('Quit copy/move mode!'), 'close',	$Fileman->getCurUrl( array( 'fm_mode' => false, 'forceFM' => 1 ) ) );

$Form->begin_form( 'fform', $Fileman->fm_mode == 'file_copy' ? T_('Copy') : T_('Move') );

	echo $Fileman->getFormHiddenInputs();
	$Form->hidden( 'confirm', 1 );

	$Fileman->SourceList->restart();
	while( $loop_src_File = & $Fileman->SourceList->get_next() )
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

$Form->end_form( array( array( 'submit', 'submit', $Fileman->fm_mode == 'file_copy' ? T_('Copy') : T_('Move'), 'SaveButton' ),
												array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

echo '<p class="notes"><strong>'.T_('You are in copy/move mode.')
				.'</strong> '.T_('Please navigate to the desired target location.').'</p>';

// End payload block:
$AdminUI->dispPayloadEnd();

/*
 * $Log$
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