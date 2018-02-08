<?php
/**
 * This file implements the automation class.
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
 * Automation Class
 *
 * @package evocore
 */
class Automation extends DataObject
{
	var $name;
	var $status;
	var $enlt_ID;
	var $owner_user_ID;
	var $autostart = 1;

	var $Newsletter = NULL;
	var $owner_User = NULL;

	/**
	 * Constructor
	 *
	 * @param object table Database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_automation__automation', 'autm_', 'autm_ID' );

		if( $db_row === NULL )
		{
			if( is_logged_in() )
			{	// Use current User for new creating Automation:
				global $current_User;
				$this->owner_User = $current_User;
			}
		}
		else
		{
			$this->ID = $db_row->autm_ID;
			$this->name = $db_row->autm_name;
			$this->status = $db_row->autm_status;
			$this->enlt_ID = $db_row->autm_enlt_ID;
			$this->owner_user_ID = $db_row->autm_owner_user_ID;
			$this->autostart = $db_row->autm_autostart;
		}
	}


	/**
	 * Get delete cascade settings
	 *
	 * @return array
	 */
	static function get_delete_cascades()
	{
		return array(
				array( 'table' => 'T_automation__step', 'fk' => 'step_autm_ID', 'msg' => T_('%d steps') ),
				array( 'table' => 'T_automation__user_state', 'fk' => 'aust_autm_ID', 'msg' => T_('%d states of User in Automation') ),
			);
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// Name:
		param_string_not_empty( 'autm_name', T_('Please enter an automation name.') );
		$this->set_from_Request( 'name' );

		// Status:
		param_string_not_empty( 'autm_status', 'Please select an automation status.' );
		$this->set_from_Request( 'status' );

		// Tied to List:
		param( 'autm_enlt_ID', 'integer', NULL );
		param_check_number( 'autm_enlt_ID', T_('Please select an automation list.'), true );
		$this->set_from_Request( 'enlt_ID' );

		// Owner:
		$autm_owner_login = param( 'autm_owner_login', 'string', NULL );
		$UserCache = & get_UserCache();
		$owner_User = & $UserCache->get_by_login( $autm_owner_login );
		if( empty( $owner_User ) )
		{
			param_error( 'owner_login', sprintf( T_('User &laquo;%s&raquo; does not exist!'), $autm_owner_login ) );
		}
		else
		{
			$this->set( 'owner_user_ID', $owner_User->ID );
			$this->owner_User = & $owner_User;
		}

		// Auto start:
		param( 'autm_autostart', 'integer', 0 );
		$this->set_from_Request( 'autostart' );

		return ! param_errors_detected();
	}


	/**
	 * Insert object into DB based on previously recorded changes.
	 *
	 * @return boolean true on success
	 */
	function dbinsert()
	{
		global $DB;

		$DB->begin( 'SERIALIZABLE' );

		if( parent::dbinsert() )
		{	// If the automation has been inserted successful:

			// Create first step automatically:
			$AutomationStep = new AutomationStep();
			$AutomationStep->set( 'autm_ID', $this->ID );
			$AutomationStep->set( 'type', 'if_condition' );
			$AutomationStep->set( 'yes_next_step_ID', 0 ); // Continue
			$AutomationStep->set( 'yes_next_step_delay', 0 ); // 0 seconds
			$AutomationStep->set( 'no_next_step_ID', -1, true ); // STOP
			$AutomationStep->set( 'error_next_step_ID', -1, true ); // STOP
			if( $AutomationStep->dbinsert() )
			{	// If first step has been inserted successfully:
				$DB->commit();
				return true;
			}
		}

		// Could not insert the automation object:
		$DB->rollback();
		return false;
	}


	/**
	 * Get name of automation
	 *
	 * @return string Name of automation
	 */
	function get_name()
	{
		return $this->get( 'name' );
	}


	/**
	 * Get user states for current time(automation steps which should be executed immediately)
	 *
	 * @return array Array( user_ID => next_step_ID )
	 */
	function get_user_states()
	{
		global $DB, $servertimenow;

		if( empty( $this->ID ) )
		{	// Automation must be stored in DB:
			return array();
		}

		$SQL = new SQL( 'Get user states for current time of automation #'.$this->ID );
		$SQL->SELECT( 'aust_user_ID, aust_next_step_ID' );
		$SQL->FROM( 'T_automation__user_state' );
		$SQL->WHERE( 'aust_autm_ID = '.$this->ID );
		$SQL->WHERE_and( 'aust_next_step_ID IS NOT NULL ' );
		$SQL->WHERE_and( 'aust_next_exec_ts <= '.$DB->quote( date2mysql( $servertimenow ) ) );

		return $DB->get_assoc( $SQL );
	}


	/**
	 * Get owner User
	 *
	 * @return object|NULL|boolean Reference on cached owner User object, NULL - if request with empty ID, FALSE - if requested owner User does not exist
	 */
	function & get_owner_User()
	{
		if( $this->owner_User === NULL )
		{	// Load owner User into cache var:
			$UserCache = & get_UserCache();
			$this->owner_User = & $UserCache->get_by_ID( $this->owner_user_ID, false, false );
		}

		return $this->owner_User;
	}


