<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<!-- layout credits goto http://bluerobot.com/web/layouts/layout2.html -->

<head xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
	<title><?php $Blog->disp( 'name', 'htmlbody' ) ?><?php single_post_title(' :: ', 'htmlhead') ?><?php single_cat_title(' :: ', 'htmlhead') ?><?php single_month_title(' :: ', 'htmlhead') ?></title>
	<base href="<?php skinbase(); // Base URL for this skin. You need this to fix relative links! ?>" />
	<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
	<meta http-equiv="imagetoolbar" content="no" />
	<meta content="TRUE" name="MSSmartTagsPreventParsing" />
	<meta name="description" content="<?php $Blog->disp( 'shortdesc', 'htmlattr' ); ?>" />
	<meta name="keywords" content="<?php $Blog->disp( 'keywords', 'htmlattr' ); ?>" />
	<style type="text/css" media="screen">
	@import url(layout2b.css);
	</style>
	<link rel="stylesheet" type="text/css" media="print" href="print.css" />
	<meta name="generator" content="b2evolution <?php echo $b2_version ?>" /> <!-- Please leave this for stats -->
	<link rel="alternate" type="text/xml" title="RDF" href="<?php $Blog->disp( 'rdf_url', 'raw' ) ?>" />
	<link rel="alternate" type="text/xml" title="RSS .92" href="<?php $Blog->disp( 'rss_url', 'raw' ) ?>" />
	<link rel="alternate" type="text/xml" title="RSS 2.0" href="<?php $Blog->disp( 'rss2_url', 'raw' ) ?>" />
	<link rel="alternate" type="application/atom+xml" title="Atom" href="<?php $Blog->disp( 'atom_url', 'raw' ) ?>" />
	<link rel="pingback" href="<?php $Blog->disp( 'pingback_url', 'raw' ) ?>" />
	<?php comments_popup_script() // Include javascript to open pop up windows ?>
</head>
<body>
<?php // --------------------------- BLOG LIST INCLUDED HERE -----------------------------
	require( dirname(__FILE__)."/_bloglist.php"); 
	// ---------------------------------- END OF BLOG LIST --------------------------------- ?>

<div id="header"><a href="<?php $Blog->disp( 'blogurl', 'raw' ) ?>" title="<?php $Blog->disp( 'name', 'htmlattr' ) ?>"><?php $Blog->disp( 'name', 'htmlbody' ) ?></a></div>

<div id="content">


<?php	// ----------------------------------- START OF POSTS ------------------------------------
if( isset($MainList) ) while( $Item = $MainList->get_item() )
{
	$MainList->date_if_changed();
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
	<span class="storyAuthor"><a href="<?php $Blog->disp( 'blogurl', 'raw' ) ?>?author=<?php the_author_ID() ?>" title="<?php echo T_('Browse all posts by this author') ?>"><?php $Item->Author->prefered_name() ?></a></span>
	@ <a href="<?php $Item->permalink() ?>"><?php $Item->issue_time() ?></a>
</div>

<div class="storyContent">
<?php $Item->content(); ?>

<div class="rightFlush">
<?php link_pages("<br />Pages: ","<br />","number") ?> 

<?php $Item->feedback_link( 'comments' ) // Link to comments ?>
<?php $Item->feedback_link( 'trackbacks', ' &bull; ' ) // Link to trackbacks ?>
<?php $Item->feedback_link( 'pingbacks', ' &bull; ' ) // Link to trackbacks ?>

<?php $Item->trackback_rdf() // trackback autodiscovery information ?>

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

<?php	locale_restore_previous();	// Restore previous locale (Blog locale) ?>

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

<p><?php $Blog->disp( 'longdesc', 'htmlbody' ); ?></p>

<h4>categories:</h4>
<form action="<?php $Blog->disp( 'blogurl', 'raw' ) ?>" method="get">
<?php	require( dirname(__FILE__)."/_categories.php"); ?>
<input type="submit" value="<?php echo T_('Get selection') ?>" />
</form>


<h4>search:</h4>

<form name="searchform" method="get" action="<?php $Blog->disp( 'blogurl', 'raw' ) ?>">
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

<a href="<?php $Blog->disp( 'rss2_url', 'raw' ) ?>"><img src="../../img/xml.gif" alt="view this weblog as RSS !" width="36" height="14" border="0"  /></a><br />
<a href="http://validator.w3.org/check/referer" title="this page validates as XHTML 1.0 Transitional"><img src="http://www.w3.org/Icons/valid-xhtml10" alt="Valid XHTML 1.0!" height="31" width="88" border="0" /></a><br />
<a href="http://feedvalidator.org/check.cgi?url=<?php $Blog->disp( 'rss2_url', 'raw' ) ?>"><img src="../../img/valid-rss.png" alt="Valid RSS!" style="border:0;width:88px;height:31px" class="middle" /></a>
</div>
<?php 
	log_hit();	// log the hit on this page
	debug_info();	// output debug info if requested
?>
</body>
</html>

