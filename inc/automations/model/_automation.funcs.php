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
	echo '<li class="breadcrumb-item'.( isset( $edited_Automation ) || isset( $edited_AutomationStep ) ? '' : ' active' ).'">'
			.( isset( $edited_Automation ) || isset( $edited_AutomationStep ) ? '<a href="'.$admin_url.'?ctrl=automations">'.T_('All').'</a>' : T_('All') )
		.'</li>';
	if( isset( $edited_AutomationStep ) )
	{	// Automation step:
		$step_Automation = & $edited_AutomationStep->get_Automation();
		echo '<li class="breadcrumb-item"><a href="'.$admin_url.'?ctrl=automations&amp;action=edit&amp;autm_ID='.$step_Automation->ID.'">'.$step_Automation->dget( 'name' ).'</a></li>';
		echo '<li class="breadcrumb-item active">'.( $edited_AutomationStep->ID > 0 ? T_('Step').' #'.$edited_AutomationStep->dget( 'order' ) : T_('New step') ).'</li>';
	}
	elseif( isset( $edited_Automation ) )
	{	// Automation:
		echo '<li class="breadcrumb-item active">'.( $edited_Automation->ID > 0 ? $edited_Automation->dget( 'name' ) : T_('New automation') ).'</li>';
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
 * Helper function to display the tied lists to automation on Results table
 *
 * @param string Newsletters data separated by "<", also ID and name are separated by ":"
 * @return string
 */
function autm_td_tied_lists( $newsletters )
{
	global $current_User;

	if( $current_User->check_perm( 'emails', 'edit' ) )
	{	// Make icon to action link if current User has a perm to edit this:
		global $admin_url;
		$r = '';
		$newsletters = explode( '<', $newsletters );
		foreach( $newsletters as $n => $newsletter )
		{
			if( preg_match( '#^(\d+):([01]):([01]):(.+)$#', $newsletter, $newsletter ) )
			{
				$r .= '<a href="'.$admin_url.'?ctrl=newsletters&amp;action=edit'
					.'&amp;enlt_ID='.$newsletter[1].'">'.$newsletter[4].'</a>'
					.( $newsletter[2] ? ' <span class="label label-success" title="'.format_to_js( T_('auto start on list subscribe') ).'">'./* TRANS: Auto Start automation on list subscribe */T_('AS').'</span>': '' )
					.( $newsletter[3] ? ' <span class="label label-danger" title="'.format_to_js( T_('auto exit on list unsubscribe') ).'">'./* TRANS: Auto Exit automation on list unsubscribe */T_('AE').'</span>': '' )
					.', ';
			}
		}
		return substr( $r, 0, -2 );
	}
	else
	{	// Newsletter names without link:
		return str_replace( '<', ', ', $newsletter_names );
	}
}


/**
 * Helper function to display the step of automation users on Results table
 *
 * @param integer Step ID
 * @param integer Step order
 * @param string Step label
 * @param string Step type
 * @param string Step info
 * @return string
 */
function autm_td_users_step( $step_ID, $step_order, $step_label, $step_type, $step_info )
{
	if( $step_ID === NULL )
	{
		return T_('Finished');
	}
	else
	{
		return '#'.$step_order.' '.step_td_label( $step_ID, $step_label, $step_type, $step_info );
	}
}


/**
 * Helper function to display automation actions per user on Results table
 *
 * @param integer Automation ID
 * @param integer User ID
 * @param string User login
 * @param integer Step ID
 * @param integer|NULL Step order
 * @return string
 */
function autm_td_users_actions( $autm_ID, $user_ID, $user_login, $step_ID, $step_order = NULL )
{
	global $admin_url;

	$r = '';

	// If step order is defined we call this from step edit form, otherwise from tab "Automation" -> "Users":
	$is_step_edit_form = ( $step_order !== NULL );

	// Append step ID to know the action is from step edit form:
	$step_action_url = $admin_url.'?ctrl=automations&amp;autm_ID='.$autm_ID.( $is_step_edit_form ? '&amp;step_ID='.$step_ID : '' ).'&amp;user_ID='.$user_ID.'&amp;'.url_crumb( 'automation' ).'&amp;action=';

	if( $step_ID > 0 )
	{	// Only for active step(excluding finished step):

		// Change execution time:
		$r .= action_icon( T_('Change execution time to now'), 'forward', $step_action_url.'reduce_step_delay' );

		// Stop automation :
		$r .= action_icon( T_('Stop automation for this user'), 'stop_square', $step_action_url.'stop_user' );
	}

	// Remove user from automation:
	$r .= action_icon( T_('Remove this user from automation'), 'remove', $step_action_url.'remove_user', '', 0, 0, array( 'onclick' => 'return confirm(\''.TS_('Are you sure want to remove this user from automation?').'\');' ) );

	// Requeue:
	$r .= ' <a href="#" class="btn btn-info btn-xs"'
		.' onclick="return requeue_automation( '.$autm_ID.', '.( $is_step_edit_form ? $step_ID : '0' ).', '.( $is_step_edit_form ? $step_order : '0' ).', '.$user_ID.', \''.$user_login.'\' )">'
			.T_('Requeue')
		.'</a>';

	return $r;
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
		'if_condition'     => T_('IF Condition'),
		'send_campaign'    => T_('Send Campaign'),
		'notify_owner'     => T_('Notify owner'),
		'add_usertag'      => T_('Add Usertag'),
		'remove_usertag'   => T_('Remove Usertag'),
		'subscribe'        => T_('Subscribe User to List'),
		'unsubscribe'      => T_('Unsubscribe User from List'),
		'start_automation' => T_('Start new automation'),
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
		'start_automation' => array(
			'YES'   => 'User started new automation %s successfully',
			'NO'    => 'Users was already in the other automation %s',
			'ERROR' => 'Automation does not exist',
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
		'start_automation' => array(
			'YES'   => NT_('Next step if User started new automation successfully'),
			'NO'    => NT_('Next step if Users was already in the other automation'),
			'ERROR' => NT_('Next step if Automation does not exist'),
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
 * @param integer Automation ID
 * @param integer Number of user queued
 * @param integer Step order
 * @return string
 */
function step_td_num_users_queued( $step_ID, $autm_ID, $num_users_queued, $step_order )
{
	if( $num_users_queued > 0 )
	{
		global $admin_url;
		$num_users_queued = '<a href="'.$admin_url.'?ctrl=automations&amp;action=edit_step&amp;step_ID='.$step_ID.'">'
				.$num_users_queued
			.'</a>'
			.' <a href="#" class="btn btn-info btn-xs" onclick="return requeue_automation( '.$autm_ID.', '.$step_ID.', '.$step_order.' )">'.T_('Requeue').'</a>';
	}

	return $num_users_queued;
}


/**
 * Helper function to display step label on Results table
 *
 * @param integer Step ID
 * @param string Step label
 * @param string Step type
 * @param string Step info
 * @return string
 */
function step_td_label( $step_ID, $step_label, $step_type, $step_info )
{
	global $current_User, $admin_url;

	$step_type_title = step_get_type_title( $step_type );

	// Display step type title as:
	$r = $current_User->check_perm( 'options', 'edit' )
		// link to edit page if current user has a permission:
		? '<a href="'.$admin_url.'?ctrl=automations&amp;action=edit_step&amp;step_ID='.$step_ID.'"><b>'.$step_type_title.'</b></a>: '
		// plain text if current user has no permission:
		: $step_type_title.': ';

	switch( $step_type )
	{
		case 'send_campaign':
			// Display email campaign title as:
			$r .= $current_User->check_perm( 'emails', 'edit' )
				// link to edit page if current user has a permission:
				? '<a href="'.$admin_url.'?ctrl=campaigns&amp;action=edit&amp;tab=send&amp;ecmp_ID='.intval( $step_info ).'">'.$step_label.'</a>'
				// plain text if current user has no permission:
				: $step_label;
			break;

		case 'subscribe':
		case 'unsubscribe':
			// Display newsletter name as:
			$r .= $current_User->check_perm( 'emails', 'edit' )
				// link to edit page if current user has a permission:
				? '<a href="'.$admin_url.'?ctrl=newsletters&amp;action=edit&amp;enlt_ID='.intval( $step_info ).'">'.$step_label.'</a>'
				// plain text if current user has no permission:
				: $step_label;
			break;

		case 'start_automation':
			// Display automation name as:
			$r .= $current_User->check_perm( 'options', 'edit' )
				// link to edit page if current user has a permission:
				? '<a href="'.$admin_url.'?ctrl=automations&amp;action=edit&amp;tab=settings&amp;autm_ID='.intval( $step_info ).'">'.$step_label.'</a>'
				// plain text if current user has no permission:
				: $step_label;
			break;

		default:
			$r = $current_User->check_perm( 'options', 'edit' )
				// link to edit page if current user has a permission:
				? '<a href="'.$admin_url.'?ctrl=automations&amp;action=edit_step&amp;step_ID='.$step_ID.'"><b>'.$step_type_title.'</b>: '.$step_label.'</a>'
				// plain text if current user has no permission:
				: $step_type_title.': '.$step_label;
	}

	return $r;
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

	$r .= action_icon( T_('Duplicate step right below current one'), 'copy', $admin_url.'?ctrl=automations&amp;action=copy_step&amp;step_ID='.$step_ID );

	$r .= action_icon( T_('Delete this step!'), 'delete', regenerate_url( 'step_ID,action', 'step_ID='.$step_ID.'&amp;action=delete_step&amp;'.url_crumb( 'automationstep' ) ) );

	return $r;
}


/**
 * Helper function to display step state of user on Results table
 *
 * @param integer|NULL Step ID
 * @param string Step label
 * @param string Step type
 * @param string Step info
 * @param string Step order
 * @return string
 */
function step_td_user_state( $step_ID, $step_label, $step_type, $step_info, $step_order )
{
	if( $step_ID === NULL )
	{	// If all steps for automation were completed for user:
		return T_('Finished');
	}

	return '#'.$step_order.' - '.step_td_label( $step_ID, $step_label, $step_type, $step_info );
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
		var evo_js_lang_requeue_automation_for_step_users = \''.TS_('Requeue automation for users of step #%s').get_manual_link( 'requeue-automation-for-step' ).'\';
		var evo_js_lang_requeue_automation_for_user = \''.TS_('Requeue automation for user "%s"').get_manual_link( 'requeue-automation-for-user' ).'\';
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
	$SQL->SELECT( 'autm_ID, autm_name, autm_status, enlt_ID, enlt_name, COUNT( DISTINCT aust_user_ID ) AS autm_users_num' );
	$SQL->SELECT_add( ', ( SELECT GROUP_CONCAT( en.enlt_ID, ":", an.aunl_autostart, ":", an.aunl_autoexit, ":", en.enlt_name ORDER BY an.aunl_order SEPARATOR "<" )
		 FROM T_automation__newsletter AS an
		INNER JOIN T_email__newsletter en ON en.enlt_ID = an.aunl_enlt_ID
		WHERE autm_ID = an.aunl_autm_ID ) AS newsletters' );
	$SQL->FROM( 'T_automation__automation' );
	$SQL->FROM_add( 'LEFT JOIN T_automation__user_state ON aust_autm_ID = autm_ID' );
	$SQL->FROM_add( 'INNER JOIN T_automation__newsletter ON autm_ID = aunl_autm_ID' );
	$SQL->FROM_add( 'INNER JOIN T_email__newsletter ON enlt_ID = aunl_enlt_ID' );
	$SQL->GROUP_BY( 'autm_ID' );

	$count_SQL = new SQL( 'Get a count of automations' );
	$count_SQL->SELECT( 'COUNT( autm_ID )' );
	$count_SQL->FROM( 'T_automation__automation' );

	if( $params['enlt_ID'] > 0 )
	{	// Restrict by newsletter:
		$SQL->WHERE( 'aunl_enlt_ID = '.$params['enlt_ID'] );
		$count_SQL->FROM_add( 'INNER JOIN T_automation__newsletter ON autm_ID = aunl_autm_ID' );
		$count_SQL->WHERE( 'aunl_enlt_ID = '.$params['enlt_ID'] );
		$url_params = '&amp;enlt_ID='.$params['enlt_ID'];
	}

	$Results = new Results( $SQL->get(), $params['results_prefix'], 'A', NULL, $count_SQL->get() );

	if( $params['display_create_button'] && $current_User->check_perm( 'options', 'edit' ) )
	{	// User must has a permission to add new automation:
		//$Results->global_icon( T_('New automation'), 'new', regenerate_url( 'action', 'action=new' ), T_('New automation').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
		$Results->global_icon( T_('New automation'), 'new', $admin_url.'?ctrl=automations&amp;action=new'.( isset( $params['enlt_ID'] ) ? '&amp;enlt_ID='.$params['enlt_ID'] : '' ), T_('New automation').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
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
			'th'    => T_('Tied to Lists'),
			'order' => 'newsletters',
			'td'    => '%autm_td_tied_lists( #newsletters# )%',
		);

	$Results->cols[] = array(
			'th'          => T_('Users'),
			'order'       => 'autm_users_num',
			'td'          => '$autm_users_num$',
			'th_class'    => 'shrinkwrap',
			'td_class'    => 'shrinkwrap',
			'default_dir' => 'D',
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