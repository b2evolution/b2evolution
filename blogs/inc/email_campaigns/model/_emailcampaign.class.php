<?php
/**
 * This file implements the email campaign class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * EVO FACTORY grants Francois PLANQUE the right to license
 * EVO FACTORY contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _emailcampaign.class.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );


/**
 * Email Campaign Class
 *
 * @package evocore
 */
class EmailCampaign extends DataObject
{
	var $date_ts;

	var $name;

	var $email_title;

	var $email_html;

	var $email_text;

	var $sent_ts;

	/**
	 * @var array|NULL User IDs which assigned for this email campaign
	 *   'all'    - All users which assigned to this campaign
	 *   'accept' - Users which already receive email newsletter
	 *   'wait'   - Users which still didn't receive email by some reason (Probably their newsletter limit was full)
	 */
	var $users = NULL;

	/**
	 * Constructor
	 *
	 * @param object table Database row
	 */
	function EmailCampaign( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_email__campaign', 'ecmp_', 'ecmp_ID', 'date_ts' );

		$this->delete_cascades = array(
				array( 'table'=>'T_email__campaign_send', 'fk'=>'csnd_camp_ID', 'msg'=>T_('%d links with users') ),
			);

		if( $db_row != NULL )
		{
			$this->ID = $db_row->ecmp_ID;
			$this->date_ts = $db_row->ecmp_date_ts;
			$this->name = $db_row->ecmp_name;
			$this->email_title = $db_row->ecmp_email_title;
			$this->email_html = $db_row->ecmp_email_html;
			$this->email_text = $db_row->ecmp_email_text;
			$this->sent_ts = $db_row->ecmp_sent_ts;
		}
	}


