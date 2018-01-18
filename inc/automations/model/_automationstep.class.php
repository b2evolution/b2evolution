<?php
/**
 * This file implements the automation step class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );


/**
 * AutomationStep Class
 *
 * @package evocore
 */
class AutomationStep extends DataObject
{
	var $autm_ID;
	var $order;
	var $label;
	var $type;
	var $info;
	var $yes_next_step_ID;
	var $yes_next_step_delay;
	var $no_next_step_ID;
	var $no_next_step_delay;
	var $error_next_step_ID;
	var $error_next_step_delay;

	var $Automation = NULL;

	var $yes_next_AutomationStep = NULL;
	var $no_next_AutomationStep = NULL;
	var $error_next_AutomationStep = NULL;

	/**
	 * Constructor
	 *
	 * @param object table Database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_automation__step', 'step_', 'step_ID' );

		if( $db_row !== NULL )
		{
			$this->ID = $db_row->step_ID;
			$this->autm_ID = $db_row->step_autm_ID;
			$this->order = $db_row->step_order;
			$this->label = $db_row->step_label;
			$this->type = $db_row->step_type;
			$this->info = $db_row->step_info;
			$this->yes_next_step_ID = $db_row->step_yes_next_step_ID;
			$this->yes_next_step_delay = $db_row->step_yes_next_step_delay;
			$this->no_next_step_ID = $db_row->step_no_next_step_ID;
			$this->no_next_step_delay = $db_row->step_no_next_step_delay;
			$this->error_next_step_ID = $db_row->step_error_next_step_ID;
			$this->error_next_step_delay = $db_row->step_error_next_step_delay;
		}
	}


	/**
	 * Get delete restriction settings
	 *
	 * @return array
	 */
	static function get_delete_restrictions()
	{
		return array(
				array( 'table' => 'T_automation__user_state', 'fk' => 'aust_next_step_ID', 'msg' => T_('%d automation user states') ),
				array( 'table' => 'T_automation__step', 'fk' => 'step_yes_next_step_ID', 'msg' => T_('it is used %d times as next step if YES') ),
				array( 'table' => 'T_automation__step', 'fk' => 'step_no_next_step_ID', 'msg' => T_('it is used %d times as next step if NO') ),
				array( 'table' => 'T_automation__step', 'fk' => 'step_error_next_step_ID', 'msg' => T_('it is used %d times as next step if ERROR') ),
			);
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		global $DB, $admin_url;

		if( empty( $this->ID ) )
		{	// Set Automation only for new creating Step:
			param( 'autm_ID', 'integer', true );
			$this->set_from_Request( 'autm_ID', 'autm_ID' );
		}

		// Order:
		$step_order = param( 'step_order', 'integer', NULL );
		if( $this->ID > 0 )
		{	// Order is required for edited step:
			param_string_not_empty( 'step_order', T_('Please enter a step order.') );
		}
		elseif( $step_order === NULL )
		{	// Set order for new creating step automatically:
			$max_order_SQL = new SQL( 'Get max step order for Automation #'.$this->get( 'autm_ID' ) );
			$max_order_SQL->SELECT( 'MAX( step_order ) + 1' );
			$max_order_SQL->FROM( 'T_automation__step' );
			$max_order_SQL->WHERE( 'step_autm_ID = '.$this->get( 'autm_ID' ) );
			set_param( 'step_order', $DB->get_var( $max_order_SQL ) );
		}
		$this->set_from_Request( 'order' );
		if( $this->get( 'order' ) > 0 )
		{	// Check for unique order per Automation:
			$check_order_SQL = new SQL( 'Check unique step order for Automation #'.$this->get( 'autm_ID' ) );
			$check_order_SQL->SELECT( 'step_ID' );
			$check_order_SQL->FROM( 'T_automation__step' );
			$check_order_SQL->WHERE( 'step_autm_ID = '.$this->get( 'autm_ID' ) );
			$check_order_SQL->WHERE_and( 'step_order = '.$this->get( 'order' ) );
			if( $this->ID > 0 )
			{	// Exclude this Step:
				$check_order_SQL->WHERE_and( 'step_ID != '.$this->ID );
			}
			if( $existing_step_ID = $DB->get_var( $check_order_SQL ) )
			{	// Display error because of duplicated order in the same Automation:
				global $admin_url;
				param_error( 'step_order',
					sprintf( T_('Step with such order already exists for current automation. Do you want to <a %s>edit that step</a>?'),
						'href="'.$admin_url.'?ctrl=automations&amp;action=edit_step&amp;step_ID='.$existing_step_ID.'"' ) );
			}
		}
		param_check_range( 'step_order', -2147483646, 2147483647, sprintf( T_('Step order must be numeric (%d - %d).'), -2147483646, 2147483647 ) );
	
		// Label:
		param( 'step_label', 'string', NULL );
		$this->set_from_Request( 'label', NULL, true );

		// Type:
		param_string_not_empty( 'step_type', T_('Please select a step type.') );
		$this->set_from_Request( 'type' );
		// Save additional info depending on step type:
		switch( $this->get( 'type' ) )
		{
			case 'send_campaign':
				// Email campaign:
				param( 'step_email_campaign', 'integer', NULL );
				param_check_number( 'step_email_campaign', T_('Please select an email campaign.'), true );
				$this->set( 'info', get_param( 'step_email_campaign' ) );
				break;

			default:
				$this->set( 'info', NULL, true );
		}

		// Next steps:
		$next_steps = array(
				'yes_next_step_ID'   => 'yes_next_step_delay',
				'no_next_step_ID'    => 'no_next_step_delay',
				'error_next_step_ID' => 'error_next_step_delay',
			);
		foreach( $next_steps as $next_step_ID_name => $next_step_delay_name )
		{
			param( 'step_'.$next_step_ID_name, 'integer', NULL );
			$this->set_from_Request( $next_step_ID_name, NULL, true );
			$step_yes_next_step_delay = param_duration( 'step_'.$next_step_delay_name );
			if( empty( $step_yes_next_step_delay ) )
			{
				$step_yes_next_step_delay = NULL;
			}
			$this->set( $next_step_delay_name, $step_yes_next_step_delay, true );
			if( $this->get( $next_step_ID_name ) > 0 && $this->get( $next_step_ID_name ) == $this->ID )
			{	// If next step is same as this current,
				// Don't translate this error message because it cannot happens:
				param_error( 'step_'.$next_step_ID_name, 'Current step cannot be used as next step!' );
			}
		}

		return ! param_errors_detected();
	}


