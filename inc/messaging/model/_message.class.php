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
	var $original_text = '';

	/**
	 * @var string
	 */
	var $renderers;

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
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_messaging__message', 'msg_', 'msg_ID', 'datetime', '', 'author_user_ID' );

		if( $db_row == NULL )
		{
			$this->set_renderers( array( 'default' ) );
		}
		else
		{
			$this->ID                = $db_row->msg_ID;
			$this->thread_ID         = $db_row->msg_thread_ID;
			$this->author_user_ID    = $db_row->msg_author_user_ID;
			$this->datetime          = $db_row->msg_datetime;
			$this->text              = $db_row->msg_text;
			$this->renderers         = $db_row->msg_renderers;
		}
	}


	/**
	 * Get this class db table config params
	 *
	 * @return array
	 */
	static function get_class_db_config()
	{
		static $message_db_config;

		if( !isset( $message_db_config ) )
		{
			$message_db_config = array_merge( parent::get_class_db_config(),
				array(
					'dbtablename'        => 'T_messaging__message',
					'dbprefix'           => 'msg_',
					'dbIDname'           => 'msg_ID',
				)
			);
		}

		return $message_db_config;
	}


	/**
	 * Get delete cascade settings
	 *
	 * @return array
	 */
	static function get_delete_cascades()
	{
		return array(
				array( 'table'=>'T_messaging__prerendering', 'fk'=>'mspr_msg_ID', 'msg'=>T_('%d prerendered content') )
			);
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		global $Plugins, $msg_text, $Settings;

		$new_thread = empty( $this->thread_ID );

		// Renderers:
		if( param( 'renderers_displayed', 'integer', 0 ) )
		{ // use "renderers" value only if it has been displayed (may be empty)
			$renderers = $Plugins->validate_renderer_list( param( 'renderers', 'array:string', array() ), array( 'Message' => & $this ) );
			$this->set_renderers( $renderers );
		}

		// Text
		if( $Settings->get( 'allow_html_message' ) )
		{ // HTML is allowed for messages
			$text_format = 'html';
		}
		else
		{ // HTML is disallowed for messages
			$text_format = 'htmlspecialchars';
		}
		$msg_text = param( 'msg_text', $text_format );
		$this->original_text = html_entity_decode( $msg_text );

		// This must get triggered before any internal validation and must pass all relevant params.
		$Plugins->trigger_event( 'MessageThreadFormSent', array(
				'content' => & $msg_text,
				'dont_remove_pre' => true,
				'renderers' => $this->get_renderers_validated(),
			) );

		if( ! $new_thread )
		{
			param_check_not_empty( 'msg_text' );
		}
		if( $text_format == 'html' )
		{ // message text may contain html, check the html sanity
			param_check_html( 'msg_text', T_('Invalid message content.') );
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
			if( $this->Thread->check_allow_reply() )
			{ // If reply is allowed we should check if this message is not a duplicate
				global $DB, $current_User;

				// Get last message of current user in this thread
				$SQL = new SQL();
				$SQL->SELECT( 'msg_text' );
				$SQL->FROM( 'T_messaging__message' );
				$SQL->WHERE( 'msg_thread_ID = '.$this->Thread->ID );
				$SQL->WHERE_and( 'msg_author_user_ID = '.$current_User->ID );
				$SQL->ORDER_BY( 'msg_ID DESC' );
				$last_message = $DB->get_var( $SQL->get() );

				if( $last_message == $msg_text )
				{
					param_error( 'msg_text', T_('It seems you tried to send the same message twice. We only kept one copy.') );
				}
			}
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
		{ // insert users/recipients which are not in contact list

			$sql = 'INSERT INTO T_messaging__contact (mct_from_user_ID, mct_to_user_ID, mct_last_contact_datetime)
								VALUES';

			$datetime = date( 'Y-m-d H:i:s', $localtimenow );

			$statements = array();
			foreach ( $contact_list as $contact_ID )
			{
				if( $contact_ID != $this->author_user_ID )
				{ // Don't insert a contact from the same user
					$statements[] = ' ('.$this->author_user_ID.', '.$contact_ID.', \''.$datetime.'\')';
				}
			}
			foreach ( $reverse_contact_list as $contact_ID )
			{
				if( $contact_ID != $this->author_user_ID )
				{ // Don't insert a contact from the same user
					$statements[] = ' ('.$contact_ID.', '.$this->author_user_ID.', \''.$datetime.'\')';
				}
			}

			if( ! empty( $statements ) )
			{ // Do insert sql only when data exist
				$sql .= implode( ', ', $statements );

				return $DB->query( $sql, 'Insert contacts' );
			}
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
		$new_Message->set( 'renderers', $message->renderers );
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
	 * Delete those messages from the database which corresponds to the given condition or to the given ids array
	 * Note: the delete cascade arrays are handled!
	 *
	 * @param string the name of this class
	 *   Note: This is required until min phpversion will be 5.3. Since PHP 5.3 we can use static::function_name to achieve late static bindings
	 * @param string where condition
	 * @param array object ids
	 * @return mixed # of rows affected or false if error
	 */
	static function db_delete_where( $class_name, $sql_where, $object_ids = NULL, $params = NULL )
	{
		global $DB;

		$DB->begin();

		if( ! empty( $sql_where ) )
		{
			$messages_to_delete = $DB->get_assoc( 'SELECT msg_ID, msg_thread_ID FROM T_messaging__message WHERE '.$sql_where );
			$object_ids = array_keys( $messages_to_delete );
			$thread_ids_to_delete = array_unique( $messages_to_delete );
		}

		if( ! $object_ids )
		{ // There is no comment to delete
			$DB->commit();
			return;
		}

		$message_ids_to_delete = implode( ', ', $object_ids );
		if( empty( $thread_ids_to_delete ) )
		{ // Make sure thread ids of the messages are collected
			$thread_ids_to_delete = $DB->get_col( 'SELECT msg_thread_ID FROM T_messaging__message WHERE msg_ID IN ( '.$message_ids_to_delete.' )' );
		}

		// Update thread statuses first unread message IDs
		$result = $DB->query( 'UPDATE T_messaging__threadstatus
				SET tsta_first_unread_msg_ID =
				( SELECT message1.msg_ID
					FROM T_messaging__message as message1
					WHERE message1.msg_thread_ID = tsta_thread_ID
						AND message1.msg_datetime > ( SELECT MAX( message2.msg_datetime)
							FROM T_messaging__message as message2
							WHERE message2.msg_ID IN ( '.$message_ids_to_delete.' )
								AND message2.msg_thread_ID = tsta_thread_ID
						)
					ORDER BY message1.msg_datetime ASC
					LIMIT 1
				)
				WHERE tsta_first_unread_msg_ID IN ( '.$message_ids_to_delete.')' ) !== false;

		if( $result )
		{ // Remove messages with all of its delete cascade relations
			$result = parent::db_delete_where( $class_name, $sql_where, $object_ids );
		}

		if( $result !== false )
		{ // Delete those threads where all of the messages were deleted
			load_class( 'messaging/model/_thread.class.php', 'Thread' );
			$orphan_thread_ids = $DB->get_col( '
				SELECT thrd_ID FROM T_messaging__thread
				LEFT JOIN T_messaging__message ON thrd_ID = msg_thread_ID
				WHERE thrd_ID IN ( '.implode( ', ', $thread_ids_to_delete ).' )
				GROUP BY thrd_ID
				HAVING COUNT(msg_ID) = 0' );

			// Delete orphan threads if there are any
			if( ( ! empty( $orphan_thread_ids ) ) && ( Thread::db_delete_where( 'Thread', NULL, $orphan_thread_ids ) === false ) )
			{ // Deleting threads was unsuccessful
				$result = false;
			}
		}

		// Commit or rollback the transaction
		( $result !== false ) ? $DB->commit() : $DB->rollback();

		return $result;
	}


	/**
	 * Delete message and dependencies from database
	 *
	 * @param Log Log object where output gets added (by reference).
	 */
	function dbdelete()
	{
		if( $this->ID == 0 ) debug_die( 'Non persistant object cannot be deleted!' );

		return parent::dbdelete();
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
		global $Settings, $UserSettings, $servertimenow, $Messages;

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
		$other_unread_threads = get_users_unread_threads( array_keys( $thrd_recipients ), $this->thread_ID, 'array', 'html', 'http:' );

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

			$notify_User = $UserCache->get_by_ID( $recipient_ID );

			// Get the messages link:
			list( $message_link ) = get_messages_link_to( $this->thread_ID, $recipient_ID );

			if( $message_link === false )
			{	// If the recipient has no access to messages page:
				$Messages->add( sprintf( T_('%s cannot be notified of this new message because we have no default messaging collection and %s has no access to the backoffice.'), $notify_User->get( 'login' ), $notify_User->get( 'login' ) ), 'error' );
				syslog_insert( sprintf( T_('%s cannot be notified for new/pending private messages because we have no default messaging collection and %s has no access to the backoffice.'), $notify_User->get( 'login' ), $notify_User->get( 'login' ) ), 'error' );
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


	/**
	 * Get the list of validated renderers for this Message. This includes stealth plugins etc.
	 * @return array List of validated renderer codes
	 */
	function get_renderers_validated()
	{
		if( ! isset( $this->renderers_validated ) )
		{
			global $Plugins;
			$this->renderers_validated = $Plugins->validate_renderer_list( $this->get_renderers(), array( 'Message' => & $this ) );
		}
		return $this->renderers_validated;
	}


	/**
	 * Get the list of renderers for this Message.
	 * @return array
	 */
	function get_renderers()
	{
		return explode( '.', $this->renderers );
	}


	/**
	 * Set the renderers of the Message.
	 *
	 * @param array List of renderer codes.
	 * @return boolean true, if it has been set; false if it has not changed
	 */
	function set_renderers( $renderers )
	{
		return $this->set_param( 'renderers', 'string', implode( '.', $renderers ) );
	}


	/**
	 * Get the prerendered content. If it has not been generated yet, it will.
	 *
	 * NOTE: This calls {@link Message::dbupdate()}, if renderers get changed (from Plugin hook).
	 *       (not for preview though)
	 *
	 * @param string Format, see {@link format_to_output()}.
	 *        Only "htmlbody", "entityencoded", "xml" and "text" get cached.
	 * @return string
	 */
	function get_prerendered_content( $format  = 'htmlbody' )
	{
		global $Plugins, $DB;

		$use_cache = $this->ID && in_array( $format, array('htmlbody', 'entityencoded', 'xml', 'text') );
		if( $use_cache )
		{ // the format/comment can be cached:
			$this->get_Thread();
			$message_renderers = $this->get_renderers_validated();
			if( empty( $message_renderers ) )
			{
				return format_to_output( $this->text, $format );
			}
			$message_renderers = implode( '.', $message_renderers );
			$cache_key = $format.'/'.$message_renderers;

			$MessagePrerenderingCache = & get_MessagePrerenderingCache();

			if( isset( $MessagePrerenderingCache[$format][$this->ID][$cache_key] ) )
			{ // already in PHP cache.
				$r = $MessagePrerenderingCache[$format][$this->ID][$cache_key];
				// Save memory, typically only accessed once.
				unset( $MessagePrerenderingCache[$format][$this->ID][$cache_key] );
			}
			else
			{ // try loading into Cache
				if( ! isset( $MessagePrerenderingCache[$format] ) )
				{ // only do the prefetch loading once.
					$MessagePrerenderingCache[$format] = array();

					$SQL = new SQL();
					$SQL->SELECT( 'mspr_msg_ID, mspr_format, mspr_renderers, mspr_content_prerendered' );
					$SQL->FROM( 'T_messaging__prerendering' );
					// load prerendered cache for each message which belongs to this messages Thread
					$SQL->FROM_add( 'INNER JOIN T_messaging__message ON mspr_msg_ID = msg_ID' );
					$SQL->WHERE( 'msg_thread_ID = '.$this->Thread->ID );
					$SQL->WHERE_and( 'mspr_format = '.$DB->quote( $format ) );
					$rows = $DB->get_results( $SQL->get(), OBJECT, 'Preload prerendered messages content ('.$format.')' );
					foreach( $rows as $row )
					{
						$row_cache_key = $row->mspr_format.'/'.$row->mspr_renderers;

						if( ! isset( $MessagePrerenderingCache[$format][$row->mspr_msg_ID] ) )
						{ // init list
							$MessagePrerenderingCache[$format][$row->mspr_msg_ID] = array();
						}

						$MessagePrerenderingCache[$format][$row->mspr_msg_ID][$row_cache_key] = $row->mspr_content_prerendered;
					}

					// Get the value for current Comment.
					if( isset( $MessagePrerenderingCache[$format][$this->ID][$cache_key] ) )
					{
						$r = $MessagePrerenderingCache[$format][$this->ID][$cache_key];
						// Save memory, typically only accessed once.
						unset( $MessagePrerenderingCache[$format][$this->ID][$cache_key] );
					}
				}
			}
		}

		if( !isset( $r ) )
		{
			$data = $this->text;
			$Plugins->trigger_event( 'FilterMsgContent', array( 'data' => & $data, 'Message' => $this ) );
			$r = format_to_output( $data, $format );

			if( $use_cache )
			{ // save into DB (using REPLACE INTO because it may have been pre-rendered by another thread since the SELECT above)
				global $servertimenow;
				$DB->query( 'REPLACE INTO T_messaging__prerendering ( mspr_msg_ID, mspr_format, mspr_renderers, mspr_content_prerendered, mspr_datemodified )
					 VALUES ( '.$this->ID.', '.$DB->quote( $format ).', '.$DB->quote( $message_renderers ).', '.$DB->quote( $r ).', '.$DB->quote( date2mysql( $servertimenow ) ).' )', 'Cache prerendered message content' );
			}
		}

		// Trigger Display plugins FOR THE STUFF THAT WOULD NOT BE PRERENDERED:
		$r = $Plugins->render( $r, $this->get_renderers_validated(), $format, array(), 'Display' );

		return $r;
	}


	/**
	 * Unset any prerendered content for this message (in PHP cache).
	 */
	function delete_prerendered_content()
	{
		global $DB;

		// Delete DB rows.
		$DB->query( 'DELETE FROM T_messaging__prerendering WHERE mspr_msg_ID = '.$this->ID );

		// Delete cache.
		$MessagePrerenderingCache = & get_MessagePrerenderingCache();
		foreach( array_keys( $MessagePrerenderingCache ) as $format )
		{
			unset( $MessagePrerenderingCache[$format][$this->ID] );
		}
	}


	/**
	 * Template function: get content of message
	 *
	 * @param string Output format, see {@link format_to_output()}
	 * @return string
	 */
	function get_content( $format = 'htmlbody' )
	{
		/* yura> This code was commented after we added new setting 'Allow html in messages content'
		         The preparing of message content is in Message::load_from_Request()

		global $evo_charset;

		// WARNING: the messages may contain MALICIOUS HTML and javascript snippets. They must ALWAYS be ESCAPED prior to display!
		$this->text = htmlentities( $this->text, ENT_COMPAT, $evo_charset );
		*/

		return $this->get_prerendered_content( $format );
	}
}

?>