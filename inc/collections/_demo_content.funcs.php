<?php
/**
 * This file implements functions that creation of demo content for posts, comments, categories, etc.
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
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $baseurl, $admin_url, $new_db_version;
global $random_password, $query;
global $timestamp, $admin_email;
global $admins_Group, $moderators_Group, $editors_Group, $users_Group, $suspect_Group, $blogb_Group;
global $blog_all_ID, $blog_home_ID, $blog_a_ID, $blog_b_ID;
global $DB;
global $default_locale, $default_country;
global $Plugins, $Settings;
global $test_install_all_features;
global $user_org_IDs;
global $user_timestamp;

load_class( 'items/model/_item.class.php', 'Item' );
load_class( 'files/model/_file.class.php', 'File' );
load_class( 'links/model/_linkuser.class.php', 'LinkUser' );
load_class( 'users/model/_group.class.php', 'Group' );
load_funcs( 'collections/model/_category.funcs.php' );
load_class( 'users/model/_organization.class.php', 'Organization' );


/**
 * Adjust timestamp value, adjusts it to the current time if not yet set
 *
 * @param timestamp Base timestamp
 * @param integer Min interval in minutes
 * @param integer Max interval in minutes
 * @param boolean Advance timestamp if TRUE, move back if otherwise
 */
function adjust_timestamp( & $base_timestamp, $min = 360, $max = 1440, $forward_direction = true )
{
	if( isset( $base_timestamp ) )
	{
		$interval = ( rand( $min, $max ) * 60 ) + rand( 0, 3600 );
		if( $forward_direction )
		{
			$base_timestamp += $interval;
		}
		else
		{
			$base_timestamp -= $interval;
		}
	}
	else
	{
		$base_timestamp = time();
	}
}


/**
 * Get array of timestamps with random intervals
 *
 * @param integer Number of iterations
 * @param integer Min interval in minutes
 * @param integer Max interval in minutes
 * @param timestamp Base timestamp
 * @return array Array of timestamps
 */
function get_post_timestamp_data( $num_posts = 1, $min = 30, $max = 720, $base_timestamp = NULL )
{
	if( is_null( $base_timestamp ) )
	{
		$base_timestamp = time();
	}

	// Add max comment time allowance, i.e., 2 comments at max. 12 hour interval
	$base_timestamp -= 1440 * 60;

	$loop_timestamp = $base_timestamp;
	$post_timestamp_array = array();
	for( $i = 0; $i < $num_posts; $i++ )
	{
		$interval = ( rand( $min, $max ) * 60 ) + rand( 0, 3600 );
		$loop_timestamp -= $interval;
		$post_timestamp_array[] = $loop_timestamp;
	}

	return $post_timestamp_array;
}


/**
 * Generate filler text for demo content
 *
 * @param string Type of filler text
 * @return string Filler text
 */
