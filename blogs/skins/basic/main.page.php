<?php
/**
 * This is the main/default page template.
 *
 * It is used to display the blog when no specific page template is available to handle the request.
 *
 * @package evoskins
 * @subpackage basic
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );

// ----------------------------- HEADER BEGINS HERE ------------------------------
skin_content_header();	// Sets charset!
?>
<html>
<head>
	<?php skin_content_meta(); /* Charset for static pages */ ?>
	<?php $Plugins->trigger_event( 'SkinBeginHtmlHead' ); ?>
	<title><?php
		request_title( '', ' - ', ' - ', 'htmlhead', array(
		 ) );
		$Blog->disp('name', 'htmlhead');
	?>
	</title>
	<?php skin_base_tag(); /* Base URL for this skin. You need this to fix relative links! */ ?>
	<meta name="generator" content="b2evolution <?php echo $app_version ?>" /> <!-- Please leave this for stats -->
</head>
<body>
<?php
// -------------------------------- END OF HEADER --------------------------------
?>

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
	$Messages->disp( '<div class="action_messages">', '</div>' );
	// --------------------------------- END OF MESSAGES ---------------------------------
	?>

  <?php
	if( isset($MainList) )
	{ // Links to previous and next post in single post mode:
		$MainList->prevnext_item_links( array(
				'block_start' => '',
				'prev_start'  => '',
				'prev_end'    => ' :',
				'next_start'  => ': ',
				'next_end'    => '',
				'block_end'   => '' ) );
	}
	?>

	<?php request_title( '<h2>', '</h2>' ) ?>

	<?php	// ---------------------------------- START OF POSTS --------------------------------------
	if( isset($MainList) ) $MainList->display_if_empty();	// Display message if no post

	if( isset($MainList) ) while( $Item = & $MainList->get_item() )
	{
		$MainList->date_if_changed( '<h2>', '</h2>', '' );
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
				<?php
					// Increment view count of first post on page:
					$Item->count_view( false );

					// Display CONTENT:
					$Item->content_teaser( array(
							'before'      => '',
							'after'       => '',
						) );
					$Item->more_link();
					$Item->content_extension( array(
							'before'      => '',
							'after'       => '',
						) );

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
		// -------------- START OF INCLUDES FOR LATEST COMMENTS, MY PROFILE, ETC. --------------
		// Note: you can customize any of the sub templates included here by
		// copying the matching php file into your skin directory.
		// Call the dispatcher:
		require $skins_path.'_dispatch.inc.php';
		// --------------- END OF INCLUDES FOR LATEST COMMENTS, MY PROFILE, ETC. ---------------
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

		<p>
		<?php
			// Display a link to contact the owner of this blog (if owner accepts messages):
			$Blog->contact_link( array(
					'before'      => ' [',
					'after'       => '] ',
					'text'   => T_('Contact'),
					'title'  => T_('Send a message to the owner of this blog...'),
				) );
		?>

		<?php
			user_login_link( ' [', '] ' );
			user_register_link( ' [', '] ' );
			user_admin_link( ' [', '] ' );
			user_logout_link( ' [', '] ' );
		?>
		</p>
	</div>

	<hr>

	<p align="center"><!-- Please help us promote b2evolution and leave this link on your blog. --><a href="http://b2evolution.net/" title="b2evolution: next generation blog software"><img src="../../rsc/img/powered-by-b2evolution-120t.gif" alt="powered by b2evolution free blog software" title="b2evolution: next generation blog software" width="120" height="32" border="0" /></a></p>

	<?php
		$Hit->log();  // log the hit on this page
		debug_info();	// output debug info if requested
	?>
</body>
</html>