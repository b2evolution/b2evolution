<?php
	/*
	 * This is a demo template displaying multiple blogs on the same page
	 *
	 * If you're new to b2evolution templates or skins, you should not start with this file
	 * It will be easier to start examining blog_a.php or noskin_a.php for instance...
	 */

	# First blog will be displayed the regular way (why bother?)
	$blog = 2;		// 2 is for "demo blog A" or your upgraded blog (depends on your install)

	# Tell b2evolution you don't want to use evoSkins
	# (evoSkins are designed to display only one blog at once + optionnaly a linkblog)
	$skin = '';

	# This setting retricts posts to those published, thus hiding drafts.
	# You should not have to change this.
	$show_statuses = array();

	# Here you can set a limit before which posts will be ignored
	# You can use a unix timestamp value or 'now' which will hide all posts in the past
	$timestamp_min = '';

	# Here you can set a limit after which posts will be ignored
	# You can use a unix timestamp value or 'now' which will hide all posts in the future
	$timestamp_max = 'now';

	# Additionnaly, you can set other values (see URL params in the manual)...
	# $order = 'ASC'; // This for example would display the blog in chronological order...

	# Let b2evolution handle the query string and load the blog data:
	require(dirname(__FILE__).'/b2evocore/_blog_main.php');

	# Now, below you'll find the magic template...
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>"><!-- InstanceBegin template="/Templates/Standard.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
<!-- InstanceBeginEditable name="doctitle" -->
<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
	<title>Multiblog demo<?php
		single_cat_title( ' - ', 'htmlhead' );
		single_month_title( ' - ', 'htmlhead' );
		single_post_title( ' - ', 'htmlhead' );
		arcdir_title( ' - ', 'htmlhead' );
		last_comments_title( ' - ', 'htmlhead' );
		stats_title( ' - ', 'htmlhead' );
		profile_title( ' - ', 'htmlhead' );
	?></title>
<!-- InstanceEndEditable -->
<!-- InstanceBeginEditable name="head" -->
<base href="<?php skinbase(); // You're not using any skin here but this won't hurt. However it will be very helpfull to have this here when you make the switch to a skin! ?>" />
<meta name="description" content="<?php $Blog->disp( 'shortdesc', 'htmlattr' ); ?>" />
<meta name="keywords" content="<?php $Blog->disp( 'keywords', 'htmlattr' ); ?>" />
<meta name="generator" content="b2evolution <?php echo $b2_version ?>" /> <!-- Please leave this for stats -->
<link rel="alternate" type="text/xml" title="RDF" href="<?php $Blog->disp( 'rdf_url', 'raw' ) ?>" />
<link rel="alternate" type="text/xml" title="RSS .92" href="<?php $Blog->disp( 'rss_url', 'raw' ) ?>" />
<link rel="alternate" type="text/xml" title="RSS 2.0" href="<?php $Blog->disp( 'rss2_url', 'raw' ) ?>" />
<link rel="alternate" type="application/atom+xml" title="Atom" href="<?php $Blog->disp( 'atom_url', 'raw' ) ?>" />
<link rel="pingback" href="<?php $Blog->disp( 'pingback_url', 'raw' ) ?>" />
<link href="skins/fplanque2002/blog.css" rel="stylesheet" type="text/css" />
 <!-- InstanceEndEditable -->
<link rel="stylesheet" href="skins/fplanque2002/basic.css" type="text/css" />
<link rel="stylesheet" href="skins/fplanque2002/fpnav.css" type="text/css" />
</head>
<body>
<div class="pageHeader">
<div class="pageHeaderContent">

<!-- InstanceBeginEditable name="NavBar2" -->
<?php // --------------------------- BLOG LIST INCLUDED HERE -----------------------------
	# this is what will start and end your blog links
	$blog_list_start = '<div class="NavBar">';
	$blog_list_end = '</div>';
	# this is what will separate your blog links
	$blog_item_start = '';
	$blog_item_end = '';
	# This is the class of for the selected blog link:
	$blog_selected_link_class = 'NavButton2';
	# This is the class of for the other blog links:
	$blog_other_link_class = 'NavButton2';
	# This is additionnal markup before and after the selected blog name
	$blog_selected_name_before = '<span class="small">';
	$blog_selected_name_after = '</span>';
	# This is additionnal markup before and after the other blog names
	$blog_other_name_before = '<span class="small">';
	$blog_other_name_after = '</span>';
	// Include the bloglist
	require( get_path('skins').'/_bloglist.php');
	// ---------------------------------- END OF BLOG LIST --------------------------------- ?>
<!-- InstanceEndEditable -->

<div class="NavBar">
<div id="Logo">&nbsp;</div>
<div class="pageTitle">
<h1 id="pageTitle"><!-- InstanceBeginEditable name="PageTitle" --><?php echo T_('Multiblog demo') ?><!-- InstanceEndEditable --></h1>
</div>
</div>

<div class="pageHeaderEnd"></div>

