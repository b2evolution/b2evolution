<?php
/**
 * This file implements the Goal class.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
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
	var $name = '';
	var $key = '';
	var $redir_url = '';

	/**
	 * @var double
	 */
 	var $default_value = '';


	/**
	 * Constructor
	 *
	 * @param object Database row
	 */
	function Goal( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_track__goal', 'goal_', 'goal_ID' );

		$this->delete_restrictions = array(
				array( 'table'=>'T_track__goalhit', 'fk'=>'ghit_goal_ID', 'msg'=>T_('%d related goal hits') ),
			);

		$this->delete_cascades = array();

 		if( $db_row )
		{
			$this->ID            = $db_row->goal_ID;
			$this->name          = $db_row->goal_name;
			$this->key           = $db_row->goal_key;
			$this->redir_url     = $db_row->goal_redir_url;
			$this->default_value = $db_row->goal_default_value;
		}
		else
		{	// Create a new goal:
		}
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
		// Name
		$this->set_string_from_param( 'name', true );

		// Key
		$this->set_string_from_param( 'key', true );

		// Redir URL:
		$this->set_string_from_param( 'redir_url' );

		// Default value:
		param( 'goal_default_value', 'string' );
		param_check_decimal( 'goal_default_value', T_('Default value must be a number.') );
		$this->set_from_Request( 'default_value', 'goal_default_value', true  );

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
				return $this->set_param( $parname, 'string', $parvalue, true );

			case 'name':
			case 'key':
			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}


	/**
	 * Check existence of specified goal in goal_key unique field.
	 *
	 * @return int ID if goal exists otherwise NULL/false
	 */
	function dbexists()
	{
		return parent::dbexists('goal_key', $this->key);
	}
}

?>