<?php
/**
 * This file implements automation functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Get array of status titles for automation
 *
 * @return array Status titles
 */
function autm_get_status_titles()
{
	return array(
			'paused' => T_('Paused'),
			'active' => T_('Active'),
		);
}


/**
 * Get status title of automation by status value
 *
 * @param string Status value
 * @return string Status title
 */
function autm_get_status_title( $status )
{
	$statuses = autm_get_status_titles();

	return isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status;
}


/**
 * Get array of type titles for automation step
 *
 * @return array Status titles
 */
function step_get_type_titles()
{
	return array(
			'if_condition'  => T_('IF Condition'),
			'send_campaign' => T_('Send Campaign'),
		);
}


/**
 * Get type title of automation step by type value
 *
 * @param string Status value
 * @return string Status title
 */
function step_get_type_title( $type )
{
	$types = step_get_type_titles();

	return isset( $types[ $type ] ) ? $types[ $type ] : $type;
}


/**
 * Helper function to display step info on Results table
 *
 * @param string Step label
 * @param string Step type
 * @return string
 */
function step_td_label( $step_label, $step_type )
{
	return ( empty( $step_label ) ? step_get_type_title( $step_type ) : $step_label );
}


/**
 * Helper function to display step info on Results table
 *
 * @param integer Next step ID
 * @param integer Next step delay
 * @return string
 */
function step_td_next_step( $next_step_ID, $next_step_delay )
{
	if( empty( $next_step_ID ) )
	{	// No defined next step:
		return '';
	}

	return $next_step_ID.' '.seconds_to_period( $next_step_delay );
}
?>