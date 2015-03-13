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
 * @subpackage bootstrap_main
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Item, $Skin, $disp;

// Default params:
$params = array_merge( array(
		'disp_title'       => true,
		'feature_block'    => false,
		'content_mode'     => 'auto',		// 'auto' will auto select depending on $disp-detail
		'item_class'       => 'bPost',
		'image_class'      => 'img-responsive',
		'image_size'       => 'fit-1280x720',
		'author_link_text' => 'preferredname',
	), $params );

echo '<div id="styled_content_block">'; // Beginning of post display
?>

<div id="<?php $Item->anchor_id() ?>" class="<?php $Item->div_classes( $params ) ?>" lang="<?php $Item->lang() ?>">

	<?php
		$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)

		if( $params['disp_title'] && $disp != 'single' && $disp != 'page' )
		{ // Don't display this on disp=single because there is already title header in h2

			$title_before = '<h2>';
			$title_after = '</h2>';
			if( $Item->is_intro() )
			{ // Display a link to edit the post only for intro post, because for all other posts it is displayed below under title
				$title_before = '<div class="post_title"><h2>';
				$title_after = '</h2>'.$Item->get_edit_link( array(
						'before' => '<div class="'.button_class( 'group' ).'">',
						'after'  => '</div>',
						'text'   => $Item->is_intro() ? get_icon( 'edit' ).' '.T_('Edit Intro') : '#',
						'class'  => button_class( 'text' ),
					) ).'</div>';
			}

			$Item->title( array(
					'before'    => $title_before,
					'after'     => $title_after,
					'link_type' => 'permalink'
				) );
		}
	?>

	<?php
	if( $disp != 'front' && ! $Item->is_intro() )
	{ // Don't display these data for intro posts
	?>
	<div class="small text-muted">
	<?php
		if( $Item->status != 'published' )
		{
			$Item->status( array( 'format' => 'styled' ) );
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
			'before'          => T_('in').' ',
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
	?>
	</div>
	<?php
	}
	?>

	<?php
		// ---------------------- POST CONTENT INCLUDED HERE ----------------------
		skin_include( '_item_content.inc.php', $params );
		// Note: You can customize the default item content by copying the generic
		// /skins/_item_content.inc.php file into the current skin folder.
		// -------------------------- END OF POST CONTENT -------------------------

		// List all tags attached to this post:
		$Item->tags( array(
				'before'    => '<div class="small">'.T_('Tags').': ',
				'after'     => '</div>',
				'separator' => ', ',
			) );
	?>

	<div class="small">
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
	</div>

	<?php
		// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
		skin_include( '_item_feedback.inc.php', array_merge( array(
				'before_section_title' => '<div class="clearfix"></div><h4>',
				'after_section_title'  => '</h4>',
				'author_link_text' => $params['author_link_text'],
			), $params ) );
		// Note: You can customize the default item feedback by copying the generic
		// /skins/_item_feedback.inc.php file into the current skin folder.
		// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------
	?>

	<?php
		locale_restore_previous();	// Restore previous locale (Blog locale)
	?>
</div>
<?php echo '</div>'; // End of post display ?>