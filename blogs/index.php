<?php 
	require(dirname(__FILE__)."/b2evocore/_main.php");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>"><head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
<title><?php echo T_('my b2evolution') ?>: <?php echo T_('my blogs') ?>...</title>
<link href="install/b2evo.css" rel="stylesheet" type="text/css" />
 
</head>
<body>
<div id="rowheader" >
<h1><a href="http://b2evolution.net/" title="b2evolution: Home"><img src="img/b2evolution_logo.png" alt="b2evolution" width="472" height="102" border="0" /></a></h1>
<div id="tagline"><?php echo T_('A blog tool like it oughta be!') ?></div>
<div id="quicklinks"><?php echo T_('My blogs') ?>:
<?php // --------------------------- BLOG LIST INCLUDED HERE -----------------------------
	$display_blog_list = 1;			// Force display even if disabled in conf
	# this is what will start and end your blog links
	$blog_list_start = '';				
	$blog_list_end = '';				
	# this is what will separate your blog links
	$blog_item_start = '';				
	$blog_item_end = ' &middot; ';
	// Include the bloglist
	require( dirname(__FILE__)."/_bloglist.php"); 
	// ---------------------------------- END OF BLOG LIST --------------------------------- ?>
	<a href="admin/b2login.php"><?php echo T_('My Back-Office') ?></a>
</div>
</div>
<p><?php echo T_('This page is a placeholder providing links to your blogs and additionnal demos of what you can do with b2evolution.') ?></p>
<p><?php echo T_('Of course, you don\'t want to keep all that for your website. Just select what you need and erase what you don\'t. You will probably also delete this page and rename one of your blogs to index.php. (When you do that, don\'t forget to update that blog settings in the back office, so the moved blog knows its new filename/URL! ;).') ?></p>
<h2><?php echo T_('My blogs') ?>:</h2>
<ul>
<?php // --------------------------- BLOG LIST -----------------------------
	for( $curr_blog_ID=blog_list_start('stub'); 
				$curr_blog_ID!=false; 
				 $curr_blog_ID=blog_list_next('stub') ) 
	{ # by uncommenting the following lines you can hide some blogs
		if( $curr_blog_ID == 1 ) continue; // Hide blog 1...
		// if( $curr_blog_ID == 2 ) continue; // Hide blog 2...
		echo '<li><strong>';
		printf( T_('Blog #%d'), $curr_blog_ID );
		echo ': <a href="';
		blog_list_iteminfo('blogurl', 'raw');
		echo '" title="';
		blog_list_iteminfo( 'shortdesc', 'htmlheader');
		echo '">';
		blog_list_iteminfo( 'name', 'htmlbody');
		echo '</a></strong> &nbsp; (';
		blog_list_iteminfo( 'stub', 'raw');
		echo ')';
		echo '</li>';
	}
	// ---------------------------------- END OF BLOG LIST --------------------------------- ?>
</ul>
<?php 
	// Select Blog #1: 
	$blog = 1;
	if( get_bloginfo( 'stub' ) != '' )
	{	// Only display if the stub is set:
?>
<ul>
<li><strong><?php echo T_('Blog #1') ?>: <a href="<?php bloginfo( 'blogurl' ); ?>"><?php echo T_('This is a special blog that aggregates all messages from all other blogs!') ?></a></strong> &nbsp; (<?php bloginfo( 'stub' ); ?>)</li>
</ul>
<?php 
	}
?>
<p><?php echo T_('Please note: the above list (as well as the menu) is automatically generated and includes only the blogs that have a &quot;stub url name&quot;. You can set this in the blog configuration in the back-office.') ?></p>
<h2><?php echo T_('More demos') ?>:</h2>
<ul>
  <li><strong><?php echo T_('Custom template') ?>: <a href="multiblogs.php"><?php echo T_('Multiple blogs displayed on the same page') ?></a></strong> &nbsp; (multiblogs.php)</li>
</ul>
<p><?php echo T_('Please note: those demos do not make use of evoSkins, even if you enabled them during install. The only way to change their look and feel is to edit their PHP template. But once, again, rememner these are just demos destined to inspire you for your own templates ;)') ?></p>
<div id="rowfooter">
<a href="http://b2evolution.net/"><?php echo T_('official website') ?></a> &middot; <a href="http://b2evolution.net/about/license.html"><?php echo T_('GNU GPL license') ?></a>
</div>

</body>
</html>
