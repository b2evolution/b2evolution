<?php
/**
 * This file implements automation functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Display breadcrumb for automation controller
 */
function autm_display_breadcrumb()
{
	global $admin_url, $edited_Automation, $edited_AutomationStep;

	echo '<nav aria-label="breadcrumb"><ol class="breadcrumb" style="margin-left:0">';
	echo '<li class="breadcrumb-item"><a href="'.$admin_url.'?ctrl=automations">All</a></li>';
	if( isset( $edited_Automation ) && $edited_Automation->ID > 0 )
	{	// Automation:
		echo '<li class="breadcrumb-item active">'.$edited_Automation->dget( 'name' ).'</li>';
	}
	if( isset( $edited_AutomationStep ) && $edited_AutomationStep->ID > 0 )
	{	// Automation step:
		$step_Automation = & $edited_AutomationStep->get_Automation();
		echo '<li class="breadcrumb-item"><a href="'.$admin_url.'?ctrl=automations&amp;action=edit&amp;autm_ID='.$step_Automation->ID.'">'.$step_Automation->dget( 'name' ).'</a></li>';
		echo '<li class="breadcrumb-item active">'.T_('Step').' #'.$edited_AutomationStep->dget( 'order' ).'</li>';
	}
	echo '</ol></nav>';
}


/**
 * Get array of status titles for automation
 *
 * @return array Status titles
 */
function autm_get_status_titles()
{
	return array(
		'paused' => T_('Paused'),
		'active' => T_('Active'),
	);
}


/**
 * Get status title of automation by status value
 *
 * @param string Status value
 * @return string Status title
 */
function autm_get_status_title( $status )
{
	$statuses = autm_get_status_titles();

	return isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status;
}


/**
 * Helper function to display automation auto start on Results table
 *
 * @param integer Automation ID
 * @param string Automation auto start
 * @param string Additional URL params
 * @return string
 */
function autm_td_autostart( $autm_ID, $autm_autostart, $url_params = '' )
{
	global $current_User;

	$autostart_icon = get_icon( $autm_autostart ? 'bullet_black' : 'bullet_empty_grey' );

	if( $autm_autostart )
	{	// If automation is auto started:
		$autostart_icon = get_icon( 'bullet_black', 'imgtag', array( 'title' => T_('The automation is auto started.') ) );
	}
	else
	{	// If automation is NOT auto started:
		$autostart_icon = get_icon( 'bullet_empty_grey', 'imgtag', array( 'title' => T_('The automation is not auto started.') ) );
	}

	if( $current_User->check_perm( 'options', 'edit' ) )
	{	// Make icon to action link if current User has a perm to edit this:
		global $admin_url;
		return '<a href="'.$admin_url.'?ctrl=automations&amp;action='.( $autm_autostart ? 'autostart_disable' : 'autostart_enable' )
			.'&amp;autm_ID='.$autm_ID.'&amp;'.url_crumb( 'automation' ).$url_params.'">'.$autostart_icon.'</a>';
	}
	else
	{	// Simple icon without link:
		return $autostart_icon;
	}
}


/**
 * Helper function to display automation status on Results table
 *
 * @param integer Automation ID
 * @param string Automation status
 * @param string Additional URL params
 * @return string
 */
function autm_td_status( $autm_ID, $autm_status, $url_params = '' )
{
	global $admin_url, $current_User;

	$r = autm_get_status_title( $autm_status );

	if( is_logged_in() && $current_User->check_perm( 'options', 'edit' ) )
	{	// Display action icon to toggle automation status:
		$r .= ' '.action_icon( '', ( $autm_status == 'active' ? 'pause' : 'play' ),
			$admin_url.'?ctrl=automations&amp;action='.( $autm_status == 'active' ? 'status_paused' : 'status_active' )
				.'&amp;autm_ID='.$autm_ID.'&amp;'.url_crumb( 'automation' ).$url_params );
	}

	return $r;
}


/**
 * Get array of type titles for automation step
 *
 * @return array Type titles
 */
function step_get_type_titles()
{
	return array(
		'if_condition'   => T_('IF Condition'),
		'send_campaign'  => T_('Send Campaign'),
		'notify_owner'   => T_('Notify owner'),
		'add_usertag'    => T_('Add Usertag'),
		'remove_usertag' => T_('Remove Usertag'),
		'subscribe'      => T_('Subscribe User to List'),
		'unsubscribe'    => T_('Unsubscribe User from List'),
	);
}


