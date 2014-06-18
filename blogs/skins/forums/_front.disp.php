<?php
/**
 * This is the template that displays the links to the latest comments for a blog
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display a feedback, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=comments
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $number_of_posts_in_cat, $cat;

$chapters = $Skin->get_chapters( $cat );

if( count( $chapters ) > 0 )
{
?>
	<table class="bForums" width="100%" cellspacing="1" cellpadding="2" border="0">
		<tr>
			<th colspan="2"><?php echo isset( $category_name ) ? $category_name : T_('Forum'); ?></th>
			<th width="70"><?php echo T_('Topics'); ?></th>
			<th width="70"><?php echo T_('Replies'); ?></th>
		</tr>
<?php
	foreach( $chapters as $Chapter )
	{	// Loop through categories:
		if( $Chapter->meta )
		{	// Meta category
			$chapters_children = $Chapter->children;
?>
		<tr class="meta_category">
			<th colspan="2"><a href="<?php echo $Chapter->get_permanent_url(); ?>" class="forumlink"><?php echo $Chapter->dget( 'name' ); ?></a></th>
			<td colspan="2">&nbsp;</td>
		</tr>
<?php
		}
		else
		{	// Simple category with posts
			$chapters_children = array( $Chapter );
		}

		foreach( $chapters_children as $Chapter )
		{	// Loop through categories:
			if( $Chapter->lock )
			{	// Set icon for locked chapter
				$chapter_icon = 'catBigLocked';
				$chapter_icon_title = T_('This forum is locked: you cannot post, reply to, or edit topics.');
			}
			else
			{	// Set icon for unlocked chapter
				$chapter_icon = 'catBig';
				$chapter_icon_title = T_('No new posts');
			}
?>
		<tr>
			<td class="status"><span class="ficon <?php echo $chapter_icon; ?>" title="<?php echo $chapter_icon_title; ?>"></span></td>
			<td class="left">
				<a href="<?php echo $Chapter->get_permanent_url(); ?>" class="forumlink"><?php echo $Chapter->dget( 'name' ); ?></a>
				<?php
				if( $Chapter->dget( 'description' ) != '' )
				{
					echo '<br />'.$Chapter->dget( 'description' );
				}
				if( count( $Chapter->children ) > 0 )
				{	// Subforums are exist
					echo '<div class="subcats">';
					echo T_('Subforums').': ';
					$cc = 0;
					foreach( $Chapter->children as $child_Chapter )
					{ // Display subforum
						echo '<a href="'.$child_Chapter->get_permanent_url().'" class="forumlink">'.$child_Chapter->get('name').'</a>';
						echo $cc < count( $Chapter->children ) - 1 ? ', ' : '';
						$cc++;
					}
					echo '</div>';
				}
				?>
			</td>
			<td class="row2"><?php echo get_postcount_in_category( $Chapter->ID ); ?></td>
			<td class="row2"><?php echo get_commentcount_in_category( $Chapter->ID ); ?></td>
		</tr>
<?php
		}
	}	// End of categories loop.
?>
	</table>
<?php
}
?>