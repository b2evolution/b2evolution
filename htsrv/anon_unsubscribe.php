<?php
/**
 * This is the handler for ANONYMOUS (not logged in) users unsubscribe calls.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */

/**
 * Do the MAIN initializations:
 */
require_once dirname(__FILE__).'/../conf/_config.php';
require_once $inc_path.'_main.inc.php';

global $Session;

header( 'Content-Type: text/html; charset='.$io_charset );

// init anonymous user request params
$type = param( 'type', 'string', true );
$req_ID = param( 'req_ID', 'string', '' );
$anon_email = param( 'anon_email', 'string', '' );

switch( $type )
{
	case 'comment':
		if( !is_email( $anon_email ) )
		{
			$Messages->add( 'Your email address is not correct. Probably the unsubscribe link was modified.' );
			$Messages->display();
			exit(0);
		}

		if( empty( $req_ID ) )
		{ // Clicked to unsubscribe link on email, but unsubscribe is not confirmed yet
			$comment_id = param( 'c', 'integer', 0 );
			$CommentCache = & get_CommentCache();
			$Comment = $CommentCache->get_by_ID( $comment_id, false );
			if( empty( $Comment ) || ( $anon_email != $Comment->get_author_email() ) || ( ! $Comment->get( 'allow_msgform' ) ) )
			{ // invalid request
				$Messages->add( 'Invalid unsubscribe request, or you have already unsubscribed.' );
				$Messages->display();
				exit(0);
			}

			$req_ID = generate_random_key(32);

			$message = sprintf( T_("We have received a request that you do not want to receive emails through\na message form on your comments anymore.\n\nTo confirm that this request is from you, please click on the following link:") )
				."\n\n"
				.get_htsrv_url().'anon_unsubscribe.php?type=comment&anon_email='.$anon_email.'&req_ID='.$req_ID
				."\n\n"
				.T_('Please note:')
				.' '.T_('For security reasons the link is only valid for your current session (by means of your session cookie).')
				."\n\n"
				.T_('If it was not you that requested this, simply ignore this email.');

			if( send_mail( $anon_email, NULL, T_('Confirm opt-out for emails through message form'), $message ) )
			{
				$Messages->add( T_('An email has been sent to you, with a link to confirm your request not to receive emails through the comments you have made on this blog.'), 'success' );
				$Session->set( 'core.msgform.optout_cmt_email', $anon_email );
				$Session->set( 'core.msgform.optout_cmt_reqID', $req_ID );
			}
			elseif( $demo_mode )
			{ // Debug mode restriction: sending email is disabled
				$Messages->add( 'Sorry, could not send email. Sending email in demo mode is disabled.', 'error' );
			}
			else
			{
				$Messages->add( T_('Sorry, could not send email.')
							.'<br />'.T_('Possible reason: the PHP mail() function may have been disabled on the server.'), 'error' );
			}

			$Messages->display();
			exit(0);
		}

		// clicked on link from e-mail
		if( ( $req_ID == $Session->get( 'core.msgform.optout_cmt_reqID' ) ) && ( $anon_email == $Session->get( 'core.msgform.optout_cmt_email' ) ) )
		{ // Update anonymous user comments to not allow msgform
			$DB->query( '
				UPDATE T_comments
				   SET comment_allow_msgform = 0
				 WHERE comment_author_email = '.$DB->quote( utf8_strtolower( $anon_email ) ) );

			$Messages->add( T_('All your comments have been marked not to allow emailing you through a message form.'), 'success' );

			$Session->delete('core.msgform.optout_cmt_email');
			$Session->delete('core.msgform.optout_cmt_reqID');
		}
		else
		{
			$Messages->add( T_('The request not to receive emails through a message form for your comments failed.'), 'error' );
		}

		$Messages->display();
		exit(0);
		// will have exited
	default:
		debug_die( 'Invalid unsubscribe request from anonymous user!' );
		break; // will have exited
}
// will have exited in all circumstances
?>