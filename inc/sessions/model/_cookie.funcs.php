<?php
/**
 * This file implements cookie functions.
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
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**
 * Get cookie domain depending on current page:
 *     - For back-office the config var $cookie_domain is used
 *     - For front-office it is dynamically generated from collection url
 *
 * @return string Cookie domain
 */
function get_cookie_domain()
{
	global $Collection, $Blog;

	if( is_admin_page() || empty( $Blog ) )
	{	// Use cookie domain of base url from config:
		global $cookie_domain;
		return $cookie_domain;
	}
	else
	{	// Use cookie domain of current collection url:
		return $Blog->get_cookie_domain();
	}
}


/**
 * Get cookie path depending on current page:
 *     - For back-office the config var $cookie_path is used
 *     - For front-office it is dynamically generated from collection url
 *
 * @return string Cookie path
 */
function get_cookie_path()
{
	global $Collection, $Blog;

	if( is_admin_page() || empty( $Blog ) )
	{	// Use cookie path of base url from config:
		global $cookie_path;
		return $cookie_path;
	}
	else
	{	// Use base path of current collection url:
		return $Blog->get_cookie_path();
	}
}


/**
 * Set a cookie to send it by evo_sendcookies()
 *
 * @param string The name of the cookie
 * @param string The value of the cookie
 * @param integer The time the cookie expires
 * @param string DEPRECATED: The path on the server in which the cookie will be available on
 * @param string DEPRECATED: The domain that the cookie is available
 * @param boolean Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client
 * @param boolean When TRUE the cookie will be made accessible only through the HTTP protocol
 */
function evo_setcookie( $name, $value = '', $expire = 0, $dummy = '', $dummy2 = '', $secure = false, $httponly = false )
{
	global $evo_cookies;

	if( ! is_array( $evo_cookies ) )
	{	// Initialize array for cookies only first time:
		$evo_cookies = array();
	}

	// Store cookie in global var:
	$evo_cookies[ $name ] = array(
			'value'    => $value,
			'expire'   => $expire,
			'secure'   => $secure,
			'httponly' => $httponly,
		);
}


/**
 * Send the predefined cookies (@see setcookie() for more details)
 */
function evo_sendcookies()
{
	global $evo_cookies;

	if( headers_sent() )
	{	// Exit to avoid errors because headers already were sent:
		return;
	}

	if( empty( $evo_cookies ) )
	{	// No cookies:
		return;
	}

	$current_cookie_domain = get_cookie_domain();
	$current_cookie_path = get_cookie_path();

	foreach( $evo_cookies as $evo_cookie_name => $evo_cookie )
	{
		setcookie( $evo_cookie_name, $evo_cookie['value'], $evo_cookie['expire'], $current_cookie_path, $current_cookie_domain, $evo_cookie['secure'], $evo_cookie['httponly'] );

		// Unset to don't send cookie twice:
		unset( $evo_cookies[ $evo_cookie_name ] );
	}
}
?>