<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * Display posts for editing
 */
require_once (dirname(__FILE__).'/_header.php');

param( 'blog', 'integer', $default_to_blog, true );
if( $blog == 0 ) $blog = $default_to_blog;
get_blogparams();

$title = T_('Browse blog:');
require (dirname(__FILE__).'/_menutop.php');
echo '<span class="menutopbloglist">';

// ---------------------------------- START OF BLOG LIST ----------------------------------
$sep = '';
for( $curr_blog_ID=blog_list_start('stub'); 
			$curr_blog_ID!=false; 
			 $curr_blog_ID=blog_list_next('stub') ) 
	{ 
	echo $sep;
	if( $curr_blog_ID == $blog ) 
	{ // This is the blog being displayed on this page ?>
	<strong>[<a href="<?php echo $pagenow ?>?blog=<?php echo $curr_blog_ID ?>"><?php blog_list_iteminfo('shortname') ?></a>]</strong>
	<?php 
	} 
	else 
	{ // This is another blog ?>
	<a href="<?php echo $pagenow ?>?blog=<?php echo $curr_blog_ID ?>"><?php blog_list_iteminfo('shortname') ?></a>
	<?php 
	} 
	$sep = ' | ';
} // --------------------------------- END OF BLOG LIST --------------------------------- 

echo '</span>';
require (dirname(__FILE__).'/_menutop_end.php');


if ($user_level > 0) 
{	
	require dirname(__FILE__).'/_edit_showposts.php';
} 
else
{ 
?>
	<div class="panelblock">
	<?php printf( T_('Since you\'re a newcomer, you\'ll have to wait for an admin to raise your level to 1, in order to be authorized to post.	You can also <a %s>e-mail the admin</a> to ask for a promotion. When you\'re promoted, just reload this page and you\'ll be able to blog. :)'), 'href="mailto:'.admin_email.'?subject=b2-promotion' ); ?>
	</div>
<?php
}

require( dirname(__FILE__).'/_footer.php' ); 

?>