<?php
/**
 * This file will display a blog, WITHOUT using skins.
 *
 * This file will set some display parameters and then display the blog in a template.
 *
 * Note: You only need to use this file for advanced use/customization of b2evolution.
 * Most of the time, calling your blog through index.php with a skin will be enough.
 * You should try to customize a skin before thrying to use this fle.
 *
 * Same display without using skins: a_stub.php
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage noskin
 */

# First, select which blog you want to display here!
# You can find these numbers in the back-office under the Blogs section.
# You can also create new blogs over there. If you do, you may duplicate this file for the new blog.
$blog = 1;

# Tell b2evolution you don't want to use evoSkins for this template:
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

require $inc_path.'_blog_main.inc.php';

// Make sure includes will check in the current folder!
$ads_current_skin_path = dirname(__FILE__).'/';


# Now, below you'll find the main template...

// --------------------- PAGE LEVEL CACHING SUPPORT ---------------------
// Note: This is totally optional. General caching must be enabled in Global settings, otherwise this will do nothing.
// Delete this block if you don't care about page level caching. Don't forget to delete the matching section at the end of the page.
load_class( '_core/model/_pagecache.class.php', 'PageCache' );
$PageCache = new PageCache( NULL );
// Check for cached content & Start caching if needed:
if( ! $PageCache->check() )
{	// Cache miss, we have to generate:
	// --------------------- PAGE LEVEL CACHING SUPPORT ---------------------

if( $disp == 'front' )
{ // Force $disp to 'posts' for all blogs on this template
	$disp = 'posts';
}

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );

// Add CSS:
require_css( 'basic_styles.css', 'rsc_url' ); // the REAL basic styles
require_css( 'basic.css', 'rsc_url' ); // Basic styles
require_css( 'blog_base.css', 'rsc_url' ); // Default styles for the blog navigation
require_css( 'item_base.css', 'rsc_url' ); // Default styles for the post CONTENT
require_css( 'fp02.css', 'rsc_url' );

add_js_for_toolbar();		// Registers all the javascripts needed by the toolbar menu

// Functions to work with AJAX response data
require_js( '#jquery#', 'rsc_url' );
require_js( '#jqueryUI#', 'rsc_url' );
require_js( 'ajax.js', 'rsc_url' );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>"><!-- InstanceBegin template="/Templates/Standard.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
<!-- InstanceBeginEditable name="doctitle" -->
	<title><?php
		// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
		request_title( array(
			'auto_pilot'      => 'seo_title',
		) );
		// ------------------------------ END OF REQUEST TITLE -----------------------------
	?></title>
<!-- InstanceEndEditable -->
<!-- InstanceBeginEditable name="head" -->
	<?php skin_content_meta(); /* Charset for static pages */ ?>
	<?php $Plugins->trigger_event( 'SkinBeginHtmlHead' ); ?>
	<?php skin_base_tag(); /* You're not using any skin here but this won't hurt. However it will be very helpfull to have this here when you make the switch to a skin! */ ?>
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
<h1 id="pageTitle"><!-- InstanceBeginEditable name="PageTitle" --><?php $Blog->disp( 'name', 'htmlbody' ) ?><!-- InstanceEndEditable --></h1>
</div>
</div>


<div class="pageSubTitle"><!-- InstanceBeginEditable name="SubTitle" --><?php $Blog->disp( 'tagline', 'htmlbody' ) ?><!-- InstanceEndEditable --></div>


<div class="main"><!-- InstanceBeginEditable name="Main" -->