	/**
	 * Get Newsletter
	 *
	 * @return object|NULL|boolean Reference on cached owner User object, NULL - if request with empty ID, FALSE - if requested owner User does not exist
	 */
	function & get_Newsletter()
	{
		if( $this->Newsletter === NULL )
		{	// Load Newsletter into cache var:
			$NewsletterCache = & get_NewsletterCache();
			$this->Newsletter = & $NewsletterCache->get_by_ID( $this->get( 'enlt_ID' ), false, false );
		}

		return $this->Newsletter;
	}


	/**
	 * Add users to this automation
	 *
	 * @param array IDs of users
	 * @param array Params
	 * @return integer Number of added users
	 */
	function add_users( $user_IDs, $params = array() )
	{
		global $DB, $servertimenow;

		$params = array_merge( array(
				'users_no_subs'   => 'ignore', // Action for users who are not subscribed to Newsletter of this Automation: 'ignore' - Ignore, 'add' - Add anyway
				'users_automated' => 'ignore', // Action for users who are already in this Automation: 'ignore' - Ignore, 'requeue' - Requeue to Start
				'users_new'       => 'add', // Action for new users: 'ignore' - Ignore, 'add' - Add to automation
			), $params );

		$added_users_num = 0;

		if( empty( $this->ID ) )
		{	// Automation must be stored in DB:
			return $added_users_num;
		}

		if( empty( $user_IDs ) )
		{	// No users to add:
			return $added_users_num;
		}

		if( $params['users_no_subs'] == 'ignore' )
		{	// Ignore not subscribed users to this Automation:
			$no_subs_SQL = new SQL( 'Get not subscribed users of the Automation #'.$this->ID );
			$no_subs_SQL->SELECT( 'user_ID' );
			$no_subs_SQL->FROM( 'T_users' );
			$no_subs_SQL->FROM_add( 'LEFT JOIN T_email__newsletter_subscription ON enls_user_ID = user_ID AND enls_enlt_ID = '.$this->get( 'enlt_ID' ) );
			$no_subs_SQL->WHERE( 'user_ID IN ( '.$DB->quote( $user_IDs ).' )' );
			$no_subs_SQL->WHERE_and( 'enls_subscribed = 0 OR enls_user_ID IS NULL' );
			// Remove not subscribed users from array:
			$user_IDs = array_diff( $user_IDs, $DB->get_col( $no_subs_SQL ) );
		}
		// else: Add not subscribed users anyway

		$automated_SQL = new SQL( 'Get users of the Automated #'.$this->ID );
		$automated_SQL->SELECT( 'aust_user_ID' );
		$automated_SQL->FROM( 'T_automation__user_state' );
		$automated_SQL->WHERE( 'aust_autm_ID = '.$this->ID );
		$automated_SQL->WHERE_and( 'aust_user_ID IN ( '.$DB->quote( $user_IDs ).' )' );
		$automated_user_IDs = $DB->get_col( $automated_SQL );

		// Remove already automated users from array:
		$user_IDs = array_diff( $user_IDs, $automated_user_IDs );


		if( count( $automated_user_IDs ) || count( $user_IDs ) )
		{	// Get first Step of this Automation:
			$first_step_SQL = new SQL( 'Get first step of automation #'.$this->ID );
			$first_step_SQL->SELECT( 'step_ID' );
			$first_step_SQL->FROM( 'T_automation__step' );
			$first_step_SQL->WHERE( 'step_autm_ID = '.$this->ID );
			$first_step_SQL->ORDER_BY( 'step_order ASC' );
			$first_step_SQL->LIMIT( 1 );
			$first_step_ID = intval( $DB->get_var( $first_step_SQL ) );
		}

		if( empty( $first_step_ID ) )
		{	// No detected first step or no users to add or requeue:
			return $added_users_num;
		}

		if( $params['users_automated'] == 'requeue' && count( $automated_user_IDs ) )
		{	// Requeue already automated users to first Step:
			$added_users_num += $DB->query( 'UPDATE T_automation__user_state 
				  SET aust_next_step_ID = '.$DB->quote( $first_step_ID ).',
				      aust_next_exec_ts = '.$DB->quote( date2mysql( $servertimenow ) ).'
				WHERE aust_autm_ID = '.$DB->quote( $this->ID ).'
				  AND aust_user_ID IN ( '.$DB->quote( $automated_user_IDs ).' )',
				'Requeue already automated users to first Step from users list' );
		}
		// else:  Ignore already automated users

		if( $params['users_new'] == 'add' && count( $user_IDs ) )
		{	// Add new users to this Automation:
			$insert_sql = array();
			foreach( $user_IDs as $user_ID )
			{
				$insert_sql[] = '( '.$DB->quote( $this->ID ).', '.$DB->quote( $user_ID ).', '.$DB->quote( $first_step_ID ).', '.$DB->quote( date2mysql( $servertimenow ) ).' )';
			}
			$added_users_num += $DB->query( 'INSERT INTO T_automation__user_state ( aust_autm_ID, aust_user_ID, aust_next_step_ID, aust_next_exec_ts )
				VALUES '.implode( ', ', $insert_sql ),
				'Insert automation user states from users list' );
		}

		return $added_users_num;
	}
}

?>