<?php
/**
 * This file implements the Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
	/**
	 * @var string Type of the plugin ("core" or "plugin")
	 */
	var $type;
	var $code;
	var $params;

	/**
	 * Indicates whether the widget is enabled.
	 *
	 * @var boolean
	 */
	var $enabled;

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
		{	// We are loading an object:
			$this->ID       = $db_row->wi_ID;
			$this->coll_ID  = $db_row->wi_coll_ID;
			$this->sco_name = $db_row->wi_sco_name;
			$this->type     = $db_row->wi_type;
			$this->code     = $db_row->wi_code;
			$this->params   = $db_row->wi_params;
			$this->order    = $db_row->wi_order;
			$this->enabled  = $db_row->wi_enabled;
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
	 * Load params
	 */
	function load_from_Request()
	{
		load_funcs('plugins/_plugin.funcs.php');

		// Loop through all widget params:
		foreach( $this->get_param_definitions( array('for_editing'=>true) ) as $parname => $parmeta )
		{
			autoform_set_param_from_request( $parname, $parmeta, $this, 'Widget' );
		}
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
	 * Get a very short desc. Used in the widget list.
	 *
	 * MAY be overriden by core widgets. Example: menu link widget.
	 */
	function get_short_desc()
	{
		return $this->get_name();
	}


	/**
	 * Get a clean description to display in the widget list
	 */
	function get_desc_for_list()
	{
		$name = $this->get_name();

		if( $this->type == 'plugin' )
		{
			return '<strong>'.$name.'</strong> ('.T_('Plugin').')';
		}

		$short_desc = $this->get_short_desc();

		if( $name == $short_desc || empty($short_desc) )
		{
			return '<strong>'.$name.'</strong>';
		}

		return '<strong>'.$short_desc.'</strong> ('.$name.')';
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
		$r = array(
				'widget_css_class' => array(
					'label' => '<span class="dimmed">'.T_( 'CSS Class' ).'</span>',
					'size' => 20,
					'note' => T_( 'Replaces $wi_class$ in your skins containers.'),
				),
				'widget_ID' => array(
					'label' => '<span class="dimmed">'.T_( 'DOM ID' ).'</span>',
					'size' => 20,
					'note' => T_( 'Replaces $wi_ID$ in your skins containers.'),
				),
			);

		if( $this->type == 'plugin' )
		{
			// Make sure Plugin is loaded:
			if( $this->get_Plugin() )
			{
				$r = array_merge( $r, $this->Plugin->get_widget_param_definitions( $params ) );
			}
		}
		return $r;
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
		{	// This is a widget specific param:
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
	 *
	 * @todo Document default params and default values.
	 *       This might link to a wiki page, too.
	 *
	 * @param array
	 */
	function init_display( $params )
	{
		global $admin_url;

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
					'grid_start' => '<table cellspacing="1" class="widget_grid">',
					'grid_end' => '</table>',
					'grid_nb_cols' => 2,
					'grid_colstart' => '<tr>',
					'grid_colend' => '</tr>',
					'grid_cellstart' => '<td>',
					'grid_cellend' => '</td>',
					'thumb_size' => 'crop-80x80',
					// 'thumb_size' => 'fit-160x120',
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
					'limit' => 100,
				), $widget_defaults, $params, $this->param_array );

		if( false )
		{	// DEBUG:
			$params['block_start'] = '<div class="debug_widget"><div class="debug_widget_name"><span class="debug_container_action"><a href="'
						.$admin_url.'?ctrl=widgets&amp;action=edit&amp;wi_ID='.$this->ID.'">Edit</a></span>'.$this->get_name().'</div><div class="$wi_class$">';
			$params['block_end'] = '</div></div>';
		}

		// Customize params to the current widget:
		// add additional css classes if required
		$widget_css_class = 'widget_'.$this->type.'_'.$this->code.( empty( $params[ 'widget_css_class' ] ) ? '' : ' '.$params[ 'widget_css_class' ] );
		// add custom id if required, default to generic id for validation purposes
		$widget_ID = ( !empty($params[ 'widget_ID' ]) ? $params[ 'widget_ID' ] : 'widget_'.$this->type.'_'.$this->code.'_'.$this->ID );
		// replace the values
		$this->disp_params = str_replace( array( '$wi_ID$', '$wi_class$' ), array( $widget_ID, $widget_css_class ), $params );
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
				// Plugin failed (happens when a plugin has been disabled for example):
				return false;
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
	 * @param string 'pages' or 'posts'
	 */
	function disp_item_list( $what )
	{
		global $Blog;
		global $timestamp_min, $timestamp_max;

		$blogCache = get_Cache( 'BlogCache' );
		// TODO: dh> does it make sense to die in $blogCache, in case the blog does not exist anymore?
		$listBlog = ( $this->disp_params[ 'blog_ID' ] ? $blogCache->get_by_ID( $this->disp_params[ 'blog_ID' ] ) : $Blog );

		// Create ItemList
		// Note: we pass a widget specific prefix in order to make sure to never interfere with the mainlist
		$limit = $this->disp_params[ 'limit' ];

		if( $this->disp_params['disp_teaser'] )
		{ // We want to show some of the post content, we need to load more info: use ItemList2
			$ItemList = & new ItemList2( $listBlog, $timestamp_min, $timestamp_max, $limit, 'ItemCache', $this->code.'_' );
		}
		else
		{ // no excerpts, use ItemListLight
			$ItemList = & new ItemListLight( $listBlog, $timestamp_min, $timestamp_max, $limit, 'ItemCacheLight', $this->code.'_' );
		}

		// Filter list:
		if( $what == 'pages' )
		{
			$ItemList->set_filters( array(
					'types' => '1000',					// Restrict to type 1000 (pages)
					'orderby' => $this->disp_params[ 'order_by' ],
					'order' => $this->disp_params[ 'order_dir' ],
					'unit' => 'posts',
				), false );
		}
		else
		{	// post list
			$ItemList->set_filters( array(
					'orderby' => $this->disp_params[ 'order_by' ],
					'order' => $this->disp_params[ 'order_dir' ],
					'unit' => 'posts',						// We want to advertise all items (not just a page or a day)
				) );
		}
		// Run the query:
		$ItemList->query();

		if( ! $ItemList->result_num_rows )
		{	// Nothing to display:
			return;
		}

		echo $this->disp_params['block_start'];

		$title = sprintf( ( $this->disp_params[ 'title_link' ] ? '<a href="'.$listBlog->gen_blogurl().'" rel="nofollow">%s</a>' : '%s' ), $this->disp_params[ 'title' ] );

		$this->disp_title( $title );

		echo $this->disp_params['list_start'];

		while( $Item = & $ItemList->get_item() )
		{
			echo $this->disp_params['item_start'];

			// Display contents of the Item depending on widget params:
			$this->disp_contents( $Item );

			echo $this->disp_params['item_end'];
		}

		echo $this->disp_params['list_end'];

		echo $this->disp_params['block_end'];
	}


	/**
	 * List of items by category
	 */
	function disp_cat_item_list()
	{
		global $BlogCache, $Blog;
		global $timestamp_min, $timestamp_max;
		$link_Blog = null;

		// if we don't have a linkblog_ID: this is the linkroll widget
		if ( array_key_exists( 'linkblog_ID' , $this->disp_params ) )
		{
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

		$limit = $this->disp_params[ 'limit' ];

		if ( array_key_exists( 'linkblog_ID' , $this->disp_params ) )
		{ // fp> document when this case happens vs not.
			// we have pick a linkblog
			compile_cat_array( $linkblog_cat, $linkblog_catsel, /* by ref */ $linkblog_cat_array, /* by ref */  $linkblog_cat_modifier, $linkblog );
		}
		else
		{ // we are using a linkroll (type=3000)
			$link_Blog = $Blog;
			compile_cat_array( $linkblog_cat, $linkblog_catsel, /* by ref */ $linkblog_cat_array, /* by ref */  $linkblog_cat_modifier, $Blog->ID );
		}

		if( $this->disp_params['disp_teaser'] )
		{ // We want to show some of the post content, we need to load more info: use ItemList2
			$LinkblogList = & new ItemList2( $link_Blog, $timestamp_min, $timestamp_max, $limit, 'ItemCache', $this->code.'_' );
		}
		else
		{ // no excerpts, use ItemListLight
			$LinkblogList = & new ItemListLight( $link_Blog, $timestamp_min, $timestamp_max, $limit, 'ItemCacheLight', $this->code.'_' );
		}

		$filters = array(
				'cat_array' => $linkblog_cat_array,
				'cat_modifier' => $linkblog_cat_modifier,
				'orderby' => 'main_cat_ID '.$this->disp_params[ 'order_by' ],
				'order' => $this->disp_params[ 'order_dir' ],
				'unit' => 'posts',
			);

		if( ! array_key_exists( 'linkblog_ID' , $this->disp_params ) )
		{ // Filters for linkroll
			$filters['types'] = '3000';
		}
		$LinkblogList->set_filters( $filters, false ); // we don't want to memorize these params

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

				// Display contents of the Item depending on widget params:
				$this->disp_contents( $Item );

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
	 * Support function for above
	 *
	 * @param Item
	 */
	function disp_contents( & $Item )
	{
		$Item->title( array(
				'link_type' => $this->disp_params['item_title_link_type'],
			) );

		if( $this->disp_params[ 'disp_excerpt' ] )
		{
			echo '<p>'.$Item->dget( 'excerpt', 'htmlbody' ).'</p>'; // Excerpts are plain text -- no html (at least for now)
		}

		if( $this->disp_params['disp_teaser'] )
		{ // we want to show some or all of the post content
			$content = $Item->get_content_teaser( 1, false, 'htmlbody' );

			if( $words = $this->disp_params['disp_teaser_maxwords'] )
			{ // limit number of words
				$content = strmaxwords( $content, $words, array(
						'continued_link' => $Item->get_permanent_url(),
						'continued_text' => '&hellip;',
					 ) );
			}
			echo ' <span class="excerpt">'.$content.'</span>';

			/* fp> does that really make sense?
				we're no longer in a linkblog/linkroll use case here, are we?
			$Item->more_link( array(
					'before'    => '',
					'after'     => '',
					'link_text' => T_('more').' &raquo;',
				) );
				*/
		}
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

		$this->disp_title();

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

		if( $this->ID != 0 )
		{
			debug_die( 'Existing object cannot be inserted!' );
		}

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
 * Revision 1.53  2009/03/14 03:02:56  fplanque
 * Moving towards an universal item list widget, step 1
 *
 * Revision 1.52  2009/03/13 02:32:07  fplanque
 * Cleaned up widgets.
 * Removed stupid widget_name param.
 *
 * Revision 1.51  2009/03/13 00:54:38  fplanque
 * calling it "sidebar links"
 *
 * Revision 1.50  2009/03/08 23:57:46  fplanque
 * 2009
 *
 * Revision 1.49  2009/03/04 01:19:41  fplanque
 * doc
 *
 * Revision 1.48  2009/02/25 17:18:03  waltercruz
 * Linkroll stuff, take #2
 *
 * Revision 1.47  2009/02/23 08:14:16  yabs
 * Added check for excerpts
 *
 * Revision 1.46  2009/02/22 23:40:09  fplanque
 * dirty links widget :/
 *
 * Revision 1.45  2009/02/22 14:42:03  waltercruz
 * A basic implementation that merges disp_cat_item_list2(links) and disp_cat_item_list(linkblog). Will delete disp_cat_item_list2 as soon fplanque says that the merge it's ok
 *
 * Revision 1.44  2009/02/22 14:15:48  waltercruz
 * updating docs
 *
 * Revision 1.43  2009/02/21 22:22:23  fplanque
 * eeeeeeek!
 *
 * Revision 1.42  2009/02/07 11:09:00  yabs
 * extra settings for linkblog
 *
 * Revision 1.41  2009/02/05 21:33:34  tblue246
 * Allow the user to enable/disable widgets.
 * Todo:
 * 	* Fix CSS for the widget state bullet @ JS widget UI.
 * 	* Maybe find a better solution than modifying get_Cache() to get only enabled widgets... :/
 * 	* Buffer JS requests when toggling the state of a widget??
 *
 * Revision 1.40  2009/01/24 00:43:25  waltercruz
 * bugfix
 *
 * Revision 1.39  2009/01/24 00:29:27  waltercruz
 * Implementing links in the blog itself, not in a linkblog, first attempt
 *
 * Revision 1.38  2008/09/24 08:44:12  fplanque
 * Fixed and normalized order params for widgets (Comments not done yet)
 *
 * Revision 1.37  2008/09/23 09:04:33  fplanque
 * moved media index to a widget
 *
 * Revision 1.36  2008/06/30 20:46:05  blueyed
 * Fix indent
 *
 * Revision 1.35  2008/04/24 02:01:04  fplanque
 * experimental
 *
 * Revision 1.34  2008/02/08 22:24:46  fplanque
 * bugfixes
 *
 * Revision 1.33  2008/01/21 09:35:36  fplanque
 * (c) 2008
 *
 * Revision 1.32  2008/01/19 14:17:27  yabs
 * bugfix : http://forums.b2evolution.net/viewtopic.php?t=13868
 *
 * Revision 1.31  2008/01/12 18:21:50  blueyed
 *  - use $timestamp_min, $timestamp_max for ItemListLight instances (fixes displaying of posts from the future in coll_post_list widget
 * - typo, todo, fix indent
 */
?>
