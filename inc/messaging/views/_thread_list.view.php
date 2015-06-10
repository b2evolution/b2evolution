<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @package messaging
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $current_User;
global $unread_messages_count;
global $DB, $Blog;
global $perm_abuse_management; // TRUE if we go from Abuse Management

if( !isset( $display_params ) )
{ // init display_params
	$display_params = array();
}
// set default values
$display_params = array_merge( array(
	'show_only_date' => 0,
	), $display_params );

// Create result set:
$Results = get_threads_results( array(
		'results_param_prefix' => $perm_abuse_management ? 'abuse_' : 'thrd_',
		'search_word' => param( 's', 'string', '', true ),
		'search_user' => param( 'u', 'string', '', true ),
		'show_closed_threads' => param( 'show_closed', 'boolean', NULL, true ),
	) );

$Results->Cache = & get_ThreadCache();

$Results->title = T_('Conversations list');
if( is_admin_page() )
{
	$Results->title .= get_manual_link( 'messaging' );
}

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_recipients( & $Form )
{
	global $perm_abuse_management;
	$Form->text( 's', get_param('s'), 20, T_('Search'), '', 255 );
	$Form->text( 'u', get_param('u'), 10, T_('User'), '', 255 );
	if( !$perm_abuse_management )
	{
		$Form->checkbox( 'show_closed', get_param('show_closed'), T_( 'Show closed conversations' ) );
	}
}

if( $perm_abuse_management )
{ // In case of abuse management
	$preset_filters = array( 'all' => array( T_('All'), get_dispctrl_url( 'abuse' ) ) );
}
else
{ // In case of simple thread list view
	$preset_filters = array(
		'avtive' => array( T_('Active conversations'), get_dispctrl_url( 'threads', 'show_closed=0' ) ),
		'all' => array( T_('All conversations'), get_dispctrl_url( 'threads', 'show_closed=1' ) )
	);
}

$Results->filter_area = array(
	'callback' => 'filter_recipients',
	'presets' => $preset_filters,
	);

// Initialize Results object
threads_results( $Results, array_merge( array(
		'abuse_management' => (int)$perm_abuse_management,
	), $display_params ) );

if( ! $perm_abuse_management )
{ // Show link to create a new conversation
	$Results->global_icon( T_('See My Contacts'), 'contacts', get_dispctrl_url( 'contacts' ), T_('See My Contacts').' ', 3, 4 );
	$Results->global_icon( T_('Create a new conversation...'), 'compose_new', get_dispctrl_url( 'threads', 'action=new' ), T_('Compose new').' &raquo;', 3, 4 );
}

$Results->display( $display_params );

?>