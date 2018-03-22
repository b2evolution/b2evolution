<?php
/**
 * This file implements the Poll class.
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
 * Poll Class
 *
 * @package evocore
 */
class Poll extends DataObject
{
	var $owner_user_ID;
	var $question_text;
	var $max_answers;

	/**
	 * @var array PollOption objects
	 */
	var $poll_options;

	/**
	 * @var array with keys:
	 *       - 'votes' - a number of votes of this Poll from all voters,
	 *       - 'voters' - a number of users which voted on at least one option of this poll.
	 */
	var $vote_nums = NULL;

	/**
	 * Constructor
	 *
	 * @param object table Database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_polls__question', 'pqst_', 'pqst_ID' );

		if( $db_row != NULL )
		{	// Get poll data from DB:
			$this->ID = $db_row->pqst_ID;
			$this->owner_user_ID = $db_row->pqst_owner_user_ID;
			$this->question_text = $db_row->pqst_question_text;
			$this->max_answers = $db_row->pqst_max_answers;
		}
		else
		{	// Set default poll data for new poll:
			if( is_logged_in() )
			{
				global $current_User;
				$this->set( 'owner_user_ID', $current_User->ID );
				$this->set( 'max_answers', 1 );
			}
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
				array( 'table' => 'T_polls__option', 'fk' => 'popt_pqst_ID', 'msg' => T_('%d poll options') ),
				array( 'table' => 'T_polls__answer', 'fk' => 'pans_pqst_ID', 'msg' => T_('%d poll answers') ),
			);
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		global $current_User;

		// Owner:
		if( $current_User->check_perm( 'polls', 'edit' ) )
		{	// Update the owner if current user has a permission to edit all polls:
			$pqst_owner_login = param( 'pqst_owner_login', 'string', NULL );
			param_check_not_empty( 'pqst_owner_login', T_('Please enter the owner\'s login.') );
			if( ! empty( $pqst_owner_login ) )
			{	// If the login is entered:
				$UserCache = & get_UserCache();
				$owner_User = & $UserCache->get_by_login( $pqst_owner_login );
				if( empty( $owner_User ) )
				{	// Wrong entered login:
					param_error( 'pqst_owner_login', sprintf( T_('User &laquo;%s&raquo; does not exist!'), $pqst_owner_login ) );
				}
				else
				{	// Set new login:
					$this->set( 'owner_user_ID', $owner_User->ID );
					$this->owner_User = & $owner_User;
				}
			}
		}
		elseif( empty( $this->ID ) )
		{	// Set onwer user ID on creating new poll:
			$this->set( 'owner_user_ID', $current_User->ID );
			$this->owner_User = & $current_User;
		}

		// Question:
		param( 'pqst_question_text', 'string', false );
		param_check_not_empty( 'pqst_question_text', T_('Please enter the text for the poll\'s question.') );
		$this->set_from_Request( 'question_text' );

		// Maximum number of answers:
		$pqst_max_answers = param( 'pqst_max_answers', 'integer', 1 );
		if( empty( $this->ID ) )
		{
			$options = array();
			$answer_options = param( 'answer_options', 'array:string', array() );
			foreach( $answer_options as $answer_option_text )
			{
				if( empty( $answer_option_text ) )
				{	// Skip empty option:
					continue;
				}

				$options[] = $answer_option_text;
			}
		}
		else
		{
			$options = $this->get_poll_options();
		}

		if( $pqst_max_answers > count( $options ) )
		{
			param_error( 'pqst_max_answers', T_('Maximum allowed answers must not exceed total number of answer options.') );
		}
		else
		{
			$this->set_from_Request( 'max_answers' );
		}

		return ! param_errors_detected();
	}


	/**
	 * Insert object into DB based on previously recorded changes.
	 *
	 * @return boolean true on success
	 */
	function dbinsert()
	{
		global $DB;

		$DB->begin( 'SERIALIZABLE' );

		if( parent::dbinsert() )
		{ // The poll was inserted successful:

			// Insert the entered answer options:
			$answer_options = param( 'answer_options', 'array:string', array() );
			$answer_option_order = 1;
			foreach( $answer_options as $answer_option_text )
			{
				if( empty( $answer_option_text ) )
				{	// Skip empty option:
					continue;
				}

				$new_PollOption = new PollOption();
				$new_PollOption->set( 'pqst_ID', $this->ID );
				$new_PollOption->set( 'option_text', $answer_option_text );
				$new_PollOption->set( 'order', $answer_option_order );
				if( ! $new_PollOption->dbinsert() )
				{	// Rollback if the poll option could not be inserted:
					$DB->rollback();
					return false;
				}

				$answer_option_order++;
			}

			$DB->commit();
			return true;
		}

		// Could not insert the poll object:
		$DB->rollback();
		return false;
	}


	/**
	 * Get name of the question. It is a string as first 200 chars of the question text:
	 *
	 * @return string
	 */
	function get_name()
	{
		return strmaxlen( $this->get( 'question_text' ), 200 );
	}


