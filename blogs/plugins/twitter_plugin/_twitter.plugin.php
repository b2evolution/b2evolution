<?php
/**
 * This file implements the Twitter plugin.
 *
 * For the most recent and complete Plugin API documentation
 * see {@link Plugin} in ../evocore/_plugin.class.php.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @copyright (c)2009 by Francois PLANQUE - {@link http://fplanque.net/}
 * @copyright (c)2007 by Lee Turner - {@link http://leeturner.org/}.
 *
 * @license GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *
 * @package plugins
 *
 * @author Lee Turner
 * @author fplanque: Francois PLANQUE.
 *
 * @todo dh> use OAuth instead of username/password: http://apiwiki.twitter.com/Authentication
 *
 * @version $Id$
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
	var $version = '3.2';
	var $author = 'Lee Turner';
	var $help_url = 'http://leeturner.org/twitterlution.php';

	/*
	 * These variables MAY be overriden.
	 */
	var $apply_rendering = 'never';
	var $group = 'ping';
	var $number_of_installs = 1;


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
	 * We require b2evo 3.2.0 or above.
	 */
	function GetDependencies()
	{
		return array(
				'requires' => array(
					'app_min' => '3.2.0-beta',
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
    /**
		 * @var Blog
		 */
		$item_Blog = $params['Item']->get_Blog();

		// Try to get twitter account for Blog:
		$oauth_token = $this->get_coll_setting( 'twitter_token', $item_Blog );
		$oauth_token_secret = $this->get_coll_setting( 'twitter_secret', $item_Blog );
		if( empty($oauth_token) || empty($oauth_token_secret) )
		{ // Not found, fallback to Trying to get twitter account for User:
			$oauth_token = $this->UserSettings->get( 'twitter_token' );
			$oauth_token_secret = $this->UserSettings->get( 'twitter_secret' );
			if( empty($oauth_token) || empty($oauth_token_secret) )
			{	// Still no twitter account found:
				$params['xmlrpcresp'] = T_('You must configure a twitter username/password before you can post to twitter.');
				return false;
			}
			else
			{	// Get additional params from User Setttings:
				$msg = $this->UserSettings->get( 'twitter_msg_format' );
				$oauth_contact = $this->UserSettings->get( 'twitter_contact' );
			}
		}
		else
		{	// Get additional params from Blog Setttings:
			$msg = $this->get_coll_setting( 'twitter_msg_format', $item_Blog );
			$oauth_contact = $this->get_coll_setting( 'twitter_contact', $item_Blog );
		}

		$title =  $params['Item']->dget('title', 'xml');
		$excerpt =  $params['Item']->dget('excerpt', 'xml');
		$url = $params['Item']->get_tinyurl();

		$msg = str_replace( '$title$', $title, $msg );
		$msg = str_replace( '$excerpt$', $excerpt, $msg );
		$msg = str_replace( '$url$', $url, $msg );

		require_once 'twitteroauth/twitteroauth.php';
		$connection = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, $oauth_token, $oauth_token_secret );

		$result = $connection->post('statuses/update', array( 'status' => $msg ));

		if( empty($result) )
		{
			return false;
		}
		elseif( !empty($result->error) )
		{
			$params['xmlrpcresp'] = $result->error;
			return false;
		}

		if( empty( $oauth_contact ) )
		{
			$oauth_contact = $this->get_twitter_contact( $oauth_token, $oauth_token_secret );
		}

		$params['xmlrpcresp'] = T_('Posted to account: @').$oauth_contact;
		return true;
	}

	/**
	 * Allowing the user to specify their twitter account name and password.
	 *
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
		require_once 'twitteroauth/twitteroauth.php';
		global $BlogCache;

		// decide to set Plugin CollSettings or UserSettings
		if( $target_type == 'blog' )
		{ // CollSettings
			// get setting from db
			$Blog = $BlogCache->get_by_ID( $target_id, false, false );
			if( empty( $Blog ) ) {
				return '<p>'.T_( 'Could not initialize' ).'</p>';
			}
			$oauth_token = $this->get_coll_setting( 'twitter_token', $Blog );
			if( !empty( $oauth_token ) )
			{ // blog has already a linked twitter user, get token secret
				$oauth_token_secret = $this->get_coll_setting( 'twitter_secret', $Blog );
				$oauth_contact = $this->get_coll_setting( 'twitter_contact', $Blog );
			}
		}
		else if ( $target_type = 'user' )
		{ // UserSettings
			// get setting from db
			if( empty ( $this->UserSettings ) ) {
				return NULL;
			}
			$oauth_token = $this->UserSettings->get( 'twitter_token', $target_id );
			if( !empty( $oauth_token ) )
			{ // user has already a linked twitter user, get token secret
				$oauth_token_secret = $this->UserSettings->get( 'twitter_secret', $target_id );
				$oauth_contact = $this->UserSettings->get( 'twitter_contact', $target_id );
			}
		}

		if( !empty( $oauth_token ) )
		{ // already linked
			if( empty( $oauth_contact ) )
			{
				$oauth_contact = $this->get_twitter_contact( $oauth_token, $oauth_token_secret );
				if( ! empty( $oauth_contact ) )
			{
					if( $target_type == 'blog' )
					{ // CollSettings
						$this->set_coll_setting( 'twitter_contact', $oauth_contact, $Blog->ID );
						$Blog->dbupdate();
					}
					else if( $target_type == 'user' )
					{ // UserSettings
						$this->UserSettings->set( 'twitter_contact', $oauth_contact, $target_id );
						$this->UserSettings->dbupdate();
					}
			}
		}
			$result = T_('Linked to').': @'.$oauth_contact.'. ';
		}

		// create new connection
		$connection = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET);

		// set callback url
		$callback = $this->get_htsrv_url( 'twitter_callback', array( 'target_type' => $target_type, 'target_id' => $target_id ), '&', true );

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
		if( empty($account->error) )
		{
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

		if( ! isset( $params['target_type'] ) || ! isset( $params['target_id'] ) )
		{
			bad_request_die( 'Missing target params!' );
		}

		$target_type = $params['target_type'];
		$target_id = $params['target_id'];

		if( $target_type == 'blog' )
		{ // redirect to blog settings
			$redirect_to = url_add_param( $admin_url, 'ctrl=coll_settings&tab=plugin_settings&blog='.$target_id );
		}
		else if ($target_type == 'user' )
		{ // redirect to user preferences form
			$redirect_to = url_add_param( $admin_url, 'ctrl=user&user_tab=preferences&user_ID='.$target_id );
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
			$Messages->add( T_( 'Error occured during twitter plugin initialization. Pleas try again.' ), 'error' );
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
				$Messages->add( T_( 'Error occured during verifying twitter plugin initialization. Pleas try again.' ), 'error' );
			}
			else
			{ // user didn't allow the connection
				$Messages->add( T_( 'Twitter plugin connection denied.' ), 'error' );
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
		{ // user preferences
			$this->UserSettings->set( 'twitter_token', $token, $target_id );
			$this->UserSettings->set( 'twitter_secret', $secret, $target_id );
			$this->UserSettings->set( 'twitter_contact', $contact, $target_id );
			$this->UserSettings->dbupdate();
		}

		/* Remove no longer needed request tokens */
		$Session->delete( 'oauth_token' );
		$Session->delete( 'oauth_token_secret' );
		$Session->dbsave();

		$Messages->add( T_( 'Twitter plugin was initialized successful!' ), 'success' );
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
			$redirect_to = url_add_param( $admin_url, 'ctrl=coll_settings&tab=plugin_settings&blog='.$target_id );

			$BlogCache = & get_BlogCache();
			$Blog = $BlogCache->get_by_ID( $target_id );

			$this->delete_coll_setting( 'twitter_token', $target_id );
			$this->delete_coll_setting( 'twitter_secret', $target_id );
			$this->delete_coll_setting( 'twitter_contact', $target_id );

			$Blog->dbupdate();
		}
		else if ($target_type == 'user' )
		{ // User settings
			$redirect_to = url_add_param( $admin_url, 'ctrl=user&user_tab=preferences&user_ID='.$target_id );

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

		$Messages->add( T_('Twitter account have been unlinked'), 'success' );
		header_redirect( $redirect_to );
		// We have EXITed already at this point!!
	}
}

