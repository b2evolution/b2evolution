<?php
/**
 * This file implements the site Skin class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * site Skin Class
 * Helper class for site skins
 *
 * @package evocore
 */
class site_Skin extends Skin
{
	/**
	 * Get generic param definitions for site header
	 *
	 * @return array
	 */
	function get_site_header_param_definitions()
	{
		global $admin_url;

		// Set params for setting "Collection for Info Pages":
		$BlogCache = & get_BlogCache();
		$BlogCache->none_option_text = T_('Same as "Default collection to display"');

		$SiteMenuCache = & get_SiteMenuCache();
		$SiteMenuCache->load_where( 'menu_translates_menu_ID IS NULL');

		return array( 
			'menu_type' => array(
				'label' => T_('Menu type'),
				'options' => array(
						array( 'auto', T_('Automatic - Collection list') ),
						array( 'auto_grouped', T_('Automatic - Grouped collection list') ),
						array( 'custom', T_('Custom menu') ),
					),
				'defaultvalue' => 'auto',
				'type' => 'radio',
				'field_lines' => true,
			),
			'info_coll_ID' => array(
				'label' => T_('Collection for Info Pages'),
				'type' => 'select_blog',
				'allow_none' => true,
				'defaultvalue' => 0,
				'hide' => ( $this->get_setting( 'menu_type', NULL, 'auto' ) == 'custom' ),
			),
			'menu_ID' => array(
				'label' => T_('Menu to display'),
				'input_suffix' => ( check_user_perm( 'options', 'edit' ) ? ' <a href="'.$admin_url.'?ctrl=menus">'.T_('Manage Menus').' &gt;&gt;</a>' : '' ),
				'type' => 'select_object',
				'object' => $SiteMenuCache,
				'allow_none' => true,
				'defaultvalue' => '',
				'hide' => in_array( $this->get_setting( 'menu_type', NULL, 'auto' ), array( 'auto', 'auto_grouped' ) ),
			),
			'fixed_header' => array(
				'label' => T_('Fixed position'),
				'note' => T_('Check to fix header top on scroll down'),
				'type' => 'checkbox',
				'defaultvalue' => 1,
			),
		);
	}


	/**
	 * Check if skin currently has sub menus
	 *
	 * @return boolean
	 */
	function has_sub_menus()
	{
		if( $this->get_setting( 'menu_type' ) == 'auto' )
		{	// Skin does NOT group collections sub tabs:
			return false;
		}

		$header_tabs = $this->get_header_tabs();

		return ( isset( $this->header_tab_active, $header_tabs[ $this->header_tab_active ]['items'] ) &&
			count( $header_tabs[ $this->header_tab_active ]['items'] ) > 1 );
	}


	/**
	 * Get header tabs if custom menu is selected or when automatic menu is grouped
	 *
	 * @return array|boolean Array of header tabs OR FALSE when
	 */
	function get_header_tabs()
	{
		if( $this->get_setting( 'menu_type' ) == 'custom' &&
		    ( $SiteMenuCache = & get_SiteMenuCache() ) &&
		    ( $SiteMenu = & $SiteMenuCache->get_by_ID( $this->get_setting( 'menu_ID' ), false, false ) ) )
		{	// Use custom menu if it is found in DB:
			return $this->get_header_tabs_custom( $SiteMenu->ID );
		}
		elseif( $this->get_setting( 'menu_type' ) == 'auto_grouped' )
		{	// Use automatic grouped menu:
			return $this->get_header_tabs_auto();
		}

		// Don't use grouped header tabs:
		return false;
	}


