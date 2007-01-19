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
 * @subpackage basic
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE - {@link http://fplanque.net/}
 * @author cafelog (team)
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
<html>
<head>
	<?php skin_content_meta(); /* Charset for static pages */ ?>
	<?php $Plugins->trigger_event( 'SkinBeginHtmlHead' ); ?>
	<title><?php
		$Blog->disp('name', 'htmlhead');
		request_title( ' - ', '', ' - ', 'htmlhead' );
	?>
	</title>
	<?php skin_base_tag(); /* Base URL for this skin. You need this to fix relative links! */ ?>
	<meta name="generator" content="b2evolution <?php echo $app_version ?>" /> <!-- Please leave this for stats -->
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
	// ---------------------------------- END OF BLOG LIST ---------------------------------
	?>


	<hr>
	<div align="center">
		<h1><?php $Blog->disp( 'name', 'htmlbody' ) ?></h1>
		<p><?php $Blog->disp( 'tagline', 'htmlbody' ) ?></p>
	</div>
	<hr>
	<small><?php $Blog->disp( 'longdesc', 'htmlbody' ); ?></small>

	<hr>

	<?php
	// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
	if( empty( $preview ) ) $Messages->disp( );
	// --------------------------------- END OF MESSAGES ---------------------------------
	?>

	<?php request_title( '<h2>', '</h2>' ) ?>

	<?php	// ---------------------------------- START OF POSTS --------------------------------------
	if( isset($MainList) ) $MainList->display_if_empty();	// Display message if no post

	if( isset($MainList) ) while( $Item = & $MainList->get_item() )
	{
		$MainList->date_if_changed();
		$Item->anchor();
		locale_temp_switch( $Item->locale ); // Temporarily switch to post locale
		?>

		<h3>
			<?php $Item->issue_time(); ?>
			<a href="<?php $Item->permanent_url() ?>" title="<?php echo T_('Permanent link to full entry') ?>"><img src="img/icon_minipost.gif" alt="Permalink" width="12" height="9" border="0" align="absmiddle" /></a>
			<?php $Item->title(); ?>
		</h3>

		<blockquote>

			<small>
			<?php
				echo T_('Categories'), ': ';
				$Item->categories();
			?>
			</small>

			<?php
				// Display images that are linked to this post:
				$Item->images( array(
						'before' =>              '<table cellspacing="5">',
						'before_image' =>        '<tr><td align="center">',
						'before_image_legend' => '<br><small>',
						'after_image_legend' =>  '</small>',
						'after_image' =>         '</td></tr>',
						'after' =>               '</table>',
						'image_size' =>          'fit-320x320'
					) );
			?>

			<div>
				<?php $Item->content( '#', '#', T_('Read more...') ); ?>
				<?php
					// Links to post pages (for multipage posts):
					$Item->page_links( '<p class="right">'.T_('Pages:').' ', '</p>', ' &middot; ' );
				?>
			</div>

			<small>
				<?php $Item->feedback_link( 'feedbacks', '', ' &bull; ' ) // Link to comments, trackback... ?>
				<?php $Item->edit_link( '', ' &bull; ' ) // Link to backoffice for editing ?>
				<?php $Item->trackback_rdf() // trackback autodiscovery information ?>
				<?php $Item->permanent_link(); ?>
			</small>

		</blockquote>

		<?php	// ------------- START OF INCLUDE FOR COMMENTS, TRACKBACK, PINGBACK, ETC. --------------
		$disp_comments = 1;					// Display the comments if requested
		$disp_comment_form = 1;			// Display the comments form if comments requested
		$disp_trackbacks = 1;				// Display the trackbacks if requested

		$disp_trackback_url = 1;		// Display the trackbal URL if trackbacks requested
		$disp_pingbacks = 0;        // Don't display the pingbacks (deprecated)
		require( dirname(__FILE__).'/_feedback.php' );
		// ----------------- END OF INCLUDE FOR COMMENTS, TRACKBACK, PINGBACK, ETC. -----------------

		locale_restore_previous();	// Restore previous locale (Blog locale)
		?>
	<?php } // --------------------------------- END OF POSTS ----------------------------------- ?>

	<?php
		// -------------- START OF INCLUDES FOR LAST COMMENTS, MY PROFILE, ETC. --------------
		// Note: you can customize any of the sub templates included here by
		// copying the matching php file into your skin directory.
		$current_skin_includes_path = dirname(__FILE__).'/';
		// Call the dispatcher:
		require $skins_path.'_dispatch.inc.php';
		// --------------- END OF INCLUDES FOR LAST COMMENTS, MY PROFILE, ETC. ---------------
	?>

	<hr>

	<div align="center">
		<strong>
		<?php
			// Links to list pages:
			if( isset($MainList) ) $MainList->page_links( '<p class="center"><strong>', '</strong></p>', '$prev$ :: $next$', array(
   				'prev_text' => '&lt;&lt; '.T_('Previous'),
   				'next_text' => T_('Next').' &gt;&gt;',
				) );
		?>
		::
		<a href="<?php $Blog->disp( 'arcdirurl', 'raw' ) ?>"><?php echo T_('Archives') ?></a>
		</strong>

		<p><?php
			user_login_link( ' [', '] ' );
			user_register_link( ' [', '] ' );
			user_admin_link( ' [', '] ' );
			user_logout_link( ' [', '] ' );
		?></p>
	</div>

	<hr>

	<div align="center">Powered by <a href="http://b2evolution.net/" title="b2evolution home"><img src="<?php echo $rsc_url; ?>img/b2evolution_logo_80.gif" alt="b2evolution" width="80" height="17" border="0" align="middle" /></a> <!-- Please help us promote b2evolution and leave this link on your blog. --></div>
	<?php
		$Hit->log();  // log the hit on this page
		debug_info();	// output debug info if requested
	?>
</body>
</html>