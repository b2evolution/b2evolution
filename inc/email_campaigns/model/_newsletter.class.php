<?php
/**
 * This file implements the newsletter class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );


/**
 * Newsletter Class
 *
 * @package evocore
 */
class Newsletter extends DataObject
{
	var $name;

	var $label;

	var $active = 1;

	var $order;

	/**
	 * @var array IDs of subscribed users
	 */
	var $user_IDs = NULL;

	/**
	 * Constructor
	 *
	 * @param object table Database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_email__newsletter', 'enlt_', 'enlt_ID' );

		if( $db_row !== NULL )
		{
			$this->ID = $db_row->enlt_ID;
			$this->name = $db_row->enlt_name;
			$this->label = $db_row->enlt_label;
			$this->active = $db_row->enlt_active;
			$this->order = $db_row->enlt_order;
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
				array( 'table' => 'T_email__campaign', 'fk' => 'ecmp_enlt_ID', 'msg' => T_('%d campaigns are linked to this list') ),
				array( 'table' => 'T_automation__newsletter', 'fk' => 'aunl_enlt_ID', 'msg' => T_('%d automations use this list') ),
				array( 'table' => 'T_automation__step', 'fk' => 'step_info', 'and_condition' => 'step_type IN ( "subscribe", "unsubscribe" )', 'msg' => T_('%d automation steps use this list') ),
			);
	}


	/**
	 * Get delete cascade settings
	 *
	 * @return array
	 */
	static function get_delete_cascades()
	{
		return array(
				array( 'table'=>'T_email__newsletter_subscription', 'fk'=>'enls_enlt_ID', 'msg'=>T_('%d user subscriptions') ),
			);
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// Active:
		param( 'enlt_active', 'integer', 0 );
		$this->set_from_Request( 'active' );

		if( param( 'enlt_name', 'string', NULL ) !== NULL )
		{	// Name:
			param_string_not_empty( 'enlt_name', T_('Please enter a list name.') );
			$this->set_from_Request( 'name' );
		}

		// Label:
		param( 'enlt_label', 'string', NULL );
		$this->set_from_Request( 'label', 'enlt_label', true );

		// Order:
		param( 'enlt_order', 'integer', NULL );
		$this->set_from_Request( 'order', 'enlt_order', true );

		return ! param_errors_detected();
	}


	/**
	 * Get name of newsletter
	 *
	 * @return string Name of newsletter
	 */
	function get_name()
	{
		return $this->get( 'name' );
	}


	/**
	 * Get IDs of users which are subscribed on this newsletter
	 *
	 * @return array User IDs
	 */
	function get_user_IDs()
	{
		if( empty( $this->ID ) )
		{
			return array();
		}

		if( $this->user_IDs === NULL )
		{	// Load user IDs from DB once and store in cache array:
			global $DB;
			$SQL = new SQL( 'Get IDs of users which are subscribed on the newsletter #'.$this->ID );
			$SQL->SELECT( 'enls_user_ID' );
			$SQL->FROM( 'T_email__newsletter_subscription' );
			$SQL->WHERE( 'enls_enlt_ID = '.$this->ID );
			$SQL->WHERE_and( 'enls_subscribed = 1' );
			$this->user_IDs = $DB->get_col( $SQL );
		}

		return $this->user_IDs;
	}
}

?>