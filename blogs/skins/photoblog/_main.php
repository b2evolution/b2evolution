<?php
/**
 * This is the main template. It displays the blog.
 *
 * However this file is not meant to be called directly.
 * It is meant to be called automagically by b2evolution.
 * To display a blog, the easiest way is to call index.php?blog=#
 * where # is the number of your blog.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2005 by Jason EDGECOMBE.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * Jason EDGECOMBE grants Francois PLANQUE the right to license
 * Jason EDGECOMBE's personal contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evoskins
 * @subpackage custom
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author cafelog (team)
 * @author edgester: Jason EDGECOMBE (personal contributions, not for hire)
 * @author fplanque: Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @version $Id$
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
		request_title( ' - ', '', ' - ', 'htmlhead', array(
				'category_text' => T_('Album').': ',
				'categories_text' => T_('Albums').': ',
		 ) );
	?></title>
	<?php skin_base_tag(); /* Base URL for this skin. You need this to fix relative links! */ ?>
	<meta name="description" content="<?php $Blog->disp( 'shortdesc', 'htmlattr' ); ?>" />
	<meta name="keywords" content="<?php $Blog->disp( 'keywords', 'htmlattr' ); ?>" />
	<meta name="generator" content="b2evolution <?php echo $app_version ?>" /> <!-- Please leave this for stats -->
	<link rel="alternate" type="text/xml" title="RSS 2.0" href="<?php $Blog->disp( 'rss2_url', 'raw' ) ?>" />
	<link rel="alternate" type="application/atom+xml" title="Atom" href="<?php $Blog->disp( 'atom_url', 'raw' ) ?>" />
	<link rel="stylesheet" href="style.css" type="text/css" />
	<?php
		$Blog->disp( 'blog_css', 'raw');
		$Blog->disp( 'user_css', 'raw');
	?>
</head>

<body>

<?php
	// --------------------------- BLOG LIST INCLUDED HERE -----------------------------
	require dirname(__FILE__).'/_bloglist.php';
	// ------------------------------- END OF BLOG LIST --------------------------------
?>

<div class="pageHeader">

	<div class="floatright">
		<a href="<?php $Blog->disp( 'dynurl', 'raw' ) ?>"><?php echo T_('Recently') ?></a>
		|
		<a href="<?php $Blog->disp( 'arcdirurl', 'raw' ) ?>"><?php echo T_('Index') ?></a>
		<?php
				user_login_link( ' | ', ' ' );
				user_register_link( ' | ', ' ' );
				user_admin_link( ' | ', ' ' );
				user_logout_link( ' | ', ' ' );
			?>
	</div>
	
	<h1 id="pageTitle"><a href="<?php $Blog->disp( 'url', 'raw' ) ?>"><?php $Blog->disp( 'name', 'htmlbody' ) ?></a></h1>

</div>
<div class="bPosts">
	
