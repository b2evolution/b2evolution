<?php
/**
 * This file implements the generic_ping_plugin.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package plugins
 *
 * @author blueyed: Daniel HAHLER
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Generic Ping Plugin
 *
 * @package plugins
 */
class generic_ping_plugin extends Plugin
{
	/**
	 * Variables below MUST be overriden by plugin implementations,
	 * either in the subclass declaration or in the subclass constructor.
	 */
	var $code = 'b2evGPing';
	var $priority = 50;
	var $version = '6.7.8';
	var $author = 'The b2evo Group';
	var $help_url = '';  // empty URL defaults to manual wiki

	/*
	 * These variables MAY be overriden.
	 */
	var $group = 'ping';


	/**
	 * Init
	 *
	 * This gets called after a plugin has been registered/instantiated.
	 */
	function PluginInit( & $params )
	{
		$this->name = T_('Generic Ping plugin');
		$this->short_desc = T_('Use this plugin to add a generic ping service to your installation.');

		if( $params['is_installed'] )
		{ // is not set for not-installed Plugins
			$this->ping_service_name = $this->Settings->get('ping_service_name');
			$this->ping_service_note = $this->Settings->get('ping_service_note');
		}
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
			'ping_service_url' => array(
					'label' => T_('Ping service URL'),
					'defaultvalue' => '',
					'type' => 'text',
					'size' => 50,
					'note' => T_('The URL of the ping service.').' '.sprintf('E.g. &laquo;%s&raquo;', 'rpc.weblogs.com/RPC2 or rpc.foobar.com:8080'),
				),
			'ping_service_extended' => array(
					'label' => T_('Extended ping?'),
					'type' => 'checkbox',
					'defaultvalue' => 0,
					'note' => T_('Use weblogUpdates.extendedPing method instead of weblogUpdates.ping?'),
				),
			'ping_service_name' => array(
					'label' => T_('Ping service name'),
					'defaultvalue' => '',
					'type' => 'text',
					'size' => 25,
					'note' => T_('The name of the ping service, used for displaying only.'),
			),
			'ping_service_note' => array(
					'label' => T_('Ping service note'),
					'defaultvalue' => '',
					'type' => 'text',
					'size' => 50,
					'note' => T_('Notes about the ping service, used for displaying only.'),
			),
		);
	}


	/**
	 * Check ping service URL and plugin code.
	 */
	function BeforeEnable()
	{
		$ping_service_url = $this->Settings->get('ping_service_url');
		if( empty($ping_service_url) )
		{
			return T_('You must configure a ping service URL before the plugin can be enabled.');
		}

		if( empty($this->code) )
		{
			return T_('The ping plugin needs a non-empty code.');
		}

		return true;
	}


	/**
	 * Check ping service URL.
	 */
	function PluginSettingsValidateSet( & $params )
	{
		if( $params['name'] == 'ping_service_url' )
		{
			if( ! $this->parse_ping_url($params['value']) )
			{
				return T_('The ping service URL is invalid.');
			}
		}
	}


	/**
	 * Parse a given ping service URL
	 *
	 * @return false|array False in case of error, array with keys 'host', 'port', 'path' otherwise
	 */
	function parse_ping_url( $url )
	{
		if( ! preg_match( '~^([^/:]+)(:\d+)?(/.*)?$~', $url, $match ) )
		{
			return false;
		}

		$r = array(
				'host' => $match[1],
				'port' => empty($match[2]) ? 80 : $match[2],
				'path' => empty($match[3]) ? '/' : $match[3],
			);

		return $r;
	}


	/**
	 * Send a ping to the configured service.
	 */
	function ItemSendPing( & $params )
	{
		global $debug;
		global $outgoing_proxy_hostname, $outgoing_proxy_port, $outgoing_proxy_username, $outgoing_proxy_password;

		$url = $this->parse_ping_url( $this->Settings->get( 'ping_service_url' ) );

		$Item = $params['Item'];
		$item_Blog = $Item->get_Blog();

		$client = new xmlrpc_client( $url['path'], $url['host'], $url['port'] );
		$client->debug = ($debug && $params['display']);

		// Set proxy for outgoing connections:
		if( ! empty( $outgoing_proxy_hostname ) )
		{
			$client->setProxy( $outgoing_proxy_hostname, $outgoing_proxy_port, $outgoing_proxy_username, $outgoing_proxy_password );
		}

		if( $this->Settings->get('ping_service_extended') )
		{
			$message = new xmlrpcmsg("weblogUpdates.extendedPing", array(
					new xmlrpcval( $item_Blog->get('name') ),
					new xmlrpcval( $item_Blog->get('url') ),
					new xmlrpcval( $Item->get_permanent_url() ),
					new xmlrpcval( $item_Blog->get('atom_url') ),
					// TODO: tags..
					));
		}
		else
		{
			$message = new xmlrpcmsg("weblogUpdates.ping", array(
					new xmlrpcval( $item_Blog->get('name') ),
					new xmlrpcval( $item_Blog->get('url') ) ));
		}
		$result = $client->send($message);

		$params['xmlrpcresp'] = $result;

		return true;
	}

}

?>