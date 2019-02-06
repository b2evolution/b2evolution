<?php
/**
 * This file handles webmention requests
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package htsrv
 */


/**
 * Initialize everything:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

require_once $inc_path.'_main.inc.php';

// Stop a request from the blocked IP addresses or Domains:
antispam_block_request();

if( $Settings->get('system_lock') )
{ // System is locked for maintenance, trackbacks are not allowed
	$Messages->add( T_('You cannot leave a comment at this time because the system is under maintenance. Please try again in a few moments.'), 'error' );
	header_redirect(); // Will save $Messages into Session
}

// Do not append Debuglog to response!
$debug = false;

// Do not append Debug JSlog to response!
$debug_jslog = false;

// Don't check new updates from b2evolution.net (@see b2evonet_get_updates()),
// in order to don't break the response data:
$allow_evo_stats = false;


/**
 * Send a webmention response and exits
 *
 * @param integer Error code
 * @param string Error message
 */
function webmention_response( $error_code = 0, $error_message = '' )
{
	header_http_response( $error_code.' '.$error_message );
	die( $error_message );
}

// Mandatory URls to post webmention comment:
param( 'source', 'url', true );
param( 'target', 'url', true );
param( 'excerpt', 'string' );

if( ! preg_match( '#/([a-z0-9\-_]+)[^/]*$#', $target, $item_url ) ||
    ! ( $ItemCache = & get_ItemCache() ) ||
    ! ( $target_Item = & $ItemCache->get_by_urltitle( $item_url[1], false, false ) ) )
{	// If item cannot be found by the requested absolute url:
	webmention_response( 400, 'You are sending a webmention to an invalid target URL' );
}

if( ! $target_Item->can_receive_webmentions() )
{	// If collection of the target Item doesn't support accepting webmentions:
	webmention_response( 400, 'Webmentions are disabled' );
}

// Initialize new Comment for webmention:
$webmention_Comment = new Comment();
$webmention_Comment->set( 'type', 'webmention' );
$webmention_Comment->set_Item( $target_Item );
$source_data = parse_url( $source );
$webmention_Comment->set( 'author', ( isset( $source_data['host'] ) ? $source_data['host'] : '' ) );
$webmention_Comment->set( 'author_IP', $Hit->IP );
$webmention_Comment->set( 'date', date('Y-m-d H:i:s', $localtimenow ) );
$webmention_Comment->set( 'content', $source.( empty( $excerpt ) ? '' : "\n".$excerpt ) );
$target_Blog = & $target_Item->get_Blog();
$webmention_Comment->set( 'status', $target_Blog->get_setting( 'new_feedback_status' ) );

if( ! $webmention_Comment->dbinsert() )
{	// Insert new "webmention" comment:
	webmention_response( 500, 'Error while registering the webmention' );
}

// Execute or schedule various notifications:
$webmention_Comment->handle_notifications( NULL, true );

webmention_response( 202, 'Webmention has been accepted' );