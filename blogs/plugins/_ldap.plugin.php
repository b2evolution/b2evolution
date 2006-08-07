<?php
/**
 * This file implements the LDAP authentification plugin.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}.
 *
 * Documentation can be found at {@link http://manual.b2evolution.net/Plugins/ldap_plugin}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package plugins
 *
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
 * @todo Register tools tab to search in LDAP (blueyed).
 * @todo Add setting subsets, which allow to map User object properties (dropdown) to LDAP search result entries (what's now hardcoded with "sn", "givenname" and "email")
 *
 * @package plugins
 */
class ldap_plugin extends Plugin
{
	var $code = 'evo_ldap_auth';
	var $priority = 50;
	var $version = '1.9-dev';
	var $author = 'dAniel hAhler';


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->name = T_('LDAP authentication');
		$this->short_desc = T_('Creates users if they could be authenticated through LDAP.');
	}


	function GetDefaultSettings()
	{
		global $Settings;

		return array(
			'search_sets' => array(
				'label' => T_('LDAP server sets'),
				'note' => T_('LDAP server sets to search.'),
				'type' => 'array',
				'max_count' => 10,
				'entries' => array(
					'server' => array(
						'label' => T_('Server'),
						'note' => T_('The LDAP server (hostname with or without port).').' '.sprintf( T_('E.g. &laquo;%s&raquo;'), 'hostname:389' ),
						'size' => 30,
					),
					'rdn' => array(
						'label' => T_('RDN'),
						'note' => T_('The LDAP RDN, used to bind to the server (%s gets replaced by the user login).').' '.sprintf( T_('E.g. &laquo;%s&raquo;'), 'cn=%s,ou=organization unit,o=Organisation' ),
						'size' => 40,
					),
					'base_dn' => array(
						'label' => T_('Base DN'),
						'note' => T_('The LDAP base DN, used as base dn for search.').' '.sprintf( T_('E.g. &laquo;%s&raquo;'), 'cn=Recipients,ou=organization unit,o=Organisation' ),
						'size' => 40,
					),
					'search_filter' => array(
						'label' => T_('Search filter'),
						'note' => T_('The search filter used to get information about the user (%s gets replaced by the user login).').' '.sprintf( T_('E.g. &laquo;%s&raquo;'), 'uid=%s' ),
						'size' => 40,
					),
					'assign_user_to_group_by' => array(
						'label' => T_('Assign group by'),
						'note' => T_('LDAP search result key to assign the group by.').' '.sprintf( T_('E.g. &laquo;%s&raquo;'), 'department' ),
						'size' => 30,
					),
					'tpl_new_grp_ID' => array(
						'label' => T_('Template Group for new'),
						'type' => 'select_group',
						'note' => T_('The group to use as template, if we create a new group. Set this to "None" to not create new groups.'),
						'allow_none' => true,
					),
				),
			),

			'fallback_grp_ID' => array(
				'label' => T_('Default group'),
				'type' => 'select_group',
				'note' => T_('The group to use as fallback, if we do not want to create a new group. "None" to not a create a new user in that case.' ),
				'allow_none' => true,
				'defaultvalue' => $Settings->get('newusers_grp_ID'),
			),
		);
	}


	/**
	 * Event handler: called when a user attemps to login.
	 *
	 * This function will check if the user is in the LDAP and create it locally if it does
	 * not exist yet.
	 *
	 * @param array 'login', 'pass' and 'pass_md5'
	 */
	function LoginAttempt( $params )
	{
		global $localtimenow;
		global $UserCache, $GroupCache, $Settings, $Hit;

		if( ( $local_User = & $UserCache->get_by_login( $params['login'] ) )
				&& $local_User->pass == $params['pass_md5'] )
		{ // User exist (with this password), do nothing
			$this->debug_log( 'User already exists locally with this password.' );
			return true;
		}

		$search_sets = $this->Settings->get( 'search_sets' );

		if( empty($search_sets) )
		{
			$this->debug_log( 'No LDAP search sets defined.' );
			return false;
		}

		// Authenticate against LDAP
		if( !function_exists( 'ldap_connect' ) )
		{
			$this->debug_log( 'LDAP does not seem to be compiled into PHP.' );
			return false;
		}

		// Loop through list of search sets
		foreach( $search_sets as $l_set )
		{

			$server_port = explode(':', $l_set['server']);
			$server = $server_port[0];
			$port = isset($server_port[1]) ? $server_port[1] : 389;

			if( !($ldap_conn = @ldap_connect( $server, $port )) )
			{
				$this->debug_log( 'Could not connect to LDAP server &laquo;'.$server.':'.$port.'&raquo;!' );
				continue;
			}
			$this->debug_log( 'Connected to server &laquo;'.$server.':'.$port.'&raquo;..' );

			$ldap_rdn = str_replace( '%s', $params['login'], $l_set['rdn'] );
			$this->debug_log( 'Using rdn &laquo;'.$ldap_rdn.'&raquo;..' );

			if( !@ldap_bind($ldap_conn, $ldap_rdn, $params['pass']) )
			{
				$this->debug_log( 'Could not bind to LDAP server!' );
				continue;
			}

			$this->debug_log( 'User successfully bound to server.' );

			// Search user info
			$search_result = ldap_search(
					$ldap_conn,
					$l_set['base_dn'],
					str_replace( '%s', $params['login'], $l_set['search_filter'] ) );

			$search_info = ldap_get_entries($ldap_conn, $search_result);

			if( $search_info['count'] != 1 )
			{ // nicht nur ein bzw kein Eintrag gefunden
				$this->debug_log( 'Found '.$search_info['count'].' entries with search!' );

				/*
				for ($i=0; $i<$search_info["count"]; $i++) {
					echo "dn: ". $search_info[$i]["dn"] ."<br>";
					echo "first cn entry: ". $search_info[$i]["cn"][0] ."<br>";
					echo "first email entry: ". $search_info[$i]["mail"][0] ."<p>";
				}
				*/
			}
			$this->debug_log( 'search_info: <pre>'.var_export( $search_info, true ).'</pre>' );
			#pre_dump( $search_info );


			if( $local_User )
			{ // User exists already locally, but password does not match the LDAP one. Update it locally.
				$local_User->set( 'pass', $params['pass_md5'] );
				$local_User->dbupdate();

				$this->debug_log( 'Updating user password locally.' );

				return true;
			}

			// create this user locally (in b2evo)
			$NewUser = new User();
			$NewUser->set( 'login', $params['login'] );
			$NewUser->set( 'nickname', $params['login'] );
			$NewUser->set( 'pass', $params['pass_md5'] );
			$NewUser->set( 'validated', 1 ); // assume the user has been validated (through email link)

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
				$NewUser->set_email( $search_info[0]['mail'][0] );
			}
			$NewUser->set( 'idmode', 'namefl' );
			$NewUser->set( 'locale', locale_from_httpaccept() ); // use the browser's locale
			#$NewUser->set( 'url', '' );
			#$NewUser->set( 'icq', 0 );
			#$NewUser->set( 'aim', '' );
			#$NewUser->set( 'msn', '' );
			#$NewUser->set( 'yim', '' );
			$NewUser->set( 'ip', $Hit->IP );
			$NewUser->set( 'domain', $Hit->get_remote_host( true ) );
			$NewUser->set( 'browser', $Hit->user_agent );
			$NewUser->set_datecreated( $localtimenow );
			$NewUser->set( 'level', 1 );
			$NewUser->set( 'notify', 1 );
			$NewUser->set( 'showonline', 1 );

			$assigned_group = false;
			if( ! empty($l_set['assign_user_to_group_by']) )
			{
				$this->debug_log( 'We want to assign the Group by &laquo;'.$l_set['assign_user_to_group_by'].'&raquo;' );
				if( isset($search_info[0][$l_set['assign_user_to_group_by']])
						&& isset($search_info[0][$l_set['assign_user_to_group_by']][0]) )
				{ // There is info we want to assign by
					$assign_by_value = $search_info[0][$l_set['assign_user_to_group_by']][0];
					$this->debug_log( 'The users info has &laquo;'.$assign_by_value.'&raquo; as value given.' );

					if( $users_Group = & $GroupCache->get_by_name( $assign_by_value, false ) )
					{ // A group with the users value returned exists.
						$NewUser->set_Group( $users_Group );
						$assigned_group = true;
						$this->debug_log( 'Adding User to existing Group.' );
					}
					else
					{
						$this->debug_log( 'Group with that name does not exist.' );

						if( $l_set['tpl_new_grp_ID'] )
						{ // we want to create a new group matching the assign-by info
							$this->debug_log( 'Template Group given, trying to create new group based on that.' );

							if( $new_Group = $GroupCache->get_by_ID( $l_set['tpl_new_grp_ID'], false ) ) // COPY!! and do not halt on error
							{ // take a copy of the Group to use as template
								$this->debug_log( 'Using Group &laquo;'.$new_Group->get('name').'&raquo; (#'.$l_set['tpl_new_grp_ID'].') as template.' );
								$new_Group->set( 'ID', 0 ); // unset ID (to allow inserting)
								$new_Group->set( 'name', $assign_by_value ); // set the wanted name
								$new_Group->dbinsert();
								$this->debug_log( 'Created Group &laquo;'.$new_Group->get('name').'&raquo;' );
								$this->debug_log( 'Assigned User to new Group.' );

								$NewUser->set_Group( $new_Group );
								$assigned_group = true;
							}
							else
							{
								$this->debug_log( 'Template Group with ID #'.$l_set['tpl_new_grp_ID'].' not found!' );
							}
						}
						else
						{
							$this->debug_log( 'No template group for creating a new group configured.' );
						}
					}
				}
			}

			if( ! $assigned_group )
			{ // Default group
				$users_Group = NULL;
				$fallback_grp_ID = $this->Settings->get( 'fallback_grp_ID' );

				if( empty($fallback_grp_ID) )
				{
					$this->debug_log( 'No default/fallback group given.' );
				}
				else
				{
					$users_Group = & $GroupCache->get_by_ID($fallback_grp_ID);

					if( $users_Group )
					{ // either $this->default_group_name is not given or wrong
						$NewUser->set_Group( $users_Group );
						$assigned_group = true;

						$this->debug_log( 'Using default/fallback group ('.$users_Group->get('name').').' );
					}
					else
					{
						$this->debug_log( 'Default/fallback group not existing ('.$fallback_grp_ID.').' );
					}
				}

			}

			if( $assigned_group )
			{
				$NewUser->dbinsert();
				$UserCache->add( $NewUser );

				$this->debug_log( 'Created user.' );
			}
			else
			{
				$this->debug_log( 'NOT created user, because no group has been assigned.' );
			}
			return true;
		}

		return false;
	}
}


