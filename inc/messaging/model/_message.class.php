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
				array( 'table'=>'T_messaging__prerendering', 'fk'=>'mspr_msg_ID', 'msg'=>T_('%d prerendered content') ),
				array( 'table'=>'T_links', 'fk'=>'link_msg_ID', 'msg'=>T_('%d links to destination private messages'),
						'class'=>'Link', 'class_path'=>'links/model/_link.class.php' ),
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
	 * Link attachments from temporary object to new created Message
	 */
	function link_from_Request()
	{
		global $DB;

		if( $this->ID == 0 )
		{	// The message must be stored in DB:
			return;
		}

		$temp_link_owner_ID = param( 'temp_link_owner_ID', 'integer', 0 );

		$TemporaryIDCache = & get_TemporaryIDCache();
		if( ! ( $TemporaryID = & $TemporaryIDCache->get_by_ID( $temp_link_owner_ID, false, false ) ) )
		{	// No temporary object of attachments:
			return;
		}

		if( $TemporaryID->type != 'message' )
		{	// Wrong temporary object:
			return;
		}

		// Load all links:
		$LinkOwner = new LinkMessage( new Message(), $TemporaryID->ID );
		$LinkOwner->load_Links();

		if( empty( $LinkOwner->Links ) )
		{	// No links:
			return;
		}

		// Change link owner from temporary to message object:
		$DB->query( 'UPDATE T_links
			  SET link_msg_ID = '.$this->ID.',
			      link_tmp_ID = NULL
			WHERE link_tmp_ID = '.$TemporaryID->ID );

		// Move all temporary files to folder of new created message:
		foreach( $LinkOwner->Links as $msg_Link )
		{
			if( $msg_File = & $msg_Link->get_File() &&
			    $msg_FileRoot = & $msg_File->get_FileRoot() )
			{
				if( ! file_exists( $msg_FileRoot->ads_path.'private_message/pm'.$this->ID.'/' ) )
				{	// Create if folder doesn't exist for files of new created message:
					if( evo_mkdir( $msg_FileRoot->ads_path.'private_message/pm'.$this->ID.'/' ) )
					{
						$tmp_folder_path = $msg_FileRoot->ads_path.'private_message/tmp'.$TemporaryID->ID.'/';
					}
				}
				$msg_File->move_to( $msg_FileRoot->type, $msg_FileRoot->in_type_ID, 'private_message/pm'.$this->ID.'/'.$msg_File->get_name() );
			}
		}

		if( isset( $tmp_folder_path ) && file_exists( $tmp_folder_path ) )
		{	// Remove temp folder from disk completely:
			rmdir_r( $tmp_folder_path );
		}

		// Delete temporary object from DB:
		$TemporaryID->dbdelete();
	}


	/**
	 * Link attachments from other message object to this Message
	 * Used to copy all links from one to another if new thread is sent to multiple recipients as individual messages
	 */
	function link_from_Message( $source_msg_ID )
	{
		if( $this->ID == 0 || $source_msg_ID == 0 )
		{	// Current and source message must be created in DB:
			return;
		}

		// Find all matches with inline tags:
		preg_match_all( '/\[(image|file|inline|video|audio|thumbnail):(\d+)(:?)([^\]]*)\]/i', $this->text, $inlines );

		if( empty( $inlines[0] ) )
		{	// If content of source message doesn't contain inline tags then we should update a content of current message,
			// so we do a quick copying of all links from source message to current:
			global $DB;
			$DB->query( 'INSERT INTO T_links
			     ( link_msg_ID,   link_datecreated, link_datemodified, link_creator_user_ID, link_lastedit_user_ID, link_file_ID, link_ltype_ID, link_position, link_order )
			SELECT '.$this->ID.', link_datecreated, link_datemodified, link_creator_user_ID, link_lastedit_user_ID, link_file_ID, link_ltype_ID, link_position, link_order
			  FROM T_links
			 WHERE link_msg_ID = '.$source_msg_ID );
		}
		else
		{	// The source message content contains at least one inline tag,
			// therefore we must update content of current message to update the link IDs of the inline tags

			// Load all links of the source message:
			$MessageCache = & get_MessageCache();
			$source_Message = & $MessageCache->get_by_ID( $source_msg_ID, false, false );
			$source_LinkOwner = new LinkMessage( $source_Message );
			$source_LinkOwner->load_Links();

			if( empty( $source_LinkOwner->Links ) )
			{	// No links:
				return;
			}

			// Initialize link owner for current message:
			$this_LinkOwner = new LinkMessage( $this );

			// Store in this array a relation of source link IDs and new copied link IDs of this message:
			$new_link_IDs = array();

			// Copy each link from source message to current:
			foreach( $source_LinkOwner->Links as $source_Link )
			{
				if( $new_link_ID = $this_LinkOwner->add_link( $source_Link->file_ID, $source_Link->position, $source_Link->order ) )
				{	// If new link is added then store this in array in order to update the message content for inline tags like [image:123]:
					$new_link_IDs[ $source_Link->ID ] = $new_link_ID;
				}
			}

			// Replace link IDs of source message in inline tags to new inserted Links of current message:
			$search_inline_tags = array();
			$replace_inline_tags = array();
			foreach( $inlines[0] as $i => $inline_tag )
			{
				$search_inline_tags[] = $inline_tag;
				$replace_inline_tags[] = '['.$inlines[1][ $i ].':'
					.$new_link_IDs[ $inlines[2][ $i ] ] // ID of new Link
					.$inlines[3][ $i ].$inlines[4][ $i ].']';
			}
			$new_message_content = replace_content_outcode( $search_inline_tags, $replace_inline_tags, $this->text, 'replace_content', 'str' );

			// Update message content in DB:
			$this->set( 'text', $new_message_content );
			$this->dbupdate();
		}
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
	 * @param object User who sent the message, it must be set only if it is not the current User
	 * @param integer Source message ID (used to copy links/attachments from previous message in mode of individual messages for multiple recipients)
	 * @return boolean|integer ID of inserted message if success, false otherwise
	 */
	function dbinsert_discussion( $from_User = NULL, $source_msg_ID = NULL )
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

							if( $source_msg_ID === NULL )
							{	// Link attachments from temporary object to new created Message:
								$this->link_from_Request();
							}
							else
							{	// Link attachments from source Message object to this Message:
								$this->link_from_Message( $source_msg_ID );
							}

							$this->send_email_notifications( true, $from_User );
							return $this->ID;
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
		$source_msg_ID = NULL;

		foreach( $this->Thread->recipients_list as $recipient_ID )
		{
			$message = $this->clone_message( $this );

			$message->Thread->recipients_list = array( $recipient_ID );

			$this_msg_ID = $message->dbinsert_discussion( $from_User, $source_msg_ID );
			if( ! $this_msg_ID )
			{
				return false;
			}

			if( $source_msg_ID === NULL )
			{	// Use first message as source for all next messages:
				// (Used to copy links/attachments from first message all next)
				$source_msg_ID = $this_msg_ID;
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

						// Link attachments from temporary object to new created Message:
						$this->link_from_Request();

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
				syslog_insert( sprintf( '%s cannot be notified for new/pending private messages because we have no default messaging collection and %s has no access to the backoffice.', $notify_User->get( 'login' ), $notify_User->get( 'login' ) ), 'error' );
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
			$sender_login = ( $from_User === NULL ) ? $current_User->get_username() : $from_User->get_username();
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

			// Render inline file tags like [image:123:caption] or [file:123:caption] :
			$data = render_inline_files( $data, $this, array(
					'check_code_block' => true,
					'image_size'       => 'original',
					'image_link_rel'   => 'lightbox[m'.$this->ID.']',
				) );

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

		$content = '';

		if( empty( $this->ID ) )
		{	// Preview mode for new creating message:
			$content = '<b>'.T_('PREVIEW').':</b><br />';
		}

		$content .= $this->get_prerendered_content( $format );

		return $content;
	}


	/**
	 * Get block of images linked to the current Message
	 *
	 * @param array of params
	 * @param string Output format, see {@link format_to_output()}
	 */
	function get_images( $params = array(), $format = 'htmlbody' )
	{
		global $Plugins;

		$r = '';

		$params = array_merge( array(
				'before'                     => '<div class="clear">',
				'before_image'               => '<div class="image_block">',
				'before_image_legend'        => '<div class="image_legend">',
				'after_image_legend'         => '</div>',
				'after_image'                => '</div>',
				'after'                      => '</div>',
				'image_size'                 => 'original',
				'image_size_x'               => 1, // Use '2' to build 2x sized thumbnail that can be used for Retina display
				'image_link_to'              => 'original', // Can be 'original' (image) or 'single' (this post)
				'image_link_rel'             => 'lightbox[m'.$this->ID.']',
				'limit'                      => 1000, // Max # of images displayed
				'before_gallery'             => '<div class="evo_image_gallery">',
				'after_gallery'              => '</div>',
				'gallery_image_size'         => 'crop-80x80',
				'gallery_image_limit'        => 1000,
				'gallery_colls'              => 5,
				'gallery_order'              => '', // 'ASC', 'DESC', 'RAND'
				'gallery_link_rel'           => 'lightbox[m'.$this->ID.']',
				'restrict_to_image_position' => 'inline', // 'teaser'|'teaserperm'|'teaserlink'|'aftermore'|'inline'|'cover'
				'exclude_inline_tagged'      => true, // Use true to exclude inline attachments which are already rendered in content by inline tags like '[image:123]'
				'data'                       =>  & $r,
				'get_rendered_attachments'   => true,
				'links_sql_select'           => '',
				'links_sql_orderby'          => 'link_order',
			), $params );

		// Get list of ALL attached files:
		$links_params = array(
				'sql_select_add' => $params['links_sql_select'],
				'sql_order_by'   => $params['links_sql_orderby']
			);

		if( empty( $this->ID ) )
		{	// Preview mode for new creating message:
			$tmp_object_ID = param( 'temp_link_owner_ID', 'integer', 0 );
		}
		else
		{	// Normal mode for existing Message in DB:
			$tmp_object_ID = NULL;
		}

		$LinkOwner = new LinkMessage( $this, $tmp_object_ID );
		if( ! $LinkList = $LinkOwner->get_attachment_LinkList( 1000, $params['restrict_to_image_position'], NULL, $links_params ) )
		{
			return '';
		}

		$exclude_link_IDs = array();
		if( $params['exclude_inline_tagged'] )
		{	// Find all links which are already rendered in content by inline tags like '[image:123]':
			preg_match_all( '/\[(image|file|inline|video|audio|thumbnail):(\d+)(:?)([^\]]*)\]/i', $this->text, $inlines );
			$exclude_link_IDs = $inlines[2];
		}

		$galleries = array();
		$image_counter = 0;
		$plugin_render_attachments = false;
		while( $image_counter < $params['limit'] && $Link = & $LinkList->get_next() )
		{
			if( in_array( $Link->ID, $exclude_link_IDs ) )
			{	// Skip this link because it is already rendered by inline tag like '[image:123]':
				continue;
			}

			if( ! ( $File = & $Link->get_File() ) )
			{	// No File object:
				global $Debuglog;
				$Debuglog->add( sprintf( 'Link ID#%d of message #%d does not have a file object!', $Link->ID, $this->ID ), array( 'error', 'files' ) );
				continue;
			}

			if( ! $File->exists() )
			{ // File doesn't exist
				global $Debuglog;
				$Debuglog->add( sprintf( 'File linked to message #%d does not exist (%s)!', $this->ID, $File->get_full_path() ), array( 'error', 'files' ) );
				continue;
			}

			$params['File'] = $File;
			$params['Link'] = $Link;
			$params['Message'] = $this;

			if( $File->is_dir() && $params['gallery_image_limit'] > 0 )
			{ // This is a directory/gallery
				if( ( $gallery = $File->get_gallery( $params ) ) != '' )
				{ // Got gallery code
					$galleries[] = $gallery;
				}
				continue;
			}

			if( ! $params['get_rendered_attachments'] )
			{ // Save $r to temp var in order to don't get the rendered data from plugins
				$temp_r = $r;
			}

			$temp_params = $params;
			foreach( $params as $param_key => $param_value )
			{ // Pass all params by reference, in order to give possibility to modify them by plugin
				// So plugins can add some data before/after image tags (E.g. used by infodots plugin)
				$params[ $param_key ] = & $params[ $param_key ];
			}

			// Prepare params before rendering message attachment:
			$Plugins->trigger_event_first_true_with_params( 'PrepareForRenderMessageAttachment', $params );

			if( count( $Plugins->trigger_event_first_true( 'RenderMessageAttachment', $params ) ) != 0 )
			{	// This attachment has been rendered by a plugin (to $params['data']), Skip this from core rendering:
				if( ! $params['get_rendered_attachments'] )
				{ // Restore $r value and mark this message has the rendered attachments
					$r = $temp_r;
					$plugin_render_attachments = true;
				}
				continue;
			}

			if( ! $File->is_image() )
			{	// Skip anything that is not an image:
				continue;
			}

			// Generate the IMG tag with all the alt, title and desc if available:
			$r .= $Link->get_tag( $params );

			$image_counter++;
			$params = $temp_params;
		}

		if( empty( $r ) && $plugin_render_attachments )
		{	// This message doesn't contain the images but it has the rendered attachments by plugins:
			$r .= 'plugin_render_attachments';
		}

		if( ! empty( $r ) )
		{
			$r = $params['before'].$r.$params['after'];

			// Character conversions:
			$r = format_to_output( $r, $format );
		}

		if( ! empty( $galleries ) )
		{	// Append galleries:
			$r .= "\n".format_to_output( implode( "\n", $galleries ), $format );
		}

		return $r;
	}


	/**
	 * Get block of attachments/files linked to the current Message
	 *
	 * @param array Array of params
	 * @param string Output format, see {@link format_to_output()}
	 * @return string HTML
	 */
	function get_files( $params = array(), $format = 'htmlbody' )
	{
		global $Plugins;
		$params = array_merge( array(
				'before' =>              '<div class="message_attachments"><h3>'.T_('Attachments').':</h3><ul class="evo_files">',
				'before_attach' =>         '<li>',
				'before_attach_size' =>    '<span class="file_size">(',
				'after_attach_size' =>     ')</span>',
				'after_attach' =>          '</li>',
				'after' =>               '</ul></div>',
			// fp> TODO: we should only have one limit param. Or is there a good reason for having two?
			// sam2kb> It's needed only for flexibility, in the meantime if user attaches 200 files he expects to see all of them in skin, I think.
				'limit_attach' =>        1000, // Max # of files displayed
				'limit' =>               1000,
				// Optionally restrict to files/images linked to specific position: 'teaser'|'teaserperm'|'teaserlink'|'aftermore'|'inline'|'cover'
				'restrict_to_image_position' => 'inline',
				'exclude_inline_tagged'      => true, // Use true to exclude inline attachments which are already rendered in content by inline tags like '[image:123]'
				'data'                       => '',
				'attach_format'              => '$icon_link$ $file_link$ $file_size$ $file_desc$', // $icon_link$ $icon$ $file_link$ $file_size$ $file_desc$
				'file_link_format'           => '$file_name$', // $icon$ $file_name$ $file_size$ $file_desc$
				'file_link_class'            => '',
				'file_link_text'             => 'filename', // 'filename' - Always display Filename, 'title' - Display Title if available
				'download_link_icon'         => 'download',
				'download_link_title'        => T_('Download file'),
				'display_download_icon'      => true,
				'display_file_size'          => true,
				'display_file_desc'          => false,
				'before_file_desc'           => '<span class="evo_file_description">',
				'after_file_desc'            => '</span>',
			), $params );

		if( empty( $this->ID ) )
		{	// Preview mode for new creating message:
			$tmp_object_ID = param( 'temp_link_owner_ID', 'integer', 0 );
		}
		else
		{	// Normal mode for existing Message in DB:
			$tmp_object_ID = NULL;
		}

		// Get list of attached files:
		$LinkOwner = new LinkMessage( $this, $tmp_object_ID );
		if( ! $LinkList = $LinkOwner->get_attachment_LinkList( $params['limit'], $params['restrict_to_image_position'] ) )
		{
			return '';
		}

		$exclude_link_IDs = array();
		if( $params['exclude_inline_tagged'] )
		{	// Find all links which are already rendered in content by inline tags like '[image:123]':
			preg_match_all( '/\[(image|file|inline|video|audio|thumbnail):(\d+)(:?)([^\]]*)\]/i', $this->text, $inlines );
			$exclude_link_IDs = $inlines[2];
		}

		load_funcs( 'files/model/_file.funcs.php' );

		$r = '';
		$i = 0;
		$r_file = array();
		/**
		 * @var File
		 */
		$File = NULL;
		while( ( $Link = & $LinkList->get_next() ) && $params['limit_attach'] > $i )
		{
			if( in_array( $Link->ID, $exclude_link_IDs ) )
			{	// Skip this link because it is already rendered by inline tag like '[image:123]':
				continue;
			}

			if( ! ( $File = & $Link->get_File() ) )
			{	// No File object:
				global $Debuglog;
				$Debuglog->add( sprintf( 'Link ID#%d of message #%d does not have a file object!', $Link->ID, $this->ID ), array( 'error', 'files' ) );
				continue;
			}

			if( ! $File->exists() )
			{	// File doesn't exist:
				global $Debuglog;
				$Debuglog->add( sprintf( 'File linked to message #%d does not exist (%s)!', $this->ID, $File->get_full_path() ), array( 'error', 'files' ) );
				continue;
			}

			$params['File'] = $File;
			$params['Message'] = $this;

			$temp_params = $params;
			foreach( $params as $param_key => $param_value )
			{	// Pass all params by reference, in order to give possibility to modify them by plugin:
				// So plugins can add some data before/after image tags (E.g. used by infodots plugin)
				$params[ $param_key ] = & $params[ $param_key ];
			}

			if( $Link->get( 'position' ) != 'attachment' )
			{	// Skip not "attachment" links:
				continue;
			}

			// Prepare params before rendering message attachment:
			$Plugins->trigger_event_first_true_with_params( 'PrepareForRenderMessageAttachment', $params );

			if( count( $Plugins->trigger_event_first_true( 'RenderMessageAttachment', $params ) ) != 0 )
			{	// This attachment has been rendered by a plugin (to $params['data']), Skip this from core rendering:
				continue;
			}

			if( $File->is_image() && $Link->get( 'position' ) != 'attachment' )
			{	// Skip images (except those in the attachment position) because these are displayed inline already:
				// fp> TODO: have a setting for each linked file to decide whether it should be displayed inline or as an attachment
				continue;
			}
			elseif( $File->is_dir() )
			{	// Skip directories/galleries:
				continue;
			}

			// A link to download a file:

			// Just icon with download icon:
			$icon = ( $params['display_download_icon'] && $File->exists() && strpos( $params['attach_format'].$params['file_link_format'], '$icon$' ) !== false ) ?
					get_icon( $params['download_link_icon'], 'imgtag', array( 'title' => $params['download_link_title'] ) ) : '';

			// A link with icon to download:
			$icon_link = ( $params['display_download_icon'] && $File->exists() && strpos( $params['attach_format'], '$icon_link$' ) !== false ) ?
					action_icon( $params['download_link_title'], $params['download_link_icon'], $Link->get_download_url(), '', 5 ) : '';

			// File size info:
			$file_size = ( $params['display_file_size'] && $File->exists() && strpos( $params['attach_format'].$params['file_link_format'], '$file_size$' ) !== false ) ?
					$params['before_attach_size'].bytesreadable( $File->get_size(), false, false ).$params['after_attach_size'] : '';

			// File description:
			$file_desc = '';
			if( $params['display_file_desc'] && $File->exists() && strpos( $params['attach_format'].$params['file_link_format'], '$file_desc$' ) !== false )
			{	// If description should be displayed:
				$file_desc = nl2br( trim( $File->get( 'desc' ) ) );
				if( $file_desc !== '' )
				{	// If file has a filled description:
					$params['before_file_desc'].$file_desc.$params['after_file_desc'];
				}
			}

			// A link with file name or file title to download:
			$file_link_format = str_replace( array( '$icon$', '$file_name$', '$file_size$' ),
				array( $icon, '$text$', $file_size ),
				$params['file_link_format'] );
			if( $params['file_link_text'] == 'filename' || trim( $File->get( 'title' ) ) === '' )
			{	// Use file name for link text:
				$file_link_text = $File->get_name();
			}
			else
			{	// Use file title only if it filled:
				$file_link_text = $File->get( 'title' );
			}
			if( $File->exists() )
			{	// Get file link to download if file exists:
				$file_link = ( strpos( $params['attach_format'], '$file_link$' ) !== false ) ?
						$File->get_view_link( $file_link_text, NULL, NULL, $file_link_format, $params['file_link_class'], $Link->get_download_url() ) : '';
			}
			else
			{	// File doesn't exist, We cannot display a link, Display only file name and warning:
				$file_link = ( strpos( $params['attach_format'], '$file_link$' ) !== false ) ?
						$file_link_text.' - <span class="red nowrap">'.get_icon( 'warning_yellow' ).' '.T_('Missing attachment!').'</span>' : '';
			}

			$r_file[$i] = $params['before_attach'];
			$r_file[$i] .= str_replace( array( '$icon$', '$icon_link$', '$file_link$', '$file_size$', '$file_desc$' ),
				array( $icon, $icon_link, $file_link, $file_size, $file_desc ),
				$params['attach_format'] );
			$r_file[$i] .= $params['after_attach'];

			$i++;
			$params = $temp_params;
		}

		if( ! empty( $r_file ) )
		{
			$r = $params['before'].implode( "\n", $r_file ).$params['after'];

			// Character conversions
			$r = format_to_output( $r, $format );
		}

		return $r;
	}
}

?>