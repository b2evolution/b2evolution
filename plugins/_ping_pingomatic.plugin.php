<?php
/**
 * This file implements the ping_pingomatic_plugin.
 *
 * For the most recent and complete Plugin API documentation
 * see {@link Plugin} in ../evocore/_plugin.class.php.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package plugins
 *
 * @author blueyed: Daniel HAHLER
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Pingomatic plugin.
 *
 * @package plugins
 */
class ping_pingomatic_plugin extends Plugin
{
	/**
	 * Variables below MUST be overriden by plugin implementations,
	 * either in the subclass declaration or in the subclass constructor.
	 */
	var $code = 'ping_pingomatic';
	var $priority = 50;
	var $version = '1.9-dev';
	var $author = 'http://daniel.hahler.de/';

	/*
	 * These variables MAY be overriden.
	 */
	var $group = 'ping';
	var $number_of_installs = 1;


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->name = T_('Ping-O-Matic plugin');
		$this->short_desc = T_('Pings the Ping-O-Matic service, which relays your ping to the most common services.');

		$this->ping_service_name = 'Ping-O-Matic';
		$this->ping_service_note = T_('Pings a service that relays the ping to the most common services.');
	}


	/**
	 * Ping the pingomatic RPC service.
	 */
	function ItemSendPing( & $params )
	{
		global $debug;

		$item_Blog = $params['Item']->get_Blog();

		$client = new xmlrpc_client( '/', 'rpc.pingomatic.com', 80 );
		$client->debug = ($debug && $params['display']);

		$message = new xmlrpcmsg("weblogUpdates.ping", array(
				new xmlrpcval( $item_Blog->get('name') ),
				new xmlrpcval( $item_Blog->get('url') ) ));
		$result = $client->send($message);

		$params['xmlrpcresp'] = $result;

		return true;
	}

}

?>