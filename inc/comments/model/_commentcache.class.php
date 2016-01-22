<?php
/**
 * This file implements the CommentCache class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evocore
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
	function __construct( $objType = 'Comment', $dbtablename = 'T_comments', $dbprefix = 'comment_', $dbIDname = 'comment_ID' )
	{
		parent::__construct( $objType, false, $dbtablename, $dbprefix, $dbIDname );
	}


	/**
	 * Load the cache **extensively**
	 */
	function load_all()
	{
		if( $this->all_loaded )
		{ // Already loaded
			return false;
		}

		debug_die( 'Load all is not allowed for CommentCache!' );
	}
}

?>