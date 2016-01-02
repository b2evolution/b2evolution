<?php
/**
 * This file implements the UI controller for file upload.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @global File
 */
global $edited_File, $selected_Filelist;

global $blog, $filename_max_length;

$edit_allowed_perm = $current_User->check_perm( 'files', 'edit_allowed', false, $selected_Filelist->get_FileRoot() );

$Form = new Form( NULL, 'fm_properties_checkchanges' );

if( get_param( 'mode' ) != 'modal' )
{
	$Form->global_icon( T_('Close properties!'), 'close', regenerate_url() );
}

$Form->begin_form( 'fform', ( get_param( 'mode' ) == 'modal' ? '' : T_('File properties') ) );

	$Form->add_crumb( 'file' );
	$Form->hidden_ctrl();
	$Form->hidden( 'action', 'update_properties' );
	$Form->hiddens_by_key( get_memorized() );

	$Form->begin_fieldset( T_('Properties') );
		if( $edit_allowed_perm )
		{ // User can edit: 
			$Form->text( 'name', $edited_File->dget('name'), 32, T_('Filename'), T_('This is the name of the file on the server hard drive.'), $filename_max_length );
		}
		else
		{ // User can view only:
			$Form->info( T_('Filename'), $edited_File->dget('name'), T_('This is the name of the file on the server hard drive.') );	
		}
		$Form->info( T_('Type'), $edited_File->get_icon().' '.$edited_File->get_type() );
	$Form->end_fieldset();

	$Form->begin_fieldset( T_('Meta data') );
		if( $edit_allowed_perm )
		{ // User can edit:
			$Form->text( 'title', $edited_File->title, 50, T_('Long title'), T_('This is a longer descriptive title'), 255 );
			$Form->text( 'alt', $edited_File->alt, 50, T_('Alternative text'), T_('This is useful for images'), 255 );
			$Form->textarea( 'desc', $edited_File->desc, 10, T_('Caption/Description') );
		}
		else
		{ // User can view only:
			$Form->info( T_('Long title'), $edited_File->dget('title'), T_('This is a longer descriptive title') );
			$Form->info( T_('Alternative text'), $edited_File->dget('alt'), T_('This is useful for images') );
			$Form->info( T_('Caption/Description'), $edited_File->dget('desc') );
		}
	$Form->end_fieldset();

	$Form->begin_fieldset( T_('Social votes') );
		$Form->info( T_('Liked'), $edited_File->get_votes_count_info( 'like' ) );
		$Form->info( T_('Disliked'), $edited_File->get_votes_count_info( 'dislike' ) );
		$Form->info( T_('Reported as inappropriate'), $edited_File->get_votes_count_info( 'inappropriate' ) );
		$Form->info( T_('Reported as spam'), $edited_File->get_votes_count_info( 'spam' ) );
	$Form->end_fieldset();

if( $edit_allowed_perm )
{ // User can edit:
	$Form->end_form( array( array( 'submit', '', T_('Save Changes!'), 'SaveButton' ) ) );
}
else
{ // User can view only:
	$Form->end_form();
}

?>