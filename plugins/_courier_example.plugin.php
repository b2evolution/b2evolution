<?php
/**
 * -----------------------------------------------------------------------------------------
 * This file provides a skeleton to create a new {@link http://b2evolution.net/ b2evolution}
 * plugin quickly.
 * See also:
 *  - {@link http://b2evolution.net/man/creating-plugin}
 *  - {@link http://doc.b2evolution.net/stable/plugins/Plugin.html}
 * (Delete this first paragraph, of course)
 * -----------------------------------------------------------------------------------------
 *
 * This file implements the Foo Plugin for {@link http://b2evolution.net/}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2010 by Your NAME - {@link http://example.com/}.
 *
 * @package plugins
 *
 * @author Your NAME
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * In-store pick-up shipment plugin
 *
 * @package plugins
 */
class courier_example_plugin extends Plugin
{

	var $name;
	var $code = 'ship_example';
	var $priority = 50;
	var $version = '7.0.0';
	var $author = 'b2evolution Group';
	var $group = 'shipment';

	var $default_shipping_costs_per_item = "[USD]1.00\n[EUR]0.60\n";


	/**
	 * Init: This gets called after a plugin has been registered/instantiated.
	 */
	function PluginInit( & $params )
	{
		$this->name = T_('Courier Example');
		$this->short_desc = $this->T_('Simple example of shipment plugin');
	}


	/**
	 * Define the GLOBAL settings of the plugin here. These can then be edited in the backoffice in System > Plugins.
	 *
	 * @param array Associative array of parameters (since v1.9).
	 *    'for_editing': true, if the settings get queried for editing;
	 *                   false, if they get queried for instantiating {@link Plugin::$Settings}.
	 * @return array see {@link Plugin::GetDefaultSettings()}.
	 * The array to be returned should define the names of the settings as keys (max length is 30 chars)
	 * and assign an array with the following keys to them (only 'label' is required):
	 */
	function GetDefaultSettings( & $params )
	{
		return array();
	}


	/**
	 * Define the PER-USER settings of the plugin here. These can then be edited by each user.
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param array Associative array of parameters.
	 *    'for_editing': true, if the settings get queried for editing;
	 *                   false, if they get queried for instantiating
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function GetDefaultUserSettings( & $params )
	{
		return array();
	}


	/**
	 * Param definitions when added as a widget.
	 *
	 * Plugins used as widget need to implement the SkinTag hook.
	 *
	 * @return array
	 */
	function get_widget_param_definitions( $params )
	{
		return array();
	}


	/**
	 * Define here the default collection/blog settings that are to be made available in the backoffice
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$default_params = array_merge( $params, array( 'default_comment_rendering' => 'never' ) );
		$plugin_params = array();

		$plugin_params['coll_shipping_costs_per_item'] = array(
			'label' => $this->T_('Shipping cost per item'),
			'type' => 'textarea',
			'note' => $this->T_('Format: [<code>Currency code</code>] <code>Cost per item</code>'),
			'rows' => 10,
			'cols' => 60,
			'defaultvalue' => $this->default_shipping_costs_per_item,
		);

		return array_merge( parent::get_coll_setting_definitions( $default_params ), $plugin_params );
	}


	/**
	 * We require b2evo 7.0+
	 */
	function GetDependencies()
	{
		return array(
				'requires' => array(
					'app_min' => '7.0-alpha',
				),
			);
	}


	/**
	 * Event handler: Called when building possible shipment options
	 *
	 * @return string Shipment option title
	 */
	function GetShipmentOption()
	{
		return $this->name;
	}


	/**
	 * Event handler: Called when determing shipping cost
	 *
	 * @return array/false Array of currency object and total price, FALSE if shipping cost cannot be determined
	 */
	function GetShippingCost( & $params )
	{
		if( empty( $params['currency_ID'] ) )
		{
			return false;
		}

		$CurrencyCache = & get_CurrencyCache();
		$current_Currency = & $CurrencyCache->get_by_ID( $params['currency_ID'], false, false );

		if( empty( $current_Currency ) )
		{
			return false;
		}

		$costs = $this->parse_shipping_cost();
		$total = 0;
		if( isset( $costs[$current_Currency->code] ) )
		{
			foreach( $params['items'] as $item )
			{
				$total += $item['quantity'] * $costs[$current_Currency->code];
			}
			return $total;
		}

		return false;
	}


	/**
	 * Parse defined shipping costs
	 */
	function parse_shipping_cost()
	{
		global $blog, $Blog;

		$shipping_costs = $this->get_coll_setting( 'coll_shipping_costs_per_item', $Blog );
		preg_match_all( '/\[([A-Z]+)\]\s?([0-9\.]+)/m', $shipping_costs, $matches, PREG_SET_ORDER );
		$r = array();
		foreach( $matches as $match )
		{
			$r[$match[1]] = floatval( $match[2] );
		}

		return $r;
	}
}
?>
