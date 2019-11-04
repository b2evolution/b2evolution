<?php
/**
 * This file implements the Param Switcher Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/widgets/_generic_menu_link.widget.php', 'generic_menu_link_Widget' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class param_switcher_Widget extends generic_menu_link_Widget
{
	var $icon = 'refresh';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'param_switcher' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'param-switcher-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Param Switcher');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 *
	 * @return string Short description
	 */
	function get_short_desc()
	{
		return format_to_output( $this->get_name().': '.$this->get_param( 'param_code' ) );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display buttons to switch between params.');
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
				'param_code' => array(
					'label' => T_('Param code'),
					'size' => 60,
				),
				'display_mode' => array(
					'type' => 'select',
					'label' => T_('Display as'),
					'options' => array(
							'auto'    => T_('Auto'),
							'list'    => T_('List'),
							'buttons' => T_('Buttons'),
						),
					'note' => sprintf( T_('Auto is based on the %s param.'), '<code>inlist</code>' ),
					'defaultvalue' => 'auto',
				),
				'buttons' => array(
					'type' => 'array',
					'label' => T_('Buttons'),
					'entries' => array(
						'value' => array(
							'label' => T_('Value'),
							'valid_pattern' => '/^[a-z0-9_\-]+$/',
							'defaultvalue' => '',
						),
						'text' => array(
							'label' => T_('Text'),
							'defaultvalue' => '',
						),
					)
				),
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{ // Disable "allow blockcache" because this widget uses the selected items
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
		$this->init_display( $params );

		$buttons = $this->get_param( 'buttons' );

		if( empty( $buttons ) )
		{	// No buttons to display:
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because no buttons to display.' );
			return false;
		}

		echo $this->disp_params['block_start'];

		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		// Get current param value and memorize it for regenerating url:
		$param_value = param( $this->get_param( 'param_code' ), 'string', '', true );

		echo $this->disp_params['button_group_start'];

		foreach( $buttons as $button )
		{	// Display button:
			echo $this->get_layout_menu_link(
				// URL to filter current page:
				regenerate_url( $this->get_param( 'param_code' ).',redir', $this->get_param( 'param_code' ).'='.$button['value'].'&amp;redir=no' ),
				// Title of the button:
				$button['text'],
				// Mark the button as active:
				( $param_value == $button['value'] ) );
		}

		echo $this->disp_params['button_group_end'];

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		return true;
	}
}

?>