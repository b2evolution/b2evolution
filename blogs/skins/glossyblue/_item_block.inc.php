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
		'item_class'      => 'post',
		'image_size'	    => 'fit-400x320',
		'content_start_excerpt' => '<div class="content_excerpt entry">',
		'content_end_excerpt'   => '</div>',
		'content_start_full'    => '<div class="content_full">',
		'content_end_full'      => '</div>',
	), $params );

?>

<div id="<?php $Item->anchor_id() ?>" class="<?php $Item->div_classes( $params ) ?>" lang="<?php $Item->lang() ?>">

	<?php
		$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)

	if( ! $Item->is_intro() )
	{
		?>
		<div class="post-date">
			<span class="post-month"><?php $Item->issue_time( array(
						'before'    => '',
						'after'     => '',
						'date_format' => 'M',
					)); ?></span>
			<span class="post-day"><?php $Item->issue_time( array(
						'before'    => '',
						'after'     => '',
						'date_format' => 'd',
					)); ?></span>
		</div>
		<?php
	}
	?>

	<div class="post-title">
		<?php
		if( $Item->status != 'published' )
		{
			$Item->status( array( 'format' => 'styled' ) );
		}
		?>
		<h2><?php $Item->title(); ?></h2>
	<span class="post-cat"><?php
			$Item->categories( array(
				'before'          => '',
				'after'           => ' ',
				'include_main'    => true,
				'include_other'   => true,
				'include_external'=> true,
				'link_categories' => true,
			) );
		?></span> <span class="post-comments"><?php // Link to comments, trackbacks, etc.:
				$Item->feedback_link( array(
								'link_before' => '',
								'link_after' => '',
								'link_text_zero' => '#',
								'link_text_one' => '#',
								'link_text_more' => '#',
								'link_title' => '#',
								'use_popup' => false,
							) ); ?></span>
	</div>
	<?php
		// ---------------------- POST CONTENT INCLUDED HERE ----------------------
		skin_include( '_item_content.inc.php', $params );
		// Note: You can customize the default item feedback by copying the generic
		// /skins/_item_content.inc.php file into the current skin folder.
		// -------------------------- END OF POST CONTENT -------------------------
	?>

	<p class="postmetadata alt small">
		<?php
			// List all tags attached to this post:
			$Item->tags( array(
					'before' =>         T_('Tags').': ',
					'after' =>          ' ',
					'separator' =>      ', ',
				) );
		?>

		<?php
			$Item->edit_link( array( // Link to backoffice for editing
					'before'    => '',
					'after'     => '',

				) );
		?>
	</p>

	<?php
		locale_restore_previous();	// Restore previous locale (Blog locale)
	?>
</div>