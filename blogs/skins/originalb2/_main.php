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
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Jason EDGECOMBE grants Francois PLANQUE the right to license
 * Jason EDGECOMBE's personal contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evoskins
 * @subpackage originalb2
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author BLUEROBOT.COM - {@link http://bluerobot.com/web/layouts/layout2.html} : layout
 * @author cafelog (team)
 * @author fplanque: Francois PLANQUE - {@link http://fplanque.net/}
 * @author edgester Jason EDGECOMBE
 *
 * {@internal Below is a list of former authors whose contributions to this file have been
 *            either removed or redesigned and rewritten anew:
 *            - (none)
 * }}
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

skin_content_header();	// Sets charset!
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<!-- layout credits goto http://bluerobot.com/web/layouts/layout2.html -->

<head xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
	<?php skin_content_meta(); /* Charset for static pages */ ?>
	<?php $Plugins->trigger_event( 'SkinBeginHtmlHead' ); ?>
	<title><?php $Blog->disp( 'name', 'htmlbody' ) ?><?php request_title( ' :: ', '', ' :: ', 'htmlhead' ) ?></title>
	<?php skin_base_tag(); /* Base URL for this skin. You need this to fix relative links! */ ?>
	<meta name="description" content="<?php $Blog->disp( 'shortdesc', 'htmlattr' ); ?>" />
	<meta name="keywords" content="<?php $Blog->disp( 'keywords', 'htmlattr' ); ?>" />
	<style type="text/css" media="screen">
	@import url(layout2b.css);
	</style>
	<link rel="stylesheet" type="text/css" media="print" href="print.css" />
	<meta name="generator" content="b2evolution <?php echo $app_version ?>" /> <!-- Please leave this for stats -->
	<link rel="alternate" type="text/xml" title="RSS 2.0" href="<?php $Blog->disp( 'rss2_url', 'raw' ) ?>" />
	<link rel="alternate" type="application/atom+xml" title="Atom" href="<?php $Blog->disp( 'atom_url', 'raw' ) ?>" />
	<?php comments_popup_script() // Include javascript to open pop up windows ?>
	<?php
		$Blog->disp( 'blog_css', 'raw');
		$Blog->disp( 'user_css', 'raw');
	?>
</head>
<body>
<?php
	/**
	 * --------------------------- BLOG LIST INCLUDED HERE -----------------------------
	 */
	require( dirname(__FILE__).'/_bloglist.php' );
	// ---------------------------------- END OF BLOG LIST --------------------------------- ?>

<div id="header"><a href="<?php $Blog->disp( 'blogurl', 'raw' ) ?>" title="<?php $Blog->disp( 'name', 'htmlattr' ) ?>"><?php $Blog->disp( 'name', 'htmlbody' ) ?></a></div>

<div id="content">


	<?php	// ----------------------------------- START OF POSTS ------------------------------------
	if( isset($MainList) ) $MainList->display_if_empty();	// Display message if no post

	if( isset($MainList) ) while( $Item = & $MainList->get_item() )
	{
		$MainList->date_if_changed();
		// Load Item's creator User:
		$Item->get_creator_User();
		locale_temp_switch( $Item->locale ); // Temporarily switch to post locale
	?>
	<div class="storyTitle">
		<?php $Item->anchor(); ?>
		<?php locale_flag( $Item->locale, 'h10px' ); // Display flag for post locale ?>
		&nbsp;
		<?php $Item->title(); ?>
		&nbsp;-&nbsp;
		Categories: <?php $Item->categories() ?>
		&nbsp;-&nbsp;
		<span class="storyAuthor"><a href="<?php echo url_add_param( $Blog->get('blogurl'), 'author='.$Item->creator_User->ID ); ?>" title="<?php echo T_('Browse all posts by this author') ?>"><?php $Item->creator_User->preferred_name() ?></a></span>
		@ <a href="<?php $Item->permanent_url() ?>"><?php $Item->issue_time() ?></a>
	</div>

	<div class="storyContent">
	<?php $Item->content(); ?>

	<div class="rightFlush">
	<?php
		// Links to post pages (for multipage posts):
		$Item->page_links( '<p class="right">'.T_('Pages:').' ', '</p>', ' &middot; ' );
	?>

	<?php $Item->feedback_link( 'comments' ) // Link to comments ?>
	<?php $Item->feedback_link( 'trackbacks', ' &bull; ' ) // Link to trackbacks ?>

	<?php $Item->edit_link( ' &bull; ' ) // Link to backoffice for editing ?>

	<?php $Item->trackback_rdf() // trackback autodiscovery information ?>

	<?php
			// THIS is an example of how to display unmixed comments, trackbacks and pingbacks.
			// doing it old b2 style :>>

			// this includes the comments and a form to add a new comment
			$disp_comments = 1;					// Display the comments if requested
			$disp_comment_form = 1;			// Display the comments form if comments requested
			$disp_trackbacks = 0;				// Display the trackbacks if requested
			$disp_trackback_url = 0;		// Display the trackbal URL if trackbacks requested
			$disp_pingbacks = 0;        // Don't display the pingbacks (deprecated)
			$disp_title = "Comments:";
			require( dirname(__FILE__).'/_feedback.php' );

			// this includes the trackbacks
			$disp_comments = 0;					// Display the comments if requested
			$disp_comment_form = 0;			// Display the comments form if comments requested
			$disp_trackbacks = 1;				// Display the trackbacks if requested
			$disp_trackback_url = 1;		// Display the trackbal URL if trackbacks requested
			$disp_pingbacks = 0;        // Don't display the pingbacks (deprecated)
			$disp_title = "Trackbacks:";
			require( dirname(__FILE__).'/_feedback.php' );

	?>

	</div>

	</div>

	<?php	locale_restore_previous();	// Restore previous locale (Blog locale) ?>

	<?php } // ---------------------------------- END OF POSTS ------------------------------------ ?>

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
<p class="center">
	powered by<br />
<a href="http://b2evolution.net/" title="b2evolution home"><img src="<?php echo $rsc_url; ?>img/b2evolution_logo_80.gif" alt="b2evolution" width="80" height="17" border="0" class="middle" /></a></p>


<div id="menu">

<p><?php $Blog->disp( 'longdesc', 'htmlbody' ); ?></p>


<?php // -------------------------- CATEGORIES INCLUDED HERE -----------------------------
	// Call the Categories plugin:
	$Plugins->call_by_code( 'evo_Cats', array(	// Add parameters below:
			'title'=>'<h4>'.T_('categories').':</h4>',
		) );
	// -------------------------------- END OF CATEGORIES ---------------------------------- ?>


<h4>search:</h4>

<?php form_formstart( $Blog->dget( 'blogurl', 'raw' ), '', 'searchform' ) ?>
	<input type="text" name="s" size="15" style="width: 100%" />
	<input type="submit" name="submit" value="<?php echo T_('Search') ?>" />
</form>


<?php // -------------------------- ARCHIVES INCLUDED HERE -----------------------------
	// Call the Archives plugin:
	$Plugins->call_by_code( 'evo_Arch', array( // Parameters follow:
			'block_start'=>'',
			'block_end'=>'',
			'title'=>'<h4>'.T_('Archives').':</h4>',
			'limit'=>'',                           // No limit
			'more_link'=>'',                       // No more link
			'list_start'=>'<ul class="compress">', // Special list start
		)	);
	// -------------------------------- END OF ARCHIVES ---------------------------------- ?>


<h4>other:</h4>
<?php
	// Administrative links:
	user_login_link( '', '<br />' );
	user_register_link( '', '<br />' );
	user_admin_link( '', '<br />' );
	user_profile_link( '', '<br />' );
	user_subs_link( '', '<br />' );
	user_logout_link( '', '<br />' );
?>
<br />

<a href="<?php $Blog->disp( 'rss2_url', 'raw' ) ?>"><img src="../../rsc/icons/feed-icon-12x12.gif" alt="view this weblog as RSS !" width="12" height="12" class="middle" /> RSS Feed</a>
</div>
<?php
	// Display additional credits (see /conf/_advanced.php):
 	// If you can add your own credits without removing the defaults, you'll be very cool :))
 	// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
	display_list( $credit_links, '<p class="center">'.T_('Credits').': ', '</p>', '|', ' ', ' ' );
	$Hit->log();	// log the hit on this page
	debug_info();	// output debug info if requested
?>
</body>
</html>