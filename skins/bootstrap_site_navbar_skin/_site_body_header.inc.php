<?php
/**
 * This is the site header include template.
 *
 * If enabled, this will be included at the top of all skins to provide a common identity and site wide navigation.
 * NOTE: each skin is responsible for calling siteskin_include( '_site_body_header.inc.php' );
 *
 * @package skins
 * @subpackage bootstrap_site_navbar_skin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $baseurl, $Settings, $Blog, $disp, $current_User;

$skin_tabs = array(
	1 => array(
			'name'  => 'B&acirc;timent',
			'colls' => array( 2 ), // only one collection in this tab
		),
	2 => array(
			'name'  => 'Blog FISA',
			'colls' => array( 3 ),
		),
	3 => array(
			'name' => 'Test 1',
			'colls' => array( 4, 6 )
		),
	4 => array(
			'name' => 'Test 2',
			'colls' => array( 5 )
		),
	5 => array(
			'name' => 'About',
			'colls' => array( 'pages', 'contact' ) // these are "special codes"
		),
	// NOTE: be careful NOT to include the same collection in several tabs
	);

// Check each collection if it can be viewed by current user:
$BlogCache = & get_BlogCache();
foreach( $skin_tabs as $s => $skin_tab )
{
	foreach( $skin_tab['colls'] as $i => $skin_coll_ID )
	{
		if( is_integer( $skin_coll_ID ) )
		{
			if( ! ( $skin_Blog = & $BlogCache->get_by_ID( $skin_coll_ID ) ) )
			{	// Wrong collection ID, Unset this from menu:
				unset( $skin_tabs[ $s ]['colls'][ $i ] );
				continue;
			}

			// Get value of collection setting "Show in front-office list":
			$in_bloglist = $skin_Blog->get( 'in_bloglist' );

			if( $in_bloglist == 'public' )
			{	// Everyone can view this collection, Keep this in menu:
				continue;
			}

			if( $in_bloglist == 'never' )
			{	// Nobody can view this collection, Skip it:
				unset( $skin_tabs[ $s ]['colls'][ $i ] );
				continue;
			}

			if( ! is_logged_in() )
			{	// Only logged in users have an access to this collection, Skip it:
				unset( $skin_tabs[ $s ]['colls'][ $i ] );
				continue;
			}

			if( $in_bloglist == 'member' &&
			    ! $current_User->check_perm( 'blog_ismember', 'view', false, $skin_coll_ID ) )
			{	// Only members have an access to this collection, Skip it:
				unset( $skin_tabs[ $s ]['colls'][ $i ] );
				continue;
			}
		}
	}
	if( empty( $skin_tabs[ $s ]['colls'] ) )
	{	// Unset this menu completely because it is empty after collections checking:
		unset( $skin_tabs[ $s ] );
	}
}

// Default selected tab:
$skin_menu_selected = NULL;

// Get disp from request string if it is not initialized yet:
$current_disp = ( isset( $_GET['disp'] ) ? $_GET['disp'] : $disp );

if( $Settings->get( 'notification_logo' ) != '' )
{
	$site_title = $Settings->get( 'notification_long_name' ) != '' ? ' title="'.$Settings->get( 'notification_long_name' ).'"' : '';
	$site_name_text = '<img src="'.$Settings->get( 'notification_logo' ).'" alt="'.$Settings->get( 'notification_short_name' ).'"'.$site_title.' />';
	$site_title_class = ' navbar-header-with-logo';
}
else
{
	$site_name_text = $Settings->get( 'notification_short_name' );
	$site_title_class = '';
}
?>

<div class="bootstrap_site_navbar_header">

	<nav class="navbar navbar-default">
		<div class="container-fluid level1">

			<div class="navbar-header<?php echo $site_title_class; ?>">
				<a href="<?php echo $baseurl; ?>" class="navbar-brand"><?php echo $site_name_text; ?></a>
			</div>

			<div class="navbar-collapse collapse">
				<ul class="nav navbar-nav navbar-right">
	<?php
		// Optional display params for widgets below
		$right_menu_params = array(
				'block_start' => '',
				'block_end' => '',
				'block_display_title' => false,
				'list_start' => '',
				'list_end' => '',
				'item_start' => '<li>',
				'item_end' => '</li>',
				'item_selected_start' => '<li>',
				'item_selected_end' => '</li>',
				'link_selected_class' => '',
				'link_default_class' => '',
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
			) ) );
		}
	?>
				</ul><?php // END OF <ul class="nav navbar-nav navbar-right"> ?>

				<ul class="nav navbar-nav">
	<?php
	foreach( $skin_tabs as $s => $skin_tab )
	{
		$skin_menu_url = '#';
		$colls_array_keys = array_keys( $skin_tab['colls'] );
		$first_coll_ID = $skin_tab['colls'][ $colls_array_keys[0] ];

		// Set url for each skin item menu:
		if( is_integer( $first_coll_ID ) )
		{	// If first menu item is collection ID:
			if( $skin_Blog = & $BlogCache->get_by_ID( $first_coll_ID, false, false ) )
			{
				$skin_menu_url = $skin_Blog->get( 'url' );
			}
			if( in_array( $Blog->ID, $skin_tab['colls'] ) &&
					( $Settings->get( 'info_blog_ID' ) != $Blog->ID || ( $current_disp != 'page' && $current_disp != 'msgform' ) ) )
			{	// Mark this menu as active:
				$skin_menu_selected = $s;
			}
		}
		elseif( in_array( 'contact', $skin_tab['colls'] ) )
		{	// If this menu contains a link to contact with collection owner:
			if( $contact_url = $Blog->get_contact_url( true ) )
			{	// If contact page is allowed for current collection:
				$skin_menu_url = $contact_url;
				if( $current_disp == 'msgform' )
				{	// Mark this menu as active:
					$skin_menu_selected = $s;
				}
			}
		}

		if( in_array( 'pages', $skin_tab['colls'] ) &&
				$current_disp == 'page' &&
				$Settings->get( 'info_blog_ID' ) == $Blog->ID )
		{	// If this menu contains the links to pages of the info collection:
			$skin_menu_selected = $s;
		}

		echo '<li'.( $skin_menu_selected == $s ? ' class="active"' : '' ).'>'
				.'<a href="'.$skin_menu_url.'">'.$skin_tab['name'].'</a>'
			.'</li>';
	}
?>
				</ul><?php // END OF <ul class="nav navbar-nav"> ?>
			</div><?php // END OF <div class="navbar-collapse collapse"> ?>

		</div><?php // END OF <div class="container-fluid level1"> ?>
	</nav><?php // END OF <nav class="navbar navbar-default"> ?>

<?php
if( isset( $skin_tabs[ $skin_menu_selected ] ) && count( $skin_tabs[ $skin_menu_selected ]['colls'] ) > 1 )
{	// Display submenus only when at least two exist:
	$colls = $skin_tabs[ $skin_menu_selected ]['colls'];
?>
<div class="container-fluid level2">
	<nav>
		<ul class="nav nav-pills">
<?php
	foreach( $colls as $coll_ID )
	{
		if( is_integer( $coll_ID ) )
		{	// Display menu item for collection:
			if( $skin_Blog = & $BlogCache->get_by_ID( $coll_ID, false, false ) )
			{
				echo '<li'.( $Blog->ID == $skin_Blog->ID ? ' class="active"' : '' )
					.'><a href="'.$skin_Blog->get( 'url' ).'">'.$skin_Blog->get( 'name' )
					.'</a></li>';
			}
		}
		elseif( $coll_ID == 'pages' )
		{	// Display menu item for Pages of the info collection:
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
								'item_start' => '<li>',
								'item_end' => '</li>',
								'item_selected_start' => '<li class="active">',
								'item_selected_end' => '</li>',
								'blog_ID' => $Settings->get( 'info_blog_ID' ),
								'item_group_by' => 'none',
								'order_by' => 'order',		// Order (as explicitly specified)
						) );
				// ---------------------------------- END OF PAGES LIST ---------------------------------
			}
		}
		elseif( $coll_ID == 'contact' )
		{	// Display menu item for Contact page:
			if( $contact_url = $Blog->get_contact_url( true ) )
			{	// If contact page is allowed for current collection:
				echo '<li'.( $current_disp == 'msgform' ? ' class="active"' : '' )
					.'><a href="'.$contact_url.'">'.T_('Contact')
					.'</a></li>';
			}
		}
	}
?>
		</ul><?php // END OF <ul class="nav nav-pills"> ?>
	</nav>
</div><?php // END OF <div class="container-fluid level2"> ?>
<?php
}
?>

</div><?php // END OF <div class="bootstrap_site_header"> ?>