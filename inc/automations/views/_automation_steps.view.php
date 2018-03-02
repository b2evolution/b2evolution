<?php
/**
 * This file display the automation form
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $edited_Automation, $admin_url;

$finished_SQL = new SQL( 'Get a count of finished users of automation #'.$edited_Automation->ID );
$finished_SQL->SELECT( 'COUNT( aust_user_ID )' );
$finished_SQL->FROM( 'T_automation__user_state' );
$finished_SQL->WHERE( 'aust_autm_ID = '.$edited_Automation->ID );
$finished_SQL->WHERE_and( 'aust_next_step_ID IS NULL' );
$finished_users = $DB->get_var( $finished_SQL );

$SQL = new SQL( 'Get all steps of automation #'.$edited_Automation->ID );
$SQL->SELECT( 'step.*' );
$SQL->SELECT_add( ', next_yes.step_order AS step_yes_next_step_order, next_no.step_order AS step_no_next_step_order, next_error.step_order AS step_error_next_step_order' );
$SQL->SELECT_add( ', COUNT( aust_next_step_ID ) AS num_users_queued' );
$SQL->SELECT_add( ', IF( ( SELECT MIN( step_order ) FROM T_automation__step WHERE step_autm_ID = '.$edited_Automation->ID.' ) = step.step_order, 1, 0 ) AS is_first_step' );
$SQL->SELECT_add( ', IF( ( SELECT MAX( step_order ) FROM T_automation__step WHERE step_autm_ID = '.$edited_Automation->ID.' ) = step.step_order, 1, 0 ) AS is_last_step' );
$SQL->FROM( 'T_automation__step AS step' );
$SQL->FROM_add( 'LEFT JOIN T_automation__step AS next_yes ON next_yes.step_ID = step.step_yes_next_step_ID' );
$SQL->FROM_add( 'LEFT JOIN T_automation__step AS next_no ON next_no.step_ID = step.step_no_next_step_ID' );
$SQL->FROM_add( 'LEFT JOIN T_automation__step AS next_error ON next_error.step_ID = step.step_error_next_step_ID' );
$SQL->FROM_add( 'LEFT JOIN T_automation__user_state ON step.step_ID = aust_next_step_ID' );
$SQL->WHERE( 'step.step_autm_ID = '.$edited_Automation->ID );
$SQL->GROUP_BY( 'step.step_ID' );

$count_SQL = new SQL( 'Get number of steps of automation #'.$edited_Automation->ID );
$count_SQL->SELECT( 'COUNT( step_ID )' );
$count_SQL->FROM( 'T_automation__step' );
$count_SQL->WHERE( 'step_autm_ID = '.$edited_Automation->ID );

$Results = new Results( $SQL->get(), 'step_', 'A', NULL, $count_SQL->get() );

if( $edited_Automation->get( 'status' ) == 'active' )
{	// Set status text and button of the Automation for title:
	$automation_status_title = ' <span class="red">('.T_('RUNNING').')</span>';
	$Results->global_icon( T_('Pause'), 'pause', regenerate_url( 'action', 'action=status_paused&amp;'.url_crumb( 'automation' ) ), T_('Pause'), 3, 4, array( 'class' => 'action_icon btn-danger' ) );
}
else
{	// Set status text and button of the Automation for title:
	$automation_status_title = ' <span class="orange">('.T_('PAUSED').')</span>';
	$Results->global_icon( T_('Play'), 'play', regenerate_url( 'action', 'action=status_active&amp;'.url_crumb( 'automation' ) ), T_('Play'), 3, 4, array( 'class' => 'action_icon btn-success' ) );
}

$Results->global_icon( T_('New step'), 'new', regenerate_url( 'action', 'action=new_step' ), T_('New step').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );

$Results->title = T_('Steps').$automation_status_title.get_manual_link( 'automation-steps' );

$Results->cols[] = array(
		'th'       => T_('Step'),
		'order'    => 'step_order',
		'td'       => '$step_order$',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'total'    => T_('Finished'),
	);

$Results->cols[] = array(
		'th'          => T_('# of users queued'),
		'order'       => 'num_users_queued',
		'default_dir' => 'D',
		'td'          => '%step_td_num_users_queued( #step_ID#, #step_autm_ID#, #num_users_queued#, #step_order# )%',
		'th_class'    => 'shrinkwrap',
		'td_class'    => 'right',
		'total'       => ( $finished_users > 0
				? '<a href="'.$admin_url.'?ctrl=automations&amp;action=edit&amp;tab=users&amp;autm_ID='.$edited_Automation->ID.'&amp;step=finished">'.$finished_users.'</a> '.
				  '<a href="#" class="btn btn-info btn-xs" onclick="return requeue_automation( '.$edited_Automation->ID.' )">'.T_('Requeue').'</a>'
				: '0' ),
		'total_class' => 'right',
	);

$Results->cols[] = array(
		'th'    => T_('Label'),
		'order' => 'step_label',
		'td'    => '%step_td_label( #step_ID#, #step_label#, #step_type#, #step_info# )%',
		'total' => T_('These users have finished the current automation (STOP state)'),
	);

$Results->cols[] = array(
		'th_group' => T_('Next'),
		'th'       => T_('Yes'),
		'order'    => 'step_label',
		'td'       => '%step_td_next_step( #step_ID#,  #step_yes_next_step_ID#, #step_yes_next_step_order#, #step_yes_next_step_delay# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'nowrap',
	);

$Results->cols[] = array(
		'th_group' => T_('Next'),
		'th'       => T_('No'),
		'order'    => 'step_label',
		'td'       => '%step_td_next_step( #step_ID#, #step_no_next_step_ID#, #step_no_next_step_order#, #step_no_next_step_delay# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'nowrap',
	);

$Results->cols[] = array(
		'th_group' => T_('Next'),
		'th'       => T_('Error'),
		'order'    => 'step_label',
		'td'       => '%step_td_next_step( #step_ID#, #step_error_next_step_ID#, #step_error_next_step_order#, #step_error_next_step_delay# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'nowrap',
	);

$Results->cols[] = array(
		'th'       => T_('Actions'),
		'td'       => '%step_td_actions( #step_ID#, #is_first_step#, #is_last_step# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
	);

$Results->display( NULL, 'session' );

// Init JS for form to requeue automation:
echo_requeue_automation_js();

// Display date/time when next scheduled job will executes automations:
$SQL = new SQL( 'Get next scheduled job for executing automations' );
$SQL->SELECT( 'ctsk_start_datetime' );
$SQL->FROM( 'T_cron__task' );
$SQL->FROM_add( 'LEFT JOIN T_cron__log ON ctsk_ID = clog_ctsk_ID' );
$SQL->WHERE( 'ctsk_key = "execute-automations"' );
$SQL->WHERE_and( 'clog_ctsk_ID IS NULL' );
$SQL->ORDER_BY( 'ctsk_start_datetime ASC, ctsk_ID ASC' );
$SQL->LIMIT( 1 );
$next_automations_date_time = $DB->get_var( $SQL );
echo '<p class="note">'.sprintf( T_('Next scheduled job for executing automations: %s'), $next_automations_date_time ? mysql2localedatetime( $next_automations_date_time ) : T_('Unknown') ).'</p>';
?>