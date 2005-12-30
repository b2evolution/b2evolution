<?php
/**
 * This file implements the File type form.
 *
 * @copyright (c)2004-2005 by PROGIDISTRI - {@link http://progidistri.com/}.
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
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Determine if we are creating or updating...
$creating = is_create_action( $action );

$Form = & new Form( 'filetypes.php', 'ftyp_checkchanges' );

$Form->global_icon( T_('Delete this filetype!'), 'delete', regenerate_url( 'action', 'action=delete' ) );
$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', $creating ?  T_('New file type') : T_('File type') );

	$Form->hidden( 'action', $creating ? 'create' : 'update' );
	
	if( ! $creating ) $Form->hidden( 'ftyp_ID', $edited_Filetype->ID );
	
	$Form->text_input( 'ftyp_extensions', $edited_Filetype->extensions, 40, T_('Extensions'), array( 'maxlength'=>80, 'required'=>true ) );
	
	$Form->text_input( 'ftyp_name', $edited_Filetype->name, 40, T_('File type name'), array( 'maxlength'=> 80, 'required'=>true ) );
		
	$Form->text_input( 'ftyp_mimetype', $edited_Filetype->mimetype, 40, T_('Mime type'), array( 'maxlength'=> 80, 'required'=>true ) );
	
	$Form->text( 'ftyp_icon', $edited_Filetype->icon, 20, T_('Icon'), '', 40 );
	
	$Form->radio( 'ftyp_viewtype',
								$edited_Filetype->viewtype,
								 array( 
												array( 'browser', T_( 'Open with browser (popup)' ), T_( 'Let the browser handle the file in a popup.' ) ),
												array( 'text', T_( 'Open with text viewer (popup)' ), T_( 'Use the online text viewer (recommended for .txt' ) ),
												array( 'image', T_( 'Open with image viewer (popup)' ), T_( 'Use the online image viewer (recommended for .gif .png .jpg' ) ),
												array( 'external', T_( 'Open with external app (no popup)' ), T_( 'Let the browser handle the file in a popup. Note: if you do not want Word to open inside of IE, you must uncheck "browse in same window" in Windows\' file types.' ) ),
												array( 'download', T_( 'Download to disk (no popup)' ), T_( 'Tell the browser to save the file to disk instead of displaying it.' ) )
											),
									T_( 'View type' ),
									true // sperate lines
							 );
	
	// Check if the extension is in the array of the not allowed upload extensions from _advanced.php
	$not_allowed = false;
	$extensions = explode ( ' ', $edited_Filetype->extensions );
	foreach($extensions as $extension)
	{
		if( in_array( $extension, $force_upload_forbiddenext ) )
		{
			$not_allowed = true;
			continue;
		}
	}
	
	$Form->checkbox( 'ftyp_allowed', $edited_Filetype->allowed, T_('Allowed'),
									T_('Check to make this file type allowed to upload and renamed'), '', 1, $not_allowed);

if( $creating )
{
	$Form->end_form( array( array( 'submit', 'submit', T_('Record'), 'SaveButton' ),
													array( 'submit', 'submit', T_('Record, then Create New'), 'SaveButton' ),
													array( 'submit', 'submit', T_('Record, then Create Similar'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}
else
{
	$Form->end_form( array( array( 'submit', 'submit', T_('Update'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}


?>