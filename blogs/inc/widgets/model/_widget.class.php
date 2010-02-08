<?php
/**
 * This file implements the Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
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

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

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
	var $disp_params = NULL;

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
			// Using parent:: instead of $this-> in order to fix http://forums.b2evolution.net//viewtopic.php?p=94778
			parent::set( 'type', $type );
			parent::set( 'code', $code );
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
	 * @param boolean true to set to NULL if empty value
	 * @return boolean true, if a value has been set; false if it has not changed
	 */
	function set( $parname, $parvalue, $make_null = false )
	{
		$params = $this->get_param_definitions( NULL );

		if( isset( $params[$parname] ) )
		{	// This is a widget specific param:
			$this->param_array[$parname] = $parvalue;
			// This is what'll be saved to the DB:
			return $this->set_param( 'params', 'string', serialize($this->param_array), $make_null );
		}

		switch( $parname )
		{
			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}


	/**
	 * Prepare display params
	 *
	 * @todo Document default params and default values.
	 *       This might link to a wiki page, too.
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function init_display( $params )
	{
		global $admin_url;

		if( !is_null($this->disp_params) )
		{ // Params have been initialized before...
			return;
		}

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
					'item_text' => '%s',
					'item_selected_start' => '<li class="selected">',
					'item_selected_end' => '</li>',
					'item_selected_text' => '%s',
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

		$this->init_display( $params ); // just in case it hasn't been done before

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
	 * Wraps display in a cacheable block.
	 *
	 * @todo dh> I think Widgets need to provide caching, e.g.
	 *           by returning something in cache_keys (so
	 *           ComponentWidget::get_cache_keys() should return
	 *           an empty list or false by default).
	 * fp> I don't understand what you mean.
	 *
	 * @param array MUST contain at least the basic display params
	 * @param array of extra keys to be used for cache keying
	 */
	function display_with_cache( $params, $keys = array() )
	{
		global $Blog, $Timer;

		$this->init_display( $params );

		if( ! $Blog->get_setting('cache_enabled_widgets') )
		{	// We do NOT want caching for this collection
			$this->display( $params );
		}
		else
		{	// Instantiate BlockCache:
			$Timer->resume( 'BlockCache' );
			// Extend cache keys:
			$keys += $this->get_cache_keys();

			// TODO: dh> I think disp_params (after being processed in init_display) should get considered for the cache key, too.

			$this->BlockCache = new BlockCache( 'widget', $keys );

			if( ! $this->BlockCache->check() )
			{	// Cache miss, we have to generate:
				$Timer->pause( 'BlockCache' );

				$this->display( $params );

				// Save collected cached data if needed:
				$this->BlockCache->end_collect();
			}

			$Timer->pause( 'BlockCache' );
		}
	}


	/**
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @return array of keys this widget depends on
	 */
	function get_cache_keys()
	{
		global $Blog;

		return array(
				'wi_ID'   => $this->ID,				// Have the widget settings changed ?
				'set_coll_ID' => $Blog->ID,		// Have the settings of the blog changed ? (ex: new skin)
			);
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
	 * List of collections/blogs
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function disp_coll_list( $filter = 'public' )
	{
		/**
		 * @var Blog
		 */
		global $Blog, $baseurl;

		echo $this->disp_params['block_start'];

		$this->disp_title();

		/**
		 * @var BlogCache
		 */
		$BlogCache = & get_BlogCache();

		if( $filter == 'owner' )
		{	// Load blogs of same owner
			$blog_array = $BlogCache->load_owner_blogs( $Blog->owner_user_ID, 'ID' );
		}
		else
		{	// Load all public blogs
			$blog_array = $BlogCache->load_public( 'ID' );
		}

		// 3.3? if( $this->disp_params['list_type'] == 'list' )
		// fp> TODO: init default value for $this->disp_params['list_type'] to avoid error
		{
			echo $this->disp_params['list_start'];

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
					printf( $this->disp_params['item_selected_text'], $l_Blog->dget( 'shortname', 'htmlbody' ) );
					echo $this->disp_params['item_selected_text_end'];
					echo '</a>';
					echo $this->disp_params['item_selected_end'];
				}
				else
				{
					echo $this->disp_params['item_text_start'];
					printf( $this->disp_params['item_text'], $l_Blog->dget( 'shortname', 'htmlbody' ) );
					echo $this->disp_params['item_text_end'];
					echo '</a>';
					echo $this->disp_params['item_end'];
				}
			}

			echo $this->disp_params['list_end'];
		}
		/* 3.3?
			Problems:
			-In FF3/XP with skin evoCamp, I click to drop down and it already reloads the page on the same blog.
			-Missing appropriate CSS so it displays at least half nicely in most of teh default skins
		{
			$select_options = '';
			foreach( $blog_array as $l_blog_ID )
			{	// Loop through all public blogs:
				$l_Blog = & $BlogCache->get_by_ID( $l_blog_ID );

				// Add item select list:
				$select_options .= '<option value="'.$l_blog_ID.'"';
				if( $Blog && $l_blog_ID == $Blog->ID )
				{
					$select_options .= ' selected="selected"';
				}
				$select_options .= '>'.$l_Blog->dget( 'shortname', 'formvalue' ).'</option>'."\n";
			}

			if( !empty($select_options) )
			{
				echo '<form action="'.$baseurl.'" method="get">';
				echo '<select name="blog" onchange="this.form.submit();">'.$select_options.'</select>';
				echo '<noscript><input type="submit" value="'.T_('Go').'" /></noscript></form>';
			}
		}
		*/
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


	/**
	 * Update the DB based on previously recorded changes
	 */
	function dbupdate()
	{
		global $DB;

		parent::dbupdate();

		// This widget has been modified, cached content depending on it should be invalidated:
		BlockCache::invalidate_key( 'wi_ID', $this->ID );
	}

}


