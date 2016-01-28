<?php
/**
 * This is the template that displays the item block: title, author, content (sub-template), tags, comments (sub-template)
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template (or other templates)
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Item, $Skin;

// Default params:
$params = array_merge( array(
		'feature_block'          	 => false,
		'item_class'                 => 'evo_post evo_content_block',
		'item_type_class'            => 'evo_post__ptyp_',
		'item_status_class'          => 'evo_post__',
		'content_mode'          	 => 'full', // We want regular "full" content, even in category browsing: i-e no excerpt or thumbnail
		'image_size'            	 => '', // Do not display images in content block - Image is handled separately
		'url_link_text_template'	 => '', // link will be displayed (except player if podcast)
		'image_class'                => '',
	), $params );

?>

<article id="<?php $Item->anchor_id() ?>" class="<?php $Item->div_classes( $params ) ?>" lang="<?php $Item->lang() ?>">

	<?php
		$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)
	?>

	<?php
		// Display images that are linked to this post:
		$Item->images( array(
				'before' =>              '<div class="evo_post_images">',
				'before_image' =>        '<figure class="evo_image_block center">',
				'before_image_legend' => '<figcaption class="evo_image_legend">',
				'after_image_legend' =>  '</figcaption>',
				'after_image' =>         '</figure>',
				'after' =>               '</div>',
				'image_size' =>          'fit-720x500',
				/* Comment the above line to use the default image size
				 * (fit-720x500). Possible values for the image_size
				 * parameter are:
				 * fit-720x500, fit-640x480, fit-520x390, fit-400x320,
				 * fit-320x320, fit-160x160, fit-160x120, fit-80x80,
				 * crop-80x80, crop-64x64, crop-48x48, crop-32x32,
				 * crop-15x15
				 * See also the $thumbnail_sizes array in conf/_advanced.php.
				 */
				// Optionally restrict to files/images linked to specific position: 'teaser'|'teaserperm'|'teaserlink'|'aftermore'|'inline'|'cover'
				'restrict_to_image_position' => 'cover,teaser,teaserperm,teaserlink',
				'before_gallery'           => '<div class="evo_post_gallery">',
				'after_gallery'            => '</div>',
				'gallery_table_start'      => '',
				'gallery_table_end'        => '',
				'gallery_row_start'        => '',
				'gallery_row_end'          => '',
				'gallery_cell_start'       => '<div class="evo_post_gallery__image">',
				'gallery_cell_end'         => '</div>',
				'gallery_image_size'       => 'crop-80x80',
				'gallery_image_limit'      => 1000,
				'gallery_colls'            => 5,
				'gallery_order'            => '', // Can be 'ASC', 'DESC', 'RAND' or empty
			) );
	?>


	<div class="evo_post_details panel-body">

		<div class="evo_post_details_header">

			<?php
				if( $Item->status != 'published' )
				{
					$Item->format_status( array(
							'template' => '<div class="floatright"><span class="note status_$status$"><span>$status_title$</span></span></div>',
						) );
				}
				
				echo '<div class="action_right">';
					
				// Link for editing
				$Item->edit_link( array(
					'before'    => '',
					'after'     => '',
					'title'     => T_('Edit title/description...'),
				) );
				
				// Permalink:
				$Item->permanent_link( array(
						'before'    => '',
						'after'     => '',
						'text' => '<i class="fa fa-external-link"></i> '.T_('Permalink'),
					) );
					
				// Link to comments, trackbacks, etc.:
				$Item->feedback_link( array(
								'type' => 'feedbacks',
								'link_before' => '',
								'link_after' => '',
								'link_text_zero' => get_icon( 'nocomment' ),
								'link_text_one' => '1 '.get_icon( 'comments' ),
								'link_text_more' => T_('%d ').get_icon( 'comments' ),
								'link_title' => '#',
							) );

				
				echo '</div>';
				
			?>

			<h3 class="evo_post_title linked"><?php
			
			if( ! $Item->is_intro() )
			{
				$permalink_title = 'permalink';
			}
			else
			{
				$permalink_title = '';
			}
				$Item->title( array(
					'link_type' => $permalink_title,
					) );
			?></h3>

			<?php
				$Item->issue_date( array(
						'before'      => '<span class="timestamp">',
						'after'       => '</span>',
						'date_format' => locale_datefmt().' H:i',
					) );
			?>

		</div>

		<?php
			// ---------------------- POST CONTENT INCLUDED HERE ----------------------
			// Note: at the top of this file, we set: 'image_size' =>	'', // Do not display images in content block - Image is handled separately
			skin_include( '_item_content.inc.php', $params );
			// Note: You can customize the default item content by copying the generic
			// /skins/_item_content.inc.php file into the current skin folder.
			// -------------------------- END OF POST CONTENT -------------------------
		?>

		<div class="evo_post_footer">
		<?php
			$Item->author( array(
					'before'    => T_('By').' ',
					'after'     => ' &bull; ',
					'link_text' => 'preferredname',
				) );
		?>

		<?php
			$Item->categories( array(
				'before'          => T_('Galleries').': ',
				'after'           => ' ',
				'include_main'    => true,
				'include_other'   => true,
				'include_external'=> true,
				'link_categories' => true,
			) );
		?>

		<?php
			// List all tags attached to this post:
			if( ! $Item->is_intro() ) {
				$Item->tags( array(
						'before' =>         ' &bull; '.T_('Tags').': ',
						'after' =>          ' ',
						'separator' =>      ', ',
					) );
			}
		?>

		<?php
			// URL link, if the post has one:
			$Item->url_link( array(
					'before'        => ' &bull; '.T_('Link').': ',
					'after'         => ' ',
					'text_template' => '$url$',
					'url_template'  => '$url$',
					'target'        => '',
					'podcast'       => false,        // DO NOT display mp3 player if post type is podcast
				) );
		?>

		</div>
	</div>

	<?php
		// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
		skin_include( '_item_feedback.inc.php', array(
				'before_section_title' => '<h4>',
				'after_section_title'  => '</h4>',
				'author_link_text' => 'preferredname',
			) );
		// Note: You can customize the default item feedback by copying the generic
		// /skins/_item_feedback.inc.php file into the current skin folder.
		// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------
	?>

	<?php
		// ------------------ WORKFLOW PROPERTIES INCLUDED HERE ------------------
		skin_include( '_item_workflow.inc.php' );
		// ---------------------- END OF WORKFLOW PROPERTIES ---------------------
	?>

	<?php
		// ------------------ META COMMENTS INCLUDED HERE ------------------
		skin_include( '_item_meta_comments.inc.php', array(
				'comment_start'         => '<article class="evo_comment evo_comment__meta panel panel-default">',
				'comment_end'           => '</article>',
			) );
		// ---------------------- END OF META COMMENTS ---------------------
	?>

	<?php
		locale_restore_previous();	// Restore previous locale (Blog locale)
	?>

</article>