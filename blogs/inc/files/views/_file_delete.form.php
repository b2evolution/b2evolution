<?php
/**
 * This file implements the UI for file deletion
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @global Filelist
 */
global $selected_Filelist;


$Form = & new Form( NULL );

$Form->global_icon( T_('Cancel delete!'), 'close', regenerate_url() );

$Form->begin_form( 'fform', T_('Delete') );

	$Form->hidden_ctrl();
	$Form->hiddens_by_key( get_memorized() );
	$Form->hidden( 'action', 'delete' );
	$Form->hidden( 'confirmed', 1 );

	$Form->begin_fieldset( T_('Confirm delete') );

	echo $selected_Filelist->count() > 1
		? T_('Do you really want to delete the following files?')
		: T_('Do you really want to delete the following file?');

	$selected_Filelist->restart();
	echo '<ul>';
		while( $l_File = & $selected_Filelist->get_next() )
		{
			echo '<li>'.$l_File->get_prefixed_name().'</li>';
		}
	echo '</ul>';

	$Form->end_fieldset();

$Form->end_form( array(
		array( 'submit', 'submit', T_('Delete'), 'DeleteButton' ) ) );


/*
 * $Log$
 * Revision 1.2  2008/01/21 09:35:29  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 10:59:59  fplanque
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