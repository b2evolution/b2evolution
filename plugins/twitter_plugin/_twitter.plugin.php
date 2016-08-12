<?php
/**
 * This file implements the Twitter plugin.
 *
 * For the most recent and complete Plugin API documentation
 * see {@link Plugin} in ../evocore/_plugin.class.php.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 * @copyright (c)2007 by Lee Turner - {@link http://leeturner.org/}.
 *
 * @package plugins
 *
 * @author Lee Turner
 * @author fplanque: Francois PLANQUE.
 *
 * @todo dh> use OAuth instead of username/password: http://apiwiki.twitter.com/Authentication
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Twitter params initialization
define( 'TWITTER_CONSUMER_KEY', 'z680vsCAnATc0ZQNgMVwbg' );
define( 'TWITTER_CONSUMER_SECRET', 'OBo8xI6pvTR1KI0LBHEkjpPPd6nN99tq4SAY8qrBp8' );

//test app
//define( 'TWITTER_CONSUMER_KEY', 'PTJjBJraSkghuFVXQysPTg' );
//define( 'TWITTER_CONSUMER_SECRET', 'pcGfALMLaOF6VCaG6FwVO5hI1jtTPEgbLyj6Yo0DN04' );

/**
 * Twitter Plugin
 *
 * This plugin will post to your twitter account when you have added a post to your blog.
 *
 * @todo use OAuth -- http://www.jaisenmathai.com/blog/2009/03/31/how-to-quickly-integrate-with-twitters-oauth-api-using-php/
 * @todo Tblue> Do not use cURL, or at least do not depend on it! We could
 *              clone/modify {@link fetch_remote_page()} to be able to do
 *              HTTP POST requests.
 */
class twitter_plugin extends Plugin
{
	/**
	 * Variables below MUST be overriden by plugin implementations,
	 * either in the subclass declaration or in the subclass constructor.
	 */
	var $code = 'evo_twitter';
	var $priority = 50;
	var $version = '6.7.5';
	var $author = 'b2evolution Group';

	/*
	 * These variables MAY be overriden.
	 */
	var $group = 'ping';
	var $number_of_installs = 1;
	var $message_length_limit = 140; // The maximum allowed number of characters in a message

	/**
	 * Init
	 *
	 * This gets called after a plugin has been registered/instantiated.
	 */
	function PluginInit( & $params )
	{
		// Check php version
		if( version_compare( phpversion(), '5.0.0', '<' ) )
		{ // the plugin is not supported
			$this->set_status( 'disabled' );
			return false;
		}

		if( !extension_loaded( 'curl' ) )
		{ // the plugin is not supported
			$this->set_status( 'disabled' );
			return false;
		}

		$this->name = T_('Twitter plugin');
		$this->short_desc = $this->T_('Post to your Twitter account when you post to your blog');
		$this->long_desc = $this->T_('Posts to your Twitter account to update Twitter.com with details of your blog post.');

		$this->ping_service_name = 'twitter.com';
		$this->ping_service_note = $this->T_('Update your twitter account with details about the new post.');
	}


	/**
	 * We require b2evo 5.0 or above.
	 */
	function GetDependencies()
	{
		return array(
				'requires' => array(
					'app_min' => '5.0',
				),
			);
	}


	/**
	 * Check if the plugin can be enabled:
	 *
	 * @return string|NULL
	 */
	function BeforeEnable()
	{

		if( empty($this->code) )
		{
			return T_('The twitter plugin needs a non-empty code.');
		}

		if( version_compare( phpversion(), '5.0.0', '<' ) )
		{
			return T_('The twitter plugin requires PHP 5.');
		}

		if( !extension_loaded( 'curl' ) )
		{
			return T_( 'The twitter plugin requires the PHP curl extension.');
		}

		// OK:
		return true;
	}


	/**
	 * Post to Twitter.
	 *
	 * @return boolean Was the ping successful?
	 */
	function ItemSendPing( & $params )
	{
		$content = array(
				'title'		=> $params['Item']->dget('title', 'xml'),
				'excerpt'	=> $params['Item']->dget('excerpt', 'xml'),
				'url'		=> $params['Item']->get_tinyurl(),
			);

		return $this->send_a_tweet( $content, $params['Item'], $params['xmlrpcresp'] );
	}


