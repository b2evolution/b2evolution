<?php
/**
 * This file implements the UI view for Emails > Newsletters
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $admin_url, $UserSettings;

// Create result set:
$SQL = new SQL();
$SQL->SELECT( 'enlt_ID, enlt_name, enlt_label, enlt_active, enlt_order,
		SUM( IF( enls_subscribed = 1, 1, 0 ) ) AS subscribed,
		SUM( IF( enls_subscribed = 0, 1, 0 ) ) AS unsubscribed' );
$SQL->FROM( 'T_email__newsletter' );
$SQL->FROM_add( 'LEFT JOIN T_email__newsletter_subscription ON enls_enlt_ID = enlt_ID' );
$SQL->GROUP_BY( 'enlt_ID, enlt_name, enlt_label, enlt_active, enlt_order' );

$count_SQL = new SQL();
$count_SQL->SELECT( 'COUNT(enlt_ID)' );
$count_SQL->FROM( 'T_email__newsletter' );

$Results = new Results( $SQL->get(), 'enlt_', 'A', NULL, $count_SQL->get() );

$Results->title = T_('Lists').get_manual_link( 'email-lists' );

if( $current_User->check_perm( 'emails', 'edit' ) )
{	// Display a button to add newsletter if current User has a perm:
	$Results->global_icon( T_('Create new list').'...', 'new', $admin_url.'?ctrl=newsletters&amp;action=new', T_('Create new list').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
}

$Results->cols[] = array(
		'th' => T_('ID'),
		'order' => 'enlt_ID',
		'th_class' => 'shrinkwrap',
		'td_class' => 'right',
		'td' => '$enlt_ID$',
	);

function newsletters_td_active( $enlt_ID, $enlt_active )
{
	global $current_User;

	if( $enlt_active )
	{	// If newsletter is active:
		$active_icon = get_icon( 'bullet_green', 'imgtag', array( 'title' => T_('The list is active.') ) );
	}
	else
	{	// If newsletter is NOT active:
		$active_icon = get_icon( 'bullet_empty_grey', 'imgtag', array( 'title' => T_('The list is not active.') ) );
	}

	if( $current_User->check_perm( 'emails', 'edit' ) )
	{	// Make icon to action link if current User has a perm to edit this:
		global $admin_url;
		return '<a href="'.$admin_url.'?ctrl=newsletters&amp;action='.( $enlt_active ? 'disactivate' : 'activate' )
			.'&amp;enlt_ID='.$enlt_ID.'&amp;'.url_crumb( 'newsletter' ).'">'.$active_icon.'</a>';
	}
	else
	{	// Simple icon without link:
		return $active_icon;
	}
}
$Results->cols[] = array(
		'th' => T_('Active'),
		'order' => 'enlt_active',
		'td' => '%newsletters_td_active( #enlt_ID#, #enlt_active# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
	);

$Results->cols[] = array(
		'th' => T_('Name'),
		'order' => 'enlt_name',
		'td' => '<a href="'.$admin_url.'?ctrl=newsletters&amp;action=edit&amp;enlt_ID=$enlt_ID$"><b>$enlt_name$</b></a>',
		'th_class' => 'shrinkwrap',
		'td_class' => 'nowrap',
	);

$Results->cols[] = array(
		'th' => T_('Label'),
		'order' => 'enlt_label',
		'td' => '$enlt_label$',
	);

$Results->cols[] = array(
		'th' => T_('Order'),
		'order' => 'enlt_order',
		'td' => '$enlt_order$',
		'th_class' => 'shrinkwrap',
		'td_class' => 'right',
	);

/*
 * Get a state of enabling newsletter by default for new registered users
 */
function newsletters_td_new_users( $enlt_ID )
{
	global $Settings;

	$def_newsletters = explode( ',', $Settings->get( 'def_newsletters' ) );

	if( in_array( $enlt_ID, $def_newsletters ) )
	{
		$title = T_('Auto-subscribe new users to this list.');
		$icon = 'bullet_full';
		$action = 'disable';
	}
	else
	{
		$title = T_('Do NOT auto-subscribe new users to this list.');
		$icon = 'bullet_empty';
		$action = 'enable';
	}

	return action_icon( $title, $icon,
		regenerate_url( 'ctrl,action', 'ctrl=newsletters&amp;action='.$action
			.'&amp;enlt_ID='.$enlt_ID
			.'&amp;'.url_crumb( 'newsletter' ) ) );
}
$Results->cols[] = array(
		'th' => T_('New users'),
		'order' => 'enlt_ord',
		'td' => '%newsletters_td_new_users( #enlt_ID# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'center',
	);

$Results->cols[] = array(
		'th' => T_('Subscribed'),
		'order' => 'subscribed',
		'default_dir' => 'D',
		'th_class' => 'shrinkwrap',
		'td_class' => 'center',
		'td' =>'$subscribed$'
	);

$Results->cols[] = array(
		'th' => T_('Unsubscribed'),
		'order' => 'unsubscribed',
		'default_dir' => 'D',
		'th_class' => 'shrinkwrap',
		'td_class' => 'center',
		'td' =>'$unsubscribed$'
	);

$Results->cols[] = array(
		'th' => T_('Actions'),
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'td' => action_icon( T_('Edit this list...'), 'properties', $admin_url.'?ctrl=newsletters&amp;action=edit&amp;enlt_ID=$enlt_ID$' )
			.( $current_User->check_perm( 'emails', 'edit' ) ?
			// Display an action icon to delete newsletter if current User has a perm:
			action_icon( T_('Delete this list!'), 'delete', regenerate_url( 'enlt_ID,action', 'enlt_ID=$enlt_ID$&amp;action=delete&amp;'.url_crumb( 'newsletter' ) ) ): '' )
	);

// Display results:
$Results->display();

?>