/*
 * $Log$
 * Revision 1.34  2006/08/07 16:49:35  fplanque
 * doc
 *
 * Revision 1.33  2006/08/07 09:57:50  blueyed
 * doc
 *
 * Revision 1.32  2006/07/31 16:53:52  blueyed
 * better example
 *
 * Revision 1.31  2006/07/31 16:40:03  blueyed
 * Fixed creating new group, based on template.
 *
 * Revision 1.30  2006/07/20 19:40:36  blueyed
 * Simplified code/minor
 *
 * Revision 1.29  2006/07/10 20:41:54  blueyed
 * Fixed PluginInit behaviour. It now gets called on both installed and non-installed Plugins, but with the "is_installed" param appropriately set.
 *
 * Revision 1.28  2006/07/07 21:26:49  blueyed
 * Bumped to 1.9-dev
 *
 * Revision 1.27  2006/07/06 19:56:29  fplanque
 * no message
 *
 * Revision 1.26  2006/06/18 01:14:03  blueyed
 * lazy instantiate user's group; normalisation
 *
 * Revision 1.25  2006/06/16 21:30:57  fplanque
 * Started clean numbering of plugin versions (feel free do add dots...)
 *
 * Revision 1.24  2006/05/30 19:39:55  fplanque
 * plugin cleanup
 *
 * Revision 1.23  2006/04/24 15:43:37  fplanque
 * no message
 *
 * Revision 1.22  2006/04/22 02:36:39  blueyed
 * Validate users on registration through email link (+cleanup around it)
 *
 * Revision 1.21  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 */
?>