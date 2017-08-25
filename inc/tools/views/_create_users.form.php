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

global $num_users, $user_groups, $advanced_user_perms;

$Form = new Form( NULL, 'create_users', 'user', 'compact' );

$Form->global_icon( T_('Cancel').'!', 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform',  T_('Create sample users') );

	$Form->add_crumb( 'tools' );
	$Form->hidden( 'ctrl', 'tools' );
	$Form->hidden( 'action',  'create_sample_users' );
	$Form->hidden( 'tab3', get_param( 'tab3' ) );

	$Form->text_input( 'num_users', ( is_null( $num_users ) ? 1000 : $num_users ), 11, T_( 'How many users' ), '', array( 'maxlength' => 10, 'required' => true ) );

	$GroupCache = & get_GroupCache();
	$GroupCache->load_all();
	$groups_options = array();
	foreach( $GroupCache->cache as $Group )
	{
		$groups_options[] = array( 'user_groups[]', $Group->ID, $Group->name, is_null( $user_groups ) || in_array( $Group->ID, $user_groups ), 0,
			$Group->check_perm( 'perm_getblog', 'allowed' ) ?
				T_('Users of this group automatically get a new collection') :
				T_('Users of this group do NOT automatically get a new collection') );
	}
	$Form->checklist( $groups_options, 'user_groups', T_('Create new users in'), true, false,
		array( 'note' => T_('Note: For each user it creates, the tool will randomly select between the allowed (checked) options above') ) );

	$advanced_user_perms_array = array(
			'noperm1'   => T_('No perm on existing collection'),
			'noperm2'   => T_('No perm on existing collection'),
			'noperm3'   => T_('No perm on existing collection'),
			'noperm4'   => T_('No perm on existing collection'),
			'noperm5'   => T_('No perm on existing collection'),
			'noperm6'   => T_('No perm on existing collection'),
			'noperm7'   => T_('No perm on existing collection'),
			'noperm8'   => T_('No perm on existing collection'),
			'noperm9'   => T_('No perm on existing collection'),
			'noperm10'  => T_('No perm on existing collection'),
			'member'    => T_('Member of existing collection'),
			'moderator' => T_('Moderator of existing collection'),
			'admin'     => T_('Administrator of existing collection'),
		);
	$advanced_user_perms_options = array();
	foreach( $advanced_user_perms_array as $advanced_perm_key => $advanced_perm_title )
	{
		$advanced_user_perms_options[] = array( 'advanced_user_perms[]', $advanced_perm_key, $advanced_perm_title, is_null( $advanced_user_perms ) || in_array( $advanced_perm_key, $advanced_user_perms ) );
	}
	$Form->checklist( $advanced_user_perms_options, 'advanced_user_perms', T_('Advanced user perms to grant on existing collections with advanced perms'), true, false,
		array( 'note' => T_('Note: For each new user/existing collection combination, the tool will randomly select between the allowed (checked) options above') ) );

$Form->end_form( array( array( 'submit', 'submit', T_('Create'), 'SaveButton' ) ) );

?>