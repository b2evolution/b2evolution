<?php
/**
 * This is the main public interface file!
 *
 * This file is NOT mandatory. You can delete it if you want.
 * You can also replace the contents of this file with contents similar to the contents
 * of a_stub.php, a_noskin.php, multiblogs.php, etc.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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

header( 'Content-type: text/html; charset='.$io_charset );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<base href="<?php echo $baseurl ?>" />
	<title><?php echo T_('Default page for b2evolution') ?></title>
	<link href="rsc/css/evo_distrib.css" rel="stylesheet" type="text/css" />
</head>
<body>
<div id="rowheader2">
<h1><a href="http://b2evolution.net/" title="b2evolution: Home"><img src="<?php echo $rsc_url ?>img/b2evolution_logo.png" alt="b2evolution" width="472" height="102" /></a></h1>
<div id="tagline"><?php echo T_('Multilingual multiuser multi-blog engine.') ?></div>
</div>

<h1><?php echo T_('Welcome to b2evolution') ?></h1>

<?php $Messages->disp(); ?>

<?php
	/**
	 * @var BlogCache
	 */
	$BlogCache = & get_Cache('BlogCache');
	$BlogCache->load_all();
	if( count( $BlogCache->cache ) == 0 )
	{	// There is no blog on this system!
		echo '<p><strong>'.T_('b2evolution is installed and ready but you have created no blog on this system yet.').'</strong></p>';
		echo '<p><strong><a href="'.$admin_url.'?ctrl=collections&amp;action=new">'.T_( 'Create a blog now...' ).'</a></strong></p>';
	}
	else
	{
	?>

	<p>
		<?php echo T_('This is the default homepage for b2evolution. It will be displayed as long as you don\'t select a default blog in the general settings.');
		?>
		( <a href="<?php echo $admin_url ?>?ctrl=settings&amp;tab=general#default_blog_ID"><?php echo T_( 'Edit config' ) ?></a> )
	</p>


	<h2><?php echo T_('Blogs on this system') ?>:</h2>
	<ul>
	<?php // --------------------------- BLOG LIST -----------------------------
		for( $curr_blog_ID = blog_list_start();
					$curr_blog_ID != false;
					 $curr_blog_ID = blog_list_next() )
		{ # by uncommenting the following lines you can hide some blogs
			// if( $curr_blog_ID == 2 ) continue; // Hide blog 2...
			echo '<li><strong>';
			printf( T_('Blog #%d'), $curr_blog_ID );
			echo ': <a href="';
			blog_list_iteminfo( 'blogurl', 'raw');
			echo '" title="';
			blog_list_iteminfo( 'shortdesc', 'htmlattr');
			echo '">';
			blog_list_iteminfo( 'name', 'htmlbody');
			echo '</a></strong>';
			echo '</li>';
		}
		// ---------------------------------- END OF BLOG LIST ---------------------------------
		?>
	</ul>

	<h2><?php echo T_('More demos') ?>:</h2>
	<ul>
		<?php
			$first_Blog = & $BlogCache->get_by_ID( 1, false );
			if( !empty( $first_Blog ) )
			{
			?>
				<li><strong><?php echo T_('Stub file') ?>: <a href="a_stub.php"><?php echo T_('Blog #1 called through a stub file') ?></a></strong> &nbsp; (a_stub.php)</li>
				<li><strong><?php echo T_('Custom template') ?>: <a href="a_noskin.php"><?php echo T_('Blog #1 called through a custom template (not a skin)') ?></a></strong> &nbsp; (a_noskin.php)</li>
				<li><strong><?php echo T_('Custom template') ?>: <a href="multiblogs.php"><?php echo T_('Multiple blogs displayed on the same page') ?></a></strong> &nbsp; (multiblogs.php)</li>
			<?php
			}
		?>
		<li><strong><?php echo T_('Custom template') ?>: <a href="summary.php"><?php echo T_('Summary of last posts in all blogs') ?></a></strong> &nbsp; (summary.php)</li>
		<li><strong><?php echo T_('Custom template') ?>: <a href="default.php"><?php echo T_('The page you\'re looking at') ?></a></strong> &nbsp; (default.php)</li>
		<li><strong><?php echo T_('Bonus feature') ?>: <a href="contact.php"><?php echo T_('A standalone form to contact the admin (A contact page for your site)') ?></a></strong> &nbsp; (contact.php)</li>
	</ul>
	<p class="note"><?php echo T_('Please note: those demos do not make use of skins. The only way to change their look and feel is to edit their PHP template. But once, again, remember these are just demos destined to inspire you for your own templates ;)') ?></p>

	<?php
	}
?>

<h2><?php echo T_('Administration') ?>:</h2>
<ul>
	<li><strong><a href="<?php echo $admin_url ?>"><?php echo T_('Go to backoffice!') ?></a></strong></li>
</ul>


<div id="rowfooter">
<a href="http://b2evolution.net/"><?php echo T_('Official website') ?></a> &middot; <a href="http://b2evolution.net/about/license.html"><?php echo T_('GNU GPL license') ?></a>
</div>
<?php debug_info(); // output debug info if requested ?>
</body>
</html>