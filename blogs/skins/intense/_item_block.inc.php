<?php
/**
 * This is the template that displays the item block
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-structure}
 *
 * This is meant to be included in a page template.
 *
 * @package evoskins
 * @subpackage intense
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Item;

// Default params:
$params = array_merge( array(
		'feature_block' => false,
		'content_mode'  => 'auto',		// 'auto' will auto select depending on $disp-detail
		'item_class'    => 'post',
		'image_size'  	=> 'fit-400x320',
	), $params );

?>

<div id="<?php $Item->anchor_id() ?>" class="<?php $Item->div_classes( $params ) ?>" lang="<?php $Item->lang() ?>">

<?php
	$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilangual blogs)
?>



<?php

	if(	$Item->is_intro() )
	{	// Display edit link only if we're displaying an intro post:
		$Item->edit_link( array( // Link to backoffice for editing
				'before' => '<div>',
				'after'  => '</div>',
			) );
	}
	if( $Item->status != 'published' )
	{
		$Item->status( array( 'format' => 'styled' ) );
	}
?>

<h2 class="post-title"><?php $Item->title(); ?></h2>

<?php

	if( ! $Item->is_intro() )
	{	// Display only if we're not displaying an intro post:
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
	// Note: You can customize the default item feedback by copying the generic
	// /skins/_item_content.inc.php file into the current skin folder.
	// -------------------------- END OF POST CONTENT -------------------------
?>

<?php
	// List all tags attached to this post:
	$Item->tags( array(
			'before'    => '<div class="posttags">'.T_('Tags').': ',
			'after'     => '</div>',
			'separator' => ', ',
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
			'before' => ' | ',
			'after'  => '',
		) );
?>

<?php
	// Link to comments, trackbacks, etc.:
	$Item->feedback_link( array(
			'type'           => 'feedbacks',
			'link_before'    => ' | ',
			'link_after'     => '',
			'link_text_zero' => '#',
			'link_text_one'  => '#',
			'link_text_more' => '#',
			'link_title'     => '#',
			'use_popup'      => false,
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