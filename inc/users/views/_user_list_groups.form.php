<?php
/**
 * This file implements the UI view to change user group membership from users list.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $admin_url;

$Form = new Form( NULL, 'users_groups_checkchanges' );

$Form->switch_template_parts( array(
		'labelclass' => 'control-label col-sm-6',
		'inputstart' => '<div class="controls col-sm-6">',
		'inputstart_radio' => '<div class="controls col-sm-6">',
		'infostart'  => '<div class="controls col-sm-6"><div class="form-control-static">',
	) );

$Form->title_fmt = '<span style="float:right">$global_icons$</span><div>$title$</div>'."\n";

$Form->begin_form( 'fform' );

$Form->add_crumb( 'users' );
$Form->hidden_ctrl();

// A link to close popup window:
$close_icon = action_icon( T_('Close this window'), 'close', '', '', 0, 0, array( 'id' => 'close_button', 'class' => 'floatright' ) );

$Form->begin_fieldset( T_('Change groups...').get_manual_link( 'userlist-change-group' ).$close_icon );

	// Primary Group:
	$GroupCache = & get_GroupCache();
	$group_where_sql = '';
	if( ! $current_User->check_perm( 'users', 'edit' ) )
	{	// Show the limited list for moderators:
		$group_where_sql = 'grp_level < '.$current_User->get_Group()->get( 'level' );
	}
	$GroupCache->clear();
	$GroupCache->load_where( ( empty( $group_where_sql ) ? '' : $group_where_sql.' AND ' ).' ( grp_usage = "primary" )' );
	$GroupCache->all_loaded = true;
	$GroupCache->none_option_text = T_('Do not change primary group');
	$Form->select_object( 'grp_ID', NULL, $GroupCache, T_('New Primary group'), '', true );

	$GroupCache->none_option_text = T_('None');
	$GroupCache->clear();
	$GroupCache->load_where( ( empty( $group_where_sql ) ? '' : $group_where_sql.' AND ' ).' ( grp_usage = "secondary" )' );
	$GroupCache->all_loaded = true;

	// Add secondary group:
	$Form->select_object( 'add_secondary_grp_ID', NULL, $GroupCache, T_('Add secondary group'), '', true );

	// Remove secondary group:
	$Form->select_object( 'remove_secondary_grp_ID', NULL, $GroupCache, T_('Remove secondary group'), '', true );

$Form->end_fieldset();

$Form->button( array( '', 'actionArray[update_groups]', T_('Make changes now!'), 'SaveButton' ) );

$Form->end_form();
?>