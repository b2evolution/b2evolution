<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * CollGroup Class
 *
 * @package evocore
 */
class CollGroup extends DataObject
{
	var $name = '';
	var $order = '';

	/**
	 * Constructor
	 *
	 * @param object Database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_coll_groups', 'cgrp_', 'cgrp_ID' );

		if( $db_row != NULL )
		{
			$this->ID    = $db_row->cgrp_ID;
			$this->name  = $db_row->cgrp_name;
			$this->order = $db_row->cgrp_order;
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
				array( 'table' => 'T_blogs', 'fk' => 'blog_cgrp_ID', 'msg' => T_('%d collections in this group') ),
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
		param_string_not_empty( 'cgrp_name', T_('Please enter a group name.') );
		$this->set_from_Request( 'name' );

		// Order
		if( param( 'cgrp_order', 'integer' ) !== 0 ) // Allow zero value
		{
			param_check_not_empty( 'cgrp_order', T_('Please enter an order number.') );
		}
		$this->set( 'order', param( 'cgrp_order', 'integer' ) );

		return ! param_errors_detected();
	}


	/**
	 * Get collection group name.
	 *
	 * @return string collection group name
	 */
	function get_name()
	{
		return $this->name;
	}
}
?>