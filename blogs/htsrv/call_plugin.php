<?php
/**
 * This file gets used to access {@link Plugin} methods that are marked to be accessible this
 * way. See {@link Plugin::GetHtsrvMethods()}.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
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


	if( method_exists( $Plugin, 'get_htsrv_methods' ) )
	{ // TODO: get_htsrv_methods is deprecated, but should stay here for transformation! (blueyed, 2006-04-27)
		if( ! in_array( $method, $Plugin->get_htsrv_methods() ) )
		{
			debug_die( 'Call to non-htsrv Plugin method!' );
		}
	}
	else
	if( ! in_array( $method, $Plugin->GetHtsrvMethods() ) )
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
 * Revision 1.5  2006/04/27 20:07:19  blueyed
 * Renamed Plugin::get_htsrv_methods() to GetHtsvMethods() (normalization)
 *
 * Revision 1.4  2006/04/19 20:13:48  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
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