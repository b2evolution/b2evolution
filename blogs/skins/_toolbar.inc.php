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
?>

<div id="evo_toolbar" class="evo_toolbar_<?php echo $Hit->agent_name; ?>">

<div class="actions_right">
	<ul>
 	<li class="menu_close" onmouseover="evo_menu_show(this)" onmouseout="evo_menu_hide(this)">
		<?php	user_profile_link( '<strong>', '</strong>', '%s '.get_icon('dropdown') ); ?>
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
					echo '<li class="separator"><hr /></li>';
					// echo '<li class="menu_close" onmouseover="evo_menu_show(this)" onmouseout="evo_menu_hide(this)">';
					// echo '<a href="#">'.T_('test').' '.get_icon('dropdown').'</a>';
					// echo '<ul>';
					foreach( $admin_skins as $admin_skin )
					{
						echo '<li><a href="admin.php?ctrl=users&amp;action=change_admin_skin&amp;new_admin_skin='
											.rawurlencode($admin_skin).'">'.T_('Admin skin:').' '.$admin_skin.'</a></li>';
					}
					// echo '</ul>';
					// echo '</li>';
				}
			}

		echo '<li class="separator"><hr /></li>';

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

<ul>
	<li class="menu_close" onmouseover="evo_menu_show(this)" onmouseout="evo_menu_hide(this)">
		<strong><?php
			echo '<a href="'.$home_url.'">b2evolution '.get_icon('dropdown').'</a>';
			// Note: if <strong></strong> is inside of the link, rollover fails in IE7
		?></strong>
    <ul>
			<?php
				echo '<li><a href="'.$home_url.'">'.T_('Home').'</a></li>';

				user_admin_link( '<li>', '</li>', T_('Dashboard'), T_('Go to admin dashboard') );

				echo '<li class="separator"><hr /></li>';


				if( $current_User->check_perm( 'blogs', 'create' ) )
				{
					echo '<li><a href="'.$admin_url.'?ctrl=collections&amp;action=new">'.T_('Create new blog').'</a></li>';
					echo '<li class="separator"><hr /></li>';
				}

				$perm_spam = $current_User->check_perm( 'spamblacklist', 'view', false );
				$perm_options = $current_User->check_perm( 'options', 'view', false );
				if( $perm_spam || $perm_options )
				{
					if( $perm_options )
					{
						echo '<li><a href="'.$admin_url.'?ctrl=system">'.T_('About this system').'</a></li>';
					}
					if( $perm_spam )
					{
						echo '<li><a href="'.$admin_url.'?ctrl=antispam">'.T_('Antispam blacklist').'</a></li>';
					}
					echo '<li class="separator"><hr /></li>';
				}
			?>
      <li><a href="http://b2evolution.net/" target="_blank"><?php echo T_('Open b2evolution.net') ?></a></li>
      <li><a href="http://forums.b2evolution.net/" target="_blank"><?php echo T_('Open Support forums') ?></a></li>
      <li><a href="http://manual.b2evolution.net/" target="_blank"><?php echo T_('Open Online manual') ?></a></li>
		</ul>
	</li>

  	<?php
			if( $is_admin_page || $current_User->check_perm( 'admin', 'visible' ) )
			{ // We are already in admin or we have permission to view admin options:
				if( !empty($Blog) )
				{
					$blog_param = '&amp;blog='.$Blog->ID;
				}
				else
				{
					$blog_param = '';
				}

				// fp> The plan is to have drop downs for each of those menu entries in order to access any authorized blog immediately

  			// Dashboard link:
				user_admin_link( '<li>', '</li>', T_('Dashboard'), T_('Go to admin dashboard') );

				// View link:
				blog_home_link( '<li>', '</li>', T_('See'), T_('See') );

  			// Write link:
				echo '<li><a href="'.$admin_url.'?ctrl=items&amp;action=new'.$blog_param.'">'.T_('Write').'</a></li>';

  			// Manage link:
				echo '<li><a href="'.$admin_url.'?ctrl=items'.$blog_param.'">'.T_('Manage').'</a></li>';

  			// Upload link:
				echo '<li><a href="'.$admin_url.'?ctrl=files'.$blog_param.'">'.T_('Upload').'</a></li>';

				// Customize current blog
				if( !empty($Blog) && $current_User->check_perm( 'blog_properties', 'edit', false, $Blog->ID ) )
				{	// We have permission to edit blog properties:
 					echo '<li class="menu_close" onmouseover="evo_menu_show(this)" onmouseout="evo_menu_hide(this)">';
						echo '<a href="'.$admin_url.'?ctrl=coll_settings'.$blog_param.'">'.T_('Customize').' '.get_icon('dropdown').'</a>';
						echo '<ul>';
						echo '<li><a href="'.$admin_url.'?ctrl=coll_settings'.$blog_param.'">'.T_('Blog properties').'</a></li>';
						echo '<li><a href="'.$admin_url.'?ctrl=coll_settings&amp;tab=features'.$blog_param.'">'.T_('Blog features').'</a></li>';
						echo '<li><a href="'.$admin_url.'?ctrl=coll_settings&amp;tab=skin'.$blog_param.'">'.T_('Blog skin').'</a></li>';
						echo '<li><a href="'.$admin_url.'?ctrl=widgets'.$blog_param.'">'.T_('Blog widgets').'</a></li>';
						echo '<li><a href="'.$admin_url.'?ctrl=coll_settings&amp;tab=urls'.$blog_param.'">'.T_('Blog URLs').'</a></li>';
						echo '</ul>';
					echo '</li>';
				}

				if( $debug )
				{
					echo '<li class="time">';
					if( !empty($seo_page_type) )
					{	// Set in skin_init()
						echo $seo_page_type.': ';
					}
					if( $robots_index === false )
					{
						echo 'NO INDEX';
					}
					else
					{
						echo 'do index';
					}
					echo '</li>';
				}
			}
  	?>
</ul>

</div>

</div>
<script type="text/javascript">
	function evo_menu_show( elt )
	{
		// window.status = elt.nodeName;

		child = elt.firstChild;
		while( child.nodeName != 'UL' )
		{
			child = child.nextSibling;
			if( child == null )
			{	// No UL was found
				return;
			}
		}

		// Find offset of parent bottom:
		var x = elt.offsetLeft;
		var y = elt.offsetTop + elt.offsetHeight;
		// Need to recurse to add parent offsets:
		var obj = elt.offsetParent;
		while (obj) {
			x += obj.offsetLeft;
			y += obj.offsetTop;
			obj = obj.offsetParent;
		}

		child.style.left = x + 'px';
		child.style.top = y + 'px';
		elt.className = 'menu_open'; // This is for IE6 which has no li:hover support
	}
	function evo_menu_hide( elt )
	{
		elt.className = 'menu_close'; // This is for IE6 which has no li:hover support
	}
</script>
