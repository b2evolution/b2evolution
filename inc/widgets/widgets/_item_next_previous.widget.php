<?php
/**
 * This file implements the item_next_previous Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
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
 * @author erhsatingin: Erwin Rommel Satingin.
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
class item_next_previous_Widget extends ComponentWidget
{
	var $icon = 'window-minimize';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'item_next_previous' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'item-next-previous-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Item Next/Previous');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('Item Next/Previous') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display controls to navigate to the next/previous items.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		global $Blog;

		$r = array_merge( array(
				'title' => array(
					'label' => T_( 'Title' ),
					'size' => 40,
					'note' => T_( 'This is the title to display' ),
					'defaultvalue' => '',
				),
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{	// Disable "allow blockcache" because this widget displays dynamic data:
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

		return $r;
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Item, $disp;

		if( isset( $params['ignored_widgets'] ) && in_array( $this->code, $params['ignored_widgets'] ) )
		{
			return false;
		}

		$params = array_merge( array(
				'widget_item_next_previous_block_start'    => '',
				'widget_item_next_previous_block_end'      => '',
				'widget_item_next_previous_template'       => '$prev$$separator$$next$',
				'widget_item_next_previous_prev_start'     => '',
				'widget_item_next_previous_prev_text'      => '&laquo; $title$',
				'widget_item_next_previous_prev_end'       => '',
				'widget_item_next_previous_prev_no_item'   => '',
				'widget_item_next_previous_prev_class'     => '',
				'widget_item_next_previous_separator'      => '',
				'widget_item_next_previous_next_start'     => '',
				'widget_item_next_previous_next_text'      => '$title$ &raquo;',
				'widget_item_next_previous_next_end'       => '',
				'widget_item_next_previous_next_no_item'   => '',
				'widget_item_next_previous_next_class'     => '',
				'widget_item_next_previous_target_blog'    => '',
				'widget_item_next_previous_post_navigation'=> NULL,
				'widget_item_next_previous_itemtype_usage' => 'post', // Include only post with type usage "post"
				'widget_item_next_previous_featured'       => NULL,
			), $params );

		$this->init_display( $params );

		if( $disp == 'single' )
		{
			echo $this->disp_params['block_start'];
			$this->disp_title();
			echo $this->disp_params['block_body_start'];

			item_prevnext_links( array(
				'block_start'    => $params['widget_item_next_previous_block_start'],
				'block_end'      => $params['widget_item_next_previous_block_end'],
				'template'       => $params['widget_item_next_previous_template'],
				'prev_start'     => $params['widget_item_next_previous_prev_start'],
				'prev_text'      => $params['widget_item_next_previous_prev_text'],
				'prev_end'       => $params['widget_item_next_previous_prev_end'],
				'prev_no_item'   => $params['widget_item_next_previous_prev_no_item'],
				'prev_class'     => $params['widget_item_next_previous_prev_class'],
				'separator'      => $params['widget_item_next_previous_separator'],
				'next_start'     => $params['widget_item_next_previous_next_start'],
				'next_text'      => $params['widget_item_next_previous_next_text'],
				'next_end'       => $params['widget_item_next_previous_next_end'],
				'next_no_item'   => $params['widget_item_next_previous_next_no_item'],
				'next_class'     => $params['widget_item_next_previous_next_class'],
				'target_blog'    => $params['widget_item_next_previous_target_blog'],
				'post_navigation'=> $params['widget_item_next_previous_post_navigation'],
				'itemtype_usage' => $params['widget_item_next_previous_itemtype_usage'],
				'featured'       => $params['widget_item_next_previous_featured'],
			) );

			echo $this->disp_params['block_body_end'];
			echo $this->disp_params['block_end'];

			return true;
		}

		return false;
	}
}

?>