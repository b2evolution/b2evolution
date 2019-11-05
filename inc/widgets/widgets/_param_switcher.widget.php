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
				'allow_switch_js' => array(
					'type' => 'checkbox',
					'label' => T_('Allow Javascript switching (dynamic)'),
					'defaultvalue' => 1,
				),
				'url_start_line' => array(
					'type' => 'begin_line',
					'label' => T_('Allow Standard switching (page reload)'),
				),
					'allow_switch_url' => array(
						'type' => 'checkbox',
						'label' => '',
						'defaultvalue' => 1,
					),
					'url_param_codes' => array(
						'label' => T_('Param codes to keep in the URL').': ',
						'note' => sprintf( T_('Separate by %s. These codes will be merged with auto-detected codes (current widget and switching widgets above this one) but this is required to take into account any switching widgets below this one.'), '<code>,</code>' ),
						'defaultvalue' => '',
					),
				'url_end_line' => array(
					'type' => 'end_line',
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

		if( $this->get_param( 'allow_switch_url' ) )
		{	// Keep additional param codes in the URL:
			$url_param_codes = $this->get_param( 'url_param_codes' );
			if( ! empty( $url_param_codes ) )
			{
				$url_param_codes = explode( ',', $url_param_codes );
				foreach( $url_param_codes as $url_param_code )
				{
					$url_param_code = trim( $url_param_code );
					if( ! empty( $url_param_code ) )
					{	// Memorize additional param to regenerate proper URL below:
						param( $url_param_code, 'string', '', true );
					}
				}
			}
		}

		echo $this->disp_params['button_group_start'];

		foreach( $buttons as $button )
		{	// Display button:
			$link_js_attrs = ( $this->get_param( 'allow_switch_js' )
				? ' data-param-switcher="'.$this->ID.'"'
				 .' data-code="'.format_to_output( $this->get_param( 'param_code' ), 'htmlattr' ).'"'
				 .' data-value="'.format_to_output( $button['value'], 'htmlattr' ).'"'
				: '' );
			echo $this->get_layout_menu_link(
				// URL to filter current page:
				( $this->get_param( 'allow_switch_url' ) ? regenerate_url( $this->get_param( 'param_code' ).',redir', $this->get_param( 'param_code' ).'='.$button['value'].'&amp;redir=no' ) : '#' ),
				// Title of the button:
				$button['text'],
				// Mark the button as active:
				( $param_value == $button['value'] ),
				// Link template:
				'<a href="$link_url$" class="$link_class$"'.$link_js_attrs.'>$link_text$</a>' );
		}

		echo $this->disp_params['button_group_end'];

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		if( $this->get_param( 'allow_switch_js' ) )
		{	// Initialize JS to allow switching by JavaScript:
		?>
<script>
jQuery( 'a[data-param-switcher=<?php echo $this->ID; ?>]' ).click( function()
{
	// Remove previous value from the URL:
	var regexp = new RegExp( '([\?&])((' + jQuery( this ).data( 'code' ) + '|redir)=[^&]*(&|$))+', 'g' );
	var url = location.href.replace( regexp, '$1' );
	url = url.replace( /[\?&]$/, '' );
	// Add param code with value of the clicked button:
	url += ( url.indexOf( '?' ) === -1 ? '?' : '&' );
	url += jQuery( this ).data( 'code' ) + '=' + jQuery( this ).data( 'value' );
	url += '&redir=no'

	// Change URL in browser address bar:
	window.history.pushState( '', '', url );

	// Change active button:
	jQuery( 'a[data-param-switcher=<?php echo $this->ID; ?>]' ).attr( 'class', '<?php echo ( empty( $this->disp_params['widget_link_class'] ) ? $this->disp_params['button_default_class'] : $this->disp_params['widget_link_class'] ); ?>' );
	jQuery( this ).attr( 'class', '<?php echo ( empty( $this->disp_params['widget_active_link_class'] ) ? $this->disp_params['button_selected_class'] : $this->disp_params['widget_active_link_class'] ); ?>' );

	return false;
} );
</script>
		<?php
		}

		return true;
	}
}

?>