	/**
	 * Get Automation object of this step
	 *
	 * @return object Automation
	 */
	function & get_Automation()
	{
		if( $this->Automation === NULL )
		{	// Initialize Automation object only first time and store in cache:
			$AutomationCache = & get_AutomationCache();
			$this->Automation = & $AutomationCache->get_by_ID( $this->get( 'autm_ID' ), false, false );
		}

		return $this->Automation;
	}


	/**
	 * Get YES next Step object of this Step
	 *
	 * @return object|NULL|boolean Reference on cached object Automation Step, NULL - if request with empty ID, FALSE - if requested object does not exist
	 */
	function & get_yes_next_AutomationStep()
	{
		if( $this->yes_next_AutomationStep === NULL )
		{	// Load next Step into cache object:
			$AutomationStepCache = & get_AutomationStepCache();
			$this->yes_next_AutomationStep = & $AutomationStepCache->get_by_ID( $this->get( 'yes_next_step_ID' ), false, false );
		}

		return $this->yes_next_AutomationStep;
	}


	/**
	 * Get NO next Step object of this Step
	 *
	 * @return object|NULL|boolean Reference on cached object Automation Step, NULL - if request with empty ID, FALSE - if requested object does not exist
	 */
	function & get_no_next_AutomationStep()
	{
		if( $this->no_next_AutomationStep === NULL )
		{	// Load next Step into cache object:
			$AutomationStepCache = & get_AutomationStepCache();
			$this->no_next_AutomationStep = & $AutomationStepCache->get_by_ID( $this->get( 'no_next_step_ID' ), false, false );
		}

		return $this->no_next_AutomationStep;
	}


	/**
	 * Get ERROR next Step object of this Step
	 *
	 * @return object|NULL|boolean Reference on cached object Automation Step, NULL - if request with empty ID, FALSE - if requested object does not exist
	 */
	function & get_error_next_AutomationStep()
	{
		if( $this->error_next_AutomationStep === NULL )
		{	// Load next Step into cache object:
			$AutomationStepCache = & get_AutomationStepCache();
			$this->error_next_AutomationStep = & $AutomationStepCache->get_by_ID( $this->get( 'error_next_step_ID' ), false, false );
		}

		return $this->error_next_AutomationStep;
	}


