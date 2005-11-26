<?php
/**
 * This is the login screen. It also handles actions related to loggin in and registering.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * Matt FOLLETT grants François PLANQUE the right to license
 * Matt FOLLETT's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package htsrv
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: François PLANQUE
 * @author mfollett: Matt FOLLETT.
 *
 * @todo Actions 'retrievepassword' / 'changepwd': bind this to {@link $Session} rather than to a single IP! (blueyed)
 *
 * @version $Id$
 */

/**
 * Includes:
 */
require_once dirname(__FILE__).'/../conf/_config.php';
require_once dirname(__FILE__).'/'.$htsrv_dirout.$core_subdir.'_main.inc.php';

param( 'action', 'string', '' );
param( 'mode', 'string', '' );
// bookmarklet stuff:
param( 'text', 'html', '' );
param( 'popupurl', 'string', '' );
param( 'popuptitle', 'string', '' );

param( 'login', 'string', '', false, true ); // override
// echo 'login: ', $login;


switch( $action )
{
	case 'logout':
		logout();          // logout $Session and set $current_User = false
		header_nocache();  // defaults to redirect_to param
		header_redirect(); // exits


	case 'lostpassword': // Lost password:
		param( 'redirect_to', 'string', $admin_url );

		// Display retrieval form:
		require dirname(__FILE__).'/_lostpass_form.php';

		exit();


	case 'retrievepassword': // Send passwort change request by mail
		$login_required = true; // Do not display "Without login.." link on the form
		param( 'redirect_to', 'string', $admin_url );

		$ForgetfulUser = & $UserCache->get_by_login( $login );

		if( $ForgetfulUser )
		{ // User exists
			// echo 'email: ', $ForgetfulUser->email;
			// echo 'locale: '.$ForgetfulUser->locale;

			if( $demo_mode && ($ForgetfulUser->login == 'demouser' || $ForgetfulUser->ID == 1) )
			{
				$Messages->add( T_('You cannot reset this account in demo mode.'), 'error' );
				break;
			}

			locale_temp_switch( $ForgetfulUser->locale );

			// DEBUG!
			// echo $message.' (password not set yet, only when sending email does not fail);

			if( empty( $ForgetfulUser->email ) )
			{
				$Messages->add( T_('You have no email address with your profile, therefore we cannot reset your password.')
					.' '.T_('Please try contacting the admin.'), 'error' );
			}
			else
			{
				$requestId = generate_random_key(22);

				$message = T_( 'Somebody (presumably you) has requested a password change for your account.' )
					."\n\n"
					.T_('Login:')." $login\n"
					.T_('Link to change your password:')
					."\n"
					.$htsrv_url.'login.php?action=changepwd'
						.'&login='.rawurlencode( $ForgetfulUser->login )
						.'&reqId='.$requestId
					."\n\n"
					.T_('Please note:')
					.' '.sprintf( T_('For security reasons the link is only valid once for %d hours with the same IP address.'), 2 )
					."\n\n"
					.T_('If it was not you that requested this password change, simply ignore this mail.');

				if( !send_mail( $ForgetfulUser->email, sprintf( T_('Password change request for %s'), $ForgetfulUser->login ), $message, $notify_from ) )
				{
					$Messages->add( T_('The email could not be sent.')
						.'<br />'.T_('Possible reason: your host may have disabled the mail() function...'), 'error' );
				}
				else
				{
					$UserSettings->set(
							'password_change_request',
							serialize( array(
								'requestId' => $requestId,
								'IP' => md5(serialize(getIpList())),
								'time' => $servertimenow ) ),
							$ForgetfulUser->ID );

					$UserSettings->dbupdate();

					$Messages->add( T_('A link to change your password has been sent to your email address.' ), 'success' );
				}
				#pre_dump( $message );
			}

			locale_restore_previous();
		}
		else
		{ // pretend that the email is sent for avoiding guessing user_login
			$Messages->add( T_('A link to change your password has been sent to your email address.' ), 'success' );
		}
		break;


	case 'changepwd': // Clicked "Change password request" link from a mail or submit changed pwd
		param( 'redirect_to', 'string', $admin_url );
		param( 'reqId', 'string', '' );

		$ForgetfulUser = & $UserCache->get_by_login($login);

		if( !$ForgetfulUser || empty($reqId) )
		{ // This was not requested
			$Messages->add( T_('Invalid password change request!'), 'error' );
			break;
		}

		$verifyData = $UserSettings->get( 'password_change_request', $ForgetfulUser->ID );
		$UserSettings->delete( 'password_change_request', $ForgetfulUser->ID );

		$UserSettings->dbupdate();

		if( !$verifyData
				|| !($verifyData = unserialize($verifyData))
				|| !is_array($verifyData)
				|| !isset($verifyData['IP']) || $verifyData['IP'] != md5(serialize(getIpList()))
				|| !isset($verifyData['time']) || $verifyData['time'] < ( $servertimenow - 7200 ) )
		{
			$Messages->add(
				sprintf( T_('Invalid password change request!')
				.' '.T_('For security reasons the link is only valid once for %d hours with the same IP address.'), 2 ), 'error' );
			$Messages->add(
				sprintf( T_('You can <a href="%s">send yourself a new link</a>.'),
				$htsrv_url.'login.php?action=retrievepassword&amp;login='.$login.'&amp;redirect_to='.rawurlencode( $redirect_to ) ), 'note' );

			$login_required = true; // Do not display "Without login.." link on the form
			break;
		}

		header_nocache();

		// Log the user in:
		$Session->set_user_ID( $ForgetfulUser->ID );

		$Messages->add( T_( 'Please change your password to something you remember now.' ), 'note' );

		$action = 'list'; // So that the user will see his profile
		$current_User = & $ForgetfulUser;
		$user_ID = $current_User->ID; // the selected user in the user admin

		/**
		 * Init the backoffice.
		 */
		require_once dirname(__FILE__).'/'.$htsrv_dirout.$admin_subdir.'_header.php';

		$AdminUI->add_headline( '<base href="'.$admin_url.'" />' );
		require( dirname(__FILE__).'/'.$htsrv_dirout.$admin_subdir.'b2users.php' );

		#header( 'Location: '.$baseurl.$admin_subdir.'b2users.php' ); // does not allow to leave a Message and IIS is known to cause problems with setcookie() and redirect.
		exit();

		break;

} // switch


// Default: login form
require dirname(__FILE__).'/_login_form.php';
exit();

?>
