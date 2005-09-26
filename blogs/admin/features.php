<?php
/**
 * This file implements the UI controller for Global Features.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005 by Halton STEWART - {@link http://hstewart.net/}.
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
 * Halton STEWART grants François PLANQUE the right to license
 * Halton STEWART's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author halton: Halton STEWART.
 * @author fplanque: François PLANQUE.
 * @author blueyed: Daniel HAHLER.
 *
 * @version $Id$
 */

/**
 * Includes:
 */
require( dirname(__FILE__).'/_header.php' );

$AdminUI->setPath( 'options', 'features' );

param( 'action', 'string' );

switch( $action )
{
	case 'update':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		param( 'submit', 'string', '' ); // TODO: use array based submit value like name="submit[set_defaults]"
		if( $submit == T_('Restore defaults') )
		{
			/*
			// TODO: insert some default settings rather than just delete them all, as per original configuration in the _advanced.php file:
			# mailserver settings
			$mailserver_url = 'mail.example.com';
			$mailserver_login = 'login@example.com';
			$mailserver_pass = 'password';
			$mailserver_port = 110;
			# by default posts will have this category
			$default_category = 1;
			# subject prefix
			$subjectprefix = 'blog:';
			# body terminator string (starting from this string, everything will be ignored, including this string)
			$bodyterminator = "___";
			# set this to 1 to run in test mode
			$thisisforfunonly = 0;
			### Special Configuration for some phone email services
			# some mobile phone email services will send identical subject & content on the same line
			# if you use such a service, set $use_phoneemail to 1, and indicate a separator string
			# when you compose your message, you'll type your subject then the separator string
			# then you type your login:password, then the separator, then content
			$use_phoneemail = 0;
			$phoneemail_separator = ':::';
			*/

			$Settings->deleteArray( array( 'eblog_enabled', 'eblog_method', 'eblog_server_host', 'eblog_server_port', 'eblog_username', 'eblog_password', 'eblog_default_category', 'eblog_subject_prefix','webhelp_enabled' ) );

			if( $Settings->updateDB() )
			{
				$Messages->add( T_('Restored default values.'), 'success' );
			}
			else
			{
				$Messages->add( T_('Settings have not changed.'), 'note' );
			}
		}
		else
		{
			// Online help
			$Request->param( 'webhelp_enabled', 'integer', 0 );
			$Settings->set( 'webhelp_enabled', $webhelp_enabled );


			// Blog by email
			$Request->param( 'eblog_enabled', 'integer', 0 );
			$Settings->set( 'eblog_enabled', $eblog_enabled );

			$Request->param( 'eblog_method', 'string', true );
			$Settings->set( 'eblog_method', strtolower(trim($eblog_method)));

			$Request->param( 'eblog_server_host', 'string', true );
			$Settings->set( 'eblog_server_host', strtolower(trim($eblog_server_host)));

			$Request->param( 'eblog_server_port', 'integer', 0 );
			$Settings->set( 'eblog_server_port', $eblog_server_port );

			$Request->param( 'eblog_username', 'string', true );
			$Settings->set( 'eblog_username', trim($eblog_username));

			$Request->param( 'eblog_password', 'string', true );
			$Settings->set( 'eblog_password', trim($eblog_password));

			$Request->param( 'eblog_default_category', 'integer', 0 );
			$Settings->set( 'eblog_default_category', $eblog_default_category );

			$Request->param( 'eblog_subject_prefix', 'string', true );
			$Settings->set( 'eblog_subject_prefix', trim($eblog_subject_prefix) );

			$Request->param( 'eblog_body_terminator', 'string', true );
			$Settings->set( 'eblog_body_terminator', trim($eblog_body_terminator) );

			$Request->param( 'eblog_test_mode', 'integer', 0 );
			$Settings->set( 'eblog_test_mode', $eblog_test_mode );

			$Request->param( 'eblog_phonemail', 'integer', 0 );
			$Settings->set( 'eblog_phonemail', $eblog_phonemail );

			$Request->param( 'eblog_phonemail_separator', 'string', true );
			$Settings->set( 'eblog_phonemail_separator', trim($eblog_phonemail_separator) );

			// Statistics
			$Request->param( 'hit_doublecheck_referer', 'integer', 0 );
			$Settings->set( 'hit_doublecheck_referer', $hit_doublecheck_referer );


			if( ! $Messages->count('error') )
			{
				if( $Settings->updateDB() )
				{
					$Messages->add( T_('Settings updated.'), 'success' );
				}
				else
				{
					$Messages->add( T_('Settings have not changed.'), 'note' );
				}
			}

		}

		break;
}

/**
 * Display page header:
 */
require dirname(__FILE__).'/_menutop.php';

// Check permission to view:
$current_User->check_perm( 'options', 'view', true );

// Begin payload block:
$AdminUI->dispPayloadBegin();

require dirname(__FILE__).'/_set_features.form.php';

// End payload block:
$AdminUI->dispPayloadEnd();

require dirname(__FILE__).'/_footer.php';
?>