/*
 * $Log$
 * Revision 1.75  2010/02/08 17:54:47  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.74  2009/12/22 08:02:11  fplanque
 * doc
 *
 * Revision 1.73  2009/12/22 03:31:10  blueyed
 * todo about cachable block handling
 *
 * Revision 1.72  2009/12/06 18:07:43  fplanque
 * Fix simplified list widgets.
 *
 * Revision 1.71  2009/12/01 04:19:25  fplanque
 * even more invalidation dimensions
 *
 * Revision 1.70  2009/12/01 03:45:37  fplanque
 * multi dimensional invalidation
 *
 * Revision 1.69  2009/11/30 23:27:13  fplanque
 * added a dimension to cache invalidation
 *
 * Revision 1.68  2009/11/30 23:16:24  fplanque
 * basic cache invalidation is working now
 *
 * Revision 1.67  2009/11/30 04:31:38  fplanque
 * BlockCache Proof Of Concept
 *
 * Revision 1.66  2009/10/03 21:00:50  tblue246
 * Bugfixes
 *
 * Revision 1.65  2009/09/26 12:00:44  tblue246
 * Minor/coding style
 *
 * Revision 1.64  2009/09/25 07:33:15  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.63  2009/08/30 17:27:03  fplanque
 * better NULL param handling all over the app
 *
 * Revision 1.62  2009/08/06 14:55:45  fplanque
 * doc
 *
 * Revision 1.61  2009/08/03 13:19:11  tblue246
 * Fixed http://forums.b2evolution.net//viewtopic.php?p=94778
 *
 * Revision 1.60  2009/07/02 21:50:13  fplanque
 * commented out unfinished code
 *
 * Revision 1.59  2009/06/18 07:36:06  yabs
 * bugfix : $type is already a param ;)
 *
 * Revision 1.58  2009/05/28 06:49:05  sam2kb
 * Blog list widget can be either a "regular list" or a "select menu"
 * See http://forums.b2evolution.net/viewtopic.php?t=18794
 *
 * Revision 1.57  2009/04/02 22:55:50  blueyed
 * ComponentWidget::disp_coll_list: add 'item_text' and 'item_selected_text' params, where %s gets replaced by theshort name. ('%s' being the default)
 *
 * Revision 1.56  2009/03/15 22:48:16  fplanque
 * refactoring... final step :)
 *
 * Revision 1.55  2009/03/15 20:35:18  fplanque
 * Universal Item List proof of concept
 *
 * Revision 1.54  2009/03/14 03:28:00  fplanque
 * tiny cleanup
 *
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
