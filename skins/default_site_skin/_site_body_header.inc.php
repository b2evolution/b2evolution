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

global $baseurl, $Settings;
?>

<nav class="sitewide_header">
<?php
	// ------------------------- "Site Header" CONTAINER EMBEDDED HERE --------------------------
	widget_container( 'site_header', array(
			// The following params will be used as defaults for widgets included in this container:
			'container_display_if_empty' => true, // Display container anyway even if no widget
			'container_start'     => '<div class="evo_container $wico_class$">',
			'container_end'       => '</div>',
			'block_start'         => '<span class="evo_widget $wi_class$">',
			'block_end'           => '</span>',
			'block_display_title' => false,
			'list_start'          => '',
			'list_end'            => '',
			'item_start'          => '',
			'item_end'            => '',
			'item_selected_start' => '',
			'item_selected_end'   => '',
			'link_selected_class' => 'swhead_item swhead_item_selected',
			'link_default_class'  => 'swhead_item',
		) );
	// ----------------------------- END OF "Site Header" CONTAINER -----------------------------
?>
	<div class="clear"></div>
</nav>

<input type="checkbox" id="nav-trigger" class="nav-trigger">
<div class="sitewide_header_menu_wrapper visible-sm visible-xs">
<?php
	// ------------------------- "Navigation Hamburger" CONTAINER EMBEDDED HERE --------------------------
	widget_container( 'navigation_hamburger', array(
			// The following params will be used as defaults for widgets included in this container:
			'container_display_if_empty' => true, // Display container anyway even if no widget
			'container_start'     => '<ul class="evo_container sitewide_header_menu $wico_class$">',
			'container_end'       => '</ul>',
			'block_start'         => '',
			'block_end'           => '',
			'block_display_title' => false,
			'list_start'          => '',
			'list_end'            => '',
			'item_start'          => '<li class="swhead_item $wi_class$">',
			'item_end'            => '</li>',
			'item_selected_start' => '<li class="swhead_item $wi_class$">',
			'item_selected_end'   => '</li>',
			'link_selected_class' => 'swhead_item_selected',
			'link_default_class'  => '',
		) );
	// ----------------------------- END OF "Navigation Hamburger" CONTAINER -----------------------------
?>
</div>