<?php
/**
 * This file implements the functions for Central Antispam module
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 * @author fplanque: Francois PLANQUE.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Get statuses for keyword
 *
 * @return array
 */
function ca_get_keyword_statuses()
{
	global $central_antispam_Module;

	return array(
			'new'       => T_('New'),
			'published' => T_('Published'),
			'revoked'   => T_('Revoked'),
			'ignored'   => T_('Ignored'),
		);
}


/**
 * Get keyword status title by value
 *
 * @return string
 */
function ca_get_keyword_status_title( $value )
{
	$statuses = ca_get_keyword_statuses();

	return isset( $statuses[ $value ] ) ? $statuses[ $value ] : $value;
}


/**
 * Get statuses for source
 *
 * @return array
 */
function ca_get_source_statuses()
{
	global $central_antispam_Module;

	return array(
			'trusted'   => T_('Trusted'),
			'promising' => T_('Promising'),
			'unknown'   => T_('Unknown'),
			'suspect'   => T_('Suspect'),
			'blocked'   => T_('Blocked'),
		);
}


/**
 * Get source status title by value
 *
 * @return string
 */
function ca_get_source_status_title( $value )
{
	$statuses = ca_get_source_statuses();

	return isset( $statuses[ $value ] ) ? $statuses[ $value ] : $value;
}


/**
 * Get status colors of central antispam keyword
 *
 * @return array Color values
 */
function ca_get_keyword_status_colors()
{
	return array(
			'new'       => '5bc0de',
			'published' => 'f0ad4e',
			'revoked'   => '333333',
			'ignored'   => '00cc00',
		);
}


/**
 * Get status color of central antispam keyword by status value
 *
 * @param string Status value
 * @return string Color value
 */
function ca_get_keyword_status_color( $status )
{
	if( $status == 'NULL' )
	{
		$status = '';
	}

	$ca_keyword_status_colors = ca_get_keyword_status_colors();

	return isset( $ca_keyword_status_colors[ $status ] ) ? '#'.$ca_keyword_status_colors[ $status ] : 'none';
}


/**
 * Get status colors of central antispam source
 *
 * @return array Color values
 */
function ca_get_source_status_colors()
{
	return array(
			'trusted'   => '00cc00',
			'promising' => 'f0ad4e',
			'unknown'   => '999999',
			'suspect'   => 'ff6600',
			'blocked'   => 'ff0000',
		);
}


/**
 * Get status color of central antispam source by status value
 *
 * @param string Status value
 * @return string Color value
 */
function ca_get_source_status_color( $status )
{
	if( $status == 'NULL' )
	{
		$status = '';
	}

	$ca_source_status_colors = ca_get_source_status_colors();

	return isset( $ca_source_status_colors[ $status ] ) ? '#'.$ca_source_status_colors[ $status ] : 'none';
}
?>