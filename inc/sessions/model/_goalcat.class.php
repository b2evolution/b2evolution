<?php
/**
 * This file implements the Goal Category class.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * Goal Category Class
 *
 * @package evocore
 */
class GoalCategory extends DataObject
{
	var $name = '';
	var $color = '';

	/**
	 * Constructor
	 *
	 * @param object Database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_track__goalcat', 'gcat_', 'gcat_ID' );

		if( $db_row )
		{
			$this->ID            = $db_row->gcat_ID;
			$this->name          = $db_row->gcat_name;
			$this->color         = $db_row->gcat_color;
		}
		else
		{ // Create a new goal category:
		}
	}


	/**
	 * Get delete restriction settings
	 *
	 * @return array
	 */
	static function get_delete_restrictions()
	{
		return array(
				array( 'table'=>'T_track__goal', 'fk'=>'goal_gcat_ID', 'msg'=>T_('%d related goals') ),
			);
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

		// Color
		$color = param( 'gcat_color', 'string', '' );
		param_check_color( 'gcat_color', T_('Invalid color code.'), true );
		$this->set_string_from_param( 'color', true );

		return ! param_errors_detected();
	}


	/**
	 * Get name
	 *
	 * @return string Goal category name
	 */
	function get_name()
	{
		return $this->name;
	}
}

?>