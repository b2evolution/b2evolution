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
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

$count = 0;
for( $curr_blog_ID = blog_list_start(); $curr_blog_ID != false; $curr_blog_ID = blog_list_next() )
{
	if( ! $current_User->check_perm( 'blog_properties', 'any', false, $curr_blog_ID ) )
	{ // Current user is not allowed to edit properties...
		continue;
	}
	if( !isset( $atleastoneshown ) )
	{ // Display headers the first time we find an viewable blog
		// TODO 0.9.1 : prewalk the list, this will also allow to know if at least one blog can be deleted
		$atleastoneshown = true;
		?>
		<div class="panelblock">
		<h2><?php echo T_('Blogs') ?>:</h2>
		<table class="grouped" cellspacing="0">
		<tr>
			<th class="firstcol"><?php echo T_('Blog') ?></th>
			<th><?php echo T_('Full Name') ?></th>
			<th><?php echo T_('Blog URL') ?></th>
			<th><?php echo T_('Static File') ?></th>
			<th><?php echo T_('Locale') ?></th>
			<th><?php echo /* TRANS: Abbrev. for Delete */ T_('Del') ?></th>
		</tr>
		<?php
	}
	?>
	<tr <?php if( $count % 2 == 1 ) echo 'class="odd"' ?>>
		<td class="firstcol"><strong>
			<?php
			blog_list_iteminfo('ID');
			echo '&nbsp;';
			if( $current_User->check_perm( 'blog_properties', 'edit', false, $curr_blog_ID ) )
			{
				?>
				<a href="blogs.php?action=edit&amp;blog=<?php blog_list_iteminfo('ID') ?>"><img src="img/properties.png" width="18" height="13" class="middle" alt="<?php echo  T_('Properties') ?>" />&nbsp;<?php blog_list_iteminfo('shortname'); ?></a>
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
				echo ' <span title="'.T_('Default blog on index.php').'">('.T_('Default').')</span>';
			}
			?>
		</td>

		<td><?php blog_list_iteminfo('name') ?></td>

		<td><a href="<?php blog_list_iteminfo('blogurl') ?>"><?php blog_list_iteminfo('suburl') ?></a></td>

		<td>
			<?php if( ($staticfilename = blog_list_iteminfo('staticfilename',false)) )
			{ // It is possible to generate a static page
			?>
			<a href="<?php blog_list_iteminfo('staticurl') ?>"><?php echo $staticfilename ?></a>
			<?php if( $current_User->check_perm( 'blog_genstatic', 'any', false, $curr_blog_ID ) )
				{ // Permission to generate a static page ?>
					[<a href="blogs.php?action=GenStatic&amp;blog=<?php blog_list_iteminfo('ID') ?>"><?php
						/* TRANS: abbrev. for "generate !" */ echo T_('Gen!') ?></a>]
					<?php
				}
			}
			?>
		</td>

		<td class="center"><?php locale_flag( blog_list_iteminfo('locale', false) ) ?></td>

		<?php if( ($curr_blog_ID == 1) || (!$current_User->check_perm( 'blog_properties', 'edit', false, $curr_blog_ID )) )
		{ // display empty cell for blog #1 and non deletable blogs
			echo '<td></td>';
		}
		elseif( $current_User->check_perm( 'blog_properties', 'edit', false, $curr_blog_ID ) )
		{ ?>
		<td class="center">
			<a href="blogs.php?action=delete&amp;blog=<?php blog_list_iteminfo('ID') ?>" style="color:red;font-weight:bold;" onclick="return confirm('<?php printf( /* TRANS: Warning this is a javascript string */ T_('Are you sure you want to delete blog #%d ?\\n\\nWARNING: This will delete ALL POST, COMMENTS,\\nCATEGORIES and other data related to that Blog!\\n\\nThis CANNOT be undone!'), $curr_blog_ID) ?>')"><img src="img/xross.gif" width="13" height="13" class="middle" alt="<?php echo /* TRANS: Abbrev. for Delete */ T_('Del') ?>" title="<?php echo T_('Delete this blog!') ?>" /></a>
			<!--<a href="blogs.php?action=copy&amp;blog=<?php blog_list_iteminfo('ID') ?>" style="color:red;font-weight:bold;" title="<?php echo T_('Copy this blog!') ?>" ><img src="img/copy.gif" width="13" height="13" class="middle" alt="<?php echo /* TRANS: Abbrev. for Copy */ T_('Copy') ?>" /></a>-->
		</td>
		<?php
		} ?>
	</tr>
	<?php
	$count++;
}

if( !isset( $atleastoneshown ) )
{ // no blog was listed because user has no rights
	echo '<div class="panelinfo">';
	echo '<P>'.T_('Sorry, you have no permission to edit/view any blog\'s properties.' ).'</p>';
}
else
{ // close table
	?>
	</table>
	<?php
}

if( $current_User->check_perm( 'blogs', 'create' ) )
{ ?>
	<p class="center"><a href="blogs.php?action=new"><img src="img/new.gif" width="13" height="13" class="middle" alt="" /> <?php echo T_('New blog...') ?></a></p>
	<?php
} ?>
</div>