	/**
	 * Get header tabs from custom menu
	 *
	 * @param integer Menu ID
	 * @return array
	 */
	function get_header_tabs_custom( $menu_ID )
	{
		global $DB, $current_locale;

		$header_tabs = array();

		$SiteMenuCache = & get_SiteMenuCache();

		if( ! ( $SiteMenu = & $SiteMenuCache->get_by_ID( $this->get_setting( 'menu_ID' ), false, false ) ) )
		{	// Wrong Menu:
			return $header_tabs;
		}

		// Check if the menu has a child matching the current locale:
		$localized_menus = $SiteMenu->get_localized_menus( $current_locale );
		if( ! empty( $localized_menus ) )
		{	// Use localized menu:
			$SiteMenu = & $localized_menus[0];
		}

		$this->header_tab_active = NULL;

		$site_menu_entries = $SiteMenu->get_entries();
		$level0_index = 0;
		foreach( $site_menu_entries as $SiteMenuEntry )
		{
			if( $header_tab = $this->get_header_tab_custom( $SiteMenuEntry ) )
			{
				$header_tabs[] = $header_tab;
				if( ! empty( $header_tab['items'] ) )
				{
					foreach( $header_tab['items'] as $sub_item )
					{
						if( ! empty( $sub_item['active'] ) )
						{
							$this->header_tab_active = $level0_index;
							break;
						}
					}
				}
				$level0_index++;
			}
		}

		return $header_tabs;
	}


	/**
	 * Get custom header tab
	 *
	 * @param object SiteMenuEntry
	 * @param array header tab params
	 */
	function get_header_tab_custom( $SiteMenuEntry )
	{
		global $Blog;

		$header_tab = false;

		if( $SiteMenuEntry->get( 'type' ) == 'text' )
		{	// Only type "Text" supports sub-entries:
			$sub_entries = $SiteMenuEntry->get_children( true );
			$sub_tabs = array();
			foreach( $sub_entries as $sub_SiteMenuEntry )
			{
				if( $sub_tab = $this->get_header_tab_custom( $sub_SiteMenuEntry ) )
				{
					$sub_tabs[] = $sub_tab;
				}
			}

			if( ! empty( $sub_tabs ) )
			{	// Display parent tab only if at least one sub tab is allowed for current display:
				$header_tab = array(
						'name'  => $SiteMenuEntry->get_text(),
						'url'   => $sub_tabs[0]['url'],
						'items' => $sub_tabs,
						'class' => $SiteMenuEntry->get( 'class' ),
					);
			}
		}
		elseif( $menu_entry_url = $SiteMenuEntry->get_url() )
		{	// Only if the menu entry is allowed for current User, page and etc.:
			$header_tab = array(
					'name'   => $SiteMenuEntry->get_text(),
					'url'    => $menu_entry_url,
					'active' => $SiteMenuEntry->is_active(),
					'class'  => $SiteMenuEntry->get( 'class' ),
				);
		}

		return $header_tab;
	}


