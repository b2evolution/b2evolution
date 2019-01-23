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
		$this->ping_service_process_message = T_('Sending webmention pings to URLs mentioned in the post').'...';
		$this->ping_service_setting_title = T_('Send Webmention');
	}


	/**
	 * Ping the detected url to send webmentions
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

		$check_urls = array_unique( $check_urls );

		// Initialize client to send webmentions:
		require_once( __DIR__.'/MentionClient.php' );
		$MentionClient = new IndieWeb\MentionClient();

		$source_url = $Item->get_permanent_url( '', '', '&' );

		$nosupport_urls = array();
		$success_urls = array();
		$failed_urls = array();
		foreach( $check_urls as $target_url )
		{
			if( ! $MentionClient->discoverWebmentionEndpoint( $target_url ) )
			{	// The URL doesn't accept webmention:
				$nosupport_urls[] = get_link_tag( $target_url, '', '', 255 );
				continue;
			}

			if( ! ( $response = $MentionClient->sendWebmention( $source_url, $target_url ) ) ||
			    $response['code'] != 202 )
			{	// Webmention couldn't be accepted by some reason:
				$failed_urls[] = get_link_tag( $target_url, '', '', 255 ).( empty( $response['body'] ) ? '' : ' ('.T_('Error').': <code>'.$response['body'].'</code>)' );
				continue;
			}

			// Webmention has been accepted successfully:
			$success_urls[] = get_link_tag( $target_url, '', '', 255 );
		}

		$messages = array();

		if( count( $success_urls ) )
		{	// Success URLs:
			$messages[] = sprintf( T_('Webmentions have been accepted in the URLs: %s.'), implode( ', ', $success_urls ) );
		}

		if( count( $nosupport_urls ) )
		{	// No support URLs:
			$messages[] = sprintf( T_('The following URLs do not support webmentions: %s.'), implode( ', ', $nosupport_urls ) );
		}

		if( count( $failed_urls ) )
		{	// Failed URLs:
			$messages[] = sprintf( T_('Webmentions couldn\'t be accepted for the URLs: %s.'), implode( ', ', $failed_urls ) );
		}

		$params['xmlrpcresp'] = array( 'message' => implode( '<br />', $messages ) );

		return true;
	}
}

?>