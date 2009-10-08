<?php
/**
 * This file implements the UI view for the user group properties.
 *
 * Called by {@link b2users.php}
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
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

$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'ctrl,grp_ID,action', 'ctrl=users' ) );

if( $edited_Group->ID == 0 )
{
	$Form->begin_form( 'fform', T_('Creating new group') );
}
else
{
	$title = ( $action == 'edit' ? T_('Editing group:') : T_('Viewing group:') )
						.' '.
						( isset($edited_grp_oldname) ? $edited_grp_oldname : $edited_Group->dget('name') )
						.' ('.T_('ID').' '.$edited_Group->ID.')';
	$Form->begin_form( 'fform', $title );
}

$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'grp_ID', $edited_Group->ID );

$perm_none_option = array( 'none', T_('No Access') );
$perm_view_option = array( 'view', T_('View details') );
$perm_edit_option = array( 'edit', T_('Edit/delete all') );


$Form->begin_fieldset( T_('General').get_manual_link('group_properties_general') );

	$Form->text( 'edited_grp_name', $edited_Group->name, 50, T_('Name'), '', 50, 'large' );

 	if( $edited_Group->ID != 1 )
	{	// Groups others than #1 can be prevented from editing users
		$Form->radio( 'edited_grp_perm_admin', $edited_Group->get('perm_admin'),
				array(  $perm_none_option,
								array( 'hidden', T_('Hidden (type in admin.php directly)') ),
								array( 'visible', T_('Visible links to admin in evobar') ) // TODO: this may be obsolete, especially now we have the evobar
							), T_('Access to Admin area'), false );
	}
	else
	{	// Group #1 always has user management right:
		$Form->info( T_('Access to Admin area'), T_('Visible link') );
	}

$Form->end_fieldset();

$Form->begin_fieldset( T_('Blogging permissions').get_manual_link('group_properties_blogging') );

	$Form->radio( 'edited_grp_perm_blogs', $edited_Group->get('perm_blogs'),
			array(  array( 'user', T_('Depending on each blog\'s permissions') ),
							array( 'viewall', T_('View all blogs') ),
							array( 'editall', T_('Full Access') )
						), T_('Blogs'), false );

	$Form->radio( 'perm_xhtmlvalidation', $edited_Group->get('perm_xhtmlvalidation'),
			array(  array( 'always', T_('Force valid XHTML + strong security'),
											T_('The security filters below will be strongly enforced.') ),
							array( 'never', T_('Basic security checking'),
											T_('Security filters below will still be enforced but with potential lesser accuracy.') )
						), T_('XHTML validation'), true );

	$Form->radio( 'perm_xhtmlvalidation_xmlrpc', $edited_Group->get('perm_xhtmlvalidation_xmlrpc'),
			array(  array( 'always', T_('Force valid XHTML + strong security'),
											T_('The security filters below will be strongly enforced.') ),
							array( 'never', T_('Basic security checking'),
											T_('Security filters below will still be enforced but with potential lesser accuracy.') )
						), T_('XHTML validation on XML-RPC calls'), true );

	$Form->checklist( array(
						array( 'prevent_css_tweaks', 1, T_('Prevent CSS tweaks'), ! $edited_Group->get('perm_xhtml_css_tweaks'), false,
											T_('WARNING: if allowed, users may deface the site, add hidden text, etc.') ),
						array( 'prevent_iframes', 1, T_('Prevent iframes'), ! $edited_Group->get('perm_xhtml_iframes'), false,
											T_('WARNING: if allowed, users may do XSS hacks, steal passwords from other users, etc.') ),
						array( 'prevent_javascript', 1, T_('Prevent javascript'), ! $edited_Group->get('perm_xhtml_javascript'), false,
											T_('WARNING: if allowed, users can easily do XSS hacks, steal passwords from other users, etc.') ),
						array( 'prevent_objects', 1, T_('Prevent objects'), ! $edited_Group->get('perm_xhtml_objects'), false,
											T_('WARNING: if allowed, users can spread viruses and malware through this blog.') ),
					), 'xhtml_security', T_('Security filters') );

	$Form->checkbox( 'apply_antispam', ! $edited_Group->get('perm_bypass_antispam'), T_('Antispam filtering'),
										T_('Inputs from these users will be checked against the antispam blacklist.') );

$Form->end_fieldset();

$Form->begin_fieldset( T_('Additional permissions').get_manual_link('group_properties_additional_permissions') );

	$Form->radio( 'edited_grp_perm_stats', $edited_Group->get('perm_stats'),
			array(  $perm_none_option,
							array( 'user', T_('View stats for specific blogs'), T_('Based on each blog\'s edit permissions') ), // fp> dirty hack, I'll tie this to blog edit perm for now
							array( 'view', T_('View stats for all blogs') ),
							array( 'edit', T_('Full Access'), T_('Includes deleting/reassigning of stats') )
						), T_('Stats'), true );

	// fp> todo perm check
	$filetypes_linkstart = '<a href="?ctrl=filetypes" title="'.T_('Edit locked file types...').'">';
	$filetypes_linkend = '</a>';
	$Form->radio( 'edited_grp_perm_files', $edited_Group->get('perm_files'),
			array(	$perm_none_option,
							array( 'view', T_('View files for all allowed roots') ),
							array( 'add', T_('Add/Upload files to allowed roots') ),
							array( 'edit', sprintf( T_('Edit %sunlocked files'), $filetypes_linkstart.get_icon('file_allowed').$filetypes_linkend ) ),
							array( 'all', sprintf( T_('Edit all files, including %slocked ones'), $filetypes_linkstart.get_icon('file_not_allowed').$filetypes_linkend ), T_('Needed for editing PHP files in skins.') ),

						), T_('Files'), true, T_('This setting will further restrict any media file permissions on specific blogs.') );

// ----------------------------------------
// Display all of the pluggable permissions

$GroupSettings = & $edited_Group->get_GroupSettings();
foreach( $GroupSettings->permission_modules as $perm_name => $module_name )
{
	$Module = & $GLOBALS[$module_name.'_Module'];
	if( method_exists( $Module, 'get_available_group_permissions' ) )
	{
		$permissions = $Module->get_available_group_permissions();
		if( array_key_exists( $perm_name, $permissions ) )
		{
			$perm = $permissions[$perm_name];
			$Form->radio( 'edited_grp_'.$perm_name, $GroupSettings->permission_values[$perm_name], $perm['available'], $perm['label'], true );
		}
	}
}
// ----------------------------------------


$Form->end_fieldset();

$Form->begin_fieldset( T_('System admin permissions').get_manual_link('group_properties_system_permissions') );

	$Form->radio( 'edited_grp_perm_spamblacklist', $edited_Group->get('perm_spamblacklist'),
			array(  $perm_none_option,
							array( 'view', T_('View only') ),
							array( 'edit', T_('Full Access') )
						), T_('Antispam'), false );

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
 * Revision 1.17  2009/10/08 20:05:52  efy-maxim
 * Modular/Pluggable Permissions
 *
 * Revision 1.16  2009/09/25 20:26:26  fplanque
 * fixes/doc
 *
 * Revision 1.15  2009/09/25 14:18:22  tblue246
 * Reverting accidental commits
 *
 * Revision 1.13  2009/09/23 13:32:21  efy-bogdan
 * Separate controller added for groups
 *
 * Revision 1.12  2009/09/18 14:22:11  efy-maxim
 * 1. 'reply' permission in group form
 * 2. functionality to store and update contacts
 * 3. fix in misc functions
 *
 * Revision 1.11  2009/09/13 12:25:34  efy-maxim
 * Messaging permissions have been added to:
 * 1. Upgrader
 * 2. Group class
 * 3. Edit Group form
 *
 * Revision 1.10  2009/07/08 22:42:15  fplanque
 * wording for newbies
 *
 * Revision 1.9  2009/03/22 16:15:06  fplanque
 * Reverted fugky hack where all possible combiantions pose as a "level"
 * Use one checkbox per attributes class/style/id
 *
 * Revision 1.7  2009/03/21 22:55:15  fplanque
 * Adding TinyMCE -- lowfat version
 *
 * Revision 1.6  2009/03/08 23:57:46  fplanque
 * 2009
 *
 * Revision 1.5  2008/01/21 09:35:36  fplanque
 * (c) 2008
 *
 * Revision 1.4  2008/01/20 18:20:25  fplanque
 * Antispam per group setting
 *
 * Revision 1.3  2008/01/20 15:31:12  fplanque
 * configurable validation/security rules
 *
 * Revision 1.2  2008/01/19 10:57:10  fplanque
 * Splitting XHTML checking by group and interface
 *
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