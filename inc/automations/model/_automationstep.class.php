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

	var $Automation;

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
				// TODO:
				//array( 'table' => 'T_email__newsletter', 'fk' => 'enlt_default_autm_ID', 'msg' => T_('%d lists use this automation') ),
			);
	}


	/**
	 * Get delete cascade settings
	 *
	 * @return array
	 */
	static function get_delete_cascades()
	{
		return array(
				// TODO:
				//array( 'table' => 'T_automation__user_state', 'fk' => 'aust_autm_ID', 'msg' => T_('%d automation user states') ),
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

		// Next step if YES:
		param( 'step_yes_next_step_ID', 'integer', NULL );
		$this->set_from_Request( 'yes_next_step_ID', NULL, true );
		$step_yes_next_step_delay = param_duration( 'step_yes_next_step_delay' );
		if( empty( $step_yes_next_step_delay ) )
		{
			$step_yes_next_step_delay = NULL;
		}
		$this->set( 'yes_next_step_delay', $step_yes_next_step_delay, true );

		// Next step if NO:
		param( 'step_no_next_step_ID', 'integer', NULL );
		$this->set_from_Request( 'no_next_step_ID', NULL, true );
		$step_no_next_step_delay = param_duration( 'step_no_next_step_delay' );
		if( empty( $step_no_next_step_delay ) )
		{
			$step_no_next_step_delay = NULL;
		}
		$this->set( 'no_next_step_delay', $step_no_next_step_delay, true );

		// Next step if Error:
		param( 'step_error_next_step_ID', 'integer', NULL );
		$this->set_from_Request( 'error_next_step_ID', NULL, true );
		$step_error_next_step_delay = param_duration( 'step_error_next_step_delay' );
		if( empty( $step_error_next_step_delay ) )
		{
			$step_error_next_step_delay = NULL;
		}
		$this->set( 'error_next_step_delay', $step_error_next_step_delay, true );

		return ! param_errors_detected();
	}


	/**
	 * Get Automation object of this step
	 *
	 * @return object Automation
	 */
	function & get_Automation()
	{
		if( ! isset( $this->Automation ) )
		{	// Initialize Automation object only first time and store in cache:
			$AutomationCache = & get_AutomationCache();
			$this->Automation = & $AutomationCache->get_by_ID( $this->get( 'autm_ID' ), false, false );
		}

		return $this->Automation;
	}


	/**
	 * Execute action for this step
	 *
	 * @param integer User ID
	 */
	function execute_action( $user_ID )
	{
		$Automation = & $this->get_Automation();

		// TODO:
		echo 'Executing Step #'.$this->ID.' of Automation: #'.$Automation->ID.' for User #'.$user_ID.'...<br>'."\r\n";
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