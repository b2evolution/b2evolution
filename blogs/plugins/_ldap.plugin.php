<?php
/**
 * This file implements the LDAP authentification plugin.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @package plugins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**
 * LDAP Plugin
 *
 * @package plugins
 */
class ldap_plugin extends Plugin
{
	/*
	 * LDAP config
	 * TODO: move to plugin settings.
	 */
	/**
	 * @var string The LDAP server.
	 */
	var $ldap_server = 'exchange.excample.com';
	/**
	 * The LDAP RDN, used to bind to the server.
	 * <code>%s</code> gets replaced by the user login.
	 * @var string
	 */
	var $ldap_rdn = 'cn=%s,ou=<Organisation Unit>,o=<Organisation>';
	/**
	 * The LDAP base DN, used as base dn for search.
	 * @var string
	 */
	var $ldap_base_dn = 'cn=Recipients,ou=<Organisation Unit>,o=<Organisation>';
	/**
	 * The search filter used to get information about the user.
	 * <code>%s</code> gets replaced by the user login.
	 * @var string
	 */
	var $ldap_searchFilter = 'rdn=%s';


	var $code = 'evo_ldap';
	var $priority = 50;
	var $version = 'CVS $Revision$';
	var $author = 'The b2evo Group';
	var $help_url = 'http://b2evolution.net/'; // TODO: create /man page
	var $is_tool = false;

	var $apply_when = 'never';
	var $apply_to_html = false;
	var $apply_to_xml = false;


	/**
	 * Constructor.
	 */
	function ldap_plugin()
	{
		$this->name = T_('LDAP authentication');
		$this->short_desc = T_('Creates users if they could be authenticated through LDAP.');
		#$this->long_desc = T_('');
	}


	/**
	 * Event handler: called when a user attemps to login.
	 *
	 * This function will check if the user is in the LDAP and create it locally if it does
	 * not exist yet.
	 *
	 * @todo Plugin settings: user group and other settings
	 *
	 * @param array 'login', 'pass' and 'pass_md5'
	 */
	function LoginAttempt( $params )
	{
		global $Debuglog, $localtimenow;
		global $UserCache, $GroupCache, $Settings, $Hit;

		if( $LocalUser =& $UserCache->get_by_login( $params['login'] )
				&& $LocalUser->pass == $params['pass_md5'] )
		{ // User exist (with this password), do nothing
			return true;
		}
		else
		{ // authenticate against LDAP
			if( !function_exists( 'ldap_connect' ) )
			{
				$Debuglog->add( 'LDAP does not seem to be compiled into PHP.', 'ldap_plugin' );
				return false;
			}

			if( !($ldapconn = @ldap_connect( $this->ldap_server )) )
			{
				$Debuglog->add( 'Could not connect to LDAP server &laquo;'.$this->ldap_server.'&raquo;!', 'ldap_plugin' );
				return false;
			}

			$this->ldap_rdn = sprintf( $this->ldap_rdn, $params['login'] );

			$Debuglog->add( 'Using rdn &laquo;'.$this->ldap_rdn.'&raquo;..', 'ldap_plugin' );

			if( !@ldap_bind($ldapconn, $this->ldap_rdn, $params['pass']) )
			{
				$Debuglog->add( 'Could not bind to LDAP server!', 'ldap_plugin' );
				return false;
			}

			$searchResult = ldap_search( $ldapconn,
																		$this->ldap_base_dn,
																		sprintf( $this->ldap_searchFilter, $params['login'] ) );

			$searchInfo = ldap_get_entries($ldapconn, $searchResult);

			if( $searchInfo['count'] != 1 )
			{ // nicht nur ein bzw kein Eintrag gefunden
				$Debuglog->add( 'Found '.$searchInfo['count'].' entries with search!', 'ldap_plugin' );

				/*
				for ($i=0; $i<$searchInfo["count"]; $i++) {
					echo "dn: ". $searchInfo[$i]["dn"] ."<br>";
					echo "first cn entry: ". $searchInfo[$i]["cn"][0] ."<br>";
					echo "first email entry: ". $searchInfo[$i]["mail"][0] ."<p>";
				}
				*/
			}
			#pre_dump( $searchInfo );


			if( $LocalUser )
			{ // User exists already locally, but password does not match
				$LocalUser->set( 'pass', $params['pass_md5'] );
				$LocalUser->dbupdate();
			}
			else
			{ // create this user locally
				$NewUser = new User();
				$NewUser->set( 'login', $params['login'] );
				$NewUser->set( 'nickname', $params['login'] );
				$NewUser->set( 'pass', $params['pass_md5'] );

				if( isset($searchInfo[0]['givenname'][0]) )
				{
					$NewUser->set( 'firstname', $searchInfo[0]['givenname'][0] );
				}
				if( isset($searchInfo[0]['sn'][0]) )
				{
					$NewUser->set( 'lastname', $searchInfo[0]['sn'][0] );
				}
				if( isset($searchInfo[0]['mail'][0]) )
				{
					$NewUser->set( 'email', $searchInfo[0]['mail'][0] );
				}
				$NewUser->set( 'idmode', 'namefl' );
				$NewUser->set( 'locale', locale_from_httpaccept() ); // use the browser's locale
				#$NewUser->set( 'url', '' );
				#$NewUser->set( 'icq', 0 );
				#$NewUser->set( 'aim', '' );
				#$NewUser->set( 'msn', '' );
				#$NewUser->set( 'yim', '' );
				$NewUser->set( 'ip', $Hit->IP );
				$NewUser->set( 'domain', $Hit->getRemoteHost() );
				$NewUser->set( 'browser', $Hit->getUserAgent() );
				$NewUser->set_datecreated( $localtimenow );
				$NewUser->set( 'level', 1 );
				$NewUser->set( 'notify', 1 );
				$NewUser->set( 'showonline', 1 );

				$UsersGroup =& $GroupCache->get_by_ID($Settings->get('newusers_grp_ID'));
				$NewUser->setGroup( $UsersGroup );

				$NewUser->dbinsert();

				$UserCache->add( $NewUser );
			}
		}
	}
}
?>