</div>
</div>


<div class="pageSubTitle"><!-- InstanceBeginEditable name="SubTitle" --><?php echo T_('This demo template displays 3 blogs at once (1 on the left, 2 on the right)') ?><!-- InstanceEndEditable --></div>


<div class="main"><!-- InstanceBeginEditable name="Main" -->

<!-- =================================== START OF MAIN AREA =================================== -->

<div class="bPosts">
<h2>#1: <a href="<?php $Blog->disp( 'blogurl', 'raw' ) ?>"><?php echo $Blog->disp( 'name', 'htmlbody' ) ?></a></h2>
<h2><?php
	single_cat_title();
	single_month_title();
	single_post_title();
	arcdir_title();
	last_comments_title();
	stats_title();
	profile_title();
?></h2>


<?php // ------------------------------------ START OF POSTS ----------------------------------------
	if( isset($MainList) ) $MainList->display_if_empty(); // Display message if no post

	if( isset($MainList) ) while( $Item = $MainList->get_item() )
	{
	$MainList->date_if_changed();
	?>
	<div class="bPost" lang="<?php $Item->lang() ?>">
		<?php $Item->anchor(); ?>
		<div class="bSmallHead">
		<a href="<?php $Item->permalink() ?>" title="<?php echo T_('Permanent link to full entry') ?>"><img src="img/icon_minipost.gif" alt="<?php echo T_('Permalink') ?>" width="12" height="9" class="middle" /></a>
		<?php $Item->issue_time();	echo ', ', T_('Categories'), ': ';	$Item->categories() ?>
		</div>
		<h3 class="bTitle"><?php $Item->title(); ?></h3>
		<div class="bText">
			<?php $Item->content(); ?>
			<?php link_pages() ?>
		</div>
		<div class="bSmallPrint">
			<?php $Item->feedback_link( 'comments', '', ' &bull; ' ) // Link to comments ?>
			<?php $Item->feedback_link( 'trackbacks', '', ' &bull; ' ) // Link to trackbacks ?>
			<?php $Item->feedback_link( 'pingbacks', '', ' &bull; ' ) // Link to trackbacks ?>

			<?php $Item->trackback_rdf() // trackback autodiscovery information ?>

			<a href="<?php $Item->permalink() ?>" title="Permanent link to full entry"><?php echo T_('Permalink') ?></a>
		</div>
		<?php // ---------------- START OF INCLUDE FOR COMMENTS, TRACKBACK, PINGBACK, ETC. ----------------
		$disp_comments = 1;					// Display the comments if requested
		$disp_comment_form = 1;			// Display the comments form if comments requested
		$disp_trackbacks = 1;				// Display the trackbacks if requested

		$disp_trackback_url = 1;		// Display the trackbal URL if trackbacks requested
		$disp_pingbacks = 1;				// Display the pingbacks if requested
		require( get_path('skins').'/_feedback.php');
		// ------------------- END OF INCLUDE FOR COMMENTS, TRACKBACK, PINGBACK, ETC. ------------------- ?>
	</div>
<?php } // ---------------------------------- END OF POSTS ------------------------------------ ?>

	<p class="center"><strong><?php posts_nav_link(); ?></strong></p>

<?php // ---------------- START OF INCLUDES FOR LAST COMMENTS, STATS ETC. ----------------
	switch( $disp )
	{
		case 'comments':
			// this includes the last comments if requested:
			require( get_path('skins').'/_lastcomments.php' );
			break;

		case 'stats':
			// this includes the statistics if requested:
			require( get_path('skins').'/_stats.php');
			break;

		case 'arcdir':
			// this includes the archive directory if requested
			require( get_path('skins').'/_arcdir.php');
			break;

		case 'profile':
			// this includes the profile form if requested
			require( get_path('skins').'/_profile.php');
			break;
	}
// ------------------- END OF INCLUDES FOR LAST COMMENTS, STATS ETC. ------------------- ?>
</div>

<!-- =================================== START OF SIDEBAR =================================== -->