/*
 * $Log$
 * Revision 1.26  2010/11/09 16:19:57  efy-asimo
 * disable twitter plugin, if curl is not loaded
 *
 * Revision 1.25  2010/10/12 12:52:17  efy-asimo
 * Move twitter callback and twitter unlink into twitter plugin class
 *
 * Revision 1.24  2010/10/05 12:53:46  efy-asimo
 * Move twitter_unlink into twitter_plugin
 *
 * Revision 1.23  2010/10/01 13:56:32  efy-asimo
 * twitter plugin save contact and fix
 *
 * Revision 1.22  2010/09/29 13:19:03  efy-asimo
 * Twitter user unlink, and twitter config params move to plugin
 *
 * Revision 1.21  2010/09/21 13:00:57  efy-asimo
 * Twitter plugin fix
 *
 * Revision 1.20  2010/08/24 08:20:19  efy-asimo
 * twitter plugin oAuth
 *
 * Revision 1.19  2010/05/11 11:56:31  efy-asimo
 * twitter plugin use tiny url
 *
 * Revision 1.18  2010/03/29 19:35:14  blueyed
 * doc/todo
 *
 * Revision 1.17  2009/09/15 18:02:05  fplanque
 * please make a separate plugin for identi.ca
 *
 * Revision 1.15  2009/06/29 02:14:04  fplanque
 * no message
 *
 * Revision 1.14  2009/06/26 22:07:20  tblue246
 * Minor (single quotes)
 *
 * Revision 1.13  2009/06/04 18:02:48  yabs
 * Removed CURL requirement.
 * Added $Item->excerpt as a replacement value ( note : I haven't added any char count check, you may wish to consider adding one )
 *
 * Revision 1.12  2009/05/28 14:47:33  fplanque
 * minor
 *
 * Revision 1.11  2009/05/28 12:49:48  fplanque
 * no message
 *
 * Revision 1.10  2009/05/27 18:00:04  fplanque
 * doc
 *
 * Revision 1.9  2009/05/27 14:02:11  fplanque
 * suggesting a hashtag : #b2p means "b2 post" http://tinyurl.com/qkdtwc
 *
 * Revision 1.8  2009/05/26 19:48:29  fplanque
 * Version bump.
 *
 * Revision 1.7  2009/05/26 19:35:22  fplanque
 * Twitter plugin: each blog can now notify a different twitter account!
 *
 * Revision 1.6  2009/05/26 18:30:02  tblue246
 * Doc, again
 *
 * Revision 1.5  2009/05/26 18:22:47  fplanque
 * better settings
 *
 * Revision 1.4  2009/05/26 18:05:12  tblue246
 * Doc
 *
 * Revision 1.3  2009/05/26 17:29:46  fplanque
 * A little bit of error management
 * (ps: BeforeEnable unecessary? how so?)
 * Tblue> I don't think the plugin code will be empty (unless the user
 *        modifies it, but why should he do that...)?
 * fp> why should we not check it? -- If user can fuck up, user WILL fuck
 *     up. Mind you it happened to me without even touching the setting;
 *     just by installing the plugin with a wrong class name.
 * Tblue> OK, the check doesn't hurt anyway. :-) "Better safe than sorry"
 *        (or summat).
 *
 * Revision 1.2  2009/05/26 17:18:36  tblue246
 * - Twitter plugin:
 * 	- removed unnecessary BeforeEnable() method.
 * 	- Todo: Do not depend on cURL
 * 	- Minor code improvements
 * - fetch_remote_page(): Todo about supporting HTTP POST requests
 *
 * Revision 1.1  2009/05/26 17:00:04  fplanque
 * added twitter plugin + better auto-install code for plugins in general
 *
 * v0.4 - Added the ability to customize the update text.
 * v0.3 - Removed echo of success or failure as this was causing a problem with the latest b2evolution
 * v0.2 - Included source parameter
 * v0.1 - Initial Release
 */
?>
