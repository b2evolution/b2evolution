<?php 
/*
 * This is the main template. It displays the blog.
 *
 * However this file is not meant to be called directly.
 * It is meant to be called automagically by b2evolution.
 * To display a blog, you should call a stub file instead, for example:
 * /blogs/index.php or /blogs/blog_b.php
 */
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
	<title><?php
		$Blog->disp('name', 'htmlhead');
		single_cat_title( ' - ', 'htmlhead' );
		single_month_title( ' - ', 'htmlhead' );
		single_post_title( ' - ', 'htmlhead' );
		arcdir_title( ' - ', 'htmlhead' );
		profile_title( ' - ', 'htmlhead' );
	?>
	</title>
	<base href="<?php skinbase(); // Base URL for this skin. You need this to fix relative links! ?>" />
	<meta name="generator" content="b2evolution <?php echo $b2_version ?>" /> <!-- Please leave this for stats -->
</head>
<body>

	<?php // --------------------------- BLOG LIST INCLUDED HERE -----------------------------
		require( dirname(__FILE__)."/_bloglist.php"); 
		// ---------------------------------- END OF BLOG LIST --------------------------------- ?>

	<?php // ------------------------------- START OF SKIN LIST -------------------------------
	echo T_( 'Select skin:' ), ' ';
	for( skin_list_start(); skin_list_next(); ) { ?>
		[<a href="<?php skin_change_url() ?>"><?php skin_list_iteminfo( 'name', 'htmlbody' ) ?></a>]
	<?php } // ------------------------------ END OF SKIN LIST ------------------------------ ?>

	<hr>
	<div align="center">
		<h1><?php $Blog->disp( 'name', 'htmlbody' ) ?></h1>
		<p><?php $Blog->disp( 'tagline', 'htmlbody' ) ?></p>
	</div>	
	<hr>

  <small><?php $Blog->disp( 'longdesc', 'htmlbody' ); ?></small>
	
	<hr>

	<h2><?php
		single_cat_title();
		single_month_title();
		single_post_title();
		arcdir_title();
		profile_title();
	?></h2>

	<?php	// ---------------------------------- START OF POSTS --------------------------------------
	if( isset($MainList) ) while( $Item = $MainList->get_item() )
	{
		$MainList->date_if_changed();
		?>

		<?php $Item->anchor(); ?>
		<h3 class="bTitle">
			<?php $Item->issue_time(); ?>
			<a href="<?php $Item->permalink() ?>" title="<?php echo T_('Permanent link to full entry') ?>"><img src="img/icon_minipost.gif" alt="Permalink" width="12" height="9" border="0" align="middle" /></a>
			<?php $Item->title(); ?>
		</h3>

		<blockquote>

			<small>
			<?php
				echo T_('Categories'), ': ';
				$Item->categories();
				echo ', ';
				$Item->wordcount();
				echo ' ', T_('words');
			?>
			</small>
		
			<div>
				<?php $Item->content( '#', '#', T_('Read more...') ); ?>
				<?php link_pages("<br />Pages: ","<br />","number") ?>
			</div>

			<small>
			<a href="<?php $Item->permalink() ?>#comments" title="<?php echo T_('Display comments / Leave a comment') ?>"><?php comments_number() ?></a>
			-
			<a href="<?php $Item->permalink() ?>#trackbacks" title="<?php echo T_('Display trackbacks / Get trackback address for this post') ?>"><?php trackback_number() ?></a>
			<?php $Item->trackback_rdf() // trackback autodiscovery information ?>
			-
			<a href="<?php $Item->permalink() ?>#comments" title="<?php echo T_('Display pingbacks') ?>"><?php pingback_number() ?></a>
			-
			<a href="<?php $Item->permalink() ?>" title="<?php echo T_('Permanent link to full entry') ?>"><?php echo T_('Permalink') ?></a>
			</small>

		</blockquote>

		<?php	// ------------- START OF INCLUDE FOR COMMENTS, TRACKBACK, PINGBACK, ETC. --------------
		$disp_comments = 1;					// Display the comments if requested
		$disp_comment_form = 1;			// Display the comments form if comments requested
		$disp_trackbacks = 1;				// Display the trackbacks if requested

		$disp_trackback_url = 1;		// Display the trackbal URL if trackbacks requested
		$disp_pingbacks = 1;				// Display the pingbacks if requested
		require( dirname(__FILE__)."/_feedback.php");
		// ----------------- END OF INCLUDE FOR COMMENTS, TRACKBACK, PINGBACK, ETC. ----------------- ?>

	<?php } // --------------------------------- END OF POSTS ----------------------------------- ?> 

	<?php // ---------------- START OF INCLUDES FOR LAST COMMENTS, STATS ETC. ----------------
		switch( $disp )
		{
			case 'arcdir':
				// this includes the archive directory if requested
				require( dirname(__FILE__).'/_arcdir.php');
				break;
	
			case 'profile':
				// this includes the profile form if requested
				require( dirname(__FILE__).'/_profile.php');
				break;
		}
		// ------------------- END OF INCLUDES FOR LAST COMMENTS, STATS ETC. ------------------- ?>

	<hr>

	<div align="center">
		<strong>
		<?php posts_nav_link(); ?>
		::
		<a href="<?php $Blog->disp( 'arcdirurl', 'raw' ) ?>"><?php echo T_('Archives') ?></a>
		</strong>

		<p><?php 
			user_login_link( ' [', '] ' ); 
			user_register_link( ' [', '] ' ); 
			user_admin_link( ' [', '] ' ); 
			user_profile_link( ' [', '] ' ); 
			user_logout_link( ' [', '] ' ); 
		?></p>
	</div>

	<hr>
	
	<div align="center">Powered by <a href="http://b2evolution.net/" title="b2evolution home"><img src="../../img/b2evolution_button.png" alt="b2evolution" width="80" height="15" border="0" align="middle" /></a> <!-- Please help us promote b2evolution and leave this link on your blog. --></div>
	<?php 
		log_hit();	// log the hit on this page
		debug_info();	// output debug info if requested
	?>
</body>
</html>
