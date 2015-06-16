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
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Item;

// Default params:
$params = array_merge( array(
		'feature_block' => false,
		'content_mode'  => 'auto', // 'auto' will auto select depending on $disp-detail
		'item_class'    => 'bPost',
		'image_size'    => 'fit-400x320',
	), $params );

?>

<div id="<?php $Item->anchor_id() ?>" lang="<?php $Item->lang() ?>">

<?php
	$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)
?>
<!-- google_ad_section_start -->
<div class="bTitle"><h3 class="bTitle"><?php
	$Item->title( array(
			'link_type' => 'permalink'
		) );
?></h3></div>
<!-- google_ad_section_end -->
<div class="bPost">
	<div class="bSmallHead">
		<?php
		if( $Item->status != 'published' )
		{
			$Item->format_status( array(
					'template' => '<div class="floatright"><span class="note status_$status$"><span>$status_title$</span></span></div>',
				) );
		}
		$Item->permanent_link( array(
				'text' => '#icon#',
			) );
		$Item->author( array(
				'before'       => ' '.T_('by').' <strong>',
				'after'        => '</strong>',
				'link_to'		   => 'userpage',
				'link_text'    => 'preferredname',
			) );
		$Item->msgform_link( array(
				'before'    => ' ',
				'after'     => '',
			) );
		$Item->issue_time( array(
				'before'    => ', ',
				'after'     => '',
				'date_format' => 'l j F Y',
			) );
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

	<!-- google_ad_section_start -->
	<?php
		// ---------------------- POST CONTENT INCLUDED HERE ----------------------
		skin_include( '_item_content.inc.php', array(
				'image_size' => 'fit-400x320',
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
	<!-- google_ad_section_end -->

	<div class="bSmallPrint">
		<?php
			$Item->edit_link( array( // Link to backoffice for editing
					'before'    => '',
					'after'     => '',
					'class'     => 'permalink_right'
				) );
		?>

		<?php
			// Link to comments, trackbacks, etc.:
			$Item->feedback_link( array(
							'type' => 'comments',
							'link_before' => ' <span class="bCommentLink">',
							'link_after' => '</span> ',
							'link_text_zero' => '#',
							'link_text_one' => '#',
							'link_text_more' => '#',
							'link_title' => '#',
						) );
		?>
		<?php
			// Link to comments, trackbacks, etc.:
			$Item->feedback_link( array(
							'type' => 'trackbacks',
							'link_before' => ' <span class="bCommentLink">',
							'link_after' => '</span> ',
							'link_text_zero' => '#',
							'link_text_one' => '#',
							'link_text_more' => '#',
							'link_title' => '#',
						) );
		?>
	</div>

	<?php
		// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
		skin_include( '_item_feedback.inc.php', array(
				'before_section_title' => '<h4>',
				'after_section_title'  => '</h4>',
				'link_to' => 'userpage>userurl',
				'author_link_text' => 'preferredname',
			) );
		// Note: You can customize the default item feedback by copying the generic
		// /skins/_item_feedback.inc.php file into the current skin folder.
		// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------
	?>
</div>
</div>
<?php
locale_restore_previous();	// Restore previous locale (Blog locale)
?>