<?php
/**
 * This file implements the UI view for the user group properties.
 *
 * Called by {@link b2users.php}
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

// Begin payload block:
$AdminUI->dispPayloadBegin();

/*
 * fplanque>> Switch code removed, see users_form
 */

$Form = & new Form( 'b2users.php' );

$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'group,action' ) );

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

  $Form->hidden( 'action', 'groupupdate' );
  $Form->hidden( 'edited_grp_ID', $edited_Group->ID );
  $Form->hidden( 'edited_grp_oldname', isset($edited_grp_oldname) ? $edited_grp_oldname : $edited_Group->dget('name','formvalue') );
}

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
 * Revision 1.25  2005/03/21 18:57:24  fplanque
 * user management refactoring (towards new evocore coding guidelines)
 * WARNING: some pre-existing bugs have not been fixed here
 *
 */
?>