function get_filler_text( $type = NULL )
{
	$filler_text = '';

	switch( $type )
	{
		case 'lorem_1paragraph':
			$filler_text = "\n\n<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>";
			break;

		case 'lorem_2more':
			$filler_text = "\n\n<p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?</p>\n\n"
		."<p>At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus, omnis voluptas assumenda est, omnis dolor repellendus. Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet ut et voluptates repudiandae sint et molestiae non recusandae. Itaque earum rerum hic tenetur a sapiente delectus, ut aut reiciendis voluptatibus maiores alias consequatur aut perferendis doloribus asperiores repellat.</p>";
			break;

		case 'info_page':
			$filler_text = T_('<p>This website is powered by b2evolution.</p>

<p>You are currently looking at an info page about "%s".</p>

<p>Info pages are very much like regular posts, except that they do not appear in the regular flow of posts. They appear as info pages in the menu instead.</p>

<p>If needed, skins can format info pages differently from regular posts.</p>');
			break;

		case 'markdown_examples_content':
			$filler_text = T_('Heading
=======

Sub-heading
-----------

### H3 header

#### H4 header ####

> Email-style angle brackets
> are used for blockquotes.

> > And, they can be nested.

> ##### Headers in blockquotes
>
> * You can quote a list.
> * Etc.

[This is a link](http://b2evolution.net/) if Links are turned on in the markdown plugin settings

Paragraphs are separated by a blank line.

    This is a preformatted
    code block.

Text attributes *Italic*, **bold**, `monospace`.

Shopping list:

* apples
* oranges
* pears

The rain---not the reign---in Spain.');
			break;
	}

	return $filler_text;
}

/**
 * Create a new blog
 * This funtion has to handle all needed DB dependencies!
 *
 * @todo move this to Blog object (only half done here)
 */
function create_blog(
		$blog_name,
		$blog_shortname,
		$blog_urlname,
		$blog_tagline = '',
		$blog_longdesc = '',
		$blog_skin_ID = 1,
		$kind = 'std', // standard blog; notorious variations: "photo", "group", "forum"
		$allow_rating_items = '',
		$use_inskin_login = 0,
		$blog_access_type = 'relative', // Deprecated param for this func, because it is defined in $Blog->dbinsert()
		$allow_html = true,
		$in_bloglist = 'public',
		$owner_user_ID = 1,
		$blog_allow_access = 'public' )
{
	global $default_locale, $install_test_features, $local_installation, $Plugins, $Blog;

	$Collection = $Blog = new Blog( NULL );

	$Blog->init_by_kind( $kind, $blog_name, $blog_shortname, $blog_urlname );

	if( ( $kind == 'forum' || $kind == 'manual' ) && ( $Plugin = & $Plugins->get_by_code( 'b2evMark' ) ) !== false )
	{ // Initialize special Markdown plugin settings for Forums and Manual blogs
		$Blog->set_setting( 'plugin'.$Plugin->ID.'_coll_apply_comment_rendering', 'opt-out' );
		$Blog->set_setting( 'plugin'.$Plugin->ID.'_links', '1' );
		$Blog->set_setting( 'plugin'.$Plugin->ID.'_images', '1' );
	}
	if( $kind == 'photo' )
	{ // Display category directory on front page for photo blogs
		$Blog->set_setting( 'front_disp', 'catdir' );
	}

	$Blog->set( 'tagline', $blog_tagline );
	$Blog->set( 'longdesc', $blog_longdesc );
	$Blog->set( 'locale', $default_locale );
	$Blog->set( 'in_bloglist', $in_bloglist );
	$Blog->set( 'owner_user_ID', $owner_user_ID );
	$Blog->set_setting( 'normal_skin_ID', $blog_skin_ID );

	if( $local_installation )
	{ // Turn off all ping plugins if the installation is local/test/intranet
		$Blog->set_setting( 'ping_plugins', '' );
	}

	$Blog->dbinsert();

	if( $install_test_features )
	{
		$allow_rating_items = 'any';
		$Blog->set_setting( 'skin'.$blog_skin_ID.'_bubbletip', '1' );
		echo_install_log( 'TEST FEATURE: Activating username bubble tips on skin of collection #'.$Blog->ID );
		$Blog->set_setting( 'skin'.$blog_skin_ID.'_gender_colored', '1' );
		echo_install_log( 'TEST FEATURE: Activating gender colored usernames on skin of collection #'.$Blog->ID );
		$Blog->set_setting( 'in_skin_editing', '1' );
		echo_install_log( 'TEST FEATURE: Activating in-skin editing on collection #'.$Blog->ID );

		if( $kind == 'manual' )
		{	// Set a posts ordering by 'post_order ASC'
			$Blog->set_setting( 'orderby', 'order' );
			$Blog->set_setting( 'orderdir', 'ASC' );
			echo_install_log( 'TEST FEATURE: Setting a posts ordering by asceding post order field on collection #'.$Blog->ID );
		}

		$Blog->set_setting( 'use_workflow', 1 );
		echo_install_log( 'TEST FEATURE: Activating workflow on collection #'.$Blog->ID );
	}
	if( $allow_rating_items != '' )
	{
		$Blog->set_setting( 'allow_rating_items', $allow_rating_items );
	}
	if( $use_inskin_login || $install_test_features )
	{
		$Blog->set_setting( 'in_skin_login', 1 );
	}

	if( !$allow_html )
	{
		$Blog->set_setting( 'allow_html_comment', 0 );
	}

	$Blog->set( 'order', $Blog->ID );

	if( ! empty( $blog_allow_access ) )
	{
		$Blog->set_setting( 'allow_access', $blog_allow_access );
		switch( $blog_allow_access )
		{	// Automatically enable/disable moderation statuses:
			case 'public':
				// Enable "Community" and "Members":
				$enable_moderation_statuses = array( 'community', 'protected' );
				$enable_comment_moderation_statuses = array( 'community', 'protected', 'review', 'draft' );
				$disable_comment_moderation_statuses = array( 'private' );
				break;
			case 'users':
				// Disable "Community" and Enable "Members":
				$disable_moderation_statuses = array( 'community' );
				$enable_moderation_statuses = array( 'protected' );
				$enable_comment_moderation_statuses = array( 'protected', 'review', 'draft' );
				$disable_comment_moderation_statuses = array( 'community', 'private' );
				break;
			case 'members':
				// Disable "Community" and "Members":
				$disable_moderation_statuses = array( 'community', 'protected' );
				$enable_comment_moderation_statuses = array( 'review', 'draft' );
				$disable_comment_moderation_statuses = array( 'community', 'protected', 'private' );
				break;
		}
		$post_moderation_statuses = $Blog->get_setting( 'post_moderation_statuses' );
		$post_moderation_statuses = empty( $post_moderation_statuses ) ? array() : explode( ',', $post_moderation_statuses );
		$comment_moderation_statuses = $Blog->get_setting( 'moderation_statuses' );
		$comment_moderation_statuses = empty( $comment_moderation_statuses ) ? array() : explode( ',', $comment_moderation_statuses );

		if( ! empty( $disable_moderation_statuses ) )
		{	// Disable moderation statuses:
			$post_moderation_statuses = array_diff( $post_moderation_statuses, $disable_moderation_statuses );
			//$comment_moderation_statuses = array_diff( $comment_moderation_statuses, $disable_moderation_statuses );
		}
		if( ! empty( $enable_moderation_statuses ) )
		{	// Enable moderation statuses:
			$post_moderation_statuses = array_unique( array_merge( $enable_moderation_statuses, $post_moderation_statuses ) );
			//$comment_moderation_statuses = array_unique( array_merge( $enable_moderation_statuses, $comment_moderation_statuses ) );
		}

		if( ! empty( $disable_comment_moderation_statuses ) )
		{
			$comment_moderation_statuses = array_diff( $comment_moderation_statuses, $disable_comment_moderation_statuses );
		}
		if( ! empty( $enable_comment_moderation_statuses ) )
		{
			$comment_moderation_statuses = array_unique( array_merge( $enable_comment_moderation_statuses, $comment_moderation_statuses ) );
		}

		$Blog->set_setting( 'post_moderation_statuses', implode( ',', $post_moderation_statuses ) );
		// Force enabled statuses regardless of previous settings
		$Blog->set_setting( 'moderation_statuses', implode( ',', $enable_comment_moderation_statuses ) );
	}

	$Blog->dbupdate();

	// Insert default group permissions:
	$Blog->insert_default_group_permissions();

	return $Blog->ID;
}


/**
 * Create a new User
 *
 * @param array Params
 * @return mixed object User if user was succesfully created otherwise false
 */
function create_user( $params = array() )
{
	global $timestamp;
	global $random_password, $admin_email;
	global $default_locale, $default_country;
	global $Messages;

	$params = array_merge( array(
			'login'     => '',
			'firstname' => NULL,
			'lastname'  => NULL,
			'pass'      => $random_password, // random
			'email'     => $admin_email,
			'status'    => 'autoactivated', // assume it's active
			'level'     => 0,
			'locale'    => $default_locale,
			'ctry_ID'   => $default_country,
			'gender'    => 'M',
			'group_ID'  => NULL,
			'org_IDs'   => NULL, // array of organization IDs
			'org_roles' => NULL, // array of organization roles
			'fields'    => NULL, // array of additional user fields
			'datecreated' => $timestamp++
		), $params );

	$GroupCache = & get_GroupCache();
	$Group = $GroupCache->get_by_ID( $params['group_ID'], false, false );
	if( ! $Group )
	{
		$Messages->add( sprintf( T_('Cannot create demo user "%s" because User Group #%d was not found.'), $params['login'], $params['group_ID'] ), 'error' );
		return false;
	}

	$User = new User();
	$User->set( 'login', $params['login'] );
	$User->set( 'firstname', $params['firstname'] );
	$User->set( 'lastname', $params['lastname'] );
	$User->set_password( $params['pass'] );
	$User->set_email( $params['email'] );
	$User->set( 'status', $params['status'] );
	$User->set( 'level', $params['level'] );
	$User->set( 'locale', $params['locale'] );
	if( !empty( $params['ctry_ID'] ) )
	{ // Set country
		$User->set( 'ctry_ID', $params['ctry_ID'] );
	}
	$User->set( 'gender', $params['gender'] );
	$User->set_Group( $Group );
	$User->set_datecreated( $params['datecreated'] );

	if( ! $User->dbinsert( false ) )
	{ // Don't continue if user creating has been failed
		return false;
	}

	if( ! empty( $params['org_IDs'] ) )
	{ // Add user to organizations
		$User->update_organizations( $params['org_IDs'], $params['org_roles'], true );
	}

	if( ! empty( $params['fields'] ) )
	{ // Additional user fields
		global $DB;
		$fields_SQL = new SQL();
		$fields_SQL->SELECT( 'ufdf_ID, ufdf_name' );
		$fields_SQL->FROM( 'T_users__fielddefs' );
		$fields_SQL->WHERE( 'ufdf_name IN ( '.$DB->quote( array_keys( $params['fields'] ) ).' )' );
		$fields = $DB->get_assoc( $fields_SQL->get() );
		$user_field_records = array();
		foreach( $fields as $field_ID => $field_name )
		{
			if( ! isset( $params['fields'][ $field_name ] ) )
			{ // Skip wrong field
				continue;
			}

			if( is_string( $params['fields'][ $field_name ] ) )
			{
				$params['fields'][ $field_name ] = array( $params['fields'][ $field_name ] );
			}

			foreach( $params['fields'][ $field_name ] as $field_value )
			{ // SQL record for each field value
				$user_field_records[] = '( '.$User->ID.', '.$field_ID.', '.$DB->quote( $field_value ).' )';
			}
		}
		if( count( $user_field_records ) )
		{ // Insert all user fields by single SQL query
			$DB->query( 'INSERT INTO T_users__fields ( uf_user_ID, uf_ufdf_ID, uf_varchar ) VALUES '
				.implode( ', ', $user_field_records ) );
		}
	}

	return $User;
}


/**
 * Associate a profile picture with a user.
 *
 * @param object User
 * @param string File name, NULL to use user login as file name
 */
function assign_profile_picture( & $User, $login = NULL )
{
	$File = new File( 'user', $User->ID, ( is_null( $login ) ? $User->login : $login ).'.jpg' );

	if( ! $File->exists() )
	{	// Don't assign if default user avatar doesn't exist on disk:
		return;
	}

	// Load meta data AND MAKE SURE IT IS CREATED IN DB:
	$File->load_meta( true );
	$User->set( 'avatar_file_ID', $File->ID );
	$User->dbupdate();

	// Set link between user and avatar file
	$LinkOwner = new LinkUser( $User );
	$File->link_to_Object( $LinkOwner );
}


/**
 * Assign secondary groups to user
 *
 * @param integer User ID
 * @param array IDs of groups
 */
function assign_secondary_groups( $user_ID, $secondary_group_IDs )
{
	if( empty( $secondary_group_IDs ) )
	{	// Nothing to assign, Exit here:
		return;
	}

	global $DB;

	$DB->query( 'INSERT INTO T_users__secondary_user_groups ( sug_user_ID, sug_grp_ID )
			VALUES ( '.$user_ID.', '.implode( ' ), ( '.$user_ID.', ', $secondary_group_IDs ).' )',
			'Assign secondary groups ('.implode( ', ', $secondary_group_IDs ).') to User #'.$user_ID );
}


/**
 * Create a demo organization
 *
 * @param integer Owner ID
 * @param string Demo organization name
 * @param boolean Add current user to the demo organization
 * @return object Created organization
 */
function create_demo_organization( $owner_ID, $org_name = 'Company XYZ', $add_current_user = true )
{
	global $DB, $Messages, $current_User;

	// Check if our sample organization already exists
	$demo_org_ID = NULL;
	$OrganizationCache = & get_OrganizationCache();
	$SQL = $OrganizationCache->get_SQL_object();
	$SQL->WHERE_and( 'org_name = '.$DB->quote( $org_name ) );

	$db_row = $DB->get_row( $SQL->get() );
	if( $db_row )
	{
		$demo_org_ID = $db_row->org_ID;
		$Organization = & $OrganizationCache->get_by_ID( $demo_org_ID );
	}
	else
	{ // Sample organization does not exist, let's create one
		$Organization = new Organization();
		$Organization->set( 'owner_user_ID', $owner_ID );
		$Organization->set( 'name', $org_name );
		$Organization->set( 'url', 'http://b2evolution.net/' );
		if( $Organization->dbinsert() )
		{
			$demo_org_ID = $Organization->ID;
			$Messages->add( sprintf( T_('The sample organization %s has been created.'), $org_name ), 'success' );
		}
	}

	// Add current user to the demo organization
	if( $add_current_user && $demo_org_ID && isset( $current_User ) )
	{
		// Get current user's organization data
		$org_roles = array();
		$org_data = $current_User->get_organizations_data();
		if( isset( $org_data[$demo_org_ID] ) )
		{
			$org_roles = array( $org_data[$demo_org_ID]['role'] );
		}
		$current_User->update_organizations( array( $demo_org_ID ), $org_roles, true);
	}

	return $Organization;
}



/**
 * Get all available demo users
 *
 * @param boolean Create the demo users if they do not exist
 * @param object Group where the created demo users be assigned
 * @param array List of organization where  the created demo users will be added
 * @return array List of available demo users
 */
function get_demo_users( $create = false, $group = NULL, $user_org_IDs = NULL )
{
	global $user_org_IDs;

	$demo_user_logins = array( 'admin', 'jay', 'mary', 'paul', 'dave', 'larry', 'kate' );
	$available_demo_users = array();
	foreach( $demo_user_logins as $demo_user_login )
	{
		$demo_User = get_demo_user( $demo_user_login, $create, $group, $user_org_IDs );
		if( $demo_User )
		{
			$available_demo_users[] = $demo_User;
		}
	}

	return $available_demo_users;
}


/**
 * Create a demo user
 *
 * @param string User $login
 * @param boolean Create demo user if it does not exist
 * @param integer Group ID of user when created
 * @param array IDs of organization
 * @return mixed object Demo user if successful, false otherwise
 */
function get_demo_user( $login, $create = false, $group_ID = NULL, $user_org_IDs = NULL )
{
	global $DB, $user_org_IDs;
	global $current_User, $mary_moderator_ID, $jay_moderator_ID, $dave_blogger_ID, $paul_blogger_ID, $larry_user_ID, $kate_user_ID;
	global $user_timestamp;

	$UserCache  = & get_UserCache();
	$demo_user = & $UserCache->get_by_login( $login );

	if( ! $demo_user && $create )
	{
		adjust_timestamp( $user_timestamp, 360, 1440, false );
		switch( $login )
		{
			case 'mary':
				$default_group_id = 2;
				$mary_moderator = create_user( array(
						'login'     => 'mary',
						'firstname' => 'Mary',
						'lastname'  => 'Wilson',
						'level'     => 4,		// NOTE: these levels define the order of display in the Organization memebers widget
						'gender'    => 'F',
						'group_ID'  => $group_ID ? $group_ID : 2,
						'org_IDs'   => $user_org_IDs,
						'org_roles' => array( 'Queen of Hearts' ),
						'fields'    => array(
								'Micro bio'   => 'I am a demo moderator for this site.'."\n".'I love it when things are neat!',
								'Website'     => 'http://b2evolution.net/',
								'Twitter'     => 'https://twitter.com/b2evolution/',
								'Facebook'    => 'https://www.facebook.com/b2evolution',
								'Linkedin'    => 'https://www.linkedin.com/company/b2evolution-net',
								'GitHub'      => 'https://github.com/b2evolution/b2evolution',
								'Google Plus' => 'https://plus.google.com/+b2evolution/posts',
							),
						'datecreated' => $user_timestamp
					) );

				if( $mary_moderator === false )
				{
					return false;
				}

				$mary_moderator_ID = $mary_moderator->ID;
				assign_profile_picture( $mary_moderator );
				$demo_user = & $mary_moderator;
				break;

			case 'jay':
				$jay_moderator = create_user( array(
						'login'     => 'jay',
						'firstname' => 'Jay',
						'lastname'  => 'Parker',
						'level'     => 3,
						'gender'    => 'M',
						'group_ID'  => $group_ID ? $group_ID : 2,
						'org_IDs'   => $user_org_IDs,
						'org_roles' => array( 'The Artist' ),
						'fields'    => array(
								'Micro bio'   => 'I am a demo moderator for this site.'."\n".'I like to keep things clean!',
								'Website'     => 'http://b2evolution.net/',
								'Twitter'     => 'https://twitter.com/b2evolution/',
								'Facebook'    => 'https://www.facebook.com/b2evolution',
								'Linkedin'    => 'https://www.linkedin.com/company/b2evolution-net',
								'GitHub'      => 'https://github.com/b2evolution/b2evolution',
								'Google Plus' => 'https://plus.google.com/+b2evolution/posts',
							),
						'datecreated' => $user_timestamp
					) );

				if( $jay_moderator === false )
				{
					return false;
				}

				$jay_moderator_ID = $jay_moderator->ID;
				assign_profile_picture( $jay_moderator );
				$demo_user = & $jay_moderator;
				break;

			case 'dave':
				$dave_blogger = create_user( array(
						'login'     => 'dave',
						'firstname' => 'David',
						'lastname'  => 'Miller',
						'level'     => 2,
						'gender'    => 'M',
						'group_ID'  => $group_ID ? $group_ID : 3,
						'org_IDs'   => $user_org_IDs,
						'org_roles' => array( 'The Writer' ),
						'fields'    => array(
								'Micro bio'   => 'I\'m a demo author.'."\n".'I like to write!',
								'Website'     => 'http://b2evolution.net/',
								'Twitter'     => 'https://twitter.com/b2evolution/',
								'Facebook'    => 'https://www.facebook.com/b2evolution',
								'Linkedin'    => 'https://www.linkedin.com/company/b2evolution-net',
								'GitHub'      => 'https://github.com/b2evolution/b2evolution',
								'Google Plus' => 'https://plus.google.com/+b2evolution/posts',
							),
						'datecreated' => $user_timestamp
					) );

				if( $dave_blogger === false )
				{
					return false;
				}

				$dave_blogger_ID = $dave_blogger->ID;
				assign_profile_picture( $dave_blogger );
				$demo_user = & $dave_blogger;
				break;

			case 'paul':
				$paul_blogger = create_user( array(
						'login'     => 'paul',
						'firstname' => 'Paul',
						'lastname'  => 'Jones',
						'level'     => 1,
						'gender'    => 'M',
						'group_ID'  => $group_ID ? $group_ID : 3,
						'org_IDs'   => $user_org_IDs,
						'org_roles' => array( 'The Thinker' ),
						'fields'    => array(
								'Micro bio'   => 'I\'m a demo author.'."\n".'I like to think before I write ;)',
								'Website'     => 'http://b2evolution.net/',
								'Twitter'     => 'https://twitter.com/b2evolution/',
								'Facebook'    => 'https://www.facebook.com/b2evolution',
								'Linkedin'    => 'https://www.linkedin.com/company/b2evolution-net',
								'GitHub'      => 'https://github.com/b2evolution/b2evolution',
								'Google Plus' => 'https://plus.google.com/+b2evolution/posts',
							),
						'datecreated' => $user_timestamp
					) );

				if( $paul_blogger === false )
				{
					return false;
				}

				$paul_blogger_ID = $paul_blogger->ID;
				assign_profile_picture( $paul_blogger );
				$demo_user = & $paul_blogger;
				break;

			case 'larry':
				$larry_user = create_user( array(
						'login'     => 'larry',
						'firstname' => 'Larry',
						'lastname'  => 'Smith',
						'level'     => 0,
						'gender'    => 'M',
						'group_ID'  => $group_ID ? $group_ID : 4,
						'fields'    => array(
								'Micro bio' => 'Hi there!',
							),
						'datecreated' => $user_timestamp
					) );

				if( $larry_user === false )
				{
					return false;
				}

				$larry_user_ID = $larry_user->ID;
				assign_profile_picture( $larry_user );
				$demo_user = & $larry_user;
				break;

			case 'kate':
				$kate_user = create_user( array(
						'login'     => 'kate',
						'firstname' => 'Kate',
						'lastname'  => 'Adams',
						'level'     => 0,
						'gender'    => 'F',
						'group_ID'  => $group_ID ? $group_ID : 4,
						'fields'    => array(
								'Micro bio' => 'Just me!',
							),
						'datecreated' => $user_timestamp
					) );

				if( $kate_user === false )
				{
					return false;
				}

				$kate_user_ID = $kate_user->ID;
				assign_profile_picture( $kate_user );
				$demo_user = & $kate_user;
				break;

			case 'admin':
				// erhsatingin> Should we recreate 'admin' user here if the initial admin user has a different login?
			default:
				return false;
		}

		if( $demo_user )
		{	// Insert default user settings:
			$DB->query( 'INSERT INTO T_users__usersettings ( uset_user_ID, uset_name, uset_value )
				VALUES ( '.$demo_user->ID.', "created_fromIPv4", '.$DB->quote( ip2int( '127.0.0.1' ) ).' ),
				       ( '.$demo_user->ID.', "user_domain", "localhost" )' );
		}
	}

	return $demo_user;
}


/**
 * Create a demo comment
 *
 * @param integer Item ID
 * @param array List of users as comment authors
 * @param string Comment status
 */
function create_demo_comment( $item_ID, $comment_users , $status = NULL, $comment_timestamp = NULL )
{
	global $DB, $now;

	if( empty( $status ) )
	{
		$ItemCache = & get_ItemCache();
		$commented_Item = $ItemCache->get_by_ID( $item_ID );
		$commented_Item->load_Blog();
		$status = $commented_Item->Blog->get_setting( 'new_feedback_status' );
	}

	// Get comment users
	if( $comment_users )
	{
		$comment_user = $comment_users[ rand( 0, count( $comment_users ) - 1 ) ];

		$user_ID = $comment_user->ID;
		$author = $comment_user->get( 'fullname' );
		$author_email = $comment_user->email;
		$author_email_url = $comment_user->url;
	}
	else
	{
		$user_ID = NULL;
		$author = T_('Anonymous Demo User') ;
		$author_email = 'anonymous@example.com';
		$author_email_url = 'http://www.example.com';
	}

	// Restrict comment status by parent item:
	$Comment = new Comment();
	$Comment->set( 'item_ID', $item_ID );
	$Comment->set( 'status', $status );
	$Comment->restrict_status( true );
	$status = $Comment->get( 'status' );

	// Set demo content depending on status
	if( $status == 'published' )
	{
		$content = T_('Hi!

This is a sample comment that has been approved by default!
Admins and moderators can very quickly approve or reject comments from the collection dashboard.');
	}
	else
	{ // draft
		$content = T_('Hi!

This is a sample comment that has **not** been approved by default!
Admins and moderators can very quickly approve or reject comments from the collection dashboard.');
	}

	if( is_null( $comment_timestamp ) )
	{
		$comment_timestamp = time();
	}

	$now = date( 'Y-m-d H:i:s', $comment_timestamp );

	$DB->query( 'INSERT INTO T_comments( comment_item_ID, comment_status,
			comment_author_user_ID, comment_author, comment_author_email, comment_author_url, comment_author_IP,
			comment_date, comment_last_touched_ts, comment_content, comment_renderers, comment_notif_status, comment_notif_flags )
			VALUES( '.$DB->quote( $item_ID ).', '.$DB->quote( $status ).', '
			.$DB->quote( $user_ID ).', '.$DB->quote( $author ).', '.$DB->quote( $author_email ).', '.$DB->quote( $author_email_url ).', "127.0.0.1", '
			.$DB->quote( $now ).', '.$DB->quote( $now ).', '.$DB->quote( $content ).', "default", "finished", "moderators_notified,members_notified,community_notified" )' );
}


/**
 * Creates a demo collection
 *
 * @param string Collection type
 * @param integer Owner ID
 * @param boolean Use demo users as comment authors
 * @param integer Shift post time in ms
 * @return integer ID of created blog
 */
function create_demo_collection( $collection_type, $owner_ID, $use_demo_user = true, $timeshift = 86400 )
{
	global $install_test_features, $DB, $admin_url, $timestamp;

	$default_blog_longdesc = T_('This is the long description for the blog named \'%s\'. %s');
	$default_blog_access_type = 'relative';

	$timestamp = time();
	$blog_ID = NULL;

	switch( $collection_type )
	{
		// =======================================================================================================
		case 'main':
			$blog_shortname = T_('Home');
			$blog_home_access_type = ( $install_test_features ) ? 'default' : $default_blog_access_type;
			$blog_more_longdesc = '<br />
<br />
<strong>'.T_('The main purpose for this blog is to be included as a side item to other blogs where it will display your favorite/related links.').'</strong>';

			$blog_home_ID = create_blog(
					T_('Homepage Title'),
					$blog_shortname,
					'home',
					T_('Change this as you like'),
					sprintf( $default_blog_longdesc, $blog_shortname, $blog_more_longdesc ),
					2, // Skin ID
					'main',
					'any',
					1,
					$blog_home_access_type,
					true,
					'never',
					$owner_ID );

			if( ! empty( $blog_home_ID ) )
			{ // Save ID of this blog in settings table, It is used on top menu, file "/skins_site/_site_body_header.inc.php"
				$DB->query( 'INSERT INTO T_settings ( set_name, set_value )
						VALUES ( '.$DB->quote( 'info_blog_ID' ).', '.$DB->quote( $blog_home_ID ).' )' );
			}
			$blog_ID = $blog_home_ID;
			break;

		// =======================================================================================================
		case 'std':
		case 'blog_a':
			if( $collection_type == 'blog_a' )
			{
				$blog_shortname = 'Blog A';
			}
			else
			{
				$blog_shortname = 'Blog';
			}
			$blog_a_access_type = ( $install_test_features ) ? 'default' : $default_blog_access_type;
			$blog_stub = 'a';
			$blog_a_ID = create_blog(
					T_('Public Blog'),
					$blog_shortname,
					$blog_stub,
					T_('This blog is completely public...'),
					sprintf( $default_blog_longdesc, $blog_shortname, '' ),
					1, // Skin ID
					'std',
					'any',
					1,
					$blog_a_access_type,
					true,
					'public',
					$owner_ID );
			$blog_ID = $blog_a_ID;
			break;

		// =======================================================================================================
		case 'blog_b':
			// Create group for Blog b
			$blogb_Group = new Group(); // COPY !
			$blogb_Group->set( 'name', 'Blog B Members' );
			$blogb_Group->set( 'usage', 'secondary' );
			$blogb_Group->set( 'level', 1 );
			$blogb_Group->set( 'perm_blogs', 'user' );
			$blogb_Group->set( 'perm_stats', 'none' );
			$blogb_Group->dbinsert();

			// Assign owner to blog b
			assign_secondary_groups( $owner_ID, array( $blogb_Group->ID ) );

			$blog_shortname = 'Blog B';
			$blog_b_access_type = ( $install_test_features ) ? 'index.php' : $default_blog_access_type;
			$blog_stub = 'b';

			$blog_b_ID = create_blog(
					T_('Members-Only Blog'),
					$blog_shortname,
					$blog_stub,
					T_('This blog has restricted access...'),
					sprintf( $default_blog_longdesc, $blog_shortname, '' ),
					1, // Skin ID
					'std',
					'',
					0,
					$blog_b_access_type,
					true,
					'public',
					$owner_ID,
					'members' );

			$BlogCache = & get_BlogCache();
			if( $b_Blog = $BlogCache->get_by_ID( $blog_b_ID, false, false ) )
			{
				$b_Blog->set_setting( 'front_disp', 'front' );
				$b_Blog->set_setting( 'skin2_layout', 'single_column' );
				$b_Blog->set( 'advanced_perms', 1 );
				$b_Blog->dbupdate();
			}
			$blog_ID = $blog_b_ID;
			break;

		// =======================================================================================================
		case 'photo':
			$blog_shortname = 'Photos';
			$blog_stub = 'photos';
			$blog_more_longdesc = '<br /><br />
					<strong>'.T_('This is a photoblog, optimized for displaying photos.').'</strong>';

			$blog_photoblog_ID = create_blog(
					'Photos',
					$blog_shortname,
					$blog_stub,
					T_('This blog shows photos...'),
					sprintf( $default_blog_longdesc, $blog_shortname, $blog_more_longdesc ),
					3, // Skin ID
					'photo', '', 0, 'relative', true, 'public',
					$owner_ID );
			$blog_ID = $blog_photoblog_ID;
			break;

		// =======================================================================================================
		case 'forum':
			$blog_shortname = 'Forums';
			$blog_stub = 'forums';
			$blog_forums_ID = create_blog(
					T_('Forums Title'),
					$blog_shortname,
					$blog_stub,
					T_('Tagline for Forums'),
					sprintf( $default_blog_longdesc, $blog_shortname, '' ),
					4, // Skin ID
					'forum', 'any', 1, 'relative', false, 'public',
					$owner_ID );
			$blog_ID = $blog_forums_ID;
			break;

		// =======================================================================================================
		case 'manual':
			$blog_shortname = 'Manual';
			$blog_stub = 'manual';
			$blog_manual_ID = create_blog(
					T_('Manual Title'),
					$blog_shortname,
					$blog_stub,
					T_('Tagline for this online manual'),
					sprintf( $default_blog_longdesc, $blog_shortname, '' ),
					5, // Skin ID
					'manual', 'any', 1, $default_blog_access_type, false, 'public',
					$owner_ID );
			$blog_ID = $blog_manual_ID;
			break;

		// =======================================================================================================
		case 'group':
			$blog_shortname = 'Tracker';
			$blog_stub = 'tracker';
			$blog_group_ID = create_blog(
					T_('Tracker Title'),
					$blog_shortname,
					$blog_stub,
					T_('Tagline for Tracker'),
					sprintf( $default_blog_longdesc, $blog_shortname, '' ),
					4, // Skin ID
					'group', 'any', 1, $default_blog_access_type, false, 'public',
					$owner_ID );
			$blog_ID = $blog_group_ID;
			break;

		default:
			// do nothing
	}

	// Create sample contents for the collection
	create_sample_content( $collection_type, $blog_ID, $owner_ID, $use_demo_user, $timeshift );

	return $blog_ID;
}


/**
 * Creates sample contents for the collection
 *
 * @param string Collection type
 * @param integer Blog ID
 * @param integer Owner ID
 * @param boolean Use demo users as comment authors
 * @param integer Shift post time in ms
 */
function create_sample_content( $collection_type, $blog_ID, $owner_ID, $use_demo_user = true, $timeshift = 86400 )
{
	global $DB, $install_test_features, $timestamp, $Settings, $admin_url;

	$timestamp = time();
	$item_IDs = array();
	$additional_comments_item_IDs = array();
	$demo_users = get_demo_users( $use_demo_user );

	$BlogCache = & get_BlogCache();

	switch( $collection_type )
	{
		// =======================================================================================================
		case 'main':
			$post_count = 13;
			$post_timestamp_array = get_post_timestamp_data( $post_count ) ;

			// Sample categories
			$cat_home_b2evo = cat_create( 'b2evolution', 'NULL', $blog_ID, NULL, true );
			$cat_home_contrib = cat_create( T_('Contributors'), 'NULL', $blog_ID, NULL, true );

			if( $edited_Blog = $BlogCache->get_by_ID( $blog_ID, false, false ) )
			{
				$edited_Blog->set_setting( 'default_cat_ID', $cat_home_b2evo );
				$edited_Blog->dbupdate();
			}

			// Sample post
			// Insert three ADVERTISEMENTS for home blog:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'photo' );
			$edited_Item->insert( $owner_ID, /* TRANS: sample ad content */ T_('b2evo: The software for blog pros!'), /* TRANS: sample ad content */ T_('The software for blog pros!'), $now, $cat_home_b2evo,
					array(), 'published', '#', '', 'http://b2evolution.net', 'open', array('default'), 'Advertisement' );
			$edit_File = new File( 'shared', 0, 'banners/b2evo-125-pros.png' );
			$LinkOwner = new LinkItem( $edited_Item );
			$edit_File->link_to_Object( $LinkOwner );

			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'photo' );
			$edited_Item->insert( $owner_ID, /* TRANS: sample ad content */ T_('b2evo: Better Blog Software!'), /* TRANS: sample ad content */ T_('Better Blog Software!'), $now, $cat_home_b2evo,
					array(), 'published', '#', '', 'http://b2evolution.net', 'open', array('default'), 'Advertisement' );
			$edit_File = new File( 'shared', 0, 'banners/b2evo-125-better.png' );
			$LinkOwner = new LinkItem( $edited_Item );
			$edit_File->link_to_Object( $LinkOwner );

			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'photo' );
			$edited_Item->insert( $owner_ID, /* TRANS: sample ad content */ T_('b2evo: The other blog tool!'), /* TRANS: sample ad content */ T_('The other blog tool!'), $now, $cat_home_b2evo,
					array(), 'published', '#', '', 'http://b2evolution.net', 'open', array('default'), 'Advertisement' );
			$edit_File = new File( 'shared', 0, 'banners/b2evo-125-other.png' );
			$LinkOwner = new LinkItem( $edited_Item );
			$edit_File->link_to_Object( $LinkOwner );

			// Insert a post into info blog:
			// walter : a weird line of code to create a post in the home a minute after the others.
			// It will show a bug on home agregation by category
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->insert( $owner_ID, 'Evo Factory', '', $now, $cat_home_contrib, array(), 'published', 'en-US', '', 'http://evofactory.com/', 'disabled', array(), 'Sidebar link' );

			// Insert a post into home:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->insert( $owner_ID, 'Francois', '', $now, $cat_home_contrib, array(), 'published', 'fr-FR', '', 'http://fplanque.com/', 'disabled', array(), 'Sidebar link' );

			// Insert a post into home:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->insert( $owner_ID, 'Blog news', '', $now, $cat_home_b2evo, array(), 'published', 'en-US', '', 'http://b2evolution.net/news.php', 'disabled', array(), 'Sidebar link' );

			// Insert a post into home:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->insert( $owner_ID, 'Web hosting', '', $now, $cat_home_b2evo, array(), 'published', 'en-US', '', 'http://b2evolution.net/web-hosting/blog/', 'disabled', array(), 'Sidebar link' );

			// Insert a post into home:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->insert( $owner_ID, 'Manual', '', $now, $cat_home_b2evo, array(), 'published',	'en-US', '', get_manual_url( NULL ), 'disabled', array(), 'Sidebar link' );

			// Insert a post into home:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->insert( $owner_ID, 'Support', '', $now, $cat_home_b2evo, array(), 'published', 'en-US', '', 'http://forums.b2evolution.net/', 'disabled', array(), 'Sidebar link' );

			// Insert a PAGE:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'photo' );
			$edited_Item->insert( $owner_ID, T_('About this site'), T_('<p>This blog platform is powered by b2evolution.</p>

<p>You are currently looking at an info page about this site.</p>

<p>Info pages are very much like regular posts, except that they do not appear in the regular flow of posts. They appear as info pages in the menu instead.</p>

<p>If needed, skins can format info pages differently from regular posts.</p>'), $now, $cat_home_b2evo,
					array( $cat_home_b2evo ), 'published', '#', '', '', 'open', array('default'), 'Standalone Page' );
			$edit_File = new File( 'shared', 1, 'logos/b2evolution_1016x208_wbg.png' );
			$LinkOwner = new LinkItem( $edited_Item );
			$edit_File->link_to_Object( $LinkOwner );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'intro' );
			$edited_Item->insert( $owner_ID, T_('Homepage post'), T_('<p>This is the Home page of this site.</p>

<p>More specifically it is the "Front page" of the first collection of this site. This first collection is called "Home". Other sample collections have been created. You can access them by clicking "Blog A", "Blog B", "Photos", etc. in the menu bar at the top of this page.</p>

<p>You can add collections at will. You can also remove them (including this "Home" collection) if you don\'t need one.</p>'),
					$now, $cat_home_b2evo, array(), 'published', '#', '', '', 'open', array( 'default' ), 'Intro-Front' );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'intro' );
			$edited_Item->insert( $owner_ID, T_('Terms & Conditions'), '<p>Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum</p>

<p>Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum</p>',
			$now, $cat_home_b2evo, array(), 'published', '#', '', '', 'open', array( 'default' ), 'Terms & Conditions' );
			if( $edited_Item->ID > 0 )
 			{	// Use this post as default terms & conditions:
 				$Settings->set( 'site_terms', $edited_Item->ID );
 				$Settings->dbupdate();
 			}
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'demo' );
			$edited_Item->insert( $owner_ID, T_('This is a Content Block'), T_('<p>This is a Post/Item of type "Content Block".</p>

<p>A content block can be included in several places.</p>'),
					$now, $cat_home_b2evo, array(), 'published', '#', '', '', 'open', array( 'default' ), 'Content Block' );
			break;

		// =======================================================================================================
		case 'std':
		case 'blog_a':
			$post_count = 11;
			$post_timestamp_array = get_post_timestamp_data( $post_count ) ;

			// Sample categories
			$cat_ann_a = cat_create( T_('Welcome'), 'NULL', $blog_ID, NULL, true );
			$cat_news = cat_create( T_('News'), 'NULL', $blog_ID, NULL, true );
			$cat_bg = cat_create( T_('Background'), 'NULL', $blog_ID, NULL, true );
			$cat_fun = cat_create( T_('Fun'), 'NULL', $blog_ID, NULL, true );
				$cat_life = cat_create( T_('In real life'), $cat_fun, $blog_ID, NULL, true );
					$cat_sports = cat_create( T_('Sports'), $cat_life, $blog_ID, NULL, true );
					$cat_movies = cat_create( T_('Movies'), $cat_life, $blog_ID, NULL, true );
					$cat_music = cat_create( T_('Music'), $cat_life, $blog_ID, NULL, true );
				$cat_web = cat_create( T_('On the web'), $cat_fun, $blog_ID, NULL, true );

			if( $edited_Blog = $BlogCache->get_by_ID( $blog_ID, false, false ) )
			{
				$edited_Blog->set_setting( 'default_cat_ID', $cat_ann_a );
				$edited_Blog->dbupdate();
			}

			// Sample posts
			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'intro' );
			$edited_Item->insert( $owner_ID, T_('Main Intro post'), T_('This is the main intro post. It appears on the homepage only.'),
				$now, $cat_ann_a, array(), 'published', '#', '', '', 'open', array('default'), 'Intro-Main' );
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->insert( $owner_ID, T_('First Post'), T_('<p>This is the first post in the "[coll:shortname]" collection.</p>

<p>It appears in a single category.</p>'), $now, $cat_ann_a );
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->insert( $owner_ID, T_('Second post'), T_('<p>This is the second post in the "[coll:shortname]" collection.</p>

<p>It appears in multiple categories.</p>'), $now, $cat_news, array( $cat_ann_a ) );
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a PAGE:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->insert( $owner_ID, T_('About Blog A'), sprintf( get_filler_text( 'info_page' ), T_('Blog A') ), $now, $cat_ann_a,
					array(), 'published', '#', '', '', 'open', array('default'), 'Standalone Page' );
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'demo' );
			$edited_Item->insert( $owner_ID, T_('This is a multipage post'), T_('<p>This is page 1 of a multipage post.</p>

<blockquote><p>This is a Block Quote.</p></blockquote>

<p>You can see the other pages by clicking on the links below the text.</p>').'

[pagebreak]

<p>'.sprintf( T_('This is page %d.'), 2 ).'</p>'.get_filler_text( 'lorem_2more' ).'

[pagebreak]

<p>'.sprintf( T_('This is page %d.'), 3 ).'</p>'.get_filler_text( 'lorem_1paragraph' ).'

[pagebreak]

<p>'.sprintf( T_('This is page %d.'), 4 ).'</p>

<p>'.T_('It is the last page.').'</p>', $now, $cat_bg );
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'demo' );
			$edited_Item->insert( $owner_ID, T_('Extended post with no teaser'), '<p>'.T_('This is an extended post with no teaser. This means that you won\'t see this teaser any more when you click the "more" link.').'</p>'.get_filler_text( 'lorem_1paragraph' )
.'[teaserbreak]

<p>'.T_('This is the extended text. You only see it when you have clicked the "more" link.').'</p>'.get_filler_text( 'lorem_2more' ), $now, $cat_bg );
			$edited_Item->set_setting( 'hide_teaser', '1' );
			$edited_Item->dbsave();
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set( 'featured', 1 );
			$edited_Item->set_tags_from_string( 'photo,demo' );
			$edited_Item->insert( $owner_ID, T_('Extended post'), '<p>'.T_('This is an extended post. This means you only see this small teaser by default and you must click on the link below to see more.').'</p>'.get_filler_text( 'lorem_1paragraph' )
.'[teaserbreak]

<p>'.T_('This is the extended text. You only see it when you have clicked the "more" link.').'</p>'.get_filler_text( 'lorem_2more' ), $now, $cat_bg );
			$LinkOwner = new LinkItem( $edited_Item );
			$edit_File = new File( 'shared', 0, 'monument-valley/john-ford-point.jpg' );
			$edit_File->link_to_Object( $LinkOwner, 1, 'cover' );
			$edit_File = new File( 'shared', 0, 'monument-valley/monument-valley-road.jpg' );
			$edit_File->link_to_Object( $LinkOwner, 2, 'teaser' );
			$edit_File = new File( 'shared', 0, 'monument-valley/monuments.jpg' );
			$edit_File->link_to_Object( $LinkOwner, 3, 'aftermore' );
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'demo' );
			$edited_Item->set_setting( 'custom_double_1', '123' );
			$edited_Item->set_setting( 'custom_double_2', '456' );
			$edited_Item->set_setting( 'custom_varchar_3', 'abc' );
			$edited_Item->set_setting( 'custom_varchar_4', 'Enter your own values' );
			$edited_Item->set_setting( 'custom_text_5', 'This is a sample text field.
 It can have multiple lines.' );
 			$edited_Item->set_setting( 'custom_html_6', 'This is an <b>HTML</b> <i>field</i>.' );
			$edited_Item->set_setting( 'custom_url_7', 'http://b2evolution.net/' );
			$post_custom_fields_ID = $edited_Item->insert( $owner_ID, T_('Custom Fields Example'),
'<p>'.T_('This post has a special post type called "Post with custom fields".').'</p>'.

'<p>'.T_('This post type defines 4 custom fields. Here are the sample values that have been entered in these fields:').'</p>'.

'<p>[fields]</p>'.

'<p>'.T_('It is also possible to selectively display only a couple of these fields:').'</p>'.

'<p>[fields:first_numeric_field, first_string_field,second_numeric_field]</p>'.

'<p>'.sprintf( T_('Finally, we can also display just the value of a specific field, like this: %s.'), '[field:first_string_field]' ).'</p>'.

'<p>'.sprintf( T_('It is also possible to create links using a custom field URL: %s'), '[link:url_field:.btn.btn-info]Click me![/link]' ).'</p>',
					$now, $cat_bg, array(), 'published', '#', '', '', 'open', array('default'), 'Post with Custom Fields' );
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'demo' );
			$edited_Item->set( 'parent_ID', $post_custom_fields_ID ); // Set parent post ID
			/*$edited_Item->insert( $owner_ID, T_('Child Post Example'), T_('<p>This post has a special post type called "Child Post".</p>'),*/
			$edited_Item->insert( $owner_ID, T_('Child Post Example'),
'<p>'.sprintf( T_('This post has a special post type called "Child Post". This allowed to specify a parent post ID. Consequently, this child post is linked to: %s.'), '[parent:titlelink] ([parent:url])' ).'</p>

<p>'.T_('This also allows us to access the custom fields of the parent post:').'</p>

<p>[parent:fields]</p>

<p>'.T_('It is also possible to selectively display only a couple of these fields:').'</p>

<p>[parent:fields:first_numeric_field, first_string_field,second_numeric_field]</p>

<p>'.sprintf( T_('Finally, we can also display just the value of a specific field, like this %s.'), '[parent:field: first_string_field]' ).'</p>

<p>'.sprintf( T_('It is also possible to create links using a custom field URL from the parent post: %s'), '[parent:link:url_field:.btn.btn-info]Click me![/link]' ).'</p>',
					$now, $cat_bg, array(), 'published', '#', '', '', 'open', array('default'), 'Child Post' );
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set( 'featured', 1 );
			$edited_Item->set_tags_from_string( 'photo,demo' );
			$edited_Item->insert( $owner_ID, T_('Image post'), T_('<p>This post has several images attached to it. Each one uses a different Attachment Position. Each may be displayed differently depending on the skin they are viewed in.</p>

<p>Check out the photoblog (accessible through the links at the top) to see a completely different skin focused more on the photos than on the blog text.</p>'), $now, $cat_bg );
			$LinkOwner = new LinkItem( $edited_Item );
			$edit_File = new File( 'shared', 0, 'monument-valley/monument-valley.jpg' );
			$edit_File->link_to_Object( $LinkOwner, 1, 'cover' );
			$edit_File = new File( 'shared', 0, 'monument-valley/monuments.jpg' );
			$edit_File->link_to_Object( $LinkOwner, 2, 'teaser' );
			$edit_File = new File( 'shared', 0, 'monument-valley' );
			$edit_File->link_to_Object( $LinkOwner, 3, 'aftermore' );
			$edit_File = new File( 'shared', 0, 'monument-valley/bus-stop-ahead.jpg' );
			$edit_File->link_to_Object( $LinkOwner, 4, 'aftermore' );
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'photo' );
			$additional_comments_item_IDs[] = $edited_Item->insert( $owner_ID, T_('Welcome to your b2evolution-powered website!'),
T_("<p>To get you started, the installer has automatically created several sample collections and populated them with some sample contents. Of course, this starter structure is all yours to edit. Until you do that, though, here's what you will find on this site:</p>

<ul>
<li><strong>Blog A</strong>: You are currently looking at it. It contains a few sample posts, using simple features of b2evolution.</li>
<li><strong>Blog B</strong>: You can access it from the link at the top of the page. It contains information about more advanced features. Note that it is deliberately using a different skin from Blog A to give you an idea of what's possible.</li>
<li><strong>Photos</strong>: This collection is an example of how you can use b2evolution to showcase photos, with photos grouped into photo albums.</li>
<li><strong>Forums</strong>: This collection is a discussion forum (a.k.a. bulletin board) allowing your users to discuss among themselves.</li>
<li><strong>Manual</strong>: This showcases how b2evolution can be used to publish structured content such as an online manual or book.</li>

</ul>

<p>You can add new collections of any type (blog, photos, forums, etc.), delete unwanted one and customize existing collections (title, sidebar, blog skin, widgets, etc.) from the admin interface.</p>"), $now, $cat_ann_a );
			$edit_File = new File( 'shared', 0, 'logos/b2evolution_1016x208_wbg.png' );
			$LinkOwner = new LinkItem( $edited_Item );
			$edit_File->link_to_Object( $LinkOwner );
			$item_IDs[] = array( $edited_Item->ID, $now );
			break;

		// =======================================================================================================
		case 'blog_b':
			$post_count = 11;
			$post_timestamp_array = get_post_timestamp_data( $post_count ) ;

			// Sample categories
			$cat_ann_b = cat_create( T_('Announcements'), 'NULL', $blog_ID );
			$cat_b2evo = cat_create( T_('b2evolution Tips'), 'NULL', $blog_ID );
			$cat_additional_skins = cat_create( T_('Get additional skins'), 'NULL', $blog_ID );

			if( $edited_Blog = $BlogCache->get_by_ID( $blog_ID, false, false ) )
			{
				$edited_Blog->set_setting( 'default_cat_ID', $cat_ann_b );
				$edited_Blog->dbupdate();
			}

			// Sample posts

			// Insert sidebar links into Blog B
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->insert( $owner_ID, 'Skin Faktory', '', $now, $cat_additional_skins, array(), 'published', 'en-US', '', 'http://www.skinfaktory.com/', 'open', array('default'), 'Sidebar link' );

			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->insert( $owner_ID, T_('b2evo skins repository'), '', $now, $cat_additional_skins, array(), 'published', 'en-US', '', 'http://skins.b2evolution.net/', 'open', array('default'), 'Sidebar link' );

			// Insert a PAGE:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->insert( $owner_ID, T_('About Blog B'), sprintf( get_filler_text( 'info_page'), T_('Blog B') ), $now, $cat_ann_b,
				array(), 'published', '#', '', '', 'open', array('default'), 'Standalone Page' );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'intro' );
			$edited_Item->insert( $owner_ID, T_('Welcome to Blog B'), sprintf( T_('<p>This is the intro post for the front page of Blog B.</p>

<p>Blog B is currently configured to show a front page like this one instead of directly showing the blog\'s posts.</p>

<ul>
<li>To view the blog\'s posts, click on "News" in the menu above.</li>
<li>If you don\'t want to have such a front page, you can disable it in the Blog\'s settings > Features > <a %s>Front Page</a>. You can also see an example of a blog without a Front Page in Blog A</li>
</ul>'), 'href="'.$admin_url.'?ctrl=coll_settings&amp;tab=home&amp;blog='.$blog_ID.'"' ),
					$now, $cat_b2evo, array(), 'published', '#', '', '', 'open', array('default'), 'Intro-Front' );
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'intro' );
			$edited_Item->insert( $owner_ID, T_('b2evolution tips category &ndash; Sub Intro post'), T_('This uses post type "Intro-Cat" and is attached to the desired Category(ies).'),
					$now, $cat_b2evo, array(), 'published', '#', '', '', 'open', array('default'), 'Intro-Cat' );
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'widgets,intro' );
			$edited_Item->insert( $owner_ID, T_('Widgets tag &ndash; Sub Intro post'), T_('This uses post type "Intro-Tag" and is tagged with the desired Tag(s).'),
					$now, $cat_b2evo, array(), 'published', '#', '', '', 'open', array('default'), 'Intro-Tag' );
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a post:
			// TODO: move to Blog A
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->insert( $owner_ID, T_('Featured post'), T_('<p>This is a demo of a featured post.</p>

<p>It will be featured whenever we have no specific "Intro" post to display for the current request. To see it in action, try displaying the "Announcements" category.</p>

<p>Also note that when the post is featured, it does not appear in the regular post flow.</p>').get_filler_text( 'lorem_1paragraph' ),
					$now, $cat_b2evo, array( $cat_ann_b ) );
			$edited_Item->set( 'featured', 1 );
			$edited_Item->dbsave();
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->insert( $owner_ID, T_('Apache optimization...'), sprintf( T_('<p>b2evolution comes with an <code>.htaccess</code> file destined to optimize the way b2evolution is handled by your webseerver (if you are using Apache). In some circumstances, that file may not be automatically activated at setup. Please se the man page about <a %s>Tricky Stuff</a> for more information.</p>

<p>For further optimization, please review the manual page about <a %s>Performance optimization</a>. Depending on your current configuration and on what your <a %s>web hosting</a> company allows you to do, you may increase the speed of b2evolution by up to a factor of 10!</p>'),
'href="'.get_manual_url( 'tricky-stuff' ).'"',
'href="'.get_manual_url( 'performance-optimization' ).'"',
'href="http://b2evolution.net/web-hosting/"' ),
					$now, $cat_b2evo, array( $cat_ann_b ) );
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'skins' );
			$edited_Item->insert( $owner_ID, T_('Skins, Stubs, Templates &amp; website integration...'), T_("<p>By default, blogs are displayed using an evoskin. (More on skins in another post.)</p>

<p>This means, blogs are accessed through '<code>index.php</code>', which loads default parameters from the database and then passes on the display job to a skin.</p>

<p>Alternatively, if you don't want to use the default DB parameters and want to, say, force a skin, a category or a specific linkblog, you can create a stub file like the provided '<code>a_stub.php</code>' and call your blog through this stub instead of index.php .</p>

<p>Finally, if you need to do some very specific customizations to your blog, you may use plain templates instead of skins. In this case, call your blog through a full template, like the provided '<code>a_noskin.php</code>'.</p>

<p>If you want to integrate a b2evolution blog into a complex website, you'll probably want to do it by copy/pasting code from <code>a_noskin.php</code> into a page of your website.</p>

<p>You will find more information in the stub/template files themselves. Open them in a text editor and read the comments in there.</p>

<p>Either way, make sure you go to the blogs admin and set the correct access method/URL for your blog. Otherwise, the permalinks will not function properly.</p>"), $now, $cat_b2evo );
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'widgets' );
			$edited_Item->insert( $owner_ID, T_('About widgets...'), T_('<p>b2evolution blogs are installed with a default selection of Widgets. For example, the sidebar of this blog includes widgets like a calendar, a search field, a list of categories, a list of XML feeds, etc.</p>

<p>You can add, remove and reorder widgets from the Blog Settings tab in the admin interface.</p>

<p>Note: in order to be displayed, widgets are placed in containers. Each container appears in a specific place in an evoskin. If you change your blog skin, the new skin may not use the same containers as the previous one. Make sure you place your widgets in containers that exist in the specific skin you are using.</p>'), $now, $cat_b2evo );
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'skins' );
			$edited_Item->insert( $owner_ID, T_('About skins...'), sprintf( T_('<p>By default, b2evolution blogs are displayed using an evoskin.</p>

<p>You can change the skin used by any blog by editing the blog settings in the admin interface.</p>

<p>You can download additional skins from the <a href="http://skins.b2evolution.net/" target="_blank">skin site</a>. To install them, unzip them in the /blogs/skins directory, then go to General Settings &gt; Skins in the admin interface and click on "Install new".</p>

<p>You can also create your own skins by duplicating, renaming and customizing any existing skin folder from the /blogs/skins directory.</p>

<p>To start customizing a skin, open its "<code>index.main.php</code>" file in an editor and read the comments in there. Note: you can also edit skins in the "Files" tab of the admin interface.</p>

<p>And, of course, read the <a href="%s" target="_blank">manual on skins</a>!</p>'), get_manual_url( 'skin-structure' ) ), $now, $cat_b2evo );
			$edited_Item->dbsave();
			// $edited_Item->insert_update_tags( 'update' );
			$item_IDs[] = array( $edited_Item->ID, $now );
			break;

		// =======================================================================================================
		case 'photo':
			$post_count = 3;
			$post_timestamp_array = get_post_timestamp_data( $post_count ) ;

			// Sample categories
			$cat_photo_album = cat_create( T_('Landscapes'), 'NULL', $blog_ID, NULL, true );

			if( $edited_Blog = $BlogCache->get_by_ID( $blog_ID, false, false ) )
			{
				$edited_Blog->set_setting( 'default_cat_ID', $cat_photo_album );
				$edited_Blog->dbupdate();
			}

			// Sample posts

			// Insert a PAGE:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->insert( $owner_ID, T_('About Photos'), sprintf( get_filler_text( 'info_page'), T_('Photos') ), $now, $cat_photo_album,
					array(), 'published', '#', '', '', 'open', array('default'), 'Standalone Page' );

			// Insert a post into photoblog:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'photo' );
			$edited_Item->insert( $owner_ID, T_('Sunset'), '',
					$now, $cat_photo_album, array(), 'published','en-US' );
			$LinkOwner = new LinkItem( $edited_Item );
			$edit_File = new File( 'shared', 0, 'sunset/sunset.jpg' );
			$photo_link_1_ID = $edit_File->link_to_Object( $LinkOwner, 1 );
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a post into photoblog:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'photo' );
			$edited_Item->insert( $owner_ID, T_('Bus Stop Ahead'), T_('In the middle of nowhere: a school bus stop where you wouldn\'t really expect it!'),
					$now, $cat_photo_album, array(), 'published','en-US', '', 'http://fplanque.com/photo/monument-valley' );
			$LinkOwner = new LinkItem( $edited_Item );
			$edit_File = new File( 'shared', 0, 'monument-valley/bus-stop-ahead.jpg' );
			$photo_link_1_ID = $edit_File->link_to_Object( $LinkOwner, 1 );
			$edit_File = new File( 'shared', 0, 'monument-valley/john-ford-point.jpg' );
			$photo_link_2_ID = $edit_File->link_to_Object( $LinkOwner, 2, 'aftermore' );
			$edit_File = new File( 'shared', 0, 'monument-valley/monuments.jpg' );
			$photo_link_3_ID = $edit_File->link_to_Object( $LinkOwner, 3, 'aftermore' );
			$edit_File = new File( 'shared', 0, 'monument-valley/monument-valley-road.jpg' );
			$photo_link_4_ID = $edit_File->link_to_Object( $LinkOwner, 4, 'aftermore' );
			$edit_File = new File( 'shared', 0, 'monument-valley/monument-valley.jpg' );
			$photo_link_5_ID = $edit_File->link_to_Object( $LinkOwner, 5, 'aftermore' );
			$item_IDs[] = array( $edited_Item->ID, $now );

			if( $install_test_features )
			{ // Add examples for infodots plugin
				$edited_Item->set_tags_from_string( 'photo,demo' );
				$edited_Item->set( 'content', $edited_Item->get( 'content' ).sprintf( '
[infodot:%s:191:36:100px]School bus [b]here[/b]

#### In the middle of nowhere:
a school bus stop where you wouldn\'t really expect it!

1. Item 1
2. Item 2
3. Item 3

[enddot]
[infodot:%s:104:99]cowboy and horse[enddot]
[infodot:%s:207:28:15em]Red planet[enddot]', $photo_link_1_ID, $photo_link_2_ID, $photo_link_4_ID ) );
				$edited_Item->dbupdate();
				echo_install_log( 'TEST FEATURE: Adding examples for plugin "Info dots renderer" on item #'.$edited_Item->ID );
				$item_IDs[] = array( $edited_Item->ID, $now );
			}
			break;

		// =======================================================================================================
		case 'forum':
			$post_count = 9;
			$post_timestamp_array = get_post_timestamp_data( $post_count ) ;

			$mary_demo_user = get_demo_user( 'mary' );
			$user_1 = $mary_demo_user ? $mary_demo_user->ID : $owner_ID;

			$jay_demo_user = get_demo_user( 'jay' );
			$user_2 = $jay_demo_user ? $jay_demo_user->ID : $owner_ID;

			$dave_demo_user = get_demo_user( 'dave' );
			$user_3 = $dave_demo_user ? $dave_demo_user->ID : $owner_ID;

			$paul_demo_user = get_demo_user( 'paul' );
			$user_4 = $paul_demo_user ? $paul_demo_user->ID : $owner_ID;

			$larry_demo_user = get_demo_user( 'larry' );
			$user_5 = $larry_demo_user ? $larry_demo_user->ID : $owner_ID;

			$kate_demo_user = get_demo_user( 'kate' );
			$user_6 = $kate_demo_user ? $kate_demo_user->ID : $owner_ID;

			// Sample categories
			$cat_forums_forum_group = cat_create( T_('A forum group'), 'NULL', $blog_ID, NULL, true, 1, NULL, true );
				$cat_forums_ann = cat_create( T_('Welcome'), $cat_forums_forum_group, $blog_ID, T_('Welcome description'), true, 1 );
				$cat_forums_aforum = cat_create( T_('A forum'), $cat_forums_forum_group, $blog_ID, T_('Short description of this forum'), true, 2 );
				$cat_forums_anforum = cat_create( T_('Another forum'), $cat_forums_forum_group, $blog_ID, T_('Short description of this forum'), true, 3 );
			$cat_forums_another_group = cat_create( T_('Another group'), 'NULL', $blog_ID, NULL, true, 2, NULL, true );
				$cat_forums_bg = cat_create( T_('Background'), $cat_forums_another_group, $blog_ID, T_('Background description'), true, 1 );
				$cat_forums_news = cat_create( T_('News'), $cat_forums_another_group, $blog_ID, T_('News description'), true, 2 );
				$cat_forums_fun = cat_create( T_('Fun'), $cat_forums_another_group, $blog_ID, T_('Fun description'), true, 3 );
					$cat_forums_life = cat_create( T_('In real life'), $cat_forums_fun, $blog_ID, NULL, true, 4, 'alpha' );
						$cat_forums_movies = cat_create( T_('Movies'), $cat_forums_life, $blog_ID, NULL, true );
						$cat_forums_music = cat_create( T_('Music'), $cat_forums_life, $blog_ID, NULL, true );
						$cat_forums_sports = cat_create( T_('Sports'), $cat_forums_life, $blog_ID, NULL, true );
					$cat_forums_web = cat_create( T_('On the web'), $cat_forums_fun, $blog_ID, NULL, true, 5 );

			if( $edited_Blog = $BlogCache->get_by_ID( $blog_ID, false, false ) )
			{
				$edited_Blog->set_setting( 'default_cat_ID', $cat_forums_forum_group );
				$edited_Blog->dbupdate();
			}


			// Sample posts

			// Insert a PAGE:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->insert( 1, T_('About Forums'), sprintf( get_filler_text( 'info_page' ), T_('Forums') ), $now, $cat_forums_ann,
				array(), 'published', '#', '', '', 'open', array('default'), 'Standalone Page' );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->insert( $user_1, T_('First Topic'), T_('<p>This is the first topic in the "[coll:shortname]" collection.</p>

<p>It appears in a single category.</p>').get_filler_text( 'lorem_2more'), $now, $cat_forums_ann );
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->insert( $user_2, T_('Second topic'), T_('<p>This is the second topic in the "[coll:shortname]" collection.</p>

<p>It appears in multiple categories.</p>').get_filler_text( 'lorem_2more'), $now, $cat_forums_news, array( $cat_forums_ann ) );
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'photo' );
			$edited_Item->insert( $user_3, T_('Image topic'), T_('<p>This topic has an image attached to it. The image is automatically resized to fit the current blog skin. You can zoom in by clicking on the thumbnail.</p>

<p>Check out the photoblog (accessible through the links at the top) to see a completely different skin focused more on the photos than on the blog text.</p>'), $now, $cat_forums_bg );
			$edit_File = new File( 'shared', 0, 'monument-valley/monuments.jpg' );
			$LinkOwner = new LinkItem( $edited_Item );
			$edit_File->link_to_Object( $LinkOwner );
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'demo' );
			$edited_Item->insert( $user_4, T_('This is a multipage topic'), T_('<p>This is page 1 of a multipage topic.</p>

<blockquote><p>This is a Block Quote.</p></blockquote>

<p>You can see the other pages by clicking on the links below the text.</p>').'

[pagebreak]

<p>'.sprintf( T_('This is page %d.'), 2 ).'</p>'.get_filler_text( 'lorem_2more' ).'

[pagebreak]

<p>'.sprintf( T_('This is page %d.'), 3 ).'</p>'.get_filler_text( 'lorem_1paragraph' ).'

[pagebreak]

<p>'.sprintf( T_('This is page %d.'), 4 ).'</p>

<p>'.T_('It is the last page.').'</p>', $now, $cat_forums_bg );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'demo' );
			$edited_Item->insert( $user_5, T_('Extended topic with no teaser'), T_('<p>This is an extended topic with no teaser. This means that you won\'t see this teaser any more when you click the "more" link.</p>

[teaserbreak]

<p>This is the extended text. You only see it when you have clicked the "more" link.</p>'), $now, $cat_forums_bg );
			$edited_Item->set_setting( 'hide_teaser', '1' );
			$edited_Item->dbsave();
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'demo' );
			$edited_Item->insert( $user_6, T_('Extended topic'), T_('<p>This is an extended topic. This means you only see this small teaser by default and you must click on the link below to see more.</p>

[teaserbreak]

<p>This is the extended text. You only see it when you have clicked the "more" link.</p>'), $now, $cat_forums_bg );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'photo' );
			$additional_comments_item_IDs[] = $edited_Item->insert( 1, T_('Welcome to your b2evolution-powered website!'),
T_("<p>To get you started, the installer has automatically created several sample collections and populated them with some sample contents. Of course, this starter structure is all yours to edit. Until you do that, though, here's what you will find on this site:</p>

<ul>
<li><strong>Blog A</strong>: You are currently looking at it. It contains a few sample posts, using simple features of b2evolution.</li>
<li><strong>Blog B</strong>: You can access it from the link at the top of the page. It contains information about more advanced features. Note that it is deliberately using a different skin from Blog A to give you an idea of what's possible.</li>
<li><strong>Photos</strong>: This collection is an example of how you can use b2evolution to showcase photos, with photos grouped into photo albums.</li>
<li><strong>Forums</strong>: This collection is a discussion forum (a.k.a. bulletin board) allowing your users to discuss among themselves.</li>
<li><strong>Manual</strong>: This showcases how b2evolution can be used to publish structured content such as an online manual or book.</li>

</ul>

<p>You can add new collections of any type (blog, photos, forums, etc.), delete unwanted one and customize existing collections (title, sidebar, blog skin, widgets, etc.) from the admin interface.</p>"), $now, $cat_forums_ann );
			$edit_File = new File( 'shared', 0, 'logos/b2evolution_1016x208_wbg.png' );
			$LinkOwner = new LinkItem( $edited_Item );
			$edit_File->link_to_Object( $LinkOwner );
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert Markdown example post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'demo' );
			$edited_Item->insert( $user_1, T_('Markdown examples'), get_filler_text( 'markdown_examples_content'), $now, $cat_forums_news );
			$item_IDs[] = array( $edited_Item->ID, $now );
			break;

		// =======================================================================================================
		case 'manual':
			$post_count = 15;
			$post_timestamp_array = get_post_timestamp_data( $post_count ) ;

			// Sample categories
			$cat_manual_intro = cat_create( T_('Introduction'), NULL, $blog_ID, NULL, true, 10 );
			$cat_manual_getstarted = cat_create( T_('Getting Started'), NULL, $blog_ID, NULL, true, 20 );
			$cat_manual_userguide = cat_create( T_('User Guide'), NULL, $blog_ID, NULL, true, 30 );
			$cat_manual_reference = cat_create( T_('Reference'), NULL, $blog_ID, NULL, true, 40, 'alpha' );

			$cat_manual_everyday = cat_create( T_('Collections'), $cat_manual_reference, $blog_ID, NULL, true, 10 );
			$cat_manual_advanced = cat_create( T_('Other'), $cat_manual_reference, $blog_ID, NULL, true, 5 );

			$cat_manual_blogs = cat_create( T_('Blogs'), $cat_manual_everyday, $blog_ID, NULL, true, 35 );
			$cat_manual_photos = cat_create( T_('Photo Albums'), $cat_manual_everyday, $blog_ID, NULL, true, 25 );
			$cat_manual_forums = cat_create( T_('Forums'), $cat_manual_everyday, $blog_ID, NULL, true, 5 );


			if( $edited_Blog = $BlogCache->get_by_ID( $blog_ID, false, false ) )
			{
				$edited_Blog->set_setting( 'default_cat_ID', $cat_manual_intro );
				$edited_Blog->dbupdate();
			}

			// Sample posts

			// Insert a main intro:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'intro' );
			$edited_Item->insert( $owner_ID, T_('Welcome here!'), T_('This is the main introduction for this demo online manual. It is a post using the type "Intro-Front". It will only appear on the front page of the manual.

You may delete this post if you don\'t want such an introduction.

Just to be clear: this is a **demo** of a manual. The user manual for b2evolution is here: http://b2evolution.net/man/.'), $now, $cat_manual_intro,
					array(), 'published', '#', '', '', 'open', array('default'), 'Intro-Front' );

			// Insert a cat intro:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'intro' );
			$edited_Item->insert( $owner_ID, T_('Chapter Intro'), T_('This is an introduction for this chapter. It is a post using the "intro-cat" type.'), $now, $cat_manual_intro,
					array(), 'published', '#', '', '', 'open', array('default'), 'Intro-Cat' );

			// Insert a cat intro:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'intro' );
			$edited_Item->insert( $owner_ID, T_('Chapter Intro'), T_('This is an introduction for this chapter. It is a post using the "intro-cat" type.')
."\n\n".T_('Contrary to the other sections which are explictely sorted by default, this section is sorted alphabetically by default.'), $now, $cat_manual_reference,
					array(), 'published', '#', '', '', 'open', array('default'), 'Intro-Cat' );

			// Insert a PAGE:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->insert( $owner_ID, T_('About this manual'), sprintf( get_filler_text( 'info_page' ), T_('Manual') ), $now, $cat_manual_intro,
					array(), 'published', '#', '', '', 'open', array('default'), 'Standalone Page' );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->insert( $owner_ID, T_('First Page'), T_('<p>This is the first page in the "[coll:shortname]" collection.</p>

<p>It appears in a single category.</p>'), $now, $cat_manual_intro, array(),
'published', '#', '', '', 'open', array('default'), 'Manual Page', NULL, 10 );
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->insert( $owner_ID, T_('Second Page'), T_('<p>This is the second page in the "[coll:shortname]" collection.</p>

<p>It appears in multiple categories.</p>'), $now, $cat_manual_intro, array( $cat_manual_getstarted ),
'published', '#', '', '', 'open', array('default'), 'Manual Page', NULL, 20 );
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'demo' );
			$edited_Item->insert( $owner_ID, T_('Wiki Tables'), /* DO NOT TRANSLATE - TOO COMPLEX */ '<p>This is the topic with samples of the wiki tables.</p>

{|
|Orange
|Apple
|-
|Bread
|Pie
|-
|Butter
|Ice cream
|}

{|
|Orange||Apple||more
|-
|Bread||Pie||more
|-
|Butter||Ice<br />cream||and<br />more
|}

{|
|Lorem ipsum dolor sit amet,
consetetur sadipscing elitr,
sed diam nonumy eirmod tempor invidunt
ut labore et dolore magna aliquyam erat,
sed diam voluptua.

At vero eos et accusam et justo duo dolores
et ea rebum. Stet clita kasd gubergren,
no sea takimata sanctus est Lorem ipsum
dolor sit amet.
|
* Lorem ipsum dolor sit amet
* consetetur sadipscing elitr
* sed diam nonumy eirmod tempor invidunt
|}

{|
! align="left"| Item
! Amount
! Cost
|-
|Orange
|10
|7.00
|-
|Bread
|4
|3.00
|-
|Butter
|1
|5.00
|-
!Total
|
|15.00
|}

<br />

{|
|+Food complements
|-
|Orange
|Apple
|-
|Bread
|Pie
|-
|Butter
|Ice cream
|}

{| class="wikitable"
|+Food complements
|-
|Orange
|Apple
|-
|Bread
|Pie
|-
|Butter
|Ice cream
|}

{| class="wikitable" style="text-align: center; color: green;"
|Orange
|Apple
|12,333.00
|-
|Bread
|Pie
|500.00
|-
|Butter
|Ice cream
|1.00
|}

{| class="wikitable"
| Orange
| Apple
| align="right"| 12,333.00
|-
| Bread
| Pie
| align="right"| 500.00
|-
| Butter
| Ice cream
| align="right"| 1.00
|}

{| class="wikitable"
| Orange || Apple     || align="right" | 12,333.00
|-
| Bread  || Pie       || align="right" | 500.00
|-
| Butter || Ice cream || align="right" | 1.00
|}

{| class="wikitable"
| Orange
| Apple
| align="right"| 12,333.00
|-
| Bread
| Pie
| align="right"| 500.00
|- style="font-style: italic; color: green;"
| Butter
| Ice cream
| align="right"| 1.00
|}

{| style="border-collapse: separate; border-spacing: 0; border: 1px solid #000; padding: 0"
|-
| style="border-style: solid; border-width: 0 1px 1px 0"|
Orange
| style="border-style: solid; border-width: 0 0 1px 0"|
Apple
|-
| style="border-style: solid; border-width: 0 1px 0 0"|
Bread
| style="border-style: solid; border-width: 0"|
Pie
|}

{| style="border-collapse: collapse; border: 1px solid #000"
|-
| style="border-style: solid; border-width: 1px"|
Orange
| style="border-style: solid; border-width: 1px"|
Apple
|-
| style="border-style: solid; border-width: 1px"|
Bread
| style="border-style: solid; border-width: 1px"|
Pie
|}

{|style="border-style: solid; border-width: 20px"
|
Hello
|}

{|style="border-style: solid; border-width: 10px 20px 100px 0"
|
Hello
|}

{| class="wikitable"
!colspan="6"|Shopping List
|-
|rowspan="2"|Bread &amp; Butter
|Pie
|Buns
|Danish
|colspan="2"|Croissant
|-
|Cheese
|colspan="2"|Ice cream
|Butter
|Yogurt
|}

{| class="wikitable" style="color:green; background-color:#ffffcc;" cellpadding="10"
|Orange
|Apple
|-
|Bread
|Pie
|-
|Butter
|Ice cream
|}

{| class="wikitable"
|+ align="bottom" style="color:#e76700;"|\'\'Food complements\'\'
|-
|Orange
|Apple
|-
|Bread
|Pie
|-
|Butter
|Ice cream
|}

{| style="color: black; background-color: #ffffcc;" width="85%"
| colspan="2" | This column width is 85% of the screen width (and has a background color)
|-
| style="width: 30%; background-color: white;"|
\'\'\'This column is 30% counted from 85% of the screen width\'\'\'
| style="width: 70%; background-color: orange;"|
\'\'\'This column is 70% counted from 85% of the screen width (and has a background color)\'\'\'
|}

{| class="wikitable"
|-
! scope="col"| Item
! scope="col"| Quantity
! scope="col"| Price
|-
! scope="row"| Bread
| 0.3 kg
| $0.65
|-
! scope="row"| Butter
| 0.125 kg
| $1.25
|-
! scope="row" colspan="2"| Total
| $1.90
|}', $now, $cat_manual_reference, array( $cat_manual_userguide ),
'published', '#', '', '', 'open', array('default'), 'Manual Page', NULL, 50 );
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'photo' );
			$edited_Item->insert( $owner_ID, T_('Image topic'), T_('<p>This topic has an image attached to it. The image is automatically resized to fit the current blog skin. You can zoom in by clicking on the thumbnail.</p>

<p>Check out the photoblog (accessible through the links at the top) to see a completely different skin focused more on the photos than on the blog text.</p>'), $now, $cat_manual_getstarted, array( $cat_manual_blogs ),
'published', '#', '', '', 'open', array('default'), 'Manual Page', NULL, 10 );
			$edit_File = new File( 'shared', 0, 'monument-valley/monuments.jpg' );
			$LinkOwner = new LinkItem( $edited_Item );
			$edit_File->link_to_Object( $LinkOwner );
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'demo' );
			$edited_Item->insert( $owner_ID, T_('This is a multipage topic'), T_('<p>This is page 1 of a multipage topic.</p>

<blockquote><p>This is a Block Quote.</p></blockquote>

<p>You can see the other pages by clicking on the links below the text.</p>').'

[pagebreak]

<p>'.sprintf( T_('This is page %d.'), 2 ).'</p>'.get_filler_text( 'lorem_2more' ).'

[pagebreak]

<p>'.sprintf( T_('This is page %d.'), 3 ).'</p>'.get_filler_text( 'lorem_1paragraph' ).'

[pagebreak]

<p>'.sprintf( T_('This is page %d.'), 4 ).'</p>

<p>'.T_('It is the last page.').'</p>', $now, $cat_manual_userguide, array(),
'published', '#', '', '', 'open', array('default'), 'Manual Page', NULL, 30 );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'demo' );
			$edited_Item->insert( $owner_ID, T_('Extended topic with no teaser'), T_('<p>This is an extended topic with no teaser. This means that you won\'t see this teaser any more when you click the "more" link.</p>

[teaserbreak]

<p>This is the extended text. You only see it when you have clicked the "more" link.</p>'), $now, $cat_manual_userguide, array(),
					'published', '#', '', '', 'open', array('default'), 'Manual Page', NULL, 20 );
			$edited_Item->set_setting( 'hide_teaser', '1' );
			$edited_Item->dbsave();
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'demo' );
			$edited_Item->insert( $owner_ID, T_('Extended topic'), T_('<p>This is an extended topic. This means you only see this small teaser by default and you must click on the link below to see more.</p>

[teaserbreak]

<p>This is the extended text. You only see it when you have clicked the "more" link.</p>'), $now, $cat_manual_userguide, array(),
					'published', '#', '', '', 'open', array('default'), 'Manual Page', NULL, 10 );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'photo' );
			$additional_comments_item_IDs[] = $edited_Item->insert( $owner_ID, T_('Welcome to your b2evolution-powered website!'),
		T_("<p>To get you started, the installer has automatically created several sample collections and populated them with some sample contents. Of course, this starter structure is all yours to edit. Until you do that, though, here's what you will find on this site:</p>

<ul>
<li><strong>Blog A</strong>: You are currently looking at it. It contains a few sample posts, using simple features of b2evolution.</li>
<li><strong>Blog B</strong>: You can access it from the link at the top of the page. It contains information about more advanced features. Note that it is deliberately using a different skin from Blog A to give you an idea of what's possible.</li>
<li><strong>Photos</strong>: This collection is an example of how you can use b2evolution to showcase photos, with photos grouped into photo albums.</li>
<li><strong>Forums</strong>: This collection is a discussion forum (a.k.a. bulletin board) allowing your users to discuss among themselves.</li>
<li><strong>Manual</strong>: This showcases how b2evolution can be used to publish structured content such as an online manual or book.</li>

</ul>

<p>You can add new collections of any type (blog, photos, forums, etc.), delete unwanted one and customize existing collections (title, sidebar, blog skin, widgets, etc.) from the admin interface.</p>"), $now, $cat_manual_intro, array( $cat_manual_everyday ),
					'published', '#', '', '', 'open', array('default'), 'Manual Page', NULL, 30 );
			$edit_File = new File( 'shared', 0, 'logos/b2evolution_1016x208_wbg.png' );
			$LinkOwner = new LinkItem( $edited_Item );
			$edit_File->link_to_Object( $LinkOwner );
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->insert( $owner_ID, T_('Sports post'), T_('<p>This is the sports post.</p>

<p>It appears in sports category.</p>'), $now, $cat_manual_blogs, array(),
					'published', '#', '', '', 'open', array('default'), 'Manual Page', NULL, 15 );
			$item_IDs[] = array( $edited_Item->ID, $now );

			// Insert a post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->insert( $owner_ID, T_('Second sports post'), T_('<p>This is the second sports post.</p>

<p>It appears in sports category.</p>'), $now, $cat_manual_blogs, array(),
					'published', '#', '', '', 'open', array('default'), 'Manual Page', NULL, 5 );

			// Insert Markdown example post:
			$post_count--;
			$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
			$edited_Item = new Item();
			$edited_Item->set_tags_from_string( 'demo' );
			$edited_Item->insert( $owner_ID, T_('Markdown examples'), get_filler_text( 'markdown_examples_content'), $now, $cat_manual_userguide );
			$item_IDs[] = array( $edited_Item->ID, $now );
			break;

		// =======================================================================================================
		case 'group':
			$post_count = 20;
			$post_timestamp_array = get_post_timestamp_data( $post_count ) ;

			// Sample categories
			$cat_group_bugs = cat_create( T_('Bug'), NULL, $blog_ID, NULL, true, 10 );
			$cat_group_features = cat_create( T_('Feature Request'), NULL, $blog_ID, NULL, true, 20 );

			if( $edited_Blog = $BlogCache->get_by_ID( $blog_ID, false, false ) )
			{
				$edited_Blog->set_setting( 'default_cat_ID', $cat_group_bugs );
				$edited_Blog->dbupdate();
			}

			// Sample posts
			$tasks = 'ABCDEFGHIJKLMNOPQRST';
			$priorities = array( 1, 2, 3, 4, 5 );
			$task_status = array( 1, 2 ); // New, In Progress

			// Check demo users if they can be assignee
			$allowed_assignee = array();
			foreach( $demo_users as $demo_user )
			{
				if( $demo_user->check_perm( 'blog_can_be_assignee', 'edit', false, $blog_ID ) )
				{
					$allowed_assignee[] = $demo_user->ID;
				}
			}

			for( $i = 0, $j = 0, $k = 0, $m = 0; $i < 20; $i++ )
			{
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );

				$edited_Item = new Item();
				$edited_Item->set_tags_from_string( 'demo' );
				$edited_Item->set( 'priority', $priorities[$j] );

				if( $use_demo_user )
				{ // Assign task to allowed assignee
					$edited_Item->set( 'assigned_user_ID', $allowed_assignee[$m] );
				}

				// Insert item first before setting task status
				$edited_Item->insert( $owner_ID, sprintf( T_('Task %s'), $tasks[$i] ),
						'<p>'.sprintf( T_('This is a demo task description for Task %s.'), $tasks[$i] ).'</p>', $now, $cat_group_bugs );

				// Now we can set the post status
				$edited_Item->set( 'pst_ID', $task_status[$k] );
				$edited_Item->dbupdate();

				$item_IDs[] = array( $edited_Item->ID, $now );


				// Iterate through all priorities and repeat
				if( $j < ( count( $priorities ) - 1 ) )
				{
					$j++;
				}
				else
				{
					$j = 0;
				}

				// Iterate through all status and repeat
				if( $k < ( count( $task_status ) - 1 ) )
				{
					$k++;
				}
				else
				{
					$k = 0;
				}

				// Iterate through all allowed assignee, increment only if $i is odd
				if( $m < ( count( $allowed_assignee ) - 1 ) )
				{
					if( $i % 2 )
					{
						$m++;
					}
				}
				else
				{
					$m = 0;
				}
			}
			break;

		default:
			// do nothing
	}

	// Create demo comments
	$comment_users = $use_demo_user ? $demo_users : NULL;
	foreach( $item_IDs as $item_ID )
	{
		$comment_timestamp = strtotime( $item_ID[1] );
		adjust_timestamp( $comment_timestamp, 30, 720 );
		create_demo_comment( $item_ID[0], $comment_users, 'published', $comment_timestamp );
		adjust_timestamp( $comment_timestamp, 30, 720 );
		create_demo_comment( $item_ID[0], $comment_users, NULL, $comment_timestamp );
	}

	if( $install_test_features && count( $additional_comments_item_IDs ) && $use_demo_user )
	{ // Create the additional comments when we install all features
		foreach( $additional_comments_item_IDs as $additional_comments_item_ID )
		{
			// Restrict comment status by parent item:
			$comment_status = 'published';
			$Comment = new Comment();
			$Comment->set( 'item_ID', $additional_comments_item_ID );
			$Comment->set( 'status', $comment_status );
			$Comment->restrict_status( true );
			$comment_status = $Comment->get( 'status' );

			foreach( $demo_users as $demo_user )
			{ // Insert the comments from each user
				$now = date( 'Y-m-d H:i:s' );
				$DB->query( 'INSERT INTO T_comments( comment_item_ID, comment_status, comment_author_user_ID, comment_author_IP,
						comment_date, comment_last_touched_ts, comment_content, comment_renderers, comment_notif_status, comment_notif_flags )
						VALUES( '.$DB->quote( $additional_comments_item_ID ).', '.$DB->quote( $comment_status ).', '.$DB->quote( $demo_user->ID ).', "127.0.0.1", '
						.$DB->quote( $now ).', '.$DB->quote( $now ).', '.$DB->quote( T_('Hi!

This is a sample comment that has been approved by default!
Admins and moderators can very quickly approve or reject comments from the collection dashboard.') ).', "default", "finished", "moderators_notified,members_notified,community_notified" )' );
			}
		}
		echo_install_log( 'TEST FEATURE: Creating additional comments on items ('.implode( ', ', $additional_comments_item_IDs ).')' );
	}
}
