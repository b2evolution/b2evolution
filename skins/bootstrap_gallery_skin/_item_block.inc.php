<?php
/**
 * This is the template that displays the item block
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template (or other templates)
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage photoalbums
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Item;

// Default params:
$params = array_merge( array(
		'feature_block'          => false,
		'item_class'             => 'bPost',
		'item_status_class'      => 'bPost',
		'content_mode'           => 'full', // We want regular "full" content, even in category browsing: i-e no excerpt or thumbnail
		'image_size'             => '', // Do not display images in content block - Image is handled separately
		'url_link_text_template' => '', // link will be displayed (except player if podcast)
	), $params );
	
?>

<!--<div id="<?php // $Item->anchor_id() ?>" class="<?php //$Item->div_classes( $params ) ?>" lang="<?php //fbPostContent$Item->lang() ?>">-->
	<div class="col-lg-12 single_post">

	<?php
		$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)
	?>

	<?php
		// Display images that are linked to this post:
		echo '<div class="post_images col-xl-9 col-lg-8 col-md-6 col-sm-6">';
		$Item->images( array(
				'before'              => '',
				'before_image'        => '<div class="single-image">',
				'before_image_legend' => '<div class="image_legend">',
				'after_image_legend'  => '</div>',
				'after_image'         => '</div>',
				'after'               => '<div class="clear"></div>',
				'image_size'          => $Skin->get_setting( 'single_thumb_size' ),
				'image_align'         => 'middle',
			) );
		echo '</div>';
	?>

<div class="bPostContent col-xl-3 col-lg-4 col-md-6 col-sm-6">

	<div class="bDetails">

		<?php
			// ---------------------- POST CONTENT INCLUDED HERE ----------------------
			// Note: at the top of this file, we set: 'image_size' =>	'', // Do not display images in content block - Image is handled separately
			skin_include( '_item_content.inc.php', $params );
			// Note: You can customize the default item content by copying the generic
			// /skins/_item_content.inc.php file into the current skin folder.
			// -------------------------- END OF POST CONTENT -------------------------
		?>

		<?php
			// URL link, if the post has one:
			$Item->url_link( array(
					'before'        => '<div class="bSmallPrint">'.T_('Link').': ',
					'after'         => '</div>',
					'text_template' => '$url$',
					'url_template'  => '$url$',
					'target'        => '',
					'podcast'       => false,        // DO NOT display mp3 player if post type is podcast
				) );
		?>

		<div class="item_comments">
			<?php
				// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
				skin_include( '_item_feedback.inc.php', array(
						'before_section_title' => '<h4>',
						'after_section_title'  => '</h4>',
						'author_link_text'     => 'preferredname',
						'comment_image_size'   => 'fit-256x256',
					) );
				// Note: You can customize the default item feedback by copying the generic
				// /skins/_item_feedback.inc.php file into the current skin folder.
				// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------
			?>
		</div>

	</div>

</div>
	<?php
		locale_restore_previous();	// Restore previous locale (Blog locale)
	?>

</div>