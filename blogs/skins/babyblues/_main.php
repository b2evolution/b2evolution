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
<html lang="<?php locale_lang() ?>">
<head>
	<title><?php
		bloginfo('name', 'htmlhead');
		single_cat_title( ' - ', 'htmlhead' );
		single_month_title( ' - ', 'htmlhead' );
		single_post_title( ' - ', 'htmlhead' );
		profile_title( ' - ', 'htmlhead' );
	?></title>
	<base href="<?php skinbase(); // Base URL for this skin. You need this to fix relative links! ?>" />
	<link rel="stylesheet" type="text/css" href="style.css">
	<?php comments_popup_script(); ?>
</head>
<body>
	<div id="wide">&nbsp;</div>
	<div id="pic"><img src="babies.jpg"></div>
	<div id="content"> 
	<!-- // b2 loop start -->
	 
	<?php	// ------------------------------------- START OF POSTS -------------------------------------
		if( isset($MainList) ) while( $Item = $MainList->get_item() )
	{
	?>
	<div class="date" align="right"><?php $MainList->date_if_changed( '', '' ) ?></div><br />
		<?php $Item->anchor(); ?>
		<div class="title"><?php $Item->title(); ?></div>
		<?php $Item->content(); ?>
		 <?php link_pages("<br />Pages: ","<br />","number") ?>
	
		 <div class="posted">by <?php the_author() ?> at <a href="<?php permalink_link() ?>"><?php $Item->time() ?></a><br />
		<?php comments_popup_link("Comments (0)", "Comments (1)", "Comments (%)") ?> |
	<?php trackback_popup_link("Trackback (0)", "Trackback (1)", "Trackback (%)") ?> |
	<?php pingback_popup_link("Pingback (0)", "Pingback (1)", "Pingback (%)") ?> |
	<a href="?cat=<?php the_category_ID() ?>" title="category: <?php the_category() ?>"><?php the_category() ?></a>
	<?php trackback_rdf() ?>
		</div>
				<?php
			// this includes the trackback url, comments, trackbacks, pingbacks and a form to add a new comment
			$disp_comments = 1;					// Display the comments if requested
			$disp_comment_form = 1;			// Display the comments form if comments requested
			$disp_trackbacks = 1;				// Display the trackbacks if requested
			$disp_trackback_url = 1;		// Display the trackbal URL if trackbacks requested
			$disp_pingbacks = 1;				// Display the pingbacks if requested
			require( dirname(__FILE__)."/_feedback.php");
	?>
	
	<p>
	<?php } // ---------------------------------- END OF POSTS ------------------------------------ ?> 
	
	<?php // ---------------- START OF INCLUDES FOR LAST COMMENTS, STATS ETC. ----------------
		switch( $disp )
		{
			case 'comments':
				// this includes the last comments if requested:
				require( dirname(__FILE__).'/_lastcomments.php' );
				break;
	
			case 'stats':
				// this includes the statistics if requested:
				require( dirname(__FILE__).'/_stats.php');
				break;
			
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
	</div>
	<div id="side">
	
		<div class="sidebody"><?php bloginfo('longdesc', 'htmlbody'); ?></div>
	
		<?php // --------------------------- BLOG LIST INCLUDED HERE -----------------------------
			require( dirname(__FILE__)."/_bloglist.php"); 
			// ---------------------------------- END OF BLOG LIST --------------------------------- ?>
	
		<div class="sidetitle" align="center"><?php echo T_('skin the site') ?></div>
		<div class="sidebody">
		<?php // ---------------------------------- START OF SKIN LIST ----------------------------------
			for( skin_list_start(); skin_list_next(); ) { ?>
				<a href="<?php skin_change_url() ?>"><?php skin_list_iteminfo( 'name' ) ?></a><br />
		<?php } // --------------------------------- END OF SKIN LIST --------------------------------- ?>
		</div>
	
		<div class="sidetitle" align="center"><?php echo T_('archives') ?></div>
		<div class="sidebody">
		<?php	// -------------------------- ARCHIVES INCLUDED HERE -----------------------------
			require( dirname(__FILE__)."/_archives.php"); 
			// -------------------------------- END OF ARCHIVES ---------------------------------- ?>
		</div>
	
		<div class="sidetitle" align="center"><?php echo T_('Misc') ?></div>
		<div class="sidebody">
			<?php 
				// Administrative links:
				user_login_link( '', '<br />' ); 
				user_register_link( '', '<br />' ); 
				user_admin_link( '', '<br />' ); 
				user_profile_link( '', '<br />' ); 
				user_logout_link( '', '<br />' ); 
			?>
		</div>
		
		<div class="sidetitle" align="center">credits</div>
		<div class="sidebody">
		design from <a href="http://lifeisadiaper.com" title="designed by Sabrina">Sabrina</a><br />
		powered by <a href="http://b2evolution.net/"title="b2evolution home"><img src="../../img/b2evolution_button.png" width="80" height="15" class="middle" alt="b2evolution" border="0" /></a><br />
		</div>
	
	</div>
	
	<?php log_hit();	// log the hit on this page ?>
	
</body>
</html>