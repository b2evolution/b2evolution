<?php
/**
 * This file implements functions to ping external sites/directories.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author cafelog (team)
 * @author fplanque: Francois PLANQUE.
 * @author vegarg
 *
 * @todo Make these plugins
 * @todo Link messages to HTML-Anchor tags, e.g. "Pinging <a href="%s">b2evolution.net</a>", to the site where the update can be seen.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Get blog params for specified ID
 *
 * @todo on a heavy multiblog system, cache them one by one...
 *
 * @param integer Blog ID
 */
function get_blogparams_by_ID( $blog_ID )
{
	global $cache_blogs;

	if( $blog_ID < 1 ) debug_die( 'No blog is selected!' );

	if( empty($cache_blogs[$blog_ID]) )
	{
		blog_load_cache();
	}
	if( !isset( $cache_blogs[$blog_ID] ) ) debug_die( T_('Requested blog does not exist!') );
	return $cache_blogs[ $blog_ID ];
}


/*
 * $Log$
 * Revision 1.9  2006/10/01 22:11:42  blueyed
 * Ping services as plugins.
 *
 * Revision 1.8  2006/08/21 16:07:44  fplanque
 * refactoring
 *
 * Revision 1.7  2006/08/21 01:02:10  blueyed
 * whitespace
 *
 * Revision 1.6  2006/08/21 00:03:13  fplanque
 * obsoleted some dirty old thing
 *
 * Revision 1.5  2006/08/19 07:56:31  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.4  2006/07/04 17:32:30  fplanque
 * no message
 *
 * Revision 1.3  2006/06/19 16:49:10  fplanque
 * minor
 *
 * Revision 1.2  2006/03/12 23:09:01  fplanque
 * doc cleanup
 *
 * Revision 1.1  2006/02/23 21:12:18  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.9  2006/02/10 20:37:10  blueyed
 * *** empty log message ***
 *
 * Revision 1.8  2005/12/12 19:21:23  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.7  2005/11/18 18:32:42  fplanque
 * Fixed xmlrpc logging insanity
 * (object should have been passed by reference but you can't pass NULL by ref)
 * And the code was geeky/unreadable anyway.
 *
 * Revision 1.6  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.5  2005/08/08 22:51:57  blueyed
 * todo
 *
 * Revision 1.4  2005/05/25 18:31:01  fplanque
 * implemented email notifications for new posts
 *
 * Revision 1.3  2005/02/28 09:06:33  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.2  2004/10/14 18:31:25  blueyed
 * granting copyright
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.28  2004/10/12 18:48:34  fplanque
 * Edited code documentation.
 *
 * Revision 1.9  2004/2/1 20:6:9  vegarg
 * Added technorati.com ping support.
 */
?>