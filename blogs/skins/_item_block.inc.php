<?php
/**
 * This is the template that displays the item block.
 * No skin should actually rely on this default version. However files like a_noskin.php or multiblogs.php
 * might rely on this if they containers/widgets that try to display post contents like the Featured/Intro post widget.
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template (or other templates)
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Item;

// Default params:
$params = array_merge( array(
		'disp_title'           => true,
		'item_title_link_type' => '#',
		'image_size'           => 'fit-400x320',
		'attached_pics'        => 'all', // 'none', 'first', 'all'
		'item_pic_link_type'   => 'original', // Can be 'original' (image) or 'single' (this post)
	), $params );

$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)
$Item->load_Blog();
?>

<div id="<?php $Item->anchor_id() ?>" class="bPost bPost<?php $Item->status_raw() ?>" lang="<?php $Item->lang() ?>">

	<div class="bSmallHead">
	<?php
		$Item->permanent_link( array(
				'text' => '#icon#',
			) );
	?>
	<?php
		$Item->issue_time(); // Post issue time
	?>
	<?php
		$Item->categories( array(
			'before'          => ', '.T_('Categories').': ',
			'after'           => ' ',
			'include_main'    => true,
			'include_other'   => true,
			'include_external'=> true,
			'link_categories' => true,
		) );
	?>
	</div>
	<?php
		if( $params['disp_title'] )
		{ // Display a title
	?>
	<h3 class="bTitle"><?php $Item->title( array(
			'link_type' => $params['item_title_link_type']
		) ); ?></h3>
	<?php } ?>

	<?php
		$image_limit = 1000;
		if( $params['attached_pics'] == 'none' )
		{ // Hide images
			$image_limit = 0;
			$params['image_size'] = NULL;
		}
		else if( $params['attached_pics'] == 'first' )
		{ // Display only first image
			$image_limit = 1;
		}
		// ---------------------- POST CONTENT INCLUDED HERE ----------------------
		skin_include( '_item_content.inc.php', array(
				'image_size'    => $params['image_size'],
				'image_limit'   => $image_limit,
				'image_link_to' => $params['item_pic_link_type'],
			) );
		// Note: You can customize the default item content by copying the generic
		// /skins/_item_content.inc.php file into the current skin folder.
		// -------------------------- END OF POST CONTENT -------------------------
	?>

	<?php
		// List all tags attached to this post:
		$Item->tags( array(
				'before' =>         '<div class="bSmallPrint">'.T_('Tags').': ',
				'after' =>          '</div>',
				'separator' =>      ', ',
			) );
	?>

	<div class="bSmallPrint">
		<?php
			// Link to comments, trackbacks, etc.:
			$Item->feedback_link( array(
							'type' => 'comments',
							'link_before' => '',
							'link_after' => ' &bull; ',
							'link_text_zero' => '#',
							'link_text_one' => '#',
							'link_text_more' => '#',
							'link_title' => '#',
							'use_popup' => false,
						) );
		?>
		<?php
			// Link to comments, trackbacks, etc.:
			$Item->feedback_link( array(
							'type' => 'trackbacks',
							'link_before' => '',
							'link_after' => ' &bull; ',
							'link_text_zero' => '#',
							'link_text_one' => '#',
							'link_text_more' => '#',
							'link_title' => '#',
							'use_popup' => false,
						) );
		?>

		<?php $Item->permanent_link(); ?>
	</div>
	<?php
		// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
		skin_include( '_item_feedback.inc.php', array(
				'before_section_title' => '<h4>',
				'after_section_title'  => '</h4>',
			) );
		// Note: You can customize the default item feedback by copying the generic
		// /skins/_item_feedback.inc.php file into the current skin folder.
		// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------
	?>
</div>

<?php
locale_restore_previous();	// Restore previous locale (Blog locale)
?>