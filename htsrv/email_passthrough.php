<?php
/**
 * This is the handler for email interaction tracking
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */

/**
 * Initialize everything:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

require_once $inc_path.'_main.inc.php';

global $DB, $Session, $modules, $localtimenow;

param( 'type', 'string', true );
param( 'email_key', 'string', true );
param( 'redirect_to', 'url', '' );

switch( $type )
{
	case 'link':
		$DB->query( 'UPDATE T_email__log
				SET emlog_last_click_ts = '.$DB->quote( date2mysql( $localtimenow ) )
				.' WHERE emlog_key = '.$DB->quote( $email_key ) );

		// Redirect
		if( empty( $redirect_to ) )
		{	// If a redirect param was not defined on submitted form then redirect to site url:
			$redirect_to = $baseurl;
		}

		// header_redirect can prevent redirection depending on some advanced settings like $allow_redirects_to_different_domain!
		// header_redirect( $redirect_to, 303 ); // Will EXIT
		header( 'Location: '.$redirect_to, true, 303 ); // explictly setting the status is required for (fast)cgi
		exit(0);
		// We have EXITed already at this point!!
		break;

	case 'img':
		$DB->query( 'UPDATE T_email__log
				SET emlog_last_open_ts = '.$DB->quote( date2mysql( $localtimenow ) )
				.' WHERE emlog_key = '.$DB->quote( $email_key ) );

		if( ! empty( $redirect_to ) )
		{
			// Redirect
			// header_redirect can prevent redirection depending on some advanced settings like $allow_redirects_to_different_domain!
			//header_redirect( $redirect_to, 302 ); // Will EXIT
			header( 'Location: '.$redirect_to, true, 302 ); // explictly setting the status is required for (fast)cgi
			exit(0);
			// We have EXITed already at this point!!
		}
		break;

	default:
		debug_die( 'Invalid email tracking type' );
}
?>