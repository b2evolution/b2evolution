<?php
	/**
	 * This is the main template. It displays the blog.
	 *
	 * However this file is not meant to be called directly.
	 * It is meant to be called automagically by b2evolution.
	 * To display a blog, you should call a stub file instead, for example:
	 * /blogs/index.php or /blogs/blog_b.php
	 *
	 * b2evolution - {@link http://b2evolution.net/}
	 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
	 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
	 *
	 * @package evoskins
	 * @subpackage custom
	 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

skin_content_header();	// Sets charset!
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<?php skin_content_meta(); /* Charset for static pages */ ?>
	<?php $Plugins->trigger_event( 'SkinBeginHtmlHead' ); ?>
	<title><?php
		$Blog->disp('name', 'htmlhead');
		request_title( ' - ', '', ' - ', 'htmlhead' );
	?></title>
	<?php skin_base_tag(); /* Base URL for this skin. You need this to fix relative links! */ ?>
	<meta name="description" content="<?php $Blog->disp( 'shortdesc', 'htmlattr' ); ?>" />
	<meta name="keywords" content="<?php $Blog->disp( 'keywords', 'htmlattr' ); ?>" />
	<meta name="generator" content="b2evolution <?php echo $app_version ?>" /> <!-- Please leave this for stats -->
	<link rel="alternate" type="text/xml" title="RSS 2.0" href="<?php $Blog->disp( 'rss2_url', 'raw' ) ?>" />
	<link rel="alternate" type="application/atom+xml" title="Atom" href="<?php $Blog->disp( 'atom_url', 'raw' ) ?>" />
	<link rel="stylesheet" href="rsc/styles.css" type="text/css" />
	<link rel="stylesheet" type="text/css" href="rsc/nifty_corners.css" />
	<link rel="stylesheet" type="text/css" href="rsc/nifty_print.css" media="print" />
	<script type="text/javascript" src="rsc/nifty_corners.js"></script>
	<script type="text/javascript">
		window.onload=function()
		{
			if(!NiftyCheck())
					return;
			Rounded("div.outerwrap","all","transparent","#fff","");
			Rounded("div.posts","all","transparent","#fff","");
			Rounded("div.bSideBar","all","transparent","#fff","");
			Rounded("div.bTitle","top","#fff","#06a3c4","smooth");
		}
	</script>
</head>
<body>
<div class="wrapper">

<div class="outerwrap">
<div class="innerwrap">

<?php
	// --------------------------- BLOG LIST INCLUDED HERE -----------------------------
	require dirname(__FILE__).'/_bloglist.php';
	// ------------------------------- END OF BLOG LIST --------------------------------
?>

<div class="pageHeader">
	<?php
		// Display container and contents:
		$Skin->container( NT_('Header'), array(
				// The following params will be used as defaults for widgets included in this container:
				'block_start' => '<div class="$wi_class$">',
				'block_end' => '</div>',
				'block_title_start' => '<h1>',
				'block_title_end' => '</h1>',
			) );
	?>
</div>

</div>
</div>

<div class="posts">
<div class="innerwrap">

<?php
	// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
	if( empty( $preview ) ) $Messages->disp( );
	// fp>> TODO: I think we should rather forget the messages here so they don't get displayed again.
	// --------------------------------- END OF MESSAGES ---------------------------------
?>

<?php request_title( '<h2>', '</h2>' ) ?>

<!-- =================================== START OF MAIN AREA =================================== -->

