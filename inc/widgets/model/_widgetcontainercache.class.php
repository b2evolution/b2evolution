<?php
/**
 * This file implements the WidgetContainerCache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package evocore
 *
 * @version $Id: _widgetcontainercache.class.php 10060 2016-03-09 10:40:31Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobjectcache.class.php', 'DataObjectCache' );

load_class( 'widgets/model/_widgetcontainer.class.php', 'WidgetContainer' );


/**
 * Widget Container Cache Class
 *
 * @package evocore
 */
class WidgetContainerCache extends DataObjectCache
{

	/**
	 * Cache by collection ID and widget container code
	 *
	 * @var cache_by_coll_and_code array
	 */
	var $cache_by_coll_and_code = array();

	/**
	 * Constructor
	 */
	function __construct()
	{
		// Call parent constructor:
		parent::__construct( 'WidgetContainer', true, 'T_widget__container', 'wico_', 'wico_ID', 'wico_order' );
	}


	/**
	 * Add a WidgetContainer to the cache
	 *
	 * @param Skin
	 * @return boolean true if it was added false otherwise
	 */
	function add( & $WidgetContainer )
	{
		$container_code = $WidgetContainer->get( 'code' );
		if( isset( $WidgetContainer->coll_ID ) && ( !empty( $container_code ) ) )
		{ // This container is not shared and it is a main container ( has code )
			if( ! isset( $this->cache_by_coll_and_code[$WidgetContainer->coll_ID] ) )
			{
				$this->cache_by_coll_and_code[$WidgetContainer->coll_ID] = array();
			}
			$this->cache_by_coll_and_code[$WidgetContainer->coll_ID][$container_code] = & $WidgetContainer;
		}

		return parent::add( $WidgetContainer );
	}


	/**
	 * Load all widget containers from the given collection
	 *
	 * @param integer collection ID
	 */
	function load_by_coll_ID( $coll_ID )
	{
		global $DB;

		if( !empty( $this->cache_by_coll_and_code[$coll_ID] ) )
		{
			return;
		}

		$sql = 'SELECT *
			FROM T_widget__container
			WHERE wico_coll_ID = '.$DB->quote( $coll_ID ).' AND wico_code IS NOT NULL
			ORDER BY wico_order';
		foreach( $DB->get_results( $sql ) as $row )
		{ // Instantiate
			$this->instantiate( $row );
		}
	}


	/**
	 * Get widget container from the given collection with the given container code
	 *
	 * @param integer collection ID
	 * @param string container code
	 * @param boolean halt on error
	 * @return WidgetContainer
	 */
	function & get_by_coll_and_code( $coll_ID, $wico_code, $halt_on_error = false )
	{
		global $DB;

		if( isset( $this->cache_by_coll_and_code[$coll_ID][$wico_code] ) )
		{
			return $this->cache_by_coll_and_code[$coll_ID][$wico_code];
		}

		$this->load_by_coll_ID( $coll_ID );

		if( empty( $this->cache_by_coll_and_code[$coll_ID][$wico_code] ) )
		{
			if( $halt_on_error )
			{
				debug_die( "Requested widget container does not exist!" );
			}
			$r = false;
			return $r;
		}

		return $this->cache_by_coll_and_code[$coll_ID][$wico_code];
	}


	/**
	 * Get widget containers from the given collection
	 *
	 * @param $coll_ID
	 * @return array of WidgetContainer
	 */
	function & get_by_coll_ID( $coll_ID )
	{
		$this->load_by_coll_ID( $coll_ID );
		if( isset( $this->cache_by_coll_and_code[$coll_ID] ) )
		{
			return $this->cache_by_coll_and_code[$coll_ID];
		}

		$r = array();
		return $r;
	}
}
?>