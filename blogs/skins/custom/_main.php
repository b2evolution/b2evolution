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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
<title><?php
	bloginfo('name', 'htmlhead');
	single_cat_title( ' - ', 'htmlhead' );
	single_month_title( ' - ', 'htmlhead' );
	single_post_title( ' - ', 'htmlhead' );
	switch( $disp )
	{
		case 'comments': echo ' - ', _('Last comments'); break;
		case 'stats': echo ' - ', _('Statistics'); break;
		case 'arcdir': echo ' - ', _('Archive Directory'); break;
	}
?>
</title>
<base href="<?php skinbase(); // Base URL for this skin. You need this to fix relative links! ?>" />
<meta name="description" content="<?php bloginfo('shortdesc', 'htmlhead'); ?>" />
<meta name="keywords" content="<?php bloginfo('keywords', 'htmlhead'); ?>" />
<link rel="alternate" type="text/xml" title="RDF" href="<?php bloginfo('rdf_url', 'raw'); ?>" />
<link rel="alternate" type="text/xml" title="RSS" href="<?php bloginfo('rss2_url', 'raw'); ?>" />
<link rel="pingback" href="<?php bloginfo('pingback_url', 'raw'); ?>" />
<link rel="stylesheet" href="custom.css" type="text/css" />
</head>
<body>
<div class="pageHeader">

<?php // --------------------------- BLOG LIST INCLUDED HERE -----------------------------
	require( dirname(__FILE__)."/_bloglist.php"); 
	// ---------------------------------- END OF BLOG LIST --------------------------------- ?>

<h1 id="pageTitle"><?php bloginfo('name', 'htmlbody') ?></h1>

<div class="pageSubTitle"><?php bloginfo('tagline', 'htmlbody') ?></div>

</div>

<div class="bPosts">
<h2><?php
	single_cat_title();
	single_month_title();
	single_post_title();
	switch( $disp )
	{
		case 'comments': echo _('Last comments'); break;
		case 'stats': echo _('Statistics'); break;
		case 'arcdir': echo _('Archive Directory'); break;
	}
?></h2>

<!-- =================================== START OF MAIN AREA =================================== -->

<?php	// ------------------------------------ START OF POSTS ----------------------------------------
	if( isset($MainList) ) while( $MainList->get_item() )
	{
		the_date( '', '<h2>', '</h2>' );
	?>
	<div class="bPost" lang="<?php the_lang() ?>">
		<?php permalink_anchor(); ?>
		<div class="bSmallHead">
		<a href="<?php permalink_link() ?>" title="Permanent link to full entry"><img src="img/icon_minipost.gif" alt="Permalink" width="12" height="9" class="middle" /></a>
		<?php the_time();  echo ', ', _('Categories'), ': ';  the_categories() ?>
		</div>
		<h3 class="bTitle"><?php the_title(); ?></h3>
		<div class="bText">
		  <?php the_content(); ?>
		  <?php link_pages("<br />Pages: ","<br />","number") ?>
		</div>
		<div class="bSmallPrint">
		<a href="<?php permalink_link() ?>#comments" title="Display comments / Leave a comment"><?php comments_number() ?></a>
		-
		<a href="<?php permalink_link() ?>#trackbacks" title="Display trackbacks / Get trackback address for this post"><?php trackback_number() ?></a>
		<?php trackback_rdf() // trackback autodiscovery information ?>
		-
		<a href="<?php permalink_link() ?>#comments" title="Display pingbacks"><?php pingback_number() ?></a>
		-
		<a href="<?php permalink_link() ?>" title="Permanent link to full entry">Permalink</a>
		<?php if( $debug==1 ) printf( _('- %d queries so far'), $querycount); ?>
		</div>
		<?php	// ---------------- START OF INCLUDE FOR COMMENTS, TRACKBACK, PINGBACK, ETC. ----------------
		$disp_comments = 1;					// Display the comments if requested
		$disp_comment_form = 1;			// Display the comments form if comments requested
		$disp_trackbacks = 1;				// Display the trackbacks if requested

		$disp_trackback_url = 1;		// Display the trackbal URL if trackbacks requested
		$disp_pingbacks = 1;				// Display the pingbacks if requested
		require( dirname(__FILE__)."/_feedback.php");
		// ------------------- END OF INCLUDE FOR COMMENTS, TRACKBACK, PINGBACK, ETC. ------------------- ?>
	</div>
<?php } // ---------------------------------- END OF POSTS ------------------------------------ ?> 

	<p class="center"><strong><?php posts_nav_link(); ?></strong></p>

