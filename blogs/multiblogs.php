<?php
/**
 * This is a demo template displaying multiple blogs on the same page
 *
 * If you're new to b2evolution templates or skins, you should not start with this file
 * It will be easier to start examining blog_a.php or noskin_a.php for instance...
 *
 * @package evoskins
 * @subpackage noskin
 */

# First blog will be displayed the regular way (why bother?)
$blog = 1;

# Tell b2evolution you don't want to use evoSkins
# (evoSkins are designed to display only one blog at once + optionnaly a linkblog)
$skin = '';

# This setting retricts posts to those published, thus hiding drafts.
# You should not have to change this.
$show_statuses = array();

# Additionnaly, you can set other values (see URL params in the manual)...
# $order = 'ASC'; // This for example would display the blog in chronological order...

# Tell b2evolution not to redirect. This is necessary only if "301" for homepage is checked and the blog URL is set
# to something else than this page -- which is the case on demo installs.
# For production systems, properly set the blog URL , then remove the line below:
$redir = 'no';

/**
 * Let b2evolution handle the query string and load the blog data:
 */
require_once dirname(__FILE__).'/conf/_config.php';

require_once $inc_path.'_blog_main.inc.php';

// Make sure includes will check in the current folder!
$ads_current_skin_path = dirname(__FILE__).'/';

// Force $disp to 'posts' for all blogs on this template
$disp = 'posts';


# Now, below you'll find the magic template...


