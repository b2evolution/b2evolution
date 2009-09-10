<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class('_core/model/dataobjects/_dataobject.class.php');

/**
 * Thread Class
 *
 */
class Thread extends DataObject
{
	var $title = '';
	var $datemodified;
	var $recipients = '';

	/**
	 * Number unread messages
	 * @var integer
	 */
	var $num_unread_messages;

	/**
	 * Recipients IDs lazy filled
	 *
	 * @var array
	 */
	var $recipients_list;

	/**
	 * Constructor
	 * @param db_row database row
	 */
	function Thread( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_messaging__thread', 'thrd_', 'thrd_ID', 'datemodified' );

		$this->delete_restrictions = array();
  		$this->delete_cascades = array();

 		if( $db_row != NULL )
		{
			$this->ID           = $db_row->thrd_ID;
			$this->title        = $db_row->thrd_title;
			$this->datemodified = $db_row->thrd_datemodified;
		}
	}

	/**
	 * Load data from Request form fields.
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		global $thrd_recipients;

		// Title
		$this->set_string_from_param( 'title', true );

		// Resipients
		$this->set_string_from_param( 'recipients', true );

		$unavailable_recipients_list = $this->find_recipients( $thrd_recipients );
		if( $unavailable_recipients_list )
		{
			param_error( 'recipients', 'The following users were not found: '.implode( ', ', $unavailable_recipients_list ) );
		}

		return ! param_errors_detected();
	}

	/**
	 * Set param value
	 *
	 * By default, all values will be considered strings
	 *
	 * @param string parameter name
	 * @param mixed parameter value
	 * @param boolean true to set to NULL if empty value
	 * @return boolean true, if a value has been set; false if it has not changed
	 */
	function set( $parname, $parvalue, $make_null = false )
	{
		switch( $parname )
		{
			case 'recipients':
				$this->recipients = $parvalue;
				break;
			case 'title':
			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}

	/**
	 * Select available recipients from database
	 *
	 * @todo Tblue> This currently leads to a "user not found" message if
	 *              the current user is specified as a recipient; this is
	 *              confusing -- it should display a message like "You cannot
	 *              send threads to yourself.".
	 * 
	 * @param string $recipients
	 */
	function find_recipients ( $recipients )
	{
		global $DB, $current_User;

		$recipients_list = array();
		foreach ( explode(',', $recipients) as $recipient )
		{
			$recipients_list[] = strtolower(trim($recipient));
		}

		$db_users_list = array();
		foreach( $DB->get_results( 'SELECT user_ID, user_login
									FROM T_users
										WHERE user_ID != '.$current_User->ID) as $row )
		{
			$db_users_list[$row->user_login] = $row->user_ID;
		}

		$this->recipients_list = array();
		$unavailable_recipients_list = array();
		foreach( $recipients_list as $recipient )
		{
			if( array_key_exists( $recipient, $db_users_list ) )
			{
				$this->recipients_list[$db_users_list[$recipient]] = $row->user_ID;
			}
			else
			{
				$unavailable_recipients_list[] = $recipient;
			}
		}

		return count( $unavailable_recipients_list ) > 0 ? $unavailable_recipients_list : false;
	}

	/**
	 * Load recipients from database
	 */
	function load_recipients()
	{
		global $DB, $current_User;

		foreach( $DB->get_results( 'SELECT msta_user_ID
									FROM T_messaging__msgstatus
										WHERE msta_thread_ID = '.$this->ID.'
										AND msta_user_ID != '.$current_User->ID.'
										GROUP BY msta_user_ID') as $row )
		{
			$this->recipients_list[] = $row->msta_user_ID;
		}
	}

	/**
	 * Delete thread and dependencies from database
	 */
	function dbdelete()
	{
		global $DB;

		if( $this->ID == 0 ) debug_die( 'Non persistant object cannot be deleted!' );

		$DB->begin();

		// Delete Messages
		$ret = $DB->query( 'DELETE FROM T_messaging__message
												WHERE msg_thread_ID='.$this->ID );
		// Delete Statuses
		$ret = $DB->query( 'DELETE FROM T_messaging__msgstatus
												WHERE msta_thread_ID='.$this->ID );
		// Delete Thread
		if( ! parent::dbdelete() )
		{
			$DB->rollback();

			return false;
		}

		$DB->commit();

		return true;
	}
}

?>
