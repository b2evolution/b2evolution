<?php
/**
 * This file implements the CommentCache class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * EVO FACTORY grants Francois PLANQUE the right to license
 * EVO FACTORY contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author asimo: Evo Factory / Attila Simo
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobjectcache.class.php', 'DataObjectCache' );

//load_class( 'comments/model/_comment.class.php', 'Comment' );

/**
 * Comment Cache Class
 *
 * @package evocore
 */
class CommentCache extends DataObjectCache
{
	/**
	 * Constructor
	 *
	 * @param string object type of elements in Cache
	 * @param string Name of the DB table
	 * @param string Prefix of fields in the table
	 * @param string Name of the ID field (including prefix)
	 */
	function CommentCache( $objType = 'Comment', $dbtablename = 'T_comments', $dbprefix = 'comment_', $dbIDname = 'comment_ID' )
	{
		parent::DataObjectCache( $objType, false, $dbtablename, $dbprefix, $dbIDname );
	}
}


/*
 * $Log$
 * Revision 1.3  2011/09/04 22:13:15  fplanque
 * copyright 2011
 *
 * Revision 1.2  2010/07/26 06:52:16  efy-asimo
 * MFB v-4-0
 *
 */
?>