/**
 * Get type title of automation step by type value
 *
 * @param string Type value
 * @return string Type title
 */
function step_get_type_title( $type )
{
	$types = step_get_type_titles();

	return isset( $types[ $type ] ) ? $types[ $type ] : $type;
}


/**
 * Get array of result titles for automation step
 *
 * @return array Result titles per step type
 */
function step_get_result_titles()
{
	return array(
		'if_condition' => array(
			'YES'   => 'YES',
			'NO'    => 'NO',
			'ERROR' => 'ERROR: %s',
		),
		'send_campaign' => array(
			'YES'   => 'Email SENT',
			'NO'    => 'Email was ALREADY sent',
			'ERROR' => 'ERROR: Email cannot be sent: %s',
		),
		'notify_owner' => array(
			'YES'   => 'Notification SENT',
			'NO'    => '',
			'ERROR' => 'ERROR: Notification cannot be sent: %s',
		),
		'add_usertag' => array(
			'YES'   => 'Tag %s was added',
			'NO'    => 'User already has the tag: %s',
			'ERROR' => 'ERROR: %s',
		),
		'remove_usertag' => array(
			'YES'   => 'Tag %s was removed',
			'NO'    => 'User didn\'t have the tag: %s',
			'ERROR' => 'ERROR: %s',
		),
		'subscribe' => array(
			'YES'   => 'User was subscribed to: %s',
			'NO'    => 'User was already subscribed to: %s',
			'ERROR' => 'List does not exist',
		),
		'unsubscribe' => array(
			'YES'   => 'User was unsubscribed from: %s',
			'NO'    => 'User was already unsubscribed from: %s',
			'ERROR' => 'List does not exist',
		),
	);
}


/**
 * Get array of result labels for automation step
 *
 * @return array Result labels per step type
 */
function step_get_result_labels()
{
	return array(
		'if_condition' => array(
			'YES'   => NT_('Next step if YES'),
			'NO'    => NT_('Next step if NO'),
			'ERROR' => NT_('Next step if ERROR'),
		),
		'send_campaign' => array(
			'YES'   => NT_('Next step if Email SENT'),
			'NO'    => NT_('Next step if Email was ALREADY sent'),
			'ERROR' => NT_('Next step if Email cannot be sent'),
		),
		'notify_owner' => array(
			'YES'   => NT_('Next step if Notification SENT'),
			'NO'    => '',
			'ERROR' => NT_('Next step if Notification cannot be sent'),
		),
		'add_usertag' => array(
			'YES'   => NT_('Next step if Tag was added'),
			'NO'    => NT_('Next step if User was already tagged'),
			'ERROR' => '',
		),
		'remove_usertag' => array(
			'YES'   => NT_('Next step if Tag was removed'),
			'NO'    => NT_('Next step if User didn\'t have that tag'),
			'ERROR' => '',
		),
		'subscribe' => array(
			'YES'   => NT_('Next step if User was subscribed'),
			'NO'    => NT_('Next step if User was already subscribed'),
			'ERROR' => NT_('Next step if List does not exist'),
		),
		'unsubscribe' => array(
			'YES'   => NT_('Next step if User was unsubscribed'),
			'NO'    => NT_('Next step if User was already unsubscribed'),
			'ERROR' => NT_('Next step if List does not exist'),
		),
	);
}


/**
 * Get result label of automation step by step type and result value
 *
 * NOTE! Return string is not translatable, Use funcs T_(), TS_() and etc. in that place where you use this func.
 *
 * @param string Step type: 'if_condition', 'send_campaign
 * @param string Step result: 'YES', 'NO', 'ERROR'
 * @return string Result label
 */
function step_get_result_label( $type, $result )
{
	$results = step_get_result_labels();

	return isset( $results[ $type ][ $result ] ) ? $results[ $type ][ $result ] : $result;
}


/**
 * Helper function to display step info on Results table
 *
 * @param integer Step ID
 * @param integer Number of user queued
 * @return string
 */
