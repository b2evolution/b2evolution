<?php
/**
 * This file gets used to access {@link Plugin} methods that are marked to be accessible this
 * way. See {@link Plugin::GetHtsrvMethods()}.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package htsrv
 */


/**
 * Initialize:
 * TODO: Don't do a full init!
 */
require_once dirname(__FILE__).'/../conf/_config.php';
require_once $inc_path.'_main.inc.php';

// Don't check new updates from b2evolution.net (@see b2evonet_get_updates()),
// in order to don't break the response data:
$allow_evo_stats = false;


param( 'plugin_ID', 'integer', true );
// fp> it is probably unnecessary complexity to handle a method here
// instead of calling handle_htsrv_action() all the time
// and letting the plugin deal with its methods internally.
param( 'method', 'string', '' );
param( 'params', 'string', null ); // serialized

if( $plugin_ID === -1 & $method == 'test_api' )
{	// Use this case to test API from ctrl=system:
	echo 'ok';
	exit(0);
}

if( is_null( $params ) )
{	// Use empty array by default if params are not sent by request:
	$params = array();
}
else
{	// Params given:
	if( param_check_serialized_array( 'params' ) )
	{	// If the params is a serialized array and doesn't contain any object inside:
		// (This may result in "false", but this means that unserializing failed)
		$params = @unserialize( $params );
	}
	else
	{	// Restrict all non array params to empty array:
		bad_request_die( 'Invalid params! Cannot unserialize.' );
	}
}


if( $plugin_ID )
{
	$Plugin = & $Plugins->get_by_ID( $plugin_ID );

	if( ! $Plugin )
	{
		bad_request_die( 'Invalid Plugin! (maybe not enabled?)' );
	}


	if( method_exists( $Plugin, 'get_htsrv_methods' ) )
	{ // TODO: get_htsrv_methods is deprecated, but should stay here for transformation! (blueyed, 2006-04-27)
		if( ! in_array( $method, $Plugin->get_htsrv_methods() ) )
		{
			bad_request_die( 'Call to non-htsrv Plugin method!' );
		}
	}
	else
	if( ! in_array( $method, $Plugin->GetHtsrvMethods() ) )
	{
		bad_request_die( 'Call to non-htsrv Plugin method!' );
	}
	elseif( ! method_exists( $Plugin, 'htsrv_'.$method ) )
	{
		bad_request_die( 'htsrv method does not exist!' );
	}

	// Call the method:
	$Plugins->call_method( $Plugin->ID, 'htsrv_'.$method, $params );
}

?>