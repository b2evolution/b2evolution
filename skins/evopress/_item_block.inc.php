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
 * @subpackage evopress
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Item;

// Default params:
$params = array_merge( array(
		'feature_block'   => false,
		'content_mode'    => 'auto',		// 'auto' will auto select depending on $disp-detail
		'item_class'      => 'post',
		'image_size'	    => 'fit-400x320',
	), $params );

?>
<div id="<?php $Item->anchor_id() ?>" class="<?php $Item->div_classes( $params ) ?>" lang="<?php $Item->lang() ?>">

	<?php
		$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)
	?>

	<?php
		if(	$Item->is_intro() )
		{	// Display edit link only if we're displaying an intro post:
			$Item->edit_link( array( // Link to backoffice for editing
					'before'    => '<div class="floatright">',
					'after'     => '</div>',
				) );
		}
		if( $Item->status != 'published' )
		{
			$Item->format_status( array(
					'template' => '<div class="floatright"><span class="note status_$status$"><span>$status_title$</span></span></div>',
				) );
		}

		echo "<h2>";
		$Item->title();
		$Item->load_Blog();
		if( $Item->Blog->get_setting( 'track_unread_content' ) )
		{
			$Item->display_unread_status();
		}
		echo "</h2>";

		if( (!$Item->is_intro()) && $Skin->get_setting( 'display_post_date') )
		{	// Display only if we're *not* displaying an intro post AND we want to see the date:
			$Item->issue_time( array(
					'before'      => '<small>',
					'after'       => '</small>',
					'time_format' => 'F jS, Y',
				) );
		}
	?>

	<?php
		// ---------------------- POST CONTENT INCLUDED HERE ----------------------
		skin_include( '_item_content.inc.php', $params );
		// Note: You can customize the default item content by copying the generic
		// /skins/_item_content.inc.php file into the current skin folder.
		// -------------------------- END OF POST CONTENT -------------------------
	?>

	<?php
		// List all tags attached to this post:
		$Item->tags( array(
				'before' =>         '<div class="posttags">'.T_('Tags').': ',
				'after' =>          '</div>',
				'separator' =>      ', ',
			) );
	?>

	<?php
		if( ! $Item->is_intro() )
		{	// Display only if we're not displaying an intro post:
			?>
			<p class="postmetadata">
				<?php
					$Item->categories( array(
						'before'          => T_('Posted in').' ',
						'after'           => ' ',
						'include_main'    => true,
						'include_other'   => true,
						'include_external'=> true,
						'link_categories' => true,
					) );
				?>

				<?php
					$Item->edit_link( array( // Link to backoffice for editing
							'before'    => ' | ',
							'after'     => '',
						) );
				?>

				<?php
					// Link to comments, trackbacks, etc.:
					$Item->feedback_link( array(
							'type' => 'feedbacks',
							'link_before' => ' | ',
							'link_after' => '',
							'link_text_zero' => '#',
							'link_text_one' => '#',
							'link_text_more' => '#',
							'link_title' => '#',
						) );
				?>
				</p>
			<?php
		}
	?>
</div>

<?php
locale_restore_previous();	// Restore previous locale (Blog locale)
?>