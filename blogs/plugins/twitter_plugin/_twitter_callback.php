<?php
/**
 * This file implements the twitter plugin _twitter_callback
 *
 * This file will be called back from twitter, after twitter user allows or deny the b2evo_twitter_plugin
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @package evocore
 * 
 * @author asimo: Evo Factory - Attila Simo
 *
 * @version $Id$
 */

require_once dirname(__FILE__).'/../../conf/_config.php';
require_once dirname(__FILE__).'/twitteroauth/twitteroauth.php';
require_once $inc_path.'_main.inc.php';
load_funcs('_core/_param.funcs.php');

global $Session, $Messages, $admin_url;

$req_token = param( 'oauth_token', 'string', '' );
$oauth_verifier = param( 'oauth_verifier', 'string', '' );
$oauth_token = $Session->get( 'oauth_token' );
$target_type = param( 'target_type', 'string', '' );
$target_id = param( 'target_id', 'string', '' );
$plugin_id = param( 'plugin_id', 'string', NULL );

$redirect_to = '';
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
	debug_die( 'Target type missing!' );
}

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

$connection = new TwitterOAuth( TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, $oauth_token, $Session->get( 'oauth_token_secret' ) );

//get access token
$access_token = $connection->getAccessToken( $oauth_verifier );

// save plugin settings for this user
global $Plugins;
$Plugin = & $Plugins->get_by_ID( $plugin_id );
if( empty( $Plugin ) )
{
	$Messages->add( T_( 'Can not find twitter plugin!' ), 'error' );
}
else if( $target_type == 'blog' )
{ // blog settings
	$Plugin->set_coll_setting( 'twitter_token', $access_token['oauth_token'], $target_id );
	$Plugin->set_coll_setting( 'twitter_secret', $access_token['oauth_token_secret'], $target_id );
	// save Collection settings
	$BlogCache = & get_BlogCache();
	$Blog = & $BlogCache->get_by_ID( $target_id, false, false );
	$Blog->dbupdate();
}
else if( $target_type == 'user' )
{ // user preferences
	$Plugin->UserSettings->set( 'twitter_token', $access_token['oauth_token'], $target_id );
	$Plugin->UserSettings->set( 'twitter_secret', $access_token['oauth_token_secret'], $target_id );
	$Plugin->UserSettings->dbupdate();
}

/* Remove no longer needed request tokens */
$Session->delete( 'oauth_token' );
$Session->delete( 'oauth_token_secret' );
$Session->dbsave();

$Messages->add( T_( 'Twitter plugin was initialized successful!' ), 'success' );
header_redirect( $redirect_to );

/*
 * $Log$
 * Revision 1.2  2010/09/29 14:32:29  efy-asimo
 * doc and small modificaitons
 *
 * Revision 1.1  2010/08/24 08:20:19  efy-asimo
 * twitter plugin oAuth
 *
 */
?>