<div class="bPosts">

	<?php
		// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
		messages( array(
			'block_start' => '<div class="action_messages">',
			'block_end'   => '</div>',
		) );
		// --------------------------------- END OF MESSAGES ---------------------------------
	?>

	<?php
	if( $disp != 'front' )
	{
		// ------------------- PREV/NEXT POST LINKS (SINGLE POST MODE) -------------------
		item_prevnext_links( array(
				'block_start' => '<table class="prevnext_post"><tr>',
				'prev_start'  => '<td>',
				'prev_end'    => '</td>',
				'next_start'  => '<td class="right">',
				'next_end'    => '</td>',
				'block_end'   => '</tr></table>',
			) );
		// ------------------------- END OF PREV/NEXT POST LINKS -------------------------
	?>

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
		// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
		mainlist_page_links( array(
				'block_start' => '<p class="center"><strong>',
				'block_end' => '</strong></p>',
			) );
		// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------
	?>


	<!-- =================================== START OF MAIN AREA =================================== -->

	<?php // ------------------------------------ START OF POSTS ----------------------------------------
		// Display message if no post:
		display_if_empty();

		while( $Item = & mainlist_get_item() )
		{	// For each blog post, do everything below up to the closing curly brace "}"
		?>

		<?php
			// ------------------------------ DATE SEPARATOR ------------------------------
			$MainList->date_if_changed( array(
					'before'      => '<h2>',
					'after'       => '</h2>',
					'date_format' => '#',
				) );
		?>
		<div id="<?php $Item->anchor_id() ?>" class="bPost bPost<?php $Item->status_raw() ?>" lang="<?php $Item->lang() ?>">

			<div class="bSmallHead">
			<?php
				$Item->permanent_link( array(
						'text' => '#icon#',
					) );
			?>
			<?php
				$Item->issue_time(); // Post issue time
			?>
			<?php
				$Item->categories( array(
					'before'          => ', '.T_('Categories').': ',
					'after'           => ' ',
					'include_main'    => true,
					'include_other'   => true,
					'include_external'=> true,
					'link_categories' => true,
				) );
			?>
			</div>
			<h3 class="bTitle"><?php $Item->title(); ?></h3>

			<?php
				// ---------------------- POST CONTENT INCLUDED HERE ----------------------
				skin_include( '_item_content.inc.php', array(
						'image_size'	=>	'fit-400x320',
					) );
				// Note: You can customize the default item feedback by copying the generic
				// /skins/_item_feedback.inc.php file into the current skin folder.
				// -------------------------- END OF POST CONTENT -------------------------
			?>

			<?php
				// List all tags attached to this post:
				$Item->tags( array(
						'before' =>         '<div class="bSmallPrint">'.T_('Tags').': ',
						'after' =>          '</div>',
						'separator' =>      ', ',
					) );
			?>

			<div class="bSmallPrint">
				<?php
					// Link to comments, trackbacks, etc.:
					$Item->feedback_link( array(
									'type' => 'comments',
									'link_before' => '',
									'link_after' => ' &bull; ',
									'link_text_zero' => '#',
									'link_text_one' => '#',
									'link_text_more' => '#',
									'link_title' => '#',
									'use_popup' => false,
								) );
				 ?>
				<?php
					// Link to comments, trackbacks, etc.:
					$Item->feedback_link( array(
									'type' => 'trackbacks',
									'link_before' => '',
									'link_after' => ' &bull; ',
									'link_text_zero' => '#',
									'link_text_one' => '#',
									'link_text_more' => '#',
									'link_title' => '#',
									'use_popup' => false,
								) );
				 ?>

				<?php	$Item->permanent_link(); ?>
			</div>
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
	<?php } // ---------------------------------- END OF POSTS ------------------------------------ ?>

	<?php
		// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
		mainlist_page_links( array(
				'block_start' => '<p class="center"><strong>',
				'block_end' => '</strong></p>',
				'prev_text' => '&lt;&lt;',
				'next_text' => '&gt;&gt;',
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

<!-- =================================== START OF SIDEBAR =================================== -->

<div class="bSideBar">

	<div class="bSideItem">

		<h3><?php
			// BLOG TITLE:
			$Blog->disp( 'name', 'htmlbody' );
			// Note: we could have called the coll_title widget instead, but that would be overkill.
		?></h3>

		<p><?php
			// BLOG LONG DESCRIPTION:
			$Blog->disp( 'longdesc', 'htmlbody' );
			// Note: we could have called the coll_longdesc widget instead, but that would be overkill.
		?></p>

		<?php
			// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
			mainlist_page_links( array(
					'block_start' => '<p class="center"><strong>',
					'block_end' => '</strong></p>',
					'links_format' => '$prev$ :: $next$',
   				'prev_text' => '&lt;&lt; '.T_('Previous'),
   				'next_text' => T_('Next').' &gt;&gt;',
				) );
			// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------
		?>

		<?php
			// --------------------------------- START OF COMMON LINKS --------------------------------
			// Call the coll_common_links widget:
			skin_widget( array(
								// CODE for the widget:
								'widget' => 'coll_common_links',
								// Optional display params:
								'show_recently' => true,
								'show_archives' => true,
								'show_categories' => false,
								'show_latestcomments' => false,
								'list_start' => '<ul>',
								'list_end' => '</ul>',
								'item_start' => '<li>',
								'item_end' => '</li>',
						) );
			// ---------------------------------- END OF COMMON LINKS ---------------------------------
		?>

		<?php
			// ------------------------------- START OF CALENDAR ---------------------------------
			// Call the Calendar plugin (if installed):
			$Plugins->call_by_code( 'evo_Calr', array(	// Params follow:
					'block_start' => '',
					'block_end' => '',
					'displaycaption' => true,
					'linktomontharchive' => false,
				) );
			// -------------------------------- END OF CALENDAR ----------------------------------
		?>
	</div>

	<?php
		// --------------------------------- START OF SEARCH FORM --------------------------------
		// Call the coll_search_form widget:
		skin_widget( array(
				// CODE for the widget:
				'widget' => 'coll_search_form',
				// Optional display params:
				'block_start' => '<div class="bSideItem">',
				'block_end' => '</div>',
				'block_title_start' => '<h3 class="sideItemTitle">',
				'block_title_end' => '</h3>',
			) );
		// ---------------------------------- END OF SEARCH FORM ---------------------------------
	?>

	<?php
		// --------------------------------- START OF CATEGORY LIST --------------------------------
		skin_widget( array(
				// CODE for the widget:
				'widget' => 'coll_category_list',
				// Optional display params
				'block_start' => '<div class="bSideItem">',
				'block_end' => '</div>',
				'block_title_start' => '<h3 class="sideItemTitle">',
				'block_title_end' => '</h3>',
			) );
		// ---------------------------------- END OF CATEGORY LIST ---------------------------------
	?>

	<?php
		// -------------------------- ARCHIVES INSERTED HERE -----------------------------
		$Plugins->call_by_code( 'evo_Arch', array(
				'block_start' => '<div class="bSideItem">',
				'block_end' => '</div>',
				'block_title_start' => '<h3>',
				'block_title_end' => '</h3>',
			) );
		// ------------------------------ END OF ARCHIVES --------------------------------
	?>

	<?php
		// --------------------------------- START OF LINKBLOG --------------------------------
		// Call the coll_search_form widget:
		skin_widget( array(
							// CODE for the widget:
							'widget' => 'linkblog',
							// Optional display params:
							'block_start' => '<div class="bSideItem">',
							'block_end' => '</div>',
							'block_title_start' => '<h3 class="sideItemTitle">',
							'block_title_end' => '</h3>',
					) );
		// ---------------------------------- END OF LINKBLOG ---------------------------------
	?>

	<?php
		// --------------------------------- START OF USER TOOLS --------------------------------
		skin_widget( array(
				// CODE for the widget:
				'widget' => 'user_tools',
				// Optional display params
				'block_start' => '<div class="bSideItem">',
				'block_end' => '</div>',
				'block_title_start' => '<h3 class="sideItemTitle">',
				'block_title_end' => '</h3>',
			) );
		// ---------------------------------- END OF USER TOOLS ---------------------------------
	?>

	<?php
		// --------------------------------- START OF XML FEEDS --------------------------------
		skin_widget( array(
				// CODE for the widget:
				'widget' => 'coll_xml_feeds',
				// Optional display params
				'block_start' => '<div class="bSideItem">',
				'block_end' => '</div>',
				'block_title_start' => '<h3 class="sideItemTitle">',
				'block_title_end' => '</h3>',
			) );
		// ---------------------------------- END OF XML FEEDS ---------------------------------
	?>

	<?php
		// Please help us promote b2evolution and leave this logo on your blog:
		powered_by( array(
				'block_start' => '<div class="powered_by">',
				'block_end'   => '</div>',
				// Check /rsc/img/ for other possible images -- Don't forget to change or remove width & height too
				'img_url'     => '$rsc$img/powered-by-b2evolution-120t.gif',
				'img_width'   => 120,
				'img_height'  => 32,
			) );
	?>

</div>
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