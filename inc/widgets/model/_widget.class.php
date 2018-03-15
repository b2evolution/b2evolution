<?php
/**
 * This file implements the Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
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
	 * Lazy instantiated.
	 *
	 * This gets set/used for widget plugins (those that hook into SkinTag).
	 * (false if this Widget is not handled by a Plugin)
	 * @see get_Plugin()
	 * @var Plugin
	 */
	var $Plugin;

	/**
	* @var BlockCache
	*/
	var $BlockCache;

	/**
	* @var Blog
	*/
	var $Blog = NULL;

	/**
	 * Widget icon name.
	 * Use icon name from http://fontawesome.io/icons/
	 *
	 * @var string
	 */
	var $icon = 'cube';

	/**
	 * Constructor
	 *
	 * @param object data row from db
	 */
	function __construct( $db_row = NULL, $type = 'core', $code = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_widget', 'wi_', 'wi_ID' );

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
	 * Get param prefix with is used on edit forms and submit data
	 *
	 * @return string
	 */
	function get_param_prefix()
	{
		return 'edit_widget_'.( empty( $this->ID ) ? '0' : $this->ID ).'_set_';
	}


	/**
	 * Get ref to the Plugin handling this Widget.
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
		foreach( $this->get_param_definitions( array( 'for_editing' => true, 'for_updating' => true  ) ) as $parname => $parmeta )
		{
			$parvalue = NULL;
			if( $parname == 'allow_blockcache'
					&& isset( $parmeta['disabled'] )
					&& ( $parmeta['disabled'] == 'disabled' ) )
			{ // Force checkbox "Allow caching" to unchecked when it is disallowed from widget config
				$parvalue = 0;
			}
			autoform_set_param_from_request( $parname, $parmeta, $this, 'Widget', NULL, $parvalue );
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
	 * Get a clean description to display in the widget list.
	 * @return string
	 */
	function get_desc_for_list()
	{
		$name = $this->get_name();

		if( $this->type == 'plugin' )
		{	// Plugin widget:
			$widget_Plugin = & $this->get_Plugin();
			$icon = '<span class="fa fa-'.$widget_Plugin->widget_icon.'"></span>';

			if( isset( $this->disp_params['title'] ) && ! empty( $this->disp_params['title'] ) )
			{
				return $icon.' <strong>'.$this->disp_params['title'].'</strong> ('.$name. ' - ' .T_('Plugin').')';
			}

			return $icon.' <strong>'.$name.'</strong> ('.T_('Plugin').')';
		}

		// Normal widget:
		$short_desc = $this->get_short_desc();
		$icon = '<span class="fa fa-'.$this->icon.'"></span>';

		if( $name == $short_desc || empty( $short_desc ) )
		{
			return $icon.' <strong>'.$name.'</strong>';
		}

		return $icon.' <strong>'.$short_desc.'</strong> ('.$name.')';
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
	 * Get help URL
	 *
	 * @return string|NULL URL, NULL - when core widget doesn't define the url yet
	 */
	function get_help_url()
	{
		if( $widget_Plugin = & $this->get_Plugin() )
		{ // Get url of the plugin widget
			$help_url = $widget_Plugin->get_help_url( '$widget_url' );
		}
		else
		{ // Core widget must defines this URL
			$help_url = NULL;
		}

		return $help_url;
	}


	/**
	 * Get help link
	 *
	 * @param string Icon
	 * @param boolean TRUE - to add info to display it in tooltip on mouseover
	 * @return string icon
	 */
	function get_help_link( $icon = 'help', $use_tooltip = true )
	{
		$widget_url = $this->get_help_url();

		if( empty( $widget_url ) )
		{ // Return empty string when widget URL is not defined
			return '';
		}

		$link_attrs = array( 'target' => '_blank' );

		if( $use_tooltip )
		{ // Add these data only for tooltip
			$link_attrs['class']  = 'action_icon help_plugin_icon';
			$link_attrs['rel']    = format_to_output( $this->get_desc(), 'htmlattr' );
		}

		return action_icon( '', $icon, $widget_url, NULL, NULL, NULL, $link_attrs );
	}


	/**
	 * Get definitions for editable params.
	 *
	 * @see Plugin::GetDefaultSettings()
	 *
	 * @param array Local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		$r = array();

		if( $this->type == 'plugin' )
		{
			// Make sure Plugin is loaded:
			if( $this->get_Plugin() )
			{
				$r = $this->Plugin->get_widget_param_definitions( $params );
			}
		}

		if( ! isset( $r['widget_css_class'] ) ||
		    ! isset( $r['widget_ID'] ) ||
		    ! isset( $r['allow_blockcache'] ) )
		{	// Start fieldset of advanced settings:
			$r['advanced_layout_start'] = array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Advanced'),
				);
		}

		if( ! isset( $r['widget_css_class'] ) )
		{	// Widget CSS class:
			$r['widget_css_class'] = array(
					'label' => '<span class="dimmed">'.T_( 'CSS Class' ).'</span>',
					'size' => 20,
					'note' => T_( 'Replaces $wi_class$ in your skins containers.'),
				);
		}

		if( ! isset( $r['widget_ID'] ) )
		{	// Widget ID:
			$r['widget_ID'] = array(
					'label' => '<span class="dimmed">'.T_( 'DOM ID' ).'</span>',
					'size' => 20,
					'note' => T_( 'Replaces $wi_ID$ in your skins containers.'),
				);
		}

		if( ! isset( $r['allow_blockcache'] ) )
		{	// Allow widget/block caching:
			$widget_Blog = & $this->get_Blog();
			$r['allow_blockcache'] = array(
					'label' => T_( 'Allow caching' ),
					'note' => ( $widget_Blog && $widget_Blog->get_setting( 'cache_enabled_widgets' ) ) ?
							T_('Uncheck to prevent this widget from ever being cached in the block cache. (The whole page may still be cached.) This is only needed when a widget is poorly handling caching and cache keys.') :
							T_('Block caching is disabled for this collection.'),
					'type' => 'checkbox',
					'defaultvalue' => true,
				);
		}

		if( ! isset( $r['widget_css_class'] ) ||
		    ! isset( $r['widget_ID'] ) ||
		    ! isset( $r['allow_blockcache'] ) )
		{	// End fieldset of advanced settings:
			$r['advanced_layout_end'] = array(
					'layout' => 'end_fieldset',
				);
		}

		return $r;
	}


	/**
	 * Load param array.
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
 	 * Get param value.
 	 *
 	 * @param string
 	 * @param boolean default false, set to true only if it is called from a widget::get_param_definition() function to avoid infinite loop
 	 * @return mixed
	 */
	function get_param( $parname, $check_infinite_loop = false, $group = NULL )
	{
		$this->load_param_array();

		if( strpos( $parname, '[' ) !== false )
		{	// Get value for array setting like "sample_sets[0][group_name_param_name]":
			$setting_names = explode( '[', $parname );
			if( isset( $this->param_array[ $setting_names[0] ] ) )
			{
				$setting_value = $this->param_array[ $setting_names[0] ];
				unset( $setting_names[0] );
				foreach( $setting_names as $setting_name )
				{
					$setting_name = trim( $setting_name, ']' );
					if( isset( $setting_value[ $setting_name ] ) )
					{
						$setting_value = $setting_value[ $setting_name ];
					}
					else
					{
						$setting_value = NULL;
						break;
					}
				}
				return $setting_value;
			}
		}
		elseif( isset( $this->param_array[ $parname ] ) )
		{	// Get normal(not array) setting value:
			return $this->param_array[ $parname ];
		}

		// Try default values:
		// Note we set 'infinite_loop' param to avoid calling the get_param() from the get_param_definitions() function recursively
		$params = $this->get_param_definitions( $check_infinite_loop ? array( 'infinite_loop' => true ) : NULL );

		if( $group === NULL )
		{	// Get param from simple field:
			if( isset( $params[$parname]['defaultvalue'] ) )
			{	// We have a default value:
				return $params[$parname]['defaultvalue'] ;
			}
		}
		else
		{	// Get param from group field:
			$parname = substr( $parname, strlen( $group ) );
			if( isset( $params[$group]['inputs'][$parname]['defaultvalue'] ) )
			{	// We have a default value:
				return $params[$group]['inputs'][$parname]['defaultvalue'] ;
			}
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
	function set( $parname, $parvalue, $make_null = false, $group = NULL )
	{
		$params = $this->get_param_definitions( NULL );

		if( isset( $params[$parname] ) || 
		    ( $group !== NULL && isset( $params[ $group ]['inputs'][ substr( $parname, strlen( $group ) ) ] ) ) )
		{ // This is a widget specific param:
			// Make sure param_array is loaded before set the param value
			$this->load_param_array();
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
	 * Request all required css and js files for this widget
	 */
	function request_required_files()
	{
	}


	/**
	 * Prepare display params
	 *
	 * @todo Document default params and default values.
	 * @todo fp> do NOT call this when just listing widget names in the back-office. It's overkill!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function init_display( $params )
	{
		global $admin_url, $debug;

		if( !is_null($this->disp_params) )
		{ // Params have been initialized before...
			return;
		}

		// Generate widget defaults (editable params) array:
		$widget_defaults = array();
		$defs = $this->get_param_definitions( array() );
		foreach( $defs as $parname => $parmeta )
		{
			if( isset( $parmeta['type'] ) && $parmeta['type'] == 'checklist' )
			{
				$widget_defaults[ $parname ] = array();
				foreach( $parmeta['options'] as $parmeta_option )
				{
					$widget_defaults[ $parname ][ $parmeta_option[0] ] = $parmeta_option[2];
				}
			}
			else
			{
				$widget_defaults[ $parname ] = ( isset( $parmeta['defaultvalue'] ) ) ? $parmeta['defaultvalue'] : NULL;
			}
		}

		// Load DB configuration:
		$this->load_param_array();

		// DEFAULT CONTAINER PARAMS:
		// Merge basic defaults < widget defaults (editable params) < container params < DB params
		// note: when called with skin_widget it falls back to basic defaults < widget defaults < calltime params < array()
		$params = array_merge( array(
					'widget_context' => 'general',		// general | item
					'block_start' => '<div class="evo_widget widget $wi_class$">',
					'block_end' => '</div>',
					'block_display_title' => true,
					'block_title_start' => '<h3>',
					'block_title_end' => '</h3>',
					'block_body_start' => '',
					'block_body_end' => '',
					'collist_start' => '',
					'collist_end' => '',
					'coll_start' => '<h4>',
					'coll_end' => '</h4>',
					'list_start' => '<ul>',
					'list_end' => '</ul>',
					'item_start' => '<li>',
					'item_end' => '</li>',
						'link_default_class' => 'default',
						'link_selected_class' => 'selected',
						'item_text_start' => '',
						'item_text_end' => '',
						'item_text' => '%s',
					'item_selected_start' => '<li class="selected">',
					'item_selected_end' => '</li>',
					'item_selected_text' => '%s',

					// Automatically detect whether we are displaying menu links as list elements or as standalone buttons:
					'inlist' => 'auto',		// may alose be true or false
					// Button styles used for Menu Links / Buttons widgets:
					'button_default_class' => 'btn btn-default',
					'button_selected_class' => 'btn btn-default active',

					'grid_start' => '<table cellspacing="1" class="widget_grid">',
						'grid_colstart' => '<tr>',
							'grid_cellstart' => '<td>',
							'grid_cellend' => '</td>',
						'grid_colend' => '</tr>',
					'grid_end' => '</table>',
					'grid_nb_cols' => 2,
					'flow_start' => '<div class="widget_flow_blocks">',
						'flow_block_start' => '<div>',
						'flow_block_end' => '</div>',
					'flow_end' => '</div>',
					'rwd_start' => '<div class="widget_rwd_blocks row">',
						'rwd_block_start' => '<div class="$wi_rwd_block_class$"><div class="widget_rwd_content clearfix">',
						'rwd_block_end' => '</div></div>',
					'rwd_end' => '</div>',
					'thumb_size' => 'crop-80x80',
					'link_type' => 'canonic',		// 'canonic' | 'context' (context will regenrate URL injecting/replacing a single filter)
					'item_selected_text_start' => '',
					'item_selected_text_end' => '',
					'group_start' => '<ul>',
					'group_end' => '</ul>',
					'group_item_start' => '<li>',
					'group_item_end' => '</li>',
					'notes_start' => '<div class="notes">',
					'notes_end' => '</div>',
					'tag_cloud_start' => '<p class="tag_cloud">',
					'tag_cloud_end' => '</p>',
					'limit' => 100,
				), $widget_defaults, $params, $this->param_array );


		// Customize params to the current widget:

		// Add additional css classes if required:
		$widget_css_class = 'widget_'.$this->type.'_'.$this->code.( empty( $params[ 'widget_css_class' ] ) ? '' : ' '.$params[ 'widget_css_class' ] );

		// Set additional css class depending on layout:
		$layout = isset( $params['layout'] ) ? $params['layout'] : ( isset( $params['thumb_layout'] ) ? $params['thumb_layout'] : NULL );
		switch( $layout )
		{
			case 'rwd':
				$widget_css_class .= ' evo_layout_rwd';
				break;
			case 'flow':
				$widget_css_class .= ' evo_layout_flow';
				break;
			case 'list':
				$widget_css_class .= ' evo_layout_list';
				break;
			case 'grid':
				$widget_css_class .= ' evo_layout_grid';
				break;
		}

		// Add custom id if required, default to generic id for validation purposes:
		$widget_ID = ( !empty($params[ 'widget_ID' ]) ? $params[ 'widget_ID' ] : 'widget_'.$this->type.'_'.$this->code.'_'.$this->ID );

		// Replace the values:
		$this->disp_params = str_replace( array( '$wi_ID$', '$wi_class$' ), array( $widget_ID, $widget_css_class ), $params );
	}


	/**
	 * Convert old display params to new name.
	 *
 	 * Use this function if some params were renamed.
	 * This function will look for the old params and convert them if no new param is present
	 */
	function convert_legacy_param( $old_name, $new_name )
	{
		//pre_dump( $this->disp_params );
		if( isset($this->disp_params[$old_name]) && !isset($this->disp_params[$new_name]) )
		{	// We have old param but NOT new param, duplicate old to new:
			$this->disp_params[$new_name] = $this->disp_params[$old_name];
		}
	}


	/**
	 * Display the widget!
	 *
	 * Should be overriden by core widgets
	 *
	 * @todo fp> handle custom params for each widget
	 *
	 * @param array MUST contain at least the basic display params
	 * @return bool true if the widget displayed something (other than a debug message)
	 */
	function display( $params )
	{
		global $Collection, $Blog;
		global $Plugins;
		global $rsc_url;

		// prepare for display:
		$this->init_display( $params );

		switch( $this->type )
		{
			case 'plugin':
				// Set widget ID param to make it available in plugin function SkinTag():
				$this->disp_params['wi_ID'] = $this->ID;
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
	 * @param array MUST contain at least the basic display params
	 * @param array of extra keys to be used for cache keying
	 */
	function display_with_cache( $params, $keys = array() )
	{
		global $Collection, $Blog, $Timer, $debug, $admin_url, $Session, $current_User;

		$this->init_display( $params );

		// Display the debug conatainers when $debug = 2 OR when it is turned on from evo menu under "Blog" -> "Show/Hide containers"
		$display_containers = ( $debug == 2 ) || ( is_logged_in() && $Session->get( 'display_containers_'.$Blog->ID ) );

		if( ! $Blog->get_setting('cache_enabled_widgets')
		    || ! $this->disp_params['allow_blockcache']
		    || $this->get_cache_status() == 'disallowed' )
		{ // NO CACHING - We do NOT want caching for this collection or for this specific widget:

			if( $display_containers )
			{ // DEBUG:
				echo '<div class="dev-blocks dev-blocks--widget"><div class="dev-blocks-name" title="'.
							( $Blog->get_setting('cache_enabled_widgets') ? 'Widget params have BlockCache turned off' : 'Collection params have BlockCache turned off' ).'">';
				if( is_logged_in() && $current_User->check_perm( 'blog_properties', 'edit', false, $Blog->ID ) )
				{	// Display a link to edit this widget only if current user has a permission:
					echo '<span class="dev-blocks-action"><a href="'.$admin_url.'?ctrl=widgets&amp;action=edit&amp;wi_ID='.$this->ID.'">Edit</a></span>';
				}
				echo 'Widget: <b>'.$this->get_name().'</b> - Cache OFF <i class="fa fa-info">?</i></div>'."\n";
			}

			$this->display( $params );

			if( $display_containers )
			{ // DEBUG:
				echo "</div>\n";
			}
		}
		else
		{ // Instantiate BlockCache:
			$Timer->resume( 'BlockCache' );
			// Extend cache keys:
			$keys += $this->get_cache_keys();

			$this->BlockCache = new BlockCache( 'widget', $keys );

			$content = $this->BlockCache->check();

			$Timer->pause( 'BlockCache' );

			if( $content !== false )
			{ // cache hit, let's display:

				if( $display_containers )
				{ // DEBUG:
					echo '<div class="dev-blocks dev-blocks--widget dev-blocks--widget--incache"><div class="dev-blocks-name" title="Cache key = '.$this->BlockCache->serialized_keys.'">';
					if( is_logged_in() && $current_User->check_perm( 'blog_properties', 'edit', false, $Blog->ID ) )
					{	// Display a link to edit this widget only if current user has a permission:
						echo '<span class="dev-blocks-action"><a href="'.$admin_url.'?ctrl=widgets&amp;action=edit&amp;wi_ID='.$this->ID.'">Edit</a></span>';
					}
					echo 'Widget: <b>'.$this->get_name().'</b> - FROM cache <i class="fa fa-info">?</i></div>'."\n";
				}

				echo $content;

				if( $display_containers )
				{ // DEBUG:
					echo "</div>\n";
				}

			}
			else
			{ // Cache miss, we have to generate:

				if( $display_containers )
				{ // DEBUG:
					echo '<div class="dev-blocks dev-blocks--widget dev-blocks--widget--notincache"><div class="dev-blocks-name" title="Cache key = '.$this->BlockCache->serialized_keys.'">';
					if( is_logged_in() && $current_User->check_perm( 'blog_properties', 'edit', false, $Blog->ID ) )
					{	// Display a link to edit this widget only if current user has a permission:
						echo '<span class="dev-blocks-action"><a href="'.$admin_url.'?ctrl=widgets&amp;action=edit&amp;wi_ID='.$this->ID.'">Edit</a></span>';
					}
					echo 'Widget: <b>'.$this->get_name().'</b> - NOT in cache <i class="fa fa-info">?</i></div>'."\n";
				}

				$this->BlockCache->start_collect();

				$this->display( $params );

				// Save collected cached data if needed:
				$this->BlockCache->end_collect();

				if( $display_containers )
				{ // DEBUG:
					echo "</div>\n";
				}

			}
		}
	}


	/**
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @return array of keys this widget depends on
	 */
	function get_cache_keys()
	{
		global $Collection, $Blog;

		if( $this->type == 'plugin' && $this->get_Plugin() )
		{	// Get widget cache keys from plugin:
			return $this->Plugin->get_widget_cache_keys( $this->ID );
		}

		return array(
				'wi_ID'       => $this->ID, // Have the widget settings changed ?
				'set_coll_ID' => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
			);
	}


	/**
	 * Get cache status
	 *
	 * @param boolean TRUE to check if blog allows a caching for widgets
	 * @return string 'enabled', 'disabled', 'disallowed', 'denied'
	 */
	function get_cache_status( $check_blog_restriction  = false )
	{
		$default_widget_params = $this->get_param_definitions( array() );
		if( ! empty( $default_widget_params )
		    && isset( $default_widget_params['allow_blockcache'] )
		    && isset( $default_widget_params['allow_blockcache']['disabled'] )
		    && ( $default_widget_params['allow_blockcache']['disabled'] == 'disabled' ) )
		{ // Widget cache is NOT allowed by widget config
			return 'disallowed';
		}
		else
		{ // Check current cache status if it is allowed
			if( $check_blog_restriction )
			{ // Check blog restriction for widget caching
				$widget_Blog = & $this->get_Blog();
				if( $widget_Blog && ! $widget_Blog->get_setting( 'cache_enabled_widgets' ) )
				{	// Widget/block cache is not allowed by collection setting:
					return 'denied';
				}
			}

			if( $this->get_param( 'allow_blockcache' ) )
			{ // Enabled
				return 'enabled';
			}
			else
			{ // Disabled
				return 'disabled';
			}
		}
	}


	/**
	 * Note: a container can prevent display of titles with 'block_display_title'
	 * This is useful for the lists in the headers
	 * fp> I'm not sure if this param should be overridable by widgets themselves (priority problem)
	 * Maybe an "auto" setting.
	 *
	 * @access protected
	 */
	function disp_title( $title = NULL, $display = true )
	{
		if( is_null($title) )
		{
			$title = & $this->disp_params['title'];
		}

		if( $this->disp_params['block_display_title'] && !empty( $title ) )
		{
			$r = $this->disp_params['block_title_start'];
			$r .= format_to_output( $title );
			$r .= $this->disp_params['block_title_end'];

			if( $display ) echo $r;

			return $r;
		}
	}


	/**
	 * List of collections/blogs
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function disp_coll_list( $filter = 'public', $order_by = 'ID', $order_dir = 'ASC' )
	{
		/**
		 * @var Blog
		 */
		global $Collection, $Blog, $baseurl;

		echo $this->disp_params['block_start'];

		$this->disp_title();

		/**
		 * @var BlogCache
		 */
		$BlogCache = & get_BlogCache();

		if( $filter == 'owner' )
		{	// Load blogs of same owner
			$blog_array = $BlogCache->load_owner_blogs( $Blog->owner_user_ID, $order_by, $order_dir );
		}
		else
		{	// Load all public blogs
			$blog_array = $BlogCache->load_public( $order_by, $order_dir );
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

		// BLOCK CACHE INVALIDATION:
		// This widget has been modified, cached content depending on it should be invalidated:
		BlockCache::invalidate_key( 'wi_ID', $this->ID );
	}


	/**
	 * Get Blog
	 *
	 * @return object Blog
	 */
	function & get_Blog()
	{
		if( $this->Blog === NULL )
		{ // Get blog only first time
			$BlogCache = & get_BlogCache();
			$this->Blog = & $BlogCache->get_by_ID( $this->coll_ID, false, false );
		}

		return $this->Blog;
	}


	/**
	 * Get current layout
	 *
	 * @return string|NULL Widget layout | NULL - if widget has no layout setting
	 */
	function get_layout()
	{
		if( isset( $this->disp_params['layout'] ) )
		{
			return $this->disp_params['layout'];
		}

		if( isset( $this->disp_params['thumb_layout'] ) )
		{
			return $this->disp_params['thumb_layout'];
		}

		return NULL;
	}


	/**
	 * Get start of layout
	 *
	 * @return string
	 */
	function get_layout_start()
	{
		switch( $this->get_layout() )
		{
			case 'grid':
				// Grid / Table layout:
				return $this->disp_params['grid_start'];

			case 'flow':
				// Flow block layout:
				return $this->disp_params['flow_start'];

			case 'rwd':
				// RWD block layout:
				return $this->disp_params['rwd_start'];

			default:
				// List layout:
				return $this->disp_params['list_start'];
		}
	}


	/**
	 * Get end of layout
	 *
	 * @param integer Cell index (used for grid/table layout)
	 * @return string
	 */
	function get_layout_end( $cell_index = 0 )
	{
		switch( $this->get_layout() )
		{
			case 'grid':
				// Grid / Table layout:
				$r = '';
				$nb_cols = isset( $this->disp_params['grid_nb_cols'] ) ? $this->disp_params['grid_nb_cols'] : 1;
				if( $cell_index && ( $cell_index % $nb_cols != 0 ) )
				{
					$r .= $this->disp_params['grid_colend'];
				}
				$r .= $this->disp_params['grid_end'];
				return $r;

			case 'flow':
				// Flow block layout:
				return $this->disp_params['flow_end'];

			case 'rwd':
				// RWD block layout:
				return $this->disp_params['rwd_end'];

			default:
				// List layout:
				return $this->disp_params['list_end'];
		}
	}


	/**
	 * Get item start of layout
	 *
	 * @param integer Cell index (used for grid/table layout)
	 * @param boolean TRUE if current item/cell is selected
	 * @param string Prefix for param
	 * @return string
	 */
	function get_layout_item_start( $cell_index = 0, $is_selected = false, $disp_param_prefix = '' )
	{
		switch( $this->get_layout() )
		{
			case 'grid':
				// Grid / Table layout:
				$r = '';
				$nb_cols = isset( $this->disp_params['grid_nb_cols'] ) ? $this->disp_params['grid_nb_cols'] : 1;
				if( $cell_index % $nb_cols == 0 )
				{
					$r .= $this->disp_params['grid_colstart'];
				}
				$r .= $this->disp_params['grid_cellstart'];
				return $r;

			case 'flow':
				// Flow block layout:
				return $this->disp_params['flow_block_start'];

			case 'rwd':
				// RWD block layout:
				$r = $this->disp_params['rwd_block_start'];
				if( isset( $this->disp_params['rwd_block_class'] ) )
				{	// Replace css class of RWD block with value from widget setting:
					$r = str_replace( '$wi_rwd_block_class$', $this->disp_params['rwd_block_class'], $r );
				}
				return $r;

			default:
				// List layout:
				if( $is_selected )
				{
					return $this->disp_params[$disp_param_prefix.'item_selected_start'];
				}
				else
				{
					return $this->disp_params[$disp_param_prefix.'item_start'];
				}
		}
	}


	/**
	 * Get item end of layout
	 *
	 * @param integer Cell index (used for grid/table layout)
	 * @param boolean TRUE if current item/cell is selected
	 * @param string Prefix for param
	 * @return string
	 */
	function get_layout_item_end( $cell_index = 0, $is_selected = false, $disp_param_prefix = '' )
	{
		switch( $this->get_layout() )
		{
			case 'grid':
				// Grid / Table layout:
				$r = $this->disp_params['grid_cellend'];
				$nb_cols = isset( $this->disp_params['grid_nb_cols'] ) ? $this->disp_params['grid_nb_cols'] : 1;
				if( $cell_index % $nb_cols == 0 )
				{
					$r .= $this->disp_params['grid_colend'];
				}
				return $r;

			case 'flow':
				// Flow block layout:
				return $this->disp_params['flow_block_end'];

			case 'rwd':
				// RWD block layout:
				return $this->disp_params['rwd_block_end'];

			default:
				// List layout:
				if( $is_selected )
				{
					return $this->disp_params[$disp_param_prefix.'item_selected_end'];
				}
				else
				{
					return $this->disp_params[$disp_param_prefix.'item_end'];
				}
		}
	}


	/**
	 * Get the list of validated renderers for this Widget. This includes stealth plugins etc.
	 *
	 * @return array List of validated renderer codes
	 */
	function get_renderers_validated()
	{
		if( ! isset( $this->renderers_validated ) )
		{
			global $Plugins;

			$widget_Blog = & $this->get_Blog();

			// Convert active renderers options for plugin functions below:
			$widget_renderers = array_keys( $this->disp_params['renderers'] );

			$this->renderers_validated = $Plugins->validate_renderer_list( $widget_renderers, array(
					'Blog'         => & $widget_Blog,
					'setting_name' => 'coll_apply_rendering'
				) );
		}

		return $this->renderers_validated;
	}


	/**
	 * Get content which is rendered with plugins
	 *
	 * @param string Source content
	 * @return string Rendered content
	 */
	function get_rendered_content( $content )
	{
		if( ! isset( $this->disp_params['renderers'] ) )
		{	// This widget has no render settings, Return original content:
			return $content;
		}

		global $Plugins;

		$widget_Blog = & $this->get_Blog();
		$widget_renderers = $this->get_renderers_validated();

		// Do some optional filtering on the content
		// Typically stuff that will help the content to validate
		// Useful for code display.
		// Will probably be used for validation also.
		$Plugins_admin = & get_Plugins_admin();
		$params = array(
				'object_type' => 'Widget',
				'object'      => & $this,
				'object_Blog' => & $widget_Blog
			);
		$fake_title = '';
		$Plugins_admin->filter_contents( $fake_title /* by ref */, $content /* by ref */, $widget_renderers, $params /* by ref */ );

		// Render block content with selected plugins:
		$Plugins->render( $content, $widget_renderers, 'htmlbody', array( 'Blog' => & $widget_Blog ), 'Render' );
		$Plugins->render( $content, $widget_renderers, 'htmlbody', array( 'Blog' => & $widget_Blog ), 'Display' );

		return $content;
	}
}
?>