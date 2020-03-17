<?php
/**
 * This file implements the Param Switcher Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
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
	function __construct( $db_row = NULL, $type = 'core', $code = 'param_switcher' )
	{
		// Call parent constructor:
		// Note: $code may be different e.g. for widget "Tabbed Items"
		parent::__construct( $db_row, $type, $code );
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
		return T_('Display buttons to switch between params. Useful for Compare Items widget.');
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
				'buttons' => array(
					'type' => 'array',
					'label' => T_('Buttons'),
					'entries' => array(
						'value' => array(
							'label' => T_('Value'),
							'valid_pattern' => '/^[a-z0-9_\-]+$/',
							'defaultvalue' => '',
							'size' => 5,
						),
						'text' => array(
							'label' => T_('Text'),
							'defaultvalue' => '',
							'size' => 10,
						),
					)
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
				'allow_switch_js' => array(
					'type' => 'checkbox',
					'label' => T_('Allow Javascript switching (dynamic)'),
					'defaultvalue' => 1,
				),
				'allow_switch_url' => array(
					'type' => 'checkbox',
					'label' => T_('Allow Standard switching (page reload)'),
					'defaultvalue' => 1,
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
	 * Get advanced definitions for editable params.
	 *
	 * @see Plugin::GetDefaultSettings()
	 *
	 * @return array Advanced params
	 */
	function get_advanced_param_definitions()
	{
		return array(
				'add_redir_no' => array(
					'type' => 'checkbox',
					'label' => sprintf( T_('Add %s'), '<code>&redir=no</code>' ),
					'note' => T_('This is normally not needed, check this only when you have an auto redirect to canonical url.'),
					'defaultvalue' => 0,
				),
			);
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Item;

		$this->init_display( $params );

		if( ! isset( $Item ) ||
		    ! $Item instanceof Item ||
		    ! $Item->get_type_setting( 'allow_switchable' ) ||
		    ! $Item->get_setting( 'switchable' ) )
		{	// No current Item or Item doesn't use a switcher:
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because current Item does not use swicther params.' );
			return false;
		}

		if( $this->get_param( 'param_code' ) == '' )
		{	// Param code must be defined:
			$this->display_error_message( 'Widget "'.$this->get_name().'" cannot be displayed with empty param code.' );
			return false;
		}

		$buttons = $this->get_param( 'buttons' );

		if( empty( $buttons ) )
		{	// No buttons to display:
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because no buttons to display.' );
			return false;
		}

		if( $Item->get_switchable_param( $this->get_param( 'param_code' ) ) === NULL )
		{	// No default value:
			$this->display_error_message( 'Widget "'.$this->get_name().'" is hidden because the param <code>'.$this->get_param( 'param_code' ).'</code> has not been declared/initialized in the Item.' );
			return false;
		}

		echo $this->disp_params['block_start'];

		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		// Display switchable tabs:
		$this->display_switchable_tabs( $buttons, $Item->get_switchable_params() );

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		return true;
	}


	/**
	 * Display switchable tabs
	 *
	 * @param array Tabs: key - tab value, value - tab text/title
	 * @param array Default params: key - param value, value - default param value
	 * @return string Active button value
	 */
	function display_switchable_tabs( $buttons, $defaults = array() )
	{
		// Get current param value and memorize it for regenerating url:
		$param_value = param( $this->get_param( 'param_code' ), 'string', '', true );

		echo $this->disp_params['button_group_start'];

		$button_is_active_by_default = false;
		$active_button_value = NULL;
		foreach( $buttons as $button )
		{	// Display button:
			if( $param_value === $button['value'] )
			{	// Active button by current param value:
				$button_is_active = true;
			}
			elseif( ! $button_is_active_by_default &&
			        $param_value === '' &&
			        isset( $defaults[ $this->get_param( 'param_code' ) ] ) &&
			        $defaults[ $this->get_param( 'param_code' ) ] == $button['value'] )
			{	// Active button by default with empty param:
				$button_is_active = true;
				$button_is_active_by_default = true;
			}
			else
			{	// No active button:
				$button_is_active = false;
			}
			$link_js_attrs = ( $this->get_param( 'allow_switch_js' )
				? ' data-param-switcher="'.$this->ID.'"'
				 .' data-code="'.format_to_output( $this->get_param( 'param_code' ), 'htmlattr' ).'"'
				 .' data-value="'.format_to_output( $button['value'], 'htmlattr' ).'"'
				: '' );
			echo $this->get_layout_menu_link(
				// URL to filter current page:
				( $this->get_param( 'allow_switch_url' )
					? regenerate_url(
						// Exclude params from current URL:
						$this->get_param( 'param_code' ).( $this->get_param( 'add_redir_no' ) ? ',redir' : '' ),
						// Add new param:
						$this->get_param( 'param_code' ).'='.$button['value'].( $this->get_param( 'add_redir_no' ) ? '&amp;redir=no' : '' ) )
					: '#' ),
				// Title of the button:
				$button['text'],
				// Mark the button as active:
				$button_is_active,
				// Link template:
				'<a href="$link_url$" class="$link_class$"'.$link_js_attrs.'>$link_text$</a>' );

			if( $button_is_active )
			{	// Set active button value:
				$active_button_value = $button['value'];
			}
		}

		echo $this->disp_params['button_group_end'];

		if( $this->get_param( 'allow_switch_js' ) )
		{	// Initialize JS to allow switching by JavaScript:
			$switchable_buttons_config = array(
					'selector'      => 'a[data-param-switcher='.$this->ID.']',
					'class_normal'  => empty( $this->disp_params['widget_link_class'] ) ? $this->disp_params['button_default_class'] : $this->disp_params['widget_link_class'],
					'class_active'  => empty( $this->disp_params['widget_active_link_class'] ) ? $this->disp_params['button_selected_class'] : $this->disp_params['widget_active_link_class'],
					'add_redier_no' => $this->get_param( 'add_redir_no' ) ? true : false,
					'defaults'      => $defaults,
				);
			expose_var_to_js( 'param_switcher_'.$this->ID, $switchable_buttons_config, 'evo_init_switchable_buttons_config' );
		}

		return $active_button_value;
	}


	/**
	 * Request all required css and js files for this widget
	 */
	function request_required_files()
	{
		// TODO: This does not get run when the param switcher is inserted into a post/item via shorttag.
		//       Cannot uglify evo_switchable_blocks.js because of the arrow function there.
		if( $this->get_param( 'allow_switch_js' ) )
		{	// Load JS to switch between blocks on change URL in address bar:
			require_js_defer( '#jquery#', 'blog' );
			require_js_defer( 'src/evo_switchable_blocks.js', 'blog' );
		}
	}
}

?>
