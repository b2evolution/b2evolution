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
			$ThreadCache = & get_Cache( 'ThreadCache' );
			$this->Thread = $ThreadCache->get_by_ID( $this->thread_ID );
		}

		return $this->Thread;
	}

	/**
	 * Insert object into DB based on previously recorded changes
	 *
	 * @return boolean true on success
	 */
	function dbinsert( $type = NULL )
	{
		global $DB, $localtimenow;

		if( $this->ID != 0 ) die( 'Existing object cannot be inserted!' );

		$DB->begin();

		$this->get_Thread();

		if( $this->Thread->ID == 0 )
		{	// We can create new thread or new threads

			if ( $type == 'discussion' )
			{	// Create one thread for all recipients
				$success = $this->dbinsert_discussion();
			}
			else
			{	// Create thread for each recipient
				$success = $this->dbinsert_individual();
			}
		}
		else
		{	// We can update thread and create new message
			$success = $this->dbinsert_message();
		}

		if( !$success )
		{
			$DB->rollback();
			return false;
		}

		$DB->commit();
		return true;
	}

	/**
	 * Insert discussion (one thread for all recipients)
	 * @return true if success, instead false
	 */
	function dbinsert_discussion()
	{
		global $DB;

		if ( $this->Thread->dbinsert() )
		{
			$this->set_param( 'thread_ID', 'integer', $this->Thread->ID);

			if( parent::dbinsert() )
			{
				if( $this->dbinsert_threadstatus( $this->Thread->recipients_list ) )
				{
					$this->send_email_notifications();
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Insert new thread for each recipient
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
	 * @return true if success, instead false
	 */
	function dbinsert_message()
	{
		global $DB, $localtimenow;

		$this->Thread->set_param( 'datemodified', 'string', date('Y-m-d H:i:s', $localtimenow) );

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

				$this->send_email_notifications( false );

				return true;
			}
		}

		return false;;
	}

	/**
	 * Insert recipients into database
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
	 * Clone current message and convert cloned message from 'individual' to 'discussion'.
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
	 * Send email notification to recipients on new thread or new message event
	 * @param true if new thread, false if new message in the current thread
	 */
	function send_email_notifications( $new_thread = true )
	{
		global $DB, $current_User, $htsrv_url_sensitive;

		// Select recipients of the current thread

		$SQL = & new SQL();

		$SQL->SELECT( 'u.user_login, u.user_email' );

		$SQL->FROM( 'T_messaging__threadstatus ts
						LEFT OUTER JOIN T_users u ON ts.tsta_user_ID = u.user_ID' );

		$SQL->WHERE( 'ts.tsta_thread_ID = '.$this->Thread->ID.' AND ts.tsta_user_ID <> '.$this->author_user_ID );

		$notifications = array();
		foreach( $DB->get_results( $SQL->get() ) as $row )
		{
			$notifications[] = array(	'login'  => $row->user_login,
										'email'  => $row->user_email	);
		}

		// Construct message subject and body

		$body = T_( 'Dear user');
		$body .= ',';
		$body .= "\n\n";

		$body .= $current_User->login;
		$body .= ' ';

		if( $new_thread )
		{
			$subject = T_( 'New conversation has been created.' ); ;

			$body .= sprintf( T_( 'has created "%s" conversation. ' ), $this->Thread->title );
			$body .= sprintf( T_( 'To access it, log in at "%s" and click Messages button.' ), $htsrv_url_sensitive.'login.php' );
		}
		else
		{
			$subject = T_( 'New message has been created.' ); ;

			$body .= sprintf( T_( 'has created new message in "%s" conversation. ' ), $this->Thread->title );
			$body .= sprintf( T_( 'To access it, log in at "%s". Click Messages button and then open above mentioned conversation.' ), $htsrv_url_sensitive.'login.php' );
		}

		$body .= "\n\n";
		$body .= T_( 'Best regards' );
		$body .= ',';
		$body .= "\n";
		$body .= T_( 'b2evolution team' );
		$body .= "\n\n\n";
		$body .= T_( 'Please do not reply to this email.' );

		// Send email notifications

		foreach( $notifications as $notification )
		{
			send_mail( $notification['email'], $notification['login'], $subject, $body );
		}
	}
}

/*
 * $Log$
 * Revision 1.10  2009/09/16 12:30:03  efy-maxim
 * Send notification on new thread or new message event
 *
 * Revision 1.9  2009/09/16 09:15:32  efy-maxim
 * Messaging module improvements
 *
 * Revision 1.8  2009/09/15 19:31:55  fplanque
 * Attempt to load classes & functions as late as possible, only when needed. Also not loading module specific stuff if a module is disabled (module granularity still needs to be improved)
 * PHP 4 compatible. Even better on PHP 5.
 * I may have broken a few things. Sorry. This is pretty hard to do in one swoop without any glitch.
 * Thanks for fixing or reporting if you spot issues.
 *
 * Revision 1.7  2009/09/15 11:20:03  efy-maxim
 * Group discussion vs Individual messages
 *
 * Revision 1.6  2009/09/14 13:20:56  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.5  2009/09/12 18:44:11  efy-maxim
 * Messaging module improvements
 *
 * Revision 1.4  2009/09/10 18:24:07  fplanque
 * doc
 *
 */
?>
