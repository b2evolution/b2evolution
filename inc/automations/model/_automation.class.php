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
	var $owner_user_ID;

	var $newsletters = NULL;
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
			$this->owner_user_ID = $db_row->autm_owner_user_ID;
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
				array( 'table' => 'T_automation__newsletter', 'fk' => 'aunl_autm_ID', 'msg' => T_('%d automation associations with lists') ),
			);
	}


	/**
	 * Get delete restriction settings
	 *
	 * @return array
	 */
	static function get_delete_restrictions()
	{
		return array(
				array( 'table' => 'T_automation__step', 'fk' => 'step_info', 'and_condition' => 'step_type = "start_automation" AND step_autm_ID != $this_ID$', 'msg' => T_('Automation is used by %s steps of other automations') ),
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
			// Update newsletters links with this Automation:
			$this->update_newsletters();
		}

		return $r;
	}


	/**
	 * Update the DB based on previously recorded changes
	 *
	 * @return boolean true on success
	 */
	function dbupdate()
	{
		$r = parent::dbupdate();

		// NOTE: Don't a result of dbupdate() to update newsletters links,
		// because it can be false if ONLY newsletters links have been changed to the edit submitted form:
		// Update newsletters links with this Automation:
		$this->update_newsletters();

		return $r;
	}


	/**
	 * Update newsletters links with Automation
	 *
	 * @return integer|boolean A count of new inserted links between automation and newsletters, FALSE - on wrong insert data
	 */
	function update_newsletters()
	{
		if( empty( $this->update_newsletters ) )
		{	// This action is not requested:
			return false;
		}

		if( empty( $this->newsletters ) )
		{	// At least one newsletter must be defined:
			return false;
		}

		$sql_newsletters_values = array();
		$aunl_order = 1;
		foreach( $this->newsletters as $newsletter )
		{
			$newsletter_ID = intval( $newsletter['ID'] );
			if( empty( $newsletter_ID ) )
			{	// Skip wrong newsletter data:
				continue;
			}
			// Build array with newsletter ID as key to avoid duplicate entry mysql error:
			$sql_newsletters_values[ $newsletter_ID ] = '( '.$this->ID.', '.$newsletter_ID.', '.intval( $newsletter['autostart'] ).', '.intval( $newsletter['autoexit'] ).', '.( $aunl_order++ ).' )';
		}

		if( empty( $sql_newsletters_values ) )
		{	// At least one newsletter must be inserted:
			return false;
		}

		global $DB;

		// Delete previous newsletter links:
		$DB->query( 'DELETE FROM T_automation__newsletter
			WHERE aunl_autm_ID = '.$this->ID );

		// Insert new newsletter links for this automation:
		$r = $DB->query( 'INSERT INTO T_automation__newsletter ( aunl_autm_ID, aunl_enlt_ID, aunl_autostart, aunl_autoexit, aunl_order ) 
			VALUES '.implode( ', ', $sql_newsletters_values ) );

		return $r;
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

		// Tied to Lists:
		$prev_newsletters = $this->get_newsletters();
		$updated_newsletters = array();
		$aunl_enlt_IDs = param( 'aunl_enlt_ID', 'array:integer', array() );
		foreach( $aunl_enlt_IDs as $n => $aunl_enlt_ID )
		{
			if( $aunl_enlt_ID > 0 )
			{
				$updated_newsletters[ $aunl_enlt_ID ] = array(
						'ID'        => (string)$aunl_enlt_ID,
						'autostart' => (string)param( 'aunl_autostart_'.$n, 'integer', 0 ),
						'autoexit'  => (string)param( 'aunl_autoexit_'.$n, 'integer', 0 ),
					);
			}
		}
		if( empty( $updated_newsletters ) )
		{	// Display an error and fill first required tied list:
			param_error( 'aunl_enlt_ID[]', T_('Please select an automation list.') );
			$this->newsletters = array( array(
					'ID'        => '0',
					'autostart' => param( 'aunl_autostart_0', 'integer', 0 ),
					'autoexit'  => param( 'aunl_autoexit_0', 'integer', 0 ),
				) );
		}
		else
		{	// Update newsletters array with new entered values:
			$updated_newsletters = array_values( $updated_newsletters );
			if( $prev_newsletters !== $updated_newsletters )
			{	// Set flag to update newsletters:
				$this->update_newsletters = true;
				$this->newsletters = $updated_newsletters;
			}
		}

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

		return ! param_errors_detected();
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
	 * @param integer Number of newsletter
	 * @return object|NULL|boolean Reference on cached Newsletter object, NULL - if request with empty ID, FALSE - if requested Newsletter does not exist
	 */
	function & get_Newsletter( $number = 1 )
	{
		$newsletters = $this->get_newsletters();

		if( ! isset( $newsletters[ $number ] ) || ! isset( $newsletters[ $number ]['ID'] ) )
		{	// No detected newsletter by number:
			$r = false;
			return $r;
		}

		// Get Newsletter from DB or cache:
		$NewsletterCache = & get_NewsletterCache();
		$Newsletter = & $NewsletterCache->get_by_ID( $newsletters[ $number ]['ID'], false, false );

		return $Newsletter;
	}


	/**
	 * Get settings of all newsletters for this Automation
	 *
	 * @return array Newsletters array of array with keys: 'ID', 'autostart', 'autoexit'
	 */
	function get_newsletters()
	{
		if( $this->newsletters === NULL )
		{	// Load newsletters settings once:
			if( empty( $this->ID ) )
			{	// Set default first newsletter setting:
				$this->newsletters = array( array( 'ID' => '', 'autostart' => 1, 'autoexit' => 1 ) );
			}
			else
			{	// Get newsletters settings from DB:
				global $DB;
				$SQL = new SQL( 'Load newsletters settings for Automation #'.$this->ID );
				$SQL->SELECT( 'aunl_enlt_ID AS ID, aunl_autostart AS autostart, aunl_autoexit AS autoexit' );
				$SQL->FROM( 'T_automation__newsletter' );
				$SQL->WHERE( 'aunl_autm_ID = '.$this->ID );
				$SQL->ORDER_BY( 'aunl_order' );
				$this->newsletters = $DB->get_results( $SQL, ARRAY_A );
			}
		}

		return $this->newsletters;
	}


	/**
	 * Get IDs of all newsletters of this Automation
	 *
	 * @return array Newsletters IDs
	 */
	function get_newsletter_IDs()
	{
		$newsletters = $this->get_newsletters();

		$newsletter_IDs = array();
		foreach( $newsletters as $newsletter )
		{
			$newsletter_IDs[] = $newsletter['ID'];
		}

		return $newsletter_IDs;
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
				'newsletter_IDs'   => NULL, // Newsletter IDs to ignore not subscribed users, NULL - to use any tied newsletter
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

			// Get newsletter IDs:
			$newsletter_IDs = ( $params['newsletter_IDs'] === NULL ? $this->get_newsletter_IDs() : $params['newsletter_IDs'] );
			if( ! is_array( $newsletter_IDs ) )
			{	// If single newsletter is given:
				$newsletter_IDs = array( $newsletter_IDs );
			}

			$no_subs_SQL = new SQL( 'Get not subscribed users of the Automation #'.$this->ID );
			$no_subs_SQL->SELECT( 'user_ID' );
			$no_subs_SQL->FROM( 'T_users' );
			$no_subs_SQL->FROM_add( 'LEFT JOIN T_email__newsletter_subscription ON enls_user_ID = user_ID AND enls_enlt_ID IN ( '.$DB->quote( $newsletter_IDs ).' )' );
			$no_subs_SQL->WHERE( 'user_ID IN ( '.$DB->quote( $user_IDs ).' )' );
			$no_subs_SQL->WHERE_and( 'enls_subscribed = 0 OR enls_user_ID IS NULL' );
			// Remove not subscribed users from array:
			$user_IDs = array_diff( $user_IDs, $DB->get_col( $no_subs_SQL ) );
		}
		// else: Add not subscribed users anyway

		if( empty( $user_IDs ) )
		{	// No users to add, Stop here:
			return $added_users_num;
		}

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


	/**
	 * Check if user is subscribed to at least one tied Newsletter of this Automation
	 *
	 * @param integer User ID
	 * @return integer|boolean ID of first tied newsletter where the requested user is subscribed, FALSE - user is not subscribed
	 */
	function is_user_subscribed( $user_ID )
	{
		$newsletter_IDs = $this->get_newsletter_IDs();

		if( empty( $newsletter_IDs ) )
		{	// No automation newsletters found:
			return false;
		}

		$NewsletterCache = & get_NewsletterCache();
		// Preload all automation newsletters by single query:
		$NewsletterCache->load_list( $newsletter_IDs );

		foreach( $newsletter_IDs as $newsletter_ID )
		{
			if( ( $automation_Newsletter = & $NewsletterCache->get_by_ID( $newsletter_ID, false, false ) ) &&
			    in_array( $user_ID, $automation_Newsletter->get_user_IDs() ) )
			{	// If user is subscribed to first tied newsletter, Stop find other subscriptions:
				return $automation_Newsletter->ID;
			}
		}

		// User is not subscribed to any tied newsletter of this Automation:
		return false;
	}
}

?>