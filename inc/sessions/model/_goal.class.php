<?php
/**
 * This file implements the Goal class.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * Goal Class
 *
 * @package evocore
 */
class Goal extends DataObject
{
	var $gcat_ID = 0;
	var $name = '';
	var $key = '';
	var $redir_url = '';
	var $temp_redir_url = '';
	var $temp_start_ts = NULL;
	var $temp_end_ts = NULL;
	var $notes = '';

	/**
	 * @var double
	 */
	var $default_value = '';


	/**
	 * Constructor
	 *
	 * @param object Database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_track__goal', 'goal_', 'goal_ID' );

		if( $db_row )
		{
			$this->ID             = $db_row->goal_ID;
			$this->gcat_ID        = $db_row->goal_gcat_ID;
			$this->name           = $db_row->goal_name;
			$this->key            = $db_row->goal_key;
			$this->redir_url      = $db_row->goal_redir_url;
			$this->temp_redir_url = $db_row->goal_temp_redir_url;
			$this->temp_start_ts  = strtotime( $db_row->goal_temp_start_ts );
			$this->temp_end_ts    = strtotime( $db_row->goal_temp_end_ts );
			$this->default_value  = $db_row->goal_default_value;
			$this->notes          = $db_row->goal_notes;
		}
	}


	/**
	 * Get delete restriction settings
	 *
	 * @return array
	 */
	static function get_delete_cascades()
	{
		return array(
				array( 'table'=>'T_track__goalhit', 'fk'=>'ghit_goal_ID', 'msg'=>T_('%d related goal hits') ),
			);
	}


	/**
	 * Generate help title text for action
	 *
	 * @param string action code: edit, delete, etc.
	 * @return string translated help string
	 */
	function get_action_title( $action )
	{
		switch( $action )
		{
			case 'edit': return T_('Edit this goal...');
			case 'copy': return T_('Duplicate this goal...');
			case 'delete': return T_('Delete this goal!');
			default:
				return '';
		}
	}


	/**
	 * Check permission on a persona
	 *
	 * @todo fp> break up central User::check_perm() so that add-on modules do not need to add code into User class.
	 *
	 * @return boolean true if granted
	 */
	function check_perm( $action= 'view', $assert = true )
	{
		/**
		* @var User
		*/
		global $current_User;

		return $current_User->check_perm( 'stats', $action, $assert );
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// Category
		param( 'goal_gcat_ID', 'integer', true );
		param_check_not_empty( 'goal_gcat_ID', T_('Please select a category.') );
		$this->set_from_Request( 'gcat_ID' );

		// Name
		$this->set_string_from_param( 'name', true );

		// Key
		$this->set_string_from_param( 'key', true );

		// Temporary Redirection URL:
		param( 'goal_temp_redir_url', 'url' );
		$this->set_from_Request( 'temp_redir_url' );

		// Normal Redirection URL:
		param( 'goal_redir_url', 'url' );
		if( $this->get( 'temp_redir_url' ) != '' )
		{ // Normal Redirection URL is required when Temporary Redirection URL is not empty
			param_check_not_empty( 'goal_redir_url', T_('Please enter Normal Redirection URL.') );
		}
		$this->set_from_Request( 'redir_url' );

		if( $this->get( 'temp_redir_url' ) != '' && $this->get( 'temp_redir_url' ) == $this->get( 'redir_url' ) )
		{ // Compare normal and temp urls
			param_error( 'goal_temp_redir_url', T_( 'Temporary Redirection URL should not be equal to Normal Redirection URL' ) );
			param_error( 'goal_redir_url', NULL, '' );
		}

		// Temporary Start
		$temp_start_date = param_date( 'goal_temp_start_date', sprintf( T_('Please enter a valid date using the following format: %s'), '<code>'.locale_input_datefmt().'</code>' ), false );
		if( ! empty( $temp_start_date ) )
		{
			$temp_start_time = param( 'goal_temp_start_time', 'string' );
			$temp_start_time = empty( $temp_start_time ) ? '00:00:00' : param_time( 'goal_temp_start_time' );
			$this->set( 'temp_start_ts', form_date( $temp_start_date, $temp_start_time ) );
		}
		else
		{
			$this->set( 'temp_start_ts', NULL );
		}

		// Temporary End
		$temp_end_date = param_date( 'goal_temp_end_date', sprintf( T_('Please enter a valid date using the following format: %s'), '<code>'.locale_input_datefmt().'</code>' ), false );
		if( ! empty( $temp_end_date ) )
		{
			$temp_end_time = param( 'goal_temp_end_time', 'string' );
			$temp_end_time = empty( $temp_end_time ) ? '00:00:00' : param_time( 'goal_temp_end_time' );
			$this->set( 'temp_end_ts', form_date( $temp_end_date, $temp_end_time ) );
		}
		else
		{
			$this->set( 'temp_end_ts', NULL );
		}

		if( $this->get( 'temp_start_ts' ) !== NULL && $this->get( 'temp_end_ts' ) !== NULL &&
		    strtotime( $this->get( 'temp_start_ts' ) ) >= strtotime( $this->get( 'temp_end_ts' ) ) )
		{ // Compare Start and End dates
			param_error( 'goal_temp_start_date', NULL, '' );
			param_error( 'goal_temp_start_time', NULL, '' );
			param_error( 'goal_temp_end_date', NULL, '' );
			param_error( 'goal_temp_end_time', T_( 'Temporary Start Date/Time should not be greater than Temporary End Date/Time' ) );
		}

		// Default value:
		param( 'goal_default_value', 'string' );
		param_check_decimal( 'goal_default_value', T_('Default value must be a number.') );
		$this->set_from_Request( 'default_value', 'goal_default_value', true  );

		// Notes
		param( 'goal_notes', 'text' );
		$this->set_from_Request( 'notes', 'goal_notes' );

		if( ! param_errors_detected() )
		{	// Check goal key for duplicating:
			$existing_goal_ID = $this->dbexists( 'goal_key', $this->get( 'key' ) );
			if( $existing_goal_ID )
			{	// We have a duplicate goal:
				global $Collection, $Blog;
				param_error( 'goal_key',
					sprintf( T_('This goal already exists. Do you want to <a %s>edit the existing goal</a>?'),
						'href="?ctrl=goals&amp;action=edit'.( isset( $Blog ) ? '&amp;blog='.$Blog->ID : '' ).'&amp;goal_ID='.$existing_goal_ID.'"' ) );
			}
		}

		return ! param_errors_detected();
	}


