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

		// Get current param value and memorize it for regenerating url:
		$param_value = param( $this->get_param( 'param_code' ), 'string', '', true );

		echo $this->disp_params['button_group_start'];

		$button_is_active_by_default = false;
		foreach( $buttons as $button )
		{	// Display button:
			if( $param_value === $button['value'] )
			{	// Active button by current param value:
				$button_is_active = true;
			}
			elseif( ! $button_is_active_by_default &&
			        $param_value === '' &&
			        $Item->get_switchable_param( $this->get_param( 'param_code' ) ) == $button['value'] )
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
				( $this->get_param( 'allow_switch_url' ) ? regenerate_url( $this->get_param( 'param_code' ).',redir', $this->get_param( 'param_code' ).'='.$button['value'].'&amp;redir=no' ) : '#' ),
				// Title of the button:
				$button['text'],
				// Mark the button as active:
				$button_is_active,
				// Link template:
				'<a href="$link_url$" class="$link_class$"'.$link_js_attrs.'>$link_text$</a>' );
		}

		echo $this->disp_params['button_group_end'];

		echo $this->disp_params['block_body_end'];

		if( $this->get_param( 'allow_switch_js' ) )
		{	// Initialize JS to allow switching by JavaScript:
		?>
<script>
jQuery( 'a[data-param-switcher=<?php echo $this->ID; ?>]' ).click( function()
{
	var default_params = {};
<?php
	// Load switchable params in order to add all default values in the current URL:
	$Item->load_switchable_params();
	if( ! empty( $Item->switchable_params ) )
	{
		foreach( $Item->switchable_params as $switchable_param_name => $switchable_param_default_value )
		{
			echo "\t".'default_params.'.$switchable_param_name.' = \''.$switchable_param_default_value.'\';'."\r\n";
		}
	}
?>

	// Remove previous value from the URL:
	var regexp = new RegExp( '([\?&])((' + jQuery( this ).data( 'code' ) + '|redir)=[^&]*(&|$))+', 'g' );
	var url = location.href.replace( regexp, '$1' );
	url = url.replace( /[\?&]$/, '' );
	// Add param code with value of the clicked button:
	url += ( url.indexOf( '?' ) === -1 ? '?' : '&' );
	url += jQuery( this ).data( 'code' ) + '=' + jQuery( this ).data( 'value' );
	for( default_param in default_params )
	{
		regexp = new RegExp( '[\?&]' + default_param + '=', 'g' );
		if( ! url.match( regexp ) )
		{	// Append defaul param if it is not found in the current URL:
			url += '&' + default_param + '=' + default_params[ default_param ];
		}
	}
	url += '&redir=no';

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

		global $evo_widget_param_switcher_js_initied;
		if( empty( $evo_widget_param_switcher_js_initied ) )
		{	// Initialize JS to allow switching by JavaScript once:
		?>
<script>
// Modifications to listen event when URL in browser address bar is changed:
history.pushState = ( f => function pushState(){
	var ret = f.apply(this, arguments);
	window.dispatchEvent(new Event('pushstate'));
	window.dispatchEvent(new Event('locationchange'));
	return ret;
})(history.pushState);

window.addEventListener( 'locationchange', function()
{	// Show/Hide custom fields by condition depending on current URL in browser address:
	var custom_fields = jQuery( '[data-display-condition]' );
	if( custom_fields.length == 0 )
	{	// No custom fields with display conditions:
		return false;
	}

	function get_url_params( url, multiple_values )
	{
		url = url.replace( /^.+\?/, '' ).split( '&' );
		var params = [];
		url.forEach( function( url_param )
		{
			url_param = url_param.split( '=' );
			params[ url_param[0] ] = multiple_values ? url_param[1].split( '|' ) : url_param[1];
		} );

		return params;
	}

	// Get params of the current URL:
	var url_params = get_url_params( location.href, false );

	// Show all custom fields by default:
	custom_fields.show();

	custom_fields.each( function()
	{	// Check each custom fields by display condition:
		var conditions = get_url_params( jQuery( this ).data( 'display-condition' ), true );
		for( var cond_param in conditions )
		{
			var url_param_value = ( typeof( url_params[ cond_param ] ) == 'undefined' ? '' : url_params[ cond_param ] );
			if( ( url_param_value === '' && conditions[ cond_param ].indexOf( '' ) === -1 ) ||
			    conditions[ cond_param ].indexOf( url_param_value ) === -1 )
			{	// Hide the custom field if at least one condition is not equal:
				jQuery( this ).hide();
				break;
			}
		}
	} );
} );
</script>
		<?php
			$evo_widget_param_switcher_js_initied = true;
		}

		echo $this->disp_params['block_end'];

		return true;
	}
}

?>