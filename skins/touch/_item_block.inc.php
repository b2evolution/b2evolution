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
 * @subpackage touch
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
<div class="post" id="<?php $Item->anchor_id() ?>" lang="<?php $Item->lang() ?>">

	<?php if( $Item->is_intro() ) { ?>
	<div class="sticky-icon"></div>
	<?php } ?>

	<?php
		if( ! $Item->is_intro() )
		{	// Link to comments, trackbacks, etc.:
			$Item->feedback_link( array(
					'type' => 'feedbacks',
					'link_before' => '<div class="comment-bubble">',
					'link_after' => '</div>',
					'link_text_zero' => '',
					'link_text_one' => '1',
					'link_text_more' => '%d',
					'link_title' => '',
				) );
		}
	?>

	<a class="post-arrow" id="arrow-<?php echo $Item->ID; ?>" href="javascript:"></a>

	<div class="calendar">
		<div class="cal-month month-<?php $Item->issue_time( array( 'time_format' => 'm', 'before' => '', 'after' => '' ) ); ?>"><?php $Item->issue_time( array( 'time_format' => 'M', 'before' => '', 'after' => '' ) ); ?></div>
		<div class="cal-date"><?php $Item->issue_time( array( 'time_format' => 'j', 'before' => '', 'after' => '' ) ); ?></div>
	</div>

	<?php
		$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)
	?>

	<?php
		if( ! $Item->is_intro() )
		{	// Display only if we're not displaying an intro post:
			$Item->edit_link( array( // Link to backoffice for editing
					'before' => '<div class="post-actions">',
					'after'  => '</div>',
				) );
		}
	?>
	<?php
		$Item->title( array(
				'before'     => '<h2 class="post-title">',
				'after'      => '</h2>',
			) );
	?>

	<div class="post-author">
		<span class="lead">By</span> <?php $Item->author( array( 'link_text' => 'preferredname' ) ) ?><br />

	<?php
		if( ! $Item->is_intro() )
		{	// Display only if we're not displaying an intro post:
			$Item->categories( array(
				'before'          => '<span class="lead">'.T_('Categories').'</span> ',
				'after'           => '<br />',
				'include_main'    => true,
				'include_other'   => true,
				'include_external'=> true,
				'link_categories' => true,
			) );
		}
	?>

	<?php
		// List all tags attached to this post:
		$Item->tags( array(
				'before' =>         '<span class="lead">'.T_('Tags').':</span>',
				'after' =>          '',
				'separator' =>      ', ',
			) );
	?>
	</div>

<div class="clearer"></div>

<div id="entry-<?php echo $Item->ID ?>" style="display:none" class="mainentry left-justified">
	<?php
		// ---------------------- POST CONTENT INCLUDED HERE ----------------------
		skin_include( '_item_content.inc.php', $params );
		// Note: You can customize the default item content by copying the generic
		// /skins/_item_content.inc.php file into the current skin folder.
		// -------------------------- END OF POST CONTENT -------------------------
	?>
</div>
	<div class="clearer"></div>
</div>

<?php
locale_restore_previous();	// Restore previous locale (Blog locale)
?>