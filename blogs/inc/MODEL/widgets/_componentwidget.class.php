<?php
/**
 * This file implements the Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class ComponentWidget extends DataObject
{
	var $coll_ID;
	/**
	 * Container name
	 */
	var $sco_name;
	var $order;
	var $type;
	var $code;
	var $params;

	/**
	 * Lazy instantiated
	 * (false if this Widget is not handled by a Plugin)
	 * @see get_Plugin()
	 * @var Plugin
	 */
	var $Plugin;


	/**
	 * Constructor
	 */
	function ComponentWidget( $db_row = NULL, $type = 'core', $code = NULL, $params = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_widget', 'wi_', 'wi_ID' );

		if( is_null($db_row) )
		{	// We are creating an object here:
			$this->set( 'type', $type );
			$this->set( 'code', $code );
			// $this->set( 'params', $params );
		}
		else
		{	// Wa are loading an object:
			$this->ID       = $db_row->wi_ID;
			$this->coll_ID  = $db_row->wi_coll_ID;
			$this->sco_name = $db_row->wi_sco_name;
			$this->type     = $db_row->wi_type;
			$this->code     = $db_row->wi_code;
			$this->params   = $db_row->wi_params;
			$this->order    = $db_row->wi_order;
		}
	}


	/**
	 * Get ref to Plugin handling this Widget
	 *
	 * @return Plugin
	 */
	function & get_Plugin()
	{
		global $Plugins;

		if( is_null( $this->Plugin ) )
		{
			if( $this->type != 'plugin' )
			{
				$this->Plugin = false;
			}
			else
			{
				$this->Plugin = & $Plugins->get_by_code( $this->code );
			}
		}

		return $this->Plugin;
	}


	/**
	 * Get name of widget
	 *
	 * Should be overriden by core widgets
	 */
	function get_name()
	{
		switch( $this->type )
		{
			case 'plugin':
				// Make sure Plugin is loaded:
				if( $this->get_Plugin() )
				{
					return $this->Plugin->name;
				}
				return T_('Inactive / Uninstalled plugin');
				break;
		}

		return T_('Unknown');
	}


	/**
	 * Get desc of widget
	 *
	 * Should be overriden by core widgets
	 */
	function get_desc()
	{
		switch( $this->type )
		{
			case 'plugin':
				// Make sure Plugin is loaded:
				if( $this->get_Plugin() )
				{
					return $this->Plugin->short_desc;
				}
				return T_('Inactive / Uninstalled plugin');
				break;
		}

		return T_('Unknown');
	}

  /**
	 * Unserialize params
	 */
	function get_params()
	{
		$params = @unserialize( $this->params );
		if( empty( $params ) )
		{
			$params = array();
		}
		return $params;
	}


  /**
	 * Serialize params
	 */
	function set_params( $params )
	{
		$this->set_param( 'params', 'string', serialize($params) );
	}


	function init_display( $params )
	{
		// Customize params to the current widget:
		$this->disp_params = str_replace( '$wi_class$', 'widget_'.$this->type.'_'.$this->code, $params );
	}

	/**
	 * Display the widget!
	 *
	 * Should be overriden by core widgets
	 *
	 * @todo fp> handle custom params for each widget
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Blog;
		global $Plugins;
		global $rsc_url;

		$this->init_display( $params );

		switch( $this->type )
		{
			case 'plugin':
				// Call plugin (will return false if Plugin is not enabled):
				if( $Plugins->call_by_code( $this->code, $this->disp_params ) )
				{
					return true;
				}
				break;
		}

		echo '<!-- Unkown '.$this->type.' widget: '.$this->code.' -->';
	}


  /**
	 * @private
	 */
	function disp_title( $title )
	{
		if( $this->disp_params['block_display_title'] )
		{
			echo $this->disp_params['block_title_start'];
			echo $title;
			echo $this->disp_params['block_title_end'];
		}
	}


  /**
	 * List of items
	 *
	 * @param array MUST contain at least the basic display params
	 * @param string 'pages' or 'posts'
	 */
	function disp_item_list( $what )
	{
		global $Blog;

		// Create ItemList
		$ItemList = & new ItemListLight( $Blog );
		// Filter list:
		if( $what == 'pages' )
		{
			$ItemList->set_filters( array(
					'types' => '1000',					// Restrict to type 1000 (pages)
					'orderby' => 'title',
					'order' => 'ASC',
					'unit' => 'all',						// We want to advertise all items (not just a page or a day)
				) );
		}
		else
		{	// post list
			$ItemList->set_filters( array(
					'unit' => 'all',						// We want to advertise all items (not just a page or a day)
				) );
		}
		// Run the query:
		$ItemList->query();

		if( ! $ItemList->result_num_rows )
		{	// Nothing to display:
			return;
		}

		echo $this->disp_params['block_start'];

		if( $what == 'pages' )
		{
   		$this->disp_title( $this->disp_params, T_('Info pages') );
		}
		else
		{
			$this->disp_title( $this->disp_params, T_('Contents') );
		}

		echo $this->disp_params['list_start'];

		while( $Item = & $ItemList->get_item() )
		{
			echo $this->disp_params['item_start'];
			$Item->permanent_link('#title#');
			echo $this->disp_params['item_end'];
		}

		echo $this->disp_params['list_end'];

		echo $this->disp_params['block_end'];
	}


  /**
	 * List of collections/blogs
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function disp_coll_list( $filter = 'public' )
	{
    /**
		 * @var Blog
		 */
		global $Blog;

		echo $this->disp_params['block_start'];

		$this->disp_title( $this->disp_params, T_('Blogs') );

		echo $this->disp_params['list_start'];

    /**
		 * @var BlogCache
		 */
		$BlogCache = & get_Cache( 'BlogCache' );

		if( $filter == 'owner' )
		{	// Load blogs of same owner
			$blog_array = $BlogCache->load_owner_blogs( $Blog->owner_user_ID, 'ID' );
		}
		else
		{	// Load all public blogs
			$blog_array = $BlogCache->load_public( 'ID' );
		}

		foreach( $blog_array as $l_blog_ID )
		{	// Loop through all public blogs:

			$l_Blog = & $BlogCache->get_by_ID( $l_blog_ID );

			if( $l_blog_ID == $Blog->ID )
			{ // This is the blog being displayed on this page:
  			echo $this->disp_params['item_selected_start'];
				$link_class = $this->disp_params['link_selected_class'];
			}
			else
			{
				echo $this->disp_params['item_start'];
				$link_class = $this->disp_params['link_default_class'];;
			}

			echo '<a href="'.$l_Blog->gen_blogurl().'" class="'.$link_class.'" title="'
										.$l_Blog->dget( 'name', 'htmlattr' ).'">';

			if( $l_blog_ID == $Blog->ID )
			{ // This is the blog being displayed on this page:
  			echo $this->disp_params['item_selected_text_start'];
				echo $l_Blog->dget( 'shortname', 'htmlbody' );
  			echo $this->disp_params['item_selected_text_end'];
				echo '</a>';
  			echo $this->disp_params['item_selected_end'];
			}
			else
			{
  			echo $this->disp_params['item_text_start'];
				echo $l_Blog->dget( 'shortname', 'htmlbody' );
  			echo $this->disp_params['item_text_end'];
				echo '</a>';
				echo $this->disp_params['item_end'];
			}
		}

		echo $this->disp_params['list_end'];

		echo $this->disp_params['block_end'];
	}


	/**
	 * Insert object into DB based on previously recorded changes.
	 *
	 * @return boolean true on success
	 */
	function dbinsert()
	{
		global $DB;

		if( $this->ID != 0 ) die( 'Existing object cannot be inserted!' );

		$DB->begin();

		$order_max = $DB->get_var(
			'SELECT MAX(wi_order)
				 FROM T_widget
				WHERE wi_coll_ID = '.$this->coll_ID.'
					AND wi_sco_name = '.$DB->quote($this->sco_name), 0, 0, 'Get current max order' );

		$this->set( 'order', $order_max+1 );

		$res = parent::dbinsert();

		$DB->commit();

		return $res;
	}
}


