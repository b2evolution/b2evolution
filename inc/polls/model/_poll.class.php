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
		{
			$this->ID = $db_row->pqst_ID;
			$this->owner_user_ID = $db_row->pqst_owner_user_ID;
			$this->question_text = $db_row->pqst_question_text;
		}
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		if( empty( $this->ID ) )
		{	// Set onwer user ID on creating new poll:
			global $current_User;
			$this->set( 'owner_user_ID', $current_User->ID );
		}

		// Question:
		$question_text = param( 'question_text', 'string', true );
		$this->set( 'question_text', $question_text );

		return ! param_errors_detected();
	}
}

?>