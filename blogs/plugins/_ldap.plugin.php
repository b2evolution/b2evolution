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
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * LDAP authentification plugin.
 *
 * It handles the event 'LoginAttempt' and creates an user locally if it
 * could bind to the LDAP server with the login and password of the user that
 * is trying to login.
 * It will update the password locally in case it differs from the LDAP one.
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
	 * A list of search sets ('server', 'rdn', 'base_dn', 'search_filter').
	 *
	 * 'server': The LDAP server
	 * 'rdn': The LDAP RDN, used to bind to the server (%s gets replaced by the user login)
	 * 'base_dn': The LDAP base DN, used as base dn for search.
	 * 'search_filter': The search filter used to get information about the user (%s gets replaced by the user login).
	 *
	 * @var array
	 */
	var $ldap_search_sets = array(
			array(
				'server' => 'exchange.excample.com',
				'rdn' => 'cn=%s,ou=<Organisation Unit>,o=<Organisation>',
				'base_dn' => 'cn=Recipients,ou=<Organisation Unit>,o=<Organisation>',
				'search_filter' => 'rdn=%s',
			),
			// You could add other sets here..
		);

	/**
	 * If set, the search information / result will be looked up for this key.
	 * If there exists a group by that name the user gets assigned to this
	 * group.
	 *
	 * A good example might be 'department'.
	 *
	 * @var string Empty to disable assignment of Groups by search info.
	 */
	var $assign_user_to_group_by = '';

	/**
	 * If you want to automagically create a new Group when there does not exist
	 * one yet with a name equal to the user's value of {@link $assign_user_to_group_by}
	 * you can define a Group name here, that gets used as a template for a new
	 * Group to create.
	 *
	 * You could create a group 'LDAP_Template' for example and configure it here
	 * to be used as template for new groups to be created.
	 *
	 * @var string Empty to disable creating new Groups.
	 */
	var $template_group_name_for_unmatched_assign = '';

	/**
	 * The default (fallback) Group name for created users.
	 *
	 * If empty (or does not exist), the default group for new users
	 * (from Settings) gets used.
	 *
	 * @var string Empty to use default group for new users.
	 */
	var $default_group_name = '';


	var $code = 'evo_ldapa';
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

		if( $local_User = & $UserCache->get_by_login( $params['login'] )
				&& $local_User->pass == $params['pass_md5'] )
		{ // User exist (with this password), do nothing
			$Debuglog->add( 'User already exists locally with this password.', 'plugin_ldap' );
			return true;
		}

		if( empty($this->ldap_search_sets) )
		{
			$Debuglog->add( 'No LDAP search sets defined.', 'plugin_ldap' );
			return false;
		}

		// Authenticate against LDAP
		if( !function_exists( 'ldap_connect' ) )
		{
			$Debuglog->add( 'LDAP does not seem to be compiled into PHP.', array('plugin_ldap', 'error') );
			return false;
		}

		// Loop through list of search sets
		foreach( $this->ldap_search_sets as $l_set )
		{
			if( !($ldap_conn = @ldap_connect( $l_set['server'] )) )
			{
				$Debuglog->add( 'Could not connect to LDAP server &laquo;'.$l_set['server'].'&raquo;!', 'plugin_ldap' );

				continue;
			}
			$Debuglog->add( 'Connected to server &laquo;'.$l_set['server'].'&raquo;..', 'plugin_ldap' );

			$ldap_rdn = sprintf( $l_set['rdn'], $params['login'] );
			$Debuglog->add( 'Using rdn &laquo;'.$ldap_rdn.'&raquo;..', 'plugin_ldap' );

			if( !@ldap_bind($ldap_conn, $ldap_rdn, $params['pass']) )
			{
				$Debuglog->add( 'Could not bind to LDAP server!', 'plugin_ldap' );

				continue;
			}

			$Debuglog->add( 'User successfully bound to server.', 'plugin_ldap' );

			// Search user info
			$search_result = ldap_search(
					$ldap_conn,
					$l_set['base_dn'],
					sprintf( $l_set['search_filter'], $params['login'] ) );

			$search_info = ldap_get_entries($ldap_conn, $search_result);

			if( $search_info['count'] != 1 )
			{ // nicht nur ein bzw kein Eintrag gefunden
				$Debuglog->add( 'Found '.$search_info['count'].' entries with search!', 'plugin_ldap' );

				/*
				for ($i=0; $i<$search_info["count"]; $i++) {
					echo "dn: ". $search_info[$i]["dn"] ."<br>";
					echo "first cn entry: ". $search_info[$i]["cn"][0] ."<br>";
					echo "first email entry: ". $search_info[$i]["mail"][0] ."<p>";
				}
				*/
			}
			$Debuglog->add( 'search_info: <pre>'.var_export( $search_info, true ).'</pre>', 'plugin_ldap' );
			#pre_dump( $search_info );


			if( $local_User )
			{ // User exists already locally, but password does not match the LDAP one. Update it locally.
				$local_User->set( 'pass', $params['pass_md5'] );
				$local_User->dbupdate();

				$Debuglog->add( 'Updating user password locally.', 'plugin_ldap' );
			}
			else
			{ // create this user locally
				$NewUser = new User();
				$NewUser->set( 'login', $params['login'] );
				$NewUser->set( 'nickname', $params['login'] );
				$NewUser->set( 'pass', $params['pass_md5'] );

				if( isset($search_info[0]['givenname'][0]) )
				{
					$NewUser->set( 'firstname', $search_info[0]['givenname'][0] );
				}
				if( isset($search_info[0]['sn'][0]) )
				{
					$NewUser->set( 'lastname', $search_info[0]['sn'][0] );
				}
				if( isset($search_info[0]['mail'][0]) )
				{
					$NewUser->set( 'email', $search_info[0]['mail'][0] );
				}
				$NewUser->set( 'idmode', 'namefl' );
				$NewUser->set( 'locale', locale_from_httpaccept() ); // use the browser's locale
				#$NewUser->set( 'url', '' );
				#$NewUser->set( 'icq', 0 );
				#$NewUser->set( 'aim', '' );
				#$NewUser->set( 'msn', '' );
				#$NewUser->set( 'yim', '' );
				$NewUser->set( 'ip', $Hit->IP );
				$NewUser->set( 'domain', $Hit->get_remote_host() );
				$NewUser->set( 'browser', $Hit->user_agent );
				$NewUser->set_datecreated( $localtimenow );
				$NewUser->set( 'level', 1 );
				$NewUser->set( 'notify', 1 );
				$NewUser->set( 'showonline', 1 );

				$assigned_group = false;
				if( !empty($this->assign_user_to_group_by) )
				{
					$Debuglog->add( 'We want to assign the Group by &laquo;'.$this->assign_user_to_group_by.'&raquo;', 'plugin_ldap' );
					if( isset($search_info[0][$this->assign_user_to_group_by])
					    && isset($search_info[0][$this->assign_user_to_group_by][0]) )
					{ // There is info we want to assign by
						$assign_by_value = $search_info[0][$this->assign_user_to_group_by][0];
						$Debuglog->add( 'The users info has &laquo;'.$assign_by_value.'&raquo; as value given.', 'plugin_ldap' );

						if( $users_Group = & $GroupCache->get_by_name( $assign_by_value, false ) )
						{ // A group with the users value returned exists.
							$NewUser->setGroup( $users_Group );
							$assigned_group = true;
							$Debuglog->add( 'Adding User to existing Group.', 'plugin_ldap' );
						}
						elseif( !empty($this->template_group_name_for_unmatched_assign) )
						{ // we want to create a new group matching the assign-by info
							$Debuglog->add( 'Group with that name does not exist yet.', 'plugin_ldap' );

							if( $new_Group = $GroupCache->get_by_name($this->template_group_name_for_unmatched_assign) ) // COPY!
							{ // take a copy of the Group to use as template
								$Debuglog->add( 'Using Group &laquo;'.$this->template_group_name_for_unmatched_assign.'&raquo; as template.', 'plugin_ldap' );
								$new_Group->set( 'ID', 0 ); // unset ID (to allow inserting)
								$new_Group->set( 'name', $assign_by_value ); // set the wanted name
								$new_Group->dbinsert();
								$Debuglog->add( 'Created Group &laquo;'.$new_Group->get('name').'&raquo;', 'plugin_ldap' );
								$Debuglog->add( 'Assigned User to new Group.', 'plugin_ldap' );

								$NewUser->setGroup( $new_Group );
								$assigned_group = true;
							}
							else
							{
								$Debuglog->add( 'Template Group &laquo;'.$this->template_group_name_for_unmatched_assign.'&raquo; not found!', 'plugin_ldap' );
							}
						}
					}
				}

				if( ! $assigned_group )
				{ // Default group
					$users_Group = NULL;
					if( !empty($this->default_group_name) )
					{
						$users_Group = & $GroupCache->get_by_name($this->default_group_name);
					}

					if( ! $users_Group )
					{ // either $this->default_group_name is not given or wrong
						$users_Group = $GroupCache->get_by_ID( $Settings->get('newusers_grp_ID') );
					}

					$NewUser->setGroup( $users_Group );
				}

				$NewUser->dbinsert();

				$UserCache->add( $NewUser );
			}
			return true;
		}

		return false;
	}
}
?>