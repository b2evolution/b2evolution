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
		param( 'thrd_title', 'string' );
		param_check_not_empty( 'thrd_title', sprintf( T_('The field &laquo;%s&raquo; cannot be empty.'), T_('Subject') ) );
		$this->set_from_Request( 'title', 'thrd_title' );

		// Resipients
		$this->set_string_from_param( 'recipients', true );

		$this->param_check__recipients( 'thrd_recipients', $thrd_recipients );

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
	 * Check are recipients available in database
	 *
	 * @param string recipients
	 */
	function param_check__recipients ( $var, $recipients )
	{
		global $DB, $current_User;

		// split recipients into array using comma separator
		$recipients_list = array();
		foreach ( explode(',', $recipients) as $recipient )
		{
			$recipients_list[] = strtolower(trim($recipient));
		}

		$error_msg = '';

		// check has recipients list login of current user
		if( in_array( $current_User->login, $recipients_list ) )
		{
			$error_msg = sprintf( T_( 'You cannot send threads to yourself: %s' ), $current_User->login );
		}

		// select all users from database
		$db_users_list = array();
		foreach( $DB->get_results( 'SELECT user_ID, user_login
									FROM T_users') as $row )
		{
			$db_users_list[$row->user_login] = $row->user_ID;
		}

		// check are recipients available in database
		$this->recipients_list = array();
		$unavailable_recipients_list = array();
		foreach( $recipients_list as $recipient )
		{
			if ( array_key_exists( $recipient, $db_users_list ) )
			{
				$this->recipients_list[] = $db_users_list[$recipient];
			}
			else
			{
				$unavailable_recipients_list[] = $recipient;
			}
		}

		if ( count( $unavailable_recipients_list ) > 0 )
		{
			if ( ! empty( $error_msg ) )
			{
				$error_msg .= '<br />';
			}

			$error_msg .= sprintf( 'The following users were not found: %s', implode( ', ', $unavailable_recipients_list ) );
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
		global $DB;

		if( $this->ID == 0 ) debug_die( 'Non persistant object cannot be deleted!' );

		$DB->begin();

		// Delete Messages
		$ret = $DB->query( 'DELETE FROM T_messaging__message
												WHERE msg_thread_ID='.$this->ID );
		// Delete Statuses
		$ret = $DB->query( 'DELETE FROM T_messaging__threadstatus
												WHERE tsta_thread_ID='.$this->ID );
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

/*
 * $Log$
 * Revision 1.7  2009/09/14 13:52:07  tblue246
 * Translation fixes; removed now pointless doc comment.
 *
 * Revision 1.6  2009/09/14 13:20:56  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.5  2009/09/14 10:33:20  efy-maxim
 * messagin module improvements
 *
 * Revision 1.4  2009/09/12 18:44:11  efy-maxim
 * Messaging module improvements
 *
 * Revision 1.3  2009/09/10 18:24:07  fplanque
 * doc
 *
 */
?>
