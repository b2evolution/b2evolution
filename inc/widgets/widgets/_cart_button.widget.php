<?php
/**
 * This file implements the Widget class to add a product to cart.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
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
class cart_button_Widget extends ComponentWidget
{
	var $icon = 'cart-plus';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'cart_button' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'cart-button-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Add to cart button');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( $this->get_name() );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display a button to add a product to cart.');
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
					'label' => T_('Block title'),
					'note' => T_('Title to display in your skin.'),
					'size' => 40,
				),
				'btn_title' => array(
					'label' => T_('Button title'),
					'size' => 40,
					'defaultvalue' => T_('Add to cart'),
				),
				'btn_class' => array(
					'label' => T_('Button class'),
					'size' => 40,
					'defaultvalue' => 'btn btn-lg btn-success'
				),
				'icon_class' => array(
					'label' => T_('Icon class'),
					'size' => 40,
					'defaultvalue' => 'fa fa-cart-plus'
				),
			), parent::get_param_definitions( $params ) );

		return $r;
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Collection, $Blog, $Item;

		if( empty( $Item ) )
		{	// Don't display the button if no current Item:
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because current Item is not defined.' );
			return false;
		}

		if( ! $Item->can_be_displayed() )
		{	// Don't display the button if current Item cannot be displayed for current User on front-office:
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because current Item is not defined.' );
			return false;
		}

		$this->init_display( $params );

		echo $this->disp_params['block_start'];

		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		// Initialize URL to add a product to cart:
		$add_cart_url = $Blog->get( 'carturl', array( 'url_suffix' => 'action=add&amp;item_ID='.$Item->ID.'&amp;qty=1' ) );

		// Display a buttton to add a product to cart:
		echo '<a href="'.$add_cart_url.'" class="'.format_to_output( $this->disp_params['btn_class'], 'htmlattr' ).'">'
				.'<i class="'.format_to_output( $this->disp_params['icon_class'], 'htmlattr' ).'"></i> '
				.format_to_output( $this->disp_params['btn_title'], 'htmlbody' )
			.'</a>';

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		return true;
	}


	/**
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @return array of keys this widget depends on
	 */
	function get_cache_keys()
	{
		global $Collection, $Blog, $Item;

		return array(
				'wi_ID'       => $this->ID, // Have the widget settings changed ?
				'set_coll_ID' => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
				'item_ID'     => $Item->ID, // Has the Item page changed?
			);
	}
}
?>