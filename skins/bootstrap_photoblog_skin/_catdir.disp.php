<?php
/**
 * This is the template that displays the category directory for a blog
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display the archive directory, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=catdir
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Blog, $Item;

// Default params:
$params = array_merge( array(
		'item_class'        => 'evo_post evo_content_block',
		'item_type_class'   => 'evo_post__ptyp_',
		'item_status_class' => 'evo_post__',
	), $params );

// --------------------------------- START OF POSTS -------------------------------------
// Display message if no post:
$params_no_content = array(
		'before' => '<div class="msg_nothing">',
		'after'  => '</div>',
		'msg_empty_logged_in'     => T_('Sorry, there is nothing to display...'),
		// This will display if the collection has not been made private. Otherwise we will be redirected to a login screen anyways
		'msg_empty_not_logged_in' => T_('This site has no public contents.')
	);
// Get only root categories of this blog
$ChapterCache = & get_ChapterCache();
$chapters = $ChapterCache->get_chapters( $Blog->ID, 0, true );
// Boolean var to know when at least one post is displayed
$no_content_to_display = true;
if( ! empty( $chapters ) )
{ // Display the posts with chapters
	foreach( $chapters as $Chapter )
	{
		// Get the posts of current category
		$ItemList = new ItemList2( $Blog, $Blog->get_timestamp_min(), $Blog->get_timestamp_max() );
		$ItemList->set_filters( array(
				'cat_array'    => array( $Chapter->ID ), // Limit only by selected cat (exclude posts from child categories)
				'cat_modifier' => NULL,
				'unit'         => 'all', // Display all items of this category, Don't limit by page
			) );
		$ItemList->query();
		if( $ItemList->result_num_rows > 0 )
		{
			$no_content_to_display = false;
?>
<div class="posts_list">
	<div class="category_title clear"><h2>- <a href="<?php echo $Chapter->get_permanent_url(); ?>"><?php echo $Chapter->get( 'name' ); ?></a></h2></div>
<?php
			while( $Item = & $ItemList->get_item() )
			{ // For each blog post, do everything below up to the closing curly brace "}"
				// Temporarily switch to post locale (useful for multilingual blogs)
				$Item->locale_temp_switch();
?>
<div id="<?php $Item->anchor_id() ?>" class="<?php $Item->div_classes( $params ) ?>" lang="<?php $Item->lang() ?>">
<?php
				// Display images that are linked to this post:
				$item_first_image = $Item->get_images( array(
						'before_images'              => '<div class="evo_post_images">',
						'before_image'               => '<figure class="evo_image_block">',
						'before_image_legend'        => '<figcaption class="evo_image_legend">',
						'after_image_legend'         => '</figcaption>',
						'after_image'                => '</figure>',
						'after_images'               => '</div>',
						'image_class'                => 'img-responsive',
						'image_size'                 => $Skin->get_setting( 'posts_thumb_size' ),
						'image_link_to'              => 'single',
						'image_desc'                 => '',
						'gallery_image_limit'        => 0, // Don't use images from attached folders.
						'limit'                      => 1, // Get only first attached image depending on position priority, see param below:
						'restrict_to_image_position' => 'cover,teaser,aftermore,inline',
						'get_rendered_attachments'   => false,
						// Sort the attachments to get firstly "Cover", then "Teaser", and "After more" as last order
						'links_sql_select'           => ', CASE '
								.'WHEN link_position = "cover"     THEN "1" '
								.'WHEN link_position = "teaser"    THEN "2" '
								.'WHEN link_position = "aftermore" THEN "3" '
								.'WHEN link_position = "inline"    THEN "4" '
								// .'ELSE "99999999"' // Use this line only if you want to put the other position types at the end
							.'END AS position_order',
						'links_sql_orderby'          => 'position_order, link_order',
					) );
				if( empty( $item_first_image ) )
				{ // No images in this post, Display an empty block
					$item_first_image = $Item->get_permanent_link( '<b>'.T_('No pictures yet').'</b>', '#', 'album_nopic' );
				}
				else if( $item_first_image == 'plugin_render_attachments' )
				{ // No images, but some attachments(e.g. videos) are rendered by plugins
					$item_first_image = $Item->get_permanent_link( '<b>'.T_('Click to see contents').'</b>', '#', 'album_nopic' );
				}
				
				// Print first image
				echo $item_first_image;
				
				// Wrap post without image in borders
				echo '<section class="evo_post__full panel panel-default">';
				echo '<div class="evo_post__full_text panel-body">';
				
				// Display a title
				echo $Item->get_title( array(
						'before' => '<header><div class="evo_post_title"><h3>',
						'after'  => '</h3></div>',
						) );
				// Restore previous locale (Blog locale)
				locale_restore_previous();
				
				// Subtitle info
				echo '<div class="small text-muted">';
		
				if( $Item->status != 'published' )
				{
					$Item->format_status( array(
							'template' => '<div class="evo_status evo_status__$status$ badge pull-right">$status_title$</div>',
						) );
				}
				// Permalink:
				$Item->permanent_link( array(
						'text' => '#icon#',
					) );

				// We want to display the post time:
				$Item->issue_time( array(
						'before'      => ' '.T_('posted on '),
						'after'       => ' ',
						'time_format' => 'M j, Y',
					) );

				// Author
				$Item->author( array(
					'before'    => ' '.T_('by').' ',
					'after'     => ' ',
					'link_text' => $params['author_link_text'],
				) );

				// Categories
				$Item->categories( array(
					'before'          => /* TRANS: category name(s) */ T_('in').' ',
					'after'           => ' ',
					'include_main'    => true,
					'include_other'   => true,
					'include_external'=> true,
					'link_categories' => true,
				) );

				// Link for editing
				$Item->edit_link( array(
					'before'    => ' &bull; ',
					'after'     => '',
				) );
				
				echo '</div></header>';

				// We want excerpt here - shrinked post
				echo $Item->excerpt( array(
							'before' => '',
							'after'  => '',
							) );
				?>

				<footer>
					<?php // FOOTER OF THE POST
						if( ! $Item->is_intro() ) // Do NOT apply tags, comments and feedback on intro posts
						{ // List all tags attached to this post:
							$Item->tags( array(
									'before'    => '<nav class="small post_tags">',
									'after'     => '</nav>',
									'separator' => ' ',
								) );
					?>

					<nav>
					<?php
						// Link to comments, trackbacks, etc.:
						$Item->feedback_link( array(
										'type' => 'comments',
										'link_before' => '',
										'link_after' => '',
										'link_text_zero' => '#',
										'link_text_one' => '#',
										'link_text_more' => '#',
										'link_title' => '#',
										// fp> WARNING: creates problem on home page: 'link_class' => 'btn btn-default btn-sm',
										// But why do we even have a comment link on the home page ? (only when logged in)
									) );

						// Link to comments, trackbacks, etc.:
						$Item->feedback_link( array(
										'type' => 'trackbacks',
										'link_before' => ' &bull; ',
										'link_after' => '',
										'link_text_zero' => '#',
										'link_text_one' => '#',
										'link_text_more' => '#',
										'link_title' => '#',
									) );
					?>
					</nav>
					<?php } ?>
				</footer>

			</div> <!-- ../content_end_full_text -->
		</section>  <!-- ../content_end_full -->
</div>
<?php
			}
		}
?>
<?php
	}
} // ---------------------------------- END OF POSTS ------------------------------------
if( $no_content_to_display )
{ // No category and no post in this blog
	echo $params_no_content['before']
		.( is_logged_in() ? $params_no_content['msg_empty_logged_in'] : $params_no_content['msg_empty_not_logged_in'] )
		.$params_no_content['after'];
}
?>