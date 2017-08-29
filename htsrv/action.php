<?php
/**
 * This is the handler for different modules action
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */

/**
 * Initialize everything:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

require_once $inc_path.'_main.inc.php';

global $Session, $modules;

// Don't check new updates from b2evolution.net (@see b2evonet_get_updates()),
// in order to don't break the response data:
$allow_evo_stats = false;

// Module name param must exists
$module_name = param( 'mname', 'string', true );

$blog = param( 'blog', 'integer', 0 );
if( ! empty( $blog ) )
{ 
	activate_blog_locale( $blog );
	// Initialize collection object because it may be used in some functions:
	$BlogCache = & get_BlogCache();
	$Collection = $Blog = & $BlogCache->get_by_ID( $blog );
}

foreach( $modules as $module )
{
	if( $module == $module_name )
	{ // the requested module was found
		$Module = & $GLOBALS[$module.'_Module'];
		if( method_exists( $Module, 'handle_htsrv_action' ) )
		{	// Module has handle_htsrv_action function, we can call it
			$Module->handle_htsrv_action();
			break;
		}
	}
}

header_redirect();
// exited

?>