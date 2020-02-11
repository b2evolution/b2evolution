<?php
/**
 * This is the site header include template.
 *
 * If enabled, this will be included at the top of all skins to provide a common identity and site wide navigation.
 * NOTE: each skin is responsible for calling siteskin_include( '_site_body_header.inc.php' );
 *
 * @package skins
 * @subpackage default_site_skin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


	// ------------------------- "Site Header" CONTAINER EMBEDDED HERE --------------------------
	widget_container( 'site_header', array(
			// The following params will be used as defaults for widgets included in this container:
			'container_display_if_empty' => false, // If no widget, don't display container at all
			'container_start'     => '<nav class="evo_site_skin__header evo_container $wico_class$">',
			'container_end'       => '<div class="clear"></div></nav>',
			'block_start'         => '<span class="evo_widget $wi_class$">',
			'block_end'           => '</span>',
			'block_display_title' => false,
			'list_start'          => '',
			'list_end'            => '',
			'item_start'          => '',
			'item_end'            => '',
			'item_selected_start' => '',
			'item_selected_end'   => '',
			'profile_menu_link_text' => 'avatar_force_login',
		) );
	// ----------------------------- END OF "Site Header" CONTAINER -----------------------------

	// ------------------------- "Navigation Hamburger" CONTAINER EMBEDDED HERE --------------------------
	widget_container( 'navigation_hamburger', array(
			// The following params will be used as defaults for widgets included in this container:
			'container_display_if_empty' => false, // If no widget, don't display container at all
			'container_start'     => '<input type="checkbox" id="nav-trigger" class="nav-trigger">'
					.'<div class="evo_container $wico_class$">'
					.'<ul class="evo_navigation_hamburger_list">',
			'container_end'       => '</ul></div>',
			'block_start'         => '',
			'block_end'           => '',
			'block_display_title' => false,
			'list_start'          => '',
			'list_end'            => '',
			'item_start'          => '<li class="$wi_class$">',
			'item_end'            => '</li>',
			'item_selected_start' => '<li class="$wi_class$">',
			'item_selected_end'   => '</li>',
		) );
	// ----------------------------- END OF "Navigation Hamburger" CONTAINER -----------------------------
?>