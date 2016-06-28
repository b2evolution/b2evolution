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
 * @subpackage bootstrap_forums
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $number_of_posts_in_cat, $cat, $legend_icons;

if( ! is_array( $legend_icons ) )
{ // Init this array only first time
	$legend_icons = array();
}

// Get ID of single selected category:
$single_cat_ID = intval( $cat );

// Get IDs of several selected categories:
$multi_cat_IDs = get_param( 'cat_array' );

if( $single_cat_ID )
{
	$ChapterCache = & get_ChapterCache();
	$current_Chapter = & $ChapterCache->get_by_ID( $single_cat_ID, false, false );
}

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
		'suffix_text'      => empty( $single_cat_ID ) ? T_('Latest topics') : '',
	) );

if( $single_cat_ID )
{	// Display sub-chapters:

$ChapterCache = & get_ChapterCache();
$chapters = $ChapterCache->get_chapters( $Blog->ID, $single_cat_ID, true );

if( count( $chapters ) > 0 )
{
?>
	<div class="panel panel-default forums_list front_panel">
<?php
	$section_is_started = false;
	foreach( $chapters as $Chapter )
	{ // Loop through categories:
		if( $Chapter->meta )
		{ // Meta category
			$chapters_children = $Chapter->get_children( true );
			if( $section_is_started )
			{ // Close previous opened table
?>
<?php
				$section_is_started = false;
			}
?>
		<header class="panel-heading meta_category"><a href="<?php echo $Chapter->get_permanent_url(); ?>" class="forumlink"><?php echo $Chapter->dget( 'name' ); ?></a></header>
<?php
		}
		else
		{ // Simple category with posts
			$chapters_children = array( $Chapter );
		}

		if( ! $section_is_started )
		{
			$section_is_started = true;
?>
		<section class="table table-hover">
<?php
		}

		foreach( $chapters_children as $Chapter )
		{ // Loop through categories:
			if( $Chapter->lock )
			{ // Set icon for locked chapter
				$chapter_icon = 'fa-lock big';
				$chapter_icon_title = T_('This forum is locked: you cannot post, reply to, or edit topics.');
				$legend_icons['forum_locked'] = 1;
			}
			else
			{ // Set icon for unlocked chapter
				$chapter_icon = 'fa-folder big';
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
		<article class="container group_row">
			<div class="ft_status__ft_title col-lg-8 col-md-7 col-sm-7 col-xs-6">
				<div class="ft_status"><i class="icon fa <?php echo $chapter_icon; ?>" title="<?php echo $chapter_icon_title; ?>"></i></div>
				<div class="ft_title ellipsis">
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
				</div>
			</div>
			<div class="ft_count col-lg-1 col-md-1 col-sm-1 col-xs-2"><?php printf( T_('%s topics'), '<div><a href="'. $Chapter->get_permanent_url() .'">'.get_postcount_in_category( $Chapter->ID ).'</a></div>' ); ?></div>
			<div class="ft_count second_of_class col-lg-1 col-md-1 col-sm-1 col-xs-2"><?php printf( T_('%s replies'), '<div><a href="'. $Chapter->get_permanent_url() .'">'.get_postcount_in_category( $Chapter->ID ).'</a></div>' ); ?></div>
			<div class="ft_date col-lg-2 col-md-3 col-sm-3"><?php echo $Chapter->get_last_touched_date( 'D M j, Y H:i' ); ?></div>
			<!-- Apply this on XS size -->
			<div class="ft_date_shrinked col-xs-2"><?php echo $Chapter->get_last_touched_date( 'm/j/y' ); ?></div>
		</article>
<?php
		}
	} // End of categories loop.
	if( $section_is_started )
	{
?>
		</section>
<?php
	}
?>
	</div>
<?php
}

}

// ---------------------------------- START OF POSTS ------------------------------------
if( isset( $MainList ) && ( empty( $single_cat_ID ) || ! empty( $multi_cat_IDs ) ||
   ( isset( $current_Chapter ) && ! $current_Chapter->meta ) /* Note: the meta categories cannot contain the posts */ ) )
{
	echo !empty( $chapters ) ? '<br />' : '';
?>
<div class="panel panel-default forums_list">
	<?php
	if( $single_cat_ID )
	{	// Display category title:
		$ChapterCache = & get_ChapterCache();
		if( $category = & $ChapterCache->get_by_ID( $single_cat_ID ) )
		{ // Display category title
			echo '<div class="panel-heading">'
					// Buttons to post/reply:
					.$Skin->get_post_button( $single_cat_ID, NULL, array(
							'group_class'  => 'pull-right',
							'button_class' => 'btn-sm',
						) )
					// Category title:
					.'<h3 class="panel-title">'.$category->get( 'name' ).'</h3>'
				.'</div>';
		}
	}
	?>

	<section class="table table-hover">
<?php

if( $single_cat_ID )
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
				'image_size'   => 'fit-1280x720',
			) );
		// ----------------------------END ITEM BLOCK  ----------------------------
	}
}
elseif( isset( $current_Chapter ) )
{ // Display a message about no posts in this category
?>
<div class="ft_no_post">
	<?php echo T_('There is no topic in this forum yet.'); ?>
</div>
<?php
}
?>
	</section>

	<div class="panel-body comments_link__pagination">
	<?php
		// Buttons to post/reply
		$Skin->display_post_button( $single_cat_ID );
		if( check_user_status( 'can_be_validated' ) )
		{	// Display a warning if current user cannot post a topic because he must activate account:
			global $Messages;
			$Messages->clear();
			$Messages->add( T_( 'You must activate your account before you can post a new topic.' )
				.' <a href="'.get_activate_info_url( NULL, '&amp;' ).'">'.T_( 'More info &raquo;' ).'</a>', 'warning' );
			$Messages->display();
		}

		// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
		mainlist_page_links( array(
				'block_start'           => '<ul class="pagination">',
				'block_end'             => '</ul>',
				'page_current_template' => '<span>$page_num$</span>',
				'page_item_before'      => '<li>',
				'page_item_after'       => '</li>',
				'page_item_current_before' => '<li class="active">',
				'page_item_current_after'  => '</li>',
				'prev_text'             => '<i class="fa fa-angle-double-left"></i>',
				'next_text'             => '<i class="fa fa-angle-double-right"></i>',
			) );
		// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------
	?>
	</div>
</div>
<?php
} // ---------------------------------- END OF POSTS ------------------------------------
?>