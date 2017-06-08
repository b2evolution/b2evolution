<?php
/**
 * This file implements the UI view for Emails > Campaigns
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
$SQL->SELECT( 'SQL_NO_CACHE ecmp_ID, ecmp_date_ts, ecmp_name, ecmp_email_title, ecmp_sent_ts' );
$SQL->FROM( 'T_email__campaign' );
$SQL->GROUP_BY( 'ecmp_ID' );

$count_SQL = new SQL();
$count_SQL->SELECT( 'SQL_NO_CACHE COUNT( ecmp_ID )' );
$count_SQL->FROM( 'T_email__campaign' );

$Results = new Results( $SQL->get(), 'emcmp_', 'D', $UserSettings->get( 'results_per_page' ), $count_SQL->get() );

$Results->title = T_('Email campaigns').get_manual_link( 'email-campaigns' );

if( $current_User->check_perm( 'emails', 'edit' ) )
{ // User must has a permission to edit emails
	$Results->global_icon( T_('Create new campaign').'...', 'new', $admin_url.'?ctrl=campaigns&amp;action=new', T_('Create new campaign').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
}

$Results->cols[] = array(
		'th' => T_('ID'),
		'order' => 'ecmp_ID',
		'th_class' => 'shrinkwrap',
		'td_class' => 'right',
		'td' => '$ecmp_ID$',
	);

$Results->cols[] = array(
		'th' => T_('Date'),
		'order' => 'ecmp_date_ts',
		'default_dir' => 'D',
		'th_class' => 'shrinkwrap',
		'td_class' => 'timestamp compact_data',
		'td' => '%mysql2localedatetime_spans( #ecmp_date_ts# )%',
	);

$Results->cols[] = array(
		'th' => T_('Name'),
		'order' => 'ecmp_name',
		'td' => '<a href="'.$admin_url.'?ctrl=campaigns&amp;action=edit&amp;ecmp_ID=$ecmp_ID$"><b>$ecmp_name$</b></a>',
		'th_class' => 'shrinkwrap',
		'td_class' => 'nowrap',
	);

$Results->cols[] = array(
		'th' => T_('Email title'),
		'order' => 'ecmp_email_title',
		'td' => '$ecmp_email_title$',
	);

$Results->cols[] = array(
		'th' => T_('Sent'),
		'order' => 'ecmp_sent_ts',
		'default_dir' => 'D',
		'th_class' => 'shrinkwrap',
		'td_class' => 'timestamp compact_data',
		'td' => '%mysql2localedatetime_spans( #ecmp_sent_ts# )%',
	);

$Results->cols[] = array(
		'th' => T_('Actions'),
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'td' => action_icon( T_('Edit this email campaign...'), 'properties', $admin_url.'?ctrl=campaigns&amp;action=edit&amp;ecmp_ID=$ecmp_ID$' )
			.action_icon( T_('Delete this email address!'), 'delete', regenerate_url( 'ecmp_ID,action', 'ecmp_ID=$ecmp_ID$&amp;action=delete&amp;'.url_crumb('campaign') ) )
	);

// Display results:
$Results->display();

?>