<?php
/**
 * This is the main front-office interface file! This will we displayed if you haven't configured
 * a default collection to display.
 *
 * This file is NOT mandatory. You can delete it if you have configured a default collection.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage noskin
 */

/**
 * First thing: Do the minimal initializations required for b2evo:
 */
require_once dirname(__FILE__).'/conf/_config.php';

/**
 * Check this: we are requiring _main.inc.php INSTEAD of _blog_main.inc.php because we are not
 * trying to initialize any particular blog
 */
require_once $inc_path.'_main.inc.php';

load_funcs('skins/_skin.funcs.php');

// Set bootstrap css classes for messages
$Messages->set_params( array(
		'class_outerdiv' => 'action_messages container-fluid',
		'class_success'  => 'alert alert-dismissible alert-success fade in',
		'class_warning'  => 'alert alert-dismissible alert-warning fade in',
		'class_error'    => 'alert alert-dismissible alert-danger fade in',
		'class_note'     => 'alert alert-dismissible alert-info fade in',
		'before_message' => '<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span></button>',
	) );

// --------------------- PAGE LEVEL CACHING SUPPORT ---------------------
// Note: This is totally optional. General caching must be enabled in Global settings, otherwise this will do nothing.
// Delete this block if you don't care about page level caching. Don't forget to delete the matching section at the end of the page.
load_class( '_core/model/_pagecache.class.php', 'PageCache' );
$PageCache = new PageCache( NULL );
// Check for cached content & Start caching if needed:
if( ! $PageCache->check() )
{ // Cache miss, we have to generate:
	// --------------------- PAGE LEVEL CACHING SUPPORT ---------------------

// Initialize font-awesome icons and use them as a priority over the glyphicons, @see get_icon()
init_fontawesome_icons( 'fontawesome-glyphicons' );

add_js_for_toolbar();		// Registers all the javascripts needed by the toolbar menu

// Send the predefined cookies:
evo_sendcookies();

headers_content_mightcache( 'text/html' );		// In most situations, you do NOT want to cache dynamic content!
?>
<!DOCTYPE html>
<html lang="<?php locale_lang() ?>">
	<head>
		<base href="<?php echo $baseurl; ?>">
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="robots" content="noindex, follow" />
		<title>b2evolution - Default Page</title>
		<script type="text/javascript" src="rsc/js/jquery.min.js"></script>
		<!-- Bootstrap -->
		<script type="text/javascript" src="rsc/js/bootstrap/bootstrap.min.js"></script>
		<link href="rsc/css/bootstrap/bootstrap.min.css" rel="stylesheet">
		<link href="rsc/build/bootstrap-b2evo_base.bmin.css" rel="stylesheet">
		<link href="rsc/build/b2evo_helper_screens.css" rel="stylesheet">
		<?php include_headlines() /* Add javascript and css files included by plugins and skin */ ?>
	</head>
	<body<?php skin_body_attrs(); ?>>
		<?php
		// ---------------------------- TOOLBAR INCLUDED HERE ----------------------------
		require skin_fallback_path( '_toolbar.inc.php' );
		// ------------------------------- END OF TOOLBAR --------------------------------
		?>
		<div id="skin_wrapper" class="<?php echo show_toolbar() ? 'skin_wrapper_loggedin' : 'skin_wrapper_anonymous' ?>">
		<!-- Start of skin_wrapper -->
		<div class="container">
			<div class="header">
				<nav>
					<ul class="nav nav-pills pull-right">
						<li role="presentation" class="active"><a href="index.php"><?php echo T_('Your site'); ?></a></li>
					</ul>
				</nav>
				<h3 class="text-muted"><a href="http://b2evolution.net/">
					<img src="rsc/img/b2evolution_254x52.png" width="254" height="52" alt="b2evolution" class="b2evolution_plane_logo"
					  srcset="rsc/img/b2evolution_508x104.png 2x,
						 		 rsc/img/b2evolution_762x156.png 3x" /></a>
				</h3>
			</div>

		<!-- InstanceBeginEditable name="Main" -->
		<?php
		/**
		 * @var BlogCache
		 */
		$BlogCache = & get_BlogCache();
		$BlogCache->load_all();

		if( $pagenow == 'index.php' || count( $BlogCache->cache ) == 0 )
		{ // This page is actually included by the index.html page OR there are no blogs
		?>
		<h1><?php echo T_('Welcome to b2evolution') ?></h1>

		<?php
			messages( array(
					'block_start' => '<div class="action_messages">',
					'block_end'   => '</div>',
				) );

			if( count( $BlogCache->cache ) == 0 )
			{ // There is no blog on this system!
				echo '<p><strong>'.T_('b2evolution is installed and ready but you haven\'t created any content collection on this system yet.').'</strong></p>';

				// Display this link to create blog
				echo '<ul class="pager"><li class="next"><a href="'.$admin_url.'?ctrl=collections&amp;action=new&amp;redirect_to='.rawurlencode( regenerate_url() ).'">'.T_( 'Create a first collection' ).' <span aria-hidden="true">&rarr;</span></a></li></ul>';
			}
			else
			{
				echo '<p><strong>'.T_('You have successfully installed b2evolution.').'</strong></p>';

				echo '<p>'.T_('You haven\'t set a default collection yet. Thus, you see this default page.').'</p>';

				if( is_logged_in() && $current_User->check_perm( 'blogs', 'create' ) )
				{ // Display this link only for users who can create blog
				?>
				<ul class="pager"><li class="next"><a href="<?php echo $admin_url ?>?ctrl=collections&amp;tab=site_settings"><?php echo T_( 'Set a default collection' ) ?> <span aria-hidden="true">&rarr;</span></a></li></ul>
				<?php
				}
			}
		}

		if( count( $BlogCache->cache ) )
		{ // There are blogs on this system!
		?>
		<h2><?php echo T_('Collections on this system') ?></h2>

		<ul>
		<?php // --------------------------- BLOG LIST -----------------------------
			for( $l_Blog = & $BlogCache->get_first();
						! is_null( $l_Blog );
						$l_Blog = & $BlogCache->get_next() )
			{ # by uncommenting the following lines you can hide some blogs
				// if( $curr_blog_ID == 2 ) continue; // Hide blog 2...
				echo '<li><strong>';
				printf( T_('Blog #%d'), $l_Blog->ID );
				echo ': <a href="'.$l_Blog->gen_blogurl().'" title="'.$l_Blog->dget( 'shortdesc', 'htmlattr' ).'">';
				$l_Blog->disp( 'name' );
				echo '</a></strong>';
				echo '</li>';
			}
			// ---------------------------------- END OF BLOG LIST ---------------------------------
			?>
		</ul>

		<?php
			if( is_logged_in() && $current_User->check_perm( 'blogs', 'create' ) )
			{ // Display this link only for users who can create blog
				echo '<ul class="pager"><li class="next"><a href="'.$admin_url.'?ctrl=collections&amp;action=new">'.T_( 'Add a new collection' ).' <span aria-hidden="true">&rarr;</span></a></li></ul>';
			}
		}
?>

			<footer class="footer">
				<p class="pull-right"><a href="https://github.com/b2evolution/b2evolution" class="text-nowrap"><?php echo T_('GitHub page'); ?></a></p>
				<p><a href="http://b2evolution.net/" class="text-nowrap">b2evolution.net</a>
				&bull; <a href="http://b2evolution.net/about/recommended-hosting-lamp-best-choices.php" class="text-nowrap"><?php echo T_('Find a host'); ?></a>
				&bull; <a href="http://b2evolution.net/man/" class="text-nowrap"><?php echo T_('Online manual'); ?></a>
				&bull; <a href="http://forums.b2evolution.net" class="text-nowrap"><?php echo T_('Help forums'); ?></a>
				</p>
			</footer>

		</div><!-- /container -->
		<!-- End of skin_wrapper -->
		</div>
	</body>
</html>
<?php
	// --------------------- PAGE LEVEL CACHING SUPPORT ---------------------
	// Save collected cached data if needed:
	$PageCache->end_collect();
}
// --------------------- PAGE LEVEL CACHING SUPPORT ---------------------
?>