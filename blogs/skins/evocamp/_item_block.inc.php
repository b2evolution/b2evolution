<?php
/**
 * This is the template that displays the item block
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template (or other templates)
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evoskins
 * @subpackage evocamp
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
	?>

	<h2><?php $Item->title(); ?></h2>

	<p class="postinfo">
	<?php
		if( ! $Item->is_intro() )
		{	// Display only if we're not displaying an intro post:
      $Item->author( array(
				'before'       => T_('By').' ',
				'after'        => ' ',
			) );
			$Item->issue_time( array(
					'before'      => /* TRANS: date */ T_('on '),
					'after'       => '',
					'time_format' => 'M j, Y',
				) );
			$Item->categories( array(
					'before'          => ' | '.T_('In '),
					'after'           => ' ',
					'include_main'    => true,
					'include_other'   => true,
					'include_external'=> true,
					'link_categories' => true,
				) );
			// Link to comments, trackbacks, etc.:
			$Item->feedback_link( array(
					'type' => 'feedbacks',
					'link_before' => ' | ',
					'link_after' => '',
					'link_text_zero' => '#',
					'link_text_one' => '#',
					'link_text_more' => '#',
					'link_title' => '#',
					'use_popup' => false,
				) );
			$Item->edit_link( array( // Link to backoffice for editing
					'before'    => ' | ',
					'after'     => '',
				) );

			}
		?>
		</p>
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
					'before' =>         '<div class="posttags">'.T_('Tags').': ',
					'after' =>          '</div>',
					'separator' =>      ', ',
				) );
		?>


	</div>

<?php
locale_restore_previous();	// Restore previous locale (Blog locale)

/*
 * $Log$
 * Revision 1.4  2011/09/04 02:30:20  fplanque
 * colorbox integration (MIT license)
 *
 * Revision 1.3  2010/02/08 17:56:22  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.2  2009/12/21 01:29:54  fplanque
 * little things
 *
 * Revision 1.1  2009/05/23 14:12:42  fplanque
 * All default skins now support featured posts and intro posts.
 *
 */
?>
