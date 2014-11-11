<?php
/**
 * This is a demo template displaying a summary of the last posts in each blog
 *
 * If you're new to b2evolution templates or skins, you should not start with this file
 * It will be easier to start examining blog_a.php or noskin_a.php for instance...
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage noskin
 */

/**
 * Check this: we are requiring _main.inc.php INSTEAD of _blog_main.inc.php because we are not
 * trying to initialize any particular blog
 */
require_once dirname(__FILE__).'/conf/_config.php';

require_once $inc_path.'_main.inc.php';

load_funcs( 'skins/_skin.funcs.php' );


// --------------------- PAGE LEVEL CACHING SUPPORT ---------------------
// Note: This is totally optional. General caching must be enabled in Global settings, otherwise this will do nothing.
// Delete this block if you don't care about page level caching. Don't forget to delete the matching section at the end of the page.
load_class( '_core/model/_pagecache.class.php', 'PageCache' );
$PageCache = new PageCache( NULL );
// Check for cached content & Start caching if needed:
if( ! $PageCache->check() )
{	// Cache miss, we have to generate:
	// --------------------- PAGE LEVEL CACHING SUPPORT ---------------------


// Add CSS:
// require_css( 'basic_styles.css', 'blog' ); // the REAL basic styles
// require_css( 'basic.css', 'blog' ); // Basic styles
// require_css( 'blog_base.css', 'blog' ); // Default styles for the blog navigation
// require_css( 'item_base.css', 'blog' ); // Default styles for the post CONTENT
// require_css( 'b2evo_base.bundle.css', 'blog' ); // Concatenation of the above
require_css( 'b2evo_base.bmin.css', 'blog' ); // Concatenation + Minifaction of the above

require_css( 'fp02.css', 'rsc_url' );

add_js_for_toolbar();		// Registers all the javascripts needed by the toolbar menu

headers_content_mightcache( 'text/html' );		// In most situations, you do NOT want to cache dynamic content!
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>"><!-- InstanceBegin template="/Templates/Standard.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
<!-- InstanceBeginEditable name="doctitle" -->
<title><?php echo T_('Summary Demo'); ?></title>
<!-- InstanceEndEditable -->
<!-- InstanceBeginEditable name="head" -->
<?php include_headlines() /* Add javascript and css files included by plugins and skin */ ?>
<!-- InstanceEndEditable -->
</head>
<body<?php skin_body_attrs(); ?>>
<!-- InstanceBeginEditable name="ToolBar" -->
<?php
	// ---------------------------- TOOLBAR INCLUDED HERE ----------------------------
	require $skins_path.'_toolbar.inc.php';
	// ------------------------------- END OF TOOLBAR --------------------------------
	echo "\n";
	if( show_toolbar() )
	{
		echo '<div id="skin_wrapper" class="skin_wrapper_loggedin">';
	}
	else
	{
		echo '<div id="skin_wrapper" class="skin_wrapper_anonymous">';
	}
	echo "\n";
?>
<!-- InstanceEndEditable -->
<div class="pageHeader">
<!-- InstanceBeginEditable name="NavBar2" -->
<?php
	// --------------------------------- START OF BLOG LIST --------------------------------
	skin_widget( array(
						// CODE for the widget:
						'widget' => 'colls_list_public',
						// Optional display params
						'block_start' => '<div class="NavBar">',
						'block_end' => '</div>',
						'block_display_title' => false,
						'list_start' => '',
						'list_end' => '',
						'item_start' => '',
						'item_end' => '',
						'item_selected_start' => '',
						'item_selected_end' => '',
						'link_selected_class' => 'NavButton2',
						'link_default_class' => 'NavButton2',
				) );
	// ---------------------------------- END OF BLOG LIST ---------------------------------
?>
<!-- InstanceEndEditable -->
<div class="pageTitle">
<h1 id="pageTitle"><!-- InstanceBeginEditable name="PageTitle" --><?php echo T_('Summary Demo') ?><!-- InstanceEndEditable --></h1>
</div>
</div>


<div class="pageSubTitle"><!-- InstanceBeginEditable name="SubTitle" --><?php echo T_('This demo template displays a summary of last posts in all blogs') ?><!-- InstanceEndEditable --></div>


<div class="main"><!-- InstanceBeginEditable name="Main" -->

<!-- =================================== START OF MAIN AREA =================================== -->


<?php // --------------------------- BLOG LIST -----------------------------

	load_class( 'items/model/_itemlist.class.php', 'ItemList' );

	$BlogCache = & get_BlogCache();

	$blog_array = $BlogCache->load_public();

	foreach( $blog_array as $blog )
	{	// Loop through all public blogs:
		# by uncommenting the following lines you can hide some blogs
		// if( $blog == 2 ) continue; // Hide blog 2...

    /**
		 * @var Blog
		 */
		$l_Blog = & $BlogCache->get_by_ID( $blog );

		?>
		<h3><a href="<?php echo $l_Blog->gen_blogurl(); ?>" title="<?php $l_Blog->disp( 'shortdesc', 'htmlattr' ); ?>"><?php $l_Blog->disp( 'name', 'htmlattr' ); ?></a></h3>
		<ul>
		<?php	// Get the 3 last posts for each blog:

			$BlogBList = new ItemList2( $l_Blog, $l_Blog->get_timestamp_min(), $l_Blog->get_timestamp_max(), 3 );

			$BlogBList->set_filters( array(
					'order' => 'DESC',
					'unit' => 'posts',
				) );

			// Run the query:
			$BlogBList->query();

			while( $Item = & $BlogBList->get_item() )
			{
				?>
				<li lang="<?php $Item->lang() ?>">
					<?php
						$Item->issue_date( array(
								'before'      => ' ',
								'after'       => ' ',
								'date_format' => '#',
							) );

						$Item->title( array(
								'link_type' => 'permalink',
							) );
					?>
					<span class="small">[<?php $Item->lang() ?>]</span>
				</li>
				<?php
			}
			?>
			<li><a href="<?php echo $l_Blog->gen_blogurl(); ?>"><?php echo T_('More posts...') ?></a></li>
		</ul>
		<?php
	}
	// ---------------------------------- END OF BLOG LIST --------------------------------- ?>
<!-- InstanceEndEditable --></div>
<div class="footer">
This is a demo page for <a href="http://b2evolution.net/">b2evolution</a>.
<!-- InstanceBeginEditable name="Baseline" -->
<?php echo '</div>' ?>
<!-- InstanceEndEditable --></div>
</body>
<!-- InstanceEnd --></html>
<?php
	// --------------------- PAGE LEVEL CACHING SUPPORT ---------------------
	// Save collected cached data if needed:
	$PageCache->end_collect();
}
// --------------------- PAGE LEVEL CACHING SUPPORT ---------------------
?>