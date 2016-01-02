<?php
/**
 * XML-RPC APIs
 *
 * This is a dummy file that loads the /xmlsrv/xmlrpc.php XML-RPC handler.
 * We need this file here in order to use the WordPress XML-RPC API since some 
 * clients just assume xmlrpc.php to be at the root level.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 */

require dirname(__FILE__).'/xmlsrv/xmlrpc.php';

?>