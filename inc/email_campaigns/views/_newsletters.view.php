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
$SQL->SELECT( 'SQL_NO_CACHE enlt_ID, enlt_name, enlt_label, enlt_active' );
$SQL->FROM( 'T_email__newsletter' );

$Results = new Results( $SQL->get(), 'enlt_', 'A' );

$Results->title = T_('Newsletters').get_manual_link( 'email-newsletters' );

if( $current_User->check_perm( 'emails', 'edit' ) )
{	// Display a button to add newsletter if current User has a perm:
	$Results->global_icon( T_('Create new newsletter').'...', 'new', $admin_url.'?ctrl=newsletters&amp;action=new', T_('Create new newsletter').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
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
		$active_icon = get_icon( 'bullet_green', 'imgtag', array( 'title' => T_('The newsletter is active.') ) );
	}
	else
	{	// If newsletter is NOT active:
		$active_icon = get_icon( 'bullet_empty_grey', 'imgtag', array( 'title' => T_('The newsletter is not active.') ) );
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
		'th' => T_('Actions'),
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'td' => action_icon( T_('Edit this newsletter...'), 'properties', $admin_url.'?ctrl=newsletters&amp;action=edit&amp;enlt_ID=$enlt_ID$' )
			.( $current_User->check_perm( 'emails', 'edit' ) ?
			// Display an action icon to delete newsletter if current User has a perm:
			action_icon( T_('Delete this newsletter!'), 'delete', regenerate_url( 'enlt_ID,action', 'enlt_ID=$enlt_ID$&amp;action=delete&amp;'.url_crumb( 'newsletter' ) ) ): '' )
	);

// Display results:
$Results->display();

?>