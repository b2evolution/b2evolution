<?php
/**
 * This file display the form to create sample users for testing
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $user_groups, $advanced_user_perms;

$Form = new Form( NULL, 'create_users', 'user', 'compact' );

$Form->global_icon( T_('Cancel!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform',  T_('Create sample users') );

	$Form->add_crumb( 'tools' );
	$Form->hidden( 'ctrl', 'tools' );
	$Form->hidden( 'action',  'create_sample_users' );
	$Form->hidden( 'tab3', get_param( 'tab3' ) );

	$GroupCache = & get_GroupCache();
	$GroupCache->load_all();
	$groups_options = array();
	foreach( $GroupCache->cache as $Group )
	{
		$groups_options[] = array( 'user_groups[]', $Group->ID, $Group->get_name(), is_null( $user_groups ) || in_array( $Group->ID, $user_groups ) );
	}
	$Form->checklist( $groups_options, 'user_groups', T_('Create new users in'), true );

	$Form->text_input( 'num_users', 10, 11, T_( 'How many users per group' ), '', array( 'maxlength' => 10, 'required' => true ) );

	/*$advanced_user_perms_array = array(
			'noperm1'   => T_('No perm'),
			'noperm2'   => T_('No perm'),
			'noperm3'   => T_('No perm'),
			'noperm4'   => T_('No perm'),
			'noperm5'   => T_('No perm'),
			'noperm6'   => T_('No perm'),
			'noperm7'   => T_('No perm'),
			'noperm8'   => T_('No perm'),
			'noperm9'   => T_('No perm'),
			'noperm10'  => T_('No perm'),
			'member'    => T_('Collection member'),
			'moderator' => T_('Collection moderator'),
			'admin'     => T_('Collection administrator'),
		);
	$advanced_user_perms_options = array();
	foreach( $advanced_user_perms_array as $advanced_perm_key => $advanced_perm_title )
	{
		$advanced_user_perms_options[] = array( 'advanced_user_perms[]', $advanced_perm_key, $advanced_perm_title, is_null( $advanced_user_perms ) || in_array( $advanced_perm_key, $advanced_user_perms ) );
	}
	$Form->checklist( $advanced_user_perms_options, 'advanced_user_perms', T_('Advanced user perms to use'), true );*/

$Form->end_form( array( array( 'submit', 'submit', T_('Create'), 'SaveButton' ) ) );

?>