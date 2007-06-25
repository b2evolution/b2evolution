<?php
/**
 * This file implements the UI view for the user group properties.
 *
 * Called by {@link b2users.php}
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @var Group
 */
global $edited_Group;

global $action;

// Begin payload block:
$this->disp_payload_begin();


$Form = & new Form( NULL, 'group_checkchanges' );

$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'grp_ID,action' ) );

if( $edited_Group->ID == 0 )
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

$Form->hidden_ctrl();
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

	if( $edited_Group->ID != 1 )
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
							array( 'user', T_('User blogs') ), // fp> dirty hack, I'll tie this to blog edit perm for now
							array( 'view', T_('View all') ),
							array( 'edit', T_('Full Access') )
						), T_('Stats') );

	$Form->radio( 'edited_grp_perm_spamblacklist', $edited_Group->get('perm_spamblacklist'),
			array(  $perm_none_option,
							array( 'view', T_('View only') ),
							array( 'edit', T_('Full Access') )
						), T_('Antispam') );

	// fp> todo perm check
	$filetypes_linkstart = '<a href="?ctrl=filetypes" title="'.T_('Edit locked file types...').'">';
	$filetypes_linkend = '</a>';
	$Form->radio( 'edited_grp_perm_files', $edited_Group->get('perm_files'),
			array(	$perm_none_option,
							$perm_view_option,
							array( 'add', T_('Add/Upload') ),
							array( 'edit', sprintf( T_('Edit %s'), $filetypes_linkstart.get_icon('file_allowed').$filetypes_linkend ) ),
							array( 'all', sprintf( T_('Edit all, including %s'), $filetypes_linkstart.get_icon('file_not_allowed').$filetypes_linkend ) ),
						), T_('Files'), false, T_('This setting will further restrict any media file permissions on specific blogs.') );

	$Form->checkbox( 'edited_grp_perm_templates', $edited_Group->get('perm_templates'), T_('Skins'), T_('Check to allow access to skin files.') );

	if( $edited_Group->ID != 1 )
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
$this->disp_payload_end();

/*
 * $Log$
 * Revision 1.1  2007/06/25 11:01:50  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.11  2007/04/26 00:11:13  fplanque
 * (c) 2007
 *
 * Revision 1.10  2007/03/20 09:53:26  fplanque
 * Letting boggers view their own stats.
 * + Letthing admins view the aggregate by default.
 *
 * Revision 1.9  2007/01/23 04:20:31  fplanque
 * wording
 *
 * Revision 1.8  2006/12/17 23:42:39  fplanque
 * Removed special behavior of blog #1. Any blog can now aggregate any other combination of blogs.
 * Look into Advanced Settings for the aggregating blog.
 * There may be side effects and new bugs created by this. Please report them :]
 *
 * Revision 1.7  2006/12/07 16:06:24  fplanque
 * prepared new file editing permission
 *
 * Revision 1.6  2006/11/24 18:27:26  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 */
?>