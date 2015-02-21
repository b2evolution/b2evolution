<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009-2014 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * {@internal Open Source relicensing agreement:
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package messaging
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-maxim: Evo Factory / Maxim.
 * @author fplanque: Francois Planque.
 *
 * @version $Id: _thread_list.view.php 8214 2015-02-10 10:17:40Z yura $
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

if( $unread_messages_count > 0 && !$perm_abuse_management )
{
	$Results->title = $Results->title.' <span class="badge badge-important">'.$unread_messages_count.'</span></b>';
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
{	// Show link to create a new conversation
	if( is_admin_page() )
	{
		$newmsg_url = regenerate_url( 'action', 'action=new' );
	}
	else
	{
		$newmsg_url = regenerate_url( 'disp', 'disp=threads&action=new' );
	}

	$Results->global_icon( T_('See My Contacts'), '', get_dispctrl_url( 'contacts' ), T_('See My Contacts').' ', 3, 4 );
	$Results->global_icon( T_('Create a new conversation...'), 'compose_new', $newmsg_url, T_('Compose new').' &raquo;', 3, 4 );
}

$Results->display( $display_params );

?>