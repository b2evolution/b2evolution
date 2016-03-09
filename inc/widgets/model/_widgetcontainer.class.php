<?php
/**
 * This file implements the WidgetContainer class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 *
 * @version $Id: _widgetcontainer.class.php 10060 2016-03-09 10:40:31Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * WidgetContainer class
 *
 * Represents a container in a web page which may contains many widgets
 *
 * @package evocore
 */
class WidgetContainer extends DataObject
{
	var $code;
	var $name;
	var $coll_ID;
	var $order;

	/**
	 * Constructor
	 *
	 * @param object data row from db
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_widget__container', 'wico_', 'wico_ID' );

		if( ! is_null($db_row) )
		{
			$this->ID = $db_row->wico_ID;
			$this->code = $db_row->wico_code;
			$this->name = $db_row->wico_name;
			$this->coll_ID = $db_row->wico_coll_ID;
			$this->order = $db_row->wico_order;
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
				array( 'table'=>'T_widget__widget', 'fk'=>'wi_wico_ID', 'msg'=>T_('%d widget in this container') ),
			);
	}


	/**
	 * Insert object into DB based on previously recorded changes.
	 *
	 * @return boolean true on success
	 */
	function dbinsert()
	{
		global $DB;

		if( $this->ID != 0 )
		{
			debug_die( 'Existing object cannot be inserted!' );
		}

		$DB->begin();

		if( ( !isset( $this->order ) ) || ( $this->order <= 0 ) )
		{ // Set the order of the container only if it was not defined explicitly
			$order_max = $DB->get_var(
				'SELECT MAX(wico_order)
					 FROM T_widget__container
					WHERE wico_coll_ID = '.$this->coll_ID, 0, 0, 'Get current max order' );

			$this->set( 'order', $order_max + 1 );
		}

		$res = parent::dbinsert();

		$DB->commit();

		return $res;
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		global $Messages;

		// Get main widget contianers
		$main_containers = & get_widget_containers();

		$wico_code = param( 'wico_code', 'string', true );
		if( isset( $main_containers[$wico_code] ) )
		{
			$Messages->add( T_('The given container code is used by a main container, please type another.'), 'error' );
		}
		$this->set( 'code', $wico_code );
		$this->set( 'name', param( 'wico_name', 'string', true ) );
		if( $this->ID != 0 )
		{
			$this->set( 'order', param( 'wico_order', 'integer', true ) );
		}
		else
		{
			$this->set( 'order', param( 'wico_order', 'integer', 0 ) );
		}

		return !param_errors_detected();
	}
}
?>