<?php // ------------------------------------ START OF POSTS ----------------------------------------
	if( isset($MainList) ) $MainList->display_if_empty(); // Display message if no post

	if( isset($MainList) ) while( $Item = & $MainList->get_item() )
	{
		$MainList->date_if_changed();
		locale_temp_switch( $Item->locale ); // Temporarily switch to post locale
	?>

	<div class="bTitle"><h3 class="bTitle"><?php $Item->title(); ?></h3></div>

	<div class="bPost" lang="<?php $Item->lang() ?>">
		<?php
			$Item->anchor(); // Anchor for permalinks to refer to
		?>

		<div class="bSmallHead">
		<?php
			$Item->permanent_link( '#icon#' );
			echo ' ';
			$Item->issue_time();
			echo ', ', T_('Categories'), ': ';
			$Item->categories();
			echo ' &nbsp; ';
		?>
		</div>

		<?php
			// Display images that are linked to this post:
			$Item->images( array(
					'before' =>              '<div class="bImages">',
					'before_image' =>        '<div class="image_block">',
					'before_image_legend' => '<div class="image_legend">',
					'after_image_legend' =>  '</div>',
					'after_image' =>         '</div>',
					'after' =>               '</div>',
					'image_size' =>          'fit-320x320'
				) );
		?>

		<div class="bText">
			<?php $Item->content(); ?>
			<?php
				// Links to post pages (for multipage posts):
				$Item->page_links( '<p class="right">'.T_('Pages:').' ', '</p>', ' &middot; ' );
			?>
		</div>

		<div class="bSmallPrint">
			<?php $Item->permanent_link(); ?>
			<?php $Item->feedback_link( 'comments', ' &bull; ' ) // Link to comments ?>
			<?php $Item->feedback_link( 'trackbacks', ' &bull; ' ) // Link to trackbacks ?>

			<?php $Item->edit_link( ' &bull; ' ) // Link to backoffice for editing ?>

			<?php $Item->trackback_rdf() // trackback autodiscovery information ?>
		</div>

		<?php
			// ------------- START OF INCLUDE FOR COMMENTS, TRACKBACK, PINGBACK, ETC. -------------
			$disp_comments = 1;					// Display the comments if requested
			$disp_comment_form = 1;			// Display the comments form if comments requested
			$disp_trackbacks = 1;				// Display the trackbacks if requested

			$disp_trackback_url = 1;		// Display the trackbal URL if trackbacks requested
			$disp_pingbacks = 0;        // Don't display the pingbacks (deprecated)
			require( dirname(__FILE__).'/_feedback.php' );
			// ---------------- END OF INCLUDE FOR COMMENTS, TRACKBACK, PINGBACK, ETC. ----------------
		?>
	</div>
<?php
	locale_restore_previous();	// Restore previous locale (Blog locale)
} // ---------------------------------- END OF POSTS ------------------------------------ ?>

		<?php
			// Links to list pages:
			if( isset($MainList) ) $MainList->page_links( '<p class="center"><strong>', '</strong></p>', '$prev$ :: $next$', array(
   				'prev_text' => '&lt;&lt; '.T_('Previous'),
   				'next_text' => T_('Next').' &gt;&gt;',
				) );
		?>
		<?php
			// previous_post( '<p class="center">%</p>' );
			// next_post( '<p class="center">%</p>' );
		?>

	<?php
		// -------------- START OF INCLUDES FOR LATEST COMMENTS, MY PROFILE, ETC. --------------
		// Note: you can customize any of the sub templates included here by
		// copying the matching php file into your skin directory.
		$current_skin_includes_path = dirname(__FILE__).'/';
		// Call the dispatcher:
		require $skins_path.'_dispatch.inc.php';
		// --------------- END OF INCLUDES FOR LATEST COMMENTS, MY PROFILE, ETC. ---------------
	?>

</div>
</div>
<!-- =================================== START OF SIDEBAR =================================== -->

<div class="bSideBar">
<div class="innerwrap">

	<?php
		// Display container contents:
		$Skin->container( NT_('Sidebar'), array(
				// The following (optional) params will be used as defaults for widgets included in this container:
				// This will enclose each widget in a block:
				'block_start' => '<div class="bSideItem $wi_class$">',
				'block_end' => '</div>',
				// This will enclose the title of each widget:
				'block_title_start' => '<h3>',
				'block_title_end' => '</h3>',
				// If a widget displays a list, this will enclose that list:
				'list_start' => '<ul>',
				'list_end' => '</ul>',
				// This will enclose each item in a list:
				'item_start' => '<li>',
				'item_end' => '</li>',
				// This will enclose sub-lists in a list:
				'group_start' => '<ul>',
				'group_end' => '</ul>',
			) );
	?>

	<p class="center">powered by<br />
	<a href="http://b2evolution.net/" title="b2evolution home"><img src="<?php echo $rsc_url; ?>img/b2evolution_logo_80.gif" alt="b2evolution" width="80" height="17" border="0" class="middle" /></a></p>

</div>
</div>

<div class="clear"><img src="<?php echo $rsc_url; ?>img/blank.gif" width="1" height="1" alt="" /></div>

<div id="pageFooter">
	<p class="baseline">
		Original <a href="http://b2evolution.net/">b2evolution</a> template design by <a href="http://severinelandrieu.com/">S&eacute;verine LANDRIEU</a> &amp; <a href="http://fplanque.net/">Fran&ccedil;ois PLANQUE</a> / <a href="http://skinfaktory.com/">The Skin Faktory</a>.
	</p>
	<p class="baseline">
		<?php
			// Display a link to contact the owner of this blog (if owner accepts messages):
			$Blog->contact_link( array(
					'before'      => '',
					'after'       => ' &bull; ',
					'text'   => T_('Contact'),
					'title'  => T_('Send a message to the owner of this blog...'),
				) );
		?>

		<?php
			// Display additional credits (see /conf/_advanced.php):
 			// If you can add your own credits without removing the defaults, you'll be very cool :))
			// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
			display_list( $credit_links, T_('Credits').': ', '', '|', ' ', ' ' );
			$Hit->log();	// log the hit on this page
			debug_info(); // output debug info if requested
		?>
	</p>
</div>

</div>

</body>
</html>