<?php
/**
 * This file implements the UI controller for file upload.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
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
$AdminUI->disp_payload_begin();

$Form = & new Form( 'files.php', 'fm_properties_checkchanges' );

$Form->global_icon( T_('Close properties!'), 'close', $Fileman->getCurUrl( array( 'fm_mode' => false ) ) );

$Form->begin_form( 'fform', T_('File properties') );

	echo $Fileman->getFormHiddenInputs();
	echo $Fileman->getFormHiddenSelectedFiles();
	$Form->hidden( 'action', 'update_properties' );

	$Form->begin_fieldset( T_('Properties') );
		$Form->info( T_('Filename'), $selectedFile->get_name(), T_('This is the name of the file on the server hard drive.') );
		$Form->info( T_('Type'), $selectedFile->get_icon().' '.$selectedFile->get_type() );
	$Form->end_fieldset();

	$Form->begin_fieldset( T_('Meta data') );
		if( $current_User->check_perm( 'files', 'edit' ) )
		{ // User can edit:
			$Form->text( 'title', $selectedFile->title, 50, T_('Long title'), T_('This is a longer descriptive title'), 255 );
			$Form->text( 'alt', $selectedFile->alt, 50, T_('Alternative text'), T_('This is useful for images'), 255 );
			$Form->textarea( 'desc', $selectedFile->desc, 10, T_('Caption/Description') );
		}
		else
		{ // User can view only:
			$Form->info( T_('Long title'), $selectedFile->dget('title'), T_('This is a longer descriptive title') );
			$Form->info( T_('Alternative text'), $selectedFile->dget('alt'), T_('This is useful for images') );
			$Form->info( T_('Caption/Description'), $selectedFile->dget('desc') );
		}
	$Form->end_fieldset();

if( $current_User->check_perm( 'files', 'edit' ) )
{ // User can edit:
	$Form->end_form( array( array( 'submit', '', T_('Update'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}
else
{ // User can view only:
	$Form->end_form();
}

// End payload block:
$AdminUI->disp_payload_end();

/*
 * $Log$
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