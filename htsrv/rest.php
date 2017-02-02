<?php
/**
 * REST API
 *
 * This file implements the REST API handler, to be called by remote clients.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package api
 */


// Initialize config:
require_once dirname(__FILE__).'/../conf/_config.php';

/**
 * @global boolean Is this an API request?
 */
$is_api_request = true;

// Initialize main functions:
require_once $inc_path.'_main.inc.php';


// Don't check new updates from b2evolution.net (@see b2evonet_get_updates()),
// in order to don't break the response data:
$allow_evo_stats = false;

// We can't display standard error messages. We must return REST API responses:
$DB->halt_on_error = false;
$DB->show_errors = false;

// Don't print out debug info at the end:
$debug = 0;

// Get two main params for REST API class:
$api_version = param( 'api_version', 'integer', 1 );
$api_request = param( 'api_request', 'string', '' );

// Load class to work with REST API:
load_class( 'rest/_restapi.class.php', 'RestApi' );

// Initialize REST API object for current request:
$RestApi = new RestApi( $api_request );

// Execute REST API request:
$RestApi->execute();
?>