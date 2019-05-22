<?php
/**
 * This file implements the Widget class to display a shipping method list.
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
class display_shipping_method_Widget extends ComponentWidget
{
	var $icon = 'shopping-cart';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'display_shipping_method' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'shipping-method-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Shipping method');
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
		return T_('Display list of the available shipping methods.');
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
					'defaultvalue' => T_('Delivery options'),
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
		global $Collection, $Blog, $Plugins;

		$this->init_display( $params );

		$this->disp_params = array_merge( array(
				'shipping_method_empty'            => '<p>'.T_('No shipment plugins found.').'</p>',
				'shipping_method_list_start'       => '<div class="evo_shipping_options">',
				'shipping_method_list_end'         => '</div>',
				'shipping_method_total_cost_start' => '',
				'shipping_method_total_cost_end'   => '',
			), $this->disp_params );

		// Get shipment plugins:
		$shipment_Plugin = get_shipment_plugin();
		$shipment_plugin_ID = $shipment_Plugin->ID;
		$shipment_options = $Plugins->trigger_collect( 'GetShipmentOption' );

		echo $this->disp_params['block_start'];
		$this->disp_title();
		echo $this->disp_params['block_body_start'];

		if( empty( $shipment_options ) )
		{
			echo $this->disp_params['shipping_method_empty'];
		}
		else
		{
			$options = array();
			foreach( $shipment_options as $plugin_ID => $title )
			{
				if( empty( $shipment_plugin_ID ) )
				{
					$shipment_plugin_ID = $plugin_ID;
				}
				$options[] = array( 'value' => $plugin_ID, 'label' => '<span class="evo_shipment_option">'.$title.'</span>' );
			}

			// Display list of shipment plugin options:
			$Form = new Form( get_htsrv_url().'anon_async.php?action=set_shipment_method', 'shipment_method', 'post', 'blockspan' );
			$Form->begin_form();

			$Form->switch_layout( 'linespan' );
			echo $this->disp_params['shipping_method_list_start'];
			$Form->radio_input( 'shipment_plugin_ID', $shipment_plugin_ID, $options, '', array( 'class' => 'evo_shipping_option', 'lines' => true ) );
			echo $this->disp_params['shipping_method_list_end'];
			$Form->switch_layout( NULL );

			// Determine current shipping cost:
			$currency = get_currency();
			$country = get_country();
			$Cart = & get_Cart();
			$cart_items = $Cart->get_items();
			$shipping_cost = 0;

			if( $shipment_Plugin && $currency && $country )
			{
				$params = array(
					'currency_ID' => $currency->ID,
					'country_ID'  => $country->ID,
					'items'       => array(),
				);

				foreach( $cart_items as $cart_item_ID => $cart_Item )
				{
					$params['items'][] = array(
							'item_ID'    => $cart_item_ID,
							'quantity'   => $Cart->get_quantity( $cart_item_ID ),
						);
				}
				$shipping_cost = $shipment_Plugin->GetShippingCost( $params );
				if( $shipping_cost !== false )
				{
					$shipping_cost = '<span class="evo_total_shipping_cost">'.$currency->shortcut.'&nbsp;'.number_format( $shipping_cost, 2 ).'</span>';
				}
				else
				{
					$shipping_cost = get_icon( 'warning_yellow' ).' <span class="text-danger">'.T_('This delivery method is not possible for this order.').'</span>';
				}

			}

			$update_button = '<button type="submit" id="button_update_shipment" name="actionArray[get_shipment_cost]" class="btn btn-primary" style="margin: 0;">'.T_('Update').'</button>';
			echo $this->disp_params['shipping_method_total_cost_start'];
			$Form->info_field( T_('Shipping cost'), $shipping_cost, array( 'class' => 'evo_shipping_cost', 'field_suffix' => '&nbsp;'.$update_button ) );
			$Form->hidden( 'redirect_to', regenerate_url() );
			echo $this->disp_params['shipping_method_total_cost_end'];

			$Form->end_form();
		}

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
		global $Collection, $Blog, $current_User, $Session;

		$cache_keys = array(
				'wi_ID'       => $this->ID, // Have the widget settings changed ?
				'set_coll_ID' => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
				// NOTE: The key 'user_ID' is used to invalidate cache when current User was updated,
				//       for example, user was in group "VIP client" and then he was moved to "Problem client"
				//       which cannot see/buy items/products with status "Members", so in such case at the user updating moment
				//       we should invalidate widget cache in order to hide some items/products for the updated user.
				'user_ID'     => ( is_logged_in() ? $current_User->ID : 0 ), // Has the current User changed?
				'curr_ID'     => $Session->get( 'currency_ID' ), // Has the active currency changed?
				'country_ID'  => $Session->get( 'country_ID' ), // Has the active country changed?
				'cart'        => $Session->ID, // Has the cart updated for current session?
			);

		// Get items form the current cart:
		$Cart = & get_Cart();
		$cart_items = $Cart->get_items();

		// Add 1 cache key for each item that is in shopping card, in order to detect changes on each one:
		foreach( $cart_items as $cart_item_ID => $cart_Item )
		{
			// 1 is a dummy value, only the key name is really important
			$cache_keys['item_'.$cart_item_ID] = 1;
		}

		return $cache_keys;
	}
}
?>