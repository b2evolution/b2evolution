<div class="panelblock">
<h2>Blogs:</h2>
<table class="thin">
	<tr>
		<th>Blog</th>
		<th>Full Name</th>
		<th>Stub Filename</th>
		<th>Stub URLname</th>
		<th>Static Filename</th>
		<th>Lang</th>
	</tr>
	<?php 
	for( $curr_blog_ID=blog_list_start(); $curr_blog_ID!=false; $curr_blog_ID=blog_list_next() )
	{
		?>
		<tr>
			<td><strong><?php blog_list_iteminfo('ID') ?>&nbsp;[<a href="b2blogs.php?action=edit&blog=<?php blog_list_iteminfo('ID') ?>">Edit</a>]&nbsp;:&nbsp;<?php blog_list_iteminfo('shortname') ?></strong></td>
			<td><?php blog_list_iteminfo('name') ?></td>
			<td><a href="<?php blog_list_iteminfo('dynurl') ?>"><?php blog_list_iteminfo('filename') ?></a></td>
			<td><a href="<?php blog_list_iteminfo('blogurl') ?>"><?php blog_list_iteminfo('stub') ?></a></td>
			<td>
				<?php if( $staticfilename=blog_list_iteminfo('staticfilename',false) ) 
				{ 
				?>
				<a href="<?php blog_list_iteminfo('staticurl') ?>"><?php echo $staticfilename ?></a>&nbsp;[<a href="b2blogs.php?action=GenStatic&blog=<?php blog_list_iteminfo('ID') ?>">Gen!</a>]
				<?php 
				}
				?>
			</td>
			<td><?php blog_list_iteminfo('lang') ?></td>
		</tr>
		<?php
	}
	?>
</table>
<p>[<a href="b2blogs.php?action=new">Create new blog</a>]</p>
</div>
