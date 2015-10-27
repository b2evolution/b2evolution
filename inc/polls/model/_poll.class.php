<?php
/**
 * This file implements the Poll class.
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
 * Poll Class
 *
 * @package evocore
 */
class Poll extends DataObject
{
	var $owner_user_ID;
	var $question_text;

	/**
	 * Constructor
	 *
	 * @param object table Database row
	 */
	function Poll( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_polls__question', 'pqst_', 'pqst_ID' );

		if( $db_row != NULL )
		{	// Get poll data from DB:
			$this->ID = $db_row->pqst_ID;
			$this->owner_user_ID = $db_row->pqst_owner_user_ID;
			$this->question_text = $db_row->pqst_question_text;
		}
		else
		{	// Set default poll data for new poll:
			if( is_logged_in() )
			{
				global $current_User;
				$this->set( 'owner_user_ID', $current_User->ID );
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

		return ! param_errors_detected();
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
}

?>