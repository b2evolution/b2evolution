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
	<ul class="sf-menu">

	<li>
		<?php
			echo '<a href="'.$home_url.'"><strong>b2evolution</strong></a>';
			// Note: if <strong></strong> is inside of the link, rollover fails in IE7
		?>
    <ul>
			<?php
				$perm_options = $current_User->check_perm( 'options', 'view', false );
				if( $perm_options )
				{
					echo '<li><a href="'.$admin_url.'?ctrl=system">'.T_('About this system').'&hellip;</a></li>';
					echo '<li class="separator"><a><hr /></a></li>';
				}

				if( $current_User->check_perm( 'blogs', 'create' ) )
				{
					echo '<li><a href="'.$admin_url.'?ctrl=collections&amp;action=new">'.T_('Create new blog').'&hellip;</a></li>';
					echo '<li class="separator"><a><hr /></a></li>';
				}

			?>
			<li><a><?php echo T_('More info') ?></a><ul>
	      <li><a href="http://b2evolution.net/" target="_blank"><?php echo T_('Open b2evolution.net') ?></a></li>
	      <li><a href="http://forums.b2evolution.net/" target="_blank"><?php echo T_('Open Support forums') ?></a></li>
	      <li><a href="http://manual.b2evolution.net/" target="_blank"><?php echo T_('Open Online manual') ?></a></li>
	    </ul></li>
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

  			// Dashboard link:
				user_admin_link( '<li>', '</li>', T_('Dashboard'), T_('Go to admin dashboard') );

				// View link:
				blog_home_link( '<li>', '</li>', T_('See'), T_('See') );

  			// Write link:
				echo '<li><a href="'.$admin_url.'?ctrl=items&amp;action=new'.$blog_param.'">'.T_('Write').'</a></li>';

  			// Manage link:
  			$items_url = $admin_url.'?ctrl=items'.$blog_param.'&amp;filter=restore';

				echo '<li><a href="'.$items_url.'">'.T_('Manage').'</a><ul>';

					echo '<li><a href="'.$items_url.'&amp;tab=list">'.T_('Posts').'&hellip;</a></li>';
					echo '<li><a href="'.$items_url.'&amp;tab=pages">'.T_('Pages').'&hellip;</a></li>';
					echo '<li><a href="'.$items_url.'&amp;tab=intros">'.T_('Intros').'&hellip;</a></li>';
					echo '<li><a href="'.$items_url.'&amp;tab=podcasts">'.T_('Podcasts').'&hellip;</a></li>';
					echo '<li><a href="'.$items_url.'&amp;tab=links">'.T_('Sidebar links').'&hellip;</a></li>';
					if( !empty($Blog) && $Blog->get_setting( 'use_workflow' ) )
					{	// We want to use workflow properties for this blog:
						echo '<li><a href="'.$items_url.'&amp;tab=tracker">'.T_('Tracker').'&hellip;</a></li>';
					}
					echo '<li><a href="'.$items_url.'&amp;tab=full">'.T_('All Items').'&hellip;</a></li>';

					echo '<li class="separator"><a><hr /></a></li>';

					if( !empty($Blog) && $current_User->check_perm( 'blog_comments', 'edit', false, $Blog->ID ) )
					{	// Comments:
						echo '<li><a href="'.$admin_url.'?ctrl=comments&amp;blog='.$Blog->ID.'">'.T_('Comments').'&hellip;</a></li>';
					}

					if( $Settings->get( 'fm_enabled' ) && $current_User->check_perm( 'files', 'view' ) )
					{	// FM enabled and permission to view files:
						echo '<li><a href="'.$admin_url.'?ctrl=files'.$blog_param.'">'.T_('Files').'&hellip;</a></li>';
					}

					if( !empty($Blog) && $current_User->check_perm( 'blog_cats', 'edit', false, $Blog->ID ) )
					{	// Chapters:
						echo '<li><a href="'.$admin_url.'?ctrl=chapters&amp;blog='.$Blog->ID.'">'.T_('Categories').'&hellip;</a></li>';
					}

					if( $current_User->check_perm( 'stats', 'list' ) )
					{	// Permission to view stats for user's blogs:
						echo '<li class="separator"><a><hr /></a></li>';
						echo '<li><a href="'.$admin_url.'?ctrl=stats&amp;tab=summary&amp;tab3=global'.$blog_param.'">'.T_('Blog stats').'&hellip;</a></li>';
					}
				echo '</ul></li>';


				// Customize current blog
				if( !empty($Blog) && $current_User->check_perm( 'blog_properties', 'edit', false, $Blog->ID ) )
				{	// We have permission to edit blog properties:
 					echo '<li>';
						echo '<a href="'.$admin_url.'?ctrl=coll_settings'.$blog_param.'">'.T_('Customize').'</a>';
						echo '<ul>';
						echo '<li><a href="'.$admin_url.'?ctrl=coll_settings'.$blog_param.'">'.T_('Blog properties').'&hellip;</a></li>';
						echo '<li><a href="'.$admin_url.'?ctrl=coll_settings&amp;tab=features'.$blog_param.'">'.T_('Blog features').'&hellip;</a></li>';
						echo '<li><a href="'.$admin_url.'?ctrl=coll_settings&amp;tab=skin'.$blog_param.'">'.T_('Blog skin').'&hellip;</a></li>';
						echo '<li><a href="'.$admin_url.'?ctrl=widgets'.$blog_param.'">'.T_('Blog widgets').'&hellip;</a></li>';
						echo '<li><a href="'.$admin_url.'?ctrl=coll_settings&amp;tab=urls'.$blog_param.'">'.T_('Blog URLs').'&hellip;</a></li>';
						echo '</ul>';
					echo '</li>';
				}

				// TOOLS:
				$perm_view_stats = $current_User->check_perm( 'stats', 'view' );
				$perm_spam = $current_User->check_perm( 'spamblacklist', 'view', false );
				$perm_options = $current_User->check_perm( 'options', 'view' );
				if( $perm_view_stats || $perm_spam || $perm_options )
				{	// Permission to view settings:
					echo '<li><a>'.T_('Tools').'</a><ul>';

						if( $perm_spam )
						{
							echo '<li><a href="'.$admin_url.'?ctrl=antispam">'.T_('Antispam blacklist').'&hellip;</a></li>';
						}

						if( $perm_options )
						{
							echo '<li><a href="'.$admin_url.'?ctrl=crontab">'.T_('Scheduler').'&hellip;</a></li>';
						}

						if( $perm_view_stats )
						{	// We have permission to view all stats,
							echo '<li class="separator"><a><hr /></a></li>';
							echo '<li><a href="'.$admin_url.'?ctrl=stats&amp;tab=summary&amp;tab3=global&amp;blog=0">'.T_('Global Stats').'&hellip;</a></li>';
							echo '<li><a href="'.$admin_url.'?ctrl=stats&amp;tab=sessions&amp;tab3=login&amp;blog=0">'.T_('User sessions').'&hellip;</a></li>';
							echo '<li><a href="'.$admin_url.'?ctrl=stats&amp;tab=goals&amp;tab3=hits&amp;blog=0">'.T_('Goals').'&hellip;</a></li>';
						}

					echo '</ul></li>';
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