	/**
	 * Define the PER-USER settings of the plugin here. These can then be edited by each user.
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param array Associative array of parameters.
	 *    'for_editing': true, if the settings get queried for editing;
	 *                   false, if they get queried for instantiating
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function GetDefaultUserSettings( & $params )
	{
		$info = NULL;
		if( isset( $params['user_ID'] ) )
		{ // initialize info only once, when needs to display the link (user_ID is set)
			$info = $this->get_twitter_link( 'user', $params['user_ID'] );
		}
		return array(
				'twitter_contact' => array(
					'label' => T_('Twitter account status'),
					'info' => $info,
					'type' => 'info',
				),
				'twitter_msg_format' => array(
					'label' => T_( 'Message format' ),
					'type' => 'text',
					'size' => 30,
					'maxlength' => 140,
					'defaultvalue' => T_( 'Just posted $title$ $url$ #b2p' ),
					'note' => T_('$title$, $excerpt$ and $url$ will be replaced appropriately.'),
				),
			);
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @todo: ideally we'd want a warning if the twitter ping is not enabled
	 *
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$info = NULL;
		if( isset( $params['blog_ID'] ) )
		{ // initialize info only once, when needs to display the link
			$info = $this->get_twitter_link( 'blog', $params['blog_ID'] );
		}
		return array(
				'twitter_contact' => array(
					'label' => T_('Twitter account status'),
					'info' => $info,
					'type' => 'info',
				),
				'twitter_msg_format' => array(
					'label' => T_( 'Message format' ),
					'type' => 'text',
					'size' => 30,
					'maxlength' => 140,
					'defaultvalue' => T_( 'Just posted $title$ $url$ #b2p' ),
					'note' => T_('$title$, $excerpt$ and $url$ will be replaced appropriately.'),
				),
			);
	}


	/**
	 * Get link to twitter oAuth
	 *
	 * @param string target type can be "blog" or "user", depends if we set blog or user setting
	 * @param string current blog id or edited user id
	 * @return string twitter oAuth link
	 */
	function get_twitter_link( $target_type, $target_id )
	{
		global $Blog;

		require_once 'twitteroauth/twitteroauth.php';

		// Uses either plugin CollSettings or UserSettings
		$oauth = $this->get_oauth_info( array(
				'type'	=> $target_type,
				'ID'	=> $target_id,
			) );

		if( !empty( $oauth['token'] ) )
		{ // already linked
			if( empty( $oauth['contact'] ) )
			{
				$oauth['contact'] = $this->get_twitter_contact( $oauth['token'], $oauth['token_secret'] );
				if( ! empty( $oauth['contact'] ) )
				{
					if( $target_type == 'blog' )
					{ // CollSettings
						$this->set_coll_setting( 'twitter_contact', $oauth['contact'], $Blog->ID );
						$Blog->dbupdate();
					}
					else if( $target_type == 'user' )
					{ // UserSettings
						$this->UserSettings->set( 'twitter_contact', $oauth['contact'], $target_id );
						$this->UserSettings->dbupdate();
					}
				}
			}
			$result = T_('Linked to').': @'.$oauth['contact'].'. ';
		}

		// create new connection
		$connection = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET);

		// set callback url
		$callback = $this->get_htsrv_url( 'twitter_callback', array(), '&', true );
		// Use the separate params for this request instead of using
		//     array $params(second param of the func above)
		//     because twitter cannot redirects to this complex url with serialized data
		$callback = url_add_param( $callback, 'target_type='.$target_type.'&target_id='.$target_id, '&' );

		$req_token = $connection->getRequestToken( $callback );

		if( $req_token == NULL )
		{
			return T_( 'Connection is not available!' );
		}

		$token = $req_token['oauth_token'];

		/* Save temporary credentials to session. */
		global $Session;
		$Session->delete( 'oauth_token' );
		$Session->delete( 'oauth_token_secret' );
		$Session->set( 'oauth_token', $req_token['oauth_token'] );
		$Session->set( 'oauth_token_secret', $req_token['oauth_token_secret'] );
		$Session->dbsave();

