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
		$Blog->disp('name', 'htmlhead');
		single_cat_title( ' - ', 'htmlhead' );
		single_month_title( ' - ', 'htmlhead' );
		single_post_title( ' - ', 'htmlhead' );
		arcdir_title( ' - ', 'htmlhead' );
		last_comments_title( ' - ', 'htmlhead' );
		stats_title( ' - ', 'htmlhead' );
		profile_title( ' - ', 'htmlhead' );
	?>
	</title>
	<base href="<?php skinbase(); // Base URL for this skin. You need this to fix relative links! ?>" />
	<meta name="description" content="<?php $Blog->disp( 'shortdesc', 'htmlattr' ); ?>" />
	<meta name="keywords" content="<?php $Blog->disp( 'keywords', 'htmlattr' ); ?>" />
	<meta name="generator" content="b2evolution <?php echo $b2_version ?>" /> <?php /* -- Please leave this for stats -- */ ?>
	<link rel="alternate" type="text/xml" title="RDF" href="<?php $Blog->disp( 'rdf_url', 'raw' ) ?>" />
	<link rel="alternate" type="text/xml" title="RSS .92" href="<?php $Blog->disp( 'rss_url', 'raw' ) ?>" />
	<link rel="alternate" type="text/xml" title="RSS 2.0" href="<?php $Blog->disp( 'rss2_url', 'raw' ) ?>" />
	<link rel="alternate" type="application/atom+xml" title="Atom" href="<?php $Blog->disp( 'atom_url', 'raw' ) ?>" />
	<link rel="pingback" href="<?php $Blog->disp( 'pingback_url', 'raw' ) ?>" />
	<link title="Ciel bleu" media="screen" href="guadeloupe.css" type="text/css" rel="stylesheet" />
</head>
<body id=standblog>

<p id=prelude>
<a title="Sauter la navigation et la recherche" href="#main">Skip to content</a> | <a title="Aller directement au menu de navigation" href="#menu">Skip to menu</a> | <a title="Aller directement au formulaire de recherche" href="#searchform">Skip to search</a>
</p>

<h1><a href="<?php $Blog->disp( 'blogurl', 'raw' ) ?>"><?php $Blog->disp( 'name', 'htmlbody' ) ?></a></h1>
<div id="tagline"><?php $Blog->disp( 'tagline', 'htmlbody' ) ?></div>

<div id=main>
<h2><?php
	single_cat_title();
	single_month_title();
	single_post_title();
	arcdir_title();
	last_comments_title();
	stats_title();
	profile_title();
?></h2>

<p class="center"><strong><?php posts_nav_link(); ?></strong></p>

