<?php // ---------------------------------- START OF BLOG LIST ----------------------------------
$sep = '';
for( $curr_blog_ID=blog_list_start('stub'); 
			$curr_blog_ID!=false; 
			 $curr_blog_ID=blog_list_next('stub') ) 
	{ 
	echo $sep;
	if( $curr_blog_ID == $blog ) { // This is the blog being displayed on this page ?>
<strong>[<a href="b2edit.php?blog=<?php echo $curr_blog_ID ?>&action=<?php echo $action; ?>"><?php blog_list_iteminfo('shortname') ?></a>]</strong>
<?php } else { // This is another blog ?>
<a href="b2edit.php?blog=<?php echo $curr_blog_ID ?>&action=<?php echo $action; ?>"><?php blog_list_iteminfo('shortname') ?></a>
<?php 
	} 
	$sep = ' | ';
} // --------------------------------- END OF BLOG LIST --------------------------------- 
?>