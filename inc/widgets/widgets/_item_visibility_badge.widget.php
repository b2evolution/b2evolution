<?php
/**
 * This file implements the item_visibility_badge Widget class.
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
class item_visibility_badge_Widget extends ComponentWidget
{
	var $icon = 'info';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'item_visibility_badge' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'item-visibility-badge-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Item Visibility Badge');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('Item Visibility Badge') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display the visibility of the item.');
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
		// '<div class="evo_status evo_status__$status$ badge pull-right" data-toggle="tooltip" data-placement="top" title="$tooltip_title$">$status_title$</div>',
		$params = array_merge( array(
				'widget_item_visibility_badge_display'  => true,
				'widget_item_visibility_badge_template' => '<div class="evo_status evo_status__$status$" data-toggle="tooltip" data-placement="top" title="$tooltip_title$">$status_title$</div>',
				'widget_item_visibility_badge_format'   => 'htmlbody'
			), $params );

		$this->init_display( $params );

		if( $params['widget_item_visibility_badge_display'] )
		{
			echo $this->disp_params['block_start'];
			$this->disp_title();
			echo $this->disp_params['block_body_start'];

			$Item->format_status( array(
					'template' => $params['widget_item_visibility_badge_template'],
					'format'   => $params['widget_item_visibility_badge_format'],
				) );

			echo $this->disp_params['block_body_end'];
			echo $this->disp_params['block_end'];

			return true;
		}

		return false;
	}
}

?>