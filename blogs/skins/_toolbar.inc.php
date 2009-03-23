<?php
/**
 * This is the Evo Toolbar include template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://manual.b2evolution.net/Skins_2.0}
 *
 * This is meant to be included in a page template.
 *
 * @todo dh> with disabled JavaScript the expanded submenu boxes
 *           for "Customize" do not get moved to the correct
 *           place/screen offset.
 *           I think the display of the submenus should get done
 *           only with JS in this case (and not with hover).
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( ! is_logged_in() )
{
	return;
}

global $Blog;

global $Settings;

global $is_admin_page, $localtimenow;

/**
 * @var User
 */
global $current_User;

global $home_url, $admin_url, $debug, $seo_page_type, $robots_index;

/**
 * @var Hit
 */
global $Hit;

global $Plugins;

load_class( '_core/ui/_menu.class.php' );

/**
 * @global Menu Evobar menu on the top left (should be useed for content & system management features)
 */
global $topleft_Menu;
$topleft_Menu = new Menu();

/**
 * @global Menu Evobar menu on the top right (should be used for current user's session, profile & prefs features)
 */
global $topright_Menu;
$topright_Menu = new Menu();


// Let the modules construct the menu:
modules_call_method( 'build_evobar_menu' );

// Call AdminAfterToolbarInit to notify Plugins that the toolbar menus are initialized. Plugins can add entries.
$Plugins->trigger_event( 'AdminAfterEvobarInit' );

?>

<div id="evo_toolbar" class="evo_toolbar_<?php echo $Hit->get_agent_name(); ?>">
	<div class="actions_right">
		<?php
			// Display evobar menu:
			echo $topright_Menu->get_html_menu( NULL, 'sf-menu-right' );
 		?>
	</div>
	<div class="actions_left">
		<?php
		if( $is_admin_page || $current_User->check_perm( 'admin', 'visible' ) )
		{ // We are already in admin or we have permission to view admin options:
			// Display evobar menu:
			echo $topleft_Menu->get_html_menu( NULL, 'sf-menu-left' );
		}
 		?>
	</ul>
</div>

</div>
