<?php
/**
 * This is a demo template displaying multiple blogs on the same page
 *
 * If you're new to b2evolution templates or skins, you should not start with this file
 * It will be easier to start examining blog_a.php or noskin_a.php for instance...
 *
 * @package evoskins
 * @subpackage noskin
 */

# First blog will be displayed the regular way (why bother?)
$blog = 1;

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

/**
 * Let b2evolution handle the query string and load the blog data:
 */
require_once dirname(__FILE__).'/conf/_config.php';

require_once $inc_path.'_blog_main.inc.php';

# Now, below you'll find the magic template...

header( 'Content-type: text/html; charset='.$io_charset );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>"><!-- InstanceBegin template="/Templates/Standard.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
<!-- InstanceBeginEditable name="doctitle" -->
	<title>Multiblog demo<?php request_title( ' - ', '', ' - ', 'htmlhead' ) ?></title>
	<!-- InstanceEndEditable -->
<!-- InstanceBeginEditable name="head" -->
	<?php skin_base_tag(); /* You're not using any skin here but this won't hurt. However it will be very helpful to have this here when you make the switch to a skin! */ ?>
	<meta name="description" content="<?php $Blog->disp( 'shortdesc', 'htmlattr' ); ?>" />
	<meta name="keywords" content="<?php $Blog->disp( 'keywords', 'htmlattr' ); ?>" />
	<meta name="generator" content="b2evolution <?php echo $app_version ?>" /> <!-- Please leave this for stats -->
	<link rel="alternate" type="text/xml" title="RSS 2.0" href="<?php $Blog->disp( 'rss2_url', 'raw' ) ?>" />
	<link rel="alternate" type="application/atom+xml" title="Atom" href="<?php $Blog->disp( 'atom_url', 'raw' ) ?>" />
	<!-- InstanceEndEditable -->
<link rel="stylesheet" href="rsc/css/fp02.css" type="text/css" />
</head>
<body>
<div class="pageHeader">
<div class="pageHeaderContent">

<!-- InstanceBeginEditable name="NavBar2" -->
	<?php
	// --------------------------- BLOG LIST INCLUDED HERE -----------------------------
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
	require $skins_path.'_bloglist.php';
	// ---------------------------------- END OF BLOG LIST ---------------------------------
	?>
	<!-- InstanceEndEditable -->

<div class="NavBar">
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
<?php request_title( '<h2>', '</h2>' ) ?>

<?php // ------------------------------------ START OF POSTS ----------------------------------------
	if( isset($MainList) ) $MainList->display_if_empty(); // Display message if no post

	if( isset($MainList) ) while( $Item = & $MainList->get_item() )
	{
		$MainList->date_if_changed();
		?>

	<div class="bPost" lang="<?php $Item->lang() ?>">
		<?php $Item->anchor(); ?>
		<div class="bSmallHead">
		<?php $Item->permanent_link( get_icon('permalink') ); ?>
		<?php $Item->issue_time();	echo ', ', T_('Categories'), ': ';	$Item->categories() ?>
		</div>
		<h3 class="bTitle"><?php $Item->title(); ?></h3>
		<div class="bText">
			<?php $Item->content(); ?>
			<?php
				// Links to post pages (for multipage posts):
				$Item->page_links( '<p class="right">'.T_('Pages:').' ', '</p>', ' &middot; ' );
			?>
		</div>
		<div class="bSmallPrint">
			<?php $Item->feedback_link( 'comments', '', ' &bull; ' ) /* Link to comments */ ?>
			<?php $Item->feedback_link( 'trackbacks', '', ' &bull; ' ) /* Link to trackbacks */ ?>

			<?php $Item->trackback_rdf() /* trackback autodiscovery information */ ?>

			<?php	$Item->permanent_link(); ?>
		</div>

		<?php
		// ---------------- START OF INCLUDE FOR COMMENTS, TRACKBACK, PINGBACK, ETC. ----------------
		$disp_comments = 1;          // Display the comments if requested
		$disp_comment_form = 1;      // Display the comments form if comments requested
		$disp_trackbacks = 1;        // Display the trackbacks if requested

		$disp_trackback_url = 1;   // Disp  lay the trackbal URL if trackbacks requested
		$disp_pingbacks = 0;        // Don't display the pingbacks (deprecated)
		require $skins_path.'_feedback.php';
		// ------------------- END OF INCLUDE FOR COMMENTS, TRACKBACK, PINGBACK, ETC. -------------------
		?>
	</div>
<?php
	} // ---------------------------------- END OF POSTS ------------------------------------
