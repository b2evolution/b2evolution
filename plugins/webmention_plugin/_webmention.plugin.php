<?php
/**
 * This file implements the webmention_plugin.
 *
 * For the most recent and complete Plugin API documentation
 * see {@link Plugin} in ../evocore/_plugin.class.php.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Webmention plugin.
 *
 * @package plugins
 */
class webmention_plugin extends Plugin
{
	/**
	 * Variables below MUST be overriden by plugin implementations,
	 * either in the subclass declaration or in the subclass constructor.
	 */
	var $code = 'webmention';
	var $priority = 50;
	var $version = '6.10.6';

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
		$this->name = T_('Webmention plugin');
		$this->short_desc = T_('Send webmentions to all URLs detected in a posted Item.');

		$this->ping_service_name = 'Webmention';
		$this->ping_service_note = T_('Send webmentions to all URLs detected in a posted Item.');
	}


	/**
	 * Ping the pingomatic RPC service.
	 */
	function ItemSendPing( & $params )
	{
		$Item = $params['Item'];

		$check_urls = array();

		if( preg_match_all( '#href="([^"]+)"#i', $Item->get_prerendered_content( 'htmlbody' ), $match_urls ) )
		{	// Get URLs from the rendered item content:
			$check_urls = $match_urls[1];
		}

		if( $Item->get( 'url' ) != '' )
		{	// Also check item URL:
			$check_urls[] = $Item->get( 'url' );
		}

		if( empty( $check_urls ) )
		{	// No urls detected
			return true;
		}

		// Initialize client to send webmentions:
		require_once( __DIR__.'/MentionClient.php' );
		$MentionClient = new IndieWeb\MentionClient();

		$source_url = $Item->get_permanent_url( '', '', '&' );

		foreach( $check_urls as $target_url )
		{
			if( $endpoint = $MentionClient->discoverWebmentionEndpoint( $target_url ) )
			{	// Send webmention if site of the posted url can receive webmention:
				$response = $MentionClient->sendWebmention( $source_url, $target_url );
				//$params['xmlrpcresp'] = $response;
			}
		}

		return true;
	}
}

?>