<div class="bSideBar">

	<!-- =================================== START OF BLOG B =================================== -->

	<div class="bSideItem">
	<?php
		// Dirty trick until we get everything into objects:
		$saved_blog = $blog;
		$blog = 3;	// Blog B now
		$Blog_B = Blog_get_by_ID( 3 );	// Blog B
	?>
		<h3>#2: <a href="<?php $Blog_B->disp( 'blogurl', 'raw' ) ?>"><?php echo $Blog_B->disp( 'name', 'htmlbody' ) ?></a></h3>
	<?php
		// You can restrict to specific categories by listing them in the two params below: '', array()
		// '', array(9,15) will restrict to cats 9 and 15
		// '9,15', array() will restrict to cats 9,15 and all their subcats
		$BlogBList = & new ItemList( $blog,	 $show_statuses, '', $m, $w, '', array(), $author, $order, $orderby, $posts, '', '', '', '', '', '', '', '3', 'posts', $timestamp_min, $timestamp_max );

		while( $Item = $BlogBList->get_item() )
		{
		?>
		<div class="bPostSide" lang="<?php $Item->lang() ?>">
			<?php $Item->anchor(); ?>

			<h3 class="bTitle"><a href="<?php $Item->permalink() ?>" title="<?php echo T_('Permanent link to full entry') ?>"><img src="img/icon_minipost.gif" alt="<?php echo T_('Permalink') ?>" width="12" height="9" class="middle" /></a><?php $Item->title(); ?></h3>
			<div class="bText">
				<?php $Item->content( 1, false ); ?>
				<?php link_pages() ?>
			</div>
		</div>
		<?php
		}

		// Restore after dirty trick:
		$blog = $saved_blog;
	?>
	</div>

	<!-- =================================== START OF BLOG C =================================== -->

	<div class="bSideItem">
	<?php
		// Dirty trick until we get everything into objects:
		$saved_blog = $blog;
		$blog = 4;		// Linkblog now
		$Blog_roll = Blog_get_by_ID( 4 ); // Blog roll
	?>
		<h3>#3: <a href="<?php $Blog_roll->disp( 'blogurl', 'raw' ) ?>"><?php echo $Blog_roll->disp( 'name', 'htmlbody' ) ?></a></h3>
	<?php
		// You can restrict to specific categories by listing them in the two params below: '', array()
		// '', array(9,15) will restrict to cats 9 and 15
		// '9,15', array() will restrict to cats 9,15 and all their subcats
		$LinkblogList = & new ItemList( $blog,	$show_statuses, '', $m, $w, '', array(), $author, $order, $orderby, $posts, '', '', '', '', '', '', '', '3', 'posts', $timestamp_min, $timestamp_max );

		while( $Item = $LinkblogList->get_item() )
		{
?>
		<div class="bPostSide" lang="<?php $Item->lang() ?>">
			<?php $Item->anchor(); ?>

			<h3 class="bTitle"><a href="<?php $Item->permalink() ?>" title="<?php echo T_('Permanent link to full entry') ?>"><img src="img/icon_minipost.gif" alt="<?php echo T_('Permalink') ?>" width="12" height="9" class="middle" /></a><?php $Item->title(); ?></h3>
			<div class="bText">
				<?php $Item->content( 1, false ); ?>
				<?php link_pages() ?>
			</div>
		</div>
		<?php
		}

		// Restore after dirty trick:
		$blog = $saved_blog;
	?>
	</div>

	<!-- =================================== END OF BLOG C =================================== -->


	<div class="bSideItem">
		<h3><?php echo T_('Misc') ?></h3>
		<ul>
			<?php
				// Administrative links:
				user_login_link( '<li>', '</li>' );
				user_register_link( '<li>', '</li>' );
				user_admin_link( '<li>', '</li>' );
				user_profile_link( '<li>', '</li>' );
				user_logout_link( '<li>', '</li>' );
			?>
		</ul>
	</div>

	<p class="center">powered by<br />
	<a href="http://b2evolution.net/" title="b2evolution home"><img src="<?php echo $img_url ?>/b2evolution_button.png" alt="b2evolution" width="80" height="15" border="0" class="middle" /></a></p>

</div>
<!-- InstanceEndEditable --></div>
<table cellspacing="3" class="wide">
	<tr>
	<td class="cartouche">Original page design by <a href="http://fplanque.net/">Fran&ccedil;ois PLANQUE</a> </td>

	<td class="cartouche" align="right"> <a href="http://b2evolution.net/" title="b2evolution home"><img src="img/b2evolution_button.png" alt="b2evolution" width="80" height="15" border="0" class="middle" /></a></td>
	</tr>
</table>
<p class="baseline">

	<a href="http://validator.w3.org/check/referer"><img style="border:0;width:88px;height:31px" src="http://www.w3.org/Icons/valid-xhtml10" alt="Valid XHTML 1.0!" class="middle" /></a>

	<a href="http://jigsaw.w3.org/css-validator/"><img style="border:0;width:88px;height:31px" src="http://jigsaw.w3.org/css-validator/images/vcss" alt="Valid CSS!" class="middle" /></a>

	<a href="http://feedvalidator.org/check.cgi?url=<?php $Blog->disp( 'rss2_url', 'raw' ) ?>"><img src="img/valid-rss.png" alt="Valid RSS!" style="border:0;width:88px;height:31px" class="middle" /></a>

	<a href="http://feedvalidator.org/check.cgi?url=<?php $Blog->disp( 'atom_url', 'raw' ) ?>"><img src="img/valid-atom.png" alt="Valid Atom!" style="border:0;width:88px;height:31px" class="middle" /></a>
	&nbsp;<!-- InstanceBeginEditable name="Baseline" -->
<?php
	log_hit();	// log the hit on this page
	debug_info(); // output debug info if requested
?>
<!-- InstanceEndEditable --></p>
</body>
<!-- InstanceEnd --></html>