function step_td_num_users_queued( $step_ID, $num_users_queued )
{
	if( $num_users_queued > 0 )
	{
		global $admin_url;
		$num_users_queued = '<a href="'.$admin_url.'?ctrl=automations&amp;action=edit_step&amp;step_ID='.$step_ID.'">'
				.$num_users_queued
			.'</a>';
	}

	return $num_users_queued;
}


/**
 * Helper function to display step label on Results table
 *
 * @param integer Step ID
 * @param string Step label
 * @param string Step type
 * @return string
 */
function step_td_label( $step_ID, $step_label, $step_type )
{
	global $current_User;

	$step_label = ( empty( $step_label ) ? step_get_type_title( $step_type ) : $step_label );

	if( $current_User->check_perm( 'options', 'edit' ) )
	{
		global $admin_url;
		$step_label = '<a href="'.$admin_url.'?ctrl=automations&amp;action=edit_step&amp;step_ID='.$step_ID.'"><b>'.$step_label.'</b></a>';
	}

	return $step_label;
}


/**
 * Helper function to display next step info on Results table
 *
 * @param integer Step ID
 * @param integer Next step ID
 * @param integer Next step order
 * @param integer Next step delay
 * @return string
 */
function step_td_next_step( $step_ID, $next_step_ID, $next_step_order, $next_step_delay )
{
	if( $next_step_ID === NULL )
	{	// If next step is not used:
		return '';
	}

	if( empty( $next_step_ID ) )
	{	// Next ordered step:
		return '<span class="green">'.T_('Continue').' ('.seconds_to_period( $next_step_delay ).')</span>';
	}
	elseif( $next_step_ID == '-1' )
	{	// Stop workflow:
		return '<span class="red">'.T_('STOP').'</span>';
	}
	elseif( $next_step_ID == $step_ID )
	{	// Loop:
		return '<span class="orange">'.T_('Loop').' ('.seconds_to_period( $next_step_delay ).')</span>';
	}

	return sprintf( T_('Go to step %d'), intval( $next_step_order ) ).' ('.seconds_to_period( $next_step_delay ).')';
}


/**
 * Helper function to display step actions on Results table
 *
 * @param integer Step ID
 * @param boolean Is first step?
 * @param boolean Is last step?
 * @return string
 */
function step_td_actions( $step_ID, $is_first_step, $is_last_step )
{
	global $admin_url;

	$r = '';

	if( $is_first_step )
	{	// First step cannot be moved up, print out blank icon:
		$r .= get_icon( 'move_up', 'noimg' );
	}
	else
	{	// Display action icon to move step up:
		$r .= action_icon( T_('Move up'), 'move_up', regenerate_url( 'step_ID,action', 'step_ID='.$step_ID.'&amp;action=move_step_up&amp;'.url_crumb( 'automationstep' ) ) );
	}

	if( $is_last_step )
	{	// Last step cannot be moved down, print out blank icon:
		$r .= get_icon( 'move_down', 'noimg' );
	}
	else
	{	// Display action icon to move step down:
		$r .= action_icon( T_('Move down'), 'move_down', regenerate_url( 'step_ID,action', 'step_ID='.$step_ID.'&amp;action=move_step_down&amp;'.url_crumb( 'automationstep' ) ) );
	}

	$r .= action_icon( T_('Edit this step'), 'edit', $admin_url.'?ctrl=automations&amp;action=edit_step&amp;step_ID='.$step_ID );

	$r .= action_icon( T_('Delete this step!'), 'delete', regenerate_url( 'step_ID,action', 'step_ID='.$step_ID.'&amp;action=delete_step&amp;'.url_crumb( 'automationstep' ) ) );

	return $r;
}


/**
 * Helper function to display step state of user on Results table
 *
 * @param integer|NULL Step ID
 * @param string Step label
 * @param string Step type
 * @param string Step order
 * @return string
 */
function step_td_user_state( $step_ID, $step_label, $step_type, $step_order )
{
	if( $step_ID === NULL )
	{	// If all steps for automation were completed for user:
		return T_('Finished');
	}

	return '#'.$step_order.' - '.step_td_label( $step_ID, $step_label, $step_type );
}


/**
 * Initialize JavaScript for AJAX loading of popup window to add user to automation
 * @param array Params
 */
