<?php
/**
 * This file display the form to create sample users for testing
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Settings, $GroupCache;

$Form = new Form( NULL, 'create_users', 'user', 'compact' );

$Form->global_icon( T_('Cancel!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform',  T_('Create sample users') );

	$Form->add_crumb( 'tools' );
	$Form->hidden( 'ctrl', 'tools' );
	$Form->hidden( 'action',  'create_sample_users' );
	$Form->hidden( 'tab3', get_param( 'tab3' ) );

	$Form->select_object( 'group_ID', $Settings->get('newusers_grp_ID'), $GroupCache, T_('Group for new users') );
	$Form->text_input( 'num_users', 10, 11, T_( 'How many users' ), '', array( 'maxlength' => 10, 'required' => true ) );

$Form->end_form( array( array( 'submit', 'submit', T_('Create'), 'SaveButton' ) ) );

?>