	/**
	 * Execute action for this step
	 *
	 * @param integer User ID
	 * @param array Additional params
	 */
	function execute_action( $user_ID, $params = array() )
	{
		global $DB, $servertimenow;

		$params = array_merge( array(
				'print_log' => true,
			), $params );

		load_funcs( 'automations/model/_automation.funcs.php' );

		$Automation = & $this->get_Automation();

		if( $params['print_log'] )
		{	// Print log:
			global $is_cli;
			$nl = empty( $is_cli ) ? '<br>' : "\r\n";

			echo 'Executing Step #'.$this->get( 'order' )
				.'('.step_get_type_title( $this->get( 'type' ) ).( $this->get( 'label' ) == '' ? '' : '"'.$this->get( 'label' ).'"' ).')'
				.' of Automation: #'.$Automation->ID.'('.$Automation->get( 'name' ).')'
				.' for User #'.$user_ID.'...'.$nl;
		}

		// Retrun ERROR result by default for all unknown cases:
		$step_result = 'ERROR';

		switch( $this->get( 'type' ) )
		{
			case 'send_campaign':
				// Send email campaign
				$EmailCampaignCache = & get_EmailCampaignCache();
				if( $step_EmailCampaign = & $EmailCampaignCache->get_by_ID( $this->get( 'info' ), false, false ) )
				{
					$user_is_waiting_email = in_array( $user_ID, $step_EmailCampaign->get_recipients( 'wait' ) );
					$user_received_email = in_array( $user_ID, $step_EmailCampaign->get_recipients( 'receive' ) );
					if( ! $user_is_waiting_email && ! $user_received_email )
					{	// If user is not waiting this email campaign and didn't receive:
						$step_result = 'NO';
					}
					elseif( $user_received_email || $step_EmailCampaign->send_email( $user_ID ) )
					{	// If user already received this email before OR email has been sent to user successfully now:
						$step_result = 'YES';
					}
					else
					{	// Some error on sending of email to user:
						// - problem with php mail function;
						// - user cannot receive such email because of day limit;
						// - user is not activated yet.
						$step_result = 'ERROR';
					}
				}
				else
				{	// Wrong stored email campaign for this step:
					$step_result = 'ERROR';
				}
				break;

			default:
				echo '- No implemented action'.$nl;
				break;
		}

		switch( $step_result )
		{
			case 'YES':
				$next_AutomationStep = & $this->get_yes_next_AutomationStep();
				$next_delay = $this->get( 'yes_next_step_delay' );
				break;

			case 'NO':
				$next_AutomationStep = & $this->get_no_next_AutomationStep();
				$next_delay = $this->get( 'no_next_step_delay' );
				break;

			case 'ERROR':
				$next_AutomationStep = & $this->get_error_next_AutomationStep();
				$next_delay = $this->get( 'error_next_step_delay' );
				break;
		}

		if( $next_AutomationStep )
		{	// Use data for next step:
			$next_step_ID = $next_AutomationStep->ID;
			$next_exec_ts = date2mysql( $servertimenow + $next_delay );
		}
		else
		{	// This was the end Step of the Automation:
			$next_step_ID = NULL;
			$next_exec_ts = NULL;
		}
		// Update data for next step or finish it:
		$DB->query( 'UPDATE T_automation__user_state
			  SET aust_next_step_ID = '.$DB->quote( $next_step_ID ).',
			      aust_next_exec_ts = '.$DB->quote( $next_exec_ts ).'
			WHERE aust_autm_ID = '.$DB->quote( $Automation->ID ).'
			  AND aust_user_ID = '.$DB->quote( $user_ID ) );

		if( $params['print_log'] )
		{	// Print log:
			echo ' - Next step: '.( $next_AutomationStep
					? '#'.$next_AutomationStep->get( 'order' )
						.'('.step_get_type_title( $this->get( 'type' ) ).( $this->get( 'label' ) == '' ? '' : ' "'.$this->get( 'label' ).'"' ).')'
						.' delay: '.seconds_to_period( $next_delay ).', '.$next_exec_ts
					: 'NO(this is last step)' ).$nl;
		}

		if( $params['print_log'] )
		{	// Print log:
			echo 'Result: '.$step_result.'.'.$nl.$nl;
		}
	}


	/**
	 * Get name of automation step, it is used for `<select>` with $AutomationStepCache
	 *
	 * @return string
	 */
	function get_name()
	{
		return '#'.$this->get( 'order' ).' - '.step_td_label( $this->get( 'label' ), $this->get( 'type' ) );
	}
}

?>