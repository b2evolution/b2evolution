<?php 
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 */
?>
<div class="panelblock">
<h2><?php echo T_('Blogs') ?>:</h2>
<table class="thin">
	<tr>
		<th><?php echo T_('Blog') ?></th>
		<th><?php echo T_('Full Name') ?></th>
		<th><?php echo T_('Stub Filename') ?></th>
		<th><?php echo T_('Stub URLname') ?></th>
		<th><?php echo T_('Static Filename') ?></th>
		<th><?php echo T_('Lang') ?></th>
	</tr>
	<?php 
	for( $curr_blog_ID=blog_list_start(); $curr_blog_ID!=false; $curr_blog_ID=blog_list_next() )
	{
		?>
		<tr>
			<td><strong><?php blog_list_iteminfo('ID') ?>&nbsp;[<a href="b2blogs.php?action=edit&blog=<?php blog_list_iteminfo('ID') ?>"><?php echo T_('Edit') ?></a>]&nbsp;:&nbsp;<?php blog_list_iteminfo('shortname') ?></strong></td>
			<td><?php blog_list_iteminfo('name') ?></td>
			<td><a href="<?php blog_list_iteminfo('dynurl') ?>"><?php blog_list_iteminfo('filename') ?></a></td>
			<td><a href="<?php blog_list_iteminfo('blogurl') ?>"><?php blog_list_iteminfo('stub') ?></a></td>
			<td>
				<?php if( $staticfilename=blog_list_iteminfo('staticfilename',false) ) 
				{ 
				?>
				<a href="<?php blog_list_iteminfo('staticurl') ?>"><?php echo $staticfilename ?></a>&nbsp;[<a href="b2blogs.php?action=GenStatic&blog=<?php blog_list_iteminfo('ID') ?>"><?php /* TRANS: abbrev. for "generate !" */ echo T_('Gen!') ?></a>]
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
<p>[<a href="b2blogs.php?action=new"><?php echo T_('Create new blog !') ?></a>]</p>
</div>