/*
 * $Log$
 * Revision 1.1  2007/06/18 21:25:47  fplanque
 * one class per core widget
 *
 * Revision 1.26  2007/05/28 15:18:30  fplanque
 * cleanup
 *
 * Revision 1.25  2007/05/28 01:36:24  fplanque
 * enhanced blog list widget
 *
 * Revision 1.24  2007/05/09 01:58:57  fplanque
 * Widget to display other blogs from same owner
 *
 * Revision 1.23  2007/05/09 01:00:24  fplanque
 * optimized querying for blog lists
 *
 * Revision 1.22  2007/05/08 00:42:07  fplanque
 * public blog list as a widget
 *
 * Revision 1.21  2007/05/07 23:26:19  fplanque
 * public blog list as a widget
 *
 * Revision 1.20  2007/04/26 00:11:06  fplanque
 * (c) 2007
 *
 * Revision 1.19  2007/03/27 18:00:13  blueyed
 * Fixed E_FATAL: "Cannot redeclare ComponentWidget::$order"; doc
 *
 * Revision 1.18  2007/03/26 17:12:40  fplanque
 * allow moving of widgets
 *
 * Revision 1.17  2007/03/26 14:21:30  fplanque
 * better defaults for pages implementation
 *
 * Revision 1.16  2007/03/26 12:59:18  fplanque
 * basic pages support
 *
 * Revision 1.15  2007/03/25 13:19:17  fplanque
 * temporarily disabled dynamic and static urls.
 * may become permanent in favor of a caching mechanism.
 *
 * Revision 1.14  2007/03/04 21:46:39  fplanque
 * category directory / albums
 *
 * Revision 1.13  2007/02/05 00:35:43  fplanque
 * small adjustments
 *
 * Revision 1.12  2007/01/25 13:41:50  fplanque
 * wording
 *
 * Revision 1.11  2007/01/14 03:24:30  fplanque
 * widgets complete proof of concept with multiple skins
 *
 * Revision 1.10  2007/01/14 01:32:11  fplanque
 * more widgets supported! :)
 *
 * Revision 1.9  2007/01/13 22:28:12  fplanque
 * doc
 *
 * Revision 1.8  2007/01/13 18:40:33  fplanque
 * SkinTag/Widget plugins now get displayed inside of the containers.
 * next step: adapt all default skins to use this.
 *
 * Revision 1.7  2007/01/13 14:35:42  blueyed
 * todo: $Plugin should be a ref?!
 *
 * Revision 1.6  2007/01/13 04:10:44  fplanque
 * implemented "add" support for plugin widgets
 *
 * Revision 1.5  2007/01/12 02:40:26  fplanque
 * widget default params proof of concept
 * (param customization to be done)
 *
 * Revision 1.4  2007/01/11 20:44:19  fplanque
 * skin containers proof of concept
 * (no params handling yet though)
 *
 * Revision 1.3  2007/01/11 02:57:25  fplanque
 * implemented removing widgets from containers
 *
 * Revision 1.2  2007/01/08 23:45:48  fplanque
 * A little less rough widget manager...
 * (can handle multiple instances of same widget and remembers order)
 *
 * Revision 1.1  2007/01/08 21:55:42  fplanque
 * very rough widget handling
 *
 */
?>