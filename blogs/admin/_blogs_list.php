<?php
/**
 * Displays list of blogs for editing
 *
 * b2evolution - {@link http://b2evolution.net/}
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
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
		<th><?php echo T_('Locale') ?></th>
	</tr>
	<?php
	for( $curr_blog_ID=blog_list_start(); $curr_blog_ID!=false; $curr_blog_ID=blog_list_next() )
	{
		if( ! $current_User->check_perm( 'blog_properties', 'any', false, $curr_blog_ID ) )
		{	// Current user is not allowed to edit cats...
			continue;
		}
		?>
		<tr>
			<td><strong>
				<?php
					blog_list_iteminfo('ID');
					echo '&nbsp;';
					if( $current_User->check_perm( 'blog_properties', 'edit', false, $curr_blog_ID ) )
					{
						?>
					<a href="b2blogs.php?action=edit&blog=<?php blog_list_iteminfo('ID') ?>"><img src="img/properties.png" width="18" height="13" class="middle" alt="<?php echo  T_('Properties') ?>" />&nbsp;<?php blog_list_iteminfo('shortname'); ?></a>
						<?php
					}
					else
					{
						blog_list_iteminfo('shortname');
					} ?>
					</strong></td>
			<td><?php blog_list_iteminfo('name') ?></td>
			<td><a href="<?php blog_list_iteminfo('dynurl') ?>"><?php blog_list_iteminfo('filename') ?></a></td>
			<td><a href="<?php blog_list_iteminfo('blogurl') ?>"><?php blog_list_iteminfo('stub') ?></a></td>
			<td>
				<?php if( $staticfilename=blog_list_iteminfo('staticfilename',false) )
				{
				?>
				<a href="<?php blog_list_iteminfo('staticurl') ?>"><?php echo $staticfilename ?></a>
				<?php if( $current_User->check_perm( 'blog_genstatic', 'any', false, $curr_blog_ID ) )
					{ // It is possible to generate a static page ?>
						[<a href="b2blogs.php?action=GenStatic&blog=<?php blog_list_iteminfo('ID') ?>"><?php
							/* TRANS: abbrev. for "generate !" */ echo T_('Gen!') ?></a>]
						<?php
					}
				}
				?>
			</td>
			<td><?php blog_list_iteminfo('locale') ?></td>
		</tr>
		<?php
	}
	?>
</table>
<?php if( $current_User->check_perm( 'blogs', 'create' ) )
{ ?>
<p class="center"><a href="b2blogs.php?action=new"><img src="img/new.png" width="13" height="12" class="middle" alt="" /> <?php echo T_('Create new blog !') ?></a></p>
<?php } ?>
</div>
