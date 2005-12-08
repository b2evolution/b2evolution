<?php
/**
 * This file implements the UI view for the user group properties.
 *
 * Called by {@link b2users.php}
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
 * {@internal
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: François PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Begin payload block:
$AdminUI->disp_payload_begin();


$Form = new Form( 'b2users.php' );

$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'grp_ID,action' ) );

if( $edited_Group->get('ID') == 0 )
{
	$Form->begin_form( 'fform', T_('Creating new group') );
}
else
{
	$title = ( $action == 'edit_user' ? T_('Editing group:') : T_('Viewing group:') )
						.' '.
						( isset($edited_grp_oldname) ? $edited_grp_oldname : $edited_Group->dget('name') )
						.' ('.T_('ID').' '.$edited_Group->ID.')';
	$Form->begin_form( 'fform', $title );
}

$Form->hidden( 'action', 'groupupdate' );
$Form->hidden( 'grp_ID', $edited_Group->ID );

$Form->begin_fieldset( T_('General') );
	$Form->text( 'edited_grp_name', $edited_Group->name, 50, T_('Name'), '', 50, 'large' );
$Form->end_fieldset();

$perm_none_option = array( 'none', '<acronym title="'.T_('No Access').'">'.T_('None').'</acronym>' );
$perm_list_option = array( 'list', '<acronym title="'.T_('View list only').'">'.T_('List').'</acronym>' );
$perm_view_option = array( 'view', '<acronym title="'.T_('View details').'">'.T_('View').'</acronym>' );
$perm_add_option = array( 'add',  '<acronym title="'.T_('Add & edit/delete self created').'">'.T_('Add').'</acronym>' );
$perm_edit_option = array( 'edit', '<acronym title="'.T_('Edit/delete all').'">'.T_('Edit').'</acronym>' );
$standard_perm_options = array(
							$perm_none_option,
							$perm_list_option,
							$perm_view_option,
							$perm_add_option,
							$perm_edit_option
						);

$Form->begin_fieldset( T_('Permissions for members of this group') );

	if( $edited_Group->get('ID') != 1 )
	{	// Groups others than #1 can be prevented from editing users
		$Form->radio( 'edited_grp_perm_admin', $edited_Group->get('perm_admin'),
				array(  array( 'none', T_('No Access') ),
								array( 'hidden', T_('Hidden') ),
								array( 'visible', T_('Visible link') )
							), T_('Access to Admin area') );
	}
	else
	{	// Group #1 always has user management right:
		$Form->info( T_('Access to Admin area'), T_('Visible link') );
	}
	$Form->radio( 'edited_grp_perm_blogs', $edited_Group->get('perm_blogs'),
			array(  array( 'user', T_('User permissions') ),
							array( 'viewall', T_('View all') ),
							array( 'editall', T_('Full Access') )
						), T_('Blogs') );
	$Form->radio( 'edited_grp_perm_stats', $edited_Group->get('perm_stats'),
			array(  $perm_none_option,
							array( 'view', T_('View only') ),
							array( 'edit', T_('Full Access') )
						), T_('Stats') );
	$Form->radio( 'edited_grp_perm_spamblacklist', $edited_Group->get('perm_spamblacklist'),
			array(  $perm_none_option,
							array( 'view', T_('View only') ),
							array( 'edit', T_('Full Access') )
						), T_('Antispam') );
	$Form->checkbox( 'edited_grp_perm_templates', $edited_Group->get('perm_templates'), T_('Templates'), T_('Check to allow template editing.') );
	$Form->radio( 'edited_grp_perm_files', $edited_Group->get('perm_files'),
			array(	$perm_none_option,
							$perm_view_option,
							array( 'add', T_('Add/Upload') ),
							$perm_edit_option
						), T_('Files') );
	if( $edited_Group->get('ID') != 1 )
	{	// Groups others than #1 can be prevented from editing users
		$Form->radio( 'edited_grp_perm_users', $edited_Group->get('perm_users'),
				array(	$perm_none_option,
								$perm_view_option,
								$perm_edit_option
							), T_('Users & Groups') );
	}
	else
	{	// Group #1 always has user management right:
		$Form->info( T_('Users & Groups'), T_('Full Access') );
	}
	$Form->radio( 'edited_grp_perm_options', $edited_Group->get('perm_options'),
			array(	$perm_none_option,
							$perm_view_option,
							$perm_edit_option
						), T_('Settings') );

$Form->end_fieldset();

if( $action != 'view_group' )
{
	$Form->buttons( array(
		array( '', '', T_('Save !'), 'SaveButton' ),
		array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}

$Form->end_form();

// End payload block:
$AdminUI->disp_payload_end();

/*
 * $Log$
 * Revision 1.42  2005/12/08 22:23:44  blueyed
 * Merged 1-2-3-4 scheme from post-phoenix
 *
 * Revision 1.41  2005/11/16 04:45:51  blueyed
 * Typo
 *
 * Revision 1.40  2005/10/28 20:08:46  blueyed
 * Normalized AdminUI
 *
 * Revision 1.39  2005/10/27 15:25:03  fplanque
 * Normalization; doc; comments.
 *
 * Revision 1.38  2005/09/06 17:13:53  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.37  2005/08/22 18:42:25  fplanque
 * minor
 *
 * Revision 1.36  2005/08/11 19:41:10  fplanque
 * no message
 *
 * Revision 1.35  2005/08/08 13:58:51  fplanque
 * rollback
 *
 * Revision 1.33  2005/07/15 18:12:33  fplanque
 * no message
 *
 * Revision 1.32  2005/06/20 17:40:14  fplanque
 * minor
 *
 * Revision 1.31  2005/06/03 20:14:38  fplanque
 * started input validation framework
 *
 * Revision 1.30  2005/05/09 19:06:53  fplanque
 * bugfixes + global access permission
 *
 * Revision 1.29  2005/05/09 16:09:38  fplanque
 * implemented file manager permissions through Groups
 *
 * Revision 1.28  2005/05/04 18:16:55  fplanque
 * Normalizing
 *
 * Revision 1.27  2005/05/03 14:43:33  fplanque
 * no message
 *
 * Revision 1.26  2005/03/22 16:36:01  fplanque
 * refactoring, standardization
 * fixed group creation bug
 *
 * Revision 1.25  2005/03/21 18:57:24  fplanque
 * user management refactoring (towards new evocore coding guidelines)
 * WARNING: some pre-existing bugs have not been fixed here
 *
 */
?>