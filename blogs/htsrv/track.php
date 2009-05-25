<?php
/**
 * This is the goal tracker + redirect handler.
 *
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package sessions
 *
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
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

$sql = 'SELECT *
					FROM T_track__goal
				 WHERE goal_key = '.$DB->quote($key);

$Goal = $DB->get_row( $sql );

if( empty($Goal) )
{
	require $skins_path.'_404_not_found.main.php'; // error & exit
	exit(0);
}

if( !empty($Goal->goal_redir_url) )
{	// TODO adapt and use header_redirect()

	$redir_url = $Goal->goal_redir_url;

	if( preg_match( '/\$([a-z_]+)\$/i', $redir_url, $matches ) )
	{	// We want to replace a special code like $hit_ID$ in the redir URL:
		// Tblue> What about using preg_replace_callback() to do this?
		// echo $matches[1];
		switch( $matches[1] )
		{
			case 'hit_ID':
				// We need to log the HIT now! Because we need the hit ID!
				$Hit->log();
				$redir_url = str_replace( '$hit_ID$', $Hit->ID, $redir_url );
				break;
		}
	}

	header( 'HTTP/1.1 302 Found' );
	header( 'Location: '.$redir_url, true, 302 ); // explictly setting the status is required for (fast)cgi
	echo str_pad( '', 1024 );
	flush();
	// At this point Firefox 2 will redirect without waiting for the end of the page, but IE7 will not :/
}
else
{	// No redirection specified, we send a blank pixel instead:
	$blank_gif = $rsc_path.'img/blank.gif';

 	header('Content-type: image/gif' );
	header('Content-Length: '.filesize( $blank_gif ) );
	readfile( $blank_gif );
	flush();
}

// We need to log the HIT now! Because we need the hit ID!
$Hit->log();

// pre_dump($Hit);

$extra_params = '';
if( isset( $_SERVER['QUERY_STRING'] ) )
{
	$extra_params = '&'.$_SERVER['QUERY_STRING'].'&';
	$extra_params = str_replace( '&key='.$key.'&', '&', $extra_params );
	$extra_params = trim( $extra_params, '&' );
}


// Record a goal hit:
$sql = 'INSERT INTO T_track__goalhit( ghit_goal_ID, ghit_hit_ID, ghit_params )
				VALUES( '.$Goal->goal_ID.', '.$Hit->ID.', '.$DB->quote($extra_params).' )';
$DB->query( $sql );



/*
 * $Log$
 * Revision 1.2  2009/05/25 19:37:50  tblue246
 * Doc/question
 *
 * Revision 1.1  2009/05/25 19:11:58  fplanque
 * Added goal tracke
 *
 */
?>
