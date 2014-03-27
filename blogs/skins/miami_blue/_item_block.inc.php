<?php
/**
 * This is the template that displays the item block
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
		'feature_block'   => false,
		'content_mode'    => 'auto',		// 'auto' will auto select depending on $disp-detail
		'item_class'      => 'bPost',
		'item_status_class' => 'bPost',
		'image_size'	    => 'fit-400x320',
	), $params );

?>

<div id="<?php $Item->anchor_id() ?>" class="<?php $Item->div_classes( $params ) ?>" lang="<?php $Item->lang() ?>">

	<?php
		$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)
	?>

	<h2 class="bTitle linked"><?php
		$Item->title( array(
			'link_type' => 'permalink'
			) );
	?></h2>
	<div class="bSmallHead">
	<?php
		if( $Item->status != 'published' )
		{
			$Item->status( array( 'format' => 'styled' ) );
		}
		$Item->issue_date( array(
				'before'      => ' ',
				'after'       => ' ',
				'date_format' => '#',
			) );

		$Item->author( array(
				'before'    => ' | '.T_('by').' <strong>',
				'after'     => '</strong>',
				'link_text' => 'preferredname',
			) );
		$Item->msgform_link( array(
				'text'    => '<span class="mb_icon envelope"></span>',
			) );
	?>
	<?php
		$Item->categories( array(
			'before'          => ' | '.T_('Categories').': ',
			'after'           => ' ',
			'include_main'    => true,
			'include_other'   => true,
			'include_external'=> true,
			'link_categories' => true,
			'before_main'     => '<strong>',
			'after_main'      => '</strong>',
		) );
	?>
	</div>

	<?php
		// ---------------------- POST CONTENT INCLUDED HERE ----------------------
		skin_include( '_item_content.inc.php', $params );
		// Note: You can customize the default item feedback by copying the generic
		// /skins/_item_content.inc.php file into the current skin folder.
		// -------------------------- END OF POST CONTENT -------------------------
	?>

	<?php
		// List all tags attached to this post:
		$Item->tags( array(
				'before' =>         '<div class="">'.T_('Tags').': ',
				'after' =>          '</div>',
				'separator' =>      ', ',
			) );
	?>

	<div class="bSmallPrint">
		<?php
			// Link to comments, trackbacks, etc.:
			$Item->feedback_link( array(
							'type' => 'feedbacks',
							'link_before' => '',
							'link_after' => ' &bull; ',
							'link_text_zero' => '#',
							'link_text_one' => '#',
							'link_text_more' => '#',
							'link_title' => '#',
							'use_popup' => false,
						) );

			$Item->edit_link( array( // Link to backoffice for editing
					'before'    => '',
					'after'     => ' &bull; ',
				) );

			$Item->permanent_link( array(
					'text' => '<span class="mb_icon page"></span>',
				) );
		?>
	</div>
	<?php
		echo get_icon( 'pixel' );
		// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
		skin_include( '_item_feedback.inc.php', array(
				'before_section_title' => '<h4>',
				'after_section_title'  => '</h4>',
				'author_link_text'     => 'preferredname',
			) );
		// Note: You can customize the default item feedback by copying the generic
		// /skins/_item_feedback.inc.php file into the current skin folder.
		// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------
	?>

	<?php
		locale_restore_previous();	// Restore previous locale (Blog locale)
	?>
</div>