	/**
	 * Add users for this campaign in DB
	 */
	function add_users()
	{
		global $DB;

		// Get user IDs from current filterset of users list
		$new_users_IDs = get_filterset_user_IDs();

		if( count( $new_users_IDs ) )
		{ // Users are found in the filterset

			// Get all active users which accept newsletter email
			$new_users_SQL = get_newsletter_users_sql( $new_users_IDs );
			$new_users = $DB->get_col( $new_users_SQL->get() );

			// Remove the users which didn't accept email before
			$DB->query( 'DELETE FROM T_email__campaign_send
				WHERE csnd_camp_ID = '.$DB->quote( $this->ID ).'
				 AND csnd_emlog_ID IS NULL' );

			// Get users which already accept newsletter email
			$old_users = $this->get_users( 'accept' );

			// Exclude old users from new users (To store value of csnd_emlog_ID)
			$new_users = array_diff( $new_users, $old_users );

			if( count( $new_users ) )
			{ // Insert new users for this campaign
				$insert_SQL = 'INSERT INTO T_email__campaign_send ( csnd_camp_ID, csnd_user_ID ) VALUES';
				foreach( $new_users as $user_ID )
				{
					$insert_SQL .= "\n".'( '.$DB->quote( $this->ID ).', '.$DB->quote( $user_ID ).' ),';
				}
				$DB->query( substr( $insert_SQL, 0, -1 ) );
			}
		}
	}


	/**
	 * Get user IDs of this campaign
	 *
	 * @param string Type of users:
	 *   'all'    - All users which assigned to this campaign
	 *   'accept' - Users which already receive email newsletter
	 *   'wait'   - Users which still didn't receive email by some reason (Probably their newsletter limit was full)
	 * @return array user IDs
	 */
	function get_users( $type = 'all' )
	{
		global $DB;

		if( !is_null( $this->users ) )
		{ // Get users from cache
			return $this->users[ $type ];
		}

		// Get users from DB
		$users_SQL = new SQL();
		$users_SQL->SELECT( 'csnd_user_ID, csnd_emlog_ID' );
		$users_SQL->FROM( 'T_email__campaign_send' );
		$users_SQL->WHERE( 'csnd_camp_ID = '.$DB->quote( $this->ID ) );
		$users = $DB->get_assoc( $users_SQL->get() );

		$this->users['all'] = array();
		$this->users['accept'] = array();
		$this->users['wait'] = array();

		foreach( $users as $user_ID => $emlog_ID )
		{
			$this->users['all'][] = $user_ID;
			if( $emlog_ID > 0 )
			{ // This user already accepted newsletter email
				$this->users['accept'][] = $user_ID;
			}
			else
			{ // This user didn't still accept email
				$this->users['wait'][] = $user_ID;
			}
		}

		return $this->users[ $type ];
	}


	/**
	 * Get the users number of this campaign
	 *
	 * @param string Type of users:
	 *   'all'    - All users which assigned to this campaign
	 *   'accept' - Users which already receive email newsletter
	 *   'wait'   - Users which still didn't receive email by some reason (Probably their newsletter limit was full)
	 * @return integer Number of users
	 */
	function get_users_count( $type = 'all' )
	{
		return count( $this->get_users( $type ) );
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		if( param( 'ecmp_name', 'string', NULL ) !== NULL )
		{ // Name
			param_string_not_empty( 'ecmp_name', T_('Please enter campaign name.') );
			$this->set_from_Request( 'name' );
		}

		if( param( 'ecmp_email_title', 'string', NULL ) !== NULL )
		{ // Email title
			param_string_not_empty( 'ecmp_email_title', T_('Please enter email title.') );
			$this->set_from_Request( 'email_title' );
		}

		if( param( 'ecmp_email_html', 'html', NULL ) !== NULL )
		{ // Email HTML message
			param_check_html( 'ecmp_email_html', T_('Please enter HTML message.') );
			$this->set_from_Request( 'email_html' );
		}

		if( param( 'ecmp_email_text', 'text', NULL ) !== NULL )
		{ // Email Plain Text message
			$this->set_from_Request( 'email_text' );
		}

		return ! param_errors_detected();
	}


	/**
	 * Check if campaign are ready to send emails
	 *
	 * @param boolean TRUE to display messages about empty fields
	 * @param string Mode: 'test' - used to don't check some fields
	 * @return boolean TRUE if all fields are filled
	 */
	function check( $display_messages = true, $mode = '' )
	{
		if( $display_messages )
		{ // Display message
			global $Messages;
		}

		$result = true;

		if( empty( $this->email_title ) )
		{ // Email title is empty
			if( $display_messages )
			{
				$Messages->add( T_('Please enter email title'), 'error' );
			}
			$result = false;
		}

		if( empty( $this->email_html ) )
		{ // Email html message is empty
			if( $display_messages )
			{
				$Messages->add( T_('Please enter email HTML message'), 'error' );
			}
			$result = false;
		}

		if( empty( $this->email_text ) )
		{ // Email text message is empty
			if( $display_messages )
			{
				$Messages->add( T_('Please enter email Plain Text message'), 'error' );
			}
			$result = false;
		}

		if( $mode != 'test' && count( $this->get_users( 'wait' ) ) == 0 )
		{ // No users found which wait this newsletter
			if( $display_messages )
			{
				$Messages->add( T_('No users found to send email newsletters'), 'error' );
			}
			$result = false;
		}

		return $result;
	}


	/**
	 * Send one email
	 *
	 * @param integer User ID
	 * @param string Email address
	 * @param string Mode: 'test' - to send test email newsletter
	 * @return boolean TRUE on success
	 */
	function send_email( $user_ID, $email_address = '', $mode = '' )
	{
		$newsletter_params = array(
				'message_html' => $this->get( 'email_html' ),
				'message_text' => $this->get( 'email_text' ),
			);

		$email_template = $mode == 'test' ? 'newsletter_test' : 'newsletter';

		return send_mail_to_User( $user_ID, $this->get( 'email_title' ), $email_template, $newsletter_params, false, array(), $email_address );
	}


	/**
	 * Send email newsletter for all users of this campaign
	 *
	 * @param boolean
	 */
	function send_all_emails( $display_messages = true )
	{
		global $DB, $localtimenow, $mail_log_insert_ID;

		// Send emails only for users which still don't accept emails
		$user_IDs = $this->get_users( 'wait' );

		if( empty( $user_IDs ) )
		{ // No users, Exit here
			return;
		}

		$DB->begin();

		// Update date of sending
		$this->set( 'sent_ts', date( 'Y-m-d H:i:s', $localtimenow ) );
		$this->dbupdate();

		if( $display_messages )
		{ // We need in this cache when display the messages
			$UserCache = & get_UserCache();
		}

		foreach( $user_IDs as $user_ID )
		{
			$result = $this->send_email( $user_ID );

			if( $result )
			{ // Email newsletter was sent for user successfully
				if( !empty( $mail_log_insert_ID ) )
				{ // ID of last inserted mail log is defined in function mail_log()
					$DB->query( 'UPDATE T_email__campaign_send
							SET csnd_emlog_ID = '.$DB->quote( $mail_log_insert_ID ).'
						WHERE csnd_camp_ID = '.$DB->quote( $this->ID ).'
							AND csnd_user_ID = '.$DB->quote( $user_ID ) );

					// Update arrays where we store which users accepted email and who waiting it now
					$this->users['accept'][] = $user_ID;
					if( ( $wait_user_ID_key = array_search( $user_ID, $this->users['wait'] ) ) !== false )
					{
						unset( $this->users['wait'][ $wait_user_ID_key ] );
					}
				}
			}

			if( $display_messages )
			{ // Print the messages
				$User = & $UserCache->get_by_ID( $user_ID, false, false );

				if( $result === true )
				{ // Success
					echo sprintf( T_('Email was sent to user: %s'), $User->get_identity_link() ).'<br />';
				}
				else
				{ // Failed, Email was NOT sent
					if( ! check_allow_new_email( 'newsletter_limit', 'last_newsletter', $user_ID ) )
					{ // Newsletter email is limited today for this user
						echo '<span class="orange">'.sprintf( T_('User %s has already received max # of newsletters today.'), $User->get_identity_link() ).'</span><br />';
					}
					else
					{ // Another error
						echo '<span class="red">'.sprintf( T_('Email was not sent to user: %s'), $User->get_identity_link() ).'</span><br />';
					}
				}

				evo_flush();
			}
		}

		$DB->commit();
	}
}

?>