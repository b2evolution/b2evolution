<?php
/**
 * Displays list of blogs for editing
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
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
		<th><?php echo T_('Blog URL') ?></th>
		<th><?php echo T_('Static File') ?></th>
		<th><?php echo T_('Locale') ?></th>
		<?php if( $current_User->check_perm( 'blog_properties', 'edit', false ) )
		{ ?>
		<th><?php echo /* TRANS: Abbrev. for Delete */ T_('Del') ?></th>
		<?php } ?>
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
					</strong>
					<?php 
					if( $curr_blog_ID == $Settings->get('default_blog_ID') )
					{
						echo ' ('.T_('Default').')';
					}
					?>
			</td>
			
			<td><?php blog_list_iteminfo('name') ?></td>
			
			<td><a href="<?php blog_list_iteminfo('blogurl') ?>"><?php blog_list_iteminfo('suburl') ?></a></td>
			
			<td>
				<?php if( ($staticfilename=blog_list_iteminfo('staticfilename',false)) && blog_list_iteminfo('filename',false) )
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
			<td class="center"><?php blog_list_iteminfo('locale') ?></td>
			<?php if( $curr_blog_ID == 1 ) { echo '<td></td>'; }
			elseif( $current_User->check_perm( 'blog_properties', 'edit', false, $curr_blog_ID ) )
			{ ?>
			<td class="center"><a href="b2blogs.php?action=delete&blog=<?php blog_list_iteminfo('ID') ?>" style="color:red;font-weight:bold;" onClick="return confirm('<?php printf( /* TRANS: Warning this is a javascript string */ T_('Are you sure you want to delete blog #%d ?\\n\\nWARNING: This will delete ALL POST, COMMENTS,\\nCATEGORIES and other data related to that Blog!\\n\\nThis CANNOT be undone!'), $curr_blog_ID) ?>')"><img src="img/xross.gif" width="13" height="13" class="middle" alt="<?php echo /* TRANS: Abbrev. for Delete */ T_('Del') ?>" /></a></td>
			<?php } ?>
		</tr>
		<?php
	}
	?>
</table>
<?php if( $current_User->check_perm( 'blogs', 'create' ) )
{ ?>
	<p class="center"><a href="b2blogs.php?action=new"><img src="img/new.gif" width="13" height="13" class="middle" alt="" /> <?php echo T_('New blog...') ?></a></p>
<?php } ?>
</div>
