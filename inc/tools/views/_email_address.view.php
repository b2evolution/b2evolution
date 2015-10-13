<?php
/**
 * This file implements the UI view for Tools > Email > Addresses
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $blog, $admin_url, $UserSettings, $email, $statuses;

param( 'email', 'string', '', true );
param( 'statuses', 'array:string', array( 'redemption', 'warning', 'suspicious3' ), true );

// Create result set:

$SQL = new SQL();
$SQL->SELECT( 'SQL_NO_CACHE emadr_ID, emadr_address, emadr_status, emadr_last_sent_ts, emadr_sent_count, emadr_sent_last_returnerror, emadr_last_error_ts, 
( emadr_prmerror_count + emadr_tmperror_count + emadr_spamerror_count + emadr_othererror_count ) AS emadr_all_count,
emadr_prmerror_count, emadr_tmperror_count, emadr_spamerror_count, emadr_othererror_count, 
COUNT( user_ID ) AS users_count' );
$SQL->FROM( 'T_email__address' );
$SQL->FROM_add( 'LEFT JOIN T_users ON user_email = emadr_address' );
$SQL->GROUP_BY( 'emadr_ID' );

$count_SQL = new SQL();
$count_SQL->SELECT( 'SQL_NO_CACHE COUNT(emadr_ID)' );
$count_SQL->FROM( 'T_email__address' );

if( !empty( $email ) )
{	// Filter by email
	$email = utf8_strtolower( $email );
	$SQL->WHERE_and( 'emadr_address LIKE '.$DB->quote( $email ) );
	$count_SQL->WHERE_and( 'emadr_address LIKE '.$DB->quote( $email ) );
}
if( !empty( $statuses ) )
{	// Filter by statuses
	$SQL->WHERE_and( 'emadr_status IN ('.$DB->quote( $statuses ).')' );
	$count_SQL->WHERE_and( 'emadr_status IN ('.$DB->quote( $statuses ).')' );
}

$Results = new Results( $SQL->get(), 'emadr_', '---D', $UserSettings->get( 'results_per_page' ), $count_SQL->get() );

$Results->title = T_('Email addresses').get_manual_link( 'email-addresses' );

$Results->global_icon( T_('Create a new email address...'), 'new', $admin_url.'?ctrl=email&amp;tab=blocked&amp;action=blocked_new', T_('Add an email address').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_email_blocked( & $Form )
{
	$Form->text_input( 'email', get_param( 'email' ), 40, T_('Email') );

	$statuses = emadr_get_status_titles();
	foreach( $statuses as $status_value => $status_title )
	{	// Display the checkboxes to filter by status
		$Form->checkbox( 'statuses[]', in_array( $status_value, get_param( 'statuses' ) ), $status_title, '', '', $status_value );
	}
}
$Results->filter_area = array(
	'callback' => 'filter_email_blocked',
	'presets' => array(
		'all'       => array( T_('All'), $admin_url.'?ctrl=email&amp;tab=blocked&amp;statuses[]=unknown&amp;statuses[]=redemption&amp;statuses[]=warning&amp;statuses[]=suspicious1&amp;statuses[]=suspicious2&amp;statuses[]=suspicious3&amp;statuses[]=prmerror&amp;statuses[]=spammer'),
		'errors'    => array( T_('Errors'), $admin_url.'?ctrl=email&amp;tab=blocked&amp;statuses[]=warning&amp;statuses[]=suspicious1&amp;statuses[]=suspicious2&amp;statuses[]=suspicious3&amp;statuses[]=prmerror&amp;statuses[]=spammer'),
		'attention' => array( T_('Need Attention'), $admin_url.'?ctrl=email&amp;tab=blocked&amp;statuses[]=redemption&amp;statuses[]=warning&amp;statuses[]=suspicious3'),
		)
	);

/**
 * Decode the special symbols
 * It used to avoid an error, because a symbol '%' is special symbol of class Result to parse the functions
 *
 * @param string Url
 * @param string Url
 */
function url_decode_special_symbols( $url )
{
	return str_replace(
			array( '%5B', '%5D' ),
			array( '[',   ']' ),
			$url
		);
}
$Results->cols[] = array(
		'th' => T_('ID'),
		'order' => 'emadr_ID',
		'th_class' => 'shrinkwrap',
		'td_class' => 'right',
		'td' => '$emadr_ID$',
	);

$Results->cols[] = array(
		'th' => T_('Address'),
		'order' => 'emadr_address',
		'td' => '<a href="'.url_decode_special_symbols( regenerate_url( 'email,action,emadr_ID', 'email=$emadr_address$' ) ).'">$emadr_address$</a>',
		'th_class' => 'shrinkwrap',
	);

