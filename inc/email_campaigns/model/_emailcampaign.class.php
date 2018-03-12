<?php
/**
 * This file implements the email campaign class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evocore
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

	var $enlt_ID;

	var $email_title;

	var $email_html;

	var $email_text;

	var $email_plaintext;

	var $sent_ts;

	var $auto_sent_ts;

	var $user_tag_sendskip;

	var $user_tag_sendsuccess;

	var $user_tag;

	var $user_tag_cta1;

	var $user_tag_cta2;

	var $user_tag_cta3;

	var $user_tag_like;

	var $user_tag_dislike;

	var $use_wysiwyg = 0;

	var $send_ctsk_ID;

	var $auto_send = 'no';

	var $sequence;

	var $Newsletter = NULL;

	/**
	 * @var array|NULL User IDs which assigned for this email campaign
	 *   'all'     - All active users which accept newsletter of this campaign
	 *   'filter'  - Filtered active users which accept newsletter of this campaign
	 *   'receive' - Users which already received email newsletter
	 *   'wait'    - Users which still didn't receive email by some reason (Probably their newsletter limit was full)
	 */
	var $users = NULL;

	/**
	 * @var string
	 */
	var $renderers;

	/**
	 * Constructor
	 *
	 * @param object table Database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_email__campaign', 'ecmp_', 'ecmp_ID', 'date_ts' );

		if( $db_row == NULL )
		{
			$this->set_renderers( array( 'default' ) );
		}
		else
		{
			$this->ID = $db_row->ecmp_ID;
			$this->date_ts = $db_row->ecmp_date_ts;
			$this->enlt_ID = $db_row->ecmp_enlt_ID;
			$this->email_title = $db_row->ecmp_email_title;
			$this->email_html = $db_row->ecmp_email_html;
			$this->email_text = $db_row->ecmp_email_text;
			$this->email_plaintext = $db_row->ecmp_email_plaintext;
			$this->sent_ts = $db_row->ecmp_sent_ts;
			$this->auto_sent_ts = $db_row->ecmp_auto_sent_ts;
			$this->renderers = $db_row->ecmp_renderers;
			$this->use_wysiwyg = $db_row->ecmp_use_wysiwyg;
			$this->send_ctsk_ID = $db_row->ecmp_send_ctsk_ID;
			$this->auto_send = $db_row->ecmp_auto_send;
			$this->user_tag_sendskip = $db_row->ecmp_user_tag_sendskip;
			$this->user_tag_sendsuccess = $db_row->ecmp_user_tag_sendsuccess;
			$this->user_tag = $db_row->ecmp_user_tag;
			$this->user_tag_cta1 = $db_row->ecmp_user_tag_cta1;
			$this->user_tag_cta2 = $db_row->ecmp_user_tag_cta2;
			$this->user_tag_cta3 = $db_row->ecmp_user_tag_cta3;
			$this->user_tag_like = $db_row->ecmp_user_tag_like;
			$this->user_tag_dislike = $db_row->ecmp_user_tag_dislike;
		}
	}


	/**
	 * Get delete cascade settings
	 *
	 * @return array
	 */
	static function get_delete_cascades()
	{
		return array(
				array( 'table'=>'T_email__campaign_send', 'fk'=>'csnd_camp_ID', 'msg'=>T_('%d links with users') ),
				array( 'table'=>'T_links', 'fk'=>'link_ecmp_ID', 'msg'=>T_('%d links to destination email campaigns'),
						'class'=>'Link', 'class_path'=>'links/model/_link.class.php' ),
			);
	}


	/**
	 * Get delete restriction settings
	 *
	 * @return array
	 */
	static function get_delete_restrictions()
	{
		return array(
				array( 'table' => 'T_automation__step', 'fk' => 'step_info', 'and_condition' => 'step_type = "send_campaign"', 'msg' => T_('%d automation steps use this email campaign') ),
			);
	}


	/**
	 * Get name of this campaign, it is used for `<select>` by cache object
	 *
	 * @return string
	 */
	function get_name()
	{
		return $this->get( 'email_title' );
	}


	/**
	 * Add recipients for this campaign into DB
	 *
	 * @param array|NULL Array of user IDs, NULL - to get user IDs from current filterset of users list
	 */
	function add_recipients( $filtered_users_IDs = NULL )
	{
		global $DB;

		if( $filtered_users_IDs === NULL )
		{	// Get user IDs from current filterset of users list:
			$filtered_users_IDs = get_filterset_user_IDs();
		}

		if( count( $filtered_users_IDs ) )
		{	// If users are found in the filterset

			// Get all active users which accept email newsletter of this campaign:
			$new_users_SQL = new SQL( 'Get recipients of list #'.$this->get( 'enlt_ID' ) );
			$new_users_SQL->SELECT( 'user_ID' );
			$new_users_SQL->FROM( 'T_users' );
			$new_users_SQL->FROM_add( 'INNER JOIN T_email__newsletter_subscription ON enls_user_ID = user_ID AND enls_subscribed = 1' );
			$new_users_SQL->WHERE( 'user_ID IN ( '.$DB->quote( $filtered_users_IDs ).' )' );
			$new_users_SQL->WHERE_and( 'user_status IN ( "activated", "autoactivated" )' );
			$new_users_SQL->WHERE_and( 'enls_enlt_ID = '.$DB->quote( $this->get( 'enlt_ID' ) ) );
			$new_users = $DB->get_col( $new_users_SQL->get(), 0, $new_users_SQL->title );

			// Remove the filtered recipients which didn't receive email newsletter yet:
			$this->remove_recipients();

			// Get users which already received email newsletter:
			$old_users = $this->get_recipients( 'receive' );

			// Exclude old users from new users (To store value of csnd_emlog_ID):
			$new_users = array_diff( $new_users, $old_users );

			if( count( $new_users ) )
			{	// Insert new users for this campaign:
				$insert_SQL = 'INSERT INTO T_email__campaign_send ( csnd_camp_ID, csnd_user_ID, csnd_status ) VALUES';
				foreach( $new_users as $user_ID )
				{
					$insert_SQL .= "\n".'( '.$DB->quote( $this->ID ).', '.$DB->quote( $user_ID ).', "ready_to_send" ),';
				}
				$DB->query( substr( $insert_SQL, 0, -1 ) );
			}
		}
	}


	/**
	 * Remove the filtered recipients which didn't receive email newsletter yet
	 */
	function remove_recipients()
	{
		if( empty( $this->ID ) )
		{	// Email campaign must be created in DB:
			return;
		}

		global $DB;

		// Manually skipped users are considered to have already received the email newsletter already
		$DB->query( 'DELETE FROM T_email__campaign_send
			WHERE csnd_camp_ID = '.$DB->quote( $this->ID ).'
				AND csnd_emlog_ID IS NULL
				AND NOT csnd_status = "skipped"' );
	}


	/**
	 * Get a member param by its name
	 *
	 * @param mixed Name of parameter
	 * @return mixed Value of parameter
	 */
	function get( $parname )
	{
		switch( $parname )
		{
			case 'name':
				if( $Newsletter = & $this->get_Newsletter() )
				{	// Get name of newsletter:
					return $Newsletter->get( 'name' );
				}
				else
				{	// Get email title of this campaign:
					return $this->get( 'email_title' );
				}
				break;

			default:
				return parent::get( $parname );
		}
	}


	/**
	 * Get Newsletter object of this email campaign
	 *
	 * @return object Newsletter
	 */
	function & get_Newsletter()
	{
		if( ! isset( $this->Newsletter ) )
		{	// Initialize Newsletter:
			$NewsletterCache = & get_NewsletterCache();
			$this->Newsletter = & $NewsletterCache->get_by_ID( $this->get( 'enlt_ID', false, false ) );
		}

		return $this->Newsletter;
	}


	/**
	 * Get recipient user IDs of this campaign
	 *
	 * @param string Type of users:
	 *   'all'     - All active users which accept newsletter of this campaign
	 *   'filter'  - Filtered active users which accept newsletter of this campaign
	 *   'receive' - Users which already received email newsletter
	 *   'skipped' - Users which will not receive email newsletter
	 *   'wait'    - Users which still didn't receive email by some reason (Probably their newsletter limit was full)
	 *   Use same keys with prefix 'unsub_' for users are NOT subscribed to Newsletter of this Email Campaign,
	 *   Use same keys with prefix 'full_' for users which are linked with this Email Campaign somehow,
	 * @return array user IDs
	 */
	function get_recipients( $type = 'all' )
	{
		// Make sure all recipients are loaded into cache:
		$this->load_recipients();

		if( isset( $this->users[ $type ] ) )
		{	// Get subscribed OR unsubscribed users:
			return $this->users[ $type ];
		}
		elseif( substr( $type, 0, 5 ) === 'full_' )
		{	// Get subscribed AND unsubscribed users:
			$type_key = substr( $type, 5 );
			if( isset( $this->users[ $type_key ], $this->users[ 'unsub_'.$type_key ] ) )
			{
				return array_merge( $this->users[ $type_key ], $this->users[ 'unsub_'.$type_key ] );
			}
		}

		// Unknown type:
		debug_die( 'Unknown recipients type "'.$type.'" for Email Campaign #'.$this->ID );
	}


	/**
	 * Load all recipient user IDs of this campaign into cache array $this->users
	 */
	function load_recipients()
	{
		global $DB;

		if( $this->users !== NULL )
		{	// Recipients already were loaded into cache:
			return;
		}

		// Get users from DB:
		$users_SQL = new SQL( 'Get recipients of campaign #'.$this->ID );
		$users_SQL->SELECT( 'user_ID, csnd_emlog_ID, csnd_user_ID, csnd_status, enls_user_ID' );
		$users_SQL->FROM( 'T_users' );
		$users_SQL->FROM_add( 'INNER JOIN T_email__campaign_send ON ( csnd_camp_ID = '.$DB->quote( $this->ID ).' AND ( csnd_user_ID = user_ID OR csnd_user_ID IS NULL ) )' );
		$users_SQL->FROM_add( 'LEFT JOIN T_email__newsletter_subscription ON enls_user_ID = user_ID AND enls_subscribed = 1 AND enls_enlt_ID = '.$DB->quote( $this->get( 'enlt_ID' ) ) );
		$users_SQL->WHERE( 'user_status IN ( "activated", "autoactivated" )' );
		$users = $DB->get_results( $users_SQL->get(), OBJECT, $users_SQL->title );

		$this->users = array(
				// Users are subscribed to Newsletter of this Email Campaign:
				'all'               => array(),
				'filter'            => array(),
				'receive'           => array(),
				'skipped'           => array(),
				'skipped_tag'       => array(),
				'error'             => array(),
				'wait'              => array(),
				// Users are NOT subscribed to Newsletter of this Email Campaign:
				'unsub_all'         => array(),
				'unsub_filter'      => array(),
				'unsub_receive'     => array(),
				'unsub_skipped'     => array(),
				'unsub_skipped_tag' => array(),
				'unsub_error'       => array(),
				'unsub_wait'        => array(),
				// All Users which are linked with this Email Campaign somehow:
				// Use prefix 'full_' like 'full_all', 'full_filter' and etc.
			);

		foreach( $users as $user_data )
		{
			if( $user_data->enls_user_ID === NULL )
			{	// This user is unsubscribed from newsletter of this email campaign:
				$this->users['unsub_all'][] = $user_data->user_ID;
			}
			else
			{	// This user is subscribed to newsletter of this email campaign:
				$this->users['all'][] = $user_data->user_ID;
			}

			if( $user_data->csnd_status == 'sent' )
			{	// This user already received newsletter email:
				if( $user_data->enls_user_ID === NULL )
				{	// This user is unsubscribed from newsletter of this email campaign:
					$this->users['unsub_receive'][] = $user_data->user_ID;
					$this->users['unsub_filter'][] = $user_data->user_ID;
				}
				else
				{	// This user is subscribed to newsletter of this email campaign:
					$this->users['receive'][] = $user_data->user_ID;
					$this->users['filter'][] = $user_data->user_ID;
				}
			}
			elseif( $user_data->csnd_status == 'skipped' )
			{ // This user will be skipped from receiving newsletter email:
				if( $user_data->enls_user_ID === NULL )
				{	// This user is unsubscribed from newsletter of this email campaign:
					$this->users['unsub_skipped'][] = $user_data->user_ID;
					$this->users['unsub_filter'][] = $user_data->user_ID;
				}
				else
				{	// This user is subscribed to newsletter of this email campaign:
					$this->users['skipped'][] = $user_data->user_ID;
					$this->users['filter'][] = $user_data->user_ID;
				}
			}
			elseif( check_usertags( $user_data->user_ID, explode( ',', $this->get( 'user_tag_sendskip' ) ), 'has_any' ) )
			{	// This user will be skipped from receiving newsletter email because of skip tags:
				if( $user_data->enls_user_ID === NULL )
				{	// This user is unsubscribed from newsletter of this email campaign:
					$this->users['unsub_skipped_tag'][] = $user_data->user_ID;
					$this->users['unsub_filter'][] = $user_data->user_ID;
				}
				else
				{	// This user is subscribed to newsletter of this email campaign:
					$this->users['skipped_tag'][] = $user_data->user_ID;
					$this->users['filter'][] = $user_data->user_ID;
				}
			}
			elseif( $user_data->csnd_status == 'send_error' )
			{ // We encountered a send error the last time we attempted to send email,:
				if( $user_data->enls_user_ID === NULL )
				{	// This user is unsubscribed from newsletter of this email campaign:
					$this->users['unsub_error'][] = $user_data->user_ID;
					$this->users['unsub_filter'][] = $user_data->user_ID;
				}
				else
				{	// This user is subscribed to newsletter of this email campaign:
					$this->users['error'][] = $user_data->user_ID;
					$this->users['filter'][] = $user_data->user_ID;
				}
			}
			elseif( $user_data->csnd_user_ID > 0 ) // Includes failed email attempts
			{	// This user didn't receive email yet:
				if( $user_data->enls_user_ID === NULL )
				{	// This user is unsubscribed from newsletter of this email campaign:
					$this->users['unsub_wait'][] = $user_data->user_ID;
					$this->users['unsub_filter'][] = $user_data->user_ID;
				}
				else
				{	// This user is subscribed to newsletter of this email campaign:
					$this->users['wait'][] = $user_data->user_ID;
					$this->users['filter'][] = $user_data->user_ID;
				}
			}
		}
	}


	/**
	 * Get the recipients number of this campaign
	 *
	 * @param string Type of users:
	 *   'all'     - All active users which accept newsletter of this campaign
	 *   'filter'  - Filtered active users which accept newsletter of this campaign
	 *   'receive' - Users which already received email newsletter
	 *   'wait'    - Users which still didn't receive email by some reason (Probably their newsletter limit was full)
	 * @param boolean TRUE to return as link to page with recipients list
	 * @return integer Number of users
	 */
	function get_recipients_count( $type = 'all', $link = false )
	{
		$recipients_count = count( $this->get_recipients( $type ) );

		if( $link )
		{	// Initialize URL to page with reciepients of this Email Campaign:
			$campaign_edit_modes = get_campaign_edit_modes( $this->ID );
			switch( $type )
			{
				case 'receive':
					$recipient_type = 'sent';
					break;
				case 'skipped':
					$recipient_type = 'skipped';
					break;
				case 'wait':
					$recipient_type = 'ready_to_send';
					break;
				case 'filter':
				default:
					$recipient_type = 'filtered';
					break;
			}

			$unsub_recipients_count = count( $this->get_recipients( 'unsub_'.$type ) );
			if( $unsub_recipients_count > 0 )
			{	// If unsubscribed users exist:
				$recipients_count = $recipients_count.' ('.T_('still subscribed').') + '.$unsub_recipients_count.' ('.T_('unsubscribed').')';
			}
			$recipients_count = '<a href="'.$campaign_edit_modes['recipient']['href'].( empty( $type ) ? '' : '&amp;recipient_type='.$recipient_type ).'">'.$recipients_count.'</a>';
		}

		return $recipients_count;
	}


	/**
	 * Insert object into DB based on previously recorded changes.
	 *
	 * @return boolean true
	 */
	function dbinsert()
	{
		// Update the message fields:
		$this->update_message_fields();

		$r = parent::dbinsert();

		// Update recipients:
		$this->update_recipients();

		return $r;
	}


	/**
	 * Update the DB based on previously recorded changes
	 *
	 * @return boolean true on success, false on failure to update, NULL if no update necessary
	 */
	function dbupdate()
	{
		// Update the message fields:
		$this->update_message_fields();

		$r = parent::dbupdate();

		// Update recipients only if newsletter has been changed:
		$this->update_recipients();

		return $r;
	}


	/**
	 * Update the message fields:
	 *     - email_html - Result of the rendered plugins from email_text
	 *     - email_plaintext - Text extraction from email_html
	 */
	function update_message_fields()
	{
		global $Plugins;

		$email_text = $this->get( 'email_text' );

		// Render inline file tags like [image:123:caption] or [file:123:caption] :
		$email_text = render_inline_files( $email_text, $this, array(
				'check_code_block' => true,
				'image_size'       => 'original',
			) );

		// This must get triggered before any internal validation and must pass all relevant params.
		$Plugins->trigger_event( 'EmailFormSent', array(
				'content'         => & $email_text,
				'dont_remove_pre' => true,
				'renderers'       => $this->get_renderers_validated(),
			) );

		// Save prerendered message:
		$Plugins->trigger_event( 'FilterEmailContent', array(
				'data'          => & $email_text,
				'EmailCampaign' => $this
			) );
		$this->set( 'email_html', format_to_output( $email_text ) );

		// Save plain-text message:
		$email_plaintext = preg_replace( '#<a[^>]+href="([^"]+)"[^>]*>[^<]*</a>#i', ' [ $1 ] ', $this->get( 'email_html' ) );
		$email_plaintext = preg_replace( '#<img[^>]+src="([^"]+)"[^>]*>#i', ' [ $1 ] ', $email_plaintext );
		$email_plaintext = preg_replace( '#[\n\r]#i', ' ', $email_plaintext );
		$email_plaintext = preg_replace( '#<(p|/h[1-6]|ul|ol)[^>]*>#i', "\n\n", $email_plaintext );
		$email_plaintext = preg_replace( '#<(br|h[1-6]|/li|code|pre|div|/?blockquote)[^>]*>#i', "\n", $email_plaintext );
		$email_plaintext = preg_replace( '#<li[^>]*>#i', "- ", $email_plaintext );
		$email_plaintext = preg_replace( '#<hr ?/?>#i', "\n\n----------------\n\n", $email_plaintext );
		$this->set( 'email_plaintext', strip_tags( $email_plaintext ) );
	}


	/**
	 * Update recipients after newsletter of this email campaign was changed
	 *
	 * @param boolean TRUE to force the updating
	 */
	function update_recipients( $force_update = false )
	{
		if( empty( $this->ID ) )
		{	// Email campaign must be created in DB:
			return;
		}

		if( ! $force_update && empty( $this->newsletter_is_changed ) )
		{	// Newsletter of this email campaign was not changed, Don't update recipients:
			return;
		}

		global $DB;

		// Remove the filtered recipients of previous newsletter which didn't receive it yet:
		$this->remove_recipients();

		// Insert recipients of current newsletter:
		$DB->query( 'INSERT INTO T_email__campaign_send ( csnd_camp_ID, csnd_user_ID, csnd_status )
			SELECT '.$this->ID.', enls_user_ID, "ready_to_send"
			  FROM T_email__newsletter_subscription
			 WHERE enls_enlt_ID = '.$this->get( 'enlt_ID' ).'
				 AND enls_subscribed = 1
			 ON DUPLICATE KEY UPDATE csnd_camp_ID = csnd_camp_ID, csnd_user_ID = csnd_user_ID' );
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		global $Plugins;

		if( param( 'ecmp_enlt_ID', 'integer', NULL ) !== NULL )
		{	// Newsletter ID:
			param_string_not_empty( 'ecmp_enlt_ID', T_('Please select a list.') );
			$this->newsletter_is_changed = ( get_param( 'ecmp_enlt_ID' ) != $this->get( 'enlt_ID' ) );
			$this->set_from_Request( 'enlt_ID' );
		}

		if( param( 'ecmp_email_title', 'string', NULL ) !== NULL )
		{	// Email title:
			param_string_not_empty( 'ecmp_email_title', T_('Please enter an email title.') );
			$this->set_from_Request( 'email_title' );
		}

		if( param( 'ecmp_email_html', 'html', NULL ) !== NULL )
		{	// Email HTML message:
			param_check_html( 'ecmp_email_html', T_('Please enter an HTML message.') );
			$this->set_from_Request( 'email_html' );
		}

		// Renderers:
		if( param( 'renderers_displayed', 'integer', 0 ) )
		{	// use "renderers" value only if it has been displayed (may be empty):
			$renderers = $Plugins->validate_renderer_list( param( 'renderers', 'array:string', array() ), array( 'EmailCampaign' => & $this ) );
			$this->set_renderers( $renderers );
		}

		if( param( 'ecmp_email_text', 'html', NULL ) !== NULL )
		{	// Save original message:
			$this->set_from_Request( 'email_text' );
		}

		if( param( 'ecmp_auto_send', 'string', NULL ) !== NULL )
		{	// Auto send:
			$this->set_from_Request( 'auto_send' );
		}

		if( param( 'ecmp_user_tag_sendskip', 'string', NULL ) !== NULL )
		{	// User tag:
			$this->set_from_Request( 'user_tag_sendskip' );
		}

		if( param( 'ecmp_user_tag_sendsuccess', 'string', NULL ) !== NULL )
		{	// User tag:
			$this->set_from_Request( 'user_tag_sendsuccess' );
		}

		if( param( 'ecmp_user_tag', 'string', NULL ) !== NULL )
		{ // User tag:
			$this->set_from_Request( 'user_tag' );
		}

		if( param( 'ecmp_user_tag_cta1', 'string', NULL ) !== NULL )
		{ // User tag:
			$this->set_from_Request( 'user_tag_cta1' );
		}

		if( param( 'ecmp_user_tag_cta2', 'string', NULL ) !== NULL )
		{ // User tag:
			$this->set_from_Request( 'user_tag_cta2' );
		}

		if( param( 'ecmp_user_tag_cta3', 'string', NULL ) !== NULL )
		{ // User tag:
			$this->set_from_Request( 'user_tag_cta3' );
		}

		if( param( 'ecmp_user_tag_like', 'string', NULL ) !== NULL )
		{ // User tag:
			$this->set_from_Request( 'user_tag_like' );
		}

		if( param( 'ecmp_user_tag_dislike', 'string', NULL ) !== NULL )
		{ // User tag:
			$this->set_from_Request( 'user_tag_dislike' );
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
				$Messages->add_to_group( T_('Please enter an email title for this campaign.'), 'error', T_('Validation errors:') );
			}
			$result = false;
		}

		if( empty( $this->email_text ) )
		{	// Email message is empty:
			if( $display_messages )
			{
				$Messages->add_to_group( T_('Please enter the email text for this campaign.'), 'error', T_('Validation errors:') );
			}
			$result = false;
		}

		if( $mode != 'test' && count( $this->get_recipients( 'wait' ) ) == 0 )
		{ // No users found which wait this newsletter
			if( $display_messages )
			{
				$Messages->add_to_group( T_('No recipients found for this campaign.'), 'error', T_('Validation errors:') );
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
	 * @param string|boolean Update time of last sending: 'auto', 'manual', FALSE - to don't update
	 * @param integer Newsletter ID, used for unsubscribe link in email footer, NULL - to use Newsletter ID of this Email Campaign
	 * @param integer Automation ID, used to store in mail log
	 * @return boolean TRUE on success
	 */
	function send_email( $user_ID, $email_address = '', $mode = '', $update_sent_ts = false, $newsletter_ID = NULL, $automation_ID = NULL )
	{
		global $localtimenow;

		$newsletter_params = array(
				'include_greeting' => false,
				'message_html'     => $this->get( 'email_html' ),
				'message_text'     => $this->get( 'email_plaintext' ),
				'newsletter'       => ( $newsletter_ID === NULL ? $this->get( 'enlt_ID' ) : $newsletter_ID ),
				'ecmp_ID'          => $this->ID,
				'autm_ID'          => $automation_ID,
				'template_parts'   => array(
						'header' => 0,
						'footer' => 0
					),
				'default_template_tag' => 1
			);

		$UserCache = & get_UserCache();

		if( $mode == 'test' )
		{ // Send a test newsletter
			global $current_User;

			$newsletter_params['boundary'] = 'b2evo-'.md5( rand() );
			$headers = array( 'Content-Type' => 'multipart/mixed; boundary="'.$newsletter_params['boundary'].'"' );

			if( $test_User = & $UserCache->get_by_ID( $user_ID, false, false ) )
			{ // Send a test email only when test user exists
				$message = mail_template( 'newsletter', 'auto', $newsletter_params, $test_User );
				$result = send_mail( $email_address, NULL, $this->get( 'email_title' ), $message, NULL, NULL, $headers );
			}
			else
			{ // No test user found
				$result = false;
			}
		}
		else
		{	// Send a newsletter to real user:
			global $DB, $mail_log_insert_ID;

			// Force email sending to not activated users if email campaign is configurated to auto sending (e-g to send email on auto subscription on registration):
			$force_on_non_activated = ( $this->get( 'auto_send' ) == 'subscription' );
			$result = send_mail_to_User( $user_ID, $this->get( 'email_title' ), 'newsletter', $newsletter_params, $force_on_non_activated, array(), $email_address );
			if( $result )
			{	// Update last sending data for newsletter per user:
				$DB->query( 'UPDATE T_email__newsletter_subscription
					SET enls_last_sent_manual_ts = '.$DB->quote( date2mysql( $localtimenow ) ).',
					    enls_send_count = enls_send_count + 1
					WHERE enls_user_ID = '.$DB->quote( $user_ID ).'
					  AND enls_enlt_ID = '.$DB->quote( $this->get( 'enlt_ID' ) ) );
				// Add tags to user after successful email sending:
				$user_tag_sendsuccess = trim( $this->get( 'user_tag_sendsuccess' ) );
				if( ! empty( $user_tag_sendsuccess ) )
				{	// Only if at least one tag is defined:
					if( $User = & $UserCache->get_by_ID( $user_ID, false, false ) )
					{
						$User->add_usertags( $user_tag_sendsuccess );
						$User->dbupdate();
					}
				}
			}

			if( empty( $mail_log_insert_ID ) )
			{	// ID of last inserted mail log is defined in function mail_log()
				// If it was not inserted we cannot mark this user as received this email campaign:
				$result = false;
			}

			if( $result )
			{	// Email newsletter was sent for user successfully:
				$this->update_user_send_status( $user_ID, 'sent' );
			}
			elseif( ( $User = & $UserCache->get_by_ID( $user_ID, false, false ) ) &&
			        $User->get_email_status() == 'prmerror' )
			{	// Unable to send email due to permanent error:
				$this->update_user_send_status( $user_ID, 'send_error' );
			}
		}

		if( $update_sent_ts == 'auto' )
		{	// Update auto date of sending:
			$this->set( 'auto_sent_ts', date( 'Y-m-d H:i:s', $localtimenow ) );
			$this->dbupdate();
		}
		elseif( $update_sent_ts == 'manual' )
		{	// Update manual date of sending:
			$this->set( 'sent_ts', date( 'Y-m-d H:i:s', $localtimenow ) );
			$this->dbupdate();
		}

		return $result;
	}


	/**
	 * Send email newsletter for all users of this campaign
	 *
	 * @param boolean|string TRUE to print out messages, 'cron_job' - to log messages for cron job
	 * @param array Force users instead of users which are ready to receive this email campaign
	 * @param string|boolean Update time of last sending: 'auto', 'manual', FALSE - to don't update
	 */
	function send_all_emails( $display_messages = true, $user_IDs = NULL, $update_sent_ts = 'manual' )
	{
		global $DB, $localtimenow, $Settings, $Messages, $mail_log_insert_ID;

		if( $user_IDs === NULL )
		{	// Send emails only for users which still don't receive emails:
			$user_IDs = $this->get_recipients( 'wait' );
		}
		else
		{	// Exclude users which already received this email campaign to avoid double sending even with forcing user IDs:
			$receive_user_IDs = $this->get_recipients( 'receive' );
			$user_IDs = array_diff( $user_IDs, $receive_user_IDs );
		}

		if( empty( $user_IDs ) )
		{	// No users, Exit here:
			return;
		}

		// It it important to randomize order so that it is not always the same users who get the news first and the same users who the get news last:
		shuffle( $user_IDs );

		$DB->begin();

		$UserCache = & get_UserCache();

		// Get chunk size to limit a sending at a time:
		$email_campaign_chunk_size = intval( $Settings->get( 'email_campaign_chunk_size' ) );

		$email_success_count = 0;
		$email_skip_count = 0;
		$email_error_count = 0;
		foreach( $user_IDs as $user_ID )
		{
			if( $email_campaign_chunk_size > 0 && $email_success_count >= $email_campaign_chunk_size )
			{	// Stop the sending because of chunk size:
				break;
			}

			if( ! ( $User = & $UserCache->get_by_ID( $user_ID, false, false ) ) )
			{	// Skip wrong recipient user:
				continue;
			}

			// Send email to user:
			$result = $this->send_email( $user_ID );

			if( $result )
			{	// Email newsletter was sent for user successfully:
				$email_success_count++;
			}
			else
			{	// This email sending was skipped:
				$email_skip_count++;
				if( $User->get_email_status() == 'prmerror' )
				{	// Unable to send email due to permanent error:
					$email_error_count++;
				}
			}

			if( $display_messages === true || $display_messages === 'cron_job' )
			{	// Print the messages:
				if( $result === true )
				{ // Success
					$result_msg = sprintf( T_('Email was sent to user: %s'), $User->get_identity_link() );
					if( $display_messages === 'cron_job' )
					{
						cron_log_action_end( $result_msg );
					}
					else
					{
						echo $result_msg.'<br />';
					}
				}
				else
				{ // Failed, Email was NOT sent
					if( ! check_allow_new_email( 'newsletter_limit', 'last_newsletter', $user_ID ) )
					{ // Newsletter email is limited today for this user
						$error_msg = sprintf( T_('User %s has already received max # of lists today.'), $User->get_identity_link() );
						if( $display_messages === 'cron_job' )
						{
							cron_log_action_end( $error_msg, 'warning' );
						}
						else
						{
							echo '<span class="orange">'.$error_msg.'</span><br />';
						}
					}
					elseif( $User->get_email_status() == 'prmerror' )
					{ // Email has permanent error
						$error_msg = sprintf( T_('Email was not sent to user: %s'), $User->get_identity_link() ).' ('.T_('Reason').': '.T_('Permanent error').')';
						if( $display_messages === 'cron_job' )
						{
							cron_log_action_end( $error_msg, 'error' );
						}
						else
						{
							echo '<span class="red">'.$error_msg.'</span><br />';
						}
					}
					else
					{ // Another error
						$error_msg = sprintf( T_('Email was not sent to user: %s'), $User->get_identity_link() );
						if( $display_messages === 'cron_job' )
						{
							cron_log_action_end( $error_msg, 'error' );
						}
						else
						{
							echo '<span class="red">'.$error_msg.'</span><br />';
						}
					}
				}

				evo_flush();
			}
		}

		if( $update_sent_ts == 'auto' )
		{	// Update auto date of sending:
			$this->set( 'auto_sent_ts', date( 'Y-m-d H:i:s', $localtimenow ) );
			$this->dbupdate();
		}
		elseif( $update_sent_ts == 'manual' )
		{	// Update manual date of sending:
			$this->set( 'sent_ts', date( 'Y-m-d H:i:s', $localtimenow ) );
			$this->dbupdate();
		}

		$DB->commit();

		if( $display_messages === true || $display_messages === 'cron_job' )
		{	// Print the messages:
			$wait_count = count( $this->users['wait'] );
			$skipped_count = count( $this->users['skipped'] ); // Recipients that are marked skipped for this campaign
			if( $wait_count > 0 )
			{	// Some recipients still wait this newsletter:
				$warning_msg = sprintf( T_('Emails have been sent to a chunk of %s recipients. %s recipients were skipped. %s recipients have not been sent to yet.'),
						$email_campaign_chunk_size, $email_skip_count + $skipped_count, $wait_count );
				if( $display_messages === 'cron_job' )
				{
					cron_log_append( "\n".$warning_msg, 'warning' );
				}
				$Messages->add( $warning_msg, 'warning' );
			}
			else
			{	// All recipients received this bewsletter:
				$success_msg = T_('Emails have been sent to all recipients of this campaign.');
				if( $display_messages === 'cron_job' )
				{
					cron_log_append( "\n".$success_msg, 'success' );
				}
				$Messages->add( $success_msg, 'success' );
			}
			if( $display_messages !== 'cron_job' )
			{	// Print out messages right now:
				$Messages->display();
			}
		}
	}


	/**
	 * Update status of sending email campaign for user
	 *
	 * @param integer User Id
	 * @param string Status: 'ready_to_send','ready_to_resend','sent','send_error','skipped'
	 * @param integer|NULL Mail log ID, NULL - to use log ID of last sending email
	 */
	function update_user_send_status( $user_ID, $status, $mail_log_ID = NULL )
	{
		global $DB, $mail_log_insert_ID, $servertimenow;

		if( empty( $this->ID ) )
		{	// Email Campaign must be stored in DB:
			return;
		}

		if( $mail_log_ID === NULL && isset( $mail_log_insert_ID ) )
		{	// Use log ID of last sending email:
			$mail_log_ID = $mail_log_insert_ID;
		}

		// Get all send statuses per users of this email campaign from cache or DB table T_email__campaign_send once:
		$all_user_IDs = $this->get_recipients( 'full_all' );

		if( in_array( $user_ID, $all_user_IDs ) )
		{	// Update user send status for this email campaign:
			$last_sent_ts_field_value = ( $mail_log_ID === NULL ? '' : ', csnd_last_sent_ts = '.$DB->quote( date2mysql( $servertimenow ) ) );
			$r = $DB->query( 'UPDATE T_email__campaign_send
				SET csnd_status = '.$DB->quote( $status ).',
				    csnd_emlog_ID = '.$DB->quote( $mail_log_ID ).'
				    '.$last_sent_ts_field_value.'
				WHERE csnd_camp_ID = '.$DB->quote( $this->ID ).'
				  AND csnd_user_ID = '.$DB->quote( $user_ID ) );
		}
		else
		{	// Insert new record for user send status:
			$last_sent_ts_field = ( $mail_log_ID === NULL ? '' : ', csnd_last_sent_ts' );
			$last_sent_ts_value = ( $mail_log_ID === NULL ? '' : ', '.$DB->quote( date2mysql( $servertimenow ) ) );
			$r = $DB->query( 'INSERT INTO T_email__campaign_send ( csnd_camp_ID, csnd_user_ID, csnd_status, csnd_emlog_ID'.$last_sent_ts_field.' )
				VALUES ( '.$DB->quote( $this->ID ).', '.$DB->quote( $user_ID ).', '.$DB->quote( $status ).', '.$DB->quote( $mail_log_ID ).$last_sent_ts_value.' )' );
		}

		if( $r )
		{	// Update the CACHE array where we store email sending status for users:
			$statuses_keys = array(
					'ready_to_send'   => 'wait',
					'ready_to_resend' => 'wait',
					'sent'            => 'receive',
					'send_error'      => 'error',
					'skipped'         => 'skipped',
				);
			$this->users['all'][] = $user_ID;
			if( isset( $statuses_keys[ $status ] ) )
			{	// Add user ID to filtered array:
				$this->users['filter'][] = $user_ID;
			}
			foreach( $statuses_keys as $email_status => $array_key )
			{
				if( $email_status == $status )
				{	// Add user ID to proper cache array:
					$this->users[ $array_key ][] = $user_ID;
				}
				elseif( ( $unset_user_ID_key = array_search( $user_ID, $this->users[ $array_key ] ) ) !== false )
				{	// Remove user ID from previous status cache array:
					unset( $this->users[ $array_key ][ $unset_user_ID_key ] );
				}
			}
		}
	}


	/**
	 * Get the list of validated renderers for this EmailCampaign. This includes stealth plugins etc.
	 * @return array List of validated renderer codes
	 */
	function get_renderers_validated()
	{
		if( ! isset( $this->renderers_validated ) )
		{
			global $Plugins;
			$this->renderers_validated = $Plugins->validate_renderer_list( $this->get_renderers(), array( 'EmailCampaign' => & $this ) );
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
	 * Get current Cronjob of this email campaign
	 *
	 * @return object Cronjob
	 */
	function & get_Cronjob()
	{
		$CronjobCache = & get_CronjobCache();

		$Cronjob = & $CronjobCache->get_by_ID( $this->get( 'send_ctsk_ID' ), false, false );

		return $Cronjob;
	}


	/**
	 * Create a scheduled job to send newsletters of this email campaign
	 *
	 * @param boolean TRUE if cron job should be created to send next chunk of waiting users, FALSE - to create first cron job
	 */
	function create_cron_job( $next_chunk = false )
	{
		global $Messages, $servertimenow, $current_User;

		if( ! $next_chunk && ( $email_campaign_Cronjob = & $this->get_Cronjob() ) )
		{	// If we create first cron job but this email campaign already has one:
			if( $current_User->check_perm( 'options', 'view' ) )
			{	// If user has an access to view cron jobs:
				global $admin_url;
				$Messages->add( sprintf( T_('A scheduled job was already created for this campaign, <a %s>click here</a> to view it.'),
					'href="'.$admin_url.'?ctrl=crontab&amp;action=view&amp;cjob_ID='.$email_campaign_Cronjob->ID.'" target="_blank"' ), 'error' );
			}
			else
			{	// If user has no access to view cron jobs:
				$Messages->add( T_('A scheduled job was already created for this campaign.'), 'error' );
			}

			return false;
		}

		if( $this->get_recipients_count( 'wait' ) > 0 )
		{	// Create cron job only when at least one user is waiting a newsletter of this email campaing:
			load_class( '/cron/model/_cronjob.class.php', 'Cronjob' );
			$email_campaign_Cronjob = new Cronjob();

			$additional_message = '';

			$start_datetime = $servertimenow;
			if( $next_chunk )
			{	// Send next chunk only after delay:
				global $Settings;
				// We should know if all waiting users are not limited by max newsletters for today:
				$user_IDs = $this->get_recipients( 'wait' );
				$all_waiting_users_limited = ( count( $user_IDs ) > 0 );
				foreach( $user_IDs as $user_ID )
				{
					if( check_allow_new_email( 'newsletter_limit', 'last_newsletter', $user_ID ) )
					{	// Newsletter email is NOT limited today for this user:
						$all_waiting_users_limited = false;
						// Stop searching other users because at least one user can receive newsletter today:
						break;
					}
				}

				if( $all_waiting_users_limited )
				{	// Force a delay between chunks if all waiting users are limited to receive more newsletters for today:
					$start_datetime += $Settings->get( 'email_campaign_cron_limited' );
					// TRANS: %s is a time period like 58 minutes, 1 hour, 12 days, 1 year and etc.
					$additional_message = ' '.sprintf( T_('Delaying next run by %s because all remaining recipients cannot accept additional emails for the current day.'), seconds_to_period( $Settings->get( 'email_campaign_cron_limited' ) ) );
				}
				else
				{	// Use a delay between chunks from general setting:
					$start_datetime += $Settings->get( 'email_campaign_cron_repeat' );
				}
			}
			$email_campaign_Cronjob->set( 'start_datetime', date2mysql( $start_datetime ) );

			// no repeat.

			// key:
			$email_campaign_Cronjob->set( 'key', 'send-email-campaign' );

			// params: specify which post this job is supposed to send notifications for:
			$email_campaign_Cronjob->set( 'params', array(
					'ecmp_ID' => $this->ID,
				) );

			// Save cronjob to DB:
			$r = $email_campaign_Cronjob->dbinsert();

			if( ! $r )
			{	// Error on cron job inserting:
				return false;
			}

			// Memorize the cron job ID which is going to handle this email campaign:
			$this->set( 'send_ctsk_ID', $email_campaign_Cronjob->ID );

			$Messages->add( T_('A scheduled job has been created for this campaign.').$additional_message, 'success' );
		}
		else
		{	// If no waiting users then don't create a cron job and reset ID of previous cron job:
			$this->set( 'send_ctsk_ID', NULL, true );

			$Messages->add( T_('No scheduled job has been created for this campaign because it has no waiting recipients.'), 'warning' );
		}

		// Update the changed email campaing settings:
		$this->dbupdate();

		return true;
	}


	/**
	 * Get title of sending method
	 *
	 * @return string
	 */
	function get_sending_title()
	{
		$titles = array(
				'no'           => T_('Manual'),
				'subscription' => T_('At subscription'),
			);

		if( isset( $titles[ $this->get( 'auto_send' ) ] ) )
		{
			return $titles[ $this->get( 'auto_send' ) ];
		}

		// Unknown sending method
		return $this->get( 'auto_send' );
	}
}

?>