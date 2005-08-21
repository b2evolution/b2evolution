<?php
/**
 * Register a new user.
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
 * }}
 *
 * @package htsrv
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: François PLANQUE
 *
 * @version $Id$
 */

/**
 * Includes:
 */
require_once dirname(__FILE__).'/../conf/_config.php';
require_once dirname(__FILE__).'/'.$htsrv_dirout.$core_subdir.'_main.inc.php';


param( 'action', 'string', '' );
param( 'yourname', 'string', '' );
param( 'email', 'string', '' );
param( 'locale', 'string', $Settings->get('default_locale') );

locale_activate( $locale );

if(!$Settings->get('newusers_canregister'))
{
	$action = 'disabled';
}

switch( $action )
{
	case 'register':
		/*
		 * Do the registration:
		 */
		param( 'redirect_to', 'string', $admin_url.'b2edit.php' );
		param( 'pass1', 'string', '' );
		param( 'pass2', 'string', '' );

		if( $UserCache->get_by_login( $yourname ) )
		{ // The login is already registered
			$Messages->add( sprintf( T_('The login &laquo;%s&raquo; is already registered, please choose another one.'), $yourname ), 'error' );
			break;
		}

		/* I don't want to mess with your user cache so the following doesn't exists
		
		if ( $UserCache->get_by_email( $email ) )
		{ //	The email is already registered
			//	Should email be checked against the blacklist ?

			$Messages->add( sprintf( T_('The email &quote;%s&quote; is already registered, if you have forgotten your password use the link below.') , $email ), 'error' );
			break;
		}
		
		*/
		
		// Replicate above function
		global $DB;
		if( $row = $DB->get_row( 'SELECT *
																FROM T_users
																	WHERE user_email = "'.$DB->escape($email).'"', 0, 0, 'Get User email' ) )
		{ 
			$Messages->add( sprintf( T_('The email &quote;%s&quote; is already registered, if you have forgotten your password use the link below.') , $email ), 'error' ) ;
			break;
		}
		
		if( !$Messages->count( 'error' ) )
		{	// We have a unique login and email
			// Build and send confirmation email

			// Get activation key seed
			
			if ( ! ( $activation_key = $Settings->get('activation_key') ) )
			{	// no activation key so create one
				$Settings->set('activation_key' , md5( $yourname ) );
			}

			//	build the email
			$theMessage = $Settings->get('conf_email');
			$theMessage = str_replace( array( '[name]' , '[link]' ) ,array( $yourname , $htsrv_url.'/confirm.php?action=activate&key='.md5($activation_key.$email) ) ,$theMessage);
		
			//	send the email
			send_mail( $email , T_('Confirm your blog registration' ) , $theMessage , $notify_from );

			// Display confirmation screen:
			require( dirname(__FILE__).'/_wait_confirmation.php' );
			exit();
		}
		
		break;

	case 'disabled':
		/*
		 * Registration disabled:
		 */
		require( dirname(__FILE__).'/_reg_disabled.php' );

		exit();
}


/*
 * Default: registration form:
 */
param( 'redirect_to', 'string', $admin_url.'b2edit.php' );
// Display reg form:
require( dirname(__FILE__).'/_reg_form.php' );

?>
