<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<!-- layout credits goto http://bluerobot.com/web/layouts/layout2.html -->

<head xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
	<title><?php bloginfo('name') ?><?php single_post_title(' :: ', 'htmlhead') ?><?php single_cat_title(' :: ', 'htmlhead') ?><?php single_month_title(' :: ', 'htmlhead') ?></title>
	<base href="<?php skinbase(); // Base URL for this skin. You need this to fix relative links! ?>" />
	<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
	<meta http-equiv="reply-to" content="<?php bloginfo('admin_email'); ?>" />
	<meta http-equiv="imagetoolbar" content="no" />
	<meta content="TRUE" name="MSSmartTagsPreventParsing" />
	<meta name="description" content="<?php bloginfo('shortdesc', 'htmlattr'); ?>" />
	<meta name="keywords" content="<?php bloginfo('keywords', 'htmlattr'); ?>" />
	<style type="text/css" media="screen">
	@import url(layout2b.css);
	</style>
	<link rel="stylesheet" type="text/css" media="print" href="print.css" />
	<link rel="alternate" type="text/xml" title="RDF" href="<?php bloginfo('rdf_url'); ?>" />
	<link rel="alternate" type="text/xml" title="RSS" href="<?php bloginfo('rss2_url'); ?>" />
	<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
	<?php comments_popup_script() // Included javascript to open pop up windows ?>
</head>
<body>
<?php // --------------------------- BLOG LIST INCLUDED HERE -----------------------------
	require( dirname(__FILE__)."/_bloglist.php"); 
	// ---------------------------------- END OF BLOG LIST --------------------------------- ?>

<div id="header"><a href="<?php bloginfo('blogurl'); ?>" title="<?php bloginfo('name'); ?>"><?php bloginfo('name'); ?></a></div>

<div id="content">


<?php	// -------------------------------------- START OF POSTS ---------------------------------------
if( isset($MainList) ) while( $MainList->get_item() )
{
	the_date( '', '<h2>', '</h2>' );
	permalink_anchor(); 
?>
<div class="storyTitle"><?php the_title(); ?>
&nbsp;-&nbsp;
Categories: <?php the_categories() ?>
&nbsp;-&nbsp;
<span class="storyAuthor"><a href="<?php bloginfo('blogurl'); ?>?author=<?php the_author_ID() ?>" title="<?php echo T_('Browse all posts by this author') ?>"><?php the_author() ?></a> - <?php the_author_email() ?></span> @ <a href="<?php permalink_link() ?>"><?php the_time() ?></a>
</div>

<div class="storyContent">
<?php the_content(); ?>

<div class="rightFlush">
<?php link_pages("<br />Pages: ","<br />","number") ?> 
<?php comments_popup_link() ?> 
<?php trackback_popup_link() ?> 
<?php pingback_popup_link() ?>

<?php trackback_rdf() ?>

<?php
		// THIS is an example of how to display unmixed comments, trackbacks and pingbacks.
		// doing it old b2 style :>>
		
		// this includes the comments and a form to add a new comment
		$disp_comments = 1;					// Display the comments if requested
		$disp_comment_form = 1;			// Display the comments form if comments requested
		$disp_trackbacks = 0;				// Display the trackbacks if requested
		$disp_trackback_url = 0;		// Display the trackbal URL if trackbacks requested
		$disp_pingbacks = 0;				// Display the pingbacks if requested
		$disp_title = "Comments:";
		require( dirname(__FILE__)."/_feedback.php");

		// this includes the trackbacks
		$disp_comments = 0;					// Display the comments if requested
		$disp_comment_form = 0;			// Display the comments form if comments requested
		$disp_trackbacks = 1;				// Display the trackbacks if requested
		$disp_trackback_url = 1;		// Display the trackbal URL if trackbacks requested
		$disp_pingbacks = 0;				// Display the pingbacks if requested
		$disp_title = "Trackbacks:";
		require( dirname(__FILE__)."/_feedback.php");

		// this includes the pingbacks
		$disp_comments = 0;					// Display the comments if requested
		$disp_comment_form = 0;			// Display the comments form if comments requested
		$disp_trackbacks = 0;				// Display the trackbacks if requested
		$disp_trackback_url = 0;		// Display the trackbal URL if trackbacks requested
		$disp_pingbacks = 1;				// Display the pingbacks if requested
		$disp_title = "Pingbacks:";
		require( dirname(__FILE__)."/_feedback.php");
?>

</div>

</div>

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
<p class="centerP"><?php timer_stop(1); ?>
	powered by<br />
	<a href="http://b2evolution.net/" title="b2evolution home"><img src="../../img/b2evolution_button.png" width="80" height="15" border="0" alt="b2evolution" /></a>
</p>


<div id="menu">

<h4>quick links:</h4>
<?php bloginfo('blogroll'); ?>

<h4>categories:</h4>
<form action="<?php bloginfo('blogurl') ?>" method="get">
<?php	require( dirname(__FILE__)."/_categories.php"); ?>
<input type="submit" value="<?php echo T_('Get selection') ?>" />
</form>


<h4>search:</h4>

<form name="searchform" method="get" action="<?php bloginfo('blogurl') ?>">
	<input type="text" name="s" size="15" style="width: 100%" />
	<input type="submit" name="submit" value="<?php echo T_('Search') ?>" />
</form>

<h4><?php echo T_('archives') ?>:</h4>
<ul class="compress">
<?php	require( dirname(__FILE__)."/_archives.php"); ?>
</ul>

<h4>skins:</h4>
<ul>
	<?php // ---------------------------------- START OF SLIN LIST ----------------------------------
	for( skin_list_start(); skin_list_next(); ) { ?>
		<li><a href="<?php skin_change_url() ?>"><?php skin_list_iteminfo( 'name' ) ?></a></li>
	<?php } // --------------------------------- END OF SKIN LIST --------------------------------- ?>
</ul>

<h4>other:</h4>
<?php 
	// Administrative links:
	user_login_link( '', '<br />' ); 
	user_register_link( '', '<br />' ); 
	user_admin_link( '', '<br />' ); 
	user_profile_link( '', '<br />' ); 
	user_logout_link( '', '<br />' ); 
?>
<br />

<a href="<?php bloginfo('rss2_url'); ?>"><img src="../../img/xml.gif" alt="view this weblog as RSS !" width="36" height="14" border="0"  /></a><br />
<a href="http://validator.w3.org/check/referer" title="this page validates as XHTML 1.0 Transitional"><img src="http://www.w3.org/Icons/valid-xhtml10" alt="Valid XHTML 1.0!" height="31" width="88" border="0" /></a><br />
<a href="http://feedvalidator.org/check.cgi?url=<?php bloginfo('rss2_url', 'raw'); ?>"><img src="../../img/valid-rss.png" alt="Valid RSS!" style="border:0;width:88px;height:31px" class="middle" /></a>
</div>

<?php
	log_hit();	// log the hit on this page
	if ($debug==1)
	{
		printf( T_('Totals: %d posts - %d queries - %01.3f seconds'), $result_num_rows, $querycount, timer_stop() );
	}
?>
</body>
</html>

