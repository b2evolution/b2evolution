<?php
/**
 * This file implements the item_comment_feed_link Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author erhsatingin: Erwin Rommel Satingin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class item_comment_feed_link_Widget extends ComponentWidget
{
	var $icon = 'file-text';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'item_comment_feed_link' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'item-comment-feed-link-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Item Comment Feed Link');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('Item Comment Feed Link') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display item comment feed link.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		$r = array_merge( array(
				'title' => array(
					'label' => T_( 'Title' ),
					'size' => 40,
					'note' => T_( 'This is the title to display' ),
					'defaultvalue' => '',
				)
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{	// Disable "allow blockcache" because item content may includes other items by inline tags like [inline:item-slug]:
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

		return $r;
	}


	/**
	 * Prepare display params
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function init_display( $params )
	{
		global $preview;

		parent::init_display( $params );

		if( $preview )
		{	// Disable block caching for this widget when item is previewed currently:
			$this->disp_params['allow_blockcache'] = 0;
		}
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Item;
		global $cookie_name, $cookie_email, $cookie_url;
		global $comment_cookies, $comment_allow_msgform, $comment_anon_notify;
		global $Plugins;
		global $Collection, $Blog, $dummy_fields;

		if( empty( $Item ) )
		{	// Don't display this widget when no Item object:
			$this->display_error_message( 'Widget "'.$this->get_name().'" is disabled because there is no Item object.' );
			return false;
		}

		// Default renderers:
		$comment_renderers = array( 'default' );

		$this->init_display( $params );

		$params = array_merge( array(
			'widget_item_comment_feed_link_display' => true,
			'widget_item_comment_feed_link_params'  => array(),
		), $params );

		$widget_params = array_merge( array(
				'skin'   => '_rss2',
				'before' => '<nav class="evo_post_feedback_feed_msg"><p class="text-center">',
				'after'  => '</p></nav>',
				'title'  => '#',
			), $params['widget_item_comment_feed_link_params'] );

		if( $Item->can_see_comments( false ) && $params['widget_item_comment_feed_link_display'] )
		{
			echo $this->disp_params['block_start'];
			$this->disp_title();
			echo $this->disp_params['block_body_start'];

			$Item->feedback_feed_link( $widget_params['skin'], $widget_params['before'], $widget_params['after'], $widget_params['title'] );

			echo $this->disp_params['block_body_end'];
			echo $this->disp_params['block_end'];

			return true;
		}
		else
		{
			$this->display_debug_message();
			return false;
		}
	}
}

?>