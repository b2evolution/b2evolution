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
			'new'       => $central_antispam_Module->T_('New'),
			'published' => $central_antispam_Module->T_('Published'),
			'revoked'   => $central_antispam_Module->T_('Revoked'),
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
			'trusted'   => $central_antispam_Module->T_('Trusted'),
			'promising' => $central_antispam_Module->T_('Promising'),
			'unknown'   => $central_antispam_Module->T_('Unknown'),
			'suspect'   => $central_antispam_Module->T_('Suspect'),
			'blocked'   => $central_antispam_Module->T_('Blocked'),
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
?>