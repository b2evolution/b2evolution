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

load_class('_core/model/dataobjects/_dataobject.class.php');

/**
 * Filetype Class
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
	 * @param table Database row
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

 		if( $db_row != NULL )
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
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// Name
		param_string_not_empty( 'goal_name', T_('Please enter a name.') );
		$this->set_from_Request( 'name' );

		// Key
		param_string_not_empty( 'goal_key', T_('Please enter a name.') );
		$this->set_from_Request( 'key' );

		param( 'goal_redir_url', 'string' );
		$this->set_from_Request( 'redir_url', 'goal_redir_url', true  );

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
	 */
	function set( $parname, $parvalue )
	{
		switch( $parname )
		{
			case 'default_value':
				$this->set_param( $parname, 'number', $parvalue, true );
				break;

			case 'redir_url':
				$this->set_param( $parname, 'string', $parvalue, true );
				break;

			case 'name':
			case 'key':
			default:
				$this->set_param( $parname, 'string', $parvalue );
		}
	}

}

/*
 * $Log$
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