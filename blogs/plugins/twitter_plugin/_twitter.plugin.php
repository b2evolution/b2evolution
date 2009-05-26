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
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


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
		$this->name = T_('Twitter plugin');
		$this->short_desc = $this->T_('Post to your Twitter account when you post to your blog');
		$this->long_desc = $this->T_('Posts to your Twitter account to update Twitter.com with details of your blog post.');

		$this->ping_service_name = 'twitter.com';
		$this->ping_service_note = $this->T_('Update your twitter account with details about the new post.');
	}


	/**
	 * Event handler: Called before the plugin is going to be installed.
	 *
	 * @todo Tblue> Do not depend on cURL.
	 *
	 * @return true|string True, if the plugin can be enabled/activated,
	 *                     a string with an error/note otherwise.
	 */
	function BeforeInstall()
	{
		if( ! extension_loaded( 'curl' ) )
		{	// CURL not available :'(
			return T_('The twitter plugin needs the PHP CURL extension to be enabled.');
		}

		// OK:
		return true;
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
		$username = $this->get_coll_setting( 'twitter_username', $item_Blog );
		$password = $this->get_coll_setting( 'twitter_password', $item_Blog );
		if( empty($username) || empty($password) )
		{ // Not found, fallback to Trying to get twitter account for User:
			$username = $this->UserSettings->get( 'twitter_username' );
			$password = $this->UserSettings->get( 'twitter_password' );
			if( empty($username) || empty($password) )
			{	// Still no twitter account found:
				$params['xmlrpcresp'] = T_('You must configure a twitter username/password before you can post to twitter.');
				return false;
			}
			else
				{	// Get additional params from User Setttings:
				$msg = $this->UserSettings->get( 'twitter_msg_format' );
			}
		}
		else
		{	// Get additional params from Blog Setttings:
			$msg = $this->get_coll_setting( 'twitter_msg_format', $item_Blog );
		}

		$title =  $params['Item']->dget('title', 'xml');
		$url = $params['Item']->get_permanent_url();
		$msg = str_replace( '$title$', $title, $msg );
		$msg = str_replace( '$url$', $url, $msg );

		$session = curl_init();
		curl_setopt( $session, CURLOPT_URL, 'http://twitter.com/statuses/update.xml' );
		curl_setopt( $session, CURLOPT_POSTFIELDS, 'status='.urlencode($msg));
		curl_setopt( $session, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
		curl_setopt( $session, CURLOPT_HEADER, false );
		curl_setopt( $session, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt( $session, CURLOPT_USERPWD, $username.':'.$password );
		curl_setopt( $session, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $session, CURLOPT_POST, 1);
		$result = curl_exec ( $session ); // will be an XML message
		curl_close( $session );

		if( empty($result) )
		{
			return false;
		}
		elseif( preg_match( '¤<error>(.*)</error>¤', $result, $matches ) )
		{
			$params['xmlrpcresp'] = $matches[1];
			return false;
		}

		$params['xmlrpcresp'] = T_('Posted to account: @').$username;
		return true;
	}

	/**
	 * Allowing the user to specify their twitter account name and password.
	 *
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function GetDefaultUserSettings( & $params )
	{
		return array(
				'twitter_username' => array(
					'label' => T_( 'Twitter username' ),
					'type' => 'text',
				),
				'twitter_password' => array(
					'label' => T_( 'Twitter password' ),
					'type' => 'password',
				),
				'twitter_msg_format' => array(
					'label' => T_( 'Message format' ),
					'type' => 'text',
					'size' => 30,
					'maxlength' => 140,
					'defaultvalue' => T_( 'Just posted $title$ $url$' ),
					'note' => T_('$title$ and $url$ will be replaced appropriately.'),
				),
			);
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		return array(
				'twitter_username' => array(
					'label' => T_( 'Twitter username' ),
					'type' => 'text',
				),
				'twitter_password' => array(
					'label' => T_( 'Twitter password' ),
					'type' => 'password',
				),
				'twitter_msg_format' => array(
					'label' => T_( 'Message format' ),
					'type' => 'text',
					'size' => 30,
					'maxlength' => 140,
					'defaultvalue' => T_( 'Just posted $title$ $url$' ),
					'note' => T_('$title$ and $url$ will be replaced appropriately.'),
				),
			);
	}

}

/*
 * $Log$
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
