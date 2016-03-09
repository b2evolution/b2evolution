<?php
/**
 * This file implements the WidgetCache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 *
 * @author fplanque: Francois PLANQUE
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobjectcache.class.php', 'DataObjectCache' );

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );

/**
 * Widget Cache Class
 *
 * @package evocore
 */
class WidgetCache extends DataObjectCache
{
	/**
	 * Cache by container
	 * @var array of coll_ID => array of container_name => array of Widget
	 */
	var $cache_container_Widget_array = array();

	/**
	 * Cache by container code
	 * @var array of coll_ID => array of container_code => array of Widget
	 */
	var $cache_container_Widget_array_by_code = array();

	/**
	 * Cache by WidgetContainer ID
	 * @var array of wico_ID => array of Widget
	 */
	var $cache_container = array();

	/**
	 * Indicates whether to load enabled widgets only.
	 * @var boolean
	 */
	var $load_enabled_only;

	/**
	 * Constructor
	 *
	 * @param boolean Load enabled widgets only?
	 */
	function __construct( $enabled_only = false )
	{
		parent::__construct( 'ComponentWidget', false, 'T_widget__widget', 'wi_', 'wi_ID', NULL, NULL, NULL );
		$this->load_enabled_only = $enabled_only;
	}


	/**
	 * @param integer Collection (blog) ID
	 * @return array of coll_ID => array of container_name => array of Widget
	 */
	function & get_by_coll_ID( $coll_ID, $by_code = false )
	{
		global $DB;

		if( ! isset( $this->cache_container_Widget_array[$coll_ID] ) )
		{	// Not in Cache yet:
			$sql = 'SELECT wi_ID, wi_wico_ID, wico_name, wico_code, wi_order, wi_enabled, wi_type, wi_code, wi_params
					      FROM T_widget__widget INNER JOIN T_widget__container ON wi_wico_ID = wico_ID
					     WHERE wico_coll_ID = '.$coll_ID;
			if ( $this->load_enabled_only )
			{	// We want to load enabled widgets only:
				$sql .= ' AND wi_enabled = 1';
			}
			$sql .= ' ORDER BY wico_order, wi_order';

			$widget_rs = $DB->get_results( $sql, OBJECT, 'Get list of widgets for collection' );

			$this->cache_container_Widget_array[$coll_ID] = array();
			$this->cache_container_Widget_array_by_code[$coll_ID] = array();
			$count = count( $widget_rs );
			for( $i = 0; $i < $count; $i++ )
			{
				// fp> NOTE: object COPYing is weird here but it needs to be like this in PHP4 or all abjects from the loop will look the same
				if( $ComponentWidget = & $this->new_obj( $widget_rs[$i] ) ) // fp> NOTE: no copy because we need copy on the next line anyway!!
				{	// We were able to instantiate the widget:
					// Add to regular cache (but not with $this->add() because we need a COPY!!):
					$this->cache[$ComponentWidget->ID] = $ComponentWidget; // COPY!!!! WEIRD BUT NECESSARY / PHP 4 (fp)
					// This is the cache we're interested in:
					$this->cache_container_Widget_array[$coll_ID][$widget_rs[$i]->wico_name][] = & $this->cache[$ComponentWidget->ID];
					// This is the cache by wico code
					$this->cache_container_Widget_array_by_code[$coll_ID][$widget_rs[$i]->wico_code][] = & $this->cache[$ComponentWidget->ID];
				}

				// TODO: dh> try the next line, and you may be able to assign by reference to $cache or use add()
				unset($ComponentWidget);
			}
			// pre_dump($this->cache_container_Widget_array[$coll_ID]);
		}

		if( $by_code )
		{
			return $this->cache_container_Widget_array_by_code[$coll_ID];
		}

		return $this->cache_container_Widget_array[$coll_ID];
	}


	/**
	 * Instanciate a new object within this cache
	 */
	function & new_obj( $row = NULL )
	{
		global $inc_path;

		if( $row->wi_type == 'core' )
		{
		/*
		fp>alex TODO: This feature is ok BUT please move this code into the offending widget files
		             there must not be any hard coced widget named in this class

			if( ! isset($GLOBALS['files_Module']) && in_array( $row->wi_code, array('coll_media_index', 'coll_avatar') ) )
			{	// Disable widgets dependent on files_Module
				$r = NULL;
				return $r;
			}
		*/

			if( ! file_exists( $inc_path.'widgets/widgets/_'.$row->wi_code.'.widget.php' ) )
			{	// For some reason, that widget doesn't seem to exist... (any more?)
				// echo "Widget $row->wi_code could not be loaded! ";
				// TODO: replace with dummy widget in order to give a chance to clean up.
				$r = NULL;
				return $r;
			}
			require_once $inc_path.'widgets/widgets/_'.$row->wi_code.'.widget.php';
			$objtype = $row->wi_code.'_Widget';
		}
		else
		{
			$objtype = 'ComponentWidget';
		}

		// Instantiate a custom object
		$obj = new $objtype( $row ); // COPY !!

		return $obj;
	}


	function & get_by_container_ID( $wico_ID )
	{
		global $DB;

		if( ! isset( $this->cache_container[ $wico_ID ] ) )
		{ // Not in Cache yet:
			$sql = 'SELECT wi_ID, wi_wico_ID, wi_order, wi_enabled, wi_type, wi_code, wi_params
					      FROM T_widget__widget
					     WHERE wi_wico_ID = '.$wico_ID;
			if( $this->load_enabled_only )
			{ // We want to load enabled widgets only:
				$sql .= ' AND wi_enabled = 1';
			}
			$sql .= ' ORDER BY wi_order';

			$results = $DB->get_results( $sql, OBJECT, 'Get list of widgets for collection' );
			$this->cache_container[$wico_ID] = array();
			foreach( $results as $row )
			{
				$this->instantiate( $row );
				if( $this->cache[$row->wi_ID] )
				{
					$this->cache_container[$wico_ID][] = & $this->cache[$row->wi_ID];
				}
			}
		}

		return $this->cache_container[ $wico_ID ];
	}


	/**
	 * @param integer Collection (blog) ID
	 * @param string Container
	 * @return array of Widget
	 */
	function & get_by_coll_container( $coll_ID, $container, $by_code = false )
	{
		// Make sure collection is loaded:
		$this->get_by_coll_ID( $coll_ID );

		if( $by_code )
		{
			return $this->cache_container_Widget_array_by_code[$coll_ID][$container];
		}

		return $this->cache_container_Widget_array[$coll_ID][$container];
	}
}

?>