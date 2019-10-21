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

	var $Blog = NULL;

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
			$this->item_ID = $db_row->wico_item_ID;
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
	 * Inserts or Updates depending on object state.
	 *
	 * @uses dbinsert()
	 * @uses dbupdate()
	 * @return boolean true on success, false on failure
	 */
	function dbsave()
	{
		global $DB;

		$DB->begin();

		$result = true;

		// Page Container:
		if( isset( $this->container_ityp_ID ) )
		{	// We should create new Item automatically for selected Item Type on the form of Page Container:
			$ItemTypeCache = & get_ItemTypeCache();
			if( ( $widget_page_ItemType = & $ItemTypeCache->get_by_ID( $this->container_ityp_ID, false, false ) ) &&
					( $widget_page_ItemType->get( 'usage' ) == 'widget-page' ) &&
					( $widget_container_Blog = & $this->get_Blog() ) )
			{	// Allow to create new Item only with usage "Widget Page" of Item Type:
				load_class( 'items/model/_item.class.php', 'Item' );
				$widget_page_Item = new Item();
				$widget_page_Item->set( 'ityp_ID', $widget_page_ItemType->ID );
				$widget_page_Item->set( 'title', 'Widget Page for container "'.$this->get( 'name' ).'"' );
				$widget_page_Item->set( 'main_cat_ID', $widget_container_Blog->get_default_cat_ID() );
				if( $widget_page_Item->dbinsert() )
				{	// Update widget container with new inserted item ID:
					$this->set( 'item_ID', $widget_page_Item->ID );
					$result_message = sprintf( T_('New item %s has been created.'), '#'.$widget_page_Item->ID.'('.$widget_page_Item->get_title().')' );
				}
				else
				{	// New Item cannot be createed by some reason:
					$result = false;
				}
			}
			else
			{	// Wrong Item Type has been selected:
				$result = false;
			}
		}

		if( $result&& ( $result = parent::dbsave() ) )
		{	// If container has been saved successfully:
			$DB->commit();
			if( isset( $result_message ) )
			{	// Display message only after success widget container saving:
				global $Messages;
				$Messages->add( $result_message, 'success' );
			}
		}
		else
		{	// Rollback changes because of some error above:
			$DB->rollback();
		}

		return $result;
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		if( empty( $this->ID ) )
		{	// For new creating container we shoud get a type from hidden field:
			$container_type = param( 'container_type', 'string' );
		}
		else
		{	// For existing container we should get current type and don't change it:
			// (Only shared container may be changed to sub-container and reverse)
			$container_type = $this->get_type();
		}

		$wico_coll_ID = param( 'wico_coll_ID', 'integer', NULL );
		$this->set( 'coll_ID', ( empty( $wico_coll_ID ) ? NULL : $wico_coll_ID ), true );

		switch( $container_type )
		{
			case 'shared':
			case 'shared-sub':
				// Only shared containers may be switched between main and sub:
				$this->set( 'main', param( 'wico_container_type', 'string' ) == 'sub' ? '0' : '1' );
				set_param( 'container_type', $this->get_type() );
				break;

			case 'page':
				// Page container cannot be a sub-container:
				$this->set( 'main', '1' );
				$container_page_type = param( 'container_page_type', 'string', NULL );
				param_check_not_empty( 'container_page_type', T_('Please select page container type.') );
				switch( $container_page_type )
				{
					case 'type':
						$container_ityp_ID = param( 'container_ityp_ID', 'integer', true );
						param_check_not_empty( 'container_ityp_ID', sprintf( T_('The field &laquo;%s&raquo; cannot be empty.'), T_('For a new page of type') ) );
						// Set temp var to know what Item Type use on creating new Item in the WidgetContainer::dbsave():
						$this->container_ityp_ID = $container_ityp_ID;
						break;

					case 'item':
						$wico_item_ID = param( 'wico_item_ID', 'integer', true );
						if( param_check_not_empty( 'wico_item_ID', sprintf( T_('The field &laquo;%s&raquo; cannot be empty.'), T_('For an existing page') ) ) )
						{
							$this->set_from_Request( 'item_ID' );
							// Check for corrent Item:
							$ItemCache = &get_ItemCache();
							if( ! ( $widget_page_Item = & $ItemCache->get_by_ID( $wico_item_ID, false, false ) ) || 
									$widget_page_Item->get_type_setting( 'usage' ) != 'widget-page' )
							{	// Display error for unavailable item:
								param_error( 'wico_item_ID', T_('Item can be used for page container only with Item Type usage "Widget Page"!') );
							}
						}
						break;
				}
				break;
		}

		param_string_not_empty( 'wico_name', sprintf( T_('The field &laquo;%s&raquo; cannot be empty.'), T_('Name') ) );
		$this->set_from_Request( 'name' );

		param_string_not_empty( 'wico_code', sprintf( T_('The field &laquo;%s&raquo; cannot be empty.'), T_('Code') ) );
		param_check_regexp( 'wico_code', '#^[A-Za-z0-9\-_]{1,32}$#', sprintf( T_('The field "%s" must be from %d to %d letters, digits or signs %s.'), T_('Code'), 1, 32, '<code>_</code>, <code>-</code>' ) );
		$this->set_from_Request( 'code' );

		if( $this->ID == 0 )
		{	// Allow to set skin type only on creating new widget container:
			param( 'wico_skin_type', 'string', '' );
			param_check_not_empty( 'wico_skin_type', sprintf( T_('The field &laquo;%s&raquo; cannot be empty.'), T_('Skin type') ) );
			$this->set_from_Request( 'skin_type' );
		}

		
		if( $this->ID > 0 )
		{	// Field "Order" is required for existing container:
			param_string_not_empty( 'wico_order', sprintf( T_('The field &laquo;%s&raquo; cannot be empty.'), T_('Order') ) );
		}
		else
		{	// Order is set automatically only if it was not defined explicitly:
			param( 'wico_order', 'integer', 0 );
		}
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


	/**
	 * Get a member param by its name
	 *
	 * @param mixed Name of parameter
	 * @return mixed Value of parameter
	 */
	function get( $parname )
	{
		switch( $parname )
		{
			case 'type':
				return $this->get_type();

			default:
				return parent::get( $parname );
		}
	}


	/**
	 * Get the Collection object for this Widget Container
	 *
	 * @return object Collection
	 */
	function & get_Blog()
	{
		if( $this->Blog === NULL )
		{	// Load collection once:
			$BlogCache = & get_BlogCache();
			$this->Blog = & $BlogCache->get_by_ID( $this->coll_ID, false, false );
		}

		return $this->Blog;
	}


	/**
	 * Get widget container type
	 *
	 * @return string Container type: 'main', 'sub', 'page', 'shared', 'shared-sub'
	 */
	function get_type()
	{
		if( $this->get( 'coll_ID' ) )
		{	// Collection/skin container:
			if( $this->get( 'item_ID' ) !== NULL )
			{	// Page container:
				return 'page';
			}
			elseif( $this->get( 'main' ) )
			{	// Main container:
				return 'main';
			}
			else
			{	// Sub container:
				return 'sub';
			}
		}
		else
		{	// Shared container for ALL collections:
			if( $this->get( 'main' ) )
			{	// Shared main container:
				return 'shared';
			}
			else
			{	// Shared sub-container:
				return 'shared-sub';
			}
		}
	}


	/**
	 * Get title of container type
	 *
	 * @param string Type key: 'main', 'sub', 'page', 'shared', 'shared-sub', NULL - to use current type of this container
	 * @return string
	 */
	function get_type_title( $type = NULL )
	{
		if( $type === NULL )
		{	// Use current type of this container:
			$type = $this->get_type();
		}

		switch( $type )
		{
			case 'main':
				return T_('Skin container');
			case 'sub':
				return $this->ID ? T_('Sub-container') : T_('New sub-container');
			case 'page':
				return $this->ID ? T_('Page container') : T_('New page container');
			case 'shared':
				return $this->ID ? T_('Shared main container') : T_('New shared main container');
			case 'shared-sub':
				return $this->ID ? T_('Shared sub-container') : T_('New shared sub-container');
		}
	}
}
?>