<?php // ------------------------------------ START OF POSTS --------------------------------------
	if( isset($MainList) ) $MainList->display_if_empty();	// Display message if no post

	if( isset($MainList) ) while( $Item = $MainList->get_item() )
{
?>

<div class="bPost" lang="<?php $Item->lang() ?>">
<?php $Item->anchor(); ?>
<h2><?php $Item->title(); ?></h2>
<div class=infos>
<h3><a href="<?php $Item->permalink() ?>" title="Permalink"><?php $Item->issue_date() ?> <?php $Item->issue_time() ?></a></h3>
&nbsp;
<h4><?php $Item->categories() ?></h4>
</div>
<div class=article>
	<?php $Item->content(); ?>
	<?php link_pages("<br />Pages: ","<br />","number") ?>
</div>
<div class=interaction>
	<?php $Item->feedback_link( 'feedbacks' ) // Link to comments ?>
	<?php $Item->trackback_rdf() // trackback autodiscovery information ?>
</div>

<div class="contenuinteraction">
<?php
		// this includes the trackback url, comments, trackbacks, pingbacks and a form to add a new comment
		$disp_comments = 1;					// Display the comments if requested
		$disp_comment_form = 1;			// Display the comments form if comments requested
		$disp_trackbacks = 1;				// Display the trackbacks if requested
		$disp_trackback_url = 1;		// Display the trackbal URL if trackbacks requested
		$disp_pingbacks = 1;				// Display the pingbacks if requested
		require( dirname(__FILE__).'/_feedback.php' );
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

<p class="center"><strong><?php posts_nav_link(); ?></strong></p>

</div>
<div id=menu>

<h4><?php $Blog->disp( 'name', 'htmlbody' ) ?>&nbsp;:</h4>
<?php $Blog->disp('longdesc', 'htmlbody') ?>
<ul>
	<li><a href="<?php $Blog->disp( 'staticurl', 'raw' ) ?>"><strong><?php echo T_('Recently') ?></strong></a><?php echo T_('(cached)') ?></li>
	<li><a href="<?php $Blog->disp( 'dynurl', 'raw' ) ?>"><strong><?php echo T_('Recently') ?></strong></a><?php echo T_('(no cache)') ?></li>
</ul>
<ul>
	<li><a href="<?php $Blog->disp( 'lastcommentsurl', 'raw' ) ?>"><strong><?php echo T_('Last comments') ?></strong></a></li>
	<li><a href="<?php $Blog->disp( 'blogstatsurl', 'raw' ) ?>"><strong>Stats</strong></a></li>
</ul>


<?php // --------------------------- BLOG LIST INCLUDED HERE -----------------------------
	require( dirname(__FILE__).'/_bloglist.php' );
	// ---------------------------------- END OF BLOG LIST --------------------------------- ?>

<div id=categories>

<h4>Categories&nbsp;:</h4>
<!-- ---------------------------- START OF CATEGORIES ---------------------------- -->
<form action="<?php $Blog->disp( 'blogurl', 'raw' ) ?>" method="get">
<?php require( dirname(__FILE__).'/_categories.php' ); ?>
<input type="submit" value="<?php echo T_('Get selection') ?>" />
<input type="reset" value="<?php echo T_('Reset form') ?>" />
</form>
<!-- ----------------------------- END OF CATEGORIES ----------------------------- -->
</div>

<h4>Search&nbsp;:</h4>
<form action=/index.php method=get>
<div id=searchform>
<ul>
	<li><input id="rechercher" size="15" name="s" />
	<li><input type="submit" value="envoyer" name="submit" /> </li></ul></div>
</form>


<form id=switcher action="<?php $Blog->disp( 'blogurl', 'raw' ) ?>" method=get>
	<fieldset><label for=set><h4><?php echo T_('Choose a skin') ?>&nbsp;:</h4></label>
	<select id=set name="skin">
		<?php // ---------------------------------- START OF SKIN LIST ----------------------------------
		for( skin_list_start(); skin_list_next(); )
		{
			echo '<option value="';
			skin_list_iteminfo( 'name' );
			echo '"';
			if( skin_list_iteminfo( 'name',false ) == $skin ) echo ' selected="selected" ';
			echo '>';
			skin_list_iteminfo( 'name' );
			echo "</option>\n";
		} // --------------------------------- END OF SKIN LIST --------------------------------- ?>
	</select>
	<input type="submit" value="Ok" />
	</fieldset>
</form>


<h4><?php echo T_('Archives') ?>&nbsp;:</h4>
<ul>
	<?php require( dirname(__FILE__).'/_archives.php'); ?>
</ul>

<?php if (! $stats) { ?>

	<h4><?php echo T_('Recent Referers') ?></h4>
	<?php refererList(5, 'global', 0, 0, 'no', '', ($blog > 1) ? $blog : ''); ?>
	<ul>
		<?php if( count( $res_stats ) ) foreach( $res_stats as $row_stats ) { ?>
			<li><a href="<?php stats_referer() ?>"><?php stats_basedomain() ?></a></li>
		<?php } // End stat loop ?>
		<li><a href="<?php $Blog->disp( 'blogstatsurl', 'raw' ) ?>"><?php echo T_('more...') ?></a></li>
	</ul>
	<br />
	<h4><?php echo T_('Top Referers') ?></h4>
	<?php refererList(5,'global',0,0,'no','baseDomain',($blog>1)?$blog:''); ?>
	<ul>
		<?php if( count( $res_stats ) ) foreach( $res_stats as $row_stats ) { ?>
			<li><a href="<?php stats_referer() ?>"><?php stats_basedomain() ?></a></li>
		<?php } // End stat loop ?>
		<li><a href="<?php $Blog->disp( 'blogstatsurl', 'raw' ) ?>"><?php echo T_('more...') ?></a></li>
	</ul>

<?php } ?>

<h4><?php echo T_('Misc') ?></h4>
<ul>
	<?php
		// Administrative links:
		user_login_link( '<li>', '</li>' );
		user_register_link( '<li>', '</li>' );
		user_admin_link( '<li>', '</li>' );
		user_profile_link( '<li>', '</li>' );
		user_logout_link( '<li>', '</li>' );
	?>
	<li><a href="<?php $Blog->disp( 'rss_url', 'raw' ) ?>">RSS 0.92 (Userland)</a></li>
	<li><a href="<?php $Blog->disp( 'rdf_url', 'raw' ) ?>">RSS 1.0 (RDF)</a></li>
	<li><a href="<?php $Blog->disp( 'rss2_url', 'raw' ) ?>">RSS 2.0 (Userland)</a></li>
	<li><a href="<?php $Blog->disp( 'atom_url', 'raw' ) ?>">Atom 0.3</a></li>
	<li><a href="http://validator.w3.org/check/referer">XHTML valide</a>
</li>
</ul>

Powered by <a href="http://b2evolution.net/" title="b2evolution home"><img src="../../img/b2evolution_button.png" alt="b2evolution" width="80" height="15" border="0" class="logo" /></a>

</div>

<p class="baseline">
This site works better with web standards! Original skin design courtesy of <a href="http://standblog.com/">Tristan NITOT</a>.
<?php
	log_hit();	// log the hit on this page
	debug_info(); // output debug info if requested
?>
</p>
</body>
</html>