		if( empty( $result ) )
		{ // wasn't linked to twitter
			$result = '<a href='.$connection->getAuthorizeURL( $req_token, false ).'>'.T_( 'Click here to link to your twitter account' ).'</a>';
		}
		else
		{
			$result = $result.'<a href='.$connection->getAuthorizeURL( $req_token, false ).'>'.T_( 'Link to another account' ).'</a>';
			$unlink_url = $this->get_htsrv_url( 'unlink_account', array( 'target_type' => $target_type, 'target_id' => $target_id ), '&' );
			$unlink_url = $unlink_url.'&'.url_crumb( $target_type );
			$result = $result.' / '.'<a href="'.$unlink_url.'">'.T_( 'Unlink this account' ).'</a>';
		}

		return $result;
	}


	/**
	 * Get twitter contact display name
	 *
	 * @access private
	 *
	 * @param string oauth_token
	 * @param string oauth tokensecret
	 * @return string contact display name on success, empty string on error
	 */
	function get_twitter_contact( $oauth_token, $oauth_token_secret )
	{
		$connection = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, $oauth_token, $oauth_token_secret );
		// get linked user account
		$account = $connection->get('account/verify_credentials');
		if( empty( $account->errors ) )
		{
			if( is_array( $account ) )
			{ // Get only first account
				$account = $account[0];
			}
			return $account->screen_name;
		}
		return '';
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
		return array( 'unlink_account', 'twitter_callback' );
	}


	/**
	 * This callback method save the user's twitter oAuth, after the user allowed the b2evo_twitter plugin.
	 * It's the twitter site callback.
	 */
	function htsrv_twitter_callback( $params )
	{
		global $Session, $Messages, $admin_url;

		$target_type = param( 'target_type', 'string', NULL );
		$target_id = param( 'target_id', 'integer', NULL );

		if( is_null( $target_type ) || is_null( $target_id ) )
		{
			bad_request_die( 'Missing target params!' );
		}

		if( $target_type == 'blog' )
		{ // redirect to blog settings
			$redirect_to = url_add_param( $admin_url, 'ctrl=coll_settings&tab=plugins&blog='.$target_id );
		}
		else if ($target_type == 'user' )
		{ // redirect to user advanced preferences form
			$redirect_to = url_add_param( $admin_url, 'ctrl=user&user_tab=advanced&user_ID='.$target_id );
		}
		else
		{
			debug_die( 'Target type has incorrect value!' );
		}

		$req_token = param( 'oauth_token', 'string', '' );
		$oauth_verifier = param( 'oauth_verifier', 'string', '' );
		$oauth_token = $Session->get( 'oauth_token' );

		// check tokens
		//if (isset($_REQUEST['oauth_token']) && $Session->get( 'oauth_token' ) !== $_REQUEST['oauth_token']) {
		if( ( !empty( $req_token ) && ( $oauth_token !== $req_token ) ) || empty( $target_type ) || empty( $target_id ) )
		{
			$Messages->add( T_( 'An error occurred during twitter plugin initialization. Please try again.' ), 'error' );
			/* Remove no longer needed request tokens */
			$Session->delete( 'oauth_token' );
			$Session->delete( 'oauth_token_secret' );
			$Session->dbsave();
			header_redirect( $redirect_to );
		}

		if( empty( $oauth_verifier ) )
		{ // twitter refused the connection
			$denied = param( 'denied', 'string', '' );
			if( empty( $denied ) )
			{
				$Messages->add( T_( 'Twitter did not answer. Twitter may be overloaded. Please try again.' ), 'error' );
			}
			else
			{ // user didn't allow the connection
				$Messages->add( T_( 'Twitter denied the connection.' ), 'error' );
			}
			header_redirect( $redirect_to ); // !!!! Where to redirect
		}

		require_once 'twitteroauth/twitteroauth.php';
		$connection = new TwitterOAuth( TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, $oauth_token, $Session->get( 'oauth_token_secret' ) );

		//get access token
		$access_token = $connection->getAccessToken( $oauth_verifier );

		// get oauth params
		$token = $access_token['oauth_token'];
		$secret = $access_token['oauth_token_secret'];
		$contact = $this->get_twitter_contact( $token, $secret );
		if( $target_type == 'blog' )
		{ // blog settings
			$this->set_coll_setting( 'twitter_token', $token, $target_id );
			$this->set_coll_setting( 'twitter_secret', $secret, $target_id );
			$this->set_coll_setting( 'twitter_contact', $contact, $target_id );
			// save Collection settings
			$BlogCache = & get_BlogCache();
			$Blog = & $BlogCache->get_by_ID( $target_id, false, false );
			$Blog->dbupdate();
		}
		else if( $target_type == 'user' )
		{ // user advanced preferences
			$this->UserSettings->set( 'twitter_token', $token, $target_id );
			$this->UserSettings->set( 'twitter_secret', $secret, $target_id );
			$this->UserSettings->set( 'twitter_contact', $contact, $target_id );
			$this->UserSettings->dbupdate();
		}

		/* Remove no longer needed request tokens */
		$Session->delete( 'oauth_token' );
		$Session->delete( 'oauth_token_secret' );
		$Session->dbsave();

		$Messages->add( T_( 'Twitter plugin was initialized successfully!' ), 'success' );
		header_redirect( $redirect_to );
	}


	/**
	 * This callback method removes the twitter user oAuth data from DB.
	 */
	function htsrv_unlink_account( $params )
	{
		global $current_User, $Messages, $admin_url, $Session;

		if( ! isset( $params['target_type'] ) || ! isset( $params['target_id'] ) )
		{
			bad_request_die( 'Missing target params!' );
		}

		$target_type = $params['target_type'];
		$target_id = $params['target_id'];

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( $target_type );

		if( $target_type == 'blog' )
		{ // Blog settings
			$redirect_to = url_add_param( $admin_url, 'ctrl=coll_settings&tab=plugins&blog='.$target_id );

			$BlogCache = & get_BlogCache();
			$Blog = $BlogCache->get_by_ID( $target_id );

			$this->delete_coll_setting( 'twitter_token', $target_id );
			$this->delete_coll_setting( 'twitter_secret', $target_id );
			$this->delete_coll_setting( 'twitter_contact', $target_id );

			$Blog->dbupdate();
		}
		else if ($target_type == 'user' )
		{ // User settings
			$redirect_to = url_add_param( $admin_url, 'ctrl=user&user_tab=advanced&user_ID='.$target_id );

			if( isset( $current_User ) && ( !$current_User->check_perm( 'users', 'edit' ) ) && ( $target_id != $current_User->ID ) )
			{ // user is only allowed to update him/herself
				$Messages->add( T_('You are only allowed to update your own profile!'), 'error' );
				header_redirect( $redirect_to );
				// We have EXITed already at this point!!
			}

			$this->UserSettings->delete( 'twitter_token', $target_id );
			$this->UserSettings->delete( 'twitter_secret', $target_id );
			$this->UserSettings->delete( 'twitter_contact', $target_id );
			$this->UserSettings->dbupdate();
		}
		else
		{
			debug_die( 'Target type has incorrect value!' );
		}

		$Messages->add( T_('Your twitter account has been unlinked.'), 'success' );
		header_redirect( $redirect_to );
		// We have EXITed already at this point!!
	}


	function get_oauth_info( $params = array() )
	{
		$params = array_merge( array(
				'type'		=> '',
				'ID'		=> '',
				'blog_ID'	=> '',
				'user_ID'	=> '',
			), $params );

		if( $params['type'] == 'blog' )
		{	// Get from CollSettings
			$blog_ID = $params['ID'];
			$try_user = false;
		}
		elseif( $params['type'] == 'user' )
		{	// Get from UserSettings
			$user_ID = $params['ID'];
			$try_user = true;
		}
		else
		{	// Get from any
			$blog_ID = $params['blog_ID'];
			$user_ID = $params['user_ID'];
			$try_user = true;
		}

		$r = array();

		if( ! empty($blog_ID) )
		{	// CollSettings
			$BlogCache = & get_Cache('BlogCache');
			$Blog = & $BlogCache->get_by_ID( $blog_ID, false, false );
			if( !empty( $Blog ) )
			{
				$r['token'] = $this->get_coll_setting( 'twitter_token', $Blog );
				if( !empty($r['token']) )
				{	// There is already a linked twitter user in this Blog, get token secret
					$r['token_secret'] = $this->get_coll_setting( 'twitter_secret', $Blog );
					$r['contact'] = $this->get_coll_setting( 'twitter_contact', $Blog );
					$r['msg_format'] = $this->get_coll_setting( 'twitter_msg_format', $Blog );

					$try_user = false; // Do not overwrite
				}
			}
		}

		if( $try_user && ! empty($user_ID) )
		{	// UserSettings
			$r['token'] = $this->UserSettings->get( 'twitter_token', $user_ID );
			if( !empty( $r['token'] ) )
			{	// There is already a linked twitter user in this User, get token secret
				$r['token_secret'] = $this->UserSettings->get( 'twitter_secret', $user_ID );
				$r['contact'] = $this->UserSettings->get( 'twitter_contact', $user_ID );
				$r['msg_format'] = $this->UserSettings->get( 'twitter_msg_format', $user_ID );
			}
		}

		return $r;
	}


	function send_a_tweet( $content, & $Item, & $xmlrpcresp )
	{
		// Uses either plugin CollSettings or UserSettings
		$oauth = $this->get_oauth_info( array(
				'user_ID'	=> $Item->get_creator_User()->ID,
				'blog_ID'	=> $Item->get_Blog()->ID,
			) );

		if( empty($oauth['msg_format']) || empty($oauth['token']) || empty($oauth['token_secret']) )
		{ // Not found, fallback to Trying to get twitter account for User:
			$xmlrpcresp = T_('You must configure a twitter username/password before you can post to twitter.');
			return false;
		}

		$content = array_merge( array(
					'title'		=> '',
					'excerpt'	=> '',
					'url'		=> ''
				), $content );

		// Replace the title and exerpt, but before replacing decode the html entities
		$msg = str_replace(
				array( '$title$', '$excerpt$' ),
				array( html_entity_decode( $content['title'] ), html_entity_decode( $content['excerpt'] ) ),
				$oauth['msg_format']
			);

		$msg_len = utf8_strlen($msg);
		$full_url_len = utf8_strlen( $content['url'] );
		$base_url_len = utf8_strlen( $Item->get_Blog()->get_baseurl_root() );

		if( (utf8_strpos($msg, '$url$') === 0) && ($base_url_len + $msg_len - 5) > $this->message_length_limit )
		{	// The message is too long and is starting with $url$
			$max_len = $this->message_length_limit + $full_url_len - $base_url_len;
			$msg = strmaxlen( str_replace( '$url$', $content['url'], $msg ), $max_len, '...' );
		}
		elseif( (utf8_strpos(strrev($msg), 'p2b# $lru$') === 0) && ($base_url_len + $msg_len - 10) > $this->message_length_limit )
		{	// The message is too long and is ending on '$url$ #b2p'
			// Strip $url$, crop the message, and add URL to the end
			$max_len = $this->message_length_limit - $base_url_len -1; // save room for space character
			$msg = strmaxlen( str_replace( '$url$ #b2p', '', $msg ), $max_len, '...' );
			$msg .= ' '.$content['url'].' #b2p';
		}
		elseif( (utf8_strpos(strrev($msg), '$lru$') === 0) && ($base_url_len + $msg_len - 5) > $this->message_length_limit )
		{	// Same as above, but without '#b2p' suffix
			$max_len = $this->message_length_limit - $base_url_len -1; // save room for space character
			$msg = strmaxlen( str_replace( '$url$', '', $msg ), $max_len, '...' );
			$msg .= ' '.$content['url'];
		}
		elseif( (utf8_strpos($msg, '$url$') !== false) && ($base_url_len + $msg_len - 5) > $this->message_length_limit )
		{	// Message is too long and $url$ is somewhere in the middle
			// We can't do much, it will be rejected by Twitter
			// TODO: find a way to trim X chars before the URL and Y chars after
			$msg = str_replace( '$url$', $content['url'], $msg );
		}
		else
		{	// We don't want to add URL. Crop the message if needed
			$msg = strmaxlen( str_replace( '$url$', $content['url'], $msg ), $this->message_length_limit, '...' );
		}

		require_once 'twitteroauth/twitteroauth.php';
		$connection = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, $oauth['token'], $oauth['token_secret'] );
		$result = $connection->post('statuses/update', array( 'status' => $msg ));

		if( empty($result) )
		{
			$xmlrpcresp = 'Unknown error while posting "'.htmlspecialchars( $msg ).'" to account @'.$oauth['contact'];
			return false;
		}
		elseif( !empty($result->error) )
		{
			$xmlrpcresp = $result->error;
			return false;
		}

		if( empty($oauth['contact']) )
		{
			$oauth['contact'] = $this->get_twitter_contact( $oauth['token'], $oauth['token_secret'] );
		}

		$xmlrpcresp = T_('Posted to account @').$oauth['contact'];
		return true;
	}
}

?>