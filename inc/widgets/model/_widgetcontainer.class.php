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
	var $skin_type;
	var $name;
	var $coll_ID;
	var $order;
	var $main;

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
			$this->skin_type = $db_row->wico_skin_type;
			$this->name = $db_row->wico_name;
			$this->coll_ID = $db_row->wico_coll_ID;
			$this->order = $db_row->wico_order;
			$this->main = $db_row->wico_main;
		}
	}


	/**
	 * Get this class db table config params
	 *
	 * @return array
	 */
	static function get_class_db_config()
	{
		static $widget_container_db_config;

		if( !isset( $widget_container_db_config ) )
		{
			$widget_container_db_config = array_merge( parent::get_class_db_config(),
				array(
					'dbtablename' => 'T_widget__container',
					'dbprefix'    => 'wico_',
					'dbIDname'    => 'wico_ID',
				)
			);
		}

		return $widget_container_db_config;
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
		param( 'wico_name', 'string', true );
		$this->set_from_Request( 'name' );

		param( 'wico_code', 'string', true );
		$this->set_from_Request( 'code' );

		param( 'wico_skin_type', 'string', '' );
		param_check_not_empty( 'wico_skin_type', sprintf( T_('The field &laquo;%s&raquo; cannot be empty.'), T_('Skin type') ) );
		$this->set_from_Request( 'skin_type' );

		param( 'wico_order', 'integer', $this->ID == 0 ? 0 : true );
		$this->set_from_Request( 'order' );

		if( ! param_errors_detected() )
		{	// Widget container code must be unique for collection, Check it only when no errors on the form:
			if( $wico_ID = $this->dbexists( array( 'wico_code', 'wico_coll_ID', 'wico_skin_type' ), array( $this->get( 'code' ), $this->get( 'coll_ID' ), $this->get( 'skin_type' ) ) ) )
			{	// We have a duplicate entry:
				param_error( 'ufdf_code',
					sprintf( T_('Another widget container already uses this code for this skin type. Do you want to <a %s>edit the existing widget container</a>?'),
						'href="?ctrl=widgets&amp;blog='.$this->get( 'coll_ID' ).'&amp;action=edit_container&amp;wico_ID='.$wico_ID.'"' ) );
			}
		}

		return !param_errors_detected();
	}
}
?>