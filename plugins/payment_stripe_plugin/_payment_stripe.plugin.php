<?php
/**
 * This file implements the HTML 5 VideoJS Player plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @author fplanque: Francois PLANQUE.
 *
 * @package plugins
 * @version $Id: _html5_videojs.plugin.php 198 2011-11-05 21:34:08Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @package plugins
 */
class payment_stripe_plugin extends Plugin
{
	var $name;
	var $code = 'payment_stripe';
	var $priority = 100;
	var $version = '7.0.1';
	var $group = 'widget';
	var $subgroup = 'content';
	var $widget_icon = 'money';
	var $number_of_installs = 1;


	function PluginInit( & $params )
	{
		$this->name = T_('Stripe payment processor');
		$this->short_desc = T_('Stripe payment processor');
		$this->long_desc = T_('Stripe payment processor');
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
		return array(
			'publish_api_key' => array(
				'label' => T_('Publishable API key'),
				'size' => 50,
				'defaultvalue' => '',
				'note' => sprintf( T_('Visit the <a %s>Stripe dashboard</a> to get your API keys'),
						'href="https://dashboard.stripe.com/account/apikeys" target="_blank"' ),
				),
			'secret_api_key' => array(
				'label' => T_('Secret API key'),
				'size' => 50,
				'type' => 'password',
				'defaultvalue' => '',
				'note' => sprintf( T_('Visit the <a %s>Stripe dashboard</a> to get your API keys'),
						'href="https://dashboard.stripe.com/account/apikeys" target="_blank"' ),
				),
			);
	}


	/**
	 * Get definitions for widget specific editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_widget_param_definitions( $params )
	{
		return array(
			'title' => array(
				'label' => T_('Title'),
				'size' => 60,
				'defaultvalue' => '',
			),
			'button_class' => array(
				'label' => T_('Button class'),
				'size' => 60,
				'defaultvalue' => 'btn btn-success',
			),
			'button_text' => array(
				'label' => T_('Button text'),
				'size' => 60,
				'defaultvalue' => T_('Checkout'),
			),
		);
	}


	/**
	 * Event handler: SkinTag
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display?
	 */
	function SkinTag( & $params )
	{
		global $Blog;

		$this->init_widget_params( $params );

		if( empty( $Blog ) )
		{	// Don't display this widget when no current Collection:
			$this->display_widget_debug_message( 'Plugin widget "'.$this->name.'" is hidden because there is no Collection.' );
			return false;
		}

		if( empty( $this->Settings->get( 'publish_api_key' ) ) ||
		    empty( $this->Settings->get( 'secret_api_key' ) ) )
		{	// Don't display this widget when no current Collection:
			$this->display_widget_debug_message( 'Plugin widget "'.$this->name.'" is hidden because API keys are not provided.' );
			return false;
		}

		require_once( __DIR__.'/stripe-php/init.php' );

		\Stripe\Stripe::setApiKey( $this->Settings->get( 'secret_api_key' ) );

		// TODO: set items from shopping cart and proper urls:
		$session = \Stripe\Checkout\Session::create([
			'payment_method_types' => ['card'],
			'line_items' => [[
				'name' => 'T-shirt',
				'description' => 'Comfortable cotton t-shirt',
				'images' => ['https://example.com/t-shirt.png'],
				'amount' => 500,
				'currency' => 'gbp',
				'quantity' => 1,
			]],
			'success_url' => $Blog->get( 'carturl' ),
			'cancel_url' => $Blog->get( 'carturl' ),
		]);

		echo '<script src="https://js.stripe.com/v3/"></script>';
		echo '<script>
			var stripe = Stripe( "'.$this->Settings->get( 'publish_api_key' ).'" );
			jQuery( document ).on( "click", ".evo_widget.widget_plugin_payment_stripe", function()
			{
				stripe.redirectToCheckout({
					// Make the id field from the Checkout Session creation API response
					// available to this file, so you can provide it as parameter here
					// instead of the {{CHECKOUT_SESSION_ID}} placeholder.
					sessionId: "'.$session['id'].'"
				}).then(function (result) {
					// If `redirectToCheckout` fails due to a browser or network
					// error, display the localized error message to your customer
					// using `result.error.message`.
				});
			} );
		</script>';

		echo $this->widget_params['block_start'];

		$widget_title = $this->get_widget_setting( 'title' );
		if( ! empty( $widget_title ) )
		{	// We want to display a title for the widget block:
			echo $this->widget_params['block_title_start'];
			echo $widget_title;
			echo $this->widget_params['block_title_end'];
		}

		echo $this->widget_params['block_body_start'];

		echo '<button class="'.$this->get_widget_setting( 'button_class' ).'">'.$this->get_widget_setting( 'button_text' ).'</button>';

		echo $this->widget_params['block_body_end'];

		echo $this->widget_params['block_end'];

		return true;
		
	}

}
?>