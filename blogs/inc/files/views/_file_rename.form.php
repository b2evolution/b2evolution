<?php
/**
 * This file implements the UI for file rename
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
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
		$Form->begin_fieldset( T_('File').': '.$loop_src_File->dget('name') );

		$Form->text( 'new_names['.$loop_src_File->get_md5_ID().']', $new_names[$loop_src_File->get_md5_ID()], 32,
									T_('New name'), $loop_src_File->dget('title'), 128 );

		$Form->end_fieldset();
	}


$Form->end_form( array( array( 'submit', 'submit', T_('Rename'), 'SaveButton' ),
												array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

/*
 * $Log$
 * Revision 1.1  2007/06/25 11:00:05  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.7  2007/04/26 00:11:10  fplanque
 * (c) 2007
 *
 * Revision 1.6  2006/12/23 22:53:10  fplanque
 * extra security
 *
 * Revision 1.5  2006/11/24 18:27:25  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 */
?>