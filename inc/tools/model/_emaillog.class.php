<?php
/**
 * This file implements the email log class.
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
 * Email Address Class
 *
 * @package evocore
 */
class EmailLog extends DataObject
{
	var $key;

	var $timestamp;

	var $user_ID;

	var $to;

	var $result;

	var $subject;

	var $headers;

	var $message;

	var $last_open_ts;

	var $last_click_ts;

	var $camp_ID;

	var $autm_ID;

	/**
	 * Constructor
	 *
	 * @param object table Database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_email__log', 'emlog_', 'emlog_ID' );

		if( $db_row != NULL )
		{
			$this->ID = $db_row->emlog_ID;
			$this->key = $db_row->emlog_key;
			$this->timestamp = $db_row->emlog_timestamp;
			$this->user_ID = $db_row->emlog_user_ID;
			$this->to = $db_row->emlog_to;
			$this->result = $db_row->emlog_result; // Result type: 'ok', 'error', 'blocked', 'simulated', 'ready_to_send'
			$this->subject = $db_row->emlog_subject;
			$this->headers = $db_row->emlog_headers;
			$this->message = $db_row->emlog_message;
			$this->last_open_ts = $db_row->emlog_last_open_ts;
			$this->last_click_ts = $db_row->emlog_last_click_ts;
			$this->camp_ID = $db_row->emlog_camp_ID;
			$this->autm_ID = $db_row->emlog_autm_ID;
		}
	}
}

?>