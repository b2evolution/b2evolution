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
 * @subpackage photoalbum
 *
 * @version $Id: _item_block.inc.php 7043 2014-07-02 08:35:45Z yura $
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

<div id="<?php $Item->anchor_id() ?>" class="<?php $Item->div_classes( $params ) ?>" lang="<?php $Item->lang() ?>">

	<?php
		$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)
	?>

	<?php
		// Display images that are linked to this post:
		$Item->images( array(
				'before'              => '<div class="post_images">',
				'before_image'        => '<div class="image_block"><div>',
				'before_image_legend' => '<div class="image_legend">',
				'after_image_legend'  => '</div>',
				'after_image'         => '</div></div>',
				'after'               => '</div>',
				'image_size'          => $Skin->get_setting( 'single_thumb_size' ),
				'image_align'         => 'middle',
			) );
	?>

<div class="bPostContent">

	<div class="bDetails">

		<?php
			// ---------------------- POST CONTENT INCLUDED HERE ----------------------
			// Note: at the top of this file, we set: 'image_size' =>	'', // Do not display images in content block - Image is handled separately
			skin_include( '_item_content.inc.php', $params );
			// Note: You can customize the default item feedback by copying the generic
			// /skins/_item_feedback.inc.php file into the current skin folder.
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
<script type="text/javascript">
var has_touch_event;
window.addEventListener( 'touchstart', function set_has_touch_event ()
{
	has_touch_event = true;
	// Remove event listener once fired, otherwise it'll kill scrolling
	window.removeEventListener( 'touchstart', set_has_touch_event );
}, false );

/**
 * Change nav position to fixed or revert to static
 */
function change_position_nav()
{
	if( has_touch_event )
	{ // Don't fix the objects on touch devices
		return;
	}

	if( nav_size )
	{ // Navigation bar
		if( !$nav.hasClass( 'fixed' ) && jQuery( window ).scrollTop() > $nav.offset().top - nav_top )
		{ // Make nav as fixed if we scroll down
			$nav.before( $navSpacer );
			$nav.addClass( 'fixed' ).css( 'top', nav_top + 'px' );
		}
		else if( $nav.hasClass( 'fixed' ) && jQuery( window ).scrollTop() < $navSpacer.offset().top - nav_top )
		{ // Remove 'fixed' class from nav if we scroll to the top of page
			$nav.removeClass( 'fixed' ).css( 'top', '' );
			$navSpacer.remove();
		}
	}
}

var $nav = jQuery( '.nav_album' );
var nav_size = $nav.size();
var nav_top = <?php echo ( is_logged_in() ? 23 : 0 ) ; ?>;
var $navSpacer = $( '<div />', {
		"height": $nav.outerHeight( true ),
	} );

jQuery( window ).resize( function()
{
	change_position_nav();
} );
jQuery( window ).scroll( function ()
{
	change_position_nav();
} );
</script>