<?php
/**
 * This file implements the Category to Snippet plugin/widget.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Category to Snippet Plugin
 *
 * This plugin displays
 */
class category_snippet_plugin extends Plugin
{
	/**
	 * Variables below MUST be overriden by plugin implementations,
	 * either in the subclass declaration or in the subclass constructor.
	 */

	var $name;
	var $code = 'evo_cat_snippet';
	var $priority = 50;
	var $version = '7.2.5';
	var $author = 'The b2evo Group';
	var $group = 'widget';
	var $subgroup = 'infoitem';
	var $widget_icon = 'sitemap';


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->name = T_('Category to Snippet');
		$this->short_desc = T_('This skin widget displays HTML snippet by Item Category.');
		$this->long_desc = $this->short_desc;
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param array Associative array of parameters.
	 *    'for_editing': true, if the settings get queried for editing;
	 *                   false, if they get queried for instantiating {@link Plugin::$UserSettings}.
	 * @return
	 */
	function get_coll_setting_definitions( & $params )
	{
		return array_merge( array(
			'snippets' => array(
				'label' => T_('Snippets'),
				'note' => T_('Please note snippets defined here may be overridden per widget by same category.'),
				'type' => 'array',
				'entries' => array(
					'cat' => array(
						'label' => T_('Category'),
						'type' => 'select',
						'options' => $this->get_categories_selector_options(),
					),
					'html_snippet' => array(
						'label' => T_('HTML snippet'),
						'type' => 'textarea',
						'defaultvalue' => '',
						'rows' => 4,
					),
				),
			),
		), parent::get_coll_setting_definitions( $params ) );
	}


	/**
	 * Get definitions for widget specific editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_widget_param_definitions( $params )
	{
		global $Blog, $admin_url;

		return array(
			'title' => array(
				'label' => T_('Block title'),
				'note' => T_('Title to display in your skin.'),
				'size' => 60,
				'defaultvalue' => '',
			),
			'snippets' => array(
				'label' => T_('Snippets'),
				'note' => sprintf( T_('Please note snippets defined here override snippets with same category defined in <a %s>current collection plugin settings</a>.'),
						'href="'.$admin_url.'?ctrl=coll_settings&tab=plugins&plugin_group=widget&blog='.$Blog->ID.'"' ),
				'type' => 'array',
				'entries' => array(
					'cat' => array(
						'label' => T_('Category'),
						'type' => 'select',
						'options' => $this->get_categories_selector_options(),
					),
					'html_snippet' => array(
						'label' => T_('HTML snippet'),
						'type' => 'textarea',
						'defaultvalue' => '',
						'rows' => 4,
					),
				),
			),
		);
	}


	/**
	 * Get categories options for selector in settings
	 *
	 * @return array
	 */
	function get_categories_selector_options()
	{
		global $Blog;

		$options = array( 0 => '---' );

		$ChapterCache = & get_ChapterCache();
		if( get_allow_cross_posting() )
		{	// Get ALL Categories from ALL Collections because cross-posting is enabled in system:
			$BlogCache = & get_BlogCache();
			$BlogCache->load_all();
			foreach( $BlogCache->cache as $cache_Blog )
			{
				$ChapterCache->clear(); // Clear categories from previois Collection:
				$options[ $cache_Blog->get( 'name' ) ] = $ChapterCache->recurse_select_options( $cache_Blog->ID );
			}
		}
		else
		{	// Get Categories of the current Collection:
			$options += $ChapterCache->recurse_select_options( $Blog->ID );
		}

		return $options;
	}


	/**
	 * Get keys for block/widget caching
	 *
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @param integer Widget ID
	 * @return array of keys this widget depends on
	 */
	function get_widget_cache_keys( $widget_ID = 0 )
	{
		global $Collection, $Blog, $Item;

		return array(
				'plugin_ID'    => $this->ID, // Have the plugin settings changed ?
				'wi_ID'        => $widget_ID, // Have the widget settings changed ?
				'set_coll_ID'  => isset( $Blog ) ? $Blog->ID : NULL, // Have the settings of the blog changed ? (ex: new skin)
				'cont_coll_ID' => isset( $Blog ) ? $Blog->ID : NULL, // Has the content of the displayed blog changed ?
				'item_ID'      => ( empty( $Item->ID ) ? 0 : $Item->ID ), // Has the Item page changed?
			);
	}


	/**
	 * Event handler: SkinTag
	 *
	 * @param array Associative array of parameters.
	 * @return boolean did we display?
	 */
	function SkinTag( & $params )
	{
		global $Blog, $Item;

		$this->init_widget_params( $params );

		if( empty( $Blog ) )
		{	// Don't display this widget when no current Collection:
			$this->display_widget_debug_message( 'Plugin widget "'.$this->name.'" is hidden because there is no Collection.' );
			return false;
		}

		if( empty( $Item ) )
		{	// Don't display this widget when no current Item:
			$this->display_widget_debug_message( 'Plugin widget "'.$this->name.'" is hidden because there is no Item.' );
			return false;
		}

		$item_cat_IDs = $Item->get( 'extra_cat_IDs' );
		if( empty( $item_cat_IDs ) )
		{	// Don't display this widget when current Item has no categories:
			$this->display_widget_debug_message( 'Plugin widget "'.$this->name.'" is hidden because Item has no categories.' );
			return false;
		}

		$cat_snippets = array();
		// 1) Use firsty settings per collection:
		$coll_snippets = $this->get_coll_setting( 'snippets', $Blog );
		if( is_array( $coll_snippets ) )
		{
			foreach( $coll_snippets as $coll_snippet )
			{
				if( in_array( $coll_snippet['cat'], $item_cat_IDs ) )
				{
					$cat_snippets[ $coll_snippet['cat'] ] = $coll_snippet['html_snippet'];
				}
			}
		}
		// 2) Override collection settings with settings per widget:
		$widget_snippets = $this->get_widget_setting( 'snippets' );
		if( is_array( $widget_snippets ) )
		{
			foreach( $widget_snippets as $widget_snippet )
			{
				if( in_array( $widget_snippet['cat'], $item_cat_IDs ) )
				{
					$cat_snippets[ $widget_snippet['cat'] ] = $widget_snippet['html_snippet'];
				}
			}
		}
		

		if( empty( $cat_snippets ) )
		{	// Don't display this widget when no snippets are defined for categories of the current Item::
			$this->display_widget_debug_message( 'Plugin widget "'.$this->name.'" is hidden because no defined snippets for categories of this Item.' );
			return false;
		}

		echo $this->widget_params['block_start'];

		$this->display_widget_title();

		echo $this->widget_params['block_body_start'];

		// Display snippets what found for categories of the current Item:
		echo implode( ' ', $cat_snippets );

		echo $this->widget_params['block_body_end'];

		echo $this->widget_params['block_end'];

		return true;
	}
}
?>