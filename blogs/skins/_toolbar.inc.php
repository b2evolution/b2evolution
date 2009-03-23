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


global $Menu;
load_class( '_core/ui/_menu.class.php' );
$Menu = new Menu();

?>

<div id="evo_toolbar" class="evo_toolbar_<?php echo $Hit->get_agent_name(); ?>">

<div class="actions_right">
	<ul class="sf-menu sf-menu-right">
 	<li>
		<?php
		//
		user_profile_link( '', '', $current_User->get_avatar_imgtag( 'crop-15x15', '', 'top' ).' <strong>%s</strong>' );
		?>
		<ul>
		<?php
			user_profile_link( '<li>', '</li>', T_('User profile').' (%s)' );
			user_subs_link( '<li>', '</li>', T_('Email subscriptions') );

			// ADMIN SKINS:
			if( $is_admin_page )
			{
				$admin_skins = get_admin_skins();
				if( count( $admin_skins ) > 1 )
				{	// We have several admin skins available: display switcher:
					echo '<li class="separator"><a><hr /></a></li>';

					echo '<li><a>'.T_('Admin skin').'</a><ul>';
					foreach( $admin_skins as $admin_skin )
					{
						echo '<li><a href="admin.php?ctrl=users&amp;action=change_admin_skin&amp;new_admin_skin='
											.rawurlencode($admin_skin).'">'.$admin_skin.'</a></li>';
					}
					echo '</ul></li>';
				}
			}

		echo '<li class="separator"><a><hr /></a></li>';

		user_logout_link( '<li>', '</li>', T_('Logout') );
		?>
		</ul>
	</li>

	<li class="time"><?php echo date( locale_shorttimefmt(), $localtimenow ); ?></li>

	<?php
		if( $is_admin_page )
		{
  		blog_home_link( '<li>', '</li>', T_('Blog').' '.get_icon('switch-to-blog'), T_('Home').' '.get_icon('switch-to-blog') );
		}
		else
		{
			user_admin_link( '<li>', '</li>', T_('Admin').' '.get_icon('switch-to-admin') );
		}
		user_logout_link( '<li>', '</li>', T_('Logout').' '.get_icon('close'), '#', array('class'=>'rollover') );
	?>
	</ul>
</div>

<div class="actions_left">

	<?php

	// Let the modules construct the menu:
	modules_call_method( 'build_evobar_menu' );

	if( $is_admin_page || $current_User->check_perm( 'admin', 'visible' ) )
	{ // We are already in admin or we have permission to view admin options:
		// Display evobar menu:
		echo $Menu->get_html_menu( NULL, 'evobar' );
	}
 	?>
</ul>

</div>

</div>
