<?php
/**
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @package skins
 * @subpackage bootstrap_site_navbar_skin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Specific code for this skin.
 *
 * ATTENTION: if you make a new skin you have to change the class name below accordingly
 */
class bootstrap_site_navbar_Skin extends Skin
{
	/**
	 * Skin version
	 * @var string
	 */
	var $version = '6.7.0';

	/**
	 * Do we want to use style.min.css instead of style.css ?
	 */
	var $use_min_css = true;  // true|false|'check' Set this to true for better optimization

	/**
	 * Get default name for the skin.
	 * Note: the admin can customize it.
	 */
	function get_default_name()
	{
		return 'Bootstrap Site Navbar';
	}


	/**
	 * Get default type for the skin.
	 */
	function get_default_type()
	{
		return 'normal';
	}


	/**
	 * Does this skin providesnormal (collection) skin functionality?
	 */
	function provides_collection_skin()
	{
		return false;
	}


	/**
	 * Does this skin provide site-skin functionality?
	 */
	function provides_site_skin()
	{
		return true;
	}


	/**
	 * What evoSkins API does has this skin been designed with?
	 *
	 * This determines where we get the fallback templates from (skins_fallback_v*)
	 * (allows to use new markup in new b2evolution versions)
	 */
	function get_api_version()
	{
		return 6;
	}


	/**
	 * Get ready for displaying the site skin.
	 *
	 * This may register some CSS or JS...
	 */
	function siteskin_init()
	{
		// Include the default skin style.css relative current SITE skin folder:
		require_css( 'style.css', 'siteskin' );
	}


	/**
	 * Get header tabs
	 *
	 * @return array
	 */
	function get_header_tabs()
	{
		global $Blog, $disp, $Settings;

		$header_tabs = array();

		// Get disp from request string if it is not initialized yet:
		$current_disp = isset( $_GET['disp'] ) ? $_GET['disp'] : ( isset( $disp ) ? $disp : NULL );

		// Get current collection ID:
		$current_blog_ID = isset( $Blog ) ? $Blog->ID : NULL;

		// Load all collection groups:
		$CollGroupCache = & get_CollGroupCache();
		$CollGroupCache->load_all();

		$this->header_tab_active = NULL;
		$level0_index = 0;
		foreach( $CollGroupCache->cache as $CollGroup )
		{
			$tab_items = array();
			$group_blogs = $CollGroup->get_blogs();

			$level0_is_active = false;

			// Check each collection if it can be viewed by current user:
			foreach( $group_blogs as $i => $group_Blog )
			{
				$coll_is_active = false;
				if( $current_blog_ID == $group_Blog->ID &&
						( $Settings->get( 'info_blog_ID' ) != $current_blog_ID || ( $current_disp != 'page' && $current_disp != 'msgform' ) ) )
				{	// Mark this menu as active:
					$coll_is_active = true;
				}

				$coll_data = array(
						'name'   => $group_Blog->get( 'name' ),
						'url'    => $group_Blog->get( 'url' ),
						'active' => ( $current_blog_ID == $group_Blog->ID )
					);

				// Get value of collection setting "Show in front-office list":
				$in_bloglist = $group_Blog->get( 'in_bloglist' );

				if( $in_bloglist == 'public' )
				{	// Everyone can view this collection, Keep this in menu:
					$tab_items[] = $coll_data;
					if( $coll_is_active )
					{
						$this->header_tab_active = $level0_index;
					}
					continue;
				}

				if( $in_bloglist == 'never' )
				{	// Nobody can view this collection, Skip it:
					continue;
				}

				if( ! is_logged_in() )
				{	// Only logged in users have an access to this collection, Skip it:
					continue;
				}

				if( $in_bloglist == 'member' &&
						! $current_User->check_perm( 'blog_ismember', 'view', false, $skin_coll_ID ) )
				{	// Only members have an access to this collection, Skip it:
					continue;
				}

				$tab_items[] = $coll_data;
				if( $coll_is_active )
				{
					$this->header_tab_active = $level0_index;
				}
			}

			if( ! empty( $tab_items ) )
			{	// Display collection group only if at least one collection is allowed for current display:
				$header_tabs[] = array(
						'name'  => $CollGroup->get_name(),
						'url'   => $tab_items[0]['url'],
						'items' => $tab_items
					);

				$level0_index++;
			}
		}

		// Additional tab with pages and contact links:
		if( isset( $Blog ) )
		{
			$tab_items = array( 'pages' );

			if( $current_disp == 'msgform' )
			{	// Mark this menu as active:
				$this->header_tab_active = $level0_index;
			}

			if( $current_disp == 'page' && $Settings->get( 'info_blog_ID' ) == $Blog->ID )
			{	// If this menu contains the links to pages of the info collection:
				$this->header_tab_active = $level0_index;
			}

			if( $contact_url = $Blog->get_contact_url( true ) )
			{	// If contact page is allowed for current collection:
				$tab_items[] = array(
						'name'   => T_('Contact'),
						'url'    => $contact_url,
						'active' => ( $current_disp == 'msgform' )
					);
			}

			if( ! empty( $contact_url ) )
			{	// Display additional tabs with static pages only user has an access to contact page:
				$header_tabs[] = array(
						'name'   => 'About',
						'url'    => $contact_url,
						'items'  => $tab_items
					);
			}
		}

		return $header_tabs;
	}
}

?>