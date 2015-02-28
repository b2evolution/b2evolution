<?php
/**
 * This file implements the IPRangeCache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobjectcache.class.php', 'DataObjectCache' );

load_class( 'antispam/model/_iprange.class.php', 'IPRange' );

/**
 * IP Range Cache Class
 *
 * @package evocore
 */
class IPRangeCache extends DataObjectCache
{
	/**
	 * Lazy filled index of IP addresses
	 */
	var $ip_index = array();

	/**
	 * Constructor
	 *
	 * @param string object type of elements in Cache
	 * @param string Name of the DB table
	 * @param string Prefix of fields in the table
	 * @param string Name of the ID field (including prefix)
	 */
	function IPRangeCache( $objType = 'IPRange', $dbtablename = 'T_antispam__iprange', $dbprefix = 'aipr_', $dbIDname = 'aipr_ID' )
	{
		parent::DataObjectCache( $objType, false, $dbtablename, $dbprefix, $dbIDname );
	}


	/**
	 * Get an object from cache by IP address
	 *
	 * Load into cache if necessary
	 *
	 * @param string IP address
	 * @param boolean false if you want to return false on error
	 * @param boolean true if function should die on empty/null
	 */
	function & get_by_ip( $req_ip, $halt_on_error = false, $halt_on_empty = false )
	{
		global $DB, $Debuglog;

		if( !isset( $this->ip_index[ $req_ip ] ) )
		{	// not yet in cache:

			$IP = ip2int( $req_ip );

			$SQL = new SQL( 'Get ID of IP range by IP address' );
			$SQL->SELECT( 'aipr_ID' );
			$SQL->FROM( 'T_antispam__iprange' );
			$SQL->WHERE( 'aipr_IPv4start <= '.$DB->quote( $IP ) );
			$SQL->WHERE_and( 'aipr_IPv4end >= '.$DB->quote( $IP ) );
			$IPRange_ID = $DB->get_var( $SQL->get() );

			// Get object from IPRangeCache bi ID
			$IPRange =  $this->get_by_ID( $IPRange_ID, $halt_on_error, $halt_on_empty );

			if( $IPRange )
			{	// It is in IPRangeCache
				$this->ip_index[ $req_ip ] = $IPRange;
			}
			else
			{	// not in the IPRangeCache
				if( $halt_on_error ) debug_die( "Requested $this->objtype does not exist!" );
				$this->ip_index[ $req_ip ] = false;
			}
		}
		else
		{
			$Debuglog->add( "Retrieving <strong>$this->objtype($req_ip)</strong> from cache" );
		}

		return $this->ip_index[ $req_ip ];
	}

}

?>