<?php // ---------------- START OF INCLUDES FOR LAST COMMENTS, STATS ETC. ----------------

	// this includes the last comments if requested:
	require( dirname(__FILE__)."/_lastcomments.php");

	// this includes the statistics if requested:
	require( dirname(__FILE__)."/_stats.php");

	// this includes the archive directory if requested
	require( dirname(__FILE__)."/_arcdir.php");

// ------------------- END OF INCLUDES FOR LAST COMMENTS, STATS ETC. ------------------- ?>
</div>
<!-- =================================== START OF SIDEBAR =================================== -->

<div class="bSideBar">

	<div class="bSideItem">
	  <h3><?php bloginfo('name', 'htmlbody') ?></h3>
	  <p><?php bloginfo('longdesc', 'htmlbody'); ?></p>
		<p class="center"><strong><?php posts_nav_link(); ?></strong></p>
		<!--?php next_post(); // activate this if you want a link to the next post in single page mode ?-->
		<!--?php previous_post(); // activate this if you want a link to the previous post in single page mode ?-->
		<ul>
	  	<li><a href="<?php bloginfo('staticurl', 'raw') ?>"><strong><?php echo _('Recently') ?></strong></a> <span class="dimmed"><?php echo _('(cached)') ?></span></li>
	  	<li><a href="<?php bloginfo('dynurl', 'raw') ?>"><strong><?php echo _('Recently') ?></strong></a> <span class="dimmed"><?php echo _('(no cache)') ?></span></li>
		</ul>
		<?php	// -------------------------- CALENDAR INCLUDED HERE -----------------------------
			require( dirname(__FILE__)."/_calendar.php"); 
			// -------------------------------- END OF CALENDAR ---------------------------------- ?>
		<ul>
	  	<li><a href="<?php bloginfo('lastcommentsurl') ?>"><strong><?php echo _('Last comments') ?></strong></a></li>
	  	<li><a href="<?php bloginfo('blogstatsurl') ?>"><strong><?php echo _('Some viewing statistics') ?></strong></a></li>
		</ul>
	</div>
	
	<div class="bSideItem">
    <h3 class="sideItemTitle"><?php echo _('Search') ?></h3>
		<form name="SearchForm" method="get" class="search" action="<?php bloginfo('blogurl') ?>">
			<input type="text" name="s" size="30" value="<?php echo $s ?>" class="SearchField" /><br />
			<input type="radio" name="sentence" value="AND" id="sentAND" <?php if( $sentence=='AND' ) echo 'checked="checked" ' ?>/><label for="sentAND"><?php echo _('All Words') ?></label>
			<input type="radio" name="sentence" value="OR" id="sentOR" <?php if( $sentence=='OR' ) echo 'checked="checked" ' ?>/><label for="sentOR"><?php echo _('Some Word') ?></label>
			<input type="radio" name="sentence" value="sentence" id="sentence" <?php if( $sentence=='sentence' ) echo 'checked="checked" ' ?>/><label for="sentence"><?php echo _('Sentence') ?></label>
			<input type="submit" name="submit" value="<?php echo _('Search') ?>" />
			<input type="reset" value="<?php echo _('Reset form') ?>" />
		</form>
	</div>

	<div class="bSideItem">
		<h3><?php echo _('Categories') ?></h3>
		<form action="<?php bloginfo('blogurl', 'raw') ?>" method="get">
		<?php	// -------------------------- CATEGORIES INCLUDED HERE -----------------------------
			require( dirname(__FILE__)."/_categories.php"); 
			// -------------------------------- END OF CATEGORIES ---------------------------------- ?>
		<br />
		<input type="submit" value="<?php echo _('Get selection') ?>" />
		<input type="reset" value="<?php echo _('Reset form') ?>" />
		</form>
	</div>

	<div class="bSideItem">
    <h3>Archives</h3>
    <ul>
			<?php	// -------------------------- ARCHIVES INCLUDED HERE -----------------------------
				require( dirname(__FILE__)."/_archives.php"); 
				// -------------------------------- END OF ARCHIVES ---------------------------------- ?>
				<li><a href="<?php bloginfo('blogurl') ?>?disp=arcdir"><?php echo _('more...') ?></a></li>
	  </ul>
  </div>

	<div class="bSideItem">
    <h3>Choose skin</h3>
		<ul>
			<?php // ---------------------------------- START OF SKIN LIST ----------------------------------
			for( skin_list_start(); skin_list_next(); ) { ?>
				<li><a href="<?php skin_change_url() ?>"><?php skin_list_iteminfo( 'name', 'htmlbody' ) ?></a></li>
			<?php } // --------------------------------- END OF SKIN LIST --------------------------------- ?>
		</ul>
	</div>

	<?php if (! $stats) 
	{ ?>
	
	<div class="bSideItem">
		<h3><?php echo _('Recent Referers') ?></h3>
			<?php refererList(5,'global',0,0,'no','',($blog>1)?$blog:''); ?>
	  	<ul>
				<?php while($row_stats = mysql_fetch_array($res_stats)){  ?>
					<li><a href="<?php stats_referer() ?>"><?php stats_basedomain() ?></a></li>
				<?php } // End stat loop ?>
				<li><a href="<?php bloginfo('blogstatsurl') ?>"><?php echo _('more...') ?></a></li>
			</ul>
		<br />
		<h3><?php echo _('Top Referers') ?></h3>
			<?php refererList(5,'global',0,0,'no','baseDomain',($blog>1)?$blog:''); ?>
	   	<ul>
				<?php while($row_stats = mysql_fetch_array($res_stats)){  ?>
					<li><a href="<?php stats_referer() ?>"><?php stats_basedomain() ?></a></li>
				<?php } // End stat loop ?>
				<li><a href="<?php bloginfo('blogstatsurl') ?>"><?php echo _('more...') ?></a></li>
			</ul>
	</div>

	<?php } ?>


	<div class="bSideItem">
    <h3><?php echo _('Blogroll') ?></h3>
		<?php	// -------------------------- BLOGROLL INCLUDED HERE -----------------------------
			require( dirname(__FILE__)."/_blogroll.php"); 
			// -------------------------------- END OF BLOGROLL ---------------------------------- ?>
	</div>

	<div class="bSideItem">
    <h3><?php echo _('Misc') ?></h3>
		<ul>  
			<li><a href="<?php echo $pathserver?>/b2login.php"><?php echo _('Login...') ?></a></li>
			<li><a href="<?php echo $pathserver?>/b2register.php"><?php echo _('Register...') ?></a></li>
		</ul>	
	</div>

	<div class="bSideItem">
    <h3><?php echo _('Syndicate this blog') ?> <img src="../../img/xml.gif" alt="XML" width="36" height="14" class="middle" /></h3>

      <ul>
        <li><a href="<?php bloginfo('rss_url', 'raw'); ?>">RSS 0.92 (Userland)</a></li>
        <li><a href="<?php bloginfo('rdf_url', 'raw'); ?>">RSS 1.0 (RDF)</a></li>
        <li><a href="<?php bloginfo('rss2_url', 'raw'); ?>">RSS 2.0 (Userland)</a></li>
      </ul>
      <p><a href="http://www.xml.com/pub/a/2002/12/18/dive-into-xml.html" title="xml.com - External - English">What
        is RSS?</a> by Mark Pilgrim</p>

	</div>

	<p class="center">powered by<br />
	<a href="http://b2evolution.net/" title="b2evolution home"><img src="../../img/b2evolution_button.png" alt="b2evolution" width="80" height="15" border="0" class="middle" /></a></p>

</div>

<p class="baseline"> <a href="http://validator.w3.org/check/referer"><img style="border:0;width:88px;height:31px" src="http://www.w3.org/Icons/valid-xhtml10" alt="Valid XHTML 1.0!" class="middle" /></a> 
  <a href="http://jigsaw.w3.org/css-validator/"><img style="border:0;width:88px;height:31px" src="http://jigsaw.w3.org/css-validator/images/vcss" alt="Valid CSS!" class="middle" /></a>&nbsp;
<?php
	log_hit();	// log the hit on this page
	if ($debug==1)
	{
		printf( _('Totals: %d posts - %d queries - %01.3f seconds'), $result_num_rows, $querycount, timer_stop() );
	}
?>
</p>
</body>
</html>
