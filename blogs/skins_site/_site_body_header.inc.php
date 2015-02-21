<?php
/**
 * This is the site header include template.
 *
 * If enabled, thiw will be included at the top of all skins to provide a common identity and site wide navigation.
 *
 * @package site_skins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $baseurl, $Settings;
?>

<div class="sitewide_header">

<?php
if( $Settings->get( 'notification_logo' ) != '' )
{
	$site_title = $Settings->get( 'notification_long_name' ) != '' ? ' title="'.$Settings->get( 'notification_long_name' ).'"' : '';
	$site_name_text = '<img src="'.$Settings->get( 'notification_logo' ).'" alt="'.$Settings->get( 'notification_short_name' ).'"'.$site_title.' />';
	$site_title_class = ' swhead_logo';
}
else
{
	$site_name_text = $Settings->get( 'notification_short_name' );
	$site_title_class = '';
}
?>
<a href="<?php echo $baseurl; ?>" class="swhead_sitename<?php echo $site_title_class; ?>"><?php echo $site_name_text; ?></a>

<?php
	// --------------------------------- START OF BLOG LIST --------------------------------
	// Call widget directly (without container):
	skin_widget( array(
						// CODE for the widget:
						'widget' => 'colls_list_public',
						// Optional display params
						'block_start' => ' ',
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
				) );
	// ---------------------------------- END OF BLOG LIST ---------------------------------

	if( $Settings->get( 'info_blog_ID' ) > 0 )
	{
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
						'item_start' => '',
						'item_end' => '',
						'item_selected_start' => '',
						'item_selected_end' => '',
						'link_selected_class' => 'swhead_item swhead_item_selected',
						'link_default_class' => 'swhead_item ',
						'blog_ID' => $Settings->get( 'info_blog_ID' ),
						'item_group_by' => 'none',
						'order_by' => 'order',		// Order (as explicitly specified)


				) );
		// ---------------------------------- END OF PAGES LIST ---------------------------------
	}
?>

	<div class="floatright">
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

		if( is_logged_in() )
		{ // Display the following menus when current user is logged in

			// Profile link:
			// Call widget directly (without container):
			skin_widget( array_merge( $right_menu_params, array(
				// CODE for the widget:
				'widget' => 'profile_menu_link',
				// Optional display params
				'profile_picture_size' => 'crop-top-32x32',
			) ) );

			// Messaging link:
			// Call widget directly (without container):
			skin_widget( array_merge( $right_menu_params, array(
				// CODE for the widget:
				'widget' => 'msg_menu_link',
				// Optional display params
				'link_type' => 'messages',
			) ) );

			// Logout link:
			// Call widget directly (without container):
			skin_widget( array_merge( $right_menu_params, array(
				// CODE for the widget:
				'widget' => 'menu_link',
				// Optional display params
				'link_type' => 'logout',
			) ) );
		}
		else
		{ // Display the following menus when current user is NOT logged in

			// Login link:
			// Call widget directly (without container):
			skin_widget( array_merge( $right_menu_params, array(
				// CODE for the widget:
				'widget' => 'menu_link',
				// Optional display params
				'link_type' => 'login',
			) ) );

			// Register link:
			// Call widget directly (without container):
			skin_widget( array_merge( $right_menu_params, array(
				// CODE for the widget:
				'widget' => 'menu_link',
				// Optional display params
				'link_type' => 'register',
				'link_selected_class' => 'swhead_item_white '.$right_menu_params['link_selected_class'],
				'link_default_class' => 'swhead_item_white '.$right_menu_params['link_default_class'],
			) ) );
		}
	?>
	</div>
	<div class="clear"></div>
</div>
