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
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage pureforums
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $number_of_posts_in_cat, $cat, $legend_icons;

if( ! is_array( $legend_icons ) )
{ // Init this array only first time
	$legend_icons = array();
}

// Breadcrumbs
$Skin->display_breadcrumbs( $cat );

$ChapterCache = & get_ChapterCache();
$chapters = $ChapterCache->get_chapters( $Blog->ID, $cat, true );

if( count( $chapters ) > 0 )
{
?>
	<table class="forums_table highlight" cellspacing="0" cellpadding="0">
<?php
	foreach( $chapters as $root_Chapter )
	{ // Loop through categories:
		if( $root_Chapter->meta )
		{ // Meta category
			$chapters_children = $root_Chapter->get_children( true );
?>
		<tr class="meta_category">
			<th colspan="5"><a href="<?php echo $root_Chapter->get_permanent_url(); ?>" class="forumlink"><?php echo $root_Chapter->dget( 'name' ); ?></a></th>
		</tr>
<?php
		}
		else
		{	// Simple category with posts
			$chapters_children = array( $root_Chapter );
		}

		foreach( $chapters_children as $Chapter )
		{	// Loop through categories:
			if( $Chapter->lock )
			{	// Set icon for locked chapter
				$chapter_icon = 'catBigLocked';
				$chapter_icon_title = T_('This forum is locked: you cannot post, reply to, or edit topics.');
				$legend_icons['forum_locked'] = 1;
			}
			else
			{	// Set icon for unlocked chapter
				$chapter_icon = 'catBig';
				$chapter_icon_title = T_('No new posts');
				$legend_icons['forum_default'] = 1;
			}
?>
		<tr>
			<td class="ft_status"><span class="ficon <?php echo $chapter_icon; ?>" title="<?php echo $chapter_icon_title; ?>"></span></td>
			<td class="ft_title">
				<a href="<?php echo $Chapter->get_permanent_url(); ?>" class="forumlink"><?php echo $Chapter->dget( 'name' ); ?></a>
				<?php
				if( $Chapter->dget( 'description' ) != '' )
				{
					echo '<br /><span class="ft_desc">'.$Chapter->dget( 'description' ).'</span>';
				}

				$sorted_sub_chapters = $Chapter->get_children( true );
				if( count( $sorted_sub_chapters ) > 0 )
				{ // Subforums exist
					echo '<div class="subcats">';
					echo T_('Subforums').': ';
					$cc = 0;
					foreach( $sorted_sub_chapters as $child_Chapter )
					{ // Display subforum
						echo '<a href="'.$child_Chapter->get_permanent_url().'" class="forumlink">'.$child_Chapter->get('name').'</a>';
						echo $cc < count( $sorted_sub_chapters ) - 1 ? ', ' : '';
						$cc++;
					}
					echo '</div>';
				}
				?>
			</td>
			<td class="ft_count"><?php printf( T_('%s topics'), '<b>'.get_postcount_in_category( $Chapter->ID ).'</b>' ); ?></td>
			<td class="ft_count"><?php printf( T_('%s replies'), '<b>'.get_commentcount_in_category( $Chapter->ID ).'</b>' ); ?></td>
			<td class="ft_date"><?php echo $Chapter->get_last_touched_date( 'D M j, Y H:i' ); ?></td>
		</tr>
<?php
		}
	}	// End of categories loop.
?>
	</table>
<?php
}
?>