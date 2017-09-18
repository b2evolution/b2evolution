<?php
/**
 * This file implements the EmailAddressCache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobjectcache.class.php', 'DataObjectCache' );

load_class( 'tools/model/_emailaddress.class.php', 'EmailAddress' );

/**
 * Email Address Cache Class
 *
 * @package evocore
 */
class EmailAddressCache extends DataObjectCache
{
	/**
	 * Constructor
	 *
	 * @param string object type of elements in Cache
	 * @param string Name of the DB table
	 * @param string Prefix of fields in the table
	 * @param string Name of the ID field (including prefix)
	 * @param string Name of the name field (including prefix)
	 */
	function __construct( $objType = 'EmailAddress', $dbtablename = 'T_email__address', $dbprefix = 'emadr_', $dbIDname = 'emadr_ID', $name_field = 'emadr_address' )
	{
		parent::__construct( $objType, false, $dbtablename, $dbprefix, $dbIDname, $name_field );
	}


	/**
	 * Get an object from cache by name
	 *
	 * Load the cache if necessary (all at once if allowed).
	 *
	 * @param integer ID of object to load
	 * @param boolean true if function should die on error
	 * @param boolean true if function should die on empty/null
	 * @return object|NULL|boolean Reference on cached object, NULL - if request with empty or wrong email address, FALSE - if requested object does not exist
	 */
	function & get_by_name( $req_name, $halt_on_error = true, $halt_on_empty = true )
	{
		if( ! is_email( $req_name ) )
		{	// Don't allow wrong email address:
			$r = NULL;
			return $r;
		}

		$EmailAddress = & parent::get_by_name( $req_name, $halt_on_error, $halt_on_empty );

		return $EmailAddress;
	}
}

?>