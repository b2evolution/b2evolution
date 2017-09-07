<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @package messaging
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

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
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_messaging__thread', 'thrd_', 'thrd_ID', 'datemodified' );

		if( $db_row != NULL )
		{
			$this->ID           = $db_row->thrd_ID;
			$this->title        = $db_row->thrd_title;
			$this->datemodified = $db_row->thrd_datemodified;
		}
		else
		{	// New Thread
			global $Session;

			// erwin > Added check for $Session to enable creation of threads in cases where there is
			// no Session available such as generation of sample private conversation during install
			if( $Session )
			{	// check if there is unsaved Thread object stored in Session
				$unsaved_Thread = $Session->get( 'core.unsaved_Thread' );
				if( !empty( $unsaved_Thread ) )
				{	// unsaved thread exists, delete it from Session
					$Session->delete( 'core.unsaved_Thread' );
					$this->title = $unsaved_Thread['title'];
					$this->text = $unsaved_Thread['text'];
				}
			}

			$logins = array();

			$user_login = param( 'user_login', 'string', '' );
			if( !empty( $user_login ) )
			{	// Set recipient list from $user_login
				$logins[] = $user_login;
			}
			else
			{	// Set recipients from Contacts form
				global $DB, $current_User;

				$recipients = param( 'recipients', 'string', '' );
				$group_ID = param( 'group_ID', 'integer', 0 );

				if( !empty( $recipients ) )
				{	// Selected users
					$recipients = explode( ',', $recipients );
					foreach( $recipients as $r => $recipient )
					{
						if( (int)trim( $recipient ) == 0 )
						{	// remove bad data
							unset( $recipients[$r] );
						}
					}
					if( count( $recipients ) > 0 )
					{
						$SQL = new SQL();
						$SQL->SELECT( 'user_ID, user_login' );
						$SQL->FROM( 'T_messaging__contact' );
						$SQL->FROM_add( 'LEFT JOIN T_users ON mct_to_user_ID = user_ID' );
						$SQL->WHERE( 'mct_from_user_ID = '.$current_User->ID );
						$SQL->WHERE_and( 'mct_to_user_ID IN ('.implode( ',', $recipients ).')' );
						// asimo> If A user block B user it means that A user doesn't want to receive private message from B,
						// but A user still should be able to send a message to B.
						//$SQL->WHERE_and( 'mct_blocked = 0' );
						$SQL->ORDER_BY( 'user_login' );

						$logins = $DB->get_assoc( $SQL->get() );
					}
				}
				else if( $group_ID > 0 )
				{	// All users from one group
					$SQL = new SQL();
					$SQL->SELECT( 'user_ID, user_login' );
					$SQL->FROM( 'T_messaging__contact_groupusers' );
					$SQL->FROM_add( 'LEFT JOIN T_users ON cgu_user_ID = user_ID' );
					$SQL->FROM_add( 'LEFT JOIN T_messaging__contact_groups ON cgu_cgr_ID = cgr_ID' );
					$SQL->FROM_add( 'LEFT JOIN T_messaging__contact ON mct_from_user_ID = cgr_user_ID AND mct_to_user_ID = user_ID' );
					$SQL->WHERE( 'cgr_user_ID = '.$current_User->ID );
					$SQL->WHERE_and( 'cgr_ID = '.$DB->quote( $group_ID ) );
					// asimo> If A user block B user it means that A user doesn't want to receive private message from B,
					// but A user still should be able to send a message to B.
					//$SQL->WHERE_and( 'mct_blocked = 0' );
					$SQL->ORDER_BY( 'user_login' );

					$logins = $DB->get_assoc( $SQL->get() );
				}
			}

			$this->recipients = implode( ', ', $logins );

			if( !empty( $logins ) )
			{	// Set this var to initialize the preselected users for fbautocomplete jQuery plugin
				global $recipients_selected;
				foreach( $logins as $user_ID => $user_login )
				{
					$recipients_selected[] = array(
						'id'    => $user_ID,
						'login' => $user_login
					);
				}
			}
		}
	}


	/**
	 * Get this class db table config params
	 *
	 * @return array
	 */
	static function get_class_db_config()
	{
		static $thread_db_config;

		if( !isset( $thread_db_config ) )
		{
			$thread_db_config = array_merge( parent::get_class_db_config(),
				array(
					'dbtablename'        => 'T_messaging__thread',
					'dbprefix'           => 'thrd_',
					'dbIDname'           => 'thrd_ID',
				)
			);
		}

		return $thread_db_config;
	}


	/**
	 * Get delete cascade settings
	 *
	 * @return array
	 */
	static function get_delete_cascades()
	{
		return array(
				array( 'table'=>'T_messaging__message', 'fk'=>'msg_thread_ID', 'msg'=>T_('%d messages in thread'),
						'class'=>'Message', 'class_path'=>'messaging/model/_message.class.php' ),
				array( 'table'=>'T_messaging__threadstatus', 'fk'=>'tsta_thread_ID', 'msg'=>T_('%d read statuses in thread') ),
			);
	}


	/**
	 * Load data from Request form fields.
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		global $thrd_recipients, $thrd_recipients_array;

		// Recipients
		$this->set_string_from_param( 'recipients', empty( $thrd_recipients_array ) ? true : false );

		// Title
		param( 'thrd_title', 'string' );
		param_check_not_empty( 'thrd_title', T_('Please enter a subject') );
		$this->set_from_Request( 'title', 'thrd_title' );

		// Message
		param_check_not_empty( 'msg_text', T_('Please enter a message') );

		$this->param_check__recipients( 'thrd_recipients', $thrd_recipients, $thrd_recipients_array );

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
	 * Check if recipients available in database
	 *
	 * @param string Input name
	 * @param string Recipients logins separated with comma (Used for browsers without JavaScript)
	 * @param string Recipients logins in array format (Used with jQuery plugin fbautocomplete)
	 * @return boolean true if all recipients allow the current User to contact them, false otherwise
	 */
	function param_check__recipients( $var, $recipients, $recipients_array )
	{
		global $DB, $current_User, $UserSettings, $Messages;

		if( !empty( $recipients_array ) )
		{	// These data is created by jQuery plugin fbautocomplete
			$recipients_list = $recipients_array['login'];
		}
		else
		{	// For browsers without JavaScript
			// split recipients into array using comma separator
			$recipients_list = array();
			$recipients = trim( str_replace( ',', ' ', $recipients ) );
			foreach( explode(' ', $recipients) as $recipient )
			{
				$login = trim($recipient);
				if( ! empty( $login ) )
				{
					$recipients_list[] = utf8_strtolower( $login );
				}
			}
		}

		$recipients_list = array_unique( $recipients_list );

		$error_msg = '';

		// check has recipients list login of current user
		if( in_array( $current_User->login, $recipients_list ) )
		{
			$error_msg = sprintf( T_( 'You cannot send threads to yourself: %s' ), $current_User->login );
		}

		// load recipient User objects
		$UserCache = & get_UserCache();
		$UserCache->load_where( 'user_login IN ( "'.implode( '","', $recipients_list ).'" )' );

		// check are recipients available in database
		$this->recipients_list = array();
		$unavailable_recipients_list = array();
		$closed_recipients_list = array();
		$status_restricted_recipients = array();
		$recipients_without_perm = array();
		$recipients_from_different_country = array();
		$recipients_restricted_pm = array();
		// check if recipient user enable private messages only if sender user doesn't have 'delete' messaging permission
		$check_enable_pm = !$current_User->check_perm( 'perm_messaging', 'delete' );
		$cross_country_restrict = has_cross_country_restriction( 'contact' );
		foreach( $recipients_list as $recipient )
		{
			$recipient_User = $UserCache->get_by_login( $recipient, false );
			if( $recipient_User === false )
			{ // user doesn't exists
				$unavailable_recipients_list[] = $recipient;
				continue;
			}

			if( !$recipient_User->check_status( 'can_receive_pm' ) )
			{ // user status restrict to receive private messages
				if( $recipient_User->check_status( 'is_closed' ) )
				{ // user account was closed
					$closed_recipients_list[] = $recipient;
					continue;
				}

				$status_restricted_recipients[] = $recipient;
				continue;
			}

			if( !$recipient_User->check_perm( 'perm_messaging', 'reply' ) )
			{ // user doesn't have permission to read private messages
				$recipients_without_perm[] = $recipient;
				continue;
			}

			if( $cross_country_restrict && $current_User->ctry_ID != $recipient_User->ctry_ID )
			{ // user can contact with other users only from the same coutnry, but this recipient country is different
				$recipients_from_different_country[] = $recipient;
				continue;
			}

			if( !$UserSettings->get( 'enable_PM', $recipient_User->ID ) )
			{ // recipient doesn't want to receive private messages
				$recipients_restricted_pm[] = $recipient;
				if( $check_enable_pm )
				{ // sender is not a user with delete ( "admin" ) messaging permission, so this user can't be in the recipients list
					continue;
				}
			}

			// recipient is correct, add to recipient list
			$this->recipients_list[] = $recipient_User->ID;
		}

		if ( count( $unavailable_recipients_list ) > 0 )
		{
			if ( ! empty( $error_msg ) )
			{
				$error_msg .= '<br />';
			}
			$error_msg .= sprintf( 'The following users were not found: %s', implode( ', ', $unavailable_recipients_list ) );
		}

		if ( count( $closed_recipients_list ) > 0 )
		{
			if ( ! empty( $error_msg ) )
			{
				$error_msg .= '<br />';
			}
			$error_msg .= sprintf( 'The following users no longer exist: %s', implode( ', ', $closed_recipients_list ) );
		}

		if ( count( $status_restricted_recipients ) > 0 )
		{
			if ( ! empty( $error_msg ) )
			{
				$error_msg .= '<br />';
			}
			$error_msg .= sprintf( 'The following users status currently does not permit to receive private messages: %s', implode( ', ', $status_restricted_recipients ) );
		}

		if ( count( $recipients_without_perm ) > 0 )
		{
			if ( ! empty( $error_msg ) )
			{
				$error_msg .= '<br />';
			}
			$error_msg .= sprintf( 'The following users have no permission to read private messages: %s', implode( ', ', $recipients_without_perm ) );
		}

		if( count( $recipients_from_different_country ) > 0 )
		{
			if ( ! empty( $error_msg ) )
			{
				$error_msg .= '<br />';
			}
			$error_msg .= sprintf( 'You are not allowed to contact with the following users: %s', implode( ', ', $recipients_from_different_country ) );
		}

		$restricted_pm_count = count( $recipients_restricted_pm );
		if ( $restricted_pm_count > 0 )
		{ // there is at least one recipient who doesn't want to receive private messages
			if( $check_enable_pm )
			{ // sender is not a user with delete ( "admin" ) messaging permission, so this user can't be in the recipients list
				if ( ! empty( $error_msg ) )
				{
					$error_msg .= '<br />';
				}
				$error_msg .= sprintf( 'The following users don\'t want to receive private messages: %s', implode( ', ', $recipients_restricted_pm ) );
			}
			else
			{ // send is an admin
				$manual_link = get_manual_link( 'messaging', T_( 'See manual' ).'.' );
				if( $restricted_pm_count > 1 )
				{ // more then one recipient don't want to receive private messages
					$note = sprintf( T_( 'Users &laquo;%s&raquo; do not allow receiving private messages. Message has been sent anyway because you are an administrator.' ), implode( ', ', $recipients_restricted_pm ) );
				}
				else
				{ // one recipient doesn't want to receive private messages
					$note = sprintf( T_( 'User &laquo;%s&raquo; does not allow receiving private messages. Message has been sent anyway because you are an administrator.' ), $recipients_restricted_pm[0] );
				}
				// add note
				$Messages->add( $note.$manual_link, 'note' );
			}
		}

		// Here we select those recipients who has blocked the sender. Note that users with 'delete' messaging permission can't be blocked!
		$blocked_contacts = check_blocked_contacts( $this->recipients_list );
		if( !empty( $blocked_contacts ) )
		{ // There is at least one blocked recipient
			if ( ! empty( $error_msg ) )
			{
				$error_msg .= '<br />';
			}
			$error_msg .= T_( 'The following users don\'t want you to contact them at this time: ' ).' '.implode( ', ', $blocked_contacts );
		}

		if( empty( $error_msg ) )
		{ // no errors yet
			$recipients_count = count( $recipients_list );
			if( ( $recipients_count > 1 ) && ( param( 'thrdtype', 'string', 'discussion' ) != 'discussion' ) )
			{ // user want's to send more then one individual messages, check if is allowed
				list( $max_new_threads, $new_threads_count ) = get_todays_thread_settings();
				if( ( !empty( $max_new_threads ) ) && ( ( $max_new_threads - $new_threads_count ) < $recipients_count ) )
				{ // user has a create thread limit, and recipients number exceed that limit
					$error_msg .= '<br />';
					$error_msg .= sprintf( T_( 'You are unable to send %d individual messages, because it exceeds your remaining daily limit of %d.' ), $recipients_count, $max_new_threads - $new_threads_count );
				}
			}
		}

		if( ! empty( $error_msg ) )
		{	// show error
			param_error( $var, $error_msg );
			return false;
		}

		return true;
	}


	/**
	 * Delete thread and dependencies from database
	 */
	function dbdelete()
	{
		if( $this->ID == 0 ) debug_die( 'Non persistant object cannot be deleted!' );

		return parent::dbdelete();
	}


	/**
	 * Load recipients of the current thread
	 *
	 * @return recipients list
	 */
	function load_recipients()
	{
		global $DB;

		if( empty( $this->recipients_list ) && ( !empty( $this->ID ) ) )
		{
			$SQL = new SQL();
			$SQL->SELECT( 'tsta_user_ID' );
			$SQL->FROM( 'T_messaging__threadstatus' );
			$SQL->WHERE( 'tsta_thread_ID = '.$this->ID );

			$this->recipients_list = array();
			foreach( $DB->get_results( $SQL->get() ) as $row )
			{
				$this->recipients_list[] = $row->tsta_user_ID;
			}
		}

		return $this->recipients_list;
	}


	/**
	 * Check permission on a persona
	 *
	 * @return boolean true if granted
	 */
	function check_perm( $action, $assert = true )
	{
		global $current_User;

		return $current_User->check_perm( 'perm_messaging', $action, $assert );
	}


	/**
	 * Check if user is recipient of the current thread
	 *
	 * @param user ID
	 * @return boolean true if user is recipient, false otherwise
	 */
	function check_thread_recipient( $user_ID )
	{
		$this->load_recipients();
		return in_array( $user_ID, $this->recipients_list );
	}


	/**
	 * Check if current User is allowed to reply on this thread.
	 * Users are allowed to reply only to those threads where they are involved and there is at least one user between the recipients who didn't block the User.
	 * Note: Currently it doesn't matter if the sender is in the recipients user's contact list or not except in that case when the sender is blocked.
	 *
	 * @return boolean true if current User is allowed, false otherwise
	 */
	function check_allow_reply()
	{
		global $DB, $current_User;

		// load thread recipients
		$this->load_recipients();

		// check if user is involved in recipients list
		if( ! $this->check_thread_recipient( $current_User->ID ) )
		{ // Deny to write a new reply for not involved users
			// asimo> We may call debug_die() here because this is not a correct state of the application
			param_error( '', T_('You cannot post a message in a thread you\'re not involved in.') );
			return false;
		}

		// check if all of the recipients are closed
		$UserCache = & get_UserCache();
		$UserCache->load_where( 'user_ID IN ( '.implode( ',', $this->recipients_list ).' )' );
		$all_closed = true;
		foreach( $this->recipients_list as $recipient_ID )
		{
			if( $recipient_ID == $current_User->ID )
			{ // skip current User
				continue;
			}
			$recipient_User = $UserCache->get_by_ID( $recipient_ID, false );
			if( $recipient_User && $recipient_User->check_status( 'can_receive_pm' ) )
			{ // this recipient exists and status allows to receive private messages
				$all_closed = false;
				break;
			}
		}
		if( $all_closed )
		{ // all recipients are closed or deleted
			param_error( '', T_( 'You cannot reply because all the other users involved in this conversation have closed their account.' ) );
			return false;
		}

		if( $current_User->check_perm( 'perm_messaging', 'delete' ) )
		{ // users with delete permission are always able to reply to a conversation where they are involved
			return true;
		}

		$SQL = new SQL();

		$SQL->SELECT( 'count( ts.tsta_user_ID )' );
		$SQL->FROM( 'T_messaging__threadstatus ts
								LEFT JOIN T_messaging__contact mc ON ts.tsta_user_ID = mc.mct_from_user_ID
											AND mc.mct_to_user_ID = '.$current_User->ID );
		// don't select current User
		$SQL->WHERE( 'ts.tsta_user_ID <> '.$current_User->ID );
		// restrict to the given thread
		$SQL->WHERE_and( 'ts.tsta_thread_ID ='.$this->ID );
		// sender is not blocked or is not present in all recipient's contact list
		$SQL->WHERE_and( '( mc.mct_blocked IS NULL OR mc.mct_blocked = 0 )' );

		if( $DB->get_var( $SQL->get(), 0, NULL, 'Count all users whou are involved in the given thread but not the current User and didn\'t block the current User' ) > 0 )
		{ // there is at least one recipient who accept the reply
			return true;
		}

		// all recipients have blocked the current User
		param_error( '', T_( 'The recipient(s) do not want you to contact them at this time.' ) );
		return false;
	}
}

?>