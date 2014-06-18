<?php
/**
 * This file implements the UI controller for file upload.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id: _file_properties.form.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @global File
 */
global $edited_File;

global $blog, $filename_max_length;

$Form = new Form( NULL, 'fm_properties_checkchanges' );

$Form->global_icon( T_('Close properties!'), 'close', regenerate_url() );

$Form->begin_form( 'fform', T_('File properties') );

	$Form->add_crumb( 'file' );
	$Form->hidden_ctrl();
	$Form->hidden( 'action', 'update_properties' );
	$Form->hiddens_by_key( get_memorized() );

	$Form->begin_fieldset( T_('Properties') );
		if( $current_User->check_perm( 'files', 'edit', false, $blog ? $blog : NULL ) )
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
		if( $current_User->check_perm( 'files', 'edit', false, $blog ? $blog : NULL ) )
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

if( $current_User->check_perm( 'files', 'edit', false, $blog ? $blog : NULL ) )
{ // User can edit:
	$Form->end_form( array( array( 'submit', '', T_('Save Changes!'), 'SaveButton' ) ) );
}
else
{ // User can view only:
	$Form->end_form();
}

?>