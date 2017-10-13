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
			'container_end'       => '<label for="nav-trigger"></label></div>',
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
<div class="sitewide_header_menu_wrapper">
	<ul class="sitewide_header_menu">
		<?php
		// Optional display params for widgets below
		$right_menu_params = array(
				'block_start' => '',
				'block_end' => '',
				'block_display_title' => false,
				'list_start' => '',
				'list_end' => '',
				'item_start' => '',
				'item_end' => '',
				'item_selected_start' => '',
				'item_selected_end' => '',
				'link_selected_class' => 'swhead_item swhead_item_selected',
				'link_default_class' => 'swhead_item ',
			);

		// --------------------------------- START OF BLOG LIST --------------------------------
		// Call widget directly (without container):
		skin_widget( array(
					// CODE for the widget:
					'widget' => 'colls_list_public',
					// Optional display params
					'block_start' => '',
					'block_end' => '',
					'block_display_title' => false,
					'list_start' => '',
					'list_end' => '',
					'item_start' => '<li class="swhead_item swhead_item_menu_sm">',
					'item_end' => '</li>',
					'item_selected_start' => '<li class="swhead_item swhead_item_menu_sm">',
					'item_selected_end' => '</li>',
					'link_selected_class' => 'swhead_item_selected',
					'link_default_class' => ''
			) );
			// ---------------------------------- END OF BLOG LIST ---------------------------------

			if( $Settings->get( 'info_blog_ID' ) > 0 )
			{ // We have a collection for info pages:
				// --------------------------------- START OF PAGES LIST --------------------------------
				// Call widget directly (without container):
				skin_widget( array(
						// CODE for the widget:
						'widget' => 'coll_page_list',
						// Optional display params
						'block_start' => '',
						'block_end' => '',
						'block_display_title' => false,
						'list_start' => '',
						'list_end' => '',
						'item_start' => '<li class="swhead_item swhead_item_menu_md">',
						'item_end' => '</li>',
						'item_selected_start' => '<li class="swhead_item swhead_item_menu_md">',
						'item_selected_end' => '</li>',
						'link_selected_class' => 'swhead_item_selected',
						'link_default_class' => '',
						'blog_ID' => $Settings->get( 'info_blog_ID' ),
						'item_group_by' => 'none',
						'order_by' => 'order',		// Order (as explicitly specified)
				) );
				// ---------------------------------- END OF PAGES LIST ---------------------------------
			}

			// --------------------------------- START OF CONTACT LINK --------------------------------
			// Call widget directly (without container):
			skin_widget( array(
					// CODE for the widget:
					'widget' => 'basic_menu_link',
					// Optional display params
					'block_start' => '',
					'block_end' => '',
					'block_display_title' => false,
					'list_start' => '',
					'list_end' => '',
					'item_start' => '<li class="swhead_item swhead_item_menu_md">',
					'item_end' => '</li>',
					'item_selected_start' => '<li class="swhead_item swhead_item_menu_md">',
					'item_selected_end' => '</li>',
					'link_selected_class' => 'swhead_item swhead_item_selected',
					'link_default_class' => 'swhead_item',
					'link_type' => 'ownercontact',
			) );
			// --------------------------------- END OF CONTACT LINK --------------------------------

			echo '<hr style="margin: 0; border-color: #696c72;">';

			if( is_logged_in() )
			{
				// Messaging link:
				// Call widget directly (without container):
				skin_widget( array_merge( $right_menu_params, array(
					// CODE for the widget:
					'widget' => 'msg_menu_link',
					// Optional display params
					'link_type' => 'messages',
					'item_start' => '<li class="swhead_item_menu_sm">',
					'item_end' => '</li>',
					'item_selected_start' => '<li class="swhead_item_menu_sm ">',
					'item_selected_end' => '</li>',
					'link_default_class' => ''
				) ) );

				// Logout link:
				// Call widget directly (without container):
				skin_widget( array_merge( $right_menu_params, array(
					// CODE for the widget:
					'widget' => 'basic_menu_link',
					// Optional display params
					'link_type' => 'logout',
					'item_start' => '<li class="swhead_item_menu_sm">',
					'item_end' => '</li>',
					'link_default_class' => ''
				) ) );
			}
			else
			{ // Display the following menus when current user is NOT logged in

				// Register link:
				// Call widget directly (without container):
				skin_widget( array_merge( $right_menu_params, array(
				// CODE for the widget:
				'widget' => 'basic_menu_link',
				// Optional display params
				'link_type' => 'register',
				'item_start' => '<li class="swhead_item_menu_sm">',
				'item_end' => '</li>',
				'link_selected_class' => 'swhead_item_white '.$right_menu_params['link_selected_class'],
				'link_default_class' => 'swhead_item_white'
			) ) );
			}
		?>
	</ul>
</div>