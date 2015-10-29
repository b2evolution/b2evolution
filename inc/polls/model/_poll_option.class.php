<?php
/**
 * This file implements the PollOption class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );


/**
 * PollOption Class
 *
 * @package evocore
 */
class PollOption extends DataObject
{
	var $pqst_ID;
	var $option_text;
	var $order;

	/**
	 * Constructor
	 *
	 * @param object table Database row
	 */
	function PollOption( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_polls__option', 'popt_', 'popt_ID' );

		if( $db_row != NULL )
		{	// Get poll option data from DB:
			$this->ID = $db_row->popt_ID;
			$this->pqst_ID = $db_row->popt_pqst_ID;
			$this->option_text = $db_row->popt_option_text;
			$this->order = $db_row->popt_order;
		}
		else
		{	// Set default poll option data for new poll:
			
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
				array( 'table' => 'T_polls__answer', 'fk' => 'pans_popt_ID', 'msg' => T_('%d poll answers') ),
			);
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request( $poll_question_ID = NULL )
	{
		if( $poll_question_ID !== NULL )
		{	// Poll question ID:
			$this->set( 'pqst_ID', $poll_question_ID );
		}

		// Option text:
		param( 'popt_option_text', 'string', false );
		param_check_not_empty( 'popt_option_text', T_('Please enter the text for the poll\'s option.') );
		$this->set_from_Request( 'option_text' );

		// Order:
		param( 'popt_order', 'integer', false );
		param_check_not_empty( 'popt_order', T_('Please enter the order for the poll\'s option.') );
		$this->set_from_Request( 'order' );

		return ! param_errors_detected();
	}


	/**
	 * Get name of the question. It is a string as first 200 chars of the option text:
	 *
	 * @return string
	 */
	function get_name()
	{
		return strmaxlen( $this->get( 'option_text' ), 200 );
	}


	/**
	 * Vote on this poll option by current User
	 *
	 * @return boolean TRUE on successful voting
	 */
	function vote()
	{
		global $current_User, $DB;

		if( !is_logged_in() )
		{	// User must be logged in for voting:
			return false;
		}

		// Set new vote or update the previous vote for current user:
		$result = $DB->query( 'REPLACE INTO T_polls__answer ( pans_pqst_ID, pans_user_ID, pans_popt_ID )
				VALUES ( '.$DB->quote( $this->pqst_ID ).', '.$DB->quote( $current_User->ID ).', '.$DB->quote( $this->ID ).' )' );

		return $result ? true : false;
	}
}

?>