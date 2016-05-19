<?php
/**
 * This is the template that displays the posts for a blog
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display the archive directory, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=posts
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
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

if( $cat > 0 )
{
	$ChapterCache = & get_ChapterCache();
	$current_Chapter = & $ChapterCache->get_by_ID( $cat, false, false );

	// Breadcrumbs
	$Skin->display_breadcrumbs( $cat );
}

if( !empty( $cat ) && ( $cat > 0 ) )
{ // Display sub-chapters

$ChapterCache = & get_ChapterCache();
$chapters = $ChapterCache->get_chapters( $Blog->ID, $cat, true );

if( count( $chapters ) > 0 )
{
?>
	<table class="forums_table" cellspacing="0" cellpadding="0">
<?php
	foreach( $chapters as $Chapter )
	{	// Loop through categories:
		if( $Chapter->meta )
		{	// Meta category
			$chapters_children = $Chapter->get_children( true );
?>
		<tr class="meta_category">
			<th colspan="5"><a href="<?php echo $Chapter->get_permanent_url(); ?>" class="forumlink"><?php echo $Chapter->dget( 'name' ); ?></a></th>
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
				$legend_icons['forum_locked'] = 1;
			}
			else
			{	// Set icon for unlocked chapter
				$chapter_icon = 'catBig';
				global $disp_detail;
				if( $disp_detail == 'posts-subcat' )
				{
					$chapter_icon_title = T_('Sub-forum (contains several topics)');
					$legend_icons['forum_sub'] = 1;
				}
				else
				{
					$chapter_icon_title = T_('Forum (contains several topics)');
					$legend_icons['forum_default'] = 1;
				}
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
					{	// Display subforum
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

}

// ---------------------------------- START OF POSTS ------------------------------------
if( isset( $MainList ) && ( empty( $cat ) ||
   ( isset( $current_Chapter ) && ! $current_Chapter->meta ) /* Note: the meta categories cannot contain the posts */ ) )
{
	echo !empty( $chapters ) ? '<br />' : '';
?>
<table class="forums_table topics_table highlight" cellspacing="0" cellpadding="0">
<?php
	if( ! empty( $cat ) )
	{ // Category title
?><tr class="table_title">
		<th colspan="5"><?php
		$ChapterCache = & get_ChapterCache();
		if( $category = & $ChapterCache->get_by_ID( $cat ) )
		{ // Display category title
			echo '<h2>'.$category->get( 'name' ).'</h2>';
		}
		?></th>
	</tr>
<?php } ?>
	<tr class="panel">
		<td colspan="5"><?php
		// Buttons to post/reply
		$Skin->display_post_button( $cat );

		// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
		mainlist_page_links( array(
				'block_start' => '<div class="navigation">',
				'block_end'   => '</div>',
				'prev_text'   => T_('Previous'),
				'next_text'   => T_('Next'),
				'prev_class'  => 'prev',
				'next_class'  => 'next',
			) );
		// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------
		?></td>
	</tr>
<?php

if( ! empty( $cat ) )
{ // Go to grab the featured posts only on pages with defined category:
	while( $Item = get_featured_Item() )
	{ // We have a intro post to display:
		// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
		skin_include( '_item_list.inc.php', array(
				'feature_block' => true,
				'content_mode'  => 'auto',		// 'auto' will auto select depending on $disp-detail
				'intro_mode'    => 'normal',	// Intro posts will be displayed in normal mode
				'item_class'    => 'featured_post',
				'image_size'    => 'fit-400x320',
			) );
		// ----------------------------END ITEM BLOCK  ----------------------------
	}
}

if( $MainList->result_num_rows > 0 )
{
	while( mainlist_get_item() )
	{ // For each blog post, do everything below up to the closing curly brace "}"

		// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
		skin_include( '_item_list.inc.php', array(
				'content_mode' => 'auto',		// 'auto' will auto select depending on $disp-detail
				'image_size'   => 'fit-400x320',
			) );
		// ----------------------------END ITEM BLOCK  ----------------------------
	}
}
elseif( isset( $current_Chapter ) )
{ // Display a message about no posts in this category
?>
<tr>
	<td colspan="5"><?php echo T_('There is no topic in this forum yet.'); ?></td>
</tr>
<?php
}
?>
	<tr class="panel bottom">
		<td colspan="5"><?php
		// Buttons to post/reply
		$Skin->display_post_button( $cat );

		// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
		mainlist_page_links( array(
				'block_start' => '<div class="navigation">',
				'block_end'   => '</div>',
				'prev_text'   => T_('Previous'),
				'next_text'   => T_('Next'),
				'prev_class'  => 'prev',
				'next_class'  => 'next',
			) );
		// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------
		?></td>
	</tr>
</table>
<?php
} // ---------------------------------- END OF POSTS ------------------------------------
?>