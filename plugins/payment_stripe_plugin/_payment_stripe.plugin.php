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
	var $group = 'payment';
	var $subgroup = 'content';
	var $widget_icon = 'money';
	var $number_of_installs = 1;


	function PluginInit( & $params )
	{
		$this->name = T_('Stripe payment processor');
		$this->short_desc = T_('Stripe payment processor');
		$this->long_desc = T_('Stripe payment processor');

		$this->payment_processor = 'Stripe';
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
				'size' => 80,
				'note' => sprintf( T_('Visit the <a %s>Stripe dashboard</a> to get your API keys'),
						'href="https://dashboard.stripe.com/account/apikeys" target="_blank"' ),
				),
			'secret_api_key' => array(
				'label' => T_('Secret API key'),
				'size' => 80,
				'type' => 'password',
				'note' => sprintf( T_('Visit the <a %s>Stripe dashboard</a> to get your API keys'),
						'href="https://dashboard.stripe.com/account/apikeys" target="_blank"' ),
				),
			'webhook_url' => array(
				'label' => T_('Webhook url'),
				'type' => 'info',
				'info' => '<code>'.$this->get_htsrv_url( 'webhook', array(), '&amp;', true ).'</code>',
				),
			'webhook_key' => array(
				'label' => T_('Webhook signing secret key'),
				'size' => 80,
				'type' => 'password',
				'note' => sprintf( T_('Visit the <a %s>Stripe webhooks endpoints</a> to get your signing secret key'),
						'href="https://dashboard.stripe.com/test/webhooks" target="_blank"' ),
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
				'defaultvalue' => 'btn btn-lg btn-success',
			),
			'button_text' => array(
				'label' => T_('Button text'),
				'size' => 60,
				'defaultvalue' => T_('Checkout'),
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
		global $Blog, $current_User;

		$this->init_widget_params( $params );

		if( empty( $Blog ) )
		{	// Don't display this widget when no current Collection:
			$this->display_widget_debug_message( 'Plugin widget "'.$this->name.'" is hidden because there is no Collection.' );
			return false;
		}

		if( substr( $this->Settings->get( 'publish_api_key' ), 0, 3 ) != 'pk_' )
		{	// Don't display this widget with wrong publishable API key:
			$this->display_widget_error_message( 'Plugin widget "'.$this->name.'" cannot be used because "Publishable API key" must start with <code>pk_</code>.' );
			return false;
		}

		if( substr( $this->Settings->get( 'secret_api_key' ), 0, 3 ) != 'sk_' )
		{	// Don't display this widget with wrong secret API key:
			$this->display_widget_error_message( 'Plugin widget "'.$this->name.'" cannot be used because "Secret API key" must start with <code>sk_</code>.' );
			return false;
		}

		if( substr( $this->Settings->get( 'webhook_key' ), 0, 6 ) != 'whsec_' )
		{	// Don't display this widget with wrong secret API key:
			$this->display_widget_error_message( 'Plugin widget "'.$this->name.'" cannot be used because "Webhook signing secret key" must start with <code>whsec_</code>.' );
			return false;
		}

		$Cart = & get_Cart();

		if( in_array( $Cart->get_payment_field( 'status', $this->payment_processor ), array( 'pending', 'success' ) ) )
		{	// Display error message if order was already paid before:
			$this->display_widget_error_message( T_('This order has already been paid for.') );
			return false;
		}

		$cart_items = $Cart->get_items();

		if( empty( $cart_items ) )
		{	// Don't display this widget without items in shopping cart:
			$this->display_widget_debug_message( 'Plugin widget "'.$this->name.'" is hidden because no items in shopping cart.' );
			return false;
		}

		echo $this->widget_params['block_start'];

		$this->display_widget_title();

		echo $this->widget_params['block_body_start'];

		$checkout_url = $this->get_htsrv_url( 'checkout', array(
				'blog'  => $Blog->ID,
				// Set widget ID in order to know what settings to use after redirect to success or cancel URLs:
				'wi_ID' => $this->widget_params['wi_ID'],
			) );
		echo '<a href="'.$checkout_url.'" class="'.$this->get_widget_setting( 'button_class' ).'">'.$this->get_widget_setting( 'button_text' ).'</a>';

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
		$cart_items = $Cart->get_items();

		// Add 1 cache key for each item that is in shopping card, in order to detect changes on each one:
		foreach( $cart_items as $cart_item_ID => $cart_Item )
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
		return array( 'checkout', 'success', 'cancel', 'webhook' );
	}


	/**
	 * Plugin action on click "Checkout" button before redirect to Stripe payment page
	 *
	 * @param array Params
	 */
	function htsrv_checkout( $params )
	{
		global $Messages, $current_User, $Session;

		$Cart = & get_Cart();

		// Check order status:
		if( in_array( $Cart->get_payment_field( 'status', $this->payment_processor ), array( 'pending', 'success' ) ) )
		{	// Display error message if order was already paid before:
			debug_die( T_('This order has already been paid for.') );
			// Exit here.
		}

		require_once( __DIR__.'/stripe-php/init.php' );
		\Stripe\Stripe::setApiKey( $this->Settings->get( 'secret_api_key' ) );

		try
		{	// Try to get a session for Stripe payment:
			$session_data = [
				'billing_address_collection' => 'required',
				'payment_method_types'       => ['card'],
				'line_items'                 => [],
				'success_url'                => $this->get_htsrv_url( 'success', $params, '&', true ),
				'cancel_url'                 => $this->get_htsrv_url( 'cancel', $params, '&', true ),
				'client_reference_id'        => $Session->ID,
			];

			if( is_logged_in() )
			{	// Use email address of current logged-in User:
				$session_data['customer_email'] = $current_User->get( 'email' );
			}

			$cart_items = $Cart->get_items();
			foreach( $cart_items as $cart_item_ID => $cart_Item )
			{	// Set all items from shopping cart:
				$session_data['line_items'][] = [
					'name'     => $Cart->get_title( $cart_item_ID, array( 'link_type' => 'none' ) ),
					'images'   => $Cart->get_image_urls( $cart_item_ID, array( 'image_size' => 'original' ) ),
					'amount'   => intval( $Cart->get_unit_price( $cart_item_ID ) * 100 ),
					'currency' => $Cart->get_currency_code( $cart_item_ID ),
					'quantity' => $Cart->get_quantity( $cart_item_ID ),
				];
			}

			// Request session:
			$session = \Stripe\Checkout\Session::create( $session_data );
		}
		catch( Exception $ex )
		{	// Display unexpected error:
			$Messages->add( 'Plugin widget "'.$this->name.'" cannot be used because of error "'.$ex->getMessage().'".', 'error' );
			// Redirect back:
			header_redirect();
			// Exit here.
		}

		// Save the payment is executing by Stripe processor:
		$Cart->save_payment( $this->payment_processor, array(
			'status'          => 'new',
			'proc_session_ID' => $session['id'],
		) );

		// Redirect to pay order:
		echo '<script src="https://js.stripe.com/v3/"></script>';
		echo '<script>
			var stripe = Stripe( "'.$this->Settings->get( 'publish_api_key' ).'" );
			stripe.redirectToCheckout({
				sessionId: "'.$session['id'].'",
			}).then(function (result) {
				alert( result.error.message );
			});
		</script>';
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
	 * Plugin action to listen webhook from Stripe server
	 *
	 * @param array Params
	 */
	function htsrv_webhook( $params )
	{
		require_once( __DIR__.'/stripe-php/init.php' );
		\Stripe\Stripe::setApiKey( $this->Settings->get( 'secret_api_key' ) );

		$payload = @file_get_contents( 'php://input' );

		try
		{
			$event = \Stripe\Webhook::constructEvent( $payload, $_SERVER['HTTP_STRIPE_SIGNATURE'], $this->Settings->get( 'webhook_key' ) );
		}
		catch( \UnexpectedValueException $e )
		{	// Invalid payload
			http_response_code( 400 );
			exit();
		}
		catch( \Stripe\Error\SignatureVerification $e )
		{	// Invalid signature
			http_response_code( 400 );
			exit();
		}

		// Handle the checkout.session.completed event
		if( ! empty( $event ) && $event->type == 'checkout.session.completed' )
		{
			$session = $event->data->object;

			$Cart = & get_Cart();

			// Check if payment exists with requested Session ID(client_reference_id) and status is proper to complete payment:
			$payment_status = $Cart->get_payment_field( 'status', $this->payment_processor, $session->client_reference_id );
			if( ! in_array( $payment_status, array( 'new', 'pending' ) ) )
			{	// Could not find payment in the requested session or payment was already completed or canceled:
				http_response_code( 400 );
				exit();
			}

			// Update payment status to "success" and store payload data into DB:
			$Cart->save_payment( $this->payment_processor, array(
				'status'      => 'success',
				'return_info' => $payload,
			) );
		}

		http_response_code( 200 );
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
}
?>