<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009-2014 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * {@internal Open Source relicensing agreement:
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package messaging
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-maxim: Evo Factory / Maxim.
 * @author fplanque: Francois Planque.
 *
 * @version $Id: _message.class.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * Message Class
 *
 */
class Message extends DataObject
{
	/**
	 * Available message statuses constants
	 *
	 * @internal Tblue> Class constants are PHP5-only!
	 */
	var $AUTHOR = 0;
	var $READ = 1;
	var $UNREAD = 2;

	var $thread_ID;
	var $author_user_ID;
	var $author_name = '';
	var $datetime = '';

	/**
	 * The content of the message
	 * WARNING: It may contains MALICIOUS HTML and javascript snippets. They must ALWAYS be ESCAPED prior to display!
	 * 
	 * @var string
	 */
	var $text = '';

	/**
	 * Thread lazy filled
	 *
	 * @var instance of Thread class
	 */
	var $Thread;


	/**
	 * Constructor
	 *
	 * @param db_row database row
	 */
	function Message( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_messaging__message', 'msg_', 'msg_ID', 'datetime', '', 'author_user_ID' );

  		$this->delete_cascades = array();
  		$this->delete_restrictions = array();

 		if( $db_row != NULL )
		{
			$this->ID                = $db_row->msg_ID;
			$this->thread_ID         = $db_row->msg_thread_ID;
			$this->author_user_ID    = $db_row->msg_author_user_ID;
			$this->datetime          = $db_row->msg_datetime;
			$this->text              = $db_row->msg_text;
		}
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		$new_thread = empty($this->thread_ID);

		// Text
		// WARNING: the messages may contain MALICIOUS HTML and javascript snippets. They must ALWAYS be ESCAPED prior to display!
		param( 'msg_text', 'html' );
		if( ! $new_thread )
		{
			param_check_not_empty( 'msg_text' );
		}
		$this->set( 'text', get_param( 'msg_text' ) );

		// Thread
		if( $new_thread )
		{
			$this->Thread->load_from_Request();
		}
		else
		{ // this is a reply to an existing conversation, check if current User is allowed to reply
			$this->get_Thread();
			$this->Thread->check_allow_reply();
		}

		return ! param_errors_detected();
	}


	/**
	 * Get Thread object
	 */
	function & get_Thread()
	{
		if( is_null($this->Thread) && !empty($this->thread_ID) )
		{
			$ThreadCache = & get_ThreadCache();
			$this->Thread = $ThreadCache->get_by_ID( $this->thread_ID );
		}

		return $this->Thread;
	}


	/**
	 * Insert discussion (one thread for all recipients)
	 *
	 * @param User who sent the message, it must be set only if it is not the current User
	 * @return true if success, false otherwise
	 */
	function dbinsert_discussion( $from_User = NULL )
	{
		global $DB;

		if( $this->ID != 0 ) die( 'Existing object cannot be inserted!' );

		$DB->begin();

		$this->get_Thread();

		if ( $this->Thread->dbinsert() )
		{
			$this->set_param( 'thread_ID', 'integer', $this->Thread->ID);

			if( parent::dbinsert() )
			{
				if( $this->dbinsert_threadstatus( $this->Thread->recipients_list ) )
				{
					if( $this->dbinsert_contacts( $this->Thread->recipients_list ) )
					{
						if( $this->dbupdate_last_contact_datetime() )
						{
							$DB->commit();

							$this->send_email_notifications( true, $from_User );
							return true;
						}
					}
				}
			}
		}

		$DB->rollback();
		return false;
	}


	/**
	 * Insert new thread for each recipient
	 *
	 * @param User who sent the message, it must be set only if it is not the current User
	 * @return true if success, instead false
	 */
	function dbinsert_individual( $from_User = NULL )
	{
		foreach( $this->Thread->recipients_list as $recipient_ID )
		{
			$message = $this->clone_message( $this );

			$message->Thread->recipients_list = array( $recipient_ID );

			if ( !$message->dbinsert_discussion( $from_User ) )
			{
				return false;
			}
		}

		return true;
	}


	/**
	 * Insert message in existing thread
	 *
	 * @return true if success, instead false
	 */
	function dbinsert_message()
	{
		global $DB, $localtimenow;

		if( $this->ID != 0 ) die( 'Existing object cannot be inserted!' );

		$DB->begin();

		$this->get_Thread();

		$this->Thread->set_param( 'datemodified', 'string', date( 'Y-m-d H:i:s', $localtimenow ) );

		if( $this->Thread->dbupdate() )
		{
			$this->set_param( 'thread_ID', 'integer', $this->Thread->ID);

			if( parent::dbinsert() )
			{
				$sql = 'UPDATE T_messaging__threadstatus
						SET tsta_first_unread_msg_ID = '.$this->ID.'
						WHERE tsta_thread_ID = '.$this->Thread->ID.'
							AND tsta_user_ID <> '.$this->author_user_ID.'
							AND tsta_first_unread_msg_ID IS NULL';

				$DB->query( $sql, 'Insert thread statuses' );

				// check if contact pairs between sender and recipients exists
				$recipient_list = $this->Thread->load_recipients();
				// remove author user from recipient list
				$recipient_list = array_diff( $recipient_list, array( $this->author_user_ID ) );
				// insert missing contact pairs if required
				if( $this->dbinsert_contacts( $recipient_list ) )
				{
					if( $this->dbupdate_last_contact_datetime() )
					{
						$DB->commit();

						$this->send_email_notifications( false );
						return true;
					}
				}
			}
		}

		$DB->rollback();
		return false;
	}


	/**
	 * Insert recipients into database
	 *
	 * @param recipients
	 * @return true if success, instead false
	 */
	function dbinsert_threadstatus( $recipients_list )
	{
		global $DB;

		$sql = 'INSERT INTO T_messaging__threadstatus (tsta_thread_ID, tsta_user_ID, tsta_first_unread_msg_ID)
							VALUES';

		foreach ( $recipients_list as $recipient_ID )
		{
			$sql .= ' ('.$this->Thread->ID.', '.$recipient_ID.', '.$this->ID.'),';
		}
		$sql .= ' ('.$this->Thread->ID.', '.$this->author_user_ID.', NULL)';

		return $DB->query( $sql, 'Insert thread statuses' );
	}


	/**
	 * Insert contacts into database
	 *
	 * @param recipients
	 * @return true if success, instead false
	 */
	function dbinsert_contacts( $recipients )
	{
		global $DB, $localtimenow;

		// select contacts of the current user
		$SQL = new SQL();

		$SQL->SELECT( 'mct_to_user_ID' );
		$SQL->FROM( 'T_messaging__contact' );
		$SQL->WHERE( 'mct_from_user_ID = '.$this->author_user_ID );

		$contact_list = array();
		foreach( $DB->get_results( $SQL->get() ) as $row )
		{
			$contact_list[] = $row->mct_to_user_ID;
		}

		// get users/recipients which are not in contact list
		$contact_list = array_diff( $recipients, $contact_list );

		// select users who have author User on their contact list
		$SQL = new SQL();

		$SQL->SELECT( 'mct_from_user_ID' );
		$SQL->FROM( 'T_messaging__contact' );
		$SQL->WHERE( 'mct_to_user_ID = '.$this->author_user_ID );

		$reverse_contact_list = array();
		foreach( $DB->get_results( $SQL->get() ) as $row )
		{
			$reverse_contact_list[] = $row->mct_from_user_ID;
		}

		// get users/recipients which are not in reverse contact list
		$reverse_contact_list = array_diff( $recipients, $reverse_contact_list );

		if( !empty( $contact_list ) || !empty( $reverse_contact_list ) )
		{	// insert users/recipients which are not in contact list

			$sql = 'INSERT INTO T_messaging__contact (mct_from_user_ID, mct_to_user_ID, mct_last_contact_datetime)
								VALUES';

			$datetime = date( 'Y-m-d H:i:s', $localtimenow );

			$statements = array();
			foreach ( $contact_list as $contact_ID )
			{
				$statements[] = ' ('.$this->author_user_ID.', '.$contact_ID.', \''.$datetime.'\')';
			}
			foreach ( $reverse_contact_list as $contact_ID )
			{
				$statements[] = ' ('.$contact_ID.', '.$this->author_user_ID.', \''.$datetime.'\')';
			}
			$sql .= implode( ', ', $statements );

			return $DB->query( $sql, 'Insert contacts' );
		}

		return true;
	}


	/**
	 * Update last contact datetimes
	 *
	 * @return true if success
	 */
	function dbupdate_last_contact_datetime()
	{
		global $DB, $localtimenow;

		// efy-maxim> TODO: two SQL queries are used instead one update with subselect,
		// because T_messaging__threadstatus alias is not converted to real table name.
		// Also, it can't be improved right now because it depends of
		// (pls. see blueyed's comment for $DB->query() function)

		$select_SQL = new SQL();
		$select_SQL->SELECT( 'GROUP_CONCAT(tsta_user_ID SEPARATOR \',\')' );
		$select_SQL->FROM( 'T_messaging__threadstatus' );
		$select_SQL->WHERE( 'tsta_thread_ID = '.$this->Thread->ID );

		$recipients = $DB->get_var( $select_SQL->get() );

		$datetime = date( 'Y-m-d H:i:s', $localtimenow );

		$update_sql = 'UPDATE T_messaging__contact
					SET mct_last_contact_datetime = \''.$datetime.'\'
					WHERE mct_from_user_ID = '.$this->author_user_ID.'
						AND mct_to_user_ID IN ('.$recipients.')';

		$DB->query( $update_sql, 'Update last contact datetimes' );

		return true;
	}


	/**
	 * Clone current message and convert cloned message from 'individual' to 'discussion'.
	 *
	 * @param instance of Message class
	 * @return cloned message
	 */
	function clone_message( $message )
	{
		$new_Message = new Message();
		$new_Message->set( 'text', $message->text );
		if( !empty( $message->author_user_ID ) )
		{
			$new_Message->set( 'author_user_ID', $message->author_user_ID );
		}
		if( !empty( $message->creator_user_ID ) )
		{
			$new_Message->creator_user_ID = $message->creator_user_ID;
		}

		$new_Thread = new Thread();
		$new_Thread->set( 'title', $message->Thread->title );

		$new_Message->Thread = & $new_Thread;

		return $new_Message;
	}


	/**
	 * Delete message and dependencies from database
	 *
	 * @param Log Log object where output gets added (by reference).
	 */
	function dbdelete()
	{
		global $DB;

		if( $this->ID == 0 ) debug_die( 'Non persistant object cannot be deleted!' );

		// Remember ID, because parent method resets it to 0
		$thread_ID = $this->thread_ID;

		$DB->begin();

		// UPDATE last unread msg_ID on this thread statuses from this message ID to the next message ID or NULL if there is no next message
		$DB->query( 'UPDATE T_messaging__threadstatus
						SET tsta_first_unread_msg_ID =
							( SELECT msg_ID
								FROM T_messaging__message
								WHERE msg_thread_ID = '.$thread_ID.' AND msg_datetime > '.$DB->quote( $this->datetime ).'
								ORDER BY msg_datetime ASC
								LIMIT 1
							)
						WHERE tsta_first_unread_msg_ID = '.$this->ID );

		// Delete Message
		if( ! parent::dbdelete() )
		{
			$DB->rollback();

			return false;
		}

		// Get a count of the messages in the current thread
		$SQL = new SQL();
		$SQL->SELECT( 'COUNT( msg_ID )' );
		$SQL->FROM( $this->dbtablename );
		$SQL->WHERE( 'msg_thread_ID = '.$DB->quote( $thread_ID ) );
		$msg_count = $DB->get_var( $SQL->get() );

		if( $msg_count == 0 )
		{	// Last message was deleted from thread now, We should also delete this thread
			load_class( 'messaging/model/_thread.class.php', 'Thread' );
			$ThreadCache = & get_ThreadCache();
			$Thread = & $ThreadCache->get_by_ID( $thread_ID );
			$Thread->dbdelete();
		}

		$DB->commit();

		return true;
	}


	/**
	 * Check permission on a message
	 *
	 * @return boolean true if granted
	 */
	function check_perm( $action, $assert = true )
	{
		global $current_User;

		return $current_User->check_perm( 'perm_messaging', $action, $assert );
	}


	/**
	 * Send email notification to recipients on new thread or new message event.
	 *
	 * @param boolean true if new thread, false if new message in the current thread
	 * @param boolean the User who sent the message, in case of current User it may be NULL ( This is not the current User e.g. in case of welcome messages )
	 * @return boolean True if all messages could be sent, false otherwise.
	 */
	function send_email_notifications( $new_thread = true, $from_User = NULL )
	{
		global $DB, $current_User, $admin_url, $baseurl, $app_name;
		global $Settings, $UserSettings, $servertimenow;

		// Select recipients of the current thread:
		$SQL = new SQL();
		$SQL->SELECT( 'u.user_ID, us.uset_value as notify_messages' );
		$SQL->FROM( 'T_messaging__threadstatus ts
						INNER JOIN T_messaging__contact c
							ON ts.tsta_user_ID = c.mct_to_user_ID AND c.mct_from_user_ID = '.$this->author_user_ID.' AND c.mct_blocked = 0
						INNER JOIN T_users u
							ON ts.tsta_user_ID = u.user_ID
						LEFT OUTER JOIN T_users__usersettings us ON u.user_ID = us.uset_user_ID AND us.uset_name = "notify_messages"' );
		$SQL->WHERE( 'ts.tsta_thread_ID = '.$this->Thread->ID.' AND ts.tsta_user_ID <> '.$this->author_user_ID );

		$thrd_recipients = $DB->get_assoc( $SQL->get() );

		// set message link:
		list( $message_link, $prefs_link ) = get_messages_link_to( $this->thread_ID );

		// Construct message subject and body:
		if( $new_thread )
		{
			$subject = NT_( '%s just sent you a new message!' );
		}
		elseif( count( $thrd_recipients ) == 1 )
		{
			$subject = NT_( '%s just replied to your message!' );
		}
		else
		{
			$subject = NT_( '%s just replied to a conversation you are involved in!' );
		}

		// Get other unread threads
		$other_unread_threads = get_users_unread_threads( array_keys( $thrd_recipients ), $this->thread_ID, 'array', 'html' );

		// Load all users who will be notified
		$UserCache = & get_UserCache();
		$UserCache->load_list( array_keys( $thrd_recipients ) );

		// Send email notifications.
		$ret = true;
		$def_notify_messages = $Settings->get( 'def_notify_messages' );
		foreach( $thrd_recipients as $recipient_ID => $notify_messages )
		{ // Send mail to recipients who needs to be notified. recipients are already loaded into the UserCache
			if( !( $notify_messages || ( is_null( $notify_messages ) && $def_notify_messages ) ) )
			{ // User should NOT be notified
				continue;
			}

			$email_template_params = array(
					'recipient_ID'         => $recipient_ID,
					'new_thread'           => $new_thread,
					'thrd_recipients'      => $thrd_recipients,
					'Message'              => $this,
					'message_link'         => $message_link,
					'other_unread_threads' => $other_unread_threads[$recipient_ID],
					'from_User'            => $from_User,
				);
			$notify_User = $UserCache->get_by_ID( $recipient_ID );
			// Change locale here to localize the email subject and content
			locale_temp_switch( $notify_User->get( 'locale' ) );
			$sender_login = ( $from_User === NULL ) ? $current_User->login : $from_User->login;
			$localized_subject = sprintf( T_( $subject ), $sender_login );
			// Note: Not activated users won't get notification email
			if( send_mail_to_User( $recipient_ID, $localized_subject, 'private_message_new', $email_template_params ) )
			{ // email sent successful, update las_unread_message_reminder timestamp, because the notification contains all unread messages
				$UserSettings->set( 'last_unread_messages_reminder', date2mysql( $servertimenow ), $recipient_ID );
			}
			else
			{ // message was not sent
				$ret = false;
			}
			locale_restore_previous();
		}
		// update reminder timestamp changes
		$UserSettings->dbupdate();
		return $ret;
	}
}

?>