?>

	<?php
		// Links to list pages:
		$MainList->page_links( '<p class="center"><strong>', '</strong></p>', '$prev$ :: $next$', array(
   			'prev_text' => '&lt;&lt; '.T_('Previous'),
   			'next_text' => T_('Next').' &gt;&gt;',
			) );
	?>

	<?php
		// -------------- START OF INCLUDES FOR LATEST COMMENTS, MY PROFILE, ETC. --------------
		// Note: you can customize any of the sub templates included here by
		// copying the matching php file into the same directory as this file.
		$current_skin_includes_path = dirname(__FILE__).'/';
		// Call the dispatcher:
		require $skins_path.'_dispatch.inc.php';
		// --------------- END OF INCLUDES FOR LATEST COMMENTS, MY PROFILE, ETC. ---------------
	?>

</div>

<!-- =================================== START OF SIDEBAR =================================== -->

<div class="bSideBar">

	<!-- =================================== START OF BLOG B =================================== -->

	<div class="bSideItem">
		<?php
		$BlogCache = & get_Cache( 'BlogCache' );
		$Blog_B = & $BlogCache->get_by_ID( 2, false );
		if( empty($Blog_B) )
		{
			echo T_('Blog #2 doesn\'t seem to exist.');
		}
		else
		{
		?>

		<h3>#2: <a href="<?php $Blog_B->disp( 'blogurl', 'raw' ) ?>"><?php echo $Blog_B->disp( 'name', 'htmlbody' ) ?></a></h3>
		<?php
		$BlogBList = & new ItemList2( $Blog_B, $timestamp_min, $timestamp_max, $posts );

		$BlogBList->set_filters( array(
				'authors' => $author,
				'ymdhms' => $m,
				'week' => $w,
				'order' => $order,
				'orderby' => $orderby,
				'unit' => $unit,
			) );

		// Run the query:
		$BlogBList->query();

		while( $Item = & $BlogBList->get_item() )
		{
			?>
			<div class="bPostSide" lang="<?php $Item->lang() ?>">
				<?php $Item->anchor(); ?>
				<h3 class="bTitle">
					<?php $Item->permanent_link( get_icon('permalink') ); ?>
					<?php $Item->title(); ?>
				</h3>
				<div class="bText">
					<?php $Item->content( 1, false ); ?>
					<?php
						// Links to post pages (for multipage posts):
						$Item->page_links( '<p class="right">'.T_('Pages:').' ', '</p>', ' &middot; ', '', 1 );
					?>
				</div>
			</div>
			<?php
		}
		}
		?>
	</div>

	<!-- =================================== START OF BLOG C =================================== -->

	<div class="bSideItem">
		<?php
		$Blog_roll = & $BlogCache->get_by_ID( 3, false );
		if( empty($Blog_roll) )
		{
			echo T_('Blog #3 doesn\'t seem to exist.');
		}
		else
		{
		?>
		<h3>#3: <a href="<?php $Blog_roll->disp( 'blogurl', 'raw' ) ?>"><?php echo $Blog_roll->disp( 'name', 'htmlbody' ) ?></a></h3>
		<?php
		$LinkblogList = & new ItemList2( $Blog_roll, $timestamp_min, $timestamp_max, $posts );

		$LinkblogList->set_filters( array(
				'authors' => $author,
				'ymdhms' => $m,
				'week' => $w,
				'order' => $order,
				'orderby' => $orderby,
				'unit' => $unit,
			) );

		// Run the query:
		$LinkblogList->query();

		while( $Item = & $LinkblogList->get_item() )
		{
			?>
			<div class="bPostSide" lang="<?php $Item->lang() ?>">
				<?php $Item->anchor(); ?>
				<h3 class="bTitle">
					<?php $Item->permanent_link( get_icon('permalink') ); ?>
					<?php $Item->title(); ?>
				</h3>
				<div class="bText">
					<?php $Item->content( 1, false ); ?>
					<?php
						// Links to post pages (for multipage posts):
						$Item->page_links( '<p class="right">'.T_('Pages:').' ', '</p>', ' &middot; ', '', 1 );
					?>
				</div>
			</div>
			<?php
		}
		}
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
				user_subs_link( '<li>', '</li>' );
				user_logout_link( '<li>', '</li>' );
			?>
		</ul>
	</div>

</div>
<!-- InstanceEndEditable --></div>
<table cellspacing="3" class="wide">
  <tr>
  <td class="cartouche">Original page design by <a href="http://fplanque.net/">Fran&ccedil;ois PLANQUE</a> </td>

	<td class="cartouche" align="right"> <a href="http://b2evolution.net/" title="b2evolution home"><img src="rsc/img/b2evolution_button.png" alt="b2evolution" width="80" height="15" class="middle" /></a></td>
  </tr>
</table>
<p class="baseline"><!-- InstanceBeginEditable name="Baseline" -->
</p>
<?php
	$Hit->log();  // log the hit on this page
	debug_info(); // output debug info if requested
?>
<p><!-- Note: don't mess with the template here :/ --><!-- InstanceEndEditable --></p>
</body>
<!-- InstanceEnd --></html>