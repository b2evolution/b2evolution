<?php
/**
 * This file implements the Common links Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );

/**
 * ComponentWidget: Common navigation links.
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @todo dh> why are "STRONG" tags hardcoded here? can this get dropped/removed? should the style
 *           get adjusted to use font-weight:bold then?
 * fp> yes but make sure to put the font-weight in a place where it applies to all (existing) skins by default; e-g blog_base.css
 *
 * @package evocore
 */
class coll_common_links_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'coll_common_links' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'common-navigation-links-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Common Navigation Links');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output($this->disp_params['title']);
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display these links: Recently, Archives, Categories, Latest Comments');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		$r = array_merge( array(
				'title' => array(
					'label' => T_('Block title'),
					'note' => T_( 'Title to display in your skin.' ),
					'size' => 40,
					'defaultvalue' => '',
				),
				'show_home' => array(
					'type' => 'checkbox',
					'label' => T_('Show "Home"'),
					'note' => T_('Go to the blog\'s home.'),
					'defaultvalue' => 1,
				),
				'show_recently' => array(
					'type' => 'checkbox',
					'label' => T_('Show "Recently"'),
					'note' => T_('Go to the most recent posts (depends on default sort order).'),
					'defaultvalue' => 1,
				),
				'show_search' => array(
					'type' => 'checkbox',
					'label' => T_('Show "Search"'),
					'note' => T_('Go to the search page.'),
					'defaultvalue' => 0,
				),
				'show_postidx' => array(
					'type' => 'checkbox',
					'label' => T_('Show "Post index"'),
					'note' => T_('Go to the post index.'),
					'defaultvalue' => 0,
				),
				'show_archives' => array(
					'type' => 'checkbox',
					'label' => T_('Show "Archives"'),
					'note' => T_('Go to the monthly/weekly/daily archive list.'),
					'defaultvalue' => 1,
				),
				'show_categories' => array(
					'type' => 'checkbox',
					'label' => T_('Show "Categories"'),
					'note' => T_('Go to the category tree.'),
					'defaultvalue' => 1,
				),
				'show_mediaidx' => array(
					'type' => 'checkbox',
					'label' => T_('Show "Photo index"'),
					'note' => T_('Go to the photo index / contact sheet.'),
					'defaultvalue' => 0,
				),
				'show_latestcomments' => array(
					'type' => 'checkbox',
					'label' => T_('Show "Latest comments"'),
					'note' => T_('Go to the latest comments.'),
					'defaultvalue' => 1,
				),
				'show_owneruserinfo' => array(
					'type' => 'checkbox',
					'label' => T_('Show "Owner details"'),
					'note' => T_('Go to user info about the blog owner.'),
					'defaultvalue' => 0,
				),
				'show_ownercontact' => array(
					'type' => 'checkbox',
					'label' => T_('Show "Contact"'),
					'note' => T_('Go to message form to contact the blog owner.'),
					'defaultvalue' => 0,
				),
				'show_sitemap' => array(
					'type' => 'checkbox',
					'label' => T_('Show "Site map"'),
					'note' => T_('Go to site map (HTML version).'),
					'defaultvalue' => 0,
				),
			), parent::get_param_definitions( $params )	);

		return $r;

	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		/**
		* @var Blog
		*/
		global $Collection, $Blog;

		$this->init_display( $params );

		// Collection common links:
		echo $this->disp_params['block_start'];

		// Display title if requested
		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		echo $this->disp_params['list_start'];

		if( $this->disp_params['show_home'] )
		{
			echo $this->disp_params['item_start'];
			echo '<strong><a href="'.$Blog->get('url').'">'.T_('Home').'</a></strong>';
			echo $this->disp_params['item_end'];
		}

		if( $this->disp_params['show_recently'] )
		{
			echo $this->disp_params['item_start'];
			echo '<strong><a href="'.$Blog->get('recentpostsurl').'">'.T_('Recently').'</a></strong>';
			echo $this->disp_params['item_end'];
		}

		if( $this->disp_params['show_search'] )
		{
			echo $this->disp_params['item_start'];
			echo '<strong><a href="'.$Blog->get('searchurl').'">'.T_('Search').'</a></strong>';
			echo $this->disp_params['item_end'];
		}

		if( $this->disp_params['show_postidx'] )
		{
			echo $this->disp_params['item_start'];
			echo '<strong><a href="'.$Blog->get('postidxurl').'">'.T_('Post index').'</a></strong>';
			echo $this->disp_params['item_end'];
		}

		if( $this->disp_params['show_archives'] )
		{
			// fp> TODO: don't display this if archives plugin not installed... or depluginize archives (I'm not sure)
			echo $this->disp_params['item_start'];
			echo '<strong><a href="'.$Blog->get('arcdirurl').'">'.T_('Archives').'</a></strong>';
			echo $this->disp_params['item_end'];
		}

		if( $this->disp_params['show_categories'] )
		{
			echo $this->disp_params['item_start'];
			echo '<strong><a href="'.$Blog->get('catdirurl').'">'.T_('Categories').'</a></strong>';
			echo $this->disp_params['item_end'];
		}

		if( $this->disp_params['show_mediaidx'] )
		{
			echo $this->disp_params['item_start'];
			echo '<strong><a href="'.$Blog->get('mediaidxurl').'">'.T_('Photo index').'</a></strong>';
			echo $this->disp_params['item_end'];
		}

		if( $this->disp_params['show_latestcomments'] && $Blog->get_setting( 'comments_latest' ) )
		{ // Display link to latest comments if this feature is enabled for current blog
			echo $this->disp_params['item_start'];
			echo '<strong><a href="'.$Blog->get('lastcommentsurl').'">'.T_('Latest comments').'</a></strong>';
			echo $this->disp_params['item_end'];
		}

		if( $this->disp_params['show_owneruserinfo'] )
		{
			echo $this->disp_params['item_start'];
			echo '<strong><a href="'.url_add_param( $Blog->get('userurl'), 'user_ID='.$Blog->owner_user_ID ).'">'.T_('Owner details').'</a></strong>';
			echo $this->disp_params['item_end'];
		}

		if( $this->disp_params['show_ownercontact'] && $url = $Blog->get_contact_url( true ) )
		{	// owner allows contact:
			echo $this->disp_params['item_start'];
			echo '<strong><a href="'.$url.'">'.T_('Contact').'</a></strong>';
			echo $this->disp_params['item_end'];
		}

		if( $this->disp_params['show_sitemap'] )
		{
			echo $this->disp_params['item_start'];
			echo '<strong><a href="'.$Blog->get('sitemapurl').'">'.T_('Site map').'</a></strong>';
			echo $this->disp_params['item_end'];
		}

		echo $this->disp_params['list_end'];

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		return true;
	}
}

?>