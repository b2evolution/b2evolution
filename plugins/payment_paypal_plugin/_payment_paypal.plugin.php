<?php
/**
 * This file implements the Payment PayPal plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}
 *
 * @author fplanque: Francois PLANQUE.
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


require __DIR__.'/vendor/autoload.php';
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;

/**
 * @package plugins
 */
class payment_paypal_plugin extends Plugin
{
	var $name;
	var $code = 'payment_paypal';
	var $priority = 100;
	var $version = '7.0.2';
	var $group = 'payment';
	var $subgroup = 'content';
	var $widget_icon = 'money';
	var $number_of_installs = 1;


	function PluginInit( & $params )
	{
		$this->name = T_('PayPal payment processor');
		$this->short_desc = T_('PayPal payment processor');
		$this->long_desc = T_('PayPal payment processor');

		$this->payment_processor = 'PayPal';
		//$this->api_version = '2019-05-16';
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
			'client_id' => array(
				'label' => T_('Client ID'),
				'size' => 80,
				'note' => sprintf( T_('Visit the <a %s>PayPal dashboard</a> to get your Client ID'),
						'href="https://developer.paypal.com/developer/applications/" target="_blank"' ),
				),
			'secret_key' => array(
				'label' => T_('Secret key'),
				'size' => 80,
				'type' => 'password',
				'note' => sprintf( T_('Visit the <a %s>PayPal dashboard</a> to get your Secret key'),
						'href="https://developer.paypal.com/developer/applications/" target="_blank"' ),
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
			'page_success' => array(
				'label' => T_('Page for success'),
				'size' => 120,
				'note' => T_('Enter slug of the page to display in case of successful checkout.').' '.T_('Leave empty to return to cart.'),
			),
			'page_cancel' => array(
				'label' => T_('Page for cancelation'),
				'size' => 120,
				'note' => T_('Enter slug of the page to display in case checkout is cancelled.').' '.T_('Leave empty to return to cart.'),
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
		global $Blog, $current_User, $rsc_url;

		$this->init_widget_params( $params );

		if( empty( $Blog ) )
		{	// Don't display this widget when no current Collection:
			$this->display_widget_debug_message( 'Plugin widget "'.$this->name.'" is hidden because there is no Collection.' );
			return false;
		}

		if( $this->Settings->get( 'client_id' ) == '' )
		{	// Don't display this widget with wrong publishable API key:
			$this->display_widget_error_message( 'Plugin widget "'.$this->name.'" cannot be used because "Client ID" is empty.' );
			return false;
		}

		if( $this->Settings->get( 'secret_key' ) == '' )
		{	// Don't display this widget with wrong secret API key:
			$this->display_widget_error_message( 'Plugin widget "'.$this->name.'" cannot be used because "Secret key" is empty.' );
			return false;
		}

		$Cart = & get_Cart();

		if( in_array( $Cart->get_payment_field( 'status', $this->payment_processor ), array( 'pending', 'success' ) ) )
		{	// Display error message if order was already paid before:
			$this->display_widget_error_message( T_('This order has already been paid for.') );
			return false;
		}

		$cart_items_data = $Cart->get_items_data();

		if( empty( $cart_items_data ) )
		{	// Don't display this widget without items in shopping cart:
			$this->display_widget_debug_message( 'Plugin widget "'.$this->name.'" is hidden because no items in shopping cart.' );
			return false;
		}

		echo $this->widget_params['block_start'];

		$this->display_widget_title();

		echo $this->widget_params['block_body_start'];

		// Render PayPal checkout button here by JS below::
		echo '<div id="evo_paypal_button_container_'.$this->widget_params['wi_ID'].'"></div>';

		// Render the PayPal button:
		echo '<script>
		jQuery( document ).on( "ready", function()
		{
			var js_obj = document.createElement( "script" );
			js_obj.src = "https://www.paypal.com/sdk/js?client-id='.$this->Settings->get( 'client_id' ).'&currency='.$Cart->get_currency_code().'";
			document.body.appendChild( js_obj );

			js_obj.onload = function()
			{
				paypal.Buttons(
				{
					style: { layout: "horizontal" },

					// Set up the transaction:
					createOrder: function( data, actions )
					{
						return fetch( "'.$this->get_htsrv_url( 'setup_transaction', array(), '&' ).'", {
							method: "post",
						} ).then(function( res ) {
							return res.json();
						} ).then(function( data ) {
							return data.orderID;
						} );
					},

					// Finalize the transaction:
					onApprove: function(data, actions)
					{
						return actions.order.capture().then( function( details )
						{	// Call your server to save the transaction
							return fetch( "'.$this->get_htsrv_url( 'complete_transaction', array(), '&' ).'&order_id=" + data.orderID,
							{
								method: "post",
								headers: { "content-type": "application/json" },
								body: JSON.stringify({
									orderID: data.orderID
								} )
							} );
						} );
					}
				}).render( "#evo_paypal_button_container_'.$this->widget_params['wi_ID'].'" );
			}
		} );
		</script>';

		echo $this->widget_params['block_body_end'];

		echo $this->widget_params['block_end'];

		return true;
	}


	/**
	 * Get URL to redirect after success or failed checkout
	 *
	 * @param string Setting name: 'page_succes', 'page_cancel'
	 * @param array Additional parameters: 'blog' - to use when global collection is not defined yet
	 * @return string
	 */
	function get_result_page_url( $setting_name, $params = array() )
	{
		$setting_value = $this->get_widget_setting( $setting_name, $params );
		if( ! empty( $setting_value ) )
		{
			if( $ItemCache = & get_ItemCache() &&
			    $page_Item = & $ItemCache->get_by_urltitle( $setting_value, false, false ) )
			{	// Use Item permanent URL:
				$result_page_url = $page_Item->get_permanent_url( '', '', '&' );
			}
			elseif( $ChapterCache = & get_ChapterCache() &&
			        $page_Chapter = & $ChapterCache->get_by_urlname( $setting_value, false, false ) )
			{	// Use Chapter permanent URL:
				$result_page_url = $page_Chapter->get_permanent_url( NULL, NULL, 1, NULL, '&' );
			}
		}

		if( empty( $result_page_url ) )
		{	// Use shopping cart page URL by default:
			global $Blog, $baseurl;
			if( ! isset( $Blog ) && isset( $params['blog'] ) )
			{	// Try to set collection from params:
				$BlogCache = & get_BlogCache();
				$Blog = $BlogCache->get_by_ID( $params['blog'], false, false );
			}
			$result_page_url = empty( $Blog ) ? $baseurl : $Blog->get( 'carturl' );
		}

		return $result_page_url;
	}


	/**
	 * Get keys for block/widget caching
	 *
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @param integer Widget ID
	 * @return array of keys this widget depends on
	 */
	function get_widget_cache_keys( $widget_ID = 0 )
	{
		global $Collection, $Blog, $current_User, $Session;

		$cache_keys = array(
				'wi_ID'       => $widget_ID, // Have the widget settings changed ?
				'set_coll_ID' => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
				// NOTE: The key 'user_ID' is used to invalidate cache when current User was updated,
				//       for example, user was in group "VIP client" and then he was moved to "Problem client"
				//       which cannot see/buy items/products with status "Members", so in such case at the user updating moment
				//       we should invalidate widget cache in order to hide some items/products for the updated user.
				'user_ID'     => ( is_logged_in() ? $current_User->ID : 0 ), // Has the current User changed?
				'cart'        => $Session->ID, // Has the cart updated for current session?
			);

		// Get items form the current cart:
		$Cart = & get_Cart();
		$cart_items_data = $Cart->get_items_data();

		// Add 1 cache key for each item that is in shopping card, in order to detect changes on each one:
		foreach( $cart_items_data as $cart_item_ID => $cart_item_data )
		{
			// 1 is a dummy value, only the key name is really important
			$cache_keys['item_'.$cart_item_ID] = 1;
		}

		return $cache_keys;
	}


	/**
	 * Return the list of Htsrv (HTTP-Services) provided by the plugin.
	 *
	 * This implements the plugin interface for the list of methods that are valid to
	 * get called through htsrv/call_plugin.php.
	 *
	 * @return array
	 */
	function GetHtsrvMethods()
	{
		return array( 'setup_transaction', 'complete_transaction', 'success', 'cancel' );
	}


	/**
	 * Plugin action to set up PayPal transaction
	 *
	 * @param array Params
	 */
	function htsrv_setup_transaction( $params )
	{
		$Cart = & get_Cart();

		// Check order status:
		if( in_array( $Cart->get_payment_field( 'status', $this->payment_processor ), array( 'pending', 'success' ) ) )
		{	// Display error message if order was already paid before:
			debug_die( T_('This order has already been paid for.') );
			// Exit here.
		}

		// Construct a request object and set desired parameters
		// Here, OrdersCreateRequest() creates a POST request to /v2/checkout/orders
		$request = new OrdersCreateRequest();
		$request->headers["prefer"] = "return=representation";
		$request->body = array(
			'intent' => 'CAPTURE',
			'application_context' => array(
				'return_url' => $this->get_htsrv_url( 'success', array(), '&' ),
				'cancel_url' => $this->get_htsrv_url( 'cancel', array(), '&' )
			),
			'purchase_units' => array(
				0 => array(
					'amount' => array(
						'currency_code' => 'USD',
						'value' => '220.00'
					)
				)
			)
		);

		try
		{	// Call API with your client and get a response for your call:
			$client = $this->get_paypal_client();
			$response = $client->execute( $request );

			// If call returns body in response, you can get the deserialized version from the result attribute of the response
			//print_r($response);
			//return $response;
			//echo evo_json_encode( $response->result );
		}
		catch( HttpException $ex )
		{
			echo $ex->statusCode;
			print_r( $ex->getMessage() );
		}
	}


	/**
	 * Plugin action to complete PayPal transaction
	 *
	 * @param array Params
	 */
	function htsrv_complete_transaction( $params )
	{
		$Cart = & get_Cart();

		// Check order status:
		if( in_array( $Cart->get_payment_field( 'status', $this->payment_processor ), array( 'pending', 'success' ) ) )
		{	// Display error message if order was already paid before:
			debug_die( T_('This order has already been paid for.') );
			// Exit here.
		}

		// Here, OrdersCaptureRequest() creates a POST request to /v2/checkout/orders
		// $response->result->id gives the orderId of the order created above
		$request = new OrdersCaptureRequest( param( 'order_id', 'string' ) );

		try
		{	// Call API with your client and get a response for your call:
			$client = $this->get_paypal_client();
			$response = $client->execute($request);

			// If call returns body in response, you can get the deserialized version from the result attribute of the response
			//print_r($response);
		}
		catch( HttpException $ex )
		{
			echo $ex->statusCode;
			print_r( $ex->getMessage() );
		}
	}


	/**
	 * Plugin action after success payment
	 *
	 * @param array Params
	 */
	function htsrv_success( $params )
	{
		global $Messages;

		$Cart = & get_Cart();

		if( $Cart->get_payment_field( 'status', $this->payment_processor ) == 'new' )
		{	// Update payment status to "pending" only when it is a "new":
			$Cart->save_payment( $this->payment_processor, array(
				'status' => 'pending',
			) );
		}

		// Clear cart after successful payment:
		// TODO: The clearing of shopping cart is temporary disabled for testing,
		//       probably it will be moved to $this->htsrv_webhook().
		//$Cart->clear();

		$Messages->add( T_('Payment has been done successfully.'), 'success' );

		header_redirect( $this->get_result_page_url( 'page_success', $params ) );
	}


	/**
	 * Plugin action after success payment
	 *
	 * @param array Params
	 */
	function htsrv_cancel( $params )
	{
		global $Messages;

		$Cart = & get_Cart();

		// Update status of the order payment:
		$Cart->save_payment( $this->payment_processor, array(
			'status' => 'cancelled',
		) );

		$Messages->add( T_('Payment has not been finished, please try it again.'), 'error' );

		header_redirect( $this->get_result_page_url( 'page_cancel', $params ) );
	}


	/**
	 * Get PayPal Client
	 *
	 * @return object
	 */
	function get_paypal_client()
	{
		$environment = new SandBoxEnvironment( $this->Settings->get( 'client_id' ), $this->Settings->get( 'secret_key' ) );
		$client = new PayPalHttpClient( $environment );

		return $client;
	}
}
?>