	function get_name()
	{
		return $this->name;
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
			case 'default_value':
				return $this->set_param( $parname, 'number', $parvalue, true );

			case 'redir_url':
			case 'temp_redir_url':
				return $this->set_param( $parname, 'string', $parvalue, true );

			case 'name':
			case 'key':
			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}


	/**
	 * Get redirection URL that is active now
	 *
	 * @param array Params
	 * @return string $this->redir_url or $this->temp_redir_url
	 */
	function get_active_url( $params = array() )
	{
		$params = array_merge( array(
			'before_temp' => '',
			'after_temp'  => '',
			), $params );

		if( empty( $this->temp_redir_url ) )
		{ // Use normal redirection URL when temporary redirection URL is empty
			return $this->redir_url;
		}
		else
		{ // Check if we can use temporary redirection URL
			global $localtimenow;

			if( ( empty( $this->temp_start_ts ) || $this->temp_start_ts <= $localtimenow ) &&
			    ( empty( $this->temp_end_ts ) || $this->temp_end_ts >= $localtimenow ) )
			{ // Use temporary redirection URL now
				return $params['before_temp'].$this->temp_redir_url.$params['after_temp'];
			}
			else
			{ // Temporary redirection URL is out date, Use normal URL now
				return $this->redir_url;
			}
		}
	}


	/**
	 * Record goal hit
	 *
	 * @param string Extra params, Use '#' to get params from the $_SERVER['QUERY_STRING']
	 */
	function record_hit( $extra_params = '#' )
	{
		global $DB, $Hit;

		if( $extra_params == '#' )
		{ // Use standard extra params from query string
			if( isset( $_SERVER['QUERY_STRING'] ) )
			{ // Set additional params in goal hit
				$extra_params = '&'.$_SERVER['QUERY_STRING'].'&';
				$extra_params = preg_replace( '/&key=[^&]+(&)?/i', '$1', $extra_params );
				$extra_params = trim( $extra_params, '&' );
			}
			if( empty( $extra_params ) )
			{ // No extra params
				$extra_params = NULL;
			}
		}

		// We need to log the HIT now! Because we need the hit ID!
		$Hit->log();

		// Insert a goal hit:
		$DB->query( 'INSERT INTO T_track__goalhit ( ghit_goal_ID, ghit_hit_ID, ghit_params )
			VALUES ( '.$this->ID.', '.$Hit->ID.', '.$DB->quote( $extra_params ).' )',
			'Record goal hit' );
	}
}

?>