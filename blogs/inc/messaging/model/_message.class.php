<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @version $Id$
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

		param( 'msg_text', 'text');
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
	 * @return true if success, false otherwise
	 */
	function dbinsert_discussion()
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

							$this->send_email_notifications();
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
	 * @return true if success, instead false
	 */
	function dbinsert_individual()
	{
		foreach( $this->Thread->recipients_list as $recipient_ID )
		{
			$message = $this->clone_message( $this );

			$message->Thread->recipients_list = array( $recipient_ID );

			if ( !$message->dbinsert_discussion() )
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

				if( $this->dbupdate_last_contact_datetime() )
				{
					$DB->commit();

					$this->send_email_notifications( false );
					return true;
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

		if( !empty( $contact_list ) )
		{	// insert users/recipients which are not in contact list

			$sql = 'INSERT INTO T_messaging__contact (mct_from_user_ID, mct_to_user_ID, mct_last_contact_datetime)
								VALUES';

			$datetime = date( 'Y-m-d H:i:s', $localtimenow );

			$statements = array();
			foreach ( $contact_list as $contact_ID )
			{
				$statements[] = ' ('.$this->author_user_ID.', '.$contact_ID.', \''.$datetime.'\')';
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

		$DB->begin();

		// UPDATE Statuses
		$DB->query( 'UPDATE T_messaging__threadstatus
						SET tsta_first_unread_msg_ID = NULL
						WHERE tsta_first_unread_msg_ID='.$this->ID );

		// Delete Message
		if( ! parent::dbdelete() )
		{
			$DB->rollback();

			return false;
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
	 * @return boolean True if all messages could be sent, false if at least one error occurred.
	 */
	function send_email_notifications( $new_thread = true )
	{
		global $DB, $current_User, $admin_url, $baseurl, $app_name;
		global $UserSettings, $Settings;

		// Select recipients of the current thread:
		$SQL = new SQL();
		$SQL->SELECT( 'u.user_login, u.user_email, u.user_nickname, u.user_firstname, us.uset_value as notify_messages' );
		$SQL->FROM( 'T_messaging__threadstatus ts
						INNER JOIN T_messaging__contact c
							ON ts.tsta_user_ID = c.mct_to_user_ID AND c.mct_from_user_ID = '.$this->author_user_ID.' AND c.mct_blocked = 0
						LEFT OUTER JOIN T_users u
							ON ts.tsta_user_ID = u.user_ID
						LEFT OUTER JOIN T_users__usersettings us ON u.user_ID = us.uset_user_ID AND us.uset_name = "notify_messages"' );
		$SQL->WHERE( 'ts.tsta_thread_ID = '.$this->Thread->ID.' AND ts.tsta_user_ID <> '.$this->author_user_ID );

		// Construct message subject and body:
		$salutation = T_( 'Hello %s !')."\n\n";

		$body = '';

		// set message link:
		$messages_link_to = $Settings->get( 'messages_link_to' );
		if( $messages_link_to == 'admin' )
		{
			$message_link = $admin_url.'?ctrl=messages&thrd_ID='.$this->Thread->ID;
      $prefs_link = $admin_url.'?ctrl=user&user_tab=userprefs'; // TODO: make this work in admin
		}
		else
		{
			$BlogCache = & get_BlogCache();
      /**
      * @var Blog
      */
			$link_to_Blog = $BlogCache->get_by_ID( $messages_link_to, false, false );
			if( $link_to_Blog )
			{
				$message_link = url_add_param( $link_to_Blog->gen_blogurl(), 'disp=messages&thrd_ID='.$this->Thread->ID );
        $prefs_link =  url_add_param( $link_to_Blog->gen_blogurl(), 'disp=userprefs' );
			}
			else
			{
				$message_link = $admin_url.'?ctrl=messages&thrd_ID='.$this->Thread->ID;
        $prefs_link = $admin_url.'?ctrl=user&user_tab=userprefs'; // TODO: make this work in admin
      }
		}


		if( $new_thread )
		{
			$subject = sprintf( T_( '%s just sent you a new message!' ), $current_User->login );

			$body .= sprintf( T_( '%s just sent you a message with the title "%s".' ), $current_User->login, $this->Thread->title );
			$body .= "\n\n";
			$body .= sprintf( T_( 'To read the full message, click here: %s' ), $message_link );
		}
		else
		{
			$subject = sprintf( T_( '%s just replied to your message!' ), $current_User->login );

			$body .= sprintf( T_( '%s just replied to your message in the "%s" conversation. ' ), $current_User->login, $this->Thread->title );
			$body .= "\n\n";
			$body .= sprintf( T_( 'To read the full message, click here: %s' ), $message_link );
		}

		$body .= "\n\n-- \n";

		$body .= sprintf( T_( 'This message was automatically generated by %s running on %s.' ), $app_name, $baseurl )
			."\n".T_( 'Please do not reply to this email.' )
      ."\n".sprintf( T_('To edit your email notification preferences, click here: %s'), $prefs_link )."\n";

    $footer = T_( 'Your login is: %s' );

		// Send email notifications:
		$ret = true;
		foreach( $DB->get_results( $SQL->get() ) as $row )
		{
			$notify_messages = ( isset( $row->notify_messages ) ) ? $row->notify_messages : $UserSettings->get_default( 'notify_messages' );
			if( $notify_messages )
			{
				$name = get_prefered_name( $row->user_nickname, $row->user_firstname, $row->user_login );
				$body = sprintf( $salutation, $name ).$body.sprintf( $footer, $row->user_login );
				$ret = send_mail( $row->user_email, $row->user_login, $subject, $body );
			}
		}

		return $ret;
	}
}

/*
 * $Log$
 * Revision 1.29  2011/10/11 02:05:41  fplanque
 * i18n/wording cleanup
 *
 * Revision 1.28  2011/10/06 06:18:29  efy-asimo
 * Add messages link to settings
 * Update messaging notifications
 *
 * Revision 1.27  2011/08/18 11:41:51  efy-asimo
 * Send all emails from noreply and email contents review
 *
 * Revision 1.26  2011/07/04 12:26:54  efy-asimo
 * Notification emails content - fix
 */
?>
