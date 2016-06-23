<?php
/**
 * This is the site header include template.
 *
 * If enabled, this will be included at the top of all skins to provide a common identity and site wide navigation.
 * NOTE: each skin is responsible for calling siteskin_include( '_site_body_header.inc.php' );
 *
 * @package foyer
 * @subpackage custom_site_skin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $baseurl, $Settings, $Blog, $disp, $current_User, $site_Skin;

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

<div class="swhead_wrapper">

		<?php if( $Settings->get( 'notification_logo' ) != '' ) { ?>
			<div class="swhead_sitename<?php echo $site_title_class; ?>">
				<a href="<?php echo $baseurl; ?>"><?php echo $site_name_text; ?></a>
			</div>
		<?php } ?>
	<div class="swhead_menus">
		<div class="container-fluid level1">

			<nav>
				<div class="pull-right">
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
				'link_selected_class' => 'btn btn-default active btn-sm ',
				'link_default_class' => 'btn btn-default btn-sm ',
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

				<ul class="nav nav-tabs pull-left">
<?php
				if( $Settings->get( 'notification_logo' ) == '' )
				{	// Display site name:
?>
					<li class="swhead_sitename no_logo<?php echo $site_title_class; ?>">
						<a href="<?php echo $baseurl; ?>"><?php echo $site_name_text; ?></a>
					</li>
<?php
				}

			if( $site_Skin->get_setting( 'grouping' ) )
			{	// Display the grouped header tabs:
				$header_tabs = $site_Skin->get_header_tabs();

				foreach( $header_tabs as $s => $header_tab )
				{	// Display level 0 tabs:
?>
					<li<?php echo ( $site_Skin->header_tab_active === $s ? ' class="active"' : '' ); ?>>
						<a href="<?php echo $header_tab['url']; ?>"><?php echo $header_tab['name']; ?></a>
					</li>
<?php
				}
			}
			else
			{	// Display not grouped header tabs:

				// --------------------------------- START OF COLLECTION LIST --------------------------------
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
									'item_start' => '<li>',
									'item_end' => '</li>',
									'item_selected_start' => '<li class="active">',
									'item_selected_end' => '</li>',
									'link_selected_class' => 'active',
									'link_default_class' => '',
							) );
				// ---------------------------------- END OF COLLECTION LIST ---------------------------------

				if( $Settings->get( 'info_blog_ID' ) > 0 )
				{	// We have a collection for info pages:
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
									'item_start' => '<li>',
									'item_end' => '</li>',
									'item_selected_start' => '<li class="active">',
									'item_selected_end' => '</li>',
									'link_selected_class' => 'active',
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
									'widget' => 'menu_link',
									// Optional display params
									'block_start' => '',
									'block_end' => '',
									'block_display_title' => false,
									'list_start' => '',
									'list_end' => '',
									'item_start' => '<li>',
									'item_end' => '</li>',
									'item_selected_start' => '<li class="active">',
									'item_selected_end' => '</li>',
									'link_selected_class' => 'active',
									'link_default_class' => '',
									'link_type' => 'ownercontact',
							) );
				// --------------------------------- END OF CONTACT LINK --------------------------------
			}
?>
				</ul>
			</nav>

		</div><?php // END OF <div class="container-fluid level1"> ?>

<?php
if( $site_Skin->get_setting( 'grouping' ) &&
    isset( $header_tabs[ $site_Skin->header_tab_active ]['items'] ) &&
    count( $header_tabs[ $site_Skin->header_tab_active ]['items'] ) > 1 )
{	// Display sub menus of the selected level 0 tab only when at least two exist:
?>
<div class="container-fluid level2">
	<nav>
		<ul class="nav nav-pills">
<?php
	foreach( $header_tabs[ $site_Skin->header_tab_active ]['items'] as $menu_item )
	{
		if( is_array( $menu_item ) )
		{	// Display menu item for collection:
?>
			<li<?php echo ( $menu_item['active'] ? ' class="active"' : '' ); ?>>
				<a href="<?php echo $menu_item['url']; ?>"><?php echo $menu_item['name']; ?></a>
			</li>
<?php
		}
		elseif( $menu_item == 'pages' )
		{	// Display menu item for Pages of the info collection:
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
							'item_start' => '<li>',
							'item_end' => '</li>',
							'item_selected_start' => '<li class="active">',
							'item_selected_end' => '</li>',
							'link_selected_class' => 'swhead_item swhead_item_selected',
							'link_default_class' => 'swhead_item ',
							'blog_ID' => $Settings->get( 'info_blog_ID' ),
							'item_group_by' => 'none',
							'order_by' => 'order',		// Order (as explicitly specified)
					) );
			// ---------------------------------- END OF PAGES LIST ---------------------------------
		}
	}
?>
		</ul>
	</nav>
</div><?php // END OF <div class="container-fluid level2"> ?>
<?php
}
?>

	</div><?php // END OF <div class="swhead_menus"> ?>
</div><?php // END OF <div class="swhead_wrapper"> ?>