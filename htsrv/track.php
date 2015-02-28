<?php
/**
 * This is the goal tracker + redirect handler.
 *
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package sessions
 *
 * @author fplanque: Francois PLANQUE.
 */

/**
 * @global Hit
 */
global $Hit;

/**
 * Do the MAIN initializations:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

/**
 * HEAVY :(
 */
require_once $inc_path.'_main.inc.php';

param( 'key', 'string', '' );

$GoalCache = & get_GoalCache();
$Goal = & $GoalCache->get_by_name( $key, false, false );

if( empty( $Goal ) )
{ // Goal key doesn't exist in DB
	load_funcs( 'skins/_skin.funcs.php' );
	require $siteskins_path.'_404_basic_not_found.main.php'; // error & exit
	exit(0);
}

if( ! empty( $Goal->redir_url ) || ! empty( $Goal->temp_redir_url ) )
{ // TODO adapt and use header_redirect()

	$redir_url = $Goal->get_active_url();

	if( preg_match( '/\$([a-z_]+)\$/i', $redir_url, $matches ) )
	{ // We want to replace a special code like $hit_ID$ in the redir URL:
		// Tblue> What about using preg_replace_callback() to do this?
		switch( $matches[1] )
		{
			case 'hit_ID':
				// We need to log the HIT now! Because we need the hit ID!
				$Hit->log();
				$redir_url = str_replace( '$hit_ID$', $Hit->ID, $redir_url );
				break;
		}
	}

	header_http_response( '302 Found' );
	header( 'Location: '.$redir_url, true, 302 ); // explictly setting the status is required for (fast)cgi
	// TODO: dh> str_repeat won't be enough (when gzipped), see http://core.trac.wordpress.org/ticket/8942
	//           should be probably a more general function and get used in e.g. bad_request_die(), too (if necessary)
	echo str_repeat( ' ', 1024 );
	evo_flush();
	// At this point Firefox 2 will redirect without waiting for the end of the page, but IE7 will not :/
}
else
{	// No redirection specified, we send a blank pixel instead:
	load_funcs( '_core/_template.funcs.php' );
	$blank_gif = $rsc_path.'img/blank.gif';

	header( 'Content-type: image/gif' );
	header( 'Content-Length: '.filesize( $blank_gif ) );
	header_nocache();
	readfile( $blank_gif );
	evo_flush();
}

// Record a goal hit:
$Goal->record_hit();
?>