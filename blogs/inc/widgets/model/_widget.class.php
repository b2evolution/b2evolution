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
	 * Array of params which have been customized for this widget instance
	 *
	 * This is saved to the DB as a serialized string ($params)
	 */
	var $param_array = NULL;

  /**
	 * Array of params used during display()
	 */
	var $disp_params;

	/**
	 * Lazy instantiated
	 * (false if this Widget is not handled by a Plugin)
	 * @see get_Plugin()
	 * @var Plugin
	 */
	var $Plugin;


	/**
	 * Constructor
	 *
	 * @param object data row from db
	 */
	function ComponentWidget( $db_row = NULL, $type = 'core', $code = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_widget', 'wi_', 'wi_ID' );

		if( is_null($db_row) )
		{	// We are creating an object here:
			$this->set( 'type', $type );
			$this->set( 'code', $code );
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
		if( $this->type == 'plugin' )
		{
			// Make sure Plugin is loaded:
			if( $this->get_Plugin() )
			{
				return $this->Plugin->name;
			}
			return T_('Inactive / Uninstalled plugin');
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
		if( $this->type == 'plugin' )
		{
			// Make sure Plugin is loaded:
			if( $this->get_Plugin() )
			{
				return $this->Plugin->short_desc;
			}
			return T_('Inactive / Uninstalled plugin');
		}

		return T_('Unknown');
	}


  /**
   * Get definitions for editable params
   *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		if( $this->type == 'plugin' )
		{
			// Make sure Plugin is loaded:
			if( $this->get_Plugin() )
			{
				return $this->Plugin->get_widget_param_definitions( $params );
			}
		}

		return array();
	}


  /**
	 * Load param array
	 */
	function load_param_array()
	{
		if( is_null( $this->param_array ) )
		{	// Param array has not been loaded yet
			$this->param_array = @unserialize( $this->params );

			if( empty( $this->param_array ) )
			{	// No saved param values were found:
				$this->param_array = array();
			}
		}
	}


  /**
 	 * param value
 	 *
	 */
	function get_param( $parname )
	{
		$this->load_param_array();

		if( isset( $this->param_array[$parname] ) )
		{	// We have a value for this param:
			return $this->param_array[$parname];
		}

		// Try default values:
		$params = $this->get_param_definitions( NULL );
		if( isset( $params[$parname]['defaultvalue'] ) )
		{	// We ahve a default value:
			return $params[$parname]['defaultvalue'] ;
		}

		return NULL;
	}


	/**
	 * Set param value
	 *
	 * @param string parameter name
	 * @param mixed parameter value
	 * @return boolean true, if a value has been set; false if it has not changed
	 */
	function set( $parname, $parvalue )
	{
		$params = $this->get_param_definitions( NULL );

		if( isset( $params[$parname] ) )
		{	// This is a widget specifc param:
			$this->param_array[$parname] = $parvalue;
			// This is what'll be saved to the DB:
			$this->set_param( 'params', 'string', serialize($this->param_array) );
			return;
		}

		switch( $parname )
		{
			default:
				return parent::set_param( $parname, 'string', $parvalue );
		}
	}


  /**
	 * Prepare display params
	 */
	function init_display( $params )
	{
		// Generate widget defaults array:
		$widget_defaults = array();
		$defs = $this->get_param_definitions( array() );
		foreach( $defs as $parname => $parmeta )
		{
			if( isset( $parmeta['defaultvalue'] ) )
			{
				$widget_defaults[ $parname ] = $parmeta['defaultvalue'];
			}
			else
			{
				$widget_defaults[ $parname ] = NULL;
			}
		}

		// Load DB configuration:
		$this->load_param_array();

		// Merge basic defaults < widget defaults < container params < DB params
		// note: when called with skin_widget it falls back to basic defaults < widget defaults < calltime params < array()
		$params = array_merge( array(
					'block_start' => '<div class="$wi_class$">',
					'block_end' => '</div>',
					'block_display_title' => true,
					'block_title_start' => '<h3>',
					'block_title_end' => '</h3>',
					'collist_start' => '',
					'collist_end' => '',
					'coll_start' => '<h4>',
					'coll_end' => '</h4>',
					'list_start' => '<ul>',
					'list_end' => '</ul>',
					'item_start' => '<li>',
					'item_end' => '</li>',
					'link_default_class' => 'default',
					'item_text_start' => '',
					'item_text_end' => '',
					'item_selected_start' => '<li class="selected">',
					'item_selected_end' => '</li>',
					'link_selected_class' => 'selected',
					'link_type' => 'canonic',		// 'canonic' | 'context' (context will regenrate URL injecting/replacing a single filter)
					'item_selected_text_start' => '',
					'item_selected_text_end' => '',
					'group_start' => '<ul>',
					'group_end' => '</ul>',
					'notes_start' => '<div class="notes">',
					'notes_end' => '</div>',
					'tag_cloud_start' => '<p class="tag_cloud">',
					'tag_cloud_end' => '</p>',
				), $widget_defaults, $params, $this->param_array );


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

		echo "Widget $this->type : $this->code did not provide a display() method! ";

		return false;
	}


  /**
   * Note: a container can prevent display of titles with 'block_display_title'
   * This is useful for the lists in the headers
   * fp> I'm not sur if this param should be overridable by widgets themselves (priority problem)
   * Maybe an "auto" setting.
   *
	 * @protected
	 */
	function disp_title( $title = NULL )
	{
		if( is_null($title) )
		{
			$title = & $this->disp_params['title'];
		}

		if( $this->disp_params['block_display_title'] && !empty( $title ) )
		{
			echo $this->disp_params['block_title_start'];
			echo format_to_output( $title );
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
		// Note: we pass a widget specific prefix in order to make sure to never interfere with the mainlist
		$ItemList = & new ItemListLight( $Blog, NULL, NULL, 20, 'ItemCacheLight', $this->code.'_' );
		// Filter list:
		if( $what == 'pages' )
		{
			$ItemList->set_filters( array(
					'types' => '1000',					// Restrict to type 1000 (pages)
					'orderby' => 'title',
					'order' => 'ASC',
					'unit' => 'all',						// We want to advertise all items (not just a page or a day)
				), false );
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
   		$this->disp_title( T_('Info pages') );
		}
		else
		{
			$this->disp_title( T_('Contents') );
		}

		echo $this->disp_params['list_start'];

		while( $Item = & $ItemList->get_item() )
		{
			echo $this->disp_params['item_start'];
			$Item->title( array(
					'link_type' => 'permalink',
				) );
			echo $this->disp_params['item_end'];
		}

		echo $this->disp_params['list_end'];

		echo $this->disp_params['block_end'];
	}


  /**
	 * List of items by category
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function disp_cat_item_list( $link_type = 'linkto_url' )
	{
		global $BlogCache, $Blog;

		$linkblog = $this->disp_params[ 'linkblog_ID' ];

		if( ! $linkblog )
		{	// No linkblog blog requested for this blog
			return;
		}

		// Load the linkblog blog:
		$link_Blog = & $BlogCache->get_by_ID( $linkblog, false );

		if( empty($link_Blog) )
		{
			echo $this->disp_params['block_start'];
			echo T_('The requested Blog doesn\'t exist any more!');
  		echo $this->disp_params['block_end'];
			return;
		}


		# This is the list of categories to restrict the linkblog to (cats will be displayed recursively)
		# Example: $linkblog_cat = '4,6,7';
		$linkblog_cat = '';

		# This is the array if categories to restrict the linkblog to (non recursive)
		# Example: $linkblog_catsel = array( 4, 6, 7 );
		$linkblog_catsel = array();

		// Compile cat array stuff:
		$linkblog_cat_array = array();
		$linkblog_cat_modifier = '';
		compile_cat_array( $linkblog_cat, $linkblog_catsel, /* by ref */ $linkblog_cat_array, /* by ref */  $linkblog_cat_modifier, $linkblog );

		$limit = ( $this->disp_params[ 'linkblog_limit' ] ? $this->disp_params[ 'linkblog_limit' ] : 1000 ); // Note: 1000 will already kill the display

		$LinkblogList = & new ItemListLight( $link_Blog, NULL, NULL, $limit );

		$LinkblogList->set_filters( array(
				'cat_array' => $linkblog_cat_array,
				'cat_modifier' => $linkblog_cat_modifier,
				'orderby' => 'main_cat_ID title',
				'order' => 'ASC',
				'unit' => 'posts',
			) );

		// Run the query:
		$LinkblogList->query();

		if( ! $LinkblogList->get_num_rows() )
		{ // empty list:
			return;
		}

		echo $this->disp_params['block_start'];

 		$this->disp_title( $this->disp_params[ 'title' ] );

		echo $this->disp_params['list_start'];

    /**
		 * @var ItemLight
		 */
		while( $Item = & $LinkblogList->get_category_group() )
		{
			// Open new cat:
			echo $this->disp_params['item_start'];
			$Item->main_category();
			echo $this->disp_params['group_start'];

			while( $Item = & $LinkblogList->get_item() )
			{
				echo $this->disp_params['item_start'];

				$Item->title( array(
						'link_type' => $link_type,
					) );

				/*
				$Item->content_teaser( array(
						'before'      => '',
						'after'       => ' ',
						'disppage'    => 1,
						'stripteaser' => false,
					) );

				$Item->more_link( array(
						'before'    => '',
						'after'     => '',
						'link_text' => T_('more').' &raquo;',
					) );
				*/


				echo $this->disp_params['item_end'];
			}

			// Close cat
			echo $this->disp_params['group_end'];
			echo $this->disp_params['item_end'];
		}

		// Close the global list
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

		$this->disp_title( T_('Blogs') );

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

			if( $Blog && $l_blog_ID == $Blog->ID )
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

			if( $Blog && $l_blog_ID == $Blog->ID )
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
 * Revision 1.12  2007/12/20 22:59:34  fplanque
 * TagCloud widget prototype
 *
 * Revision 1.11  2007/12/20 10:48:51  fplanque
 * doc
 *
 * Revision 1.10  2007/12/18 10:26:58  yabs
 * adding params
 *
 * Revision 1.9  2007/11/04 01:10:57  fplanque
 * skin cleanup continued
 *
 * Revision 1.8  2007/11/03 04:56:04  fplanque
 * permalink / title links cleanup
 *
 * Revision 1.7  2007/09/24 12:08:24  yabs
 * minor bug fix
 *
 * Revision 1.6  2007/09/23 18:57:15  fplanque
 * filter handling fixes
 *
 * Revision 1.5  2007/09/17 18:03:52  blueyed
 * Fixed cases for no $Blog, e.g. with contact.php
 *
 * Revision 1.4  2007/09/04 19:48:33  fplanque
 * small fixes
 *
 * Revision 1.3  2007/06/30 20:37:50  fplanque
 * fixes
 *
 * Revision 1.2  2007/06/29 00:25:02  fplanque
 * minor
 *
 * Revision 1.1  2007/06/25 11:01:57  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.11  2007/06/23 00:12:38  fplanque
 * cleanup
 *
 * Revision 1.10  2007/06/21 23:28:18  blueyed
 * todos
 *
 * Revision 1.9  2007/06/21 00:44:36  fplanque
 * linkblog now a widget
 *
 * Revision 1.8  2007/06/20 23:12:51  fplanque
 * "Who's online" moved to a plugin
 *
 * Revision 1.7  2007/06/20 21:42:13  fplanque
 * implemented working widget/plugin params
 *
 * Revision 1.6  2007/06/20 14:25:00  fplanque
 * fixes
 *
 * Revision 1.5  2007/06/20 13:19:29  fplanque
 * Free html widget
 *
 * Revision 1.4  2007/06/20 00:48:18  fplanque
 * some real life widget settings
 *
 * Revision 1.3  2007/06/19 20:42:53  fplanque
 * basic demo of widget params handled by autoform_*
 *
 * Revision 1.2  2007/06/19 00:03:26  fplanque
 * doc / trying to make sense of automatic settings forms generation.
 *
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
 */
?>
