<?php
/**
 * This file implements the UI for file deletion
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link https://thequod.de/}.
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
 *
 * In addition, as a special exception, the copyright holders give permission to link
 * the code of this program with the PHP/SWF Charts library by maani.us (or with
 * modified versions of this library that use the same license as PHP/SWF Charts library
 * by maani.us), and distribute linked combinations including the two. You must obey the
 * GNU General Public License in all respects for all of the code used other than the
 * PHP/SWF Charts library by maani.us. If you modify this file, you may extend this
 * exception to your version of the file, but you are not obligated to do so. If you do
 * not wish to do so, delete this exception statement from your version.
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: François PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


$Form = & new Form( 'files.php' );

$Form->global_icon( T_('Cancel delete!'), 'close',	$Fileman->getCurUrl( ) );

$Form->begin_form( 'fform', T_('Delete') );
	echo $Fileman->getFormHiddenInputs();
	echo $Fileman->getFormHiddenSelectedFiles();
	$Form->hidden( 'action', 'delete' );
	$Form->hidden( 'confirmed', 1 );

	echo $selected_Filelist->count() > 1
		? T_('Do you really want to delete the following files?')
		: T_('Do you really want to delete the following file?');

	$selected_Filelist->restart();
	?>

	<ul>

	<?php
	while( $l_File = & $selected_Filelist->get_next() )
	{
		echo '<li>'.$l_File->get_prefixed_name().'</li>';
	}
	?>

	</ul>

<?php
$Form->end_form( array(
		array( 'submit', 'submit', T_('Delete'), 'DeleteButton' ),
		array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );


/*
 * $Log$
 * Revision 1.1  2005/11/19 03:45:51  blueyed
 * Transformed 'delete' to 1-2-3-4 scheme, plus small fixes
 *
 *
 */
?>