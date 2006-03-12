<?php
/**
 * This file gets used to access {@link Plugin} methods that are marked to be accessible this
 * way. See {@link Plugin::get_htsrv_methods()}.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link https://thequod.de/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package htsrv
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */


/**
 * Initialize:
 * TODO: Don't do a full init!
 */
require_once dirname(__FILE__).'/../conf/_config.php';
require_once $inc_path.'_main.inc.php';


param( 'plugin_ID', 'integer', true );
param( 'method', 'string', '' );
param( 'params', 'string', '' );

$params = @unserialize($params);


if( $plugin_ID )
{
	$Plugin = & $Plugins->get_by_ID( $plugin_ID );

	if( ! $Plugin )
	{
		debug_die( 'Invalid Plugin!' );
	}

	if( ! in_array( $method, $Plugin->get_htsrv_methods() ) )
	{
		debug_die( 'Call to non-htsrv Plugin method!' );
	}
	elseif( ! method_exists( $Plugin, 'htsrv_'.$method ) )
	{
		debug_die( 'htsrv method does not exist!' );
	}

	// Call the method:
	$r = $Plugins->call_method( $Plugin->ID, 'htsrv_'.$method, $params );

	if( $r === false )
	{
		debug_die( 'The plugin htsrv method returned false!' );
	}
}


/* {{{ Revision log:
 * $Log$
 * Revision 1.3  2006/03/12 23:08:53  fplanque
 * doc cleanup
 *
 * Revision 1.2  2006/02/28 18:07:55  blueyed
 * Path fixes
 *
 * Revision 1.1  2006/01/28 23:43:35  blueyed
 * htsrv method for plugins. See Plugin::get_htsrv_url().
 *
 * }}}
 */
?>