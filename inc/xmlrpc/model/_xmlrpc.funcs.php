<?php
/**
 * @package evocore
 * @subpackage xmlrpc {@link http://xmlrpc.usefulinc.com/doc/}
 * @copyright Edd Dumbill <edd@usefulinc.com> (C) 1999-2001
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Usage:
// $client = new xmlrpc_client( $basesubpath.$xmlsrv_subdir.'xmlrpc.php', $basehost, substr( $baseport, 1 ) );
// $client->debug = true;

// Original fix for missing extension file by "Michel Valdrighi" <m@tidakada.com>
if(function_exists('xml_parser_create'))
{
	/**
	 * Can we use XML-RPC functionality?
	 *
	 * @constant CANUSEXMLRPC true|string Either === true or holds the error message.
	 */
	define( 'CANUSEXMLRPC', TRUE );
}
elseif( !(bool)ini_get('enable_dl') || (bool)ini_get('safe_mode'))
{ // We'll not be able to do dynamic loading (fix by Sakichan)
	/**
	 * @ignore
	 */
	define( 'CANUSEXMLRPC', 'XML extension not loaded, but we cannot dynamically load.' );
}
elseif( !empty($WINDIR) )
{	// Win 32 fix. From: "Leo West" <lwest@imaginet.fr>
	if (function_exists('dl') && @dl('php3_xml.dll'))
	{
		/**
		 * @ignore
		 */
		define( 'CANUSEXMLRPC', true );
	}
	else
	{
		/**
		 * @ignore
		 */
		define( 'CANUSEXMLRPC', 'Could not load php3_xml.dll!' );
	}
}
else
{
	if (function_exists('dl') && @dl('xml.so'))
	{
		/**
		 * @ignore
		 */
		define( 'CANUSEXMLRPC', true );
	}
	else
	{
		/**
		 * @ignore
		 */
		define( 'CANUSEXMLRPC', 'Could not load xml.so!' );
	}
}

if( true !== CANUSEXMLRPC )
{
	return;
}


load_funcs('_ext/xmlrpc/_xmlrpc.inc.php');

// b2evolution: Set internal encoding for the XML-RPC library.
global $xmlrpc_internalencoding, $evo_charset;
$xmlrpc_internalencoding = strtoupper( $evo_charset );


// --------------------------------------- SUPPORT FUNCTIONS ----------------------------------------

/*
 * evocore: We add xmlrpc_decode_recurse because the default PHP implementation
 * of xmlrpc_decode won't recurse! Bleh!
 * update: XML-RPC for PHP now copes with this, but we keep a stub for backward compatibility
 */
function xmlrpc_decode_recurse($xmlrpc_val)
{
	return php_xmlrpc_decode($xmlrpc_val);
}


?>
