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
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: François PLANQUE
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

// Begin payload block:
$AdminUI->dispPayloadBegin();

/*
 * fplanque>> Switch code removed, see users_form
 */

$Form = & new Form( 'b2users.php' );

$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'grp_ID,action' ) );

if( $edited_Group->get('ID') == 0 )
{
	$Form->begin_form( 'fform', T_('Creating new group') );
}
else
{
	$title = ($current_User->check_perm( 'users', 'edit' ) ? T_('Editing group:') : T_('Viewing group:') )
						.' '.
						( isset($edited_grp_oldname) ? $edited_grp_oldname : $edited_Group->dget('name') )
						.' ('.T_('ID').' '.$edited_Group->ID.')';
	$Form->begin_form( 'fform', $title );
}

$Form->hidden( 'action', 'groupupdate' );
$Form->hidden( 'edited_grp_ID', $edited_Group->ID );
$Form->hidden( 'edited_grp_oldname', isset($edited_grp_oldname) ? $edited_grp_oldname : $edited_Group->dget('name','formvalue') );

$Form->fieldset( T_('General') );
$Form->text( 'edited_grp_name', $edited_Group->name, 50, T_('Name'), '', 50, 'large' );
$Form->fieldset_end();

$Form->fieldset( T_('Permissons for members of this group') );

$Form->radio( 'edited_grp_perm_blogs', $edited_Group->get('perm_blogs'),
		array(  array( 'user', T_('User permissions') ),
						array( 'viewall', T_('View all') ),
						array( 'editall', T_('Full Access') )
					), T_('Blogs') );
$Form->radio( 'edited_grp_perm_stats', $edited_Group->get('perm_stats'),
		array(  array( 'none', T_('No Access') ),
						array( 'view', T_('View only') ),
						array( 'edit', T_('Full Access') )
					), T_('Statistics') );
$Form->radio( 'edited_grp_perm_spamblacklist', $edited_Group->get('perm_spamblacklist'),
		array(  array( 'none', T_('No Access') ),
						array( 'view', T_('View only') ),
						array( 'edit', T_('Full Access') )
					), T_('Antispam') );
$Form->radio( 'edited_grp_perm_options', $edited_Group->get('perm_options'),
		array(  array( 'none', T_('No Access') ),
						array( 'view', T_('View only') ),
						array( 'edit', T_('Full Access') )
					), T_('Global options') );
$Form->checkbox( 'edited_grp_perm_templates', $edited_Group->get('perm_templates'), T_('Templates'), T_('Check to allow template editing.') );

if( $edited_Group->get('ID') != 1 )
{	// Groups others than #1 can be prevented from editing users
	$Form->radio( 'edited_grp_perm_users', $edited_Group->get('perm_users'),
			array(  array( 'none', T_('No Access') ),
							array( 'view', T_('View only') ),
							array( 'edit', T_('Full Access') )
						), T_('User/Group Management') );
}
else
{
	$Form->info( T_('User/Group Management'), T_('Full Access') );
}
$Form->fieldset_end();

if( $current_User->check_perm( 'users', 'edit' ) )
{
	$Form->buttons( array( array( '', '', T_('Save !'), 'SaveButton' ),
												 array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}

$Form->fieldset_end();
$Form->end_form();

// End payload block:
$AdminUI->dispPayloadEnd();

/*
 * $Log$
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