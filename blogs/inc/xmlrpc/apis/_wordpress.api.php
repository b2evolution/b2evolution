<?php
/**
 * XML-RPC : Wordpress API
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2009 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @author waltercruz
 *
 * @see http://codex.wordpress.org/XML-RPC_wp
 *
 * @package xmlsrv
 *
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Wordpress has some aliases to metaweblog APIS.

$xmlrpc_procs['wp.getCategories'] = array(
				'function' => 'mw_getcategories',
				'signature' => $mwgetcats_sig,
				'docstring' => $mwgetcats_doc );

$xmlrpc_procs['wp.uploadFile '] = array(
				'function' => 'mw_newmediaobject',
				'signature' => $mwnewMediaObject_sig,
				'docstring' => $mwnewMediaObject_doc);

?>
