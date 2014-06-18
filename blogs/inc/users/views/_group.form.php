<?php
/**
 * This file implements the UI view for the user group properties.
 *
 * Called by {@link b2users.php}
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * @version $Id: _group.form.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @var Group
 */
global $edited_Group;

global $action;

// asimo> this may belong to the pluggable permissions display
// javascript to handle shared root permissions, when file permission was changed
?>
<script type="text/javascript">
	function file_perm_changed()
	{
		var file_perm = jQuery( '[name="edited_grp_perm_files"]:checked' ).val();
		if( file_perm == null )
		{ // there is file perms radio
			return;
		}

		switch( file_perm )
		{
		case "none":
			jQuery('#edited_grp_perm_shared_root_radio_2').attr('disabled', 'disabled');
			jQuery('#edited_grp_perm_shared_root_radio_3').attr('disabled', 'disabled');
			jQuery('#edited_grp_perm_shared_root_radio_4').attr('disabled', 'disabled');
			break;
		case "view":
			jQuery('#edited_grp_perm_shared_root_radio_2').removeAttr('disabled');
			jQuery('#edited_grp_perm_shared_root_radio_3').attr('disabled', 'disabled');
			jQuery('#edited_grp_perm_shared_root_radio_4').attr('disabled', 'disabled');
			break;
		case "add":
			jQuery('#edited_grp_perm_shared_root_radio_2').removeAttr('disabled');
			jQuery('#edited_grp_perm_shared_root_radio_3').removeAttr('disabled');
			jQuery('#edited_grp_perm_shared_root_radio_4').attr('disabled', 'disabled');
			break;
		default:
			jQuery('#edited_grp_perm_shared_root_radio_2').removeAttr('disabled');
			jQuery('#edited_grp_perm_shared_root_radio_3').removeAttr('disabled');
			jQuery('#edited_grp_perm_shared_root_radio_4').removeAttr('disabled');
		}
	}
</script>
<?php

/**
 * Display pluggable permissions
 *
 * @param string perm block name  'additional'|'system'
 */
function display_pluggable_permissions( &$Form, $perm_block )
{
	global $edited_Group;

	$GroupSettings = & $edited_Group->get_GroupSettings();
	foreach( $GroupSettings->permission_modules as $perm_name => $module_name )
	{
		$Module = & $GLOBALS[$module_name.'_Module'];
		if( method_exists( $Module, 'get_available_group_permissions' ) )
		{
			$permissions = $Module->get_available_group_permissions( $edited_Group->ID );
			if( array_key_exists( $perm_name, $permissions ) )
			{
				$perm = $permissions[$perm_name];
				if( $perm['perm_block'] == $perm_block )
				{
					if( ! isset( $perm['perm_type'] ) )
					{
						$perm['perm_type'] = 'radiobox';
					}

					switch( $perm['perm_type'] )
					{
						case 'checkbox':
							$Form->checkbox_input( 'edited_grp_'.$perm_name, $GroupSettings->permission_values[$perm_name] == 'allowed', $perm['label'], array( 'input_suffix' => ' '.$perm['note'], 'value' => 'allowed' ) );
						break;

						case 'radiobox':
							if( ! isset( $perm['field_lines'] ) )
							{
								$perm['field_lines'] = true;
							}
							if( ! isset( $perm['field_note'] ) )
							{
								$perm['field_note'] = '';
							}
							$Form->radio( 'edited_grp_'.$perm_name, $GroupSettings->permission_values[$perm_name], $perm['options'], $perm['label'], $perm['field_lines'], $perm['field_note'] );
						break;

						case 'info':
							$Form->info( $perm['label'], $perm['info'] );
						break;

						case 'text_input':
							$Form->text_input( 'edited_grp_'.$perm_name, $GroupSettings->permission_values[$perm_name], 5, $perm['label'], $perm['note'], array( 'maxlength' => $perm['maxlength'] ) );
						break;
					}
				}
			}
		}
	}
}

$Form = new Form( NULL, 'group_checkchanges' );

$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'ctrl,grp_ID,action', 'ctrl=groups' ) );

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

	$Form->add_crumb( 'group' );
	$Form->hidden_ctrl();
	$Form->hidden( 'action', 'update' );
	$Form->hidden( 'grp_ID', $edited_Group->ID );

$perm_none_option = array( 'none', T_('No Access') );
$perm_view_option = array( 'view', T_('View details') );
$perm_edit_option = array( 'edit', T_('Edit/delete all') );


$Form->begin_fieldset( T_('General').get_manual_link('group_properties_general') );

	$Form->text( 'edited_grp_name', $edited_Group->name, 50, T_('Name'), '', 50, 'large' );

	display_pluggable_permissions( $Form, 'core_general' );

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

	// Display pluggable permissions:
	display_pluggable_permissions( $Form, 'blogging' );

$Form->end_fieldset();

$Form->begin_fieldset( T_('Additional permissions').get_manual_link('group_properties_additional_permissions') );

	$Form->radio( 'edited_grp_perm_stats', $edited_Group->get('perm_stats'),
			array(  $perm_none_option,
							array( 'user', T_('View stats for specific blogs'), T_('Based on each blog\'s edit permissions') ), // fp> dirty hack, I'll tie this to blog edit perm for now
							array( 'view', T_('View stats for all blogs') ),
							array( 'edit', T_('Full Access'), T_('Includes deleting/reassigning of stats') )
						), T_('Stats'), true );

	// Display pluggable permissions:
	display_pluggable_permissions( $Form, 'additional' );

$Form->end_fieldset();

$Form->begin_fieldset( T_('System admin permissions').get_manual_link('group_properties_system_permissions') );

	// Display pluggable permissions:
	display_pluggable_permissions( $Form, 'core' );

	// show Settings children permissions only if this user group has at least "View details" rights on global System Settings
	echo '<div id="perm_options_children"'.( $edited_Group->check_perm( 'options', 'view' ) ? '' : ' style="display:none"' ).'>';
	display_pluggable_permissions( $Form, 'core2' );
	display_pluggable_permissions( $Form, 'system' );
	echo '</div>';

	display_pluggable_permissions( $Form, 'core3' );

$Form->end_fieldset();

$Form->begin_fieldset( T_( 'Notification options') );

	// Display pluggale notification options
	display_pluggable_permissions( $Form, 'notifications');

$Form->end_fieldset();

if( $action != 'view' )
{
	$Form->buttons( array( array( '', '', T_('Save Changes!'), 'SaveButton' ) ) );
}

$Form->end_form();

// set shared root permission availability, when form was loaded and when file perms was changed
?>
<script type="text/javascript">
file_perm_changed();
jQuery( '[name="edited_grp_perm_files"]' ).click( function() {
	file_perm_changed();
} );

jQuery( 'input[name=edited_grp_perm_options]' ).click( function()
{	// Show/Hide the children permissions of the Settings permission
	if( jQuery( this ).val() == 'none' )
	{
		jQuery( 'div#perm_options_children' ).hide();
	}
	else
	{
		jQuery( 'div#perm_options_children' ).show();
	}
} );
</script>