<!-- =================================== START OF MAIN AREA =================================== -->
	
	<?php
	// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
	if( empty( $preview ) ) $Messages->disp( );
	// fp>> TODO: I think we should rather forget the messages here so they don't get displayed again.
	// --------------------------------- END OF MESSAGES ---------------------------------
	?>
	

	<?php
		if( isset($MainList) )
		{ // Links to list pages:
			$MainList->page_links( '<div class="nav_right">', '</div>', '$next$ $prev$', array(
				'prev_text' => '<img src="img/prev.gif" width="29" height="29" alt="'.T_('Previous').'" title="'.T_('Previous').'" />',
				'next_text' => '<img src="img/next.gif" width="29" height="29" alt="'.T_('Next').'" title="'.T_('Next').'" />',
				'no_prev_text' => '',
				'no_next_text' => '<img src="'.$rsc_url.'/img/blank.gif" width="29" height="29" alt="" class="no_nav" />',

			) );
		}
	?>

	
	<?php
	// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
	request_title( '<h2>', '</h2>', ' - ', 'htmlbody', array(
				'category_text' => T_('Album').': ',
				'categories_text' => T_('Albums').': ',
		 ), false, '<h2>&nbsp;</h2>' );
	// ------------------------------ END OF REQUEST TITLE -----------------------------
	?>
	
	

	<?php
	// ------------------------------------ START OF POSTS ----------------------------------------
	if( isset($MainList) ) $MainList->display_if_empty(); // Display message if no post

	if( isset($MainList) ) while( $Item = & $MainList->get_item() )
	{
	?>
	
	<?php
		//previous_post();	// link to previous post in single page mode
		//next_post(); 			// link to next post in single page mode
	?>
	
	<div class="bPost bPost<?php $Item->status( 'raw' ) ?>" lang="<?php $Item->lang() ?>">
		<?php
			locale_temp_switch( $Item->locale ); // Temporarily switch to post locale
			$Item->anchor(); // Anchor for permalinks to refer to
		?>


		<?php
			// Display images that are linked to this post:
			$Item->images( array(
					'before' =>              '<div class="bImages">',
					'before_image' =>        '<div class="image_block">',
					'before_image_legend' => '<div class="image_legend">',
					'after_image_legend' =>  '</div>',
					'after_image' =>         '</div>',
					'after' =>               '</div>',
					'image_size' =>          'fit-720x500'
				) );
		?>


		<div class="bDetails">

			<div class="bSmallHead">

				<?php $Item->feedback_link( 'feedbacks', '<div class="action_right">', '</div>',
								get_icon( 'nocomment' ), get_icon( 'comments' ), get_icon( 'comments' ) ) // Link to comments ?>

				<div class="action_right"><?php $Item->permanent_link( T_('Permalink'), '#' ); ?></div>

				<?php $Item->edit_link( '<div class="action_right">', '</div>', T_('Edit...'), T_('Edit title/description...') ) // Link to backoffice for editing ?>

				<h3 class="bTitle"><?php $Item->title(); ?></h3>
				<span class="timestamp"><?php $Item->issue_date( locale_datefmt().' H:i' ); ?></span>

			</div>

			<div class="bText">
				<?php $Item->content(); ?>
				<?php
					// Links to post pages (for multipage posts):
					$Item->page_links( '<p class="right">'.T_('Pages:').' ', '</p>', ' &middot; ' );
				?>
			</div>

			<div class="bSmallPrint">
			<?php
					echo T_('Albums'), ': ';
					$Item->categories();
				?>
			</div>
		</div>

		<?php
			// ------------- START OF INCLUDE FOR COMMENTS, TRACKBACK, PINGBACK, ETC. -------------
			$disp_comments = 1;					// Display the comments if requested
			$disp_comment_form = 1;			// Display the comments form if comments requested
			$disp_trackbacks = 1;				// Display the trackbacks if requested

			$disp_trackback_url = 1;		// Display the trackbal URL if trackbacks requested
			$disp_pingbacks = 0;        // Don't display the pingbacks (deprecated)
			require( dirname(__FILE__).'/_feedback.php' );
			// -------------- END OF INCLUDE FOR COMMENTS, TRACKBACK, PINGBACK, ETC. --------------
		?>

		<?php
			locale_restore_previous();	// Restore previous locale (Blog locale)
		?>
	</div>
	<?php
	} // ---------------------------------- END OF POSTS ------------------------------------

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
<div id="pageFooter">

	<p class="baseline">
		<?php
			// Display a link to contact the owner of this blog (if owner accepts messages):
			$Blog->contact_link( array(
					'before'      => '',
					'after'       => ' | ',
					'text'   => T_('Contact'),
					'title'  => T_('Send a message to the owner of this blog...'),
				) );
		?>

		<a href="<?php $Blog->disp( 'lastcommentsurl', 'raw' ) ?>"><?php echo T_('Latest comments') ?></a>
		|
		<a href="<?php $Blog->disp( 'rss2_url', 'raw' ) ?>">RSS 2.0</a> /
		<a href="<?php $Blog->disp( 'atom_url', 'raw' ) ?>"><?php echo T_('Atom Feed') ?></a> /
		<a href="http://webreference.fr/2006/08/30/rss_atom_xml" title="External - English"><?php echo T_('What is RSS?') ?></a>
		<?php
			user_profile_link( ' | ', ' ' );
			user_subs_link( ' | ', ' ' );
		?>
	</p>

	<p class="baseline">
		Powered by <a href="http://b2evolution.net/" title="b2evolution home">b2evolution</a>
		|
		Skin by <a href="http://skinfaktory.com/">The Skin Faktory</a>
		|
		<?php
			// Display additional credits (see /conf/_advanced.php):
 			// If you can add your own credits without removing the defaults, you'll be very cool :))
		 	// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
			display_list( $credit_links, T_('Credits').': ', ' ', '|', ' ', ' ' );
		?>
	</p>
</div>
<?php
	$Hit->log();	// log the hit on this page
	debug_info(); // output debug info if requested
?>
</body>
</html>