// --------------------- PAGE LEVEL CACHING SUPPORT ---------------------
// Note: This is totally optional. General caching must be enabled in Global settings, otherwise this will do nothing.
// Delete this block if you don't care about page level caching. Don't forget to delete the matching section at the end of the page.
load_class( '_core/model/_pagecache.class.php', 'PageCache' );
$PageCache = new PageCache( NULL );
// Check for cached content & Start caching if needed:
if( ! $PageCache->check() )
{	// Cache miss, we have to generate:
	// --------------------- PAGE LEVEL CACHING SUPPORT ---------------------


// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );

// Add CSS:
require_css( 'basic_styles.css', 'rsc_url' ); // the REAL basic styles
require_css( 'basic.css', 'rsc_url' ); // Basic styles
// Bootstrap
require_js( '#bootstrap#', 'rsc_url' );
require_css( '#bootstrap_css#', 'rsc_url' );
require_css( '#bootstrap_theme_css#', 'rsc_url' );
require_css( 'bootstrap/b2evo.css', 'rsc_url' );
add_css_headline( '
.navbar-collapse .nav {
	margin: 0;
}
.jumbotron {
	margin-top: 52px;
}
.jumbotron.loggedin {
	margin-top: 75px;
}
.bPost {
	margin: 0 0 2em 0;
}
div.image_block {
	margin: 0 10px 10px 0;
	display: inline-block;
}
div.image_block img {
	margin: 0;
}
/* Multiblogs blog c fix */
@media (min-width: 992px) and (max-width: 1199px) {
	.col-md-12.collection-c {
		clear: both;
	}
}' );

// Set this var to TRUE in order to use glyph icons, @see get_icon()
$use_glyphicons = true;

add_js_for_toolbar();		// Registers all the javascripts needed by the toolbar menu

// Functions to work with AJAX response data
require_js( '#jquery#', 'rsc_url' );
require_js( '#jqueryUI#', 'rsc_url' );
require_js( 'ajax.js', 'rsc_url' );
// Colorbox (a lightweight Lightbox alternative) allows to zoom on images and do slideshows with groups of images:
require_js_helper( 'colorbox' );

headers_content_mightcache( 'text/html' );		// In most situations, you do NOT want to cache dynamic content!
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>"><!-- InstanceBegin template="/Templates/Standard.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
<!-- InstanceBeginEditable name="doctitle" -->
	<title><?php
		// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
		request_title( array(
			'title_before'=> '',
			'title_after' => ' - ',
			'title_none'  => '',
			'glue'        => ' - ',
			'format'      => 'htmlhead',
		) );
		// ------------------------------ END OF REQUEST TITLE -----------------------------
	?>Multiblog Demo</title>
	<!-- InstanceEndEditable -->
<!-- InstanceBeginEditable name="head" -->
	<?php skin_base_tag(); /* You're not using any skin here but this won't hurt. However it will be very helpful to have this here when you make the switch to a skin! */ ?>
	<?php skin_description_tag(); ?>
	<?php skin_keywords_tag(); ?>
	<meta name="generator" content="b2evolution <?php echo $app_version ?>" /> <!-- Please leave this for stats -->
	<link rel="alternate" type="application/rss+xml" title="RSS 2.0" href="<?php $Blog->disp( 'rss2_url', 'raw' ) ?>" />
	<link rel="alternate" type="application/atom+xml" title="Atom" href="<?php $Blog->disp( 'atom_url', 'raw' ) ?>" />
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
?>
<!-- InstanceEndEditable -->
<!-- InstanceBeginEditable name="NavBar" -->
<?php
	// --------------------------------- START OF BLOG LIST --------------------------------
	skin_widget( array(
						// CODE for the widget:
						'widget' => 'colls_list_public',
						// Optional display params
						'block_start' => '<div class="navbar navbar-inverse navbar-fixed-top'.( show_toolbar() ? ' skin_wrapper_loggedin' : '' ).'" role="navigation">'
							.'<div class="container">'
								.'<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse"> 
										<span class="icon-bar"></span>
										<span class="icon-bar"></span>
										<span class="icon-bar"></span>
									</button>'
								.'<div class="navbar-header"><a href="http://b2evolution.net/" class="navbar-brand">b2evolution</a></div>'
								.'<div class="navbar-collapse collapse">',
						'block_end' => '</div></div></div>',
						'block_display_title' => false,
						'list_start' => '<ul class="nav navbar-nav">',
						'list_end' => '</ul>',
						'item_start' => '<li>',
						'item_end' => '<li>',
						'item_selected_start' => '<li class="selected">',
						'item_selected_end' => '</li>',
						'link_selected_class' => '',
						'link_default_class' => '',
				) );
	// ---------------------------------- END OF BLOG LIST ---------------------------------
?>
<!-- InstanceEndEditable -->

<div class="jumbotron<?php echo show_toolbar() ? ' loggedin' : ''; ?>">
	<div class="container">
		<h1><!-- InstanceBeginEditable name="PageTitle" --><?php echo T_('Multiblog demo') ?><!-- InstanceEndEditable --></h1>
		<p><!-- InstanceBeginEditable name="SubTitle" --><?php echo T_('This demo template displays 3 blogs at once') ?><!-- InstanceEndEditable --></p>
	</div>
</div>


<div class="container">

<div class="row"><!-- InstanceBeginEditable name="Main" -->

<!-- =================================== START OF MAIN AREA =================================== -->

	<div class="col-md-7 col-lg-5">
		<h2>#1: <a href="<?php $Blog->disp( 'blogurl', 'raw' ) ?>"><?php echo $Blog->disp( 'name', 'htmlbody' ) ?></a></h2>

	<?php
		// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
		request_title( array(
				'title_before'=> '<h2>',
				'title_after' => '</h2>',
				'title_none'  => '',
				'glue'        => ' - ',
				'title_single_disp' => true,
				'format'      => 'htmlbody',
			) );
		// ------------------------------ END OF REQUEST TITLE -----------------------------
	?>

	<?php
	if( $disp != 'front' )
	{ // ------------------------------------ START OF POSTS ----------------------------------------
		// Display message if no post:
		display_if_empty();

		while( $Item = & mainlist_get_item() )
		{ // For each blog post, do everything below up to the closing curly brace "}"
			?>

		<div id="<?php $Item->anchor_id() ?>" class="bPost bPost<?php $Item->status_raw() ?>" lang="<?php $Item->lang() ?>">

			<h4 class="bTitle">
				<?php
					$Item->permanent_link( array(
							'text' => '#icon#',
						) );
				?>
				<?php $Item->title(); ?>
			</h4>

			<?php
				// ---------------------- POST CONTENT INCLUDED HERE ----------------------
				skin_include( '_item_content.inc.php', array(
						'image_size' => 'fit-400x320',
					) );
				// Note: You can customize the default item feedback by copying the generic
				// /skins/_item_feedback.inc.php file into the current skin folder.
				// -------------------------- END OF POST CONTENT -------------------------
			?>

			<?php
				// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
				skin_include( '_item_feedback.inc.php', array(
						'before_section_title' => '<h4>',
						'after_section_title'  => '</h4>',
					) );
				// Note: You can customize the default item feedback by copying the generic
				// /skins/_item_feedback.inc.php file into the current skin folder.
				// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------
			?>
		</div>
	<?php
		} // ---------------------------------- END OF POSTS ------------------------------------
	?>

	<?php
		// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
		mainlist_page_links( array(
				'block_start' => '<div class="center"><ul class="pagination">',
				'block_end' => '</ul></div>',
				'page_current_template' => '<span><b>$page_num$</b></span>',
				'page_item_before' => '<li>',
				'page_item_after' => '</li>',
				'prev_text' => '&lt;&lt;',
				'next_text' => '&gt;&gt;',
				'list_span' => 4,
			) );
		// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------
	}
	?>

	<?php
		// -------------- MAIN CONTENT TEMPLATE INCLUDED HERE (Based on $disp) --------------
		skin_include( '$disp$', array(
				'disp_posts'  => '',		// We already handled this case above
				'disp_single' => '',		// We already handled this case above
				'disp_page'   => '',		// We already handled this case above
			) );
		// Note: you can customize any of the sub templates included here by
		// copying the matching php file into your skin directory.
		// ------------------------- END OF MAIN CONTENT TEMPLATE ---------------------------
	?>

	</div>

<!-- =================================== START OF BLOG B =================================== -->

	<div class="col-md-5 col-lg-5">
		<?php
		$BlogCache = & get_BlogCache();
		$Blog_B = & $BlogCache->get_by_ID( 2, false );
		if( empty($Blog_B) )
		{
			echo sprintf( T_('Blog #%d doesn\'t seem to exist.'), 2 );
		}
		else
		{
			?>

			<h2>#2: <a href="<?php $Blog_B->disp( 'blogurl', 'raw' ) ?>"><?php echo $Blog_B->disp( 'name', 'htmlbody' ) ?></a></h2>
			<?php
			$list_prefix = 'blog2_';
			$BlogBList = new ItemList2( $Blog_B, $Blog_B->get_timestamp_min(), $Blog_B->get_timestamp_max(), $posts, 'ItemCache', $list_prefix );

			$BlogBList->set_filters( array(
					'authors' => $author,
					'ymdhms' => $m,
					'week' => $w,
					'order' => $order,
					'orderby' => $orderby,
					'unit' => $unit,
					'page' => param( $list_prefix.'paged', 'integer', 1, true ),
				) );

			// Run the query:
			$BlogBList->query();

			while( $Item = & $BlogBList->get_item() )
			{
				?>
				<div id="<?php $Item->anchor_id() ?>" class="bPost bPostSide<?php $Item->status_raw() ?>" lang="<?php $Item->lang() ?>">

					<h4 class="bTitle">
						<?php
							$Item->permanent_link( array(
									'text' => '#icon#',
								) );
						?>
						<?php $Item->title(); ?>
					</h4>

					<?php
						// ---------------------- POST CONTENT INCLUDED HERE ----------------------
						skin_include( '_item_content.inc.php', array(
								'image_size' => 'fit-400x320',
							) );
						// Note: You can customize the default item feedback by copying the generic
						// /skins/_item_feedback.inc.php file into the current skin folder.
						// -------------------------- END OF POST CONTENT -------------------------
					?>
				</div>
				<?php
			}

			// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
			mainlist_page_links( array(
					'block_start' => '<div class="center"><ul class="pagination">',
					'block_end' => '</ul></div>',
					'page_current_template' => '<span><b>$page_num$</b></span>',
					'page_item_before' => '<li>',
					'page_item_after' => '</li>',
					'prev_text' => '&lt;&lt;',
					'next_text' => '&gt;&gt;',
					'list_span' => 4,
				), $BlogBList );
			// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------
		}
		?>

	</div>

<!-- =================================== START OF BLOG C =================================== -->

	<div class="col-md-12 col-lg-2 collection-c">
		<?php
		$Blog_roll = & $BlogCache->get_by_ID( 4, false );
		if( empty($Blog_roll) )
		{
			echo sprintf( T_('Blog #%d doesn\'t seem to exist.'), 3 );
		}
		else
		{
		?>
		<h2>#3: <a href="<?php $Blog_roll->disp( 'blogurl', 'raw' ) ?>"><?php echo $Blog_roll->disp( 'name', 'htmlbody' ) ?></a></h2>
		<?php
		$LinkblogList = new ItemList2( $Blog_roll, $Blog_roll->get_timestamp_min(), $Blog_roll->get_timestamp_max(), $posts );

		$LinkblogList->set_filters( array(
				'authors' => $author,
				'ymdhms' => $m,
				'week' => $w,
				'order' => $order,
				'orderby' => $orderby,
				'unit' => $unit,
			) );

		// Run the query:
		$LinkblogList->query();

		while( $Item = & $LinkblogList->get_item() )
		{
			?>
			<div id="<?php $Item->anchor_id() ?>" class="bPostSide bPostSide<?php $Item->status_raw() ?>" lang="<?php $Item->lang() ?>">
				<h4 class="bTitle">
					<?php
						$Item->permanent_link( array(
								'text' => '#icon#',
							) );
					?>
					<?php $Item->title(); ?>
				</h4>
				<div class="bText">
					<?php
						// Display images that are linked to this post:
						$Item->images( array(
								'before'                     => '<div class="center">',
								'before_image'               => '<div class="image_block">',
								'after_image'                => '</div>',
								'after'                      => '</div>',
								'image_size'                 => 'fit-160x120',
								'restrict_to_image_position' => 'teaser,aftermore',
							) );
					?>
				</div>
			</div>
			<?php
		}
		}
		?>

<!-- =================================== END OF BLOG C =================================== -->

	</div>

</div>

<!-- InstanceEndEditable -->

<div class="row">
	<div class="col-lg-12 well">This is a demo page for <a href="http://b2evolution.net/">b2evolution</a>.</div>
</div>

</div>

</body>
<!-- InstanceEnd --></html>
<?php
	// --------------------- PAGE LEVEL CACHING SUPPORT ---------------------
	// Save collected cached data if needed:
	$PageCache->end_collect();
}
// --------------------- PAGE LEVEL CACHING SUPPORT ---------------------
?>