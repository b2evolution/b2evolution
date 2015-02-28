<?php
/**
 * This file implements the ping_b2evonet_plugin.
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
 * Ping b2evonet plugin.
 *
 * @package plugins
 */
class ping_b2evonet_plugin extends Plugin
{
	/**
	 * Variables below MUST be overriden by plugin implementations,
	 * either in the subclass declaration or in the subclass constructor.
	 */
	var $code = 'ping_b2evonet';
	var $priority = 50;
	var $version = '5.0.0';
	var $author = 'The b2evo Group';

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
		$this->name = T_('Ping b2evolution.net');
		$this->short_desc = T_('Ping the b2evolution.net site');
		$this->long_desc = T_('Pings the b2evolution.net site to include your post in the list of recently updated blogs.');

		$this->ping_service_name = 'b2evolution.net';
		$this->ping_service_note = $this->long_desc;
	}


	/**
	 * Ping the b2evonet RPC service.
	 */
	function ItemSendPing( & $params )
	{
		global $evonetsrv_host, $evonetsrv_port, $evonetsrv_uri;
		global $debug, $baseurl, $instance_name, $evo_charset;

    /**
		 * @var Blog
		 */
		$item_Blog = $params['Item']->get_Blog();

		$client = new xmlrpc_client( $evonetsrv_uri, $evonetsrv_host, $evonetsrv_port);
		$client->debug = ( $debug == 2 );

		$message = new xmlrpcmsg( 'b2evo.ping', array(
				new xmlrpcval($item_Blog->ID),    // id
				new xmlrpcval($baseurl),		      // user -- is this unique enough?
				new xmlrpcval($instance_name),		// pass -- fp> TODO: do we actually want randomly generated instance names?
				new xmlrpcval(convert_charset( $item_Blog->get('name'), 'utf-8', $evo_charset ) ),
				new xmlrpcval(convert_charset( $item_Blog->get('url'), 'utf-8', $evo_charset ) ),
				new xmlrpcval($item_Blog->locale),
				new xmlrpcval(convert_charset( $params['Item']->get('title'), 'utf-8', $evo_charset ) ),
			)  );
		$result = $client->send($message);

		$params['xmlrpcresp'] = $result;

		return true;
	}

}

?>