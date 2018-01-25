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
load_funcs( 'automations/model/_automation.funcs.php' );


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
	 * Insert object into DB based on previously recorded changes.
	 *
	 * @return boolean true on success
	 */
	function dbinsert()
	{
		if( $r = parent::dbinsert() )
		{
			// Update next steps to default values:
			$next_steps = array(
					'yes_next_step_ID'   => 'yes_next_step_delay',
					'no_next_step_ID'    => 'no_next_step_delay',
					'error_next_step_ID' => 'error_next_step_delay',
				);
			foreach( $next_steps as $next_step_ID_name => $next_step_delay_name )
			{
				if( $this->get( $next_step_ID_name ) === NULL && $this->get( $next_step_delay_name ) === NULL )
				{	// Try to set default next steps and delays if they are not selected on creating new step:
					switch( $this->get( 'type' ) )
					{
						case 'if_condition':
							switch( $next_step_ID_name )
							{
								case 'no_next_step_ID':
								case 'error_next_step_ID':
									$this->set( $next_step_ID_name, -1 ); // STOP
									// 0 seconds
									break;
							}
							break;

						case 'send_campaign':
							switch( $next_step_ID_name )
							{
								case 'yes_next_step_ID':
									// Continue to next ordered step
									$this->set( $next_step_delay_name, 259200/* 3 days */ );
									break;
								case 'no_next_step_ID':
									$this->set( $next_step_ID_name, -1 ); // STOP
									// 0 seconds
									break;
								case 'error_next_step_ID':
									$this->set( $next_step_ID_name, $this->ID ); // Loop
									$this->set( $next_step_delay_name, 604800/* 7 days */ );
									break;
							}
							break;
					}
				}
			}
			$r = $this->dbupdate();
		}

		return $r;
	}


	/**
	 * Get a member param by its name
	 *
	 * @param mixed Name of parameter
	 * @return mixed Value of parameter
	 */
	function get( $parname )
	{
		switch( $parname )
		{
			case 'if_condition_js_object':
				if( $this->get( 'type' ) == 'if_condition' && $this->get( 'info' ) != '' )
				{	// Format values(like dates) of the field "IF Condition" from MySQL DB format to current locale format:
					$json_object = json_decode( $this->get( 'info' ) );

					if( $json_object === NULL || ! isset( $json_object->valid ) || $json_object->valid !== true )
					{	// Wrong object, Return null:
						return 'null';
					}

					return json_encode( $this->format_condition_object( $json_object, 'from_mysql' ) );
				}
				else
				{	// No stored object, Return null:
					return 'null';
				}
				
		}

		return parent::get( $parname );
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
			case 'if_condition':
				// IF Condition:
				param_string_not_empty( 'step_if_condition', T_('Please set a condition.') );
				$this->set( 'info', $this->format_condition_to_mysql( get_param( 'step_if_condition' ) ) );
				break;

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
			$step_next_step_delay = param_duration( 'step_'.$next_step_delay_name );
			if( empty( $step_next_step_delay ) )
			{
				$step_next_step_delay = NULL;
			}
			$this->set( $next_step_delay_name, $step_next_step_delay, true );
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
	 * Get next Step object of this Step by step ID
	 *
	 * @param integer Step ID
	 * @return object|boolean Next Automation Step OR
	 *                        FALSE - if automation should be stopped after this Step
	 *                                because either it is configured for STOP action
	 *                                            or it is the latest step of the automation
	 */
	function & get_next_AutomationStep_by_ID( $next_step_ID )
	{
		$next_step_ID = intval( $next_step_ID );

		$next_AutomationStep = false;

		$AutomationStepCache = & get_AutomationStepCache();
		if( $next_step_ID > 0 )
		{	// Get a next Step by defined ID:
			$next_AutomationStep = & $AutomationStepCache->get_by_ID( $next_step_ID, false, false );
		}

		if( $next_step_ID == -1 )
		{	// Stop workflow when option is selected to "STOP":
			$next_AutomationStep = false;
		}
		elseif( $next_step_ID == 0 || ! $next_AutomationStep )
		{	// Get next ordered Step when option is selected to "Continue" OR Step cannot be found by ID in DB:
			global $DB;
			$next_ordered_step_SQL = new SQL( 'Get next ordered Step after current Step #'.$this->ID );
			$next_ordered_step_SQL->SELECT( 'step_ID' );
			$next_ordered_step_SQL->FROM( 'T_automation__step' );
			$next_ordered_step_SQL->WHERE( 'step_autm_ID = '.$DB->quote( $this->get( 'autm_ID' ) ) );
			$next_ordered_step_SQL->WHERE_and( 'step_order > '.$DB->quote( $this->get( 'order' ) ) );
			$next_ordered_step_SQL->ORDER_BY( 'step_order ASC' );
			$next_ordered_step_SQL->LIMIT( 1 );
			$next_ordered_step_ID = $DB->get_var( $next_ordered_step_SQL );
			$next_AutomationStep = & $AutomationStepCache->get_by_ID( $next_ordered_step_ID, false, false );
			if( empty( $next_AutomationStep ) )
			{	// If it is the latest Step of the Automation:
				$next_AutomationStep = false;
			}
		}

		return $next_AutomationStep;
	}


	/**
	 * Get YES next Step object of this Step
	 *
	 * @return object|boolean Next Automation Step OR
	 *                        FALSE - if automation should be stopped after this Step
	 *                                because either it is configured for STOP action
	 *                                            or it is the latest step of the automation
	 */
	function & get_yes_next_AutomationStep()
	{
		if( $this->yes_next_AutomationStep === NULL )
		{	// Load next Step into cache object:
			$this->yes_next_AutomationStep = & $this->get_next_AutomationStep_by_ID( $this->get( 'yes_next_step_ID' ) );
		}

		return $this->yes_next_AutomationStep;
	}


	/**
	 * Get NO next Step object of this Step
	 *
	 * @return object|boolean Next Automation Step OR
	 *                        FALSE - if automation should be stopped after this Step
	 *                                because either it is configured for STOP action
	 *                                            or it is the latest step of the automation
	 */
	function & get_no_next_AutomationStep()
	{
		if( $this->no_next_AutomationStep === NULL )
		{	// Load next Step into cache object:
			$this->no_next_AutomationStep = & $this->get_next_AutomationStep_by_ID( $this->get( 'no_next_step_ID' ) );
		}

		return $this->no_next_AutomationStep;
	}


	/**
	 * Get ERROR next Step object of this Step
	 *
	 * @return object|boolean Next Automation Step OR
	 *                        FALSE - if automation should be stopped after this Step
	 *                                because either it is configured for STOP action
	 *                                            or it is the latest step of the automation
	 */
	function & get_error_next_AutomationStep()
	{
		if( $this->error_next_AutomationStep === NULL )
		{	// Load next Step into cache object:
			$this->error_next_AutomationStep = & $this->get_next_AutomationStep_by_ID( $this->get( 'error_next_step_ID' ) );
		}

		return $this->error_next_AutomationStep;
	}


	/**
	 * Execute action for this step
	 *
	 * @param integer User ID
	 * @param string Log process into this param
	 */
	function execute_action( $user_ID, & $process_log )
	{
		global $DB, $servertimenow, $mail_log_message;

		$Automation = & $this->get_Automation();

		$log_nl = "\n";
		$log_point = ' - ';

		// Log:
		$process_log = $log_nl.'Executing Step #'.$this->get( 'order' )
			.'('.step_get_type_title( $this->get( 'type' ) ).( $this->get( 'label' ) == '' ? '' : '"'.$this->get( 'label' ).'"' ).')'
			.' of Automation: #'.$Automation->ID.'('.$Automation->get( 'name' ).')'
			.' for User #'.$user_ID.'...'.$log_nl;

		// Retrun ERROR result by default for all unknown cases:
		$step_result = 'ERROR';
		$additional_result_message = '';

		$UserCache = & get_UserCache();
		if( $step_User = & $UserCache->get_by_ID( $user_ID, false, false ) )
		{	// Allow to execute action only if User is detected in DB:
			switch( $this->get( 'type' ) )
			{
				case 'if_condition':
					if( $this->check_if_condition( $step_User, $if_condition_log ) )
					{	// The user is matched to condition of this step:
						$step_result = 'YES';
					}
					else
					{	// The user is NOT matched to condition of this step:
						$step_result = 'NO';
					}
					// Log:
					$process_log .= $log_point.'Log: '.$if_condition_log.$log_nl;
					break;

				case 'send_campaign':
					// Send email campaign
					$EmailCampaignCache = & get_EmailCampaignCache();
					if( $step_EmailCampaign = & $EmailCampaignCache->get_by_ID( $this->get( 'info' ), false, false ) )
					{
						$user_is_waiting_email = in_array( $user_ID, $step_EmailCampaign->get_recipients( 'wait' ) );
						$user_received_email = in_array( $user_ID, $step_EmailCampaign->get_recipients( 'receive' ) );
						if( $user_received_email )
						{	// If user already received this email:
							$step_result = 'NO';
						}
						elseif( $user_is_waiting_email && $step_EmailCampaign->send_email( $user_ID ) )
						{	// If user already received this email before OR email has been sent to user successfully now:
							$step_result = 'YES';
						}
						else
						{	// Some error on sending of email to user:
							// - problem with php mail function;
							// - user cannot receive such email because of day limit;
							// - user is not activated yet.
							$step_result = 'ERROR';
							$additional_result_message = empty( $mail_log_message ) ? 'Email could not be sent by unknown reason.' : $mail_log_message;
						}
					}
					else
					{	// Wrong stored email campaign for this step:
						$step_result = 'ERROR';
						$additional_result_message = 'Email Campaign #'.$this->get( 'info' ).' is not found in DB.';
					}
					break;

				default:
					// Log:
					$process_log .= $log_point.'No implemented action'.$log_nl;
					break;
			}
		}
		else
		{	// Wrong user:
			$additional_result_message = 'User #'.$user_ID.' is not found in DB.';
		}

		// Log:
		if( $step_result == 'ERROR' && empty( $additional_result_message ) )
		{	// Set default additional error message:
			$additional_result_message = 'Unknown error';
		}
		$process_log .= $log_point.'Result: '.$this->get_result_title( $step_result, $additional_result_message ).'.'.$log_nl;

		// Get data for next step:
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
		{	// Use data for next step if it is defined:
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
			  AND aust_user_ID = '.$DB->quote( $user_ID ),
			'Update data for next Step after executing Step #'.$this->ID );

		// Log:
		$process_log .= ( $next_AutomationStep
				? $log_point.'Next step: #'.$next_AutomationStep->get( 'order' )
					.'('.step_get_type_title( $this->get( 'type' ) ).( $this->get( 'label' ) == '' ? '' : ' "'.$this->get( 'label' ).'"' ).')'
					.' delay: '.seconds_to_period( $next_delay ).', '.$next_exec_ts
				: $log_point.'There is no next step configured.' ).$log_nl;
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


	/**
	 * Get result title depending on step type
	 *
	 * NOTE! Return string is not translatable, Use funcs T_(), TS_() and etc. in that place where you use this func.
	 *
	 * @param string Result: YES, NO, ERROR
	 * @param string Additional message, for example: some error message
	 * @return string Result title
	 */
	function get_result_title( $result, $additional_message = '' )
	{
		$result_title = step_get_result_title( $this->get( 'type' ), $result );

		if( strpos( $result_title, '%s' ) !== false )
		{	// Replace mask with additional message like error:
			$result_title = sprintf( $result_title, '"'.$additional_message.'"' );
		}

		return $result_title;
	}


	/**
	 * Check result of "IF Condition"
	 *
	 * @param object User
	 * @param string Log process into this param
	 * @return boolean TRUE if condition is matched for given user, otherwise FALSE
	 */
	function check_if_condition( $step_User, & $process_log )
	{
		if( $this->get( 'type' ) != 'if_condition' )
		{	// This is allowed only for step type "IF Condition":
			return false;
		}

		$json_object = json_decode( $this->get( 'info' ) );

		if( $json_object === NULL || ! isset( $json_object->valid ) || $json_object->valid !== true )
		{	// Wrong object, Return false:
			return false;
		}

		return $this->check_if_condition_object( $json_object, $step_User, $process_log );
	}


	/**
	 * Check result of "IF Condition" object(one group of rules)
	 * Used recursively to find all sub grouped conditions
	 *
	 * @param object JSON object of step type "IF Condition"
	 * @param object User
	 * @param string Log process into this param
	 * @return boolean TRUE if condition is matched for given user, otherwise FALSE
	 */
	function check_if_condition_object( $json_object, $step_User, & $process_log )
	{
		if( ! isset( $json_object->condition ) || ! in_array( $json_object->condition, array( 'AND', 'OR' ) ) || empty( $json_object->rules ) )
		{	// Wrong json object params, Skip it:
			return false;
		}

		// Log:
		$process_log .= ' ('.$json_object->condition;
		// Array to convert operator names to log format:
		$log_operators = array(
				'equal'            => '=',
				'not_equal'        => '&#8800;',
				'less'             => '<',
				'less_or_equal'    => '&#8804;',
				'greater'          => '>',
				'greater_or_equal' => '&#8805;',
				'between'          => array( 'BETWEEN', 'AND' ),
				'not_between'      => array( 'NOT BETWEEN', 'AND' ),
			);
		$log_bold_start = '<b>';
		$log_bold_end = '</b>';
		$log_rule_separator = ', ';

		if( $json_object->condition == 'AND' )
		{	// Default result for group with operator 'AND':
			$conditions_result = true;
			$stop_result = false;
		}
		else
		{	// Default result for group with operator 'OR':
			$conditions_result = false;
			$stop_result = true;
		}

		foreach( $json_object->rules as $rule )
		{
			if( $conditions_result == $stop_result )
			{	// Skip this rule because previous rules already returned the end result for current condition(AND|OR):
				$process_log .= $log_rule_separator.$log_bold_start.'ignored'.$log_bold_end;
				continue;
			}

			if( isset( $rule->rules ) && is_array( $rule->rules ) )
			{	// This is a group of conditions, Run this function recursively:
				$process_log .= $log_rule_separator;
				$rule_result = $this->check_if_condition_object( $rule, $step_User, $process_log );
			}
			else
			{	// This is a single field:
				$rule_result = $this->check_if_condition_rule( $rule, $step_User, $process_log );
				// Log:
				$process_log .= $log_rule_separator.$rule->field.' ';
				if( is_array( $log_operators[ $rule->operator ] ) )
				{	// Multiple operator and values:
					foreach( $log_operators[ $rule->operator ] as $o => $operator )
					{
						$process_log .= ' '.$operator.' "'.$rule->value[ $o ].'"';
					}
				}
				else
				{	// Single operator and value:
					$process_log .= ' '.$log_operators[ $rule->operator ].' "'.$rule->value.'"';
				}
				$process_log .= ': '.$log_bold_start.( $rule_result ? 'TRUE' : 'FALSE' ).$log_bold_end;
			}

			// Append current result with previous results:
			if( $json_object->condition == 'AND' )
			{	// AND condition:
				$conditions_result = $conditions_result && $rule_result;
			}
			else
			{	// OR condition:
				$conditions_result = $conditions_result || $rule_result;
			}
		}

		// Log:
		$process_log .= ') : '.$log_bold_start.( $conditions_result ? 'TRUE' : 'FALSE' ).$log_bold_end;

		return $conditions_result;
	}


	/**
	 * Check rule of "IF Condition" for given User
	 *
	 * @param object Rule, object with properties: field, value, operator
	 * @param object User
	 * @return boolean TRUE if condition is matched for given user, otherwise FALSE
	 */
	function check_if_condition_rule( $rule, $step_User )
	{
		switch( $rule->field )
		{
			case 'user_has_tag':
				// Check if User has a tag:
				$user_tags = $step_User->get_usertags();
				switch( $rule->operator )
				{
					case 'equal':
						return in_array( $rule->value, $user_tags );
					case 'not_equal':
						return ! in_array( $rule->value, $user_tags );
				}
				break;

			case 'date':
				// Check current date:
				global $localtimenow;
				if( is_array( $rule->value ) )
				{
					$rule_date_ts = strtotime( $rule->value[0] );
					$rule_date_ts2 = strtotime( $rule->value[1] );
				}
				else
				{
					$rule_date_ts = strtotime( $rule->value );
				}
				switch( $rule->operator )
				{
					case 'equal':
						return $localtimenow == $rule_date_ts;
					case 'not_equal':
						return $localtimenow != $rule_date_ts;
					case 'less':
						return $localtimenow < $rule_date_ts;
					case 'less_or_equal':
						return $localtimenow <= $rule_date_ts;
					case 'greater':
						return $localtimenow > $rule_date_ts;
					case 'greater_or_equal':
						return $localtimenow >= $rule_date_ts;
					case 'between':
						return $localtimenow >= $rule_date_ts && $localtimenow <= $rule_date_ts2;
					case 'not_between':
						return $localtimenow < $rule_date_ts && $localtimenow > $rule_date_ts2;
				}
				break;
		}

		// Unknown field or operator:
		return false;
	}


	/**
	 * Format values(like dates) of the field "IF Condition" to store in MySQL DB
	 *
	 * @param string Source condition
	 * @return string Condition with formatted values for MySQL DB
	 */
	function format_condition_to_mysql( $condition )
	{
		if( $this->get( 'type' ) != 'if_condition' )
		{	// This is allowed only for step type "IF Condition":
			return '';
		}

		$json_object = json_decode( $condition );

		if( $json_object === NULL || ! isset( $json_object->valid ) || $json_object->valid !== true )
		{	// Wrong object:
			return '';
		}

		return json_encode( $this->format_condition_object( $json_object, 'to_mysql' ) );
	}


	/**
	 * Format JSON object to/from DB format
	 * Used recursively to find all sub grouped conditions
	 *
	 * @param object JSON object of step type "IF Condition"
	 * @param string Format action: 'to_mysql', 'from_mysql'
	 * @return string
	 */
	function format_condition_object( $json_object, $action )
	{
		if( empty( $json_object->rules ) )
		{	// No rules, Skip it:
			return $json_object;
		}

		foreach( $json_object->rules as $r => $rule )
		{
			if( isset( $rule->rules ) && is_array( $rule->rules ) )
			{	// This is a group of conditions, Run this function recursively:
				$json_object->rules[ $r ] = $this->format_condition_object( $rule, $action );
			}
			else
			{	// This is a single field, Format condition only for this field:
				if( is_array( $rule->value ) )
				{	// Field with multiple values like 'between'(field BETWEEN value_1 AND value_2):
					foreach( $rule->value as $v => $rule_value )
					{
						$rule->value[ $v ] = $this->format_condition_rule_value( $rule_value, $rule->type, $action );
					}
				}
				else
				{	// Field with single value like 'equal'(field = value):
					$rule->value = $this->format_condition_rule_value( $rule->value, $rule->type, $action );
				}
				$json_object->rules[ $r ] = $rule;
			}
		}

		return $json_object;
	}


	/**
	 * Format rule value to/from DB format
	 *
	 * @param string Rule value
	 * @param string Rule type
	 * @param string Format action: 'to_mysql', 'from_mysql'
	 */
	function format_condition_rule_value( $rule_value, $rule_type, $action )
	{
		switch( $rule_type )
		{
			case 'date':
				switch( $action )
				{
					case 'to_mysql':
						$formatted_date = format_input_date_to_iso( $rule_value );
						return $formatted_date ? $formatted_date : $rule_value;

					case 'from_mysql':
						return mysql2date( locale_input_datefmt(), $rule_value );
				}
				break;
		}

		return $rule_value;
	}
}

?>