<?php
/**
 * This file implements the item_title Widget class.
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
class item_title_Widget extends ComponentWidget
{
	var $icon = 'header';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'item_title' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'item-title-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Item Title');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('Item Title') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display the title of the item.');
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

		$params = array_merge( array(
				'widget_item_title_display'         => true,
				'widget_item_title_line_before'     => '',
					'widget_item_title_before'        => '',
					'widget_item_title_after'         => '',
					'widget_item_title_single_before' => '',
					'widget_item_title_single_after'  => '',
					'widget_lnk_type'                 => '#',

					'widget_item_title_before_title'        => '',
					'widget_item_title_after_title'         => '',
					'widget_item_title_single_before_title' => '',
					'widget_item_title_single_after_title'  => '',
					'widget_item_title_format'              => 'htmlbody',
					'widget_item_title_link_type'           => '#',
					'widget_item_title_link_class'          => '#',
					'widget_item_title_max_length'          => '',
					'widget_item_title_target_blog'         => '',
					'widget_item_title_nav_target'          => NULL,
					'widget_item_title_title_field'         => 'title', // '#' for custom title
					'widget_item_title_custom_title'        => $Item->title,
				'widget_item_title_line_after'            => '</div>',

				'widget_item_title_show_edit_intro'       => false,
			), $params );

		$this->init_display( $params );

		if( $this->params['widget_item_title_display'] )
		{
			echo $this->disp_params['block_start'];
			$this->disp_title();
			echo $this->disp_params['block_body_start'];

			echo $params['widget_item_title_line_before'];

			if( $disp == 'single' || $disp == 'page' )
			{
				$title_before       = $params['widget_item_title_single_before'];
				$title_after        = $params['widget_item_title_single_after'];
				$title_before_title = $params['widget_item_title_single_before_title'];
				$title_after_title  = $params['widget_item_title_single_after_title'];
			}
			else
			{
				$title_before       = $params['widget_item_title_before'];
				$title_after        = $params['widget_item_title_after'];
				$title_before_title = $params['widget_item_title_before_title'];
				$title_after_title  = $params['widget_item_title_after_title'];
			}

			// POST TITLE:
			$Item->title( array(
					'before'          => $title_before,
					'after'           => $title_after,
					'before_title'    => $title_before_title,
					'after_title'     => $title_after_title,
					'format'          => $params['widget_item_title_format'],
					'link_type'       => $params['widget_item_title_link_type'],
					'link_class'      => $params['widget_item_title_link_class'],
					'max_length'      => $params['widget_item_title_max_length'],
					'target_blog'     => $params['widget_item_title_target_blog'],
					'nav_target'      => $params['widget_item_title_nav_target'],
					'title_field'     => $params['widget_item_title_title_field'],
					'custom_title'    => $params['widget_item_title_custom_title'],
				) );

			// EDIT LINK:
			if( $Item->is_intro() && $params['widget_item_title_show_edit_intro'] )
			{ // Display edit link only for intro posts, because for all other posts the link is displayed on the info line.
				$Item->edit_link( array(
							'before' => '<div class="'.button_class( 'group' ).'">',
							'after'  => '</div>',
							'text'   => $Item->is_intro() ? get_icon( 'edit' ).' '.T_('Edit Intro') : '#',
							'class'  => button_class( 'text' ),
						) );
			}

			echo $params['widget_item_title_line_after'];

			echo $this->disp_params['block_body_end'];
			echo $this->disp_params['block_end'];

			return true;
		}

		return false;
	}
}

?>