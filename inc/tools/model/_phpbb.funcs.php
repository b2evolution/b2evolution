<?php
/**
 * This file implements the functions to work with phpBB database importer.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Get aliases of the tables from the phpBB database
 *
 * @param string Table prefix
 * @return array Aliases
 */
function phpbb_tables_aliases( $phpbb_db_prefix )
{
	$phpbb_db_aliases = array(
			'BB_users'         => $phpbb_db_prefix.'users',
			'BB_user_fields'   => $phpbb_db_prefix.'profile_fields_data',
			'BB_ranks'         => $phpbb_db_prefix.'ranks',
			'BB_categories'    => $phpbb_db_prefix.'categories',
			'BB_forums'        => $phpbb_db_prefix.'forums',
			'BB_topics'        => $phpbb_db_prefix.'topics',
			'BB_posts'         => $phpbb_db_prefix.'posts',
			'BB_posts_text'    => $phpbb_db_prefix.'posts_text',
			'BB_privmsgs'      => $phpbb_db_prefix.'privmsgs',
			'BB_privmsgs_text' => $phpbb_db_prefix.'privmsgs_text',
			'BB_privmsgs_to'   => $phpbb_db_prefix.'privmsgs_to',
			'BB_sessions_keys' => $phpbb_db_prefix.'sessions_keys',
			'BB_attachments'   => $phpbb_db_prefix.'attachments',
			'BB_config'        => $phpbb_db_prefix.'config',
		);

	return $phpbb_db_aliases;
}


/**
 * Initialize the config variables for phpBB
 */
function phpbb_init_config()
{
	global $Session;
	global $phpbb_db_config, $phpbb_DB;

	$phpbb_db_config = phpbb_get_var( 'db_config' );
	if( is_array( $phpbb_db_config ) && count( $phpbb_db_config ) > 0 )
	{	// Connect to DB when config is already defined
		$phpbb_db_config['show_errors'] = true;
		$phpbb_db_config['aliases'] = phpbb_tables_aliases( $phpbb_db_config['prefix'] );
		$phpbb_DB = new DB( $phpbb_db_config );
	}
	else
	{	// Default config values
		global $db_config, $allow_install_test_features;
		$phpbb_db_config = array(
				'host'     => $db_config['host'],
				'name'     => '',
				'user'     => $db_config['user'],
				'password' => '',
				'prefix'   => 'phpbb_',
			);

		if( $allow_install_test_features )
		{	// Set maximum default values in test mode
			$phpbb_db_config['password'] = $db_config['password'];
			phpbb_set_var( 'blog_ID', '5' ); // Forums

			$GroupCache = & get_GroupCache();
			if( $normal_Group = & $GroupCache->get_by_name( 'Normal Users', false ) )
			{
				phpbb_set_var( 'group_default', $normal_Group->ID ); // Basic Users
				phpbb_set_var( 'all_group_default', $normal_Group->ID ); // Privileged Bloggers
			}
			if( $invalid_Group = & $GroupCache->get_by_name( 'Misbehaving/Suspect Users', false ) )
			{
				phpbb_set_var( 'group_invalid', $invalid_Group->ID ); // Invalid Users
			}
		}
	}
}


/**
 * Print out the log message on the screen
 *
 * @param string Message
 * @param string Type: message|error|warning
 * @param string Text after message
 * @param string Text before message
 */
function phpbb_log( $message, $type = 'message', $nl = '<br />', $bl = '' )
{
	switch( $type )
	{
		case 'error':
			echo $bl.'<span class="red">'.$message.'</span>'.$nl;
			break;

		case 'warning':
			echo $bl.'<span class="orange">'.T_('WARNING: ').$message.'</span>'.$nl;
			break;

		default:
			echo $bl.$message.$nl;
			break;
	}

	evo_flush();
}


/**
 * Get variable that was saved in the session
 *
 * @param string Name
 * @return mixed Value
 */
function phpbb_get_var( $name )
{
	global $Session, $phpbb_version;

	switch( $name )
	{
		case 'path_avatars':
			global $media_path;
			$default_value = $media_path.'import/avatars';
			break;

		case 'path_attachments':
			global $media_path;
			$default_value = $media_path.'import/attachments';
			break;

		default:
			$default_value = NULL;
			break;
	}

	return $Session->get( 'phpbb'.$phpbb_version.'.'.$name, $default_value );
}


/**
 * Set variable to store some phpBB config values
 *
 * @param string Name
 * @param string Value
 * @param boolean TRUE - to immediate saving the Sessions
 */
function phpbb_set_var( $name, $value, $immediate_save = false )
{
	global $Session, $phpbb_version;

	$Session->set( 'phpbb'.$phpbb_version.'.'.$name, $value );
	if( $immediate_save )
	{
		$Session->dbsave();
	}
}


/**
 * Delete phpBB config variable
 *
 * @param string Name
 */
function phpbb_unset_var( $name )
{
	global $Session, $phpbb_version;

	$Session->delete( 'phpbb'.$phpbb_version.'.'.$name );
}


/**
 * Get table name
 *
 * @param string Table name
 * @param string Table prefix
 * @return string Full table name
 */
function phpbb_table_name( $table_name, $table_prefix = 'phpbbimporter__' )
{
	return $table_prefix.$table_name;
}


/**
 * Add a temporary table into the DB
 *
 * @param string Table name
 */
function phpbb_table_add( $table_name )
{
	global $DB;

	$table_name = phpbb_table_name( $table_name );

	$DB->query( 'DROP TABLE IF EXISTS '.$table_name );

	$DB->query( 'CREATE TABLE IF NOT EXISTS '.$table_name.' (
		phpbb_ID INT(11) NULL,
		b2evo_ID INT(11) NULL
	) ENGINE = innodb' );
}


/**
 * Delete a temporary table
 *
 * @param string Table name
 */
function phpbb_table_delete( $table_name )
{
	global $DB;

	$table_name = phpbb_table_name( $table_name );

	$DB->query( 'DROP TABLE IF EXISTS '.$table_name );
}


/**
 * Add the links for IDs from two tables
 *
 * @param string Table name
 * @param array The links between two tables
 */
