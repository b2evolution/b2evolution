<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file built upon code from original b2 - http://cafelog.com/
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
	<title>b2evo &gt; <?php echo $title; ?></title>
	<link href="admin.css" rel="stylesheet" type="text/css" />
	<?php if( $mode == 'sidebar' )
	{ ?>
	<link href="sidebar.css" rel="stylesheet" type="text/css" />
	<?php } ?>
</head>
<body>

<?php
param( 'blog', 'integer', 0, true );	// We need this for the urls

if( empty($mode) )
{	// We're not running in an special mode (bookmarklet, sidebar...)
?>

<div id="header">
	<a href="http://b2evolution.net/" title="<?php echo T_("visit b2evolution's website") ?>"><img id="evologo" src="../img/b2evolution_minilogo2.png" alt="b2evolution"  title="<?php echo T_("visit b2evolution's website") ?>" width="185" height="40" /></a>

	<div id="headfunctions">
		<a href="<?php echo $htsrv_url ?>/login.php?action=logout"><?php echo T_('Logout') ?></a> &middot;
		<a href="<?php echo $baseurl ?>"><?php echo T_('Exit to blogs') ?></a><br />
	</div>

	<?php /* FOR TESTING ONLY - OBHANDLERTEST
	<div id="headinfo">
		b2evo v <strong><?php echo $b2_version ?></strong>
		&middot; <?php echo T_('Blog time:') ?> <strong><?php echo date_i18n( locale_timefmt(), $localtimenow ) ?></strong>
		&middot; <?php echo T_('GMT:') ?> <strong><?php echo gmdate( locale_timefmt(), $servertimenow); ?></strong>
		&middot; <?php echo T_('Logged in as:'), ' <strong>', $user_login; ?></strong>
	</div>
	FOR TESTING ONLY - OBHANDLERTEST */ ?>
	
	
	<ul>
	<?php
		if( ($title == T_('New post in blog:')) || ($title == T_('Adding new post...')) )
			echo '<li class="current">';
		else
			echo '<li>';
		echo '<a href="b2edit.php?blog=', $blog, '" style="font-weight: bold;">', T_('New Post'), '</a></li>';

		if( ($title == T_('Browse blog:')) || ($title == T_('Editing post')) || ($title == T_('Updating post...')) || ($title == T_('Updating post status...')) || ($title == T_('Deleting post...'))  )
			echo '<li class="current">';
		else
			echo '<li>';
		echo '<a href="b2browse.php?blog=', $blog, '" style="font-weight: bold;">', T_('Browse/Edit'), '</a></li>';

		if( $title == T_('Categories for blog:') )
			echo '<li class="current">';
		else
			echo '<li>';
		echo '<a href="b2categories.php?blog=', $blog, '" >', T_('Cats'), '</a></li>';

		if( $title == T_('Blogs') )
			echo '<li class="current">';
		else
			echo '<li>';
		echo '<a href="b2blogs.php" >', T_('Blogs'), '</a></li>';

		if( $current_User->check_perm( 'stats', 'view' ) )
		{
			if( $title == T_('View Stats') )
				echo '<li class="current">';
			else
				echo '<li>';
			echo '<a href="b2stats.php" >', T_('Stats'), '</a></li>';
		}

		if( $current_User->check_perm( 'spamblacklist', 'view' ) )
		{
			if( $title == T_('Antispam') )
				echo '<li class="current">';
			else
				echo '<li>';
			echo '<a href="b2antispam.php" >', T_('Antispam'), '</a></li>';
		}

		if( $current_User->check_perm( 'templates', 'any' ) )
		{
			if( $title == T_('Custom skin template editing') )
				echo '<li class="current">';
			else
				echo '<li>';
			echo '<a href="b2template.php">', T_('Templates'), '</a></li>';
		}

		if( $current_User->check_perm( 'users', 'view' ) )
		{
			if( $title == T_('User management') )
				echo '<li class="current">';
			else
				echo '<li>';
			echo '<a href="b2users.php" >', T_('Users'), '</a></li>';
		}

		if( $current_User->check_perm( 'options', 'view' ) )
		{
			if( $title == T_('Options') )
				echo '<li class="current">';
			else
				echo '<li>';
			echo '<a href="b2options.php" >', T_('Options'), '</a></li>';
		}

		if( $title == T_('My Profile') )
			echo '<li class="current">';
		else
			echo '<li>';
		echo '<a href="b2profile.php" >', T_('My Profile'), '</a></li>';

	?>

	</ul>
</div>

<?php
}	// not in special mode
?>

<h1><strong>:: <?php echo $title; ?></strong>


