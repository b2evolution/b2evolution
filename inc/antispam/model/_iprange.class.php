<?php
/**
 * This file implements the IP Range class.
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
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_antispam__iprange', 'aipr_', 'aipr_ID' );

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
			if( $ip_ranges = get_ip_ranges( $aipr_IPv4start, $aipr_IPv4end, $this->ID ) )
			{	// If at least one IP range is found:
				if( param( 'delete_conflicts', 'integer', 0 ) )
				{	// Delete all conflicts:
					global $DB;
					$delete_ip_ranges_IDs = array();
					$ip_ranges_html = '<ul>';
					foreach( $ip_ranges as $ip_range )
					{
						$ip_ranges_html .= '<li>- '.int2ip( $ip_range->aipr_IPv4start ).' - '.int2ip( $ip_range->aipr_IPv4end ).'</li>';
						$delete_ip_ranges_IDs[] = $ip_range->aipr_ID;
					}
					$ip_ranges_html .= '</ul>';
					$delete_result = $DB->query( 'DELETE FROM T_antispam__iprange
						WHERE aipr_ID IN ( '.$DB->quote( $delete_ip_ranges_IDs ).' )' );
					if( $delete_result )
					{	// If IP ranges have been deleted successfully:
						$Messages->add( sprintf( T_('Conflicting IP Ranges have been deleted: %s'), $ip_ranges_html ), 'success' );
					}
				}
				else
				{	// Inform user about conflicts:
					global $admin_url;
					$ip_ranges_html = '<ul>';
					foreach( $ip_ranges as $ip_range )
					{
						$ip_ranges_html .= '<li>- '.int2ip( $ip_range->aipr_IPv4start ).' - '.int2ip( $ip_range->aipr_IPv4end ).' - <a href="'.$admin_url.'?ctrl=antispam&amp;tab3=ipranges&amp;action=iprange_edit&amp;iprange_ID='.$ip_range->aipr_ID.'">'.T_('Edit this range').'</a></li>';
					}
					$ip_ranges_html .= '</ul>';
					$Messages->add( sprintf( T_('Conflicting IP Ranges already exist: %s'), $ip_ranges_html )
						.'<button class="btn btn-danger" type="button" id="delete_iprange_conflicts">'.T_('Delete all conflicts and save new Range').'</button>', 'error' );
				}
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