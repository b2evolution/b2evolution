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
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2005 by Jason EDGECOMBE.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * Jason EDGECOMBE grants François PLANQUE the right to license
 * Jason EDGECOMBE's personal contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evoskins
 * @subpackage wpc
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author wordpress (team)
 * @author cafelog (team)
 * @author fplanque: François PLANQUE - {@link http://fplanque.net/}
 * @author edgester: Jason EDGECOMBE
 *
 * {@internal Below is a list of former authors whose contributions to this file have been
 *            either removed or redesigned and rewritten anew:
 *            - (none)
 * }}
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
	<title><?php
		$Blog->disp('name', 'htmlhead');
		single_cat_title( ' - ', 'htmlhead' );
		single_month_title( ' - ', 'htmlhead' );
		single_post_title( ' - ', 'htmlhead' );
		arcdir_title( ' - ', 'htmlhead' );
		last_comments_title( ' - ', 'htmlhead' );
		stats_title( ' - ', 'htmlhead' );
	?>
	</title>
	<base href="<?php skinbase(); /* Base URL for this skin. You need this to fix relative links! */ ?>" />
	<meta name="description" content="<?php $Blog->disp( 'shortdesc', 'htmlattr' ); ?>" />
	<meta name="keywords" content="<?php $Blog->disp( 'keywords', 'htmlattr' ); ?>" />
	<meta name="generator" content="b2evolution <?php echo $app_version ?>" /> <!-- Please leave this for stats -->
	<link rel="alternate" type="text/xml" title="RDF" href="<?php $Blog->disp( 'rdf_url', 'raw' ) ?>" />
	<link rel="alternate" type="text/xml" title="RSS .92" href="<?php $Blog->disp( 'rss_url', 'raw' ) ?>" />
	<link rel="alternate" type="text/xml" title="RSS 2.0" href="<?php $Blog->disp( 'rss2_url', 'raw' ) ?>" />
	<link rel="alternate" type="application/atom+xml" title="Atom" href="<?php $Blog->disp( 'atom_url', 'raw' ) ?>" />
	<link rel="pingback" href="<?php $Blog->disp( 'pingback_url', 'raw' ) ?>" />
	<style type="text/css">
		@import url(../../rsc/img.css);	/* Import standard image styles */
		@import url(../../rsc/blog_elements.css);	/* Import standard blog elements styles */
		@import url(style.css);
	</style>
	<?php
		$Blog->disp( 'blog_css', 'raw');
		$Blog->disp( 'user_css', 'raw');
	?>
</head>
<body>
<div id="rap">
<h1 id="header"><a href="<?php bloginfo('url'); ?>"><?php $Blog->disp( 'name', 'htmlbody' ) ?></a></h1>

<!-- =================================== START OF MAIN AREA =================================== -->

<div id="content">

<?php // ------------------------------------ START OF POSTS ----------------------------------------
	if( isset($MainList) ) $MainList->display_if_empty(); // Display message if no post

	if( isset($MainList) ) while( $Item = $MainList->get_item() )
	{
		$MainList->date_if_changed();
	?>

<div class="post" lang="<?php $Item->lang() ?>">
		<?php
			locale_temp_switch( $Item->locale ); // Temporarily switch to post locale
			$Item->anchor(); // Anchor for permalinks to refer to
		?>
	 <h3 class="storytitle"><a href="<?php $Item->permalink() ?>" rel="bookmark" title="<?php echo T_('Permanent link to full entry') ?>"><?php $Item->title(); ?></a></h3>
	<div class="meta"><?php echo T_('Filed under:'); ?> <?php $Item->categories(); ?> &#8212; <?php $Item->Author->prefered_name() ?> @ <?php $Item->issue_time() ?>
		<?php $Item->edit_link( '', '', T_('Edit This') ) // Link to backoffice for editing ?>
	</div>

	<div class="storycontent">
			<?php $Item->content(); ?>
	</div>

	<div class="feedback">
			<?php link_pages() ?>
			<?php $Item->feedback_link( 'comments' ) // Link to comments ?>
			<?php $Item->feedback_link( 'trackbacks', ' &bull; ' ) // Link to trackbacks ?>
			<?php $Item->feedback_link( 'pingbacks', ' &bull; ' ) // Link to trackbacks ?>
	</div>

	<?php $Item->trackback_rdf() // trackback autodiscovery information ?>

	<?php
	/**
	 * ------------- START OF INCLUDE FOR COMMENTS, TRACKBACK, PINGBACK, ETC. -------------
	 */
	$disp_comments = 1;					// Display the comments if requested
	$disp_comment_form = 1;			// Display the comments form if comments requested
	$disp_trackbacks = 1;				// Display the trackbacks if requested

	$disp_trackback_url = 1;		// Display the trackbal URL if trackbacks requested
	$disp_pingbacks = 1;				// Display the pingbacks if requested
	require( dirname(__FILE__).'/_feedback.php' );
	// ---------------- END OF INCLUDE FOR COMMENTS, TRACKBACK, PINGBACK, ETC. ----------------

	locale_restore_previous();	// Restore previous locale (Blog locale)
	?>
</div>

<?php } // ---------------------------------- END OF POSTS ------------------------------------ ?>

