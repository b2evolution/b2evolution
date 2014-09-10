<?php
/**
 * This file implements the IP Range class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
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
 * @version $Id: _iprange.class.php 849 2012-02-16 09:09:09Z attila $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );


/**
 * Slug Class
 *
 * @package evocore
 */
class IPRange extends DataObject
{
	var $IPv4start;

	var $IPv4end;

	var $user_count;

	var $status;

	var $block_count;

	/**
	 * Constructor
	 *
	 * @param object table Database row
	 */
	function IPRange( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_antispam__iprange', 'aipr_', 'aipr_ID' );

		if( $db_row != NULL )
		{
			$this->ID = $db_row->aipr_ID;
			$this->IPv4start = $db_row->aipr_IPv4start;
			$this->IPv4end = $db_row->aipr_IPv4end;
			$this->user_count = $db_row->aipr_user_count;
			$this->status = $db_row->aipr_status;
			$this->block_count = $db_row->aipr_block_count;
		}
	}


	/**
	 * Get a member param by its name
	 *
	 * @param mixed Name of parameter
	 * @return mixed Value of parameter
	 */
	function get( $parname )
	{
		return parent::get( $parname );
	}


	/**
	 * Set param value
	 *
	 * @param string parameter name
	 * @param mixed parameter value
	 * @param boolean true to set to NULL if empty value
	 * @return boolean true, if a value has been set; false if it has not changed
	 */
	function set( $parname, $parvalue, $make_null = false )
	{
		return $this->set_param( $parname, 'string', $parvalue, $make_null );
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		global $Messages;

		$aipr_status = param( 'aipr_status', 'string', true );
		$this->set( 'status', $aipr_status, true );

		$aipr_IPv4start = param( 'aipr_IPv4start', 'string', true );
		param_check_regexp( 'aipr_IPv4start', '#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$#i', T_('Please enter a correct IP range start') );
		$aipr_IPv4start = ip2int( $aipr_IPv4start );
		$this->set( 'IPv4start', $aipr_IPv4start );

		$aipr_IPv4end = param( 'aipr_IPv4end', 'string', true );
		param_check_regexp( 'aipr_IPv4end', '#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$#i', T_('Please enter a correct IP range end') );
		$aipr_IPv4end = ip2int( $aipr_IPv4end );
		$this->set( 'IPv4end', $aipr_IPv4end );

		if( $aipr_IPv4start > $aipr_IPv4end )
		{
			$Messages->add( T_('IP range start must be less than IP range end'), 'error' );
		}

		if( ! param_errors_detected() )
		{	// Check IPs for inside in other ranges
			if( $ip_range = get_ip_range( $aipr_IPv4start, $aipr_IPv4end, $this->ID ) )
			{
				$admin_url;
				$Messages->add( sprintf( T_('IP range already exists with params: %s - <a %s>Edit this range</a>'),
					int2ip( $ip_range->aipr_IPv4start ).' - '.int2ip( $ip_range->aipr_IPv4end ),
					'href="'.$admin_url.'?ctrl=antispam&amp;tab3=ipranges&amp;action=iprange_edit&amp;iprange_ID='.$ip_range->aipr_ID.'"' ), 'error' );
			}
		}

		return ! param_errors_detected();
	}


	/**
	 * Get name of IP range
	 *
	 * @return string Name of IP range
	 */
	function get_name()
	{
		return int2ip( $this->IPv4start ).' - '.int2ip( $this->IPv4end );
	}
}

?>