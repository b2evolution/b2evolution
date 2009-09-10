<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class('_core/model/dataobjects/_dataobject.class.php');

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
		// Text
		$this->set_string_from_param( 'text', true );

		// Thread
		if( empty($this->thread_ID) )
		{
			$this->Thread->load_from_Request();
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
			case 'text':
				return $this->set_param( $parname, 'string', $parvalue, $make_null);
			default:
				return parent::set( $parname, $parvalue, $make_null );
		}
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
	function dbinsert()
	{
		global $DB, $localtimenow;

		if( $this->ID != 0 ) die( 'Existing object cannot be inserted!' );

		$DB->begin();

		// Create thread for new message

		$this->get_Thread();

		if( $this->Thread->ID == 0 && !$this->Thread->dbinsert() )
		{
			$DB->rollback();

			$Log->add( 'Thread has not been creted.', 'error' );
			return false;
		}
		else
		{
			$this->Thread->set_param( 'datemodified', 'string', date('Y-m-d H:i:s', $localtimenow) );
			$this->Thread->dbupdate();

			// Load recipients - Lazy filled
			$this->Thread->load_recipients();
		}

		$this->set_param( 'thread_ID', 'integer', $this->Thread->ID);


		if( $result = parent::dbinsert() )
		{ // We can insert message status for each recipient

			$sql = 'INSERT INTO T_messaging__msgstatus (msta_thread_ID, msta_msg_ID, msta_user_ID, msta_status)
								VALUES';

			foreach ($this->Thread->recipients_list as $recipient_ID)
			{
				$sql .= ' ('.$this->thread_ID.', '.$this->ID.', '.$recipient_ID.', '.$this->UNREAD.'),';
			}
			$sql .= ' ('.$this->thread_ID.', '.$this->ID.', '.$this->author_user_ID.', '.$this->AUTHOR.')';

			$DB->query( $sql, 'Insert message statuses' );
		}

		$DB->commit();

		return true;
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

		// Delete Statuses
		$DB->query( 'DELETE FROM T_messaging__msgstatus
												WHERE msta_msg_ID='.$this->ID );

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
	 * Check relations
	 *
	 * @param string $message
	 * @return boolean result
	 */
	function check_delete( $message )
	{
		global $DB, $Messages;

		if( ! parent::check_delete( $message ) )
		{
			return false;
		}

		$var = $DB->get_var('SELECT COUNT(*) FROM T_messaging__msgstatus WHERE msta_thread_ID = '.$this->thread_ID);

		if( $var == 1 )
		{
			$Messages->add( '', 'restrict' );
			$Messages->head = array(
					'container' => $message,
					'restrict' => T_('Last message of the thread can\'t be deleted.')
				);

			return false;
		}

		return true;
	}

}

?>
