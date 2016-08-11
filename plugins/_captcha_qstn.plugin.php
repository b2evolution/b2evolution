<?php
/**
 * This file implements the Captcha Questions plugin.
 *
 * The core functionality was provided by Francois PLANQUE.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2012-2016 by Francois Planque - {@link http://fplanque.com/}.
 *
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * }}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * The Captcha Questions Plugin.
 *
 * It displays an captcha question through {@link CaptchaValidated()} and validates
 * it in {@link CaptchaValidated()}.
 */
class captcha_qstn_plugin extends Plugin
{
	var $version = '6.7.5';
	var $group = 'antispam';
	var $code = 'captcha_qstn';


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->name = $this->T_('Captcha questions');
		$this->short_desc = $this->T_('Use questions to tell humans and robots apart.');
	}


	/**
	 * Define the GLOBAL settings of the plugin here. These can then be edited in the backoffice in System > Plugins.
	 *
	 * @param array Associative array of parameters (since v1.9).
	 *    'for_editing': true, if the settings get queried for editing;
	 *                   false, if they get queried for instantiating {@link Plugin::$Settings}.
	 * @return array see {@link Plugin::GetDefaultSettings()}.
	 * The array to be returned should define the names of the settings as keys (max length is 30 chars)
	 * and assign an array with the following keys to them (only 'label' is required):
	 */
	function GetDefaultSettings( & $params )
	{
		global $Settings;

		return array(
				'use_for_anonymous_comment' => array(
					'label' => $this->T_('Use for anonymous comment forms'),
					'defaultvalue' => 1,
					'note' => $this->T_('Should this plugin be used for anonymous users on comment forms?'),
					'type' => 'checkbox',
				),
				'use_for_registration' => array(
					'label' => $this->T_('Use for new registration forms'),
					'defaultvalue' => 1,
					'note' => $this->T_('Should this plugin be used on registration forms?'),
					'type' => 'checkbox',
				),
				'use_for_anonymous_message' => array(
					'label' => $this->T_('Use for anonymous messaging forms'),
					'defaultvalue' => 1,
					'note' => $this->T_('Should this plugin be used for anonymous users on messaging forms?'),
					'type' => 'checkbox',
				),
				'questions' => array(
					'label' => $this->T_('Questions'),
					'defaultvalue' => '',
					'note' => $this->T_('Type each question in one line with following format:<br />Question text? answer1|answer2|...|answerN'),
					'cols' => 80,
					'rows' => 10,
					'type' => 'html_textarea',
				),
			);
	}


	/**
	 * We want a table to store our Captcha data (private key, timestamp, ..).
	 *
	 * @return array
	 */
	function GetDbLayout()
	{
		return array(
				'CREATE TABLE '.$this->get_sql_table('questions').' (
					cptq_ID       INT UNSIGNED NOT NULL AUTO_INCREMENT,
					cptq_question VARCHAR( 255 ) NOT NULL,
					cptq_answers  VARCHAR( 255 ) NOT NULL,
					PRIMARY KEY( cptq_ID )
				) ENGINE = innodb DEFAULT CHARSET = utf8',

				// Assign a random question ID for each IP address
				'CREATE TABLE '.$this->get_sql_table('ip_question').' (
					cptip_IP      INT UNSIGNED NOT NULL,
					cptip_cptq_ID INT UNSIGNED NOT NULL,
					KEY( cptip_IP, cptip_cptq_ID )
				) ENGINE = innodb',
			);
	}


	/**
	 * Check the available questions in DB table.
	 */
	function BeforeEnable()
	{
		// Convert value of setting "questions" into DB table records
		$this->PluginSettingsUpdateAction();

		if( $error = $this->validate_questions() )
		{
			return $error;
		}

		return true;
	}


	/**
	 * Update the questions
	 */
	function PluginSettingsUpdateAction()
	{
		global $DB, $Messages;

		// Store in this array all questions IDs which are inserted or updated on currect action
		$questions_IDs = array( -1 );
		// Store in this array only questions with correct format
		$questions_formated = array();

		$questions = $this->Settings->get( 'questions' );
		if( empty( $questions ) )
		{ // No questions
			// Delete all questions from DB:
			$DB->query( 'DELETE FROM '.$this->get_sql_table( 'questions' ) );
			return true;
		}

		$questions = explode( "\n", str_replace( "\r\n", "\n", $questions ) );

		$insert_data = array();
		$update_data = array();
		foreach( $questions as $question )
		{
			if( preg_match( '/(.+\?)(.+)/i', $question, $match ) )
			{ // Check a question for correct format
				$question = trim( $match[1] );
				$answers = trim( $match[2] );
				if( !empty( $question ) && !empty( $answers ) )
				{ // Save this question in DB
					$SQL = new SQL();
					$SQL->SELECT( 'cptq_ID' );
					$SQL->FROM( $this->get_sql_table( 'questions' ) );
					$SQL->WHERE( 'cptq_question = '.$DB->quote( $question ) );

					if( $question_ID = $DB->get_var( $SQL->get() ) )
					{ // This question already exists, we should only update the answers
						$update_data[ $question_ID ] = $answers;
					}
					else
					{ // New question
						$insert_data[] = '( '.$DB->quote( $question ).', '.$DB->quote( $answers ).' )';
					}

					$questions_formated[] = $question.' '.$answers;
				}
			}
			elseif( ! empty( $question ) )
			{ // Incorrect format of question
				$Messages->add( sprintf( T_('Invalid question format in the line %s.'), '"<b>'.$question.'</b>"' ), 'error' );
			}
		}

		if( param_errors_detected() )
		{ // Don't save the changes on errors
			return false;
		}
		else
		{ // Save the changes on success
			if( count( $insert_data ) )
			{ // Insert new questions in DB:
				foreach( $insert_data as $qstn_data )
				{
					$DB->query( 'INSERT INTO '.$this->get_sql_table( 'questions' ).'
						( cptq_question, cptq_answers ) VALUES '.$qstn_data );
					$questions_IDs[] = $DB->insert_id;
				}
			}

			if( count( $update_data ) )
			{ // Update the questions in DB:
				foreach( $update_data as $qstn_ID => $qstn_answers )
				{
					$DB->query( 'UPDATE '.$this->get_sql_table( 'questions' ).'
						  SET cptq_answers = '.$DB->quote( $qstn_answers ).'
						WHERE cptq_ID = '.$qstn_ID );
					$questions_IDs[] = $qstn_ID;
				}
			}

			// Delete the old questions from DB:
			$DB->query( 'DELETE FROM '.$this->get_sql_table( 'questions' ).'
				WHERE cptq_ID NOT IN ( '.implode( ', ', $questions_IDs ).' )' );

			// Resave the questions in order to delere all whitespaces and questions with incorrect format
			$this->Settings->set( 'questions', implode( "\r\n", $questions_formated ) );

			return true;
		}
	}


	/**
	 * Validate the given answer against our stored one.
	 *
	 * This event is provided for other plugins and gets used internally
	 * for other events we're hooking into.
	 *
	 * @param array Associative array of parameters.
	 * @return boolean|NULL
	 */
	function CaptchaValidated( & $params )
	{
		global $DB, $localtimenow, $Session;

		if( ! isset( $params['form_type'] ) || ! $this->does_apply( $params['form_type'] ) )
		{	// We should not apply captcha to the requested form:
			return;
		}

		$posted_answer = utf8_strtolower( param( 'captcha_qstn_'.$this->ID.'_answer', 'string', '' ) );

		if( empty( $posted_answer ) )
		{
			$this->debug_log( 'captcha_qstn_'.$this->ID.'_answer' );
			$params['validate_error'] = $this->T_('Please enter the captcha answer.');
			if( $params['form_type'] == 'comment' && ( $comment_Item = & $params['Comment']->get_Item() ) )
			{
				syslog_insert( 'Comment captcha answer is not entered', 'warning', 'item', $comment_Item->ID, 'plugin', $this->ID );
			}
			return false;
		}

		$question = $this->CaptchaQuestion();

		$posted_answer_is_correct = false;

		$answers = explode( '|', utf8_strtolower( $question->cptq_answers ) );
		foreach( $answers as $answer )
		{
			if( $posted_answer == $answer )
			{	// Correct answer is found in DB
				$posted_answer_is_correct = true;
				break;
			}
		}

		if( !$posted_answer_is_correct )
		{
			$this->debug_log( 'Posted ('.$posted_answer.') and saved ('.$question->cptq_answers.') answer do not match!' );
			$params['validate_error'] = $this->T_('The entered answer is incorrect.');
			if( $params['form_type'] == 'comment' && ( $comment_Item = & $params['Comment']->get_Item() ) )
			{
				syslog_insert( 'Comment captcha answer is incorrect', 'warning', 'item', $comment_Item->ID, 'plugin', $this->ID );
			}
			return false;
		}

		// If answer is correct:
		//   We should clean the question ID that was assigned for current session and IP address
		//   It gives to assign new question on the next captcha event
		$this->CaptchaQuestionCleanup();

		return true;
	}


	/**
	 * Get question for current user
	 *
	 * @return object Question row from DB table "questions"
	 */
	function CaptchaQuestion()
	{
		global $DB, $Session;

		$question = NULL;

		$IP = ip2int( $_SERVER['REMOTE_ADDR'] );

		// Get question ID from Session
		$this->question_ID = $Session->get( 'captcha_qstn_'.$this->ID.'_ID' );

		if( empty( $this->question_ID ) )
		{	// Get question from DB by current IP address
			$SQL = new SQL();
			$SQL->SELECT( 'cq.*' );
			$SQL->FROM( $this->get_sql_table( 'ip_question' ) );
			$SQL->FROM_add( 'INNER JOIN '.$this->get_sql_table( 'questions' ).' AS cq ON cptip_cptq_ID = cptq_ID' );
			$SQL->WHERE( 'cptip_IP = '.$DB->quote( $IP ) );

			if( $question = $DB->get_row( $SQL->get() ) )
			{
				$this->question_ID = $question->cptq_ID;
			}
		}

		if( empty( $this->question_ID ) )
		{	// Assign new random question for current IP address
			$question = $this->CaptchaQuestionNew();
		}

		if( empty( $question ) && !empty( $this->question_ID ) )
		{	// Get question data
			$SQL = new SQL();
			$SQL->SELECT( '*' );
			$SQL->FROM( $this->get_sql_table( 'questions' ) );
			$SQL->WHERE( 'cptq_ID = '.$DB->quote( $this->question_ID ) );
			$question = $DB->get_row( $SQL->get() );

			if( empty( $question ) )
			{	// Assign random question if previous question doesn't exist in DB
				// This case may happens when admin changed the questions but user has the old question ID in the session or in DB table "ip_question"
				$question = $this->CaptchaQuestionNew();
			}
		}

		return $question;
	}

	/**
	 * Assign new random question for current IP address
	 *
	 * @return object Question row from DB table "questions"
	 */
	function CaptchaQuestionNew()
	{
		global $DB, $Session;

		$IP = ip2int( $_SERVER['REMOTE_ADDR'] );

		// Get new random question from DB
		$SQL = new SQL();
		$SQL->SELECT( '*' );
		$SQL->FROM( $this->get_sql_table( 'questions' ) );
		$SQL->ORDER_BY( 'RAND()' );
		$SQL->LIMIT( 1 );
		$question = $DB->get_row( $SQL->get() );

		// Insert a record for current IP address with assigned question ID
		$DB->query( 'INSERT INTO '.$this->get_sql_table( 'ip_question' ).'
			( cptip_IP, cptip_cptq_ID ) VALUES
			( '.$DB->quote( $IP ).', '.$DB->quote( $question->cptq_ID ).' )' );

		// Save the assigned question ID in the session
		$Session->set( 'captcha_qstn_'.$this->ID.'_ID', $question->cptq_ID );
		$Session->dbsave();

		$this->question_ID = $question->cptq_ID;

		return $question;
	}


	/**
	 * Cleanup used captcha data
	 */
	function CaptchaQuestionCleanup()
	{
		global $DB, $Session;

		$IP = ip2int( $_SERVER['REMOTE_ADDR'] );

		// Remove question ID from session
		$Session->delete( 'captcha_qstn_'.$this->ID.'_ID' );

		// Remove question ID from DB table for current IP address
		$DB->query( 'DELETE FROM '.$this->get_sql_table( 'ip_question' ).'
			WHERE cptip_IP = '.$DB->quote( $IP ) );
	}


	/**
	 * When a comment form gets displayed, we inject our captcha and an input field to
	 * enter the answer.
	 *
	 * The question ID is saved into the user's Session and in the DB table "ip_question".
	 *
	 * @param array Associative array of parameters
	 *   - 'Form': the form where payload should get added (by reference, OPTIONALLY!)
	 *   - 'form_use_fieldset':
	 *   - 'key': A key that is associated to the caller of the event (string, OPTIONALLY!)
	 *   - 'form_type': Form type ( comment|register|message )
	 * @return boolean|NULL true, if displayed; false, if error; NULL if it does not apply
	 */
	function CaptchaPayload( & $params )
	{
		global $DB, $Session;

		if( ! isset( $params['form_type'] ) || ! $this->does_apply( $params['form_type'] ) )
		{	// We should not apply captcha to the requested form:
			return;
		}

		$question = $this->CaptchaQuestion();

		if( empty( $question ) )
		{	// No the defined questions
			return;
		}

		$this->debug_log( 'Question ID is: ('.$this->question_ID.')' );

		if( ! isset( $params['Form'] ) )
		{	// there's no Form where we add to, but we create our own form:
			$Form = new Form( regenerate_url() );
			$Form->begin_form();
		}
		else
		{
			$Form = & $params['Form'];
			if( ! isset( $params['form_use_fieldset'] ) || $params['form_use_fieldset'] )
			{
				$Form->begin_fieldset();
			}
		}

		$Form->info( $this->T_('Captcha question'), $question->cptq_question );
		$Form->text_input( 'captcha_qstn_'.$this->ID.'_answer', param( 'captcha_qstn_'.$this->ID.'_answer', 'string', '' ),
				10, $this->T_('Captcha answer'), ( empty( $params['use_placeholders'] ) ? $this->T_('Please answer the question above').'.' : '' ),
				array(
						'placeholder' => empty( $params['use_placeholders'] ) ? '' : T_('Please answer the question above'),
					)
			);

		if( ! isset($params['Form']) )
		{	// there's no Form where we add to, but our own form:
			$Form->end_form( array( array( 'submit', 'submit', $this->T_('Validate me'), 'ActionButton' ) ) );
		}
		else
		{
			if( ! isset($params['form_use_fieldset']) || $params['form_use_fieldset'] )
			{
				$Form->end_fieldset();
			}
		}

		return true;
	}


	/**
	 * We display our captcha with comment forms.
	 */
	function DisplayCommentFormFieldset( & $params )
	{
		$params['form_type'] = 'comment';
		$this->CaptchaPayload( $params );
	}


	/**
	 * Validate the answer against our stored one.
	 *
	 * In case of error we add a message of category 'error' which prevents the comment from
	 * being posted.
	 *
	 * @param array Associative array of parameters.
	 */
	function BeforeCommentFormInsert( & $params )
	{
		$params['form_type'] = 'comment';
		$this->validate_form_by_captcha( $params );
	}


	/**
	 * Validate the answer against our stored one.
	 *
	 * In case of error we add a message of category 'error' which prevents the comment from
	 * being posted.
	 *
	 * @param array Associative array of parameters.
	 */
	function validate_form_by_captcha( & $params )
	{
		if( ! empty( $params['is_preview'] ) )
		{	// Don't validate on preview action:
			return;
		}

		if( empty( $params['form_type'] ) )
		{	// Form type must be defined:
			return;
		}

		if( $this->CaptchaValidated( $params ) === false )
		{	// Some error on captcha validation:
			$validate_error = $params['validate_error'];
			param_error( 'captcha_qstn_'.$this->ID.'_answer', $validate_error );
		}
	}


	/**
	 * We display our captcha with the register form.
	 */
	function DisplayRegisterFormFieldset( & $params )
	{
		$params['form_type'] = 'register';
		$this->CaptchaPayload( $params );
	}


	/**
	 * Validate the given private key against our stored one.
	 *
	 * In case of error we add a message of category 'error' which prevents the
	 * user from being registered.
	 */
	function RegisterFormSent( & $params )
	{
		$params['form_type'] = 'register';
		$this->validate_form_by_captcha( $params );
	}


	/**
	 * We display our captcha with the message form.
	 */
	function DisplayMessageFormFieldset( & $params )
	{
		$params['form_type'] = 'message';
		$this->CaptchaPayload( $params );
	}


	/**
	 * Validate the given private key against our stored one.
	 *
	 * In case of error we add a message of category 'error' which prevents the
	 * user from being registered.
	 */
	function MessageFormSent( & $params )
	{
		$params['form_type'] = 'message';
		$this->validate_form_by_captcha( $params );
	}


	/* PRIVATE methods */

	/**
	 * Checks if we should captcha the current request, according to the settings made.
	 *
	 * @param string Form type ( comment|register|message )
	 * @return boolean
	 */
	function does_apply( $form_type )
	{
		switch( $form_type )
		{
			case 'comment':
				if( !is_logged_in() )
				{
					return $this->Settings->get( 'use_for_anonymous_comment' );
				}
				break;

			case 'register':
				return $this->Settings->get( 'use_for_registration' );

			case 'message':
				if( !is_logged_in() )
				{
					return $this->Settings->get( 'use_for_anonymous_message' );
				}
				break;
		}

		return false;
	}


	/**
	 * Check if questions exist in DB.
	 *
	 * @access private
	 */
	function validate_questions()
	{
		global $DB;

		$SQL = new SQL();
		$SQL->SELECT( 'cptq_ID' );
		$SQL->FROM( $this->get_sql_table( 'questions' ) );

		if( ! $DB->get_var( $SQL->get() ) )
		{
			return $this->T_( 'Not Enabled: You should create at least one question!' );
		}
	}

}

?>