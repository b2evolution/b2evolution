<?php
/**
 * This file implements the UI view for Emails > Newsletters > Campaigns
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $edited_Newsletter;

if( $edited_Newsletter->ID > 0 )
{	// Display campaigns attached to this Newsletter:
	campaign_results_block( array(
			'enlt_ID'               => $edited_Newsletter->ID,
		) );
}