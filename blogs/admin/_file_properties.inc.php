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
 * @author fplanque: François PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

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

$Form->global_icon( T_('Close properties!'), 'close',	$Fileman->getCurUrl( array( 'fm_mode' => false, 'forceFM' => 1 ) ) );

$Form->begin_form( 'fform', T_('File properties') );

	echo $Fileman->getFormHiddenInputs();
	echo $Fileman->getFormHiddenSelectedFiles();
	$Form->hidden( 'action', 'update_properties' );

	$Form->fieldset( T_('Properties') );
		$Form->info( T_('Filename'), $selectedFile->get_name(), T_('This is the name of the file on the server hard drive.') );
		$Form->info( T_('Type'), getIcon( $selectedFile ).' '.$selectedFile->getType() );
	$Form->fieldset_end();

	$Form->fieldset( T_('Meta data') );
	  $Form->text( 'title', $selectedFile->title, 50, T_('Long title'), T_('This is a longer descriptive title'), 255 );
	  $Form->text( 'alt', $selectedFile->alt, 50, T_('Alternative text'), T_('This is useful for images'), 255 );
	  $Form->textarea( 'desc', $selectedFile->desc, 10, T_('Caption/Description') );
	$Form->fieldset_end();

$Form->end_form( array( array( 'submit', '', T_('Update'), 'SaveButton' ),
												array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

// End payload block:
$AdminUI->dispPayloadEnd();

/*
 * $Log$
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