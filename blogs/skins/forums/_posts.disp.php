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
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// --------------------------------- START OF POSTS -------------------------------------
global $cat, $MainList;

if( $cat > 0 )
{ // One single category is set
	// Breadcrumbs
	$Skin->display_breadcrumbs( $cat );

?>
<div class="post_panel">
<?php
	// Buttons to post/reply
	$Skin->display_post_button( $cat );

	// Page title
	$ChapterCache = & get_ChapterCache();
	if( $category = & $ChapterCache->get_by_ID( $cat ) )
	{	// Display category title
		$category_name = $category->get( 'name' ); // $category_name is also used below
		echo '<h2 class="page_title">'.$category_name.'</h2>';
	}
?>
</div>
<div class="clear"></div>
<?php
}

// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
mainlist_page_links( array(
		'block_start' => '<div class="navigation navigation_top">'.T_('Page').': ',
		'block_end' => '</div><div class="clear"></div>',
		'prev_text' => T_('Previous'),
		'next_text' => T_('Next'),
	) );
// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------

if( !empty( $cat ) && ( $cat > 0 ) )
{	// Display sub-chapters

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

}

// ---------------------------------- START OF POSTS ------------------------------------
if( isset( $MainList ) && $MainList->result_num_rows > 0 )
{
	echo !empty( $chapters ) ? '<br />' : '';
?>
<table class="bForums bPosts" width="100%" cellspacing="1" cellpadding="2" border="0">
	<tr>
		<th colspan="2"><?php echo T_('Topics'); ?></th>
		<th width="70"><?php echo T_('Replies'); ?></th>
		<th width="100"><?php echo T_('Author'); ?></th>
		<th width="160"><?php echo T_('Last Post'); ?></th>
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

while( mainlist_get_item() )
{	// For each blog post, do everything below up to the closing curly brace "}"

	// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
	skin_include( '_item_list.inc.php', array(
			'content_mode' => 'auto',		// 'auto' will auto select depending on $disp-detail
			'image_size'   => 'fit-400x320',
		) );
	// ----------------------------END ITEM BLOCK  ----------------------------
}
?>
</table>
<?php
} // ---------------------------------- END OF POSTS ------------------------------------


// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
mainlist_page_links( array(
		'block_start' => '<div class="navigation font11">'.T_('Page').': ',
		'block_end' => '</div>',
		'prev_text' => T_('Previous'),
		'next_text' => T_('Next'),
	) );
// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------

?>