function phpbb_table_insert_links( $table_name, $links )
{
	if( empty( $links ) )
	{	// No links
		return;
	}

	global $DB;

	$links_data = array();
	$l = 1;
	foreach( $links as $phpbb_ID => $b2evo_ID )
	{
		$links_data[] = '( '.(int)$phpbb_ID.', '.(int)$b2evo_ID.' )';
		if( $l == 1000 )
		{	// Insert data by 1000 records
			$DB->query( 'INSERT INTO '.phpbb_table_name( $table_name ).' ( phpbb_ID, b2evo_ID )
					VALUES '.implode( ', ', $links_data ) );
			$links_data = array();
			$l = 1;
		}
		$l++;
	}

	if( count( $links_data ) > 0 )
	{	// Insert the rest records
			$DB->query( 'INSERT INTO '.phpbb_table_name( $table_name ).' ( phpbb_ID, b2evo_ID )
					VALUES '.implode( ', ', $links_data ) );
	}
}


/**
 * Get the links for IDs from two tables
 *
 * @param string Table name
 * @return array The links between two tables
 */
function phpbb_table_get_links( $table_name )
{
	/**
	* @var array Cache for the links of IDs between phpbb and b2evo tables
	*/
	global $phpbb_cache_links;

	if( !is_array( $phpbb_cache_links ) )
	{	// Init cache
		$phpbb_cache_links = array();
	}

	if( isset( $phpbb_cache_links[ $table_name ] ) )
	{	// The links from this table are already defined
		return $phpbb_cache_links[ $table_name ];
	}

	// Get the links from DB only first time
	global $DB;

	$table_exists = $DB->get_row( 'SHOW TABLES LIKE "'.phpbb_table_name( $table_name ).'"' );
	if( !empty( $table_exists ) )
	{	// Table exists in DB
		$SQL = new SQL();
		$SQL->SELECT( 'phpbb_ID, b2evo_ID' );
		$SQL->FROM( phpbb_table_name( $table_name ) );

		$phpbb_cache_links[ $table_name ] = $DB->get_assoc( $SQL->get() );
	}
	else
	{	// No table found in DB (it can be happened when we interrupt the processing by refreshing of the page)
		$phpbb_cache_links[ $table_name ] = array();
	}

	return $phpbb_cache_links[ $table_name ];
}


/**
 * Get user ranks from phpBB
 *
 * @return array Ranks
 */
function phpbb_ranks()
{
	global $phpbb_DB;

	$SQL = new SQL();
	$SQL->SELECT( 'rank_id, rank_title' );
	$SQL->FROM( 'BB_ranks' );
	$SQL->ORDER_BY( 'rank_min' );

	$ranks = $phpbb_DB->get_assoc( $SQL->get() );

	return $ranks;
}


/**
 * Get rank info
 *
 * @param integer Rank ID
 * @param boolean TRUE - to get only a count of users of selected rank
 * @return string Rank info (users count and etc.)
 */
function phpbb_rank_info( $rank_ID, $get_only_count = false )
{
	global $phpbb_DB, $rank_users_count;

	if( !isset( $rank_users_count ) )
	{	// Init array only first time
		$SQL = new SQL();
		$SQL->SELECT( 'rank_id, COUNT( user_id ) AS cnt' );
		$SQL->FROM( 'BB_users' );
		$SQL->FROM_add( 'LEFT JOIN BB_ranks ON user_rank = rank_id' );
		//$SQL->WHERE( 'user_id IN ( SELECT poster_id FROM phpbb_posts WHERE poster_id = user_id )' );
		$SQL->GROUP_BY( 'user_rank' );
		$rank_users_count = $phpbb_DB->get_assoc( $SQL->get() );
	}

	if( $get_only_count )
	{	// Return only a count of users of the rank
		return !empty( $rank_users_count[ $rank_ID ] ) ? (int)$rank_users_count[ $rank_ID ] : 0;
	}

	if( !empty( $rank_users_count[ $rank_ID ] ) )
	{
		$users_count = (int)$rank_users_count[ $rank_ID ];
		$r = sprintf( '%s users', $users_count );

		// Get the first 10 users of each rank
		$SQL = new SQL();
		$SQL->SELECT( 'username' );
		$SQL->FROM( 'BB_users' );
		$SQL->FROM_add( 'INNER JOIN BB_posts ON poster_id = user_id' ); // Get users which have at least one post
		$SQL->WHERE( 'user_rank = '.$phpbb_DB->quote( $rank_ID ) );
		$SQL->ORDER_BY( 'user_id' );
		$SQL->GROUP_BY( 'user_id' );
		$SQL->LIMIT( '10' );
		$users = $phpbb_DB->get_col( $SQL->get() );

		foreach( $users as $u => $username )
		{
			$users[ $u ] = $username;
		}
		$r .= ': '.implode( ', ', $users );
		$r .= ( $users_count > 10 ) ? ' ...' : '';

		return $r;
	}
}


/**
 * Get user groups from b2evolution
 *
 * @return array Groups
 */
function b2evo_groups()
{
	global $DB;

	$SQL = new SQL();
	$SQL->SELECT( 'grp_ID, CONCAT( grp_name, " (", grp_level, ")" )' );
	$SQL->FROM( 'T_groups' );
	$SQL->ORDER_BY( 'grp_level DESC, grp_name ASC' );

	$groups = array( '0' => T_( 'No import' ) );
	$groups = array_merge( $groups, $DB->get_assoc( $SQL->get() ) );

	return $groups;
}


/**
 * Import users from phpbb into b2evo
 */
function phpbb_import_users()
{
	global $DB, $phpbb_DB, $tableprefix, $phpbb_version;

	if( !phpbb_check_step( 'users' ) )
	{	// Check current step
		return; // Exit here if we cannot process this step
	}

	// Reset previous values:
	phpbb_unset_var( 'users_count_imported' );
	phpbb_unset_var( 'users_count_updated' );
	phpbb_unset_var( 'avatars_count_imported' );
	phpbb_unset_var( 'avatars_count_missing' );

	phpbb_log( T_('Importing users...') );

	/**
	 * @var array IDs of the Users;
	 *        Key is ID from phpBB
	 *        Value is new inserted ID from b2evo
	 */
	$users_IDs = array();

	// Get ranks that will be imported ( array( phpbb_rank_ID => b2evo_group_ID ) )
	$phpbb_ranks = phpbb_get_var( 'ranks' );

	// Remove ranks that will not be imported
	if( count( $phpbb_ranks ) > 0 )
	{
		foreach( $phpbb_ranks as $rank_ID => $b2evo_group_ID )
		{
			if( empty( $b2evo_group_ID ) )
			{	// Unset this rank, because it selected as no import
				unset( $phpbb_ranks[ $rank_ID ] );
			}
		}
	}

	$phpbb_users_sql_where_ranks = '';
	if( count( $phpbb_ranks ) > 0 )
	{	// Limit users by the selected ranks
		$phpbb_users_sql_where_ranks = ' OR u.user_rank IN ( '.$phpbb_DB->quote( array_keys( $phpbb_ranks ) ).' )';
	}

	$DB->begin();

	// Init SQL to get the users data and the count of the users
	$phpbb_users_SQL = new SQL();
	$phpbb_users_SQL->FROM( 'BB_users u' );
	$phpbb_users_SQL->FROM_add( 'INNER JOIN BB_posts p ON p.poster_id = u.user_id' ); // Get users which have at least one post
	$phpbb_users_SQL->WHERE( '( u.user_rank IS NULL OR u.user_rank = 0'.$phpbb_users_sql_where_ranks.' )' );
	$phpbb_users_SQL->ORDER_BY( 'u.user_id' );

	// Get the count of the topics
	$count_SQL = $phpbb_users_SQL;
	$count_SQL->SELECT( 'COUNT( DISTINCT u.user_id )' );
	$phpbb_users_count = $phpbb_DB->get_var( $count_SQL->get() );

	if( $phpbb_users_count > 0 )
	{
		phpbb_log( sprintf( T_('%s users have been found in the phpBB database'), $phpbb_users_count ) );
	}
	else
	{	// No users
		phpbb_log( T_('No users found in the phpBB database.'), 'error' );
		$DB->commit();
		return; // Exit here
	}

	// Get the duplicated emails
	$emails_SQL = new SQL();
	$emails_SQL->SELECT( 'user_email, ""' );
	$emails_SQL->FROM( 'BB_users' );
	$emails_SQL->GROUP_BY( 'user_email' );
	$emails_SQL->HAVING( 'COUNT( user_id ) > 1' );
	$phpbb_emails_duplicated = $phpbb_DB->get_assoc( $emails_SQL->get() );

	phpbb_log( T_('Start importing <b>users</b> into the b2evolution database...'), 'message', '' );

	// Init SQL to get the users
	$users_SQL = $phpbb_users_SQL;
	if( $phpbb_version == 3 )
	{	// phpBB v3:

		// Detect what user fields exist in phpBB database:
		$db_user_fields = $phpbb_DB->get_col( 'SHOW COLUMNS FROM BB_user_fields' );
		$sel_fields = array( 'icq', 'website', 'aim', 'yim', 'msnm', 'interests', 'facebook', 'googleplus', 'location', 'skype', 'twitter', 'yahoo', 'youtube', 'aol' );
		foreach( $sel_fields as $s => $sel_field_key )
		{
			if( in_array( 'pf_phpbb_'.$sel_field_key, $db_user_fields ) )
			{	// Try to get a value if user field exists:
				$sel_fields[ $sel_field_key ] = 'uf.pf_phpbb_'.$sel_field_key;
			}
			else
			{	// Select empty value if field isn't detected in DB:
				$sel_fields[ $sel_field_key ] = '""';
			}
			$sel_fields[ $sel_field_key ] .= ' AS user_'.$sel_field_key;
			unset( $sel_fields[ $s ] );
		}

		$users_SQL->SELECT( 'u.user_id, u.user_inactive_reason, u.username, u.user_password, u.user_email, u.user_lang, u.user_regdate,
							 '.implode( ', ', $sel_fields ).', u.user_rank,
							 u.user_allow_viewonline, u.user_notify_pm, u.user_avatar' );
		$users_SQL->FROM_add( 'LEFT JOIN BB_user_fields uf ON uf.user_id = u.user_id' );
	}
	else
	{	// phpBB v2:
		$users_SQL->SELECT( 'u.user_id, u.user_active, u.username, u.user_password, u.user_email, u.user_lang, u.user_level, u.user_regdate,
							 u.user_icq, u.user_website, u.user_aim, u.user_yim, u.user_msnm, u.user_interests, u.user_rank,
							 u.user_allow_viewonline, u.user_notify_pm, u.user_avatar' );
	}
	$users_SQL->GROUP_BY( 'u.user_id' );

	// Get all users IPs in one sql query
	$users_ips_SQL = new SQL();
	$users_ips_SQL->SELECT( 'user_id, last_ip' );
	$users_ips_SQL->FROM( 'BB_sessions_keys' );
	$users_ips_SQL->ORDER_BY( 'last_login DESC' );
	$users_ips = $phpbb_DB->get_assoc( $users_ips_SQL->get() );

	// Get a path where we should import avatars from:
	$path_avatars = phpbb_get_import_path( 'path_avatars' );

	$page = 0;
	$page_size = 1000;
	$phpbb_users_count_imported = 0;
	$phpbb_users_count_updated = 0;
	do
	{	// Split by page to optimize process
		// It gives to save the memory rather than if we get all users by one query without LIMIT clause

		// Get the users
		$users_SQL->LIMIT( ( $page * $page_size ).', '.$page_size );
		$phpbb_users = $phpbb_DB->get_results( $users_SQL->get() );
		$phpbb_users_count = count( $phpbb_users );

		// Insert the new users
		foreach( $phpbb_users as $p => $phpbb_user )
		{
			if( $p % 100 == 0 )
			{	// Display the processing dots after 100 users
				phpbb_log( ' .', 'message', '' );
			}

			if( $phpbb_user->user_id < 1 )
			{	// Skip the users with invalid ID
				phpbb_log( sprintf( T_( 'User "%s" with ID %s ignored' ), $phpbb_user->username, $phpbb_user->user_id ), 'error', ' ', '<br />' );
				continue;
			}

			if( $phpbb_user->username == '¥åßßå' )
			{	// Special rule for this username
				$user_login = 'yabba';
			}
			else
			{	// Replace unauthorized chars from username
				$user_login = preg_replace( '/([^a-z0-9_]+)/i', '_', $phpbb_user->username );
				$user_login = utf8_substr( utf8_strtolower( $user_login ), 0, 20 );
				if( $user_login == '_' )
				{	// If all username chars are unauthorized
					$user_login = 'user_1';
				}
			}

			$user_has_duplicated_email = false;
			if( isset( $phpbb_emails_duplicated[$phpbb_user->user_email] ) )
			{	// The user has the duplicate email
				if( !empty( $phpbb_emails_duplicated[$phpbb_user->user_email] ) )
				{	// The other user already was imported with such email
					phpbb_log( '<br />'.sprintf( T_( 'The phpBB users "%s" and "%s" have the same email address "%s" and will be merged in b2evolution as just "%s"' ),
							$phpbb_emails_duplicated[$phpbb_user->user_email]['username'], // Username of the first user
							$user_login, // Username of the second user (duplicate)
							$phpbb_user->user_email, // The same email address
							$phpbb_emails_duplicated[$phpbb_user->user_email]['username'] // This username will be used in b2evolution
						), 'error', ' ' );

					// Set link between current phpBB user ID and b2evo user ID of first user with this duplicated email address
					// This link will be used to merge the topics, comments and messages from all phpBB users with the same email address for ONE b2evo user
					$users_IDs[$phpbb_user->user_id] = $users_IDs[ $phpbb_emails_duplicated[$phpbb_user->user_email]['user_ID'] ];

					// Don't import this user
					unset( $phpbb_users[$p] );
					continue;
				}
				$phpbb_emails_duplicated[$phpbb_user->user_email] = array(
						'username' => $user_login,
						'user_ID'  => $phpbb_user->user_id
					);
				$user_has_duplicated_email = true;
			}

			// Check if this user already exists with same email address in b2evo DB
			$SQL = new SQL();
			$SQL->SELECT( 'user_ID, user_login' );
			$SQL->FROM( 'T_users' );
			$SQL->WHERE( 'user_email = '.$DB->quote( utf8_strtolower( $phpbb_user->user_email ) ) );
			$b2evo_user = $DB->get_row( $SQL->get() );
			if( !empty( $b2evo_user ) )
			{	// User already exists in DB of b2evo
				// Don't insert this user
				// Update the link between IDs of this user from two databases
				$users_IDs[$phpbb_user->user_id] = $b2evo_user->user_ID;
				unset( $phpbb_users[$p] ); // Unset already existing user from this array to exclude the updating of the fields and settings
				$phpbb_users_count_updated++;

				// Import user's avatar:
				phpbb_import_avatar( $phpbb_user, $b2evo_user->user_ID, $path_avatars );

				phpbb_log( sprintf( T_( 'The user #%s already exists with E-mail address "%s" in the b2evolution database -- Merging User "%s" with user "%s".' ), $phpbb_user->user_id, $phpbb_user->user_email, $user_login, $b2evo_user->user_login ), 'warning', ' ', '<br />' );
				continue;
			}

			// Check if this user already exists with same login in b2evo DB
			$user_login_number = 0;
			$next_login = $user_login;
			$user_login_mask = preg_replace( '#\d+$#', '', $user_login );
			do
			{
				$SQL = new SQL();
				$SQL->SELECT( 'user_ID' );
				$SQL->FROM( 'T_users' );
				$SQL->WHERE( 'user_login = '.$DB->quote( $next_login ) );
				if( $b2evo_user_ID = $DB->get_var( $SQL->get() ) )
				{	// Duplicated user login, Change to next login by increasing the number at the end
					$next_login = $user_login_mask.( ++$user_login_number );
				}
			}
			while( $b2evo_user_ID );
			if( $user_login != $next_login )
			{	// Duplicated login was changed, Display a message about this event
				phpbb_log( sprintf( T_( 'The login "%s" already exists with a different email address. The user "%s" will be imported as "%s"' ), $user_login, $user_login, $next_login ), 'warning', ' ', '<br />' );
				$user_login = $next_login;
			}

			if( !empty( $users_ips[ $phpbb_user->user_id ] ) )
			{	// Decode user ip from hex format
				$phpbb_user->user_ip = phpbb_decode_ip( $users_ips[ $phpbb_user->user_id ] );
			}

			$user_data = array(
					'user_login'              => $user_login,
					'user_email'              => $phpbb_user->user_email,
					'user_created_datetime'   => date( 'Y-m-d H:i:s', $phpbb_user->user_regdate ),
					'user_profileupdate_date' => date( 'Y-m-d H:i:s', $phpbb_user->user_regdate ),
					'user_locale'             => 'en-US'
				);
			if( $phpbb_version == 3 )
			{	// phpBB v3:
				if( preg_match( '/^(\$[^\$]+)(\$[^\$]+)*\$([^\$]+)$/', $phpbb_user->user_password, $password_data ) )
				{	// Save password value and password driver code in two separate fields:
					$user_data['user_pass_driver'] = 'bb'.$password_data[1];
					if( $user_PasswordDriver = get_PasswordDriver( $user_data['user_pass_driver'] ) )
					{
						$user_data['user_salt'] = $user_PasswordDriver->extract_salt( $phpbb_user->user_password );
						$user_data['user_pass'] = $user_PasswordDriver->clear_hash( $phpbb_user->user_password );
					}
				}
				if( ! isset( $user_data['user_pass'] ) )
				{	// Unknown password format:
					$user_data['user_pass'] = '';
					$user_data['user_pass_driver'] = 'evo$salted';
				}
				$user_data['user_level'] = 0;
				$user_data['user_status'] = $phpbb_user->user_inactive_reason == '0' ? 'autoactivated' : 'closed';
			}
			else
			{	// phpBB v2:
				$user_data['user_pass'] = $phpbb_user->user_password;
				$user_data['user_pass_driver'] = 'evo$md5';
				$user_data['user_level'] = $phpbb_user->user_level;
				$user_data['user_status'] = $phpbb_user->user_active == '1' ? 'autoactivated' : 'closed';
			}

			if( !empty( $phpbb_user->user_rank ) && !empty( $phpbb_ranks[ $phpbb_user->user_rank ] ) )
			{	// Define the user's group
				$user_data['user_grp_ID'] = $phpbb_ranks[ $phpbb_user->user_rank ];
			}
			if( !isset( $user_data['user_grp_ID'] ) )
			{	// Set default group
				$user_data['user_grp_ID'] = phpbb_get_var( 'group_default' );
			}

			// Add the DB quotes for the user fields
			$import_data = array();
			foreach( $user_data as $field_value )
			{
				$import_data[] = $phpbb_DB->quote( $field_value );
			}

			// *** EXECUTE QUERY TO INSERT NEW USER *** //
			$DB->query( 'INSERT INTO '.$tableprefix.'users ( '.implode( ', ', array_keys( $user_data ) ).' )
					VALUES ( '.implode( ', ', $import_data ).' )');

			if( !$DB->get_results() )
			{	// User was not inserted
				phpbb_log( sprintf( T_( 'User "%s" with ID %s cannot be imported. MySQL error: %s.' ) , $phpbb_user->username, $phpbb_user->user_id, $DB->last_error ), 'error', ' ', '<br />' );
				continue;
			}

			$user_ID = $DB->insert_id;

			// Import user's avatar:
			phpbb_import_avatar( $phpbb_user, $user_ID, $path_avatars );

			// Save new inserted ID of the user
			$users_IDs[$phpbb_user->user_id] = $user_ID;
			if( $user_has_duplicated_email )
			{
				$phpbb_emails_duplicated[$phpbb_user->user_email]['user_ID'] = $phpbb_user->user_id;
			}

			// Import the user's fields
			phpbb_import_user_fields( $phpbb_user, $user_ID );
			// Import user's settings
			phpbb_import_user_settings( $phpbb_user, $user_ID );

			$phpbb_users_count_imported++;
		}

		$page++;
	}
	while( $phpbb_users_count > 0 );

	// Add temporary table to store the links between user's IDs from phpbb and b2evo tables
	phpbb_table_add( 'users' );
	phpbb_table_insert_links( 'users', $users_IDs );

	$DB->commit();

	phpbb_set_var( 'users_count_imported', $phpbb_users_count_imported );
	phpbb_set_var( 'users_count_updated', $phpbb_users_count_updated );
}


/**
 * Import users fields from phpbb into b2evo
 *
 * @param array User data from phpBB
 * @param integer New inserted user ID in the b2evo database
 */
function phpbb_import_user_fields( $phpbb_user, $b2evo_user_ID )
{
	global $DB, $phpbb_DB, $phpbb_cache_b2evo_users_fields;

	if( !isset( $phpbb_cache_b2evo_users_fields ) )
	{	// Get users fields from b2evo database
		$SQL = new SQL();
		$SQL->SELECT( 'ufdf_ID, ufdf_name' );
		$SQL->FROM( 'T_users__fielddefs' );
		$phpbb_cache_b2evo_users_fields = $DB->get_assoc( $SQL->get() );
	}

	if( empty( $phpbb_cache_b2evo_users_fields ) )
	{	// No users fields
		return;
	}

	$fields_links = array(
		'rank'      => 'Role',
		'icq'       => 'ICQ ID',
		'website'   => 'Website',
		'aim'       => 'AOL AIM',
		'yim'       => 'Yahoo IM',
		'msnm'      => 'MSN/Live IM',
		'occ'       => 'Role',
		'interests' => 'I like',
		'facebook'  => 'Facebook',
		'googleplus'=> 'Google Plus',
		'location'  => 'Main address',
		'skype'     => 'Skype',
		'twitter'   => 'Twitter',
		'yahoo'     => 'Yahoo IM',
		'youtube'   => 'YouTube',
		'aol'       => 'AOL AIM',
	);

	$fields_types_IDs = array();
	foreach( $phpbb_cache_b2evo_users_fields as $field_ID => $field_name )
	{
		if( $field_link_key = array_search( $field_name, $fields_links ) )
		{
			$fields_types_IDs[$field_link_key] = $field_ID;
		}
	}

	if( empty( $fields_types_IDs ) )
	{	// No links between phpBB fields and b2evo fields
		return;
	}

	global $phpbb_cache_phpbb_ranks;

	if( !isset( $phpbb_cache_phpbb_ranks ) )
	{	// Get the titles of the user ranks from phpBB
		$SQL = new SQL();
		$SQL->SELECT( 'rank_id, rank_title' );
		$SQL->FROM( 'BB_ranks' );
		$phpbb_cache_phpbb_ranks = $phpbb_DB->get_assoc( $SQL->get() );
	}

	$import_data = array();
	foreach( $fields_types_IDs as $field_type => $field_ID )
	{
		if( ! isset( $phpbb_user->{'user_'.$field_type} ) )
		{	// Skip this user field because the given phpBB DB doesn't contain it:
			continue;
		}
		$field_value = trim( $phpbb_user->{'user_'.$field_type} );
		if( $field_type == 'rank' )
		{	// If field is "rank" we should get the value from table "phpbb_ranks" by rank_ID
			$field_value = !empty( $phpbb_cache_phpbb_ranks[$field_value] ) ? $phpbb_cache_phpbb_ranks[$field_value] : '';
		}

		if( $field_value != '' )
		{	// field is filled, we can put it into DB
			$import_data[] = '( '.
					$DB->quote( $b2evo_user_ID ).', '.
					$DB->quote( $field_ID ).', '.
					$DB->quote( $field_value ).
				' )';
		}
	}

	if( count( $import_data ) == 0 )
	{	// No data to insert, Exit here
		return;
	}

	global $tableprefix;

	$DB->query( 'INSERT INTO '.$tableprefix.'users__fields ( uf_user_ID, uf_ufdf_ID, uf_varchar )
			VALUES '.implode( ', ', $import_data ) );
}


/**
 * Import users settings from phpbb into b2evo
 *
 * @param array User data from phpBB
 * @param integer New inserted user ID in the b2evo database
 */
function phpbb_import_user_settings( $phpbb_user, $b2evo_user_ID )
{
	global $DB, $tableprefix;

	$settings_links = array(
		'allow_viewonline' => 'show_online',
		'notify_pm'        => 'notify_messages',
		'ip'               => 'created_fromIPv4',
	);

	$import_data = array();
	foreach( $settings_links as $phpbb_field => $b2evo_field )
	{
		if( !isset( $phpbb_user->{'user_'.$phpbb_field} ) )
		{	// Skip empty value
			continue;
		}

		$setting_value = trim( $phpbb_user->{'user_'.$phpbb_field} );
		if( $phpbb_field == 'ip' )
		{
			$setting_value = ip2int( $setting_value );
		}
		$import_data[] = '( '.
				$DB->quote( $b2evo_user_ID ).', '.
				$DB->quote( $b2evo_field ).', '.
				$DB->quote( $setting_value ).
			' )';
	}

	if( !empty( $import_data ) )
	{	// *** EXECUTE QUERY TO INSERT NEW USERS SETTINGS *** //
		$DB->query( 'INSERT INTO '.$tableprefix.'users__usersettings ( uset_user_ID, uset_name, uset_value )
				VALUES '.implode( ', ', $import_data ) );
	}
}


/**
 * Import invalid user (which doesn't exist in DB)
 *
 * @param integer User ID in DB of phpBB
 * @param array Users IDs (users which already imported)
 * @param string Username
 * @return boolean TRUE on success
 */
function phpbb_import_invalid_user( $phpbb_user_ID, & $users_IDs, $phpbb_username = '' )
{
	//return false;
	$group_invalid_ID = phpbb_get_var( 'group_invalid' );

	if( empty( $group_invalid_ID ) )
	{	// If the invalid group ID is empty it means we shouldn't import the invalid users
		return false;
	}

	if( !isset( $users_IDs[ (string) $phpbb_user_ID ] ) )
	{	// If user is not imported yet
		global $DB, $tableprefix;

		$user_email = '';
		if( !empty( $phpbb_username ) )
		{	// If username is defined
			$user_login = $phpbb_username;
		}
		else
		{
			if( $phpbb_user_ID == '-1' )
			{	// Anonymous user
				$user_login = 'anonymous';
				$user_email = 'ano@nymo.us';
			}
			else
			{	// All other users
				$user_login = 'user_'.$phpbb_user_ID;
			}
		}

		// Check if this user already exists in b2evo DB
		$SQL = new SQL();
		$SQL->SELECT( 'user_ID' );
		$SQL->FROM( 'T_users' );
		$SQL->WHERE( 'user_login = '.$DB->quote( $user_login ) );
		$b2evo_user_ID = $DB->get_var( $SQL->get() );
		if( empty( $b2evo_user_ID ) )
		{	// User doesn't exist in DB of b2evo yet, Insert new user
			$user_data = array(
					'user_login'            => $DB->quote( $user_login ),
					'user_grp_ID'           => $DB->quote( $group_invalid_ID ),
					'user_pass'             => $DB->quote( '' ),
					'user_email'            => $DB->quote( $user_email ),
					'user_status'           => $DB->quote( 'closed' ),
					'user_locale'           => $DB->quote( 'en-US' ),
				);

			// *** EXECUTE QUERY TO INSERT NEW INVALID USER *** //
			$DB->query( 'INSERT INTO '.$tableprefix.'users ( '.implode( ', ', array_keys( $user_data ) ).' )
					VALUES ( '.implode( ', ', $user_data ).' )' );

			if( !$DB->get_results() )
			{	// User was not inserted
				phpbb_log( sprintf( T_( 'User "%s" cannot be imported. MySQL error: %s.' ) , $phpbb_user_ID, $DB->last_error ), 'error', ' ', '<br />' );
				return false;
			}

			$b2evo_user_ID = $DB->insert_id;

			$GroupCache = & get_GroupCache();
			$Group = & $GroupCache->get_by_ID( $group_invalid_ID, false );
			if( $Group )
			{
				phpbb_log( sprintf( T_( 'Created user "%s" in the "%s" group' ) , $user_login, $Group->get_name() ), 'message', ' ', '<br />' );
			}
		}

		$users_IDs[ (string) $phpbb_user_ID ] = $b2evo_user_ID;

		phpbb_table_insert_links( 'users', array( $phpbb_user_ID => $b2evo_user_ID ) );

		return true;
	}
}


/**
 * Import the forums
 */
function phpbb_import_forums()
{
	global $DB, $phpbb_DB, $phpbb_version;

	if( !phpbb_check_step( 'forums' ) )
	{	// Check current step
		return; // Exit here if we cannot process this step
	}

	phpbb_unset_var( 'forums_count_imported' );

	phpbb_log( T_('Importing forums...') );

	/**
	 * @var array IDs of the Forums
	 *        Key is ID from phpBB
	 *        Value is new inserted ID from b2evo
	 */
	$forums_IDs = array();

	$DB->begin();

	$import_categories = phpbb_get_var( 'import_categories' );
	$import_forums = phpbb_get_var( 'import_forums' );

	// Get the categories and forums from phpbb database:
	if( $phpbb_version == 3 )
	{	// pnpBB v3:
		$import_forums = array_merge( $import_forums, $import_categories );
		if( empty( $import_forums ) )
		{	// No selected forums to import
			$phpbb_forums = array();
		}
		else
		{	// Get the forums from phpbb database:
			$forum_SQL = new SQL();
			$forum_SQL->SELECT( 'f.forum_id, f.forum_name, f.forum_desc, f.right_id AS forum_order, f.parent_id AS forum_parent, IF( f.parent_id > 0, 0, 1 ) AS forum_meta, f.parent_id AS cat_id, forum_status AS forum_lock' );
			$forum_SQL->FROM( 'BB_forums f' );
			$forum_SQL->ORDER_BY( 'f.right_id' );
			$forum_SQL->WHERE( 'forum_id IN ( '.$phpbb_DB->quote( $import_forums ).' )' );
			$phpbb_forums = $phpbb_DB->get_results( $forum_SQL->get() );
		}
	}
	else
	{	// pnpBB v2:

		// Get the categories from phpbb database:
		$cat_SQL = new SQL();
		$cat_SQL->SELECT( 'cat_id AS forum_id, cat_title AS forum_name, "" AS forum_desc, cat_order AS forum_order, NULL AS forum_parent, 1 AS forum_meta, 0 AS cat_id, 0 AS forum_lock' );
		$cat_SQL->FROM( 'BB_categories' );
		if( !empty( $import_categories ) )
		{	// Select only these categories
			$cat_SQL->WHERE( 'cat_id IN ( '.$phpbb_DB->quote( $import_categories ).' )' );
		}
		else
		{	// If no categories to import
			$cat_SQL->WHERE( 'cat_id = -1' );
		}

		// Get the forums from phpbb database:
		$forum_SQL = new SQL();
		$forum_SQL->SELECT( 'f.forum_id, f.forum_name, f.forum_desc, f.forum_order, f.forum_parent, 0 AS forum_meta, f.cat_id, forum_status AS forum_lock' );
		$forum_SQL->FROM( 'BB_forums f' );
		$forum_SQL->FROM_add( 'LEFT JOIN BB_categories c ON f.cat_id = c.cat_id' );
		$forum_SQL->ORDER_BY( 'c.cat_order, f.forum_order' );
		if( !empty( $import_forums ) )
		{	// Select only these forums
			$forum_SQL->WHERE( 'forum_id IN ( '.$phpbb_DB->quote( $import_forums ).' )' );
		}
		else
		{	// If no forums to import
			$forum_SQL->WHERE( 'forum_id = -1' );
		}

		$phpbb_forums = $phpbb_DB->get_results( '('.$cat_SQL->get().') UNION ('.$forum_SQL->get().')' );
	}

	if( count( $phpbb_forums ) > 0 )
	{
		phpbb_log( sprintf( T_('%s forums have been found in the phpBB database'), count( $phpbb_forums ) ) );
	}
	else
	{	// No forums
		phpbb_log( T_('No found forums in the phpBB database.'), 'error' );
		$DB->commit();
		return; // Exit here
	}

	phpbb_log( T_('Start importing <b>forums</b> as <b>categories</b> into the b2evolution database...') );

	// Insert the new forums
	$phpbb_forums_count_imported = 0;
	$forums_parents = array();
	foreach( $phpbb_forums as $p => $phpbb_forum )
	{
		$forum_data = array(
			'cat_blog_ID'     => $DB->quote( phpbb_get_var( 'blog_ID' ) ),
			'cat_name'        => $DB->quote( $phpbb_forum->forum_name ),
			'cat_description' => $DB->quote( $phpbb_forum->forum_desc ),
			'cat_order'       => $DB->quote( $phpbb_forum->forum_order ),
			'cat_urlname'     => $DB->quote( phpbb_unique_urlname( $phpbb_forum->forum_name, 'T_categories', 'cat_urlname' ) ),
			'cat_meta'        => $DB->quote( $phpbb_forum->forum_meta ),
			'cat_lock'        => $DB->quote( $phpbb_forum->forum_lock )
		);

		$DB->query( 'INSERT INTO T_categories ( '.implode( ', ', array_keys( $forum_data ) ).' )
			VALUES ( '.implode( ', ', $forum_data ).' )' );

		if( $phpbb_forum->forum_meta == '1' )
		{	// Category
			$forums_IDs['cat_'.$phpbb_forum->forum_id] = $DB->insert_id;
		}
		else
		{	// Forum
			$forums_IDs[$phpbb_forum->forum_id] = $DB->insert_id;
		}

		if( isset( $phpbb_forum->cat_id ) && $phpbb_forum->cat_id > 0 )
		{	// First level forum has category as parent
			$forums_parents[$phpbb_forum->forum_id] = 'cat_'.$phpbb_forum->cat_id;
		}
		elseif( isset( $phpbb_forum->forum_parent ) && $phpbb_forum->forum_parent > 0 )
		{	// Save parent ID to update it in the next step
			$forums_parents[$phpbb_forum->forum_id] = $phpbb_forum->forum_parent;
		}

		phpbb_log( sprintf( T_('The forum "%s" is imported.'), $phpbb_forum->forum_name ) );
		$phpbb_forums_count_imported++;
	}

	if( count( $forums_parents ) > 0 )
	{	// Update the parents IDs
		foreach( $forums_parents as $phpbb_forum_ID => $phpbb_parent_ID )
		{
			if( isset( $forums_IDs[ (string) $phpbb_forum_ID ], $forums_IDs[ (string) $phpbb_parent_ID ] ) )
			{
				$DB->query( 'UPDATE T_categories
					  SET cat_parent_ID = '.$DB->quote( $forums_IDs[ (string) $phpbb_parent_ID ] ).'
					WHERE cat_ID = '.$DB->quote( $forums_IDs[ (string) $phpbb_forum_ID ] ) );
			}
		}
	}

	// Add temporary table to store the links between forums's IDs from phpbb and b2evo tables
	phpbb_table_add( 'forums' );
	phpbb_table_insert_links( 'forums', $forums_IDs );

	$DB->commit();

	phpbb_set_var( 'forums_count_imported', $phpbb_forums_count_imported );
}

/**
 * Get the unique url name
 *
 * @param string Source text
 * @param string Table name
 * @param string Field name
 * @return string category's url name
 */
function phpbb_unique_urlname( $source, $table, $field )
{
	global $DB;

	// Replace special chars/umlauts, if we can convert charsets:
	$url_name = utf8_url_slug( $source, -1, true );

	$url_number = 1;
	$url_name_correct = $url_name;
	do
	{	// Check for unique url name in DB
		$SQL = new SQL();
		$SQL->SELECT( $field );
		$SQL->FROM( $table );
		$SQL->WHERE( $field.' = '.$DB->quote( $url_name_correct ) );
		$row = $DB->get_var( $SQL->get() );
		if( $row )
		{	// Row already exists with such field; Make it unique:
			$url_name_correct = $url_name.'-'.$url_number;
			$url_number++;
		}
	}
	while( !empty( $row ) );

	return $url_name_correct;
}


/**
 * Import the topics
 */
function phpbb_import_topics()
{
	global $DB, $phpbb_DB, $tableprefix, $phpbb_version;

	if( !phpbb_check_step( 'topics' ) )
	{	// Check current step
		return; // Exit here if we cannot process this step
	}

	$import_forums = phpbb_get_var( 'import_forums' );

	// Reset previous values:
	phpbb_unset_var( 'attachments_count_imported' );
	phpbb_unset_var( 'attachments_count_missing' );

	phpbb_log( T_('Importing topics...') );

	/**
	 * @var array IDs of the Topics;
	 *        Key is ID from phpBB
	 *        Value is new inserted ID from b2evo
	 */
	$topics_IDs = array();

	$DB->begin();

	// Init SQL to get the topics data and the count of the topics
	$SQL = new SQL();
	$SQL->FROM( 'BB_topics t' );
	$SQL->FROM_add( 'INNER JOIN BB_posts p ON t.topic_first_post_id = p.post_id' );
	if( $phpbb_version == 2 )
	{	// pnpBB v2:
		$SQL->FROM_add( 'INNER JOIN BB_posts_text pt ON p.post_id = pt.post_id' );
	}
	$SQL->WHERE( 't.topic_status != 2' ); // Don't select MOVIED topics
	if( !empty( $import_forums ) )
	{	// Select the topics only from these forums
		$SQL->WHERE_and( 't.forum_id IN ( '.$phpbb_DB->quote( $import_forums ).' )' );
	}
	else
	{	// If no forums to import
		$SQL->WHERE_and( 't.forum_id = -1' );
	}

	// Get the count of the topics
	$count_SQL = $SQL;
	$count_SQL->SELECT( 'COUNT( t.topic_id )' );
	$phpbb_topics_count = $phpbb_DB->get_var( $count_SQL->get() );

	if( $phpbb_topics_count > 0 )
	{
		phpbb_log( sprintf( T_('%s topics have been found in the phpBB database'), $phpbb_topics_count ) );
	}
	else
	{	// No topics
		phpbb_log( T_('No found topics in the phpBB database.'), 'error' );
		$DB->commit();
		return; // Exit here
	}

	// Get a path where we should import topic attachments from:
	$path_attachments = phpbb_get_import_path( 'path_attachments' );

	$forums_IDs = phpbb_table_get_links( 'forums' );
	$users_IDs = phpbb_table_get_links( 'users' );

	$topic_fields = array(
			'post_creator_user_ID',
			'post_lastedit_user_ID',
			'post_main_cat_ID',
			'post_title',
			'post_content',
			'post_urltitle',
			'post_renderers',
			'post_datestart',
			'post_datecreated',
			'post_datemodified',
			'post_locale',
			'post_status',
			'post_comment_status',
			'post_featured',
			'post_excerpt',
			'post_excerpt_autogenerated',
			'post_wordcount'
		);

	phpbb_log( T_('Start importing <b>topics</b> as <b>posts</b> into the b2evolution database...'), 'message', '' );

	$BlogCache = & get_BlogCache();
	$phpbbBlog = & $BlogCache->get_by_ID( phpbb_get_var( 'blog_ID' ) );

	// Init SQL to get the topics
	$topics_SQL = $SQL;
	if( $phpbb_version == 3 )
	{	// pnpBB v3:
		$topics_SQL->SELECT( 't.topic_id, t.forum_id, t.topic_time, t.topic_poster, t.topic_title,
			t.topic_status, t.topic_type, t.topic_first_post_id, p.post_id, p.post_text, p.bbcode_uid, p.post_username' );
	}
	else
	{	// pnpBB v2:
		$topics_SQL->SELECT( 't.topic_id, t.forum_id, t.topic_time, t.topic_poster, t.topic_title,
			t.topic_status, t.topic_type, t.topic_first_post_id, p.post_id, pt.post_text, pt.bbcode_uid, p.post_username' );
	}
	$topics_SQL->ORDER_BY( 't.topic_id' );

	$page = 0;
	$page_size = 1000;
	$phpbb_topics_count_imported = 0;
	do
	{	// Split by page to optimize process
		// It gives to save the memory rather than if we get all topics by one query without LIMIT clause

		// Get the topics by page
		$topics_SQL->LIMIT( ( $page * $page_size ).', '.$page_size );
		$phpbb_topics = $phpbb_DB->get_results( $topics_SQL->get() );

		// Insert the new topics
		foreach( $phpbb_topics as $p => $phpbb_topic )
		{
			if( !isset( $forums_IDs[ (string) $phpbb_topic->forum_id ] ) )
			{	// The topic has the incorrect forum's ID by some reason
				phpbb_log( sprintf( '<br />'.T_('Skipped topic: %s. Incorrect forum ID: %s. <b>Content:</b> %s'), $phpbb_topic->topic_id, $phpbb_topic->forum_id, substr( $phpbb_topic->post_text, 0, 250 ).' ...' ), 'error' );
				continue;
			}

			if( !isset( $users_IDs[ (string) $phpbb_topic->topic_poster ] ) )
			{	// The topic has the incorrect user's ID by some reason
				if( !phpbb_import_invalid_user( $phpbb_topic->topic_poster, $users_IDs, $phpbb_topic->post_username ) )
				{	// We cannot create invalid user
					phpbb_log( sprintf( '<br />'.T_('Skipped topic: %s. Incorrect user ID: %s. <b>Content:</b> %s'), $phpbb_topic->topic_id, $phpbb_topic->topic_poster, substr( $phpbb_topic->post_text, 0, 250 ).' ...' ), 'error' );
					continue;
				}
			}

			$author_ID = $users_IDs[ (string) $phpbb_topic->topic_poster ];
			$forum_ID = $forums_IDs[ (string) $phpbb_topic->forum_id ];
			$canonical_slug = phpbb_unique_urlname( 'topic-'.$phpbb_topic->topic_id, 'T_slug', 'slug_title' );
			//$canonical_slug = 'topic-'.$phpbb_topic->topic_id;
			$second_slug = phpbb_unique_urlname( 'forumpost-'.$phpbb_topic->topic_first_post_id, 'T_slug', 'slug_title' );
			//$second_slug = 'forumpost-'.$phpbb_topic->topic_first_post_id;
			$post_content = phpbb_decode_bbcode( $phpbb_topic->post_text, $phpbb_topic->bbcode_uid );
			$topic_time = date( 'Y-m-d H:i:s', $phpbb_topic->topic_time );

			if( $phpbb_topic->topic_status == '1' )
			{	// If topic is closed set comment status - closed
				$post_comment_status = 'closed';
			}
			elseif( $phpbb_topic->topic_type == '2' )
			{	// If topic is "Announcement" set comment status - disabled
				$post_comment_status = 'disabled';
			}
			else
			{	// Comment status is open for all other topics
				$post_comment_status = 'open';
			}

			$topic_data = array(
				'post_creator_user_ID'       => $DB->quote( $author_ID ),
				'post_lastedit_user_ID'      => $DB->quote( $author_ID ),
				'post_main_cat_ID'           => $DB->quote( $forum_ID ),
				'post_title'                 => $DB->quote( $phpbb_topic->topic_title),
				'post_content'               => $DB->quote( $post_content ),
				'post_urltitle'              => $DB->quote( $canonical_slug ),
				'post_renderers'             => $DB->quote( 'b2evBBco.b2evALnk.b2WPAutP.evo_code' ),
				'post_datestart'             => $DB->quote( $topic_time ),
				'post_datecreated'           => $DB->quote( $topic_time ),
				'post_datemodified'          => $DB->quote( $topic_time ),
				'post_locale'                => $DB->quote( $phpbbBlog->get( 'locale' ) ),
				'post_status'                => $DB->quote( 'published' ),
				'post_comment_status'        => $DB->quote( $post_comment_status ),
				'post_featured'              => $DB->quote( $phpbb_topic->topic_type > 0 ? '1' : '0' ),
				'post_excerpt'               => $DB->quote( excerpt( $post_content ) ),
				'post_excerpt_autogenerated' => $DB->quote( '1' ),
				'post_wordcount'             => $DB->quote( bpost_count_words( $post_content ) ),
			);

			// *** EXECUTE QUERY TO INSERT NEW POST *** //
			$DB->query( 'INSERT INTO '.$tableprefix.'items__item ( '.implode( ', ', $topic_fields ).' )
					VALUES ( '.implode( ', ', $topic_data ).' )' );

			$item_ID = $DB->insert_id;

			$topics_IDs[$phpbb_topic->topic_id] = $item_ID;

			// Insert a link with a forum(category)
			$DB->query( 'INSERT INTO '.$tableprefix.'postcats ( postcat_post_ID, postcat_cat_ID )
					VALUES ( '.$DB->quote( $item_ID ).', '.$DB->quote( $forum_ID ).' )' );

			// Insert a canonical and second slugs for the post
			$DB->query( 'INSERT INTO '.$tableprefix.'slug ( slug_title, slug_type, slug_itm_ID ) VALUES
					( '.$DB->quote( $canonical_slug ).', '.$DB->quote( 'item' ).', '.$DB->quote( $item_ID ).' ),
					( '.$DB->quote( $second_slug ).', '.$DB->quote( 'item' ).', '.$DB->quote( $item_ID ).' )' );
			$canonical_slug_ID = $DB->insert_id;

			// Insert a tiny slug for the post
			/*
			load_funcs( 'slugs/model/_slug.funcs.php' );
			$DB->query( 'INSERT INTO T_slug ( slug_title, slug_type, slug_itm_ID )
				VALUES ( '.$DB->quote( getnext_tinyurl() ).', '.$DB->quote( 'item' ).', '.$DB->quote( $item_ID ).' )' );
			$tiny_slug_ID = $DB->insert_id;*/

			// Update the slug's IDs of the post
			$DB->query( 'UPDATE '.$tableprefix.'items__item
					  SET post_canonical_slug_ID = '.$DB->quote( $canonical_slug_ID )/*.', post_tiny_slug_ID = '.$DB->quote( $tiny_slug_ID )*/.'
					WHERE post_ID = '.$DB->quote( $item_ID ) );

			if( $path_attachments )
			{	// Import attachments of this topic:
				phpbb_import_attachments( 'itm', $path_attachments, $phpbb_topic->post_id, $item_ID );
			}

			$phpbb_topics_count_imported++;

			if( $phpbb_topics_count_imported % 100 == 0 )
			{	// Display the processing dots after 500 topics
				phpbb_log( ' .', 'message', '' );
			}
		}

		$page++;
	}
	while( count( $phpbb_topics ) > 0 );

	// Add temporary table to store the links between topics's IDs from phpbb and b2evo tables
	phpbb_table_add( 'topics' );
	phpbb_table_insert_links( 'topics', $topics_IDs );

	if( empty( $path_attachments ) )
	{	// Count missing attachments of the imported topics if the path is not correct:
		phpbb_count_missing_attachments( array_keys( $topics_IDs ) );
	}

	$DB->commit();

	phpbb_set_var( 'topics_count_imported', $phpbb_topics_count_imported );
}


/**
 * Decode html tags from bbcode string
 *
 * @param string Encoded string
 * @param string bbcode ID
 * @return string Decoded string
 */
function phpbb_decode_bbcode( $string, $bbcode_uid )
{
	/**** These lines were commented after plugin 'BB code' was installed by default ****

	$bbcode_patterns = array(
			'[b:'.$bbcode_uid.']', '[/b:'.$bbcode_uid.']',
			'[u:'.$bbcode_uid.']', '[/u:'.$bbcode_uid.']',
			'[i:'.$bbcode_uid.']', '[/i:'.$bbcode_uid.']',
			'[code:1:'.$bbcode_uid.']', '[/code:1:'.$bbcode_uid.']',
			'[code:'.$bbcode_uid.']', '[/code:'.$bbcode_uid.']',
			'[php:1:'.$bbcode_uid.']', '[/php:1:'.$bbcode_uid.']',
			'[quote:'.$bbcode_uid.']', '[/quote:'.$bbcode_uid.']',
			'[list:'.$bbcode_uid.'][*:'.$bbcode_uid.']', '[/list:u:'.$bbcode_uid.']',
			'[list=([a1]):'.$bbcode_uid.'][*:'.$bbcode_uid.']', '[/list:o:'.$bbcode_uid.']',
			'[*:'.$bbcode_uid.']',
			'[/color:'.$bbcode_uid.']',
			'[/size:'.$bbcode_uid.']'
		);
	$bbcode_encoded_tags = array(
			'<b>', '</b>',
			'<u>', '</u>',
			'<i>', '</i>',
			'<pre>', '</pre>',
			'<pre>', '</pre>',
			'<pre>', '</pre>',
			'<blockquote>', '</blockquote>',
			'<ul><li>', '</li></ul>',
			'<ol><li>', '</li></ol>',
			'</li><li>',
			'</span>',
			'</span>'
		);

	$string = str_replace( $bbcode_patterns, $bbcode_encoded_tags, $string );

	if( strpos( $string, '[' ) !== false )
	{	// Replace complex bbcoded tags
		$bbcode_patterns = array(
				'/\[quote:'.$bbcode_uid.'="(.+)"\]/si',
				'/\[url=(.+)\](.+)\[\/url\]/si',
				'/\[url\](.+)\[\/url\]/si',
				'/\[img:'.$bbcode_uid.'\](.+)\[\/img:'.$bbcode_uid.'\]/si',
				'/\[color=(\#[0-9A-F]{6}|[a-z]+):'.$bbcode_uid.'\]/si',
				'/\[size=([1-2]?[0-9]):'.$bbcode_uid.'\]/si',
				'/\[email\]([a-z0-9&\-_.]+?@[\w\-]+\.([\w\-\.]+\.)?[\w]+)\[\/email\]/si',
				'/\[list=([a1]):$uid\]/si'
			);
		$bbcode_encoded_tags = array(
				'<blockquote><b>$1:</b><br />',
				'<a href="$1" target="_blank">$2</a>',
				'<a href="$1" target="_blank">$1</a>',
				'<img src="$1" />',
				'<span style="color: $1">',
				'<span style="font-size: $1px">',
				'<a href="mailto:$1">$1</a>',
				'<ol type="$1">'
			);
		$string = preg_replace( $bbcode_patterns, $bbcode_encoded_tags, $string );
	}

	****/

	$bbcode_patterns = array(
			'[code:1:'.$bbcode_uid.']', '[/code:1:'.$bbcode_uid.']',
			'[code:'.$bbcode_uid.']', '[/code:'.$bbcode_uid.']',
			'[php:1:'.$bbcode_uid.']', '[/php:1:'.$bbcode_uid.']',
		);
	$bbcode_encoded_tags = array(
			'<!-- codeblock lang="" line="1" --><pre><code>', '</code></pre><!-- /codeblock -->',
			'<!-- codeblock lang="" line="1" --><pre><code>', '</code></pre><!-- /codeblock -->',
			'<!-- codeblock lang="php" line="1" --><pre><code>', '</code></pre><!-- /codeblock -->',
		);

	$string = str_replace( $bbcode_patterns, $bbcode_encoded_tags, $string );

	if( strpos( $string, '[' ) !== false )
	{	// Replace complex bbcoded tags
		$bbcode_patterns = array(
				'/\[url\]([^\1]+?)\[\/url\]/si',
				'/\[quote:([a-z0-9]+)="([^"]+)"\]/si',
				'/\[img([:a-z0-9]*?)\]([^\1]+?)\[\/img([:a-z0-9]*?)\]/si',
			);
		$bbcode_encoded_tags = array(
				'$1',
				'[quote=@$2]',
				'$2'
			);
		$string = preg_replace( $bbcode_patterns, $bbcode_encoded_tags, $string );
	}

	// We should remove bbcode_uid because plugin 'BB code' doesn't work with UID
	$string = str_replace( ':'.$bbcode_uid.']', ']', $string );

	// $string = nl2br( $string );

	return $string;
}


/**
 * Import the replies
 */
function phpbb_import_replies()
{
	global $DB, $phpbb_DB, $tableprefix, $phpbb_version;

	if( !phpbb_check_step( 'replies' ) )
	{	// Check current step
		return; // Exit here if we cannot process this step
	}

	$import_forums = phpbb_get_var( 'import_forums' );

	// Reset previous value
	phpbb_unset_var( 'replies_count_imported' );

	phpbb_log( T_('Importing replies...') );

	$DB->begin();

	// Init SQL to get the replies data and the count of the replies
	$SQL = new SQL();
	$SQL->FROM( 'BB_posts p' );
	if( $phpbb_version == 2 )
	{	// pnpBB v2:
		$SQL->FROM_add( 'LEFT JOIN BB_posts_text pt ON p.post_id = pt.post_id' );
	}
	//$SQL->FROM_add( 'LEFT JOIN BB_topics t ON p.post_id = t.topic_first_post_id' );
	//$SQL->WHERE( 't.topic_id IS NULL' );
	if( !empty( $import_forums ) )
	{	// Select the replies only from these forums
		$SQL->WHERE( 'p.forum_id IN ( '.$phpbb_DB->quote( $import_forums ).' )' );
	}
	else
	{	// If no forums to import
		$SQL->WHERE( 'p.forum_id = -1' );
	}
	//$SQL->WHERE_and( 'p.post_id NOT IN ( SELECT topic_first_post_id FROM BB_topics )' );

	// Get the count of the replies
	$count_SQL = $SQL;
	$count_SQL->SELECT( 'COUNT( p.post_id )' );
	$phpbb_replies_count = $phpbb_DB->get_var( $count_SQL->get() );

	if( $phpbb_replies_count > 0 )
	{
		phpbb_log( sprintf( T_('%s post have been found in the phpBB database, %s of which are replies'), $phpbb_replies_count, $phpbb_replies_count - (int)phpbb_get_var( 'topics_count_imported' ) ) );
	}
	else
	{	// No replies
		phpbb_log( T_('No found replies in the phpBB database.'), 'error' );
		$DB->commit();
		return; // Exit here
	}

	$users_IDs = phpbb_table_get_links( 'users' );
	$topics_IDs = phpbb_table_get_links( 'topics' );

	phpbb_log( T_('Start importing <b>replies</b> as <b>comments</b> into the b2evolution database...'), 'message', '' );

	$BlogCache = & get_BlogCache();
	$phpbbBlog = & $BlogCache->get_by_ID( phpbb_get_var( 'blog_ID' ) );


	// Init SQL to get the replies from phpbb database
	$SQL = $SQL;
	if( $phpbb_version == 3 )
	{	// pnpBB v3:
		$SQL->SELECT( 'p.post_id, p.topic_id, p.post_time, p.post_edit_time, p.poster_id, p.poster_ip, p.post_username, p.post_text, p.bbcode_uid' );
	}
	else
	{	// pnpBB v2:
		$SQL->SELECT( 'p.post_id, p.topic_id, p.post_time, p.post_edit_time, p.poster_id, p.poster_ip, p.post_username, pt.post_text, pt.bbcode_uid' );
	}
	$SQL->ORDER_BY( 'p.post_id' );

	$page = 0;
	$page_size = 1000;
	$phpbb_replies_count_imported = 0;
	$comments_import_data = array();
	$comments_slugs_import_data = array();
	$replies_IDs = array();
	do
	{	// Split by page to optimize process
		// It gives to save the memory rather than if we get all replies by one query without LIMIT clause

		// Get the replies from phpbb database by page
		$SQL->LIMIT( ( $page * $page_size ).', '.$page_size );
		$phpbb_replies = $phpbb_DB->get_results( $SQL->get() );

		// Insert the new replies
		foreach( $phpbb_replies as $p => $phpbb_reply )
		{
			if( phpbb_post_is_topic( $phpbb_reply->post_id ) )
			{	// This post is a content of the topic
				// It is first post; for b2evo this post is Item, not Comment
				// Do NOT import this post as Comment
				//phpbb_log( sprintf( '<br />'.T_('Skipped reply: %s. The reply is first post of the topic. <b>Content:</b> %s'), $phpbb_reply->post_id, substr( $phpbb_reply->post_text, 0, 250 ).' ...' ), 'error' );
				continue;
			}

			if( !isset( $topics_IDs[ (string) $phpbb_reply->topic_id ] ) )
			{	// The reply has the incorrect topic's ID by some reason
				phpbb_log( sprintf( '<br />'.T_('Skipped reply: %s. Incorrect topic ID: %s. <b>Content:</b> %s'), $phpbb_reply->post_id, $phpbb_reply->topic_id, substr( $phpbb_reply->post_text, 0, 250 ).' ...' ), 'error' );
				continue;
			}

			if( $phpbb_reply->poster_id == '-1' )
			{	// Comment from anonymous user
				$author_ID = 'NULL';
				$author_name = $DB->quote( $phpbb_reply->post_username );
			}
			else if( isset( $users_IDs[ (string) $phpbb_reply->poster_id ] ) )
			{	// Author is registered user
				$author_ID = $DB->quote( $users_IDs[ (string) $phpbb_reply->poster_id ] );
				$author_name = 'NULL';
			}
			else
			{	// Incorrect user ID, Do NOT import this reply
				if( !phpbb_import_invalid_user( $phpbb_reply->poster_id, $users_IDs, $phpbb_reply->post_username ) )
				{	// We cannot create invalid user
					phpbb_log( sprintf( '<br />'.T_('Skipped reply: %s. Incorrect user ID: %s. <b>Content:</b> %s'), $phpbb_reply->post_id, $phpbb_reply->poster_id, substr( $phpbb_reply->post_text, 0, 250 ).' ...' ), 'error' );
					continue;
				}
			}

			$comment_data = array(
				'comment_item_ID'        => $DB->quote( $topics_IDs[ (string) $phpbb_reply->topic_id ] ),
				'comment_author_user_ID' => $author_ID,
				'comment_date'           => $DB->quote( date( 'Y-m-d H:i:s', $phpbb_reply->post_time ) ),
				'comment_author_IP'      => $DB->quote( phpbb_decode_ip( $phpbb_reply->poster_ip ) ),
				'comment_author'         => $author_name,
				'comment_content'        => $DB->quote( phpbb_decode_bbcode( $phpbb_reply->post_text, $phpbb_reply->bbcode_uid ) ),
				'comment_renderers'      => $DB->quote( 'b2evBBco.b2evALnk.b2WPAutP.evo_code' ),
				'comment_status'         => $DB->quote( 'published' ),
			);

			$replies_IDs[] = $phpbb_reply->post_id;

			$comments_import_data[ $phpbb_reply->post_id ] = '( '.implode( ', ', $comment_data ).' )';

			$comments_slugs_import_data[] = '( '.$DB->quote( 'forumpost-'.$phpbb_reply->post_id ).', '.$DB->quote( 'item' ).', '.$DB->quote( $topics_IDs[ (string) $phpbb_reply->topic_id ] ).' )';

			if( count( $comments_import_data ) == 100 )
			{	// Insert the 100 comments in one query:
				phpbb_insert_comments( $comments_import_data, $comments_slugs_import_data );
				// Clear arrays for next portion:
				$comments_import_data = array();
				$comments_slugs_import_data = array();
			}

			$phpbb_replies_count_imported++;

			if( $phpbb_replies_count_imported % 1000 == 0 )
			{	// Display the processing dots after 1000 topics
				phpbb_log( ' .', 'message', '' );
			}
		}

		$page++;
	}
	while( count( $phpbb_replies ) > 0 );

	if( count( $comments_import_data ) > 0 )
	{	// Insert the rest comments:
		phpbb_insert_comments( $comments_import_data, $comments_slugs_import_data );
	}

	// Get a path where we should import the reply attachments from:
	$path_attachments = phpbb_get_import_path( 'path_attachments' );

	if( empty( $path_attachments ) )
	{	// Count missing attachments of the imported replies if the path is not correct:
		phpbb_count_missing_attachments( $replies_IDs );
	}

	$DB->commit();

	phpbb_set_var( 'replies_count_imported', $phpbb_replies_count_imported );
}


/**
 * Insert comments in DB
 *
 * @param array Query data for the comments table
 * @param array Query data for slugs of the comments
 */
function phpbb_insert_comments( $comments_import_data, $comments_slugs_import_data )
{
	global $DB, $tableprefix;

	$comment_insert_result = $DB->query( 'INSERT INTO '.$tableprefix.'comments
			( comment_item_ID, comment_author_user_ID, comment_date, comment_author_IP, comment_author, comment_content, comment_renderers, comment_status )
			VALUES '.implode( ', ', $comments_import_data ) );

	if( ! $comment_insert_result )
	{	// Some errors
		phpbb_log( '<br />'.sprintf( T_( 'MySQL error: %s.' ) , $DB->last_error ), 'error', ' ' );
	}
	else
	{
		// Get a path where we should import the reply attachments from:
		$path_attachments = phpbb_get_import_path( 'path_attachments' );

		if( $path_attachments )
		{	// If attachments should be inserted
			// Get IDs of the last 100 inserted comments in single query above:
			$last_inserted_comment_IDs = phpbb_get_last_inserted_IDs( $tableprefix.'comments', 'comment_ID', count( $comments_import_data ) );

			$attachments_insert_queries = array();
			$c = 0;
			foreach( $comments_import_data as $reply_ID => $insert_data )
			{	// Import attachments of the last 100 replies:
				$attachments_insert_queries = array_merge( $attachments_insert_queries, phpbb_get_attachments_insert_data( 'cmt', $path_attachments, $reply_ID, $last_inserted_comment_IDs[ $c ] ) );
				$c++;
			}
			// Insert the links of all 100 comments in single query:
			phpbb_insert_attachments( 'cmt', $attachments_insert_queries );
		}
	}

	// Insert the slugs for the replies
	$DB->query( 'INSERT INTO '.$tableprefix.'slug ( slug_title, slug_type, slug_itm_ID )
			VALUES '.implode( ', ', $comments_slugs_import_data ) );
}

/**
 * Decode IP address from phpBB database
 *
 * @param string IP encoded
 * @return string IP decoded
 */
function phpbb_decode_ip( $int_ip )
{
	$hexipbang = explode( '.', chunk_split( $int_ip, 2, '.' ) );
	return count( $hexipbang ) == 4 ? hexdec( $hexipbang[0] ).'.'.hexdec( $hexipbang[1] ).'.'.hexdec( $hexipbang[2] ).'.'.hexdec( $hexipbang[3] ) : '';
}


/**
 * Check if the post is a topic
 *
 * @param string post ID
 * @return boolean TRUE if the post is a topic
*/
function phpbb_post_is_topic( $post_id )
{
	global $phpbb_cache_topics_posts;

	if( isset( $phpbb_cache_topics_posts ) )
	{	// Get result from cache
		return isset( $phpbb_cache_topics_posts[ (string) $post_id ] );
	}

	global $phpbb_DB;
	$phpbb_db_config = phpbb_get_var( 'db_config' );

	// Get result from DB first time
	$SQL = new SQL();
	$SQL->SELECT( 'topic_first_post_id, topic_id' );
	$SQL->FROM( $phpbb_db_config['prefix'].'topics' );
	$SQL->WHERE( 'topic_status != 2' ); // Don't select MOVIED topics

	$phpbb_cache_topics_posts = array();
	$phpbb_DB->query($SQL->get());
	while( $tp_row = $phpbb_DB->get_row(NULL, ARRAY_A) )
	{
		$phpbb_cache_topics_posts[ $tp_row['topic_first_post_id'] ] = $tp_row['topic_id'];
	}

	return isset( $phpbb_cache_topics_posts[ (string) $post_id ] );
}


/**
 * Import the messages
 */
function phpbb_import_messages()
{
	global $DB, $phpbb_DB, $tableprefix, $phpbb_version;

	if( !phpbb_check_step( 'messages' ) )
	{	// Check current step
		return; // Exit here if we cannot process this step
	}

	// Reset previous value
	phpbb_unset_var( 'messages_count_imported' );

	phpbb_log( T_('Importing messages...') );

	$DB->begin();

	// Init SQL to get the messages data and the count of the messages
	$SQL = new SQL();
	$SQL->FROM( 'BB_privmsgs m' );
	if( $phpbb_version == 3 )
	{	// pnpBB v3:
		$SQL->FROM_add( 'INNER JOIN BB_privmsgs_to mt ON m.msg_id = mt.msg_id' );
	}
	else
	{	// pnpBB v2:
		$SQL->FROM_add( 'INNER JOIN BB_privmsgs_text mt ON m.privmsgs_id = mt.privmsgs_text_id' );
	}

	// Get the count of the messages
	$count_SQL = $SQL;
	if( $phpbb_version == 3 )
	{	// pnpBB v3:
		$count_SQL->SELECT( 'COUNT( DISTINCT m.msg_id )' );
	}
	else
	{	// pnpBB v2:
		$count_SQL->SELECT( 'COUNT( DISTINCT m.privmsgs_id )' );
	}
	$phpbb_messages_count = $phpbb_DB->get_var( $count_SQL->get() );

	if( $phpbb_messages_count > 0 )
	{
		phpbb_log( sprintf( T_('%s messages have been found in the phpBB database'), $phpbb_messages_count ) );
	}
	else
	{	// No messages
		phpbb_log( T_('No found messages in the phpBB database.'), 'error' );
		$DB->commit();
		return; // Exit here
	}

	$users_IDs = phpbb_table_get_links( 'users' );

	phpbb_log( T_('Start importing <b>private messages</b> into the b2evolution database...'), 'message', '' );

	// Init SQL to get the messages from phpbb database:
	if( $phpbb_version == 3 )
	{	// pnpBB v3:
		$SQL->SELECT( 'm.msg_id AS id, m.message_subject AS subject, m.message_text AS text, m.message_time AS time, m.author_id AS from_user_id, mt.user_id AS to_user_id, m.bbcode_uid' );
		$SQL->WHERE( 'm.message_subject NOT LIKE \'Re: %\' ' );
		$SQL->ORDER_BY( 'm.message_time' );
		$SQL->GROUP_BY( 'm.author_id, mt.user_id, m.message_time' );
	}
	else
	{	// pnpBB v2:
		$SQL->SELECT( 'm.privmsgs_id AS id, m.privmsgs_subject AS subject, mt.privmsgs_text AS text, m.privmsgs_date AS time, m.privmsgs_from_userid AS from_user_id, m.privmsgs_to_userid AS to_user_id, mt.privmsgs_bbcode_uid AS bbcode_uid' );
		$SQL->WHERE( 'm.privmsgs_subject NOT LIKE \'Re: %\' ' );
		$SQL->ORDER_BY( 'm.privmsgs_date' );
		$SQL->GROUP_BY( 'm.privmsgs_from_userid, m.privmsgs_to_userid, m.privmsgs_date' );
	}

	$page = 0;
	$page_size = 100;
	$phpbb_messages_count_imported = 0;
	$phpbb_missing_users = 0;
	do
	{	// Split by page to optimize process
		// It gives to save the memory rather than if we get all replies by one query without LIMIT clause

		// Get the messages from phpbb database
		$SQL->LIMIT( ( $page * $page_size ).', '.$page_size );
		$phpbb_messages = $phpbb_DB->get_results( $SQL->get() );

		foreach( $phpbb_messages as $message )
		{
			if( !isset( $users_IDs[ (string) $message->from_user_id ] ) )
			{	// The message has the incorrect user's ID by some reason
				/*if( !phpbb_import_invalid_user( $message->from_user_id, $users_IDs ) )
				{	// We cannot create invalid user
					phpbb_log( sprintf( '<br />'.T_('Skipped message: %s. Incorrect user ID: %s. <b>Content:</b> %s'), $message->id, $message->from_user_id, substr( $message->subject, 0, 250 ).' ...' ), 'error' );
					continue;
				}*/
				$phpbb_missing_users++;
				//phpbb_log( sprintf( '<br />'.T_('Skipped message: %s. Incorrect sender user ID: %s. <b>Content:</b> %s'), $message->id, $message->from_user_id, substr( $message->subject, 0, 250 ).' ...' ), 'error', ' ' );
				continue;
			}
			if( !isset( $users_IDs[ (string) $message->to_user_id ] ) )
			{	// The message has the incorrect user's ID by some reason
				$phpbb_missing_users++;
				//phpbb_log( sprintf( '<br />'.T_('Skipped message: %s. Incorrect reciever user ID: %s. <b>Content:</b> %s'), $message->id, $message->to_user_id, substr( $message->subject, 0, 250 ).' ...' ), 'error', ' ' );
				continue;
			}

			$DB->query( 'INSERT INTO '.$tableprefix.'messaging__thread ( thrd_title, thrd_datemodified )
					VALUES ( '.$DB->quote( $message->subject ).', '.$DB->quote( date( 'Y-m-d H:i:s', $message->time ) ).' )' );

			$thread_ID = $DB->insert_id;

			// Import all messages from this thread
			$count_messages = phpbb_import_messages_texts( $thread_ID, $message );

			$phpbb_messages_count_imported += $count_messages;

			if( $phpbb_messages_count_imported % 1000 == 0 )
			{	// Display the processing dots after 1000 topics
				phpbb_log( ' .', 'message', '' );
			}
		}

		$page++;
	}
	while( count( $phpbb_messages ) > 0 );

	$DB->commit();

	phpbb_set_var( 'messages_count_imported', $phpbb_messages_count_imported );
	phpbb_set_var( 'messages_count_missing_users', $phpbb_missing_users );
}


/**
 * Import all messages from the thread
 *
 * @param integer Thread ID
 * @param array Message
 * @return integer Number of the imported messaqes
 */
function phpbb_import_messages_texts( $thread_ID, $message )
{
	global $DB, $phpbb_DB, $tableprefix, $phpbb_version;

	// Get a path where we should import the message attachments from:
	$path_attachments = phpbb_get_import_path( 'path_attachments' );

	$SQL = new SQL();
	if( $phpbb_version == 3 )
	{	// pnpBB v3:
		$SQL->SELECT( 'm.msg_id AS id, m.message_subject AS subject, m.message_text AS text, m.message_time AS time, m.author_id AS from_user_id, mt.user_id AS to_user_id, m.bbcode_uid' );
		$SQL->FROM( 'BB_privmsgs m' );
		$SQL->FROM_add( 'INNER JOIN BB_privmsgs_to mt ON m.msg_id = mt.msg_id' );
		$SQL->WHERE( 'm.message_subject = '.$DB->quote( 'Re: '.$message->subject ) );
		$SQL->WHERE_and( 'm.author_id IN ( '.$DB->quote( $message->from_user_id ).', '.$DB->quote( $message->to_user_id ).' )' );
		$SQL->WHERE_and( 'mt.user_id IN ( '.$DB->quote( $message->from_user_id ).', '.$DB->quote( $message->to_user_id ).' )' );
		$SQL->ORDER_BY( 'm.message_time' );
		$SQL->GROUP_BY( 'm.author_id, mt.user_id, m.message_time' );
	}
	else
	{	// pnpBB v2:
		$SQL->SELECT( 'm.privmsgs_id AS id, m.privmsgs_subject AS subject, mt.privmsgs_text AS text, m.privmsgs_date AS time, m.privmsgs_from_userid AS from_user_id, m.privmsgs_to_userid AS to_user_id, mt.privmsgs_bbcode_uid AS bbcode_uid' );
		$SQL->FROM( 'BB_privmsgs m' );
		$SQL->FROM_add( 'INNER JOIN BB_privmsgs_text mt ON m.privmsgs_id = mt.privmsgs_text_id' );
		$SQL->WHERE( 'm.privmsgs_subject = '.$DB->quote( 'Re: '.$message->subject ) );
		$SQL->WHERE_and( 'm.privmsgs_from_userid IN ( '.$DB->quote( $message->from_user_id ).', '.$DB->quote( $message->to_user_id ).' )' );
		$SQL->WHERE_and( 'm.privmsgs_to_userid IN ( '.$DB->quote( $message->from_user_id ).', '.$DB->quote( $message->to_user_id ).' )' );
		$SQL->ORDER_BY( 'm.privmsgs_date' );
		$SQL->GROUP_BY( 'm.privmsgs_from_userid, m.privmsgs_to_userid, m.privmsgs_date' );
	}
	$phpbb_messages = $phpbb_DB->get_results( $SQL->get() );

	$phpbb_messages = array_merge( $phpbb_messages, array( $message ) );

	$users_IDs = phpbb_table_get_links( 'users' );

	$message_import_data = array();
	$threadstatus_import_data = array();
	foreach( $phpbb_messages as $message )
	{
		if( !isset( $users_IDs[ $message->from_user_id ] ) || !isset( $users_IDs[ $message->to_user_id ] ) )
		{	// No users
			continue;
		}

		$message_import_data[ $message->id ] = '( '.
				$DB->quote( $users_IDs[ $message->from_user_id ] ).', '.
				$DB->quote( date( 'Y-m-d H:i:s', $message->time ) ).', '.
				$DB->quote( $thread_ID ).', '.
				$DB->quote( phpbb_decode_bbcode( $message->text, $message->bbcode_uid ) ).', '.
				'\'default\''.
			' )';
		$threadstatus_import_data[ $message->to_user_id ] = '( '.
				$DB->quote( $thread_ID ).', '.
				$DB->quote( $users_IDs[ $message->to_user_id ] ).', '.
				'NULL'.
			' )';
		$threadstatus_import_data[ $message->from_user_id ] = '( '.
				$DB->quote( $thread_ID ).', '.
				$DB->quote( $users_IDs[ $message->from_user_id ] ).', '.
				'NULL'.
			' )';
	}

	$DB->query( 'INSERT INTO '.$tableprefix.'messaging__message ( msg_author_user_ID, msg_datetime, msg_thread_ID, msg_text, msg_renderers )
			VALUES '.implode( ', ', $message_import_data ) );

	$DB->insert_id;

	if( $path_attachments )
	{	// If attachments should be inserted
		// Get IDs of the last inserted messages in single query above:
		$last_inserted_msg_IDs = phpbb_get_last_inserted_IDs( $tableprefix.'messaging__message', 'msg_ID', count( $message_import_data ) );

		$m = 0;
		foreach( $message_import_data as $message_id => $insert_data )
		{	// Import attachments of the inserted private message:
			phpbb_import_attachments( 'msg', $path_attachments, $message_id, $last_inserted_msg_IDs[ $m ] );
			$m++;
		}
	}
	else
	{	// Count missing attachments of the imported messages if the path is not correct:
		phpbb_count_missing_attachments( array_keys( $message_import_data ) );
	}

	$DB->query( 'INSERT INTO '.$tableprefix.'messaging__threadstatus ( tsta_thread_ID, tsta_user_ID, tsta_first_unread_msg_ID )
			VALUES '.implode( ', ', $threadstatus_import_data ) );

	return count( $message_import_data );
}


/**
 * Clear all temporary data which are used during import
 */
function phpbb_clear_temporary_data()
{
	// Delete DB tables
	phpbb_table_delete( 'users' );
	phpbb_table_delete( 'forums' );
	phpbb_table_delete( 'topics' );

	// Delete the session variables
	phpbb_unset_var( 'blog_ID' );
	phpbb_unset_var( 'group_default' );
	phpbb_unset_var( 'all_group_default' );
	phpbb_unset_var( 'users_count_imported' );
	phpbb_unset_var( 'users_count_updated' );
	phpbb_unset_var( 'forums_count_imported' );
	phpbb_unset_var( 'topics_count_imported' );
	phpbb_unset_var( 'replies_count_imported' );
	phpbb_unset_var( 'messages_count_imported' );
	phpbb_unset_var( 'attachments_count_imported' );
	phpbb_unset_var( 'attachments_count_missing' );
	phpbb_unset_var( 'avatars_count_imported' );
	phpbb_unset_var( 'avatars_count_missing' );
}


/**
 * Display forums of phpBB to select what to import
 *
 * @param object Form
 */
function phpbb_forums_list( & $Form )
{
	global $phpbb_DB, $phpbb_subforums_list_level, $phpbb_version;

	$phpbb_DB->begin();

	// Get the categories from phpbb database:
	if( $phpbb_version == 3 )
	{	// pnpBB v3:
		$cats_SQL = new SQL();
		$cats_SQL->SELECT( 'forum_id AS cat_id, forum_name AS cat_title' );
		$cats_SQL->FROM( 'BB_forums' );
		$cats_SQL->WHERE( 'parent_id = 0' );
		$cats_SQL->ORDER_BY( 'right_id' );
	}
	else
	{	// pnpBB v2:
		$cats_SQL = new SQL();
		$cats_SQL->SELECT( 'cat_id, cat_title' );
		$cats_SQL->FROM( 'BB_categories' );
		$cats_SQL->ORDER_BY( 'cat_order' );
	}
	$categories = $phpbb_DB->get_results( $cats_SQL->get() );

	$import_categories = phpbb_get_var( 'import_categories' );
	foreach( $categories as $category )
	{
		$Form->checkbox_input( 'phpbb_categories[]', !is_array( $import_categories ) || in_array( $category->cat_id, $import_categories ), '', array(
				'input_suffix' => $category->cat_title,
				'value' => $category->cat_id
			) );

		// Display forums
		$phpbb_subforums_list_level = 0;
		phpbb_subforums_list( $Form, $category->cat_id );
	}

	$phpbb_DB->commit();

	echo '<script type="text/javascript">
	/* <![CDATA[ */
	jQuery( document ).ready( function()
	{
		jQuery( "input[name^=phpbb_categories]" ).click( function()
		{
			if( jQuery( "div.phpbb_forums_" + jQuery( this ).val() + "_0" ).length > 0 )
			{	// Check/Uncheck child forums
				if( jQuery( this ).is( ":checked" ) )
				{
					jQuery( "div.phpbb_forums_" + jQuery( this ).val() + "_0 input[type=checkbox]" ).attr( "checked", "checked" );
				}
				else
				{
					jQuery( "div.phpbb_forums_" + jQuery( this ).val() + "_0 input[type=checkbox]" ).removeAttr( "checked" );
				}
			}
		} );

		jQuery( "input[name^=phpbb_forums]" ).click( function()
		{
			if( jQuery( "div.phpbb_forums_0_" + jQuery( this ).val() ).length > 0 )
			{	// Check/Uncheck child forums
				if( jQuery( this ).is( ":checked" ) )
				{
					jQuery( "div.phpbb_forums_0_" + jQuery( this ).val() + " input[type=checkbox]" ).attr( "checked", "checked" );
				}
				else
				{
					jQuery( "div.phpbb_forums_0_" + jQuery( this ).val() + " input[type=checkbox]" ).removeAttr( "checked" );
				}
			}

			if( jQuery( this ).is( ":checked" ) )
			{	// Check parent forums
				jQuery( this ).parents( "div[class^=phpbb_forums_]" ).prev().find( "input[type=checkbox]" ).attr( "checked", "checked" );
			}
		} );
	} );
	/* ]]> */
	</script>';
}


/**
 * Display subforums to select what to import
 *
 * @param object Form
 * @param integer Category ID
 * @param integer Forum parent ID
 */
function phpbb_subforums_list( & $Form, $cat_id, $forum_parent_id = 0 )
{
	global $phpbb_DB, $phpbb_subforums_list_level, $phpbb_version;

	// Get the forums from phpbb database:
	$forums_SQL = new SQL();
	$forums_SQL->SELECT( 'f.forum_id, f.forum_name' );
	$forums_SQL->FROM( 'BB_forums f' );
	if( $phpbb_version == 3 )
	{	// pnpBB v3:
		$forums_SQL->FROM_add( 'LEFT JOIN BB_forums f2 ON f.parent_id = f2.forum_id' );
		if( $cat_id > 0 )
		{	// Get all top forums of the category:
			$forums_SQL->WHERE( 'f.parent_id = '.$phpbb_DB->quote( $cat_id ) );
		}
		elseif( $forum_parent_id > 0 )
		{	// Get subforums:
			$forums_SQL->WHERE( 'f.parent_id = '.$phpbb_DB->quote( $forum_parent_id ) );
		}
		else
		{	// Wrong a call of this function
			return;
		}
		$forums_SQL->ORDER_BY( 'f2.right_id, f.right_id' );
	}
	else
	{	// pnpBB v2:
		$forums_SQL->FROM_add( 'LEFT JOIN BB_categories c ON f.cat_id = c.cat_id' );
		if( $cat_id > 0 )
		{	// Get all top forums of the category:
			$forums_SQL->WHERE( 'f.cat_id = '.$phpbb_DB->quote( $cat_id ) );
			$forums_SQL->WHERE_AND( 'f.forum_parent = 0' );
		}
		elseif( $forum_parent_id > 0 )
		{	// Get subforums:
			$forums_SQL->WHERE( 'f.forum_parent = '.$phpbb_DB->quote( $forum_parent_id ) );
		}
		else
		{	// Wrong a call of this function
			return;
		}
		$forums_SQL->ORDER_BY( 'c.cat_order, f.forum_order' );
	}
	$forums = $phpbb_DB->get_results( $forums_SQL->get() );

	if( count( $forums ) == 0 )
	{
		return;
	}

	$phpbb_subforums_list_level++;

	// Group all subforums in one div
	echo '<div class="phpbb_forums_'.$cat_id.'_'.$forum_parent_id.'">';

	$import_forums = phpbb_get_var( 'import_forums' );
	foreach( $forums as $forum )
	{	// Display forums
		$Form->checkbox_input( 'phpbb_forums[]', !is_array( $import_forums ) || in_array( $forum->forum_id, $import_forums ), '', array(
				'input_prefix' => '<span style="margin-left:'.( $phpbb_subforums_list_level * 20 ).'px">',
				'input_suffix' => ' '.$forum->forum_name.'</span>',
				'value' => $forum->forum_id,
			) );
		phpbb_subforums_list( $Form, 0, $forum->forum_id );
	}

	echo '</div>';

	$phpbb_subforums_list_level--;
}


/**
 * Get config value from phpBB DB
 *
 * @param string Config name
 * @return string Config value
 */
function phpbb_get_config( $name )
{
	global $phpbb_DB, $phpbb_config;

	if( ! is_array( $phpbb_config ) )
	{	// Initialize config array only first time:
		$phpbb_config = array();
	}

	if( ! isset( $phpbb_config[ $name ] ) )
	{	// Get value only first time and store in cache array:
		$SQL = new SQL( 'Get phpBB config value of "'.$name.'" from DB' );
		$SQL->SELECT( 'config_value' );
		$SQL->FROM( 'BB_config' );
		$SQL->WHERE( 'config_name = '.$phpbb_DB->quote( $name ) );
		$phpbb_config[ $name ] = $phpbb_DB->get_var( $SQL->get(), 0, NULL, $SQL->title );
	}

	return $phpbb_config[ $name ];
}


/**
 * Get a path of import files
 *
 * @param string Path var name: 'path_avatars', 'path_attachments'
 */
function phpbb_get_import_path( $path_var_name )
{
	$path_avatars = phpbb_get_var( $path_var_name );

	if( ! empty( $path_avatars ) )
	{
		$path_avatars = preg_replace( '/(\/|\\\\)$/i', '', $path_avatars ).'/';
		if( ! empty( $path_avatars ) && file_exists( $path_avatars ) && is_dir( $path_avatars ) )
		{	// Folder with files is correct, we can import them:
			return $path_avatars;
		}
	}

	return false;
}


/**
 * Import user's avatar
 *
 * @param object User (from phpBB)
 * @param integer User ID (from b2evo)
 * @param string Path avatars
 * @return boolean TRUE on successful importing or if user has no avatar, FALSE on error
 */
function phpbb_import_avatar( $phpbb_user, $user_ID, $path_avatars )
{
	global $DB, $tableprefix, $phpbb_version;

	$user_avatar = $phpbb_user->user_avatar;

	if( empty( $user_avatar ) )
	{	// User has no avatar, Don't try to import:
		return true;
	}

	if( empty( $path_avatars ) )
	{	// Only count what missing avatars if the path is not correct:
		phpbb_log( sprintf( T_( 'Impossible to copy avatar file of the user #%s(%s) because the source for avatars %s is wrong.' ),
			$phpbb_user->user_id, $phpbb_user->username.' / '.$phpbb_user->user_email, '<code>'.phpbb_get_var( 'path_avatars' ).'</code>' ), 'error', ' ', '<br />' );
		phpbb_set_var( 'avatars_count_missing', phpbb_get_var( 'avatars_count_missing' ) + 1 );
		return false;
	}

	if( $phpbb_version == 3 )
	{	// phpBB v3:
		if( preg_match( '#^https?://#', $phpbb_user->user_avatar ) )
		{	// The avatar file is loaded from remoter url:
			$avatar_file_type = 'url';
			$avatar_file_url = $phpbb_user->user_avatar;
		}
		else
		{	// The avatar file is located on the disk:
			$avatar_file_type = 'file';
			$avatar_extension = preg_replace( '#^[^\.]+(\.[a-z]+)$#i', '$1', $phpbb_user->user_avatar );
			$avatar_file_name = phpbb_get_config( 'avatar_salt' ).'_'.$phpbb_user->user_id.$avatar_extension;
			$avatar_file_path = $path_avatars.$avatar_file_name;
		}
	}
	else
	{	// phpBB v2:
		$avatar_file_type = 'file';
		$avatar_file_name = $phpbb_user->user_avatar;
		$avatar_file_path = $path_avatars.$avatar_file_name;
	}

	if( $avatar_file_type == 'url' )
	{	// Try to get a file from remote url:
		$avatar_file_content = fetch_remote_page( $avatar_file_url, $avatar_file_url_info );
		if( $avatar_file_content === false )
		{	// Some server restriction to get files from remote server:
			$avatar_file_url_error = ' '.sprintf( T_('Error: %s'), $avatar_file_url_info['message'] )
				.'; '.sprintf( T_('Status code: %s'), $avatar_file_url_info['status'] );
			phpbb_log( sprintf( T_( 'Impossible to get avatar file of the user #%s(%s) because your server has a restriction to get avatar file from remote url %s.' ),
				$phpbb_user->user_id, $phpbb_user->username.' / '.$phpbb_user->user_email, '<code>'.$avatar_file_url.'</code>' ).$avatar_file_url_error, 'error', ' ', '<br />' );
			// Update the count of missing avatars:
			phpbb_set_var( 'avatars_count_missing', phpbb_get_var( 'avatars_count_missing' ) + 1 );
			return false;
		}
		if( empty( $avatar_file_url_info['mimetype'] ) || ! in_array( $avatar_file_url_info['mimetype'], array( 'image/gif', 'image/jpeg', 'image/png' ) ) )
		{	// Wrong image type of remote url:
			phpbb_log( sprintf( T_( 'Impossible to get avatar file of the user #%s(%s) because wrong image type of the remote avatar file %s.' ),
				$phpbb_user->user_id, $phpbb_user->username.' / '.$phpbb_user->user_email, '<code>'.$avatar_file_url.'</code>' ), 'error', ' ', '<br />' );
			// Update the count of missing avatars:
			phpbb_set_var( 'avatars_count_missing', phpbb_get_var( 'avatars_count_missing' ) + 1 );
			return false;
		}
		switch( $avatar_file_url_info['mimetype'] )
		{
			case 'image/gif':
				$avatar_extension = 'gif';
				break;
			case 'image/jpeg':
				$avatar_extension = 'jpg';
				break;
			case 'image/png':
				$avatar_extension = 'png';
				break;
		}
	}
	else
	{	// Check if the avatar file really exists on the disk:
		if( ! file_exists( $avatar_file_path ) )
		{	// Display an error if avatar file is not found on the disk:
			phpbb_log( sprintf( T_( 'Avatar file of the user #%s(%s) is not found on %s.' ),
				$phpbb_user->user_id, $phpbb_user->username.' / '.$phpbb_user->user_email, '<code>'.$avatar_file_path.'</code>' ), 'error', ' ', '<br />' );
			// Update the count of missing avatars:
			phpbb_set_var( 'avatars_count_missing', phpbb_get_var( 'avatars_count_missing' ) + 1 );
			return false;
		}
	}

	// Get File Root of user:
	$FileRootCache = & get_FileRootCache();
	$user_FileRoot = & $FileRootCache->get_by_type_and_ID( 'user', $user_ID );

	if( $avatar_file_type == 'url' )
	{	// Create avatar file from remote url:
		$avatar_file_name = phpbb_get_config( 'avatar_salt' ).'_'.$phpbb_user->user_id.'.'.$avatar_extension;
		$user_File = new File( 'user', $user_ID, 'profile_pictures/'.$avatar_file_name );
		if( ! $user_File->create() ||
		    ! ( $avatar_file_handle = fopen( $user_File->_adfp_full_path, 'w' ) ) )
		{	// Impossible to create new file:
			phpbb_log( sprintf( T_( 'Impossible to create new avatar file of the user #%s(%s) from the remote url %s on %s.' ),
				$phpbb_user->user_id, $phpbb_user->username.' / '.$phpbb_user->user_email, '<code>'.$avatar_file_url.'</code>', '<code>'.$user_File->_adfp_full_path.'</code>' ), 'error', ' ', '<br />' );
			// Update the count of missing avatars:
			phpbb_set_var( 'avatars_count_missing', phpbb_get_var( 'avatars_count_missing' ) + 1 );
			return false;
		}
		// Write a content of remote avatar to local file on disk:
		fwrite( $avatar_file_handle, $avatar_file_content );
		fclose( $avatar_file_handle );

		$imported_file_ID = $user_File->ID;

		if( empty( $imported_file_ID ) )
		{	// Display an error if there is some error on copying the avatar file:
			phpbb_log( sprintf( T_( 'Impossible to save avatar file of the user #%s(%s) from the remote url %s in DB.' ),
				$phpbb_user->user_id, $phpbb_user->username.' / '.$phpbb_user->user_email, '<code>'.$avatar_file_url.'</code>' ), 'error', ' ', '<br />' );
			// Update the count of missing avatars:
			phpbb_set_var( 'avatars_count_missing', phpbb_get_var( 'avatars_count_missing' ) + 1 );
			return false;
		}
	}
	else
	{	// Copy avatar file to user media folder:
		$imported_file_ID = copy_file( $avatar_file_path, $user_FileRoot->ID, 'profile_pictures', false );

		if( empty( $imported_file_ID ) )
		{	// Display an error if there is some error on copying the avatar file:
			phpbb_log( sprintf( T_( 'Impossible to copy avatar file of the user #%s(%s) from %s to %s, please check file rights of the files and folders.' ),
				$phpbb_user->user_id, $phpbb_user->username.' / '.$phpbb_user->user_email, '<code>'.$avatar_file_path.'</code>', '<code>'.$user_FileRoot->ads_path.'</code>' ), 'error', ' ', '<br />' );
			// Update the count of missing avatars:
			phpbb_set_var( 'avatars_count_missing', phpbb_get_var( 'avatars_count_missing' ) + 1 );
			return false;
		}
	}

	// Update user's avatar:
	$result = $DB->query( 'UPDATE '.$tableprefix.'users
				SET user_avatar_file_ID = '.$DB->quote( $imported_file_ID ).'
			WHERE user_ID = '.$DB->quote( $user_ID ).'
				AND user_avatar_file_ID IS NULL' );

	if( $result )
	{	// Insert a link with new file:
		global $localtimenow;
		$result = $DB->query( 'INSERT INTO '.$tableprefix.'links
						 ( link_datecreated, link_datemodified, link_creator_user_ID, link_lastedit_user_ID, link_usr_ID, link_file_ID, link_position, link_order )
			VALUES ( '.$DB->quote( date( 'Y-m-d H:i:s', $localtimenow ) ).', '.$DB->quote( date( 'Y-m-d H:i:s', $localtimenow ) ).', '.$DB->quote( $user_ID ).', '.$DB->quote( $user_ID ).', '.$DB->quote( $user_ID ).', '.$DB->quote( $imported_file_ID ).', "aftermore", 1 )' );
	}

	if( $result )
	{	// Update the count of imported avatars:
		phpbb_set_var( 'avatars_count_imported', phpbb_get_var( 'avatars_count_imported' ) + 1 );
		return true;
	}
	else
	{	// Some mysql error:
		// Note: do NOT translate the string below, because such error must not occurs on correct DB working, so it is system error and only admin can solve this:
		phpbb_log( sprintf( 'Error on DB storing record of the importing avatar file of the user #%s(%s) from %s (Error: %s).',
			$phpbb_user->user_id, $phpbb_user->username.' / '.$phpbb_user->user_email, '<code>'.( isset( $avatar_file_path ) ? $avatar_file_path : $avatar_file_url ).'</code>', '<code>'.$DB->last_error.'</code>' ), 'error', ' ', '<br />' );
		// Update the count of missing avatars:
		phpbb_set_var( 'avatars_count_missing', phpbb_get_var( 'avatars_count_missing' ) + 1 );
		return false;
	}
}


/**
 * Import attachments of topic, reply or private message
 *
 * @param string Target type: 'itm', 'cmt', 'msg'
 * @param string Attachments path
 * @param integer ID of topic, reply or private message (from phpBB DB)
 * @param integer ID of topic, reply or private message (from b2evolution DB)
 * @return boolean TRUE on successful importing, FALSE on error
 */
function phpbb_import_attachments( $target_type, $path_attachments, $target_ID, $new_object_ID )
{
	if( empty( $path_attachments ) )
	{	// Only count what missing attachments if the path is not correct:
		phpbb_count_missing_attachments( $target_ID );
		// Display an error to inform user about missing attachments:
		phpbb_log( sprintf( T_( 'Impossible to copy attachment of #%s because the source for attachments %s is wrong.' ),
			$target_ID, '<code>'.phpbb_get_var( 'path_attachments' ).'</code>' ), 'error', ' ', '<br />' );
		phpbb_set_var( 'avatars_count_missing', phpbb_get_var( 'avatars_count_missing' ) + 1 );
		return false;
	}
	else
	{	// Do the import if the path is correct:

		// Generate new data of insert query:
		$attachments_insert_data = phpbb_get_attachments_insert_data( $target_type, $path_attachments, $target_ID, $new_object_ID );

		// Insert the links in DB:
		return phpbb_insert_attachments( $target_type, $attachments_insert_data );
	}
}


/**
 * Count the missing attachments of topic, reply or private message
 *
 * @param array IDs of topic, reply or private message (from phpBB DB)
 */
function phpbb_count_missing_attachments( $target_IDs )
{
	global $phpbb_DB;

	if( empty( $target_IDs ) )
	{	// No targets, Don't count:
		return;
	}

	$attachments_count_missing = phpbb_get_var( 'attachments_count_missing' );

	// Get how much attachments have the targets:
	$targets_missing_count = $phpbb_DB->get_var( 'SELECT COUNT( attach_id )
		 FROM BB_attachments
		WHERE post_msg_id IN ( '.$phpbb_DB->quote( $target_IDs ).' )' );

	// Update the count of imported/missing attachments:
	phpbb_set_var( 'attachments_count_missing', $attachments_count_missing + $targets_missing_count );
}


/**
 * Insert attachments of topic, reply or private message
 *
 * @param string Target type: 'itm', 'cmt', 'msg'
 * @param array|NULL Array of insert data or NULL to generate new
 * @return boolean
 */
function phpbb_insert_attachments( $target_type, $attachments_insert_data )
{
	global $DB, $tableprefix;

	if( empty( $attachments_insert_data ) )
	{	// Nothing to insert, Exit here:
		return true;
	}

	$attachments_count_imported = phpbb_get_var( 'attachments_count_imported' );
	$attachments_count_missing = phpbb_get_var( 'attachments_count_missing' );

	// Execute a query to insert the links:
	$r = $DB->query( 'INSERT INTO '.$tableprefix.'links
					 ( link_datecreated, link_datemodified, link_creator_user_ID, link_lastedit_user_ID, link_'.$target_type.'_ID, link_file_ID, link_position, link_order )
		VALUES '.implode( ', ', $attachments_insert_data ) );

	if( $r )
	{	// Increase a count of the imported attachments:
		$attachments_count_imported += $DB->affected_rows;
	}
	else
	{	// Some mysql error:
		$attachments_count_missing += count( $attachments_insert_data );
		// Note: do NOT translate the string below, because such error must not occurs on correct DB working, so it is system error and only admin can solve this:
		phpbb_log( sprintf( 'Error on DB storing record of the importing attachment of "%s" (Error: %s, Data: %s).',
			$target_type, '<code>'.$DB->last_error.'</code>', '<code>'.implode( ', ', $attachments_insert_data ).'</code>' ), 'error', ' ', '<br />' );
	}

	// Update the count of imported/missing attachments:
	phpbb_set_var( 'attachments_count_imported', $attachments_count_imported );
	phpbb_set_var( 'attachments_count_missing', $attachments_count_missing );

	return $r;
}


/**
 * Get data for an insert SQL query statement of attachments of topic, reply or private message
 *
 * @param string Target type: 'itm', 'cmt', 'msg'
 * @param string Attachments path
 * @param integer ID of topic, reply or private message (from phpBB DB)
 * @param integer ID of topic, reply or private message (from b2evolution DB)
 * @return array
 */
function phpbb_get_attachments_insert_data( $target_type, $path_attachments, $target_ID, $new_object_ID )
{
	global $DB, $phpbb_DB, $tableprefix, $phpbb_version, $localtimenow;

	if( $phpbb_version != 3 )
	{	// This function is only for phpBB3:
		return array();
	}

	$attachments = $phpbb_DB->get_results( 'SELECT poster_id, physical_filename, real_filename
		 FROM BB_attachments
		WHERE post_msg_id = '.$phpbb_DB->quote( $target_ID ) );

	if( empty( $attachments ) )
	{	// This target has no attachment:
		return array();
	}

	$users_IDs = phpbb_table_get_links( 'users' );

	$FileRootCache = & get_FileRootCache();
	$FileCache = & get_FileCache();

	$attachments_count_missing = phpbb_get_var( 'attachments_count_missing' );

	$attachments_insert_data = array();

	$link_order = 1;
	foreach( $attachments as $attachment )
	{
		if( ! file_exists( $path_attachments.$attachment->physical_filename ) )
		{	// If physical file(like "2_1888733359c9d121321b5cfbe93ba714") doesn't exist then try to use file with real name(like "image.jpg")
			if( ! file_exists( $path_attachments.$attachment->real_filename ) )
			{	// The file with real name doesn't exist too, Skip this attachment:
				$attachments_count_missing++;
				// Display an error to inform user about the missing attachment:
				phpbb_log( sprintf( T_( 'Attachment of the object #%s(%s) is not found on %s or %s.' ),
					$target_ID, $target_type, '<code>'.$path_attachments.$attachment->physical_filename.'</code>', '<code>'.$path_attachments.$attachment->real_filename.'</code>' ), 'error', ' ', '<br />' );
				continue;
			}
			else
			{	// Use file with real name instead of physical:
				$attachment->physical_filename = $attachment->real_filename;
			}
		}

		if( ! $users_IDs[ (string) $attachment->poster_id ] )
		{	// Wrong file author:
			$attachments_count_missing++;
			// Display an error to inform user about the missing attachment:
			phpbb_log( sprintf( T_( 'Impossible to import attachment of the object #%s(%s) because file author could not be detected by id #%s.' ),
				$target_ID, $target_type, $attachment->poster_id ), 'error', ' ', '<br />' );
			continue;
		}

		$author_ID = $users_IDs[ (string) $attachment->poster_id ];

		$FileRootCache = & get_FileRootCache();
		if( $target_type == 'msg' )
		{	// Get root for private messages:
			$object_FileRoot = & $FileRootCache->get_by_type_and_ID( 'user', $author_ID );
			$root_path = 'private_message/pm'.$new_object_ID;
			$link_position = 'inline';
		}
		else
		{	// Get root for topics and replies:
			$object_FileRoot = & $FileRootCache->get_by_type_and_ID( 'collection', phpbb_get_var( 'blog_ID' ) );
			$root_path = 'quick-uploads/'.( $target_type == 'itm' ? 'p' : 'c' ).$new_object_ID;
			$link_position = 'aftermore';
		}

		// Try to rename file to real name because b2evo function copy_file() has a restriciton for not allowed extenssions:
		if( $attachment->physical_filename != $attachment->real_filename &&
		    ! @rename( $path_attachments.$attachment->physical_filename, $path_attachments.$attachment->real_filename ) )
		{	// Impossible to rename file from phpBB format like "2_1df806e219313f4432b82040685b8ff1" to real file name with extension, Skip it:
			$attachments_count_missing++;
			// Display an error to inform user about the missing attachment:
			phpbb_log( sprintf( T_( 'Impossible to rename attachment of the object #%s(%s) from physical file name %s to real %s.' ),
				$target_ID, $target_type, '<code>'.$path_attachments.$attachment->physical_filename.'</code>', '<code>'.$path_attachments.$attachment->real_filename.'</code>' ), 'error', ' ', '<br />' );
			continue;
		}

		// Copy a file from phpBB attachments folder to b2evolution media folder:
		$imported_file_ID = copy_file( $path_attachments.$attachment->real_filename, $object_FileRoot->ID, $root_path, false );
		if( empty( $imported_file_ID ) )
		{	// Impossible to copy the file:
			$attachments_count_missing++;
			// Display an error to inform user about the missing attachment:
			phpbb_log( sprintf( T_( 'Impossible to copy attachment of the object #%s(%s) from %s to the folder %s, please check system log and file rights of the files and folders.' ),
				$target_ID, $target_type, '<code>'.$path_attachments.$attachment->real_filename.'</code>', '<code>'.$object_FileRoot->ads_path.$root_path.'</code>' ), 'error', ' ', '<br />' );
		}

		if( $attachment->physical_filename != $attachment->real_filename )
		{	// Rename file back from real name to physical name like 2_1888733359c9d121321b5cfbe93ba714,
			// to avoid files missing because some files can have same real file names:
			@rename( $path_attachments.$attachment->real_filename, $path_attachments.$attachment->physical_filename );
		}

		if( empty( $imported_file_ID ) )
		{	// Skip this if file could not be copied,
			// NOTE: Run this after back renaming to avoid file rewriting from another object:
			continue;
		}

		// Initialize a part for VALUES of SQL query to insert a link with new file,
		// For SQL "INSERT INTO links ( link_datecreated, link_datemodified, link_creator_user_ID, link_lastedit_user_ID, link_'.$target_type.'_ID, link_file_ID, link_position, link_order )":
		$attachments_insert_data[] = '( '.$DB->quote( date( 'Y-m-d H:i:s', $localtimenow ) ).', '.$DB->quote( date( 'Y-m-d H:i:s', $localtimenow ) ).', '.$DB->quote( $author_ID ).', '.$DB->quote( $author_ID ).', '.$DB->quote( $new_object_ID ).', '.$DB->quote( $imported_file_ID ).', "'.$link_position.'", '.( $link_order++ ).' )';
	}

	// Update the count of missing attachments:
	phpbb_set_var( 'attachments_count_missing', $attachments_count_missing );

	return $attachments_insert_data;
}


/**
 * Check if we can process current step
 *
 * @param string Step name
 * @return boolean TRUE - step is available to process
 */
function phpbb_check_step( $step_name )
{
	$steps_levels = array(
		'users'    => 1,
		'forums'   => 2,
		'topics'   => 3,
		'replies'  => 4,
		'messages' => 5,
	);

	if( empty( $steps_levels[ $step_name ] ) )
	{	// Invalid step name!
		return false;
	}

	$current_step = phpbb_get_var( 'current_step' );

	if( empty( $current_step ) )
	{	// It is first step
		phpbb_set_var( 'current_step', $step_name, true );
		return true;
	}
	else if( empty( $steps_levels[ $current_step ] ) )
	{	// Invalid current step name!
		return false;
	}/*
	else if( $steps_levels[ $step_name ] <= $steps_levels[ $current_step ] )
	{	// User tries open previous step that already been processed
		phpbb_log( T_('This import step has already been processed.'), 'error', ' ' );
		// Continue button
		// echo '<input type="submit" class="SaveButton" value="'.( $steps_levels[ $step_name ] < max( $steps_levels ) ? T_('Continue').'!' : T_('Go to Forum') ).'" name="submit" />';
		return false;
	}*/

	// Save step name in the Sessions
	phpbb_set_var( 'current_step', $step_name, true );

	return true;

}


/**
 * Display steps panel
 *
 * @param integer Current step
 */
function phpbb_display_steps( $current_step )
{
	$steps = array(
			1 => T_('Database connection'),
			2 => T_('User group mapping'),
			3 => T_('Import users'),
			4 => T_('Import forums'),
			5 => T_('Import topics'),
			6 => T_('Import replies'),
			7 => T_('Import messages'),
		);

	echo get_tool_steps( $steps, $current_step );
}


/**
 * Get last inserted IDs
 *
 * @param string Table name
 * @param string ID field name
 * @param integer Number of last records
 * @return array
 */
function phpbb_get_last_inserted_IDs( $table_name, $ID_field_name, $limit )
{
	global $DB;

	$SQL = new SQL( 'Get IDs of last '.$limit.' inserted records in the table '.$table_name );
	$SQL->SELECT( $ID_field_name );
	$SQL->FROM( $table_name );
	$SQL->ORDER_BY( $ID_field_name.' DESC' );
	$SQL->LIMIT( $limit );
	$last_inserted_IDs = $DB->get_col( $SQL->get(), 0, $SQL->title );

	return array_reverse( $last_inserted_IDs );
}
?>