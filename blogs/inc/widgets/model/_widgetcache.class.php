<?php
/**
 * This file implements the WidgetCache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class('_core/model/dataobjects/_dataobjectcache.class.php');

load_class( 'widgets/model/_widget.class.php' );

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
	 * Indicates whether to load enabled widgets only.
	 * @var boolean
	 */
	var $load_enabled_only;

	/**
	 * Constructor
	 *
	 * @param boolean Load enabled widgets only?
	 */
	function WidgetCache( $enabled_only = false )
	{
		parent::DataObjectCache( 'ComponentWidget', false, 'T_widget', 'wi_', 'wi_ID', NULL, NULL, NULL );
		$this->load_enabled_only = $enabled_only;
	}


	/**
	 * @param integer Collection (blog) ID
	 * @return array of coll_ID => array of container_name => array of Widget
	 */
	function & get_by_coll_ID( $coll_ID )
	{
		global $DB;

		if( ! isset( $this->cache_container_Widget_array[$coll_ID] ) )
		{	// Not in Cache yet:
			$sql = 'SELECT *
					      FROM T_widget
					     WHERE wi_coll_ID = '.$coll_ID;
			if ( $this->load_enabled_only )
			{	// We want to load enabled widgets only:
				$sql .= ' AND wi_enabled = 1';
			}
			$sql .= ' ORDER BY wi_sco_name, wi_order';

			$widget_rs = $DB->get_results( $sql, OBJECT, 'Get list of widgets for collection' );

			$this->cache_container_Widget_array[$coll_ID] = array();
			$count = count( $widget_rs );
			for( $i = 0; $i < $count; $i++ )
			{
				// fp> NOTE: object COPYing is weird here but it needs to be like this in PHP4 or all abjects from the loop will look the same
				if( $ComponentWidget = & $this->new_obj( $widget_rs[$i] ) ) // fp> NOTE: no copy because we need copy on the next line anyway!!
				{	// We were able to instantiate the widget:
					// Add to regular cache (but not with $this->add() because we need a COPY!!):
					$this->cache[$ComponentWidget->ID] = $ComponentWidget; // COPY!!!! WEIRD BUT NECESSARY / PHP 4 (fp)
					// This is the cache we're interested in:
					$this->cache_container_Widget_array[$ComponentWidget->coll_ID][$ComponentWidget->sco_name][] = & $this->cache[$ComponentWidget->ID];
				}

				// TODO: dh> try the next line, and you may be able to assign by reference to $cache or use add()
				unset($ComponentWidget);
			}
			// pre_dump($this->cache_container_Widget_array[$coll_ID]);
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


	/**
	 * @param integer Collection (blog) ID
	 * @param string Container
	 * @return array of Widget
	 */
	function & get_by_coll_container( $coll_ID, $container )
	{
		// Make sure collection is loaded:
		$this->get_by_coll_ID( $coll_ID );

		return $this->cache_container_Widget_array[$coll_ID][$container];
	}
}


/*
 * $Log$
 * Revision 1.5  2009/03/06 16:40:26  blueyed
 * Fix path check/inclusion of widget classes.
 *
 * Revision 1.4  2009/03/05 23:38:53  blueyed
 * Merge autoload branch (lp:~blueyed/b2evolution/autoload) into CVS HEAD.
 *
 * Revision 1.3  2009/02/05 21:33:34  tblue246
 * Allow the user to enable/disable widgets.
 * Todo:
 * 	* Fix CSS for the widget state bullet @ JS widget UI.
 * 	* Maybe find a better solution than modifying get_Cache() to get only enabled widgets... :/
 * 	* Buffer JS requests when toggling the state of a widget??
 *
 * Revision 1.2  2008/01/21 09:35:36  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 11:01:57  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.3  2007/06/20 21:42:13  fplanque
 * implemented working widget/plugin params
 *
 * Revision 1.2  2007/06/20 14:25:00  fplanque
 * fixes
 *
 * Revision 1.1  2007/06/18 21:25:48  fplanque
 * one class per core widget
 *
 * Revision 1.5  2007/04/26 00:11:06  fplanque
 * (c) 2007
 *
 * Revision 1.4  2007/03/26 17:12:40  fplanque
 * allow moving of widgets
 *
 * Revision 1.3  2007/02/26 03:19:40  fplanque
 * hum... I cannot reproduce the old bug anymore :/
 *
 * Revision 1.2  2007/02/10 18:37:16  blueyed
 * doc/todo
 *
 * Revision 1.1  2007/01/11 20:44:19  fplanque
 * skin containers proof of concept
 * (no params handling yet though)
 */
?>