function echo_requeue_automation_js()
{
	global $admin_url;

	// Initialize JavaScript to build and open window:
	echo_modalwindow_js();

	// Initialize variables for the file "evo_user_deldata.js":
	echo '<script type="text/javascript">
		var evo_js_lang_loading = \''.TS_('Loading...').'\';
		var evo_js_lang_requeue_automation_for_finished_steps = \''.TS_('Requeue automation for finished steps').get_manual_link( 'requeue-automation-for-finished-steps' ).'\';
		var evo_js_lang_requeue = \''.TS_('Requeue').'\';
		var evo_js_requeue_automation_ajax_url = \''.$admin_url.'\';
	</script>';
}


/**
 * Display the campaigns results table
 *
 * @param array Params
 */
function automation_results_block( $params = array() )
{
	global $admin_url, $current_User, $DB;

	$params = array_merge( array(
		'enlt_ID'               => NULL, // Newsletter ID
		'results_title'         => T_('Automations').get_manual_link( 'automations-list' ),
		'results_prefix'        => 'autm_',
		'display_create_button' => true
	), $params );

	// Additional URL param, e-g to change status from newsletter page:
	$url_params = '';

	$SQL = new SQL( 'Get automations' );
	$SQL->SELECT( 'autm_ID, autm_name, autm_status, autm_autostart, enlt_ID, enlt_name' );
	$SQL->FROM( 'T_automation__automation' );
	$SQL->FROM_add( 'INNER JOIN T_email__newsletter ON enlt_ID = autm_enlt_ID' );
	if( $params['enlt_ID'] > 0 )
	{	// Restrict by newsletter:
		$SQL->WHERE( 'autm_enlt_ID = '.$params['enlt_ID'] );
		$url_params = '&amp;enlt_ID='.$params['enlt_ID'];
	}

	$Results = new Results( $SQL->get(), $params['results_prefix'], 'A', NULL );

	if( $params['display_create_button'] && $current_User->check_perm( 'options', 'edit' ) )
	{	// User must has a permission to add new automation:
		$Results->global_icon( T_('New automation'), 'new', regenerate_url( 'action', 'action=new' ), T_('New automation').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
	}

	$Results->title = $params['results_title'];

	$Results->cols[] = array(
			'th'       => T_('ID'),
			'order'    => 'autm_ID',
			'td'       => '$autm_ID$',
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
		);

	$Results->cols[] = array(
			'th'    => T_('Name'),
			'order' => 'autm_name',
			'td'    => ( $current_User->check_perm( 'options', 'edit' )
				? '<a href="'.$admin_url.'?ctrl=automations&amp;action=edit&amp;autm_ID=$autm_ID$"><b>$autm_name$</b></a>'
				: '$autm_name$' ),
		);

	$Results->cols[] = array(
			'th'    => T_('Tied to List'),
			'order' => 'enlt_name',
			'td'    => ( $current_User->check_perm( 'emails', 'edit' )
				? '<a href="'.$admin_url.'?ctrl=newsletters&amp;action=edit&amp;enlt_ID=$enlt_ID$"><b>$enlt_name$</b></a>'
				: '$enlt_name$' ),
		);

	$Results->cols[] = array(
			'th'    => T_('Auto start'),
			'order' => 'enlt_name',
			'td'    => '%autm_td_autostart( #autm_ID#, #autm_autostart#, "'.$url_params.'" )%',
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
		);

	$Results->cols[] = array(
			'th'       => T_('Status'),
			'order'    => 'autm_status',
			'td'       => '%autm_td_status( #autm_ID#, #autm_status#, "'.$url_params.'" )%',
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
		);

	if( $current_User->check_perm( 'options', 'edit' ) )
	{	// Display actions column only if current user has a permission to edit options:
		$Results->cols[] = array(
				'th'       => T_('Actions'),
				'td'       => action_icon( T_('Edit this automation'), 'edit', $admin_url.'?ctrl=automations&amp;action=edit&amp;autm_ID=$autm_ID$' )
										 .action_icon( T_('Delete this automation!'), 'delete', regenerate_url( 'autm_ID,action', 'autm_ID=$autm_ID$&amp;action=delete&amp;'.url_crumb( 'automation' ) ) ),
				'th_class' => 'shrinkwrap',
				'td_class' => 'shrinkwrap',
			);
	}

	$Results->display( NULL, 'session' );
}
?>