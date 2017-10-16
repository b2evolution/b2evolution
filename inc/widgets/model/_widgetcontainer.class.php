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
	var $item_ID;
	var $ityp_ID;

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
			$this->item_ID = $db_row->wico_item_ID;
			$this->ityp_ID = $db_row->wico_ityp_ID;
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
		{	// Set the order of the container only if it was not defined explicitly
			$SQL = new SQL( 'Get max order of '.( $this->get( 'coll_ID' ) == 0 ? 'shared containers' : 'containers in collection #'.$this->get( 'coll_ID' ) ) );
			$SQL->SELECT( 'MAX( wico_order )' );
			$SQL->FROM( 'T_widget__container' );
			$SQL->WHERE( 'wico_coll_ID '.( $this->get( 'coll_ID' ) == 0 ? 'IS NULL' : '= '.$this->get( 'coll_ID' ) ) );
			$this->set( 'order', $DB->get_var( $SQL ) + 1 );
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
		$wico_coll_ID = param( 'wico_coll_ID', 'integer', NULL );
		$this->set( 'coll_ID', ( empty( $wico_coll_ID ) ? NULL : $wico_coll_ID ), true );

		if( $this->get( 'coll_ID' ) == 0 )
		{	// If shared container:
			$this->set( 'main', param( 'wico_container_type', 'string' ) == 'sub' ? '0' : '1' );
		}

		// Item ID/Item Type ID:
		switch( param( 'wico_page_type', 'string', NULL ) )
		{
			case 'type':
				param( 'wico_ityp_ID', 'integer', true );
				param_check_not_empty( 'wico_ityp_ID', sprintf( T_('The field &laquo;%s&raquo; cannot be empty.'), T_('For a new page of type') ) );
				$this->set_from_Request( 'ityp_ID' );
				$this->set( 'item_ID', NULL, true );
				break;

			case 'item':
				param( 'wico_item_ID', 'integer', true );
				param_check_not_empty( 'wico_item_ID', sprintf( T_('The field &laquo;%s&raquo; cannot be empty.'), T_('For an existing page') ) );
				$this->set_from_Request( 'item_ID' );
				$this->set( 'ityp_ID', NULL, true );
				break;

			default:
				$this->set( 'ityp_ID', NULL, true );
				$this->set( 'item_ID', NULL, true );
				break;
		}

		param( 'wico_name', 'string', true );
		param_check_not_empty( 'wico_name', sprintf( T_('The field &laquo;%s&raquo; cannot be empty.'), T_('Name') ) );
		$this->set_from_Request( 'name' );

		param( 'wico_code', 'string', true );
		param_check_not_empty( 'wico_code', sprintf( T_('The field &laquo;%s&raquo; cannot be empty.'), T_('Code') ) );
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