	/**
	 * Get user object of this poll owner
	 *
	 * @return object User
	 */
	function & get_owner_User()
	{
		if( ! isset( $this->owner_User ) )
		{	// Get the owner User only first time:
			$UserCache = & get_UserCache();
			$this->owner_User = & $UserCache->get_by_ID( $this->owner_user_ID );
		}

		return $this->owner_User;
	}


	/**
	 * Get numbers of votes and voters for this Poll
	 *
	 * @param string|NULL NULL - to return an array of all possible numbers,
	 *                    'votes' - to return a number of votes of this Poll from all voters,
	 *                    'voters' - to return a number of users which voted on at least one option of this poll.
	 * @return array|integer Depends on param $type
	 */
	function get_vote_nums( $type = NULL )
	{
		global $DB;

		if( $this->vote_nums === NULL )
		{	// Get from DB once and store in cache array:
			$nums_SQL = new SQL( 'Get a count of votes and voters for the edited poll #'.$this->ID );
			$nums_SQL->SELECT( 'COUNT( pans_pqst_ID ) AS votes, COUNT( DISTINCT pans_user_ID ) AS voters' );
			$nums_SQL->FROM( 'T_polls__answer' );
			$nums_SQL->WHERE( 'pans_pqst_ID = '.$this->ID );
			$this->vote_nums = $DB->get_row( $nums_SQL, ARRAY_A );
		}

		if( $type === NULL )
		{	// Get all numbers:
			return $this->vote_nums;
		}
		else
		{	// Get a specific number:
			if( ! isset( $this->vote_nums[ $type ] ) )
			{
				debug_die( 'Wrong param type "'.$type.'" for Poll::get_vote_nums()' );
			}
			return $this->vote_nums[ $type ];
		}
	}


	/**
	 * Get all options of this poll
	 *
	 * @return array PollOption objects
	 */
	function get_poll_options()
	{
		if( empty( $this->ID ) )
		{	// New creating poll doesn't have the options, Return empty array:
			return array();
		}

		if( $this->poll_options === NULL )
		{	// Get options from DB only first time, and store them in cache array:
			global $DB;

			// Get numbers of votes and voters for the edited poll:
			$poll_voters_num = $this->get_vote_nums( 'voters' );
			if( $poll_voters_num == 0 )
			{	// To don't devide by zero
				$poll_voters_num = 1;
			}

			$SQL = new SQL( 'Get all options of this poll' );
			$SQL->SELECT( 'popt_ID AS ID, popt_pqst_ID AS pqst_ID, popt_option_text AS option_text,' );
			//$SQL->SELECT_add( 'COUNT( * ) AS answers_count,' );
			$SQL->SELECT_add( 'ROUND( COUNT( pans_pqst_ID ) / '.$poll_voters_num.' * 100 ) AS percent' );
			$SQL->FROM( 'T_polls__option' );
			$SQL->FROM_add( 'LEFT JOIN T_polls__answer ON pans_popt_ID = popt_ID' );
			$SQL->WHERE( 'popt_pqst_ID = '.$this->ID );
			$SQL->GROUP_BY( 'popt_ID' );
			$SQL->ORDER_BY( 'popt_order' );

			$this->poll_options = $DB->get_results( $SQL );
		}

		return $this->poll_options;
	}


	/**
	 * Get max percent of poll options
	 *
	 * @return integer Max percent
	 */
	function get_max_poll_options_percent()
	{
		// Get all poll options:
		$poll_options = $this->get_poll_options();

		$max_percent = 0;

		foreach( $poll_options as $poll_option )
		{	// Find max percent in all poll options:
			if( $poll_option->percent > $max_percent )
			{
				$max_percent = $poll_option->percent;
			}
		}

		return $max_percent;
	}


	/**
	 * Get a vote of the current user
	 *
	 * @return integer|boolean Poll option ID OR FALSE if current user didn't vote on this poll yet
	 */
	function get_user_vote()
	{
		if( empty( $this->ID ) || ! is_logged_in() )
		{	// User must be logged in and poll question must exists in DB
			return false;
		}

		global $DB, $current_User;

		// Get answer of current user for this poll:
		$poll_option_SQL = new SQL( 'Get answer of current user on the poll question' );
		$poll_option_SQL->SELECT( 'pans_popt_ID' );
		$poll_option_SQL->FROM( 'T_polls__answer' );
		$poll_option_SQL->WHERE( 'pans_pqst_ID = '.$this->ID );
		$poll_option_SQL->WHERE_and( 'pans_user_ID = '.$current_User->ID );
		$poll_option_IDs = $DB->get_col( $poll_option_SQL );

		return empty( $poll_option_IDs ) ? false : $poll_option_IDs;
	}


	/**
	 * Clears all votes of the current user
	 */
	function clear_user_votes()
	{
		if( empty( $this->ID ) || ! is_logged_in() )
		{ // User must be logged in and poll question must exists in DB
			return false;
		}

		global $DB, $current_User;

		$DB->query( 'DELETE FROM T_polls__answer WHERE pans_user_ID = '.$current_User->ID.' AND pans_pqst_ID = '.$this->ID );

		return true;
	}
}

?>