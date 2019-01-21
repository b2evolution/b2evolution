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

// Mandatory URls to post webmention comment:
param( 'source', 'url', true );
param( 'target', 'url', true );

// Insert "webmention" comment to the Item with permanent URL == $target