$Results->cols[] = array(
		'th' => T_('Status'),
		'order' => 'emadr_status',
		'td' => '$emadr_status$',
		'td' => /* Check permission: */$current_User->check_perm( 'emails', 'edit' ) ?
			/* Current user can edit emails */'<a href="#" rel="$emadr_status$">%emadr_get_status_title( #emadr_status# )%</a>' :
			/* No edit, only view the status */'%emadr_get_status_title( #emadr_status# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap emadr_status_edit',
		'extra' => array ( 'style' => 'background-color: %emadr_get_status_color( "#emadr_status#" )%;', 'format_to_output' => false )
	);

$Results->cols[] = array(
		'th_group' => T_('Send messages'),
		'th' => T_('Last sent date'),
		'order' => 'emadr_last_sent_ts',
		'default_dir' => 'D',
		'td_class' => 'timestamp',
		'td' => '%mysql2localedatetime_spans( #emadr_last_sent_ts#, "M-d" )%',
	);

$Results->cols[] = array(
		'th_group' => T_('Send messages'),
		'th' => T_('Sent count'),
		'order' => 'emadr_sent_count',
		'default_dir' => 'D',
		'td' => '<a href="'.$admin_url.'?ctrl=email&amp;tab=sent&amp;filter=new&amp;email=$emadr_address$">$emadr_sent_count$</a>',
		'td_class' => 'right'
	);

$Results->cols[] = array(
		'th_group' => T_('Send messages'),
		'th' => T_('Since last error'),
		'order' => 'emadr_sent_last_returnerror',
		'default_dir' => 'D',
		'td' => '$emadr_sent_last_returnerror$',
		'td_class' => 'right'
	);

$Results->cols[] = array(
		'th_group' => T_('Returned error messages'),
		'th' => T_('Last error date'),
		'order' => 'emadr_last_error_ts',
		'default_dir' => 'D',
		'td_class' => 'timestamp',
		'td' => '%mysql2localedatetime_spans( #emadr_last_error_ts#, "M-d" )%',
	);

$Results->cols[] = array(
		'th_group' => T_('Returned error messages'),
		'th' => T_('Count'),
		'order' => 'emadr_all_count',
		'default_dir' => 'D',
		'td' => '$emadr_all_count$',
		'td_class' => 'right'
	);

$Results->cols[] = array(
		'th_group' => T_('Returned error messages'),
		'th' => T_('Permanent errors'),
		'order' => 'emadr_prmerror_count',
		'default_dir' => 'D',
		'td' => '$emadr_prmerror_count$',
		'td_class' => 'right'
	);

$Results->cols[] = array(
		'th_group' => T_('Returned error messages'),
		'th' => T_('Temporary errors'),
		'order' => 'emadr_tmperror_count',
		'default_dir' => 'D',
		'td' => '$emadr_tmperror_count$',
		'td_class' => 'right'
	);

$Results->cols[] = array(
		'th_group' => T_('Returned error messages'),
		'th' => T_('Spam errors'),
		'order' => 'emadr_spamerror_count',
		'default_dir' => 'D',
		'td' => '$emadr_spamerror_count$',
		'td_class' => 'right'
	);

$Results->cols[] = array(
		'th_group' => T_('Returned error messages'),
		'th' => T_('Other errors'),
		'order' => 'emadr_othererror_count',
		'default_dir' => 'D',
		'td' => '$emadr_othererror_count$',
		'td_class' => 'right'
	);

$Results->cols[] = array(
		'th' => T_('Users'),
		'order' => 'users_count',
		'default_dir' => 'D',
		'td' => '<a href="'.$admin_url.'?ctrl=users&amp;filter=new&amp;keywords=$emadr_address$" title="'.format_to_output( T_('Go to users list with this email address'), 'htmlattr' ).'">$users_count$</a>',
		'td_class' => 'right'
	);

$Results->cols[] = array(
		'th' => T_('Actions'),
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'td' => action_icon( T_('Filter the returned emails by this email address...'), 'magnifier', $admin_url.'?ctrl=email&amp;tab=return&amp;email=$emadr_address$' )
			.action_icon( T_('Edit this email address...'), 'properties', $admin_url.'?ctrl=email&amp;tab=blocked&amp;emadr_ID=$emadr_ID$' )
			.action_icon( T_('Delete this email address!'), 'delete', url_decode_special_symbols( regenerate_url( 'emadr_ID,action', 'emadr_ID=$emadr_ID$&amp;action=blocked_delete&amp;'.url_crumb('email_blocked') ) ) )
	);

// Display results:
$Results->display();

if( $current_User->check_perm( 'emails', 'edit' ) )
{ // Check permission to edit emails:
	// Print JS to edit an email status
	echo_editable_column_js( array(
		'column_selector' => '.emadr_status_edit',
		'ajax_url'        => get_secure_htsrv_url().'async.php?action=emadr_status_edit&'.url_crumb( 'emadrstatus' ),
		'options'         => emadr_get_status_titles(),
		'new_field_name'  => 'new_status',
		'ID_value'        => 'jQuery( ":first", jQuery( this ).parent() ).text()',
		'ID_name'         => 'emadr_ID',
		'colored_cells'   => true ) );
}

?>