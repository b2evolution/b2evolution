<?php // ---------------------------------- START OF BLOG LIST ----------------------------------
$sep = '';
for( $curr_blog_ID=blog_list_start('stub'); 
			$curr_blog_ID!=false; 
			 $curr_blog_ID=blog_list_next('stub') ) 
	{ 
	echo $sep;
	if( $curr_blog_ID == $blog ) 
	{ // This is the blog being displayed on this page ?>
	<strong>[<a href="<?php echo $pagenow ?>?blog=<?php echo $curr_blog_ID ?>&action=<?php echo $action; ?>"><?php blog_list_iteminfo('shortname') ?></a>]</strong>
	<?php 
	} 
	else 
	{ // This is another blog ?>
	<a href="<?php echo $pagenow ?>?blog=<?php echo $curr_blog_ID ?>&action=<?php echo $action; ?>"><?php blog_list_iteminfo('shortname') ?></a>
	[<a href="stop" onClick="return edit_reload(this.ownerDocument.forms.namedItem('post'), <?php echo  $curr_blog_ID ?>)" title="EXPERIMENTAL">R</a>]
	<?php 
	} 
	$sep = ' | ';
} // --------------------------------- END OF BLOG LIST --------------------------------- 
?>