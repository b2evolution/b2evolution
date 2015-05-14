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
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage bootstrap_forums
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $number_of_posts_in_cat, $cat;

if( $cat > 0 )
{
	$ChapterCache = & get_ChapterCache();
	$current_Chapter = & $ChapterCache->get_by_ID( $cat, false, false );

	// Init MainList
	$page = param( 'paged', 'integer', 1 );
	$MainList = new ItemList2( $Blog, $Blog->get_timestamp_min(), $Blog->get_timestamp_max(), $Blog->get_setting('posts_per_page') );
	$MainList->load_from_Request();
	$MainList->set_filters( array(
			'cat_array' => array( $cat ), // Limit only by selected cat (exclude posts from child categories)
			'cat_modifier' => NULL,
			'page' => $page
		) );
	$MainList->query();
	$MainList->nav_target = $cat; // set navigation target, we are always navigating through category in this skin

	// Load read statuses if required
	$MainList->load_content_read_statuses();

	// Breadcrumbs
	skin_widget( array(
			// CODE for the widget:
			'widget' => 'breadcrumb_path',
			// Optional display params
			'block_start'      => '<ol class="breadcrumb">',
			'block_end'        => '</ol><div class="clear"></div>',
			'separator'        => '',
			'item_mask'        => '<li><a href="$url$">$title$</a></li>',
			'item_active_mask' => '<li class="active">$title$</li>',
		) );
}

if( !empty( $cat ) && ( $cat > 0 ) )
{ // Display sub-chapters

$chapters = $Skin->get_chapters( $cat );

if( count( $chapters ) > 0 )
{
?>
	<div class="panel panel-default forums_list">
<?php
	$table_is_started = false;
	foreach( $chapters as $Chapter )
	{ // Loop through categories:
		if( $Chapter->meta )
		{ // Meta category
			$chapters_children = $Chapter->children;
			if( $table_is_started )
			{ // Close previous opened table
?>
			</tbody>
		</table>
<?php
				$table_is_started = false;
			}
?>
		<div class="panel-heading meta_category"><a href="<?php echo $Chapter->get_permanent_url(); ?>" class="forumlink"><?php echo $Chapter->dget( 'name' ); ?></a></div>
<?php
		}
		else
		{ // Simple category with posts
			$chapters_children = array( $Chapter );
		}

		if( ! $table_is_started )
		{
			$table_is_started = true;
?>
		<table class="table table-hover">
			<tbody>
<?php
		}

		foreach( $chapters_children as $Chapter )
		{ // Loop through categories:
			if( $Chapter->lock )
			{ // Set icon for locked chapter
				$chapter_icon = 'fa-lock big';
				$chapter_icon_title = T_('This forum is locked: you cannot post, reply to, or edit topics.');
			}
			else
			{ // Set icon for unlocked chapter
				$chapter_icon = 'fa-folder big';
				$chapter_icon_title = T_('No new posts');
			}
?>
		<tr>
			<td class="ft_status"><i class="icon fa <?php echo $chapter_icon; ?>" title="<?php echo $chapter_icon_title; ?>"></i></td>
			<td class="ft_title">
				<a href="<?php echo $Chapter->get_permanent_url(); ?>" class="forumlink"><?php echo $Chapter->dget( 'name' ); ?></a>
				<?php
				if( $Chapter->dget( 'description' ) != '' )
				{
					echo '<br /><span class="ft_desc">'.$Chapter->dget( 'description' ).'</span>';
				}
				if( count( $Chapter->children ) > 0 )
				{ // Subforums exist
					echo '<div class="subcats">';
					echo T_('Subforums').': ';
					$cc = 0;
					foreach( $Chapter->children as $child_Chapter )
					{	// Display subforum
						echo '<a href="'.$child_Chapter->get_permanent_url().'" class="forumlink">'.$child_Chapter->get('name').'</a>';
						echo $cc < count( $Chapter->children ) - 1 ? ', ' : '';
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
	} // End of categories loop.
	if( $table_is_started )
	{
?>
			</tbody>
		</table>
<?php
	}
?>
	</div>
<?php
}

}

// ---------------------------------- START OF POSTS ------------------------------------
if( isset( $MainList ) && ( empty( $cat ) ||
   ( isset( $current_Chapter ) && ! $current_Chapter->meta ) /* Note: the meta categories cannot contain the posts */ ) )
{
	echo !empty( $chapters ) ? '<br />' : '';
?>
<div class="panel panel-default forums_list">
	<?php
	if( ! empty( $cat ) )
	{ // Category title
		$ChapterCache = & get_ChapterCache();
		if( $category = & $ChapterCache->get_by_ID( $cat ) )
		{ // Display category title
			echo '<div class="panel-heading">'.$category->get( 'name' ).'</div>';
		}
	}
	?>
	<div class="panel-body">
	<?php
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
		?>
	</div>

	<table class="table table-hover">
		<tbody>
<?php

if( ! empty( $cat ) )
{ // Go to grab the featured posts only on pages with defined category:
	while( $Item = get_featured_Item() )
	{ // We have a intro post to display:
		// ---------------------- ITEM LIST INCLUDED HERE ------------------------
		skin_include( '_item_list.inc.php' );
		// ----------------------------END ITEM LIST  ----------------------------
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
		</tbody>
	</table>

	<div class="panel-body">
	<?php
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
	?>
	</div>

</div>
<?php
} // ---------------------------------- END OF POSTS ------------------------------------
?>