	/**
	 * Get automatic (from collection List) header tabs
	 *
	 * @return array
	 */
	function get_header_tabs_auto()
	{
		global $Blog, $disp, $current_User;

		if( isset( $this->header_tabs ) )
		{	// Get header tabs from previous request:
			return $this->header_tabs;
		}

		$this->header_tabs = array();

		// Get disp from request string if it is not initialized yet:
		$current_disp = isset( $_GET['disp'] ) ? $_GET['disp'] : ( isset( $disp ) ? $disp : NULL );

		// Get current collection ID:
		$current_blog_ID = isset( $Blog ) ? $Blog->ID : NULL;

		// Load all sections except of "No Section" because collections of this section are displayed as separate tabs at the end:
		$SectionCache = & get_SectionCache();
		$SectionCache->clear();
		$SectionCache->load_where( 'sec_ID != 1' );

		$this->header_tab_active = NULL;
		$level0_index = 0;
		foreach( $SectionCache->cache as $Section )
		{
			$tab_items = array();
			$group_blogs = $Section->get_blogs();

			$level0_is_active = false;

			// Check each collection if it can be viewed by current user:
			foreach( $group_blogs as $i => $group_Blog )
			{
				$coll_is_active = false;
				if( $current_blog_ID == $group_Blog->ID &&
						( $this->get_info_coll_ID() != $current_blog_ID || ( $current_disp != 'page' && $current_disp != 'msgform' ) ) )
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
						! check_user_perm( 'blog_ismember', 'view', false, $group_Blog->ID ) )
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
			{	// Display section only if at least one collection is allowed for current display:
				$this->header_tabs[] = array(
						'name'  => $Section->get_name(),
						'url'   => $tab_items[0]['url'],
						'items' => $tab_items
					);

				$level0_index++;
			}
		}

		// Load all collection from "No Section" and put them after all section tabs:
		$BlogCache = & get_BlogCache();
		$BlogCache->clear();
		$public_colls_SQL = $BlogCache->get_public_colls_SQL();
		$public_colls_SQL->WHERE_and( 'blog_sec_ID = 1' );
		$BlogCache->load_by_sql( $public_colls_SQL );

		foreach( $BlogCache->cache as $nosec_Blog )
		{
			$this->header_tabs[] = array(
					'name' => $nosec_Blog->get( 'shortname' ),
					'url'  => $nosec_Blog->get( 'url' ),
				);

			if( $current_blog_ID == $nosec_Blog->ID )
			{	// Mark this tab as active if this is a current collection:
				$this->header_tab_active = $level0_index;
			}

			$level0_index++;
		}

		// Additional tab with pages and contact links:
		if( isset( $Blog ) )
		{
			$tab_items = array( 'pages' );

			if( $current_disp == 'msgform' )
			{	// Mark this menu as active:
				$this->header_tab_active = $level0_index;
			}

			if( $current_disp == 'page' && $this->get_info_coll_ID() == $Blog->ID )
			{	// If this menu contains the links to pages of the info/shared collection:
				$this->header_tab_active = $level0_index;
			}

			if( $contact_url = $Blog->get_contact_url() )
			{	// If contact page is allowed for current collection:
				$tab_item = array(
						'name'   => T_('Contact'),
						'url'    => $contact_url,
						'active' => ( $current_disp == 'msgform' )
					);
				if( $Blog->get_setting( 'msgform_nofollowto' ) )
				{	// Use nofollow attribute:
					$tab_item['rel'] = 'nofollow';
				}
				$tab_items[] = $tab_item;
			}

			if( ! empty( $contact_url ) )
			{	// Display additional tabs with static pages only user has an access to contact page:
				$this->header_tabs[] = array(
						'name'   => T_('About'),
						'url'    => $contact_url,
						'items'  => $tab_items
					);
			}
		}

		return $this->header_tabs;
	}


	/**
	 * Get attribute for header tab
	 *
	 * @param array Tab data
	 * @param integer Tab index in array of all tabs
	 * @param array Additional params
	 * @return string
	 */
	function get_header_tab_attr_class( $tab, $index = NULL, $params = array() )
	{
		$params = array_merge( array(
				'class'        => '',
				'class_active' => 'active',
			), $params );

		$class = $params['class'];

		if( ! empty( $tab['class'] ) )
		{	// Append extra CSS classes of Menu Entry:
			$class .= ' '.$tab['class'];
		}

		if( $this->header_tab_active === $index || ! empty( $tab['active'] ) )
		{	// This tab is active currently:
			$class .= ' '.$params['class_active'];
		}

		$class = trim( $class );

		return $class === '' ? '' : ' class="'.$class.'"';
	}


	/**
	 * Get ID of collection for Info Pages
	 *
	 * @return integer ID
	 */
	function get_info_coll_ID()
	{
		$info_coll_ID = $this->get_setting( 'info_coll_ID' );

		if( empty( $info_coll_ID ) )
		{	// Use same collection as "Default collection to display":
			global $Settings;
			return $Settings->get( 'default_blog_ID' );
		}

		return $info_coll_ID;
	}


	/**
	 * Additional JavaScript code for skin settings form
	 */
	function echo_settings_form_js()
	{
?>
<script>
jQuery( '[name=edit_skin_<?php echo $this->ID; ?>_set_menu_type]' ).click( function()
{
	var is_custom_mode = ( jQuery( '[name=edit_skin_<?php echo $this->ID; ?>_set_menu_type]:checked' ).val() == 'custom' );
	jQuery( '#ffield_edit_skin_<?php echo $this->ID; ?>_set_info_coll_ID' ).toggle( ! is_custom_mode );
	jQuery( '#ffield_edit_skin_<?php echo $this->ID; ?>_set_menu_ID' ).toggle( is_custom_mode );
} );
</script>
<?php
	}
}
?>