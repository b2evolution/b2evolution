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

  	$this->delete_cascades = array(
			);

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


/*
 * $Log$
 * Revision 1.10  2011/09/07 12:00:20  lxndral
 * internal searches update
 *
 * Revision 1.9  2009/09/20 20:07:19  blueyed
 *  - DataObject::dbexists quotes always
 *  - phpdoc fixes
 *  - style fixes
 *
 * Revision 1.8  2009/09/19 20:49:51  fplanque
 * Cleaner way of implementing permissions.
 *
 * Revision 1.7  2009/09/14 13:38:10  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.6  2009/09/02 22:50:50  efy-maxim
 * Clean error message for currency/goal already exists
 *
 * Revision 1.5  2009/08/30 17:27:03  fplanque
 * better NULL param handling all over the app
 *
 * Revision 1.4  2009/08/30 14:00:53  fplanque
 * simpler form processing
 *
 * Revision 1.3  2009/08/30 00:30:52  fplanque
 * increased modularity
 *
 * Revision 1.2  2008/05/26 19:26:32  fplanque
 * minor
 *
 * Revision 1.1  2008/04/20 13:13:36  fplanque
 * no message
 *
 */
?>