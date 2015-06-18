<?php
/**
 * This is the Evo Toolbar include template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-development-primer}
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

/**
 * @var User
 */
global $current_User;

if( !$current_User->check_perm( 'admin', 'toolbar' ) )
{ // don't show toolbar for current User
	return;
}

global $Blog;

global $Settings;

global $is_admin_page, $localtimenow, $disp_detail;

global $home_url, $admin_url, $debug, $seo_page_type, $robots_index;

global $request_transaction_name;

/**
 * @var Hit
 */
global $Hit;

global $Plugins;

global $locale_from_get, $disp_handler, $disp_handler_custom, $disp_handler_custom_found;

global $Session;

load_class( '_core/ui/_menu.class.php', 'Menu' );

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


// Switch to users locale (if not overridden via REQUEST):
if( ! $locale_from_get )
{
	locale_temp_switch($current_User->locale);
}

// Let the modules construct the menu:
modules_call_method( 'build_evobar_menu' );

// Call AdminAfterToolbarInit to notify Plugins that the toolbar menus are initialized. Plugins can add entries.
$Plugins->trigger_event( 'AdminAfterEvobarInit' );

?>

<div id="evo_toolbar" class="evo_toolbar_<?php echo $Hit->get_agent_name(); ?>">
	<div class="actions_right">
		<?php
			// Display evobar menu:
			echo $topright_Menu->get_html_menu( NULL, 'evobar-menu-right' );
		?>
	</div>
	<div class="actions_left">
		<?php
		if( $topleft_Menu->has_entires() )
		{ // The Menu has entries, it means that current User has permission to at least one action
			// Display evobar menu:
			echo $topleft_Menu->get_html_menu( NULL, 'evobar-menu-left' );
		}
		?>
	</div>
	<div class="clear"></div>
</div>

<?php
	if( ! is_admin_page() && isset( $Blog ) && $Session->get( 'display_includes_'.$Blog->ID ) )
	{ // Wrap the include with a visible div:
		echo '<div class="dev-blocks dev-blocks--include dev-blocks--belowtoolbar">';
		echo '<div class="dev-blocks-name"><b>';
		if( ! empty( $disp_handler_custom ) )
		{ // Custom template
			echo 'CUSTOM Main template: ';
			if( empty( $disp_handler_custom_found ) )
			{ // Custom template in NOT found
				echo $disp_handler_custom.' -&gt; Fallback to:';
			}
			else
			{ // Custom template in found
				echo $disp_handler_custom.' -&gt; Found:';
			}
		}
		else
		{ // Default template
			echo 'Main template: ';
		}
		echo '</b> '.rel_path_to_base( $disp_handler ).'</div>';
		echo '</div>';
	}
?>


<?php
if( ! $locale_from_get )
{
	locale_restore_previous();
}
?>