</div>


<!-- =================================== START OF SIDEBAR =================================== -->

<div id="menu">

<ul>

	<?php // -------------------------- LINKBLOG INCLUDED HERE -----------------------------
		require( dirname(__FILE__).'/_linkblog.php' );
		// -------------------------------- END OF LINKBLOG ---------------------------------- ?>

 <li id="categories"><?php echo T_('Categories'); ?>:
	<?php // -------------------------- CATEGORIES INCLUDED HERE -----------------------------
		require( dirname(__FILE__).'/_categories.php' );
		// -------------------------------- END OF CATEGORIES ---------------------------------- ?>
 </li>

 <li id="search">
   <label for="s"><?php echo T_('Search') ?>:</label>
	<?php form_formstart( $Blog->dget( 'blogurl', 'raw' ), '', 'searchform' ) ?>
	<div>
		<input type="text" name="s" id="s" size="15" value="<?php echo htmlspecialchars($s) ?>" /><br />
		<input type="submit" name="submit" value="<?php echo T_('Search') ?>" />
	</div>
	</form>
 </li>

	<li><?php echo T_('Archives') ?>:
		<?php // -------------------------- ARCHIVES INCLUDED HERE -----------------------------
			// Call the Archives plugin:
			$Plugins->call_by_code( 'evo_Arch', array( // Parameters follow:
					'limit'=>'',                           // No limit
					'more_link'=>'',                       // No more link
				)	);
			// -------------------------------- END OF ARCHIVES ---------------------------------- ?>
	</li>

	<li id="calendar">
	<?php // -------------------------- CALENDAR INCLUDED HERE -----------------------------
		require( dirname(__FILE__).'/_calendar.php' );
		// -------------------------------- END OF CALENDAR ---------------------------------- ?>
	</li>


	<?php if( ! $Blog->get('force_skin') )
	{	// We skin switching is allowed for this blog: ?>
	<li><?php echo T_('Choose skin') ?>:
		<ul>
			<?php // ------------------------------- START OF SKIN LIST -------------------------------
			for( skin_list_start(); skin_list_next(); ) { ?>
				<li><a href="<?php skin_change_url() ?>"><?php skin_list_iteminfo( 'name', 'htmlbody' ) ?></a></li>
			<?php } // ------------------------------ END OF SKIN LIST ------------------------------ ?>
		</ul>
	</li>
	<?php } ?>

	<li id="other"><?php echo T_('Other'); ?>:
	<ul>
		<?php
			user_login_link( '<li>', '</li>' );
			user_register_link( '<li>', '</li>' );
			user_admin_link( '<li>', '</li>' );
			user_logout_link( '<li>', '</li>' );
		?>
	</ul>
 </li>


 <li id="meta"><?php echo T_('Meta'); ?>:
 	<ul>
		<li><a href="<?php $Blog->disp( 'rss2_url', 'raw' ); ?>" title="<?php echo T_('Syndicate this site using RSS'); ?>"><?php echo T_('<abbr title="Really Simple Syndication">RSS</abbr> 2.0'); ?></a></li>
		<li><a href="<?php $Blog->disp( 'comments_rss2_url', 'raw' ) ?>" title="<?php echo T_('The latest comments to all posts in RSS'); ?>"><?php echo T_('Comments <abbr title="Really Simple Syndication">RSS</abbr> 2.0'); ?></a></li>
		<li><a href="http://validator.w3.org/check/referer" title="<?php echo T_('This page validates as XHTML 1.0 Transitional'); ?>"><?php echo T_('Valid <abbr title="eXtensible HyperText Markup Language">XHTML</abbr>'); ?></a></li>
		<li><a href="http://b2evolution.net/" title="<?php echo T_('Powered by b2evolution; multilingual multiuser multi-blog engine.'); ?>">b2evolution</a></li>
	</ul>
 </li>

</ul>

</div>

</div>

<p class="credit"><cite>powered by &nbsp;<a href="http://b2evolution.net/" title="<?php echo T_('Powered by b2evolution; multilingual multiuser multi-blog engine.'); ?>"><img src="../../img/b2evolution_button.png" alt="b2evolution" width="80" height="15" class="middle" /></a></cite><br />
<?php echo T_('This skin features a CSS file originally designed for WordPress (See design credits in style.css).') ?><br />
<?php echo T_('Original design credits for this skin:') ?> <a href="http://mezzoblue.com">Dave Shea</a> &amp; <a href="http://photomatt.net">Matthew Mullenweg</a><br />
<?php echo T_('In order to ensure maximum compatibility with WP CSS files, most b2evolution features that do not exist in WP are hidden from this generic wpc_* skin.') ?>
</p>

<?php
	$Hit->log();	// log the hit on this page
	debug_info(); // output debug info if requested
?>

</body>

</html>
