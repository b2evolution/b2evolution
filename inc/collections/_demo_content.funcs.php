<?php
/**
 * This file implements functions that creation of demo content for posts, comments, categories, etc.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
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

global $available_item_types;

load_class( 'items/model/_item.class.php', 'Item' );
load_class( 'files/model/_file.class.php', 'File' );
load_class( 'links/model/_linkuser.class.php', 'LinkUser' );
load_class( 'users/model/_group.class.php', 'Group' );
load_funcs( 'collections/model/_category.funcs.php' );
load_class( 'users/model/_organization.class.php', 'Organization' );
load_class( 'collections/model/_section.class.php', 'Section' );


/**
 * Begin install task.
 * This will offer other display methods in the future
 */
function task_begin( $title )
{
	echo get_install_format_text( $title."\n" );
	evo_flush();
}


/**
 * End install task.
 * This will offer other display methods in the future
 */
function task_end( $message = 'OK.' )
{
	echo get_install_format_text( $message."<br />\n", 'br' );
}


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


function is_available_item_type( $blog_ID, $item_type_name = '#', $item_types = array() )
{
	global $DB, $available_item_types;

	$BlogCache = & get_BlogCache();
	$ItemTypeCache = & get_ItemTypeCache();

	if( $item_type_name == '#' )
	{
		$Blog = & $BlogCache->get_by_ID( $blog_ID );
		$default_item_type = $ItemTypeCache->get_by_ID( $Blog->get_setting( 'default_post_type' ) );
		$item_type_name = $default_item_type->get_name();
	}

	if( ! isset( $available_item_types[$blog_ID] ) )
	{
		// Get available item types for the current collection
		$SQL = new SQL();
		$SQL->SELECT( 'it.ityp_name' );
		$SQL->FROM( 'T_items__type AS it' );
		$SQL->FROM_add( 'INNER JOIN T_items__type_coll ON itc_ityp_ID = ityp_ID AND itc_coll_ID = '.$blog_ID );
		$available_item_types[$blog_ID] = $DB->get_col( $SQL->get() );
	}

	return in_array( $item_type_name, $available_item_types[$blog_ID] );
}


/**
 * Display installation options
 *
 * @param array Display params
 */
function echo_installation_options( $params = array() )
{
	$params = array_merge( array(
			'enable_create_demo_users' => true,
			'show_create_organization' => true,
			'show_create_messages'     => true,
	), $params );

	$collections = array(
			'home'     => T_('Global home page'),
			'a'        => T_('Sample Blog A (Public)'),
			'b'        => T_('Sample Blog B (Private)'),
			'photos'   => T_('Photo Albums'),
			'forums'   => T_('Forums'),
			'manual'   => T_('Online Manual'),
			'group'    => T_('Tracker'),
		);

	// Allow all modules to set what collections should be installed
	$module_collections = modules_call_method( 'get_demo_collections' );
	if( ! empty( $module_collections ) )
	{
		foreach( $module_collections as $module_key => $module_colls )
		{
			foreach( $module_colls as $module_coll_key => $module_coll_title )
			{
				$collections[ $module_key.'_'.$module_coll_key ] = $module_coll_title;
			}
		}
	}

	$r = '<div class="checkbox">
				<label>
					<input type="checkbox" name="create_sample_contents" id="create_sample_contents" value="1" checked="checked" />'
					.T_('Create a demo site').'
				</label>
				<div id="create_sample_contents_options" style="margin:10px 0 0 20px">
					<div class="radio" style="margin-left:1em">
						<label>
							<input type="radio" name="demo_content_type" id="minisite_demo" value="minisite" />'
							.T_('Mini-Site').'
						</label>
					</div>
					<div class="radio" style="margin-left:1em">
						<label>
							<input type="radio" name="demo_content_type" id="complex_site_demo" value="complex_site" checked="checked" />'
							.T_('Complex Site, including:').'
						</label>
					</div>';

	// Display the collections to select which install
	foreach( $collections as $coll_index => $coll_title )
	{	// Display the checkboxes to select what demo collection to install
		$r .= '<div class="checkbox" style="margin-left:2em">
						<label>
							<input type="checkbox" name="collections[]" id="collection_'.$coll_index.'" value="'.$coll_index.'" checked="checked" />'
							.$coll_title.'
						</label>
					</div>';
	}

	$r .= '</div></div>';


	$r .= '<div class="checkbox" style="margin-top: 15px">
					<label>
						<input type="checkbox" name="create_demo_users" id="create_demo_users" value="1" checked="checked" '.( $params['enable_create_demo_users'] ? '' : 'disabled="disabled"' ).' />'
						.( $params['enable_create_demo_users'] ? T_('Create demo users') : T_('Your system already has several user accounts, so we won\'t create demo users.') ).
					'</label>
					<div id="create_demo_user_options" style="margin: 10px 0 0 20px">';

	if( $params['show_create_organization'] )
	{
		$r .= '<div class="checkbox" style="margin-left: 1em">
						<label>
							<input type="checkbox" name="create_demo_organization" id="create_demo_organization" value="1" checked="checked" />'
							.T_('Create a demo organization / team').
						'</label>
					</div>';
	}

	if( $params['show_create_messages'] )
	{
		$r .= '<div class="checkbox" style="margin-left: 1em">
						<label>
							<input type="checkbox" name="create_sample_private_messages" id="create_sameple_private_messages" value="1" checked="checked" />'
							.T_('Create demo private messages between users').
						'</label>
					</div>';
	}

	$r .= '</div></div>';

	$r .= '<script type="text/javascript">
					function toggle_create_demo_content_options()
					{
						if( jQuery( "#create_sample_contents" ).is( ":checked" ) )
						{
							jQuery( "#create_sample_contents_options" ).show();
						}
						else
						{
							jQuery( "#create_sample_contents_options" ).hide();
						}
					}

					function toggle_demo_content_type_options()
					{
						if( jQuery( "input[name=\"demo_content_type\"]:checked" ).val() == "minisite" )
						{
							jQuery( "input[name=\'collections[]\']" ).attr( "disabled", true );
						}
						else
						{
							jQuery( "input[name=\'collections[]\']" ).removeAttr( "disabled" );
						}
					}

					function toggle_create_demo_user_options()
					{
						if( jQuery( "#create_demo_users" ).is( ":checked" ) )
						{
							jQuery( "input[name=\'create_demo_organization\']" ).removeAttr( "disabled" );
							jQuery( "input[name=\'create_sample_private_messages\']" ).removeAttr( "disabled" );
						}
						else
						{
							jQuery( "input[name=\'create_demo_organization\']" ).attr( "disabled", true );
							jQuery( "input[name=\'create_sample_private_messages\']" ).attr( "disabled", true );
						}
					}

					jQuery( document ).ready( function() {
							toggle_create_demo_content_options();
							toggle_demo_content_type_options();
							toggle_create_demo_user_options();
						} );

					jQuery( "#create_sample_contents" ).click( toggle_create_demo_content_options );
					jQuery( "input[name=\"demo_content_type\"]" ).click( toggle_demo_content_type_options );
					jQuery( "#create_demo_users" ).click( toggle_create_demo_user_options );

				</script>';

	return $r;
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
		$blog_skin_name = 'Bootstrap Blog',
		$kind = 'std', // standard blog; notorious variations: "photo", "group", "forum"
		$allow_rating_items = '',
		$use_inskin_login = 0,
		$blog_access_type = '#', // '#' - to use default access type from $Settings->get( 'coll_access_type' ) - "Default URL for New Collections", possible values: baseurl, default, index.php, extrabase, extrapath, relative, subdom, absolute
		$allow_html = true,
		$in_bloglist = 'public',
		$owner_user_ID = 1,
		$blog_allow_access = 'public',
		$section_ID = NULL )
{
	global $default_locale, $install_test_features, $local_installation, $Plugins, $Blog;

	$SkinCache = & get_SkinCache();
	$blog_Skin = & $SkinCache->get_by_name( $blog_skin_name, false, false );
	if( ! $blog_Skin )
	{	// Try looking for skin using class name:
		$blog_skin_class = strtolower( $blog_skin_name );
		$blog_skin_class = trim( preg_replace( array( '/\h+/', '/_[s|S]kin$/' ), array( '_', '' ), $blog_skin_class ) ).'_Skin';
		$blog_Skin = & $SkinCache->get_by_class( $blog_skin_name, false, false );
	}

	if( ! $blog_Skin )
	{
		trigger_error( sprintf( 'Unable to find the default skin of the collection (%s).', $blog_skin_name ), E_USER_NOTICE );
		return false;
	}

	$Collection = $Blog = new Blog( NULL );

	if( $blog_access_type != '#' )
	{	// Force default new collection URL with a given param:
		$Blog->set( 'access_type', $blog_access_type );
	}

	$Blog->set( 'sec_ID', $section_ID );

	$Blog->init_by_kind( $kind, $blog_name, $blog_shortname, $blog_urlname );

	if( ( $kind == 'forum' || $kind == 'manual' ) && ( $Plugin = & $Plugins->get_by_code( 'b2evMark' ) ) !== false )
	{	// Initialize special Markdown plugin settings for Forums and Manual blogs
		$Blog->set_setting( 'plugin'.$Plugin->ID.'_coll_apply_comment_rendering', 'opt-out' );
		$Blog->set_setting( 'plugin'.$Plugin->ID.'_links', '1' );
		$Blog->set_setting( 'plugin'.$Plugin->ID.'_images', '1' );
	}
	if( $kind == 'photo' )
	{	// Display category directory on front page for photo blogs
		$Blog->set_setting( 'front_disp', 'catdir' );
	}

	$Blog->set( 'tagline', $blog_tagline );
	$Blog->set( 'longdesc', $blog_longdesc );
	$Blog->set( 'locale', $default_locale );
	$Blog->set( 'in_bloglist', $in_bloglist );
	$Blog->set( 'owner_user_ID', $owner_user_ID );
	$Blog->set( 'normal_skin_ID', $blog_Skin->ID );

	$Blog->dbinsert();

	if( $install_test_features )
	{
		$allow_rating_items = 'any';
		$Blog->set_setting( 'skin'.$blog_Skin->ID.'_bubbletip', '1' );
		echo_install_log( 'TEST FEATURE: Activating username bubble tips on skin of collection #'.$Blog->ID );
		$Blog->set_setting( 'skin'.$blog_Skin->ID.'_gender_colored', '1' );
		echo_install_log( 'TEST FEATURE: Activating gender colored usernames on skin of collection #'.$Blog->ID );
		$Blog->set_setting( 'in_skin_editing', '1' );
		echo_install_log( 'TEST FEATURE: Activating in-skin editing on collection #'.$Blog->ID );

		if( $kind == 'manual' )
		{	// Set a posts ordering by 'postcat_order ASC'
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

	if( $local_installation || $Blog->get_setting( 'allow_access' ) != 'public' )
	{	// Turn off all ping plugins if the installation is local/test/intranet or this is a not public collection:
		$Blog->set_setting( 'ping_plugins', '' );
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
	global $Messages, $DB;

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
			'org_priorities' => NULL, // array of organization priorities
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
	{	// Set country
		$User->set( 'ctry_ID', $params['ctry_ID'] );
	}
	$User->set( 'gender', $params['gender'] );
	$User->set_Group( $Group );
	//$User->set_datecreated( $params['datecreated'] );
	$User->set_datecreated( time() ); // Use current time temporarily, we'll update these later

	if( ! $User->dbinsert( false ) )
	{	// Don't continue if user creating has been failed
		return false;
	}

	// Update user_created_datetime using FROM_UNIXTIME to prevent invalid datetime values during DST spring forward - fall back
	$DB->query( 'UPDATE T_users SET user_created_datetime = FROM_UNIXTIME('.$params['datecreated'].') WHERE user_login = '.$DB->quote( $params['login'] ) );

	if( ! empty( $params['org_IDs'] ) )
	{	// Add user to organizations:
		$User->update_organizations( $params['org_IDs'], $params['org_roles'], $params['org_priorities'], true );
	}

	if( ! empty( $params['fields'] ) )
	{	// Additional user fields
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
			{	// Skip wrong field
				continue;
			}

			if( is_string( $params['fields'][ $field_name ] ) )
			{
				$params['fields'][ $field_name ] = array( $params['fields'][ $field_name ] );
			}

			foreach( $params['fields'][ $field_name ] as $field_value )
			{	// SQL record for each field value
				$user_field_records[] = '( '.$User->ID.', '.$field_ID.', '.$DB->quote( $field_value ).' )';
			}
		}
		if( count( $user_field_records ) )
		{	// Insert all user fields by single SQL query
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
	$SQL = $OrganizationCache->get_SQL_object( 'Check if our sample organization already exists' );
	$SQL->WHERE_and( 'org_name = '.$DB->quote( $org_name ) );

	$db_row = $DB->get_row( $SQL );
	if( $db_row )
	{
		$demo_org_ID = $db_row->org_ID;
		$Organization = & $OrganizationCache->get_by_ID( $demo_org_ID );
	}
	else
	{	// Sample organization does not exist, let's create one
		$Organization = new Organization();
		$Organization->set( 'owner_user_ID', $owner_ID );
		$Organization->set( 'name', $org_name );
		$Organization->set( 'url', 'http://b2evolution.net/' );
		if( $Organization->dbinsert() )
		{
			$demo_org_ID = $Organization->ID;
			$Messages->add_to_group( sprintf( T_('The sample organization %s has been created.'), $org_name ), 'success', T_('Demo contents').':' );
		}
	}

	// Add current user to the demo organization
	if( $add_current_user && $demo_org_ID && isset( $current_User ) )
	{
		// Get current user's organization data
		$org_roles = array();
		$org_priorities = array();
		$org_data = $current_User->get_organizations_data();
		if( isset( $org_data[ $demo_org_ID ] ) )
		{
			$org_roles = array( $org_data[ $demo_org_ID ]['role'] );
			$org_priorities = array( $org_data[ $demo_org_ID ]['priority'] );
		}
		$current_User->update_organizations( array( $demo_org_ID ), $org_roles, $org_priorities, true );
	}

	return $Organization;
}


/**
 * Returns list  of valid demo users
 *
 * @return array Array of demo users with default settings
 */
function get_demo_users_defaults()
{
	return array(
		'admin' => array(
				'login'     => 'admin',
				'firstname' => 'Johnny',
				'lastname'  => 'Admin',
				'level'     => 10, // NOTE: these levels define the order of display in the Organization members widget
				'gender'    => 'M',
				'group'     => 'Administrators',
				'org_IDs'   => '#',
				'org_roles' => array( 'King of Spades' ),
				'org_priorities' => array( 0 ),
				'fields'    => array(
						'Micro bio'   => 'I am the demo administrator of this site.'."\n".'I love having so much power!',
						'Website'     => 'http://b2evolution.net/',
						'Twitter'     => 'https://twitter.com/b2evolution/',
						'Facebook'    => 'https://www.facebook.com/b2evolution',
						'Linkedin'    => 'https://www.linkedin.com/company/b2evolution-net',
						'GitHub'      => 'https://github.com/b2evolution/b2evolution',
						'Google Plus' => 'https://plus.google.com/+b2evolution/posts',
					)
			),
		'mary' => array(
				'login'     => 'mary',
				'firstname' => 'Mary',
				'lastname'  => 'Wilson',
				'level'     => 4, // NOTE: these levels define the order of display in the Organization members widget
				'gender'    => 'F',
				'group'     => 'Moderators',
				'org_IDs'   => '#',
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
			),
		'jay' => array(
				'login'     => 'jay',
				'firstname' => 'Jay',
				'lastname'  => 'Parker',
				'level'     => 3, // NOTE: these levels define the order of display in the Organization members widget
				'gender'    => 'M',
				'group'     => 'Moderators',
				'org_IDs'   => '#',
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
			),
		'dave' => array(
				'login'     => 'dave',
				'firstname' => 'David',
				'lastname'  => 'Miller',
				'level'     => 2, // NOTE: these levels define the order of display in the Organization members widget
				'gender'    => 'M',
				'group'     => 'Editors',
				'org_IDs'   => '#',
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
			),
		'paul' => array(
				'login'     => 'paul',
				'firstname' => 'Paul',
				'lastname'  => 'Jones',
				'level'     => 1, // NOTE: these levels define the order of display in the Organization members widget
				'gender'    => 'M',
				'group'     => 'Editors',
				'org_IDs'   => '#',
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
			),
		'larry' => array(
				'login'     => 'larry',
				'firstname' => 'Larry',
				'lastname'  => 'Smith',
				'level'     => 0,
				'gender'    => 'M',
				'group'     => 'Normal Users',
				'fields'    => array(
						'Micro bio' => 'Hi there!',
					),
			),
		'kate' => array(
				'login'     => 'kate',
				'firstname' => 'Kate',
				'lastname'  => 'Adams',
				'level'     => 0,
				'gender'    => 'F',
				'group'     => 'Normal Users',
				'fields'    => array(
						'Micro bio' => 'Just me!',
					),
			),
		);
}


/**
 * Get all available demo users
 *
 * @param boolean Create the demo users if they do not exist
 * @param boolean Display ouput
 * @return array Array of available demo users indexed by login
 */
function get_demo_users( $create = false, $output = true )
{
	$demo_users = get_demo_users_defaults();
	$demo_users_logins = array_keys( $demo_users );

	$available_demo_users = array();
	foreach( $demo_users_logins as $demo_user_login )
	{
		$demo_User = get_demo_user( $demo_user_login, $create, $output );
		if( $demo_User )
		{
			$available_demo_users[$demo_user_login] = $demo_User;
		}
	}

	return $available_demo_users;
}


/**
 * Get demo user
 *
 * @param string User $login
 * @param boolean Create demo user if it does not exist
 * @param boolean Display output
 * @return mixed object Demo user if successful, false otherwise
 */
function get_demo_user( $login, $create = false, $output = true )
{
	global $DB;
	global $current_User;
	global $user_timestamp;

	// Get list of demo users:
	$demo_users = get_demo_users_defaults();

	if( ! isset( $demo_users[$login] ) )
	{	// Specified login not included in the list of demo users:
		return false;
	}

	$UserCache = & get_UserCache();
	// Check if demo user is already created:
	$demo_user = & $UserCache->get_by_login( $login );

	$GroupCache = & get_GroupCache();
	if( isset( $demo_users[$login]['group'] )
			&& $user_default_Group = $GroupCache->get_by_name( $demo_users[$login]['group'], false, false ) )
	{	// Get default group ID:
		$group_ID = $user_default_Group->ID;
	}
	else
	{
		$group_ID = $GroupCache->get_by_name( 'Normal Users' );
	}

	$user_org_IDs = NULL;
	if( isset( $demo_users[$login]['org_IDs'] )  )
	{
		if( $demo_users[$login]['org_IDs'] == '#' )
		{	// Get first available organization:
			if( $organization_ID = $DB->get_var( 'SELECT org_ID FROM T_users__organization ORDER BY org_ID ASC LIMIT 1' ) )
			{
				$user_org_IDs = array( $organization_ID );
			}
		}
		elseif( is_array( $demo_users[$login]['org_IDs'] ) )
		{
			$user_org_IDs = $demo_users[$login]['org_IDs'];
		}
	}

	if( ! $demo_user && $create )
	{	// Demo user does not exist yet but we can create:
		if( $login == 'admin' && $admin_user = $UserCache->get_by_ID( 1, false, false ) )
		{	// Admin user must have been renamed, skip:
			return false;
		}

		if( $output )
		{
			task_begin( sprintf( 'Creating demo user %s...', $login ) );
		}
		adjust_timestamp( $user_timestamp, 360, 1440, false );

		$user_defaults = array_merge( $demo_users[$login], array(
			'group_ID'    => $group_ID,
			'org_IDs'     => $user_org_IDs,
			'datecreated' => $user_timestamp,
		) );

		$demo_user = create_user( $user_defaults );
		if( $demo_user === false )
		{	// Cannot create demo user, exiting:
			return false;
		}

		// Try to assign profile picture to demo user:
		assign_profile_picture( $demo_user );

		if( $demo_user )
		{	// Insert default user settings:
			$DB->query( 'INSERT INTO T_users__usersettings ( uset_user_ID, uset_name, uset_value )
				VALUES ( '.$demo_user->ID.', "created_fromIPv4", '.$DB->quote( ip2int( '127.0.0.1' ) ).' ),
				       ( '.$demo_user->ID.', "user_domain", "localhost" )' );
		}
		if( $output )
		{
			task_end();
		}
	}
	elseif( $demo_user )
	{
		if( ! $demo_user->get( 'avatar_file_ID' ) )
		{	// Demo user already exists but has avatar has not been set:
			assign_profile_picture( $demo_user );
		}

		if( isset( $user_defaults['org_IDs'] ) && isset( $user_defaults['org_roles'] ) )
		{
			$org_priorities = isset( $user_defaults['org_priorities'] ) ? $user_defaults['org_priorities'] : array();
			$demo_user->update_organizations( $user_org_IDs, $user_defaults['org_roles'], $org_priorities, true );
		}
	}

	return $demo_user;
}


/**
 * Create demo private messages
 */
function create_demo_messages()
{
	global $UserSettings, $DB, $now, $localtimenow;

	load_class( 'messaging/model/_thread.class.php', 'Thread' );
	load_class( 'messaging/model/_message.class.php', 'Message' );
	load_class( 'users/model/_usersettings.class.php', 'UserSettings' );
	$UserSettings = new UserSettings();
	$UserCache = & get_UserCache();

	$users_SQL = new SQL();
	$users_SQL->SELECT( 'user_ID, user_login' );
	$users_SQL->FROM( 'T_users' );
	$users_SQL->WHERE( 'NOT user_ID  = 1' );
	$users_SQL->ORDER_BY( 'user_ID' );
	$users = $DB->get_results( $users_SQL->get() );

	for( $i = 0; $i < count( $users ); $i++ )
	{
		if( $i % 2 == 0 )
		{
			$author_ID = 1;
			$recipient_ID = $users[$i]->user_ID;
		}
		else
		{
			$author_ID = $users[$i]->user_ID;
			$recipient_ID = 1;
		}

		$author_User = & $UserCache->get_by_ID( $author_ID );
		$recipient_User = & $UserCache->get_by_ID( $recipient_ID );

		$loop_Thread = new Thread();
		$loop_Message = new Message();

		// Initial message
		$loop_Message->Thread = $loop_Thread;
		$loop_Message->Thread->set_param( 'datemodified', 'string', date( 'Y-m-d H:i:s', $localtimenow - 60 ) );
		$loop_Message->Thread->set( 'title', sprintf( T_('Demo private conversation #%s'), $i + 1 ) );
		$loop_Message->Thread->recipients_list = array( $recipient_ID );
		$loop_Message->set( 'author_user_ID', $author_ID );
		$loop_Message->creator_user_ID = $author_ID;
		$loop_Message->set( 'text', sprintf( T_('This is a demo private message to %s.'), $recipient_User->login ) );

		$DB->begin();
		$conversation_saved = false;
		if( $loop_Message->Thread->dbinsert() )
		{
			$loop_Message->set_param( 'thread_ID', 'integer', $loop_Message->Thread->ID );
			if( $loop_Message->dbinsert() )
			{
				if( $loop_Message->dbinsert_threadstatus( $loop_Message->Thread->recipients_list ) )
				{
					if( $loop_Message->dbinsert_contacts( $loop_Message->Thread->recipients_list ) )
					{
						if( $loop_Message->dbupdate_last_contact_datetime() )
						{
							$conversation_saved = true;
						}
					}
				}
			}
		}

		if( $conversation_saved )
		{
			$conversation_saved = false;

			// Reply message
			$loop_reply_Message = new Message();
			$loop_reply_Message->Thread = $loop_Thread;
			$loop_reply_Message->set( 'author_user_ID', $recipient_ID );
			$loop_reply_Message->creator_user_ID = $author_ID;
			$loop_reply_Message->set( 'text', sprintf( T_('This is a demo private reply to %s.'), $author_User->login ) );
			$loop_reply_Message->set_param( 'thread_ID', 'integer', $loop_reply_Message->Thread->ID );

			if( $loop_reply_Message->dbinsert() )
			{
				// Mark reply message as unread by initiator
				$sql = 'UPDATE T_messaging__threadstatus
						SET tsta_first_unread_msg_ID = '.$loop_reply_Message->ID.'
						WHERE tsta_thread_ID = '.$loop_reply_Message->Thread->ID.'
							AND tsta_user_ID = '.$author_ID.'
							AND tsta_first_unread_msg_ID IS NULL';
				$DB->query( $sql, 'Insert thread statuses' );

				// Mark all messages as read by recipient
				$sql = 'UPDATE T_messaging__threadstatus
						SET tsta_first_unread_msg_ID = NULL
						WHERE tsta_thread_ID = '.$loop_reply_Message->Thread->ID.'
							AND tsta_user_ID = '.$recipient_ID;
				$DB->query( $sql, 'Insert thread statuses' );

				// check if contact pairs between sender and recipients exists
				$recipient_list = $loop_reply_Message->Thread->load_recipients();
				// remove author user from recipient list
				$recipient_list = array_diff( $recipient_list, array( $loop_reply_Message->author_user_ID ) );
				// insert missing contact pairs if required
				if( $loop_reply_Message->dbinsert_contacts( $recipient_list ) )
				{
					if( $loop_reply_Message->dbupdate_last_contact_datetime() )
					{
						$DB->commit();
						$conversation_saved = true;
					}
				}
			}
		}

		if( ! $conversation_saved )
		{
			$DB->rollback();
		}
	}
}

/**
 * This is called only for fresh installs and fills the tables with
 * demo/tutorial things.
 *
 * @param array Array of user objects
 * @param boolean True to create users for the demo content
 * @return integer Number of collections created
 */
function create_demo_contents( $demo_users = array(), $use_demo_users = true, $initial_install = true )
{
	global $current_User, $DB, $Settings;

	// Global exception handler function
	function demo_content_error_handler( $errno, $errstr, $errfile, $errline )
	{	// handle only E_USER_NOTICE
		if( $errno == E_USER_NOTICE )
		{
			echo get_install_format_text( '<span class="text-warning"><evo:warning>'.$errstr.'</evo:warning></span> ' );
		}
	}

	// Set global exception handler
	set_error_handler( "demo_content_error_handler" );

	$mary_moderator_ID = isset( $demo_users['mary'] ) ? $demo_users['mary']->ID : $current_User->ID;
	$jay_moderator_ID  = isset( $demo_users['jay'] ) ? $demo_users['jay']->ID : $current_User->ID;
	$dave_blogger_ID   = isset( $demo_users['dave'] ) ? $demo_users['dave']->ID : $current_User->ID;
	$paul_blogger_ID   = isset( $demo_users['paul'] ) ? $demo_users['paul']->ID : $current_User->ID;
	$larry_user_ID     = isset( $demo_users['larry'] ) ? $demo_users['larry']->ID : $current_User->ID;
	$kate_user_ID      = isset( $demo_users['kate'] ) ? $demo_users['kate']->ID : $current_User->ID;

	load_class( 'collections/model/_blog.class.php', 'Blog' );
	load_class( 'files/model/_file.class.php', 'File' );
	load_class( 'files/model/_filetype.class.php', 'FileType' );
	load_class( 'links/model/_link.class.php', 'Link' );
	load_funcs( 'widgets/_widgets.funcs.php' );

	$create_sample_contents = param( 'create_sample_contents', 'string', '' );
	if( $create_sample_contents == 'all' )
	{	// Array contains which collections should be installed
		$install_collection_minisite = 0;
		$install_collection_home     = 1;
		$install_collection_bloga    = 1;
		$install_collection_blogb    = 1;
		$install_collection_photos   = 1;
		$install_collection_forums   = 1;
		$install_collection_manual   = 1;
		$install_collection_tracker  = 1;
		$site_skins_setting          = 1;
	}
	else
	{	// Array contains which collections should be installed
		$demo_content_type = param( 'demo_content_type', 'string', NULL );
		if( $demo_content_type == 'minisite' )
		{
			$install_collection_minisite = 1;
			$install_collection_home     = 0;
			$install_collection_bloga    = 0;
			$install_collection_blogb    = 0;
			$install_collection_photos   = 0;
			$install_collection_forums   = 0;
			$install_collection_manual   = 0;
			$install_collection_tracker  = 0;
			$site_skins_setting          = 0;
		}
		else
		{
			$collections = param( 'collections', 'array:string', array() );
			$install_collection_minisite = 0;
			$install_collection_home     = in_array( 'home', $collections );
			$install_collection_bloga    = in_array( 'a', $collections );
			$install_collection_blogb    = in_array( 'b', $collections );
			$install_collection_photos   = in_array( 'photos', $collections );
			$install_collection_forums   = in_array( 'forums', $collections );
			$install_collection_manual   = in_array( 'manual', $collections );
			$install_collection_tracker  = in_array( 'group', $collections );
			$site_skins_setting          = 1;
		}
	}

	task_begin( 'Creating default sections... ' );
	if( $demo_content_type != 'minisite' )
	{
		$SectionCache = & get_SectionCache();

		$sections = array(
				'No Section' => array( 'owner_ID' => 1, 'order' => 1 ),
				'Home'       => array( 'owner_ID' => 1, 'order' => 2 ),
				'Blogs'      => array( 'owner_ID' => $jay_moderator_ID, 'order' => 3 ),
				'Photos'     => array( 'owner_ID' => $dave_blogger_ID, 'order' => 4 ),
				'Forums'     => array( 'owner_ID' => $paul_blogger_ID, 'order' => 5 ),
				'Manual'     => array( 'owner_ID' => $dave_blogger_ID, 'order' => 6 ),
			);

		foreach( $sections as $section_name => $section_data )
		{
			if( $loop_Section = $SectionCache->get_by_name( $section_name, false, false ) )
			{
				$sections[$section_name]['ID'] = $loop_Section->ID;
			}
			else
			{
				$new_Section = new Section();
				$new_Section->set( 'name', $section_name );
				$new_Section->set( 'order', $section_data['order'] );
				$new_Section->set( 'owner_user_ID', $section_data['owner_ID'] );
				$new_Section->dbsave();

				$sections[$section_name]['ID'] = $new_Section->ID;
			}
		}
	}
	task_end();

	if( $install_collection_blogb )
	{
		global $demo_poll_ID;
		task_begin( 'Creating default polls... ' );
		$demo_poll_ID = create_demo_poll();
		task_end();
	}

	// Number of demo collections created:
	$collection_created = 0;

	// Use this var to shift the posts of the collections in time below:
	$timeshift = 0;

	// Shared widgets should have already been installed:
	// insert_shared_widgets( 'normal' );

	if( $install_collection_home )
	{	// Install Home blog
		task_begin( 'Creating Home collection...' );
		$section_ID = isset( $sections['Home']['ID'] ) ? $sections['Home']['ID'] : 1;
		if( $blog_ID = create_demo_collection( 'main', $jay_moderator_ID, $use_demo_users, $timeshift, $section_ID ) )
		{
			if( $initial_install )
			{
				if( is_callable( 'update_install_progress_bar' ) )
				{
					update_install_progress_bar();
				}
			}
			else
			{	// Insert basic widgets:
				insert_basic_widgets( $blog_ID, 'normal', false, 'main' );
				insert_basic_widgets( $blog_ID, 'mobile', false, 'main' );
				insert_basic_widgets( $blog_ID, 'tablet', false, 'main' );
			}

			$collection_created++;
			task_end();
		}
		else
		{
			task_end( '<span class="text-danger">Failed.</span>' );
		}
	}

	if( $install_collection_bloga )
	{	// Install Blog A
		$timeshift += 86400;
		task_begin( 'Creating Blog A collection...' );
		$section_ID = isset( $sections['Blogs']['ID'] ) ? $sections['Blogs']['ID'] : 1;
		if( $blog_ID = create_demo_collection( 'blog_a', $jay_moderator_ID, $use_demo_users, $timeshift, $section_ID ) )
		{
			if( $initial_install )
			{
				if( is_callable( 'update_install_progress_bar' ) )
				{
					update_install_progress_bar();
				}
			}
			else
			{	// Insert basic widgets:
				insert_basic_widgets( $blog_ID, 'normal', false, 'std' );
				insert_basic_widgets( $blog_ID, 'mobile', false, 'std' );
				insert_basic_widgets( $blog_ID, 'tablet', false, 'std' );
			}
			$collection_created++;
			task_end();
		}
		else
		{
			task_end( '<span class="text-danger">Failed.</span>' );
		}
	}

	if( $install_collection_blogb )
	{	// Install Blog B
		$timeshift += 86400;
		task_begin( 'Creating Blog B collection...' );
		$section_ID = isset( $sections['Blogs']['ID'] ) ? $sections['Blogs']['ID'] : 1;
		if( $blog_ID = create_demo_collection( 'blog_b', $paul_blogger_ID, $use_demo_users, $timeshift, $section_ID ) )
		{
			if( $initial_install )
			{
				if( is_callable( 'update_install_progress_bar' ) )
				{
					update_install_progress_bar();
				}
			}
			else
			{	// Insert basic widgets:
				insert_basic_widgets( $blog_ID, 'normal', false, 'std' );
				insert_basic_widgets( $blog_ID, 'mobile', false, 'std' );
				insert_basic_widgets( $blog_ID, 'tablet', false, 'std' );
			}
			$collection_created++;
			task_end();
		}
		else
		{
			task_end( '<span class="text-danger">Failed.</span>' );
		}
	}

	if( $install_collection_photos )
	{	// Install Photos blog
		$timeshift += 86400;
		task_begin( 'Creating Photos collection...' );
		$section_ID = isset( $sections['Photos']['ID'] ) ? $sections['Photos']['ID'] : 1;
		if( $blog_ID = create_demo_collection( 'photo', $dave_blogger_ID, $use_demo_users, $timeshift, $section_ID ) )
		{
			if( $initial_install )
			{
				if( is_callable( 'update_install_progress_bar' ) )
				{
					update_install_progress_bar();
				}
			}
			else
			{	// Insert basic widgets:
				insert_basic_widgets( $blog_ID, 'normal', false, 'photo' );
				insert_basic_widgets( $blog_ID, 'mobile', false, 'photo' );
				insert_basic_widgets( $blog_ID, 'tablet', false, 'photo' );
			}
			$collection_created++;
			task_end();
		}
		else
		{
			task_end( '<span class="text-danger">Failed.</span>' );
		}
	}

	if( $install_collection_forums )
	{	// Install Forums blog
		$timeshift += 86400;
		task_begin( 'Creating Forums collection...' );
		$section_ID = isset( $sections['Forums']['ID'] ) ? $sections['Forums']['ID'] : 1;
		if( $blog_ID = create_demo_collection( 'forum', $paul_blogger_ID, $use_demo_users, $timeshift, $section_ID ) )
		{
			if( $initial_install )
			{
				if( is_callable( 'update_install_progress_bar' ) )
				{
					update_install_progress_bar();
				}
			}
			else
			{	// Insert basic widgets:
				insert_basic_widgets( $blog_ID, 'normal', false, 'forum' );
				insert_basic_widgets( $blog_ID, 'mobile', false, 'forum' );
				insert_basic_widgets( $blog_ID, 'tablet', false, 'forum' );
			}
			$collection_created++;
			task_end();
		}
		else
		{
			task_end( '<span class="text-danger">Failed.</span>' );
		}
	}

	if( $install_collection_manual )
	{	// Install Manual blog
		$timeshift += 86400;
		task_begin( 'Creating Manual collection...' );
		$section_ID = isset( $sections['Manual']['ID'] ) ? $sections['Manual']['ID'] : 1;
		if( $blog_ID = create_demo_collection( 'manual', $dave_blogger_ID, $use_demo_users, $timeshift, $section_ID ) )
		{
			if( $initial_install )
			{
				if( is_callable( 'update_install_progress_bar' ) )
				{
					update_install_progress_bar();
				}
			}
			else
			{	// Insert basic widgets:
				insert_basic_widgets( $blog_ID, 'normal', false, 'manual' );
				insert_basic_widgets( $blog_ID, 'mobile', false, 'manual' );
				insert_basic_widgets( $blog_ID, 'tablet', false, 'manual' );
			}
			$collection_created++;
			task_end();
		}
		else
		{
			task_end( '<span class="text-danger">Failed.</span>' );
		}
	}

	if( $install_collection_tracker )
	{	// Install Tracker blog
		$timeshift += 86400;
		task_begin( 'Creating Tracker collection...' );
		$section_ID = isset( $sections['Forums']['ID'] ) ? $sections['Forums']['ID'] : 1;
		if( $blog_ID = create_demo_collection( 'group', $jay_moderator_ID, $use_demo_users, $timeshift, $section_ID ) )
		{
			if( $initial_install )
			{
				if( is_callable( 'update_install_progress_bar' ) )
				{
					update_install_progress_bar();
				}
			}
			else
			{	// Insert basic widgets:
				insert_basic_widgets( $blog_ID, 'normal', false, 'group' );
				insert_basic_widgets( $blog_ID, 'mobile', false, 'group' );
				insert_basic_widgets( $blog_ID, 'tablet', false, 'group' );
			}
			$collection_created++;
			task_end();
		}
		else
		{
			task_end( '<span class="text-danger">Failed.</span>' );
		}
	}

	if( $install_collection_minisite )
	{	// Install Mini-site collection
		$timeshift += 86400;
		task_begin( 'Creating Mini-Site collection...' );
		if( $blog_ID = create_demo_collection( 'minisite', $jay_moderator_ID, $use_demo_users, $timeshift, 1 ) )
		{
			if( $initial_install )
			{
				if( is_callable( 'update_install_progress_bar' ) )
				{
					update_install_progress_bar();
				}
			}
			else
			{
				// Insert basic widgets:
				insert_basic_widgets( $blog_ID, 'normal', false, 'minisite' );
				insert_basic_widgets( $blog_ID, 'mobile', false, 'minisite' );
				insert_basic_widgets( $blog_ID, 'tablet', false, 'minisite' );
			}
			$collection_created++;
			task_end();
		}
		else
		{
			task_end( '<span class="text-danger">Failed.</span>' );
		}
	}

	// Setting default login and default messaging collection:
	task_begin( 'Setting default login and default messaging collection...' );
	if( $demo_content_type == 'minisite' )
	{
		$Settings->set( 'login_blog_ID', 0 );
		$Settings->set( 'msg_blog_ID', 0 );
		$Settings->set( 'info_blog_ID', 0 );
		$Settings->dbupdate();
	}
	else
	{
		$BlogCache = & get_BlogCache();
		//$BlogCache->load_where( 'blog_type = "main" )' );
		if( $first_Blog = & $BlogCache->get_first() )
		{	// Set first blog as default login and default messaging collection
			$Settings->set( 'login_blog_ID', $first_Blog->ID );
			$Settings->set( 'msg_blog_ID', $first_Blog->ID );
			$Settings->set( 'info_blog_ID', $first_Blog->ID );
			$Settings->dbupdate();
		}
	}
	if( $initial_install )
	{
		if( is_callable( 'update_install_progress_bar' ) )
		{
			update_install_progress_bar();
		}
	}
	task_end();

	task_begin( 'Set setting for site skins...' );
	$Settings->set( 'site_skins_enabled', $site_skins_setting );
	$Settings->dbupdate();
	task_end();

	if( $initial_install )
	{
		if( $install_test_features )
		{
			echo_install_log( 'TEST FEATURE: Creating fake hit statistics' );
			task_begin( 'Creating fake hit statistics... ' );
			load_funcs('sessions/model/_hitlog.funcs.php');
			load_funcs('_core/_url.funcs.php');
			$insert_data_count = generate_hit_stat(10, 0, 5000);
			echo sprintf( '%d test hits are added.', $insert_data_count );
			task_end();
		}

		/*
		// Note: we don't really need this any longer, but we might use it for a better default setup later...
		echo 'Creating default user/blog permissions... ';
		// Admin for blog A:
		$query = "INSERT INTO T_coll_user_perms( bloguser_blog_ID, bloguser_user_ID, bloguser_ismember,
								bloguser_perm_poststatuses, bloguser_perm_delpost, bloguser_perm_comments,
								bloguser_perm_meta_comment, bloguser_perm_cats, bloguser_perm_properties,
								bloguser_perm_media_upload, bloguser_perm_media_browse, bloguser_perm_media_change )
							VALUES
								( $blog_a_ID, ".$User_Demo->ID.", 1,
								'published,deprecated,protected,private,draft', 1, 1, 1, 0, 0, 1, 1, 1 )";
		$DB->query( $query );
		echo "OK.<br />\n";
		*/

		// Allow all modules to create their own demo contents:
		modules_call_method( 'create_demo_contents' );

		// Set default locations for each post in test mode installation
		create_default_posts_location();

		//install_basic_widgets( $new_db_version );

		load_funcs( 'tools/model/_system.funcs.php' );
		system_init_caches( true, true ); // Outputs messages
	}

	restore_error_handler();

	return $collection_created;
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
	{	// draft
		$content = T_('Hi!

This is a sample comment that has **not** been approved by default!
Admins and moderators can very quickly approve or reject comments from the collection dashboard.');
	}

	if( is_null( $comment_timestamp ) )
	{
		$comment_timestamp = time();
	}

	// We are using FROM_UNIXTIME to prevent invalid datetime during DST spring forward - fall back
	$DB->query( 'INSERT INTO T_comments( comment_item_ID, comment_status,
			comment_author_user_ID, comment_author, comment_author_email, comment_author_url, comment_author_IP,
			comment_date, comment_last_touched_ts, comment_content, comment_renderers, comment_notif_status, comment_notif_flags )
			VALUES( '.$DB->quote( $item_ID ).', '.$DB->quote( $status ).', '
			.$DB->quote( $user_ID ).', '.$DB->quote( $author ).', '.$DB->quote( $author_email ).', '.$DB->quote( $author_email_url ).', "127.0.0.1", '
			.'FROM_UNIXTIME('.$comment_timestamp.'), FROM_UNIXTIME('.$comment_timestamp.'), '.$DB->quote( $content ).', "default", "finished", "moderators_notified,members_notified,community_notified" )' );
}


/**
 * Creates a demo collection
 *
 * @param string Collection type
 * @param integer Owner ID
 * @param boolean Use demo users as comment authors
 * @param integer Shift post time in ms
 * @param integer Section ID
 * @return integer ID of created blog
 */
function create_demo_collection( $collection_type, $owner_ID, $use_demo_user = true, $timeshift = 86400, $section_ID = 1 )
{
	global $install_test_features, $DB, $admin_url, $timestamp;
	global $blog_minisite_ID, $blog_home_ID, $blog_a_ID, $blog_b_ID, $blog_photoblog_ID, $blog_forums_ID, $blog_manual_ID, $events_blog_ID;

	$default_blog_longdesc = T_('This is the long description for the collection named \'%s\'. %s');
	$default_blog_access_type = 'relative';

	$timestamp = time();
	$blog_ID = NULL;

	switch( $collection_type )
	{
		// =======================================================================================================
		case 'minisite':
			$blog_shortname = T_('Mini-Site');
			$blog_more_longdesc = '<br />
<br />
<strong>'.T_('The main purpose for this blog is to be included as a side item to other blogs where it will display your favorite/related links.').'</strong>';

			$blog_minisite_ID = create_blog(
					T_('Mini-Site Title'),
					$blog_shortname,
					'minisite',
					T_('Change this as you like'),
					sprintf( $default_blog_longdesc, $blog_shortname, $blog_more_longdesc ),
					'Jared Skin',
					'minisite',
					'any',
					1,
					'default',
					true,
					'never',
					$owner_ID,
					'public',
					$section_ID );

			if( $blog_minisite_ID )
			{
				$blog_ID = $blog_minisite_ID;

				$BlogCache = & get_BlogCache();
				if( $minisite_Blog = $BlogCache->get_by_ID( $blog_minisite_ID, false, false ) )
				{
					$blog_skin_ID = $minisite_Blog->get_skin_ID();
					if( ! empty( $blog_skin_ID ) )
					{
						$SkinCache = & get_SkinCache();
						$Skin = & $SkinCache->get_by_ID( $blog_skin_ID );
						$Skin->set_setting( 'section_2_image_file_ID', NULL );
						$Skin->set_setting( 'section_3_display', 1 );
						$Skin->set_setting( 'section_3_title_color', '#FFFFFF' );
						$Skin->set_setting( 'section_3_text_color', '#FFFFFF' );
						$Skin->set_setting( 'section_3_link_color', '#FFFFFF' );
						$Skin->set_setting( 'section_3_link_h_color', '#FFFFFF' );
						$Skin->set_setting( 'section_4_image_file_ID', NULL );
						$Skin->dbupdate_settings();
					}
				}
			}

			break;

		// =======================================================================================================
		case 'main':
			$blog_shortname = T_('Home');
			$blog_home_access_type = ( $install_test_features ) ? 'default' : $default_blog_access_type;
			$blog_more_longdesc = '';

			$blog_home_ID = create_blog(
					T_('Homepage Title'),
					$blog_shortname,
					'home',
					T_('Change this as you like'),
					sprintf( $default_blog_longdesc, $blog_shortname, $blog_more_longdesc ),
					'Bootstrap Main',
					'main',
					'any',
					1,
					'default',
					true,
					'never',
					$owner_ID,
					'public',
					$section_ID );

			if( $blog_home_ID )
			{
				if( ! $DB->get_var( 'SELECT set_value FROM T_settings WHERE set_name = '.$DB->quote( 'info_blog_ID' ) ) && ! empty( $blog_home_ID ) )
				{	// Save ID of this blog in settings table, It is used on top menu, file "/skins_site/_site_body_header.inc.php"
					$DB->query( 'REPLACE INTO T_settings ( set_name, set_value )
							VALUES ( '.$DB->quote( 'info_blog_ID' ).', '.$DB->quote( $blog_home_ID ).' )' );
				}
				$blog_ID = $blog_home_ID;
			}
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
			$blog_stub = 'a';
			$blog_a_ID = create_blog(
					T_('Public Blog'),
					$blog_shortname,
					$blog_stub,
					T_('This blog is completely public...'),
					sprintf( $default_blog_longdesc, $blog_shortname, '' ),
					'Bootstrap Blog',
					'std',
					'any',
					1,
					'#',
					true,
					'public',
					$owner_ID,
					'public',
					$section_ID );
			if( $blog_a_ID )
			{
				$blog_ID = $blog_a_ID;
			}
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
			$blog_stub = 'b';

			$blog_b_ID = create_blog(
					T_('Members-Only Blog'),
					$blog_shortname,
					$blog_stub,
					T_('This blog has restricted access...'),
					sprintf( $default_blog_longdesc, $blog_shortname, '' ),
					'Bootstrap Blog',
					'std',
					'',
					0,
					'#',
					true,
					'public',
					$owner_ID,
					'members',
					$section_ID );

			if( $blog_b_ID )
			{
				$BlogCache = & get_BlogCache();
				if( $b_Blog = $BlogCache->get_by_ID( $blog_b_ID, false, false ) )
				{
					$b_Blog->set_setting( 'front_disp', 'front' );
					$b_Blog->set_setting( 'skin2_layout', 'single_column' );
					$b_Blog->set( 'advanced_perms', 1 );
					$b_Blog->dbupdate();
				}
				$blog_ID = $blog_b_ID;
			}
			break;

		// =======================================================================================================
		case 'photo':
			$blog_shortname = 'Photos';
			$blog_stub = 'photos';
			$blog_more_longdesc = '';

			$blog_photoblog_ID = create_blog(
					'Photos',
					$blog_shortname,
					$blog_stub,
					T_('This blog shows photos...'),
					sprintf( $default_blog_longdesc, $blog_shortname, $blog_more_longdesc ),
					'Bootstrap Gallery Skin',
					'photo', '', 0, '#', true, 'public',
					$owner_ID,
					'public',
					$section_ID );
			if( $blog_photoblog_ID )
			{
				$blog_ID = $blog_photoblog_ID;
			}
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
					'Bootstrap Forums',
					'forum', 'any', 1, '#', false, 'public',
					$owner_ID,
					'public',
					$section_ID );
			if( $blog_forums_ID )
			{
				$blog_ID = $blog_forums_ID;
			}
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
					'Bootstrap Manual',
					'manual', 'any', 1, '#', false, 'public',
					$owner_ID,
					'public',
					$section_ID );
			if( $blog_manual_ID )
			{
				$blog_ID = $blog_manual_ID;
			}
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
					'Bootstrap Forums',
					'group', 'any', 1, '#', false, 'public',
					$owner_ID,
					'public',
					$section_ID );
			if( $blog_group_ID )
			{
				$blog_ID = $blog_group_ID;
			}
			break;

		default:
			// do nothing
	}

	if( ! empty( $blog_ID ) )
	{
		// Create sample contents for the collection:
		create_sample_content( $collection_type, $blog_ID, $owner_ID, $use_demo_user, $timeshift );
		return $blog_ID;
	}
	else
	{
		return false;
	}
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
	global $DB, $install_test_features, $timestamp, $Settings, $admin_url, $installed_collection_info_pages;

	if( ! isset( $installed_collection_info_pages ) )
	{	// Array for item IDs which should be used in default shared widget containers "Main Navigation" and "Navigation Hamburger":
		$installed_collection_info_pages = array();
	}

	$timestamp = time();
	$item_IDs = array();
	$additional_comments_item_IDs = array();
	$demo_users = get_demo_users( false );

	$BlogCache = & get_BlogCache();

	switch( $collection_type )
	{
		// =======================================================================================================
		case 'minisite':
			$post_count = 2;
			$post_timestamp_array = get_post_timestamp_data( $post_count ) ;

			// Sample categories:
			$cat_minisite_b2evo = cat_create( 'b2evolution', 'NULL', $blog_ID, NULL, true );
			$cat_minisite_contrib = cat_create( T_('Contributors'), 'NULL', $blog_ID, NULL, true );

			if( $edited_Blog = $BlogCache->get_by_ID( $blog_ID, false, false ) )
			{
				$edited_Blog->set_setting( 'default_cat_ID', $cat_minisite_b2evo );
				$edited_Blog->dbupdate();
			}

			// Sample content:
			if( is_available_item_type( $blog_ID, 'Widget Page' ) )
			{
				// Insert a PAGE:
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$installed_collection_info_pages['widget_page'] = $edited_Item->insert( $owner_ID, T_('More info'), '', $now, $cat_minisite_b2evo,
						array(), 'published', '#', '', '', 'open', array('default'), 'Widget Page' );
				$edit_File = new File( 'shared', 0, 'monument-valley/monuments.jpg' );
				$LinkOwner = new LinkItem( $edited_Item );
				$edit_File->link_to_Object( $LinkOwner, 1, 'cover' );
				$item_IDs[] = array( $edited_Item->ID, $now );
			}

			if( is_available_item_type( $blog_ID, 'Standalone Page' ) )
			{
				// Insert a PAGE:
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$edited_Item->insert( $owner_ID, T_('About Minisite'), sprintf( get_filler_text( 'info_page' ), T_('Mini-Site') ), $now, $cat_minisite_b2evo,
						array(), 'published', '#', '', '', 'open', array('default'), 'Standalone Page' );
				$edit_File = new File( 'shared', 0, 'monument-valley/monument-valley.jpg' );
				$LinkOwner = new LinkItem( $edited_Item );
				$edit_File->link_to_Object( $LinkOwner, 1, 'cover' );
				$item_IDs[] = array( $edited_Item->ID, $now );
			}
			break;

		// =======================================================================================================
		case 'main':
			$post_count = 18;
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
			if( is_available_item_type( $blog_ID, 'Advertisement' ) )
			{
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
			}

			if( is_available_item_type( $blog_ID, 'Sidebar link' ) )
			{
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
			}

			if( is_available_item_type( $blog_ID, 'Standalone Page' ) )
			{
				// Insert a PAGE:
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$edited_Item->set_tags_from_string( 'photo' );
				$installed_collection_info_pages[] = $edited_Item->insert( $owner_ID, T_('About this site'), T_('<p>This blog platform is powered by b2evolution.</p>

<p>You are currently looking at an info page about this site.</p>

<p>Info pages are very much like regular posts, except that they do not appear in the regular flow of posts. They appear as info pages in the menu instead.</p>

<p>If needed, skins can format info pages differently from regular posts.</p>'), $now, $cat_home_b2evo,
						array( $cat_home_b2evo ), 'published', '#', '', '', 'open', array('default'), 'Standalone Page' );
				$edit_File = new File( 'shared', 1, 'logos/b2evolution_1016x208_wbg.png' );
				$LinkOwner = new LinkItem( $edited_Item );
				$edit_File->link_to_Object( $LinkOwner );
			}

			if( is_available_item_type( $blog_ID, 'Widget Page' ) )
			{
				// Insert a WIDGET PAGE:
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$edited_Item->set_tags_from_string( 'demo' );
				$installed_collection_info_pages['widget_page'] = $edited_Item->insert( $owner_ID, T_('Widget Page'), '', $now, $cat_home_b2evo,
						array( $cat_home_b2evo ), 'published', '#', '', '', 'open', array( 'default' ), 'Widget Page' );
			}

			if( is_available_item_type( $blog_ID, 'Intro-Front' ) )
			{
				// Insert a post:
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$edited_Item->set_tags_from_string( 'intro' );
				$edited_Item->insert( $owner_ID, T_('Homepage post'), T_('<p>This is the Home page of this site.</p>

<p>More specifically it is the "Front page" of the first collection of this site. This first collection is called "Home". Other sample collections have been created. You can access them by clicking "Blog A", "Blog B", "Photos", etc. in the menu bar at the top of this page.</p>

<p>You can add collections at will. You can also remove them (including this "Home" collection) if you don\'t need one.</p>'),
						$now, $cat_home_b2evo, array(), 'published', '#', '', '', 'open', array( 'default' ), 'Intro-Front' );
			}

			if( is_available_item_type( $blog_ID, 'Terms & Conditions' ) )
			{
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
			}

			if( is_available_item_type( $blog_ID, 'Content Block' ) )
			{
				// Insert a post:
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$edited_Item->set_tags_from_string( 'demo' );
				$edited_Item->insert( $owner_ID, T_('This is a Content Block'), T_('<p>This is a Post/Item of type "Content Block".</p>

<p>A content block can be included in several places.</p>'),
						$now, $cat_home_b2evo, array(), 'published', '#', '', '', 'open', array( 'default' ), 'Content Block' );

				// Insert a post:
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$edited_Item->set_tags_from_string( 'demo' );
				$edited_Item->insert( $owner_ID, T_('Login Required'), '<p class="center">'.T_( 'You need to log in before you can access this section.' ).'</p>',
						$now, $cat_home_b2evo, array(), 'published', '#', 'login-required', '', 'open', array( 'default' ), 'Content Block' );

				// Insert a post:
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$edited_Item->set_tags_from_string( 'demo' );
				$edited_Item->insert( $owner_ID, T_('Access Denied'), '<p class="center">'.T_( 'You are not a member of this collection, therefore you are not allowed to access it.' ).'</p>',
						$now, $cat_home_b2evo, array(), 'published', '#', 'access-denied', '', 'open', array( 'default' ), 'Content Block' );

				// Insert a post:
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$edited_Item->set_tags_from_string( 'demo' );
				$edited_Item->insert( $owner_ID, T_('Help content'), '### '.T_('Email preferences')
					."\n\n"
					.sprintf( T_('You can see and change all your email subscriptions and notifications coming from this site by clicking <a %s>here</a>'), 'href="'.$edited_Blog->get( 'subsurl' ).'"' )
					."\n\n"
					.'### '.T_('Managing your personal information')
					."\n\n"
					.sprintf( T_('You can see and correct the personal details we know about you by clicking <a %s>here</a>'), 'href="'.$edited_Blog->get( 'profileurl' ).'"' )
					."\n\n"
					.'### '.T_('Closing your account')
					."\n\n"
					.sprintf( T_('You can close your account yourself by clicking <a %s>here</a>'), 'href="'.$edited_Blog->get( 'closeaccounturl' ).'"' ),
						$now, $cat_home_b2evo, array(), 'published', '#', 'help-content', '', 'open', array( 'default' ), 'Content Block' );

				// Insert a post:
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$edited_Item->set_tags_from_string( 'demo' );
				$edited_Item->insert( $owner_ID, T_('Register content'), T_('The information you provide in this form will be recorded in your user account.')
					."\n\n"
					.T_('You will be able to modify it (or even close your account) at any time after logging in with your username and password.')
					."\n\n"
					.T_('Should you forget your password, you will be able to reset it by receiving a link on your email address.')
					."\n\n"
					.T_('All other info is used to personalize your experience with this website.')
					."\n\n"
					.T_('This site may allow conversation between users.')
					.' '.T_('Your email address and password will not be shared with other users.')
					.' '.T_('All other information may be shared with other users.')
					.' '.T_('Do not provide information you are not willing to share.'),
						$now, $cat_home_b2evo, array(), 'published', '#', 'register-content', '', 'open', array( 'default' ), 'Content Block' );
			}
			break;

		// =======================================================================================================
		case 'std':
		case 'blog_a':
			$post_count = 14;
			$post_timestamp_array = get_post_timestamp_data( $post_count ) ;

			// Sample categories
			$cat_ann_a = cat_create( T_('Welcome'), 'NULL', $blog_ID, NULL, true );
			$cat_news = cat_create( T_('News'), 'NULL', $blog_ID, NULL, true );
			$cat_bg = cat_create( T_('Background'), 'NULL', $blog_ID, NULL, true );
			$cat_fun = cat_create( T_('Fun'), 'NULL', $blog_ID, NULL, true );
				$cat_life = cat_create( T_('In real life'), $cat_fun, $blog_ID, NULL, true );
					$cat_recipes = cat_create( T_('Recipes'), $cat_life, $blog_ID, NULL, true, NULL, NULL, false, 'Recipe' );
					$cat_movies = cat_create( T_('Movies'), $cat_life, $blog_ID, NULL, true );
					$cat_music = cat_create( T_('Music'), $cat_life, $blog_ID, NULL, true );
				$cat_web = cat_create( T_('On the web'), $cat_fun, $blog_ID, NULL, true );

			if( $edited_Blog = $BlogCache->get_by_ID( $blog_ID, false, false ) )
			{
				$edited_Blog->set_setting( 'default_cat_ID', $cat_ann_a );
				$edited_Blog->dbupdate();
			}

			// Sample posts
			if( is_available_item_type( $blog_ID, 'Intro-Main' ) )
			{
				// Insert a post:
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$edited_Item->set_tags_from_string( 'intro' );
				$edited_Item->insert( $owner_ID, T_('Main Intro post'), T_('This is the main intro post. It appears on the homepage only.'),
					$now, $cat_ann_a, array(), 'published', '#', '', '', 'open', array('default'), 'Intro-Main' );
				$item_IDs[] = array( $edited_Item->ID, $now );
			}

			if( is_available_item_type( $blog_ID, '#' ) )
			{
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
			}

			if( is_available_item_type( $blog_ID, 'Standalone Page' ) )
			{
				// Insert a PAGE:
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$edited_Item->insert( $owner_ID, T_('About Blog A'), sprintf( get_filler_text( 'info_page' ), T_('Blog A') ), $now, $cat_ann_a,
						array(), 'published', '#', '', '', 'open', array('default'), 'Standalone Page' );
				$item_IDs[] = array( $edited_Item->ID, $now );
			}

			if( is_available_item_type( $blog_ID, '#' ) )
			{
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
			}

			if( is_available_item_type( $blog_ID, 'Post with Custom Fields' ) )
			{
				// Insert a post:
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$edited_Item->set_tags_from_string( 'demo' );
				$edited_Item->set_custom_field( 'first_numeric_field', '123' );
				$edited_Item->set_custom_field( 'second_numeric_field', '456' );
				$edited_Item->set_custom_field( 'usd_price', '29.99' );
				$edited_Item->set_custom_field( 'eur_price', '24.79' );
				$edited_Item->set_custom_field( 'first_string_field', 'abc' );
				$edited_Item->set_custom_field( 'multiline_plain_text_field', 'This is a sample text field.
It can have multiple lines.' );
				$edited_Item->set_custom_field( 'multiline_html_field', 'This is an <b>HTML</b> <i>field</i>.' );
				$edited_Item->set_custom_field( 'url_field', 'http://b2evolution.net/' );
				$edited_Item->set_custom_field( 'checkmark_field', '1' );
				$post_custom_fields_ID = $edited_Item->insert( $owner_ID, T_('Custom Fields Example'),
'<p>'.T_('This post has a special post type called "Post with Custom Fields".').'</p>'.

'<p>'.T_('This post type defines 4 custom fields. Here are the sample values that have been entered in these fields:').'</p>'.

'<p>[fields]</p>'.

'[teaserbreak]'.

'<p>'.T_('It is also possible to selectively display only a couple of these fields:').'</p>'.

'<p>[fields:first_numeric_field,first_string_field,second_numeric_field]</p>'.

'<p>'.sprintf( T_('Finally, we can also display just the value of a specific field, like this: %s.'), '[field:first_string_field]' ).'</p>'.

'<p>'.sprintf( T_('It is also possible to create links using a custom field URL: %s'), '[link:url_field:.btn.btn-info]Click me![/link]' ).'</p>',
						$now, $cat_bg, array(), 'published', '#', '', '', 'open', array('default'), 'Post with Custom Fields' );
				$edit_File = new File( 'shared', 0, 'monument-valley/monument-valley.jpg' );
				$LinkOwner = new LinkItem( $edited_Item );
				$custom_item_link_ID = $edit_File->link_to_Object( $LinkOwner, 1, 'attachment' );
				$edited_Item->set_custom_field( 'image_1', $custom_item_link_ID );
				$edited_Item->dbupdate();
				$item_IDs[] = array( $edited_Item->ID, $now );

				// Insert a post:
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$edited_Item->set_tags_from_string( 'demo' );
				$edited_Item->set_custom_field( 'first_numeric_field', '123.45' );
				$edited_Item->set_custom_field( 'second_numeric_field', '456' );
				$edited_Item->set_custom_field( 'usd_price', '17.50' );
				$edited_Item->set_custom_field( 'eur_price', '14.95' );
				$edited_Item->set_custom_field( 'first_string_field', 'abcdef' );
				$edited_Item->set_custom_field( 'multiline_plain_text_field', 'This is a sample text field.
It can have multiple lines.
This is an extra line.' );
				$edited_Item->set_custom_field( 'multiline_html_field', 'This is an <b>HTML</b> <i>field</i>.' );
				$edited_Item->set_custom_field( 'url_field', 'http://b2evolution.net/' );
				$edited_Item->set_custom_field( 'checkmark_field', '0' );
				$another_custom_fields_example_ID = $edited_Item->insert( $owner_ID, T_('Another Custom Fields Example'),
'<p>'.T_('This post has a special post type called "Post with Custom Fields".').'</p>'.

'<p>'.T_('This post type defines 4 custom fields. Here are the sample values that have been entered in these fields:').'</p>'.

'<p>[fields]</p>'.

'[teaserbreak]'.

'<p>'.T_('It is also possible to selectively display only a couple of these fields:').'</p>'.

'<p>[fields:first_numeric_field,first_string_field,second_numeric_field]</p>'.

'<p>'.sprintf( T_('Finally, we can also display just the value of a specific field, like this: %s.'), '[field:first_string_field]' ).'</p>'.

'<p>'.sprintf( T_('It is also possible to create links using a custom field URL: %s'), '[link:url_field:.btn.btn-info]Click me![/link]' ).'</p>',
						$now, $cat_bg, array(), 'published', '#', '', '', 'open', array('default'), 'Post with Custom Fields' );
				$edit_File = new File( 'shared', 0, 'monument-valley/monument-valley-road.jpg' );
				$LinkOwner = new LinkItem( $edited_Item );
				$another_item_link_ID = $edit_File->link_to_Object( $LinkOwner, 1, 'attachment' );
				$edited_Item->set_custom_field( 'image_1', $another_item_link_ID );
				$edited_Item->dbupdate();
				$item_IDs[] = array( $edited_Item->ID, $now );

				if( is_available_item_type( $blog_ID, 'Child Post' ) )
				{
					// Insert a post:
					$post_count--;
					$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
					$edited_Item = new Item();
					$edited_Item->set_tags_from_string( 'demo' );
					$edited_Item->set_custom_field( 'first_numeric_field', '123' );
					$edited_Item->set_custom_field( 'first_string_field', 'abc' );
					$edited_Item->set_custom_field( 'image_1', $custom_item_link_ID );
					$edited_Item->set_custom_field( 'checkmark_field', '1' );
					$edited_Item->set( 'parent_ID', $post_custom_fields_ID ); // Set parent post ID
					/*$edited_Item->insert( $owner_ID, T_('Child Post Example'), T_('<p>This post has a special post type called "Child Post".</p>'),*/
					$edited_Item->insert( $owner_ID, T_('Child Post Example'),
'<p>'.sprintf( T_('This post has a special post type called "Child Post". This allowed to specify a parent post ID. Consequently, this child post is linked to: %s.'), '[parent:titlelink] ([parent:url])' ).'</p>'.

'<p>'.T_('This also allows us to access the custom fields of the parent post:').'</p>'.

'<p>[parent:fields]</p>'.

'[teaserbreak]'.

'<p>'.T_('It is also possible to selectively display only a couple of these fields:').'</p>'.

'<p>[parent:fields:first_numeric_field,first_string_field,second_numeric_field]</p>'.

'<p>'.sprintf( T_('Finally, we can also display just the value of a specific field, like this: %s.'), '[parent:field:first_string_field]' ).'</p>'.

'<p>'.sprintf( T_('We can also reference fields of any other post like this: %s or like this: %s.'), '[item:another-custom-fields-example:field:first_string_field]', '[item:'.$another_custom_fields_example_ID.':field:first_string_field]' ).'</p>'.

'<p>'.sprintf( T_('It is also possible to create links using a custom field URL from the parent post: %s'), '[parent:link:url_field:.btn.btn-info]Click me![/link]' ).'</p>'.

'<h3>'.T_('Replicated fields').'</h3>'.

'<p>'.T_('By using the same field names, it is also possible to automatically replicate some fields from parent to child (recursively).').'</p>'.

'<p>'.T_('This child post has the following fields which automatically replicate from its parent:').'</p>'.

'<p>[fields]</p>'.

'<p>'.sprintf( T_('Another way to show this, is to use b2evolution\'s %s short tag:'), '`[compare:...]`' ).'</p>'.

'<p>[compare:$this$,$parent$]</p>',
							$now, $cat_bg, array(), 'published', '#', '', '', 'open', array('default'), 'Child Post' );
					$item_IDs[] = array( $edited_Item->ID, $now );
				}
			}

			if( is_available_item_type( $blog_ID, 'Recipe' ) )
			{
				// Insert a post:
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$edited_Item->set_tags_from_string( 'demo,photo' );
				$edited_Item->set_setting( 'custom:course', TD_('Main Course') );
				$edited_Item->set_setting( 'custom:cuisine', TD_('South African') );
				$edited_Item->set_setting( 'custom:servings', '2' );
				$edited_Item->set_setting( 'custom:prep_time', '1' );
				$edited_Item->set_setting( 'custom:cook_time', '20' );
				$edited_Item->set_setting( 'custom:passive_time', '3' );
				$edited_Item->set_setting( 'custom:ingredients', TD_('1 jar Peppedew Peppers (or piquante pepper)
4oz goat cheese (any flavor)
1 tbsp mayonnaise
1 tbsp sour cream
1 bunch of chives, chopped
hearty shot of hot sauce (Franks, Yellowbird)
hearty crack of pepper') );
				$mongolian_beef_ID = $edited_Item->insert( $owner_ID, TD_('Stuffed Peppers'),
'<p>'.TD_('We found these during Happy Hour at Chiso’s Grill in Bee Cave, Tx. We’ve since tweaked the recipe a bit. This recipe is just a starting point, add/remove anything you want (like more hot sauce if you’re into that).').'</p>'.
'[teaserbreak]'.
'<ol>'.
	'<li>'.TD_('combine goat cheese, mayo, sour cream, 2/3rds of your chives, hot sauce, black pepper').'</li>'.
	'<li>'.TD_('if you are feeling spry, beat the mixture to make it fluffy').'</li>'.
	'<li>'.TD_('put filling in a plastic bag, snip of the tip with scissors to make a piping bag').'</li>'.
	'<li>'.TD_('fill peppers, place in bowl, top with chives and hot sauce').'</li>'.
'</ol>',
						$now, $cat_recipes, array(), 'published', '#', '', '', 'open', array('default'), 'Recipe' );
				$edit_File = new File( 'shared', 0, 'recipes/stuffed-peppers.jpg' );
				$LinkOwner = new LinkItem( $edited_Item );
				$edit_File->link_to_Object( $LinkOwner, 1, 'teaser' );
				$item_IDs[] = array( $edited_Item->ID, $now );

				// Insert a post:
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$edited_Item->set_tags_from_string( 'demo,photo' );
				$edited_Item->set_setting( 'custom:course', TD_('Main Course') );
				$edited_Item->set_setting( 'custom:cuisine', TD_('Mongolian') );
				$edited_Item->set_setting( 'custom:servings', '4' );
				$edited_Item->set_setting( 'custom:prep_time', '2' );
				$edited_Item->set_setting( 'custom:cook_time', '35' );
				$edited_Item->set_setting( 'custom:passive_time', '5' );
				$edited_Item->set_setting( 'custom:ingredients', TD_('vegetable oil
1⁄2 teaspoon ginger
1 tablespoon garlic
1⁄2 cup soy sauce
1⁄2 cup water
3⁄4 cup dark brown sugar
1 lb flank steak
1 yellow onion
2 large green onions') );
				$mongolian_beef_ID = $edited_Item->insert( $owner_ID, TD_('Mongolian Beef'),
'<p>'.TD_('A quick go-to dinner. Can be made with almost any meat. I often used ground. Works perfect for lettuce wraps. Try replacing the onion with thinly sliced fennel.').'</p>'.
'<p>'.TD_('Optional: spice this thing up, with a dose of your favorite chili paste/sauce.').'</p>'.
'[teaserbreak]'.
'<ol>'.
	'<li>'.TD_('Slice the beef thin and cook with a bit of oil (your choice) and the yellow onion (cut into petals) in a medium saucepan. Set aside when done.').'</li>'.
	'<li>'.TD_('Make the sauce by heating 2 tsp of vegetable oil over med/low heat in the same pan. Don’t get the oil too hot.').'</li>'.
	'<li>'.TD_('Add ginger and garlic to the pan and quickly add the soy sauce and water before the garlic scorches.').'</li>'.
	'<li>'.TD_('Dissolve the brown sugar in the sauce, then raise the heat to medium and boil the sauce for 2-3 minutes or until the sauce thickens.').'</li>'.
	'<li>'.TD_('Remove from the heat, add beef back in. Toss').'</li>'.
	'<li>'.TD_('Serve with rice, top with green onions').'</li>'.
'</ol>',
						$now, $cat_recipes, array(), 'published', '#', '', '', 'open', array('default'), 'Recipe' );
				$edit_File = new File( 'shared', 0, 'recipes/mongolian-beef.jpg' );
				$LinkOwner = new LinkItem( $edited_Item );
				$edit_File->link_to_Object( $LinkOwner, 1, 'teaser' );
				$item_IDs[] = array( $edited_Item->ID, $now );
			}

			if( is_available_item_type( $blog_ID, '#' ) )
			{
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
			}
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

			if( is_available_item_type( $blog_ID, 'Sidebar link' ) )
			{
				// Insert sidebar links into Blog B
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$edited_Item->insert( $owner_ID, 'Skin Faktory', '', $now, $cat_additional_skins, array(), 'published', 'en-US', '', 'http://www.skinfaktory.com/', 'open', array('default'), 'Sidebar link', NULL, NULL, false );

				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$edited_Item->insert( $owner_ID, T_('b2evo skins repository'), '', $now, $cat_additional_skins, array(), 'published', 'en-US', '', 'http://skins.b2evolution.net/', 'open', array('default'), 'Sidebar link', NULL, NULL, false );
			}

			if( is_available_item_type( $blog_ID, 'Standalone Page' ) )
			{
				// Insert a PAGE:
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$edited_Item->insert( $owner_ID, T_('About Blog B'), sprintf( get_filler_text( 'info_page'), T_('Blog B') ), $now, $cat_ann_b,
					array(), 'published', '#', '', '', 'open', array('default'), 'Standalone Page', NULL, NULL, false );
			}

			if( is_available_item_type( $blog_ID, 'Intro-Front' ) )
			{
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
						$now, $cat_b2evo, array(), 'published', '#', '', '', 'open', array('default'), 'Intro-Front', NULL, NULL, false );
				$item_IDs[] = array( $edited_Item->ID, $now );
			}

			if( is_available_item_type( $blog_ID, 'Intro-Cat' ) )
			{
				// Insert a post:
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$edited_Item->set_tags_from_string( 'intro' );
				$edited_Item->insert( $owner_ID, T_('b2evolution tips category &ndash; Sub Intro post'), T_('This uses post type "Intro-Cat" and is attached to the desired Category(ies).'),
						$now, $cat_b2evo, array(), 'published', '#', '', '', 'open', array('default'), 'Intro-Cat', NULL, NULL, false );
				$item_IDs[] = array( $edited_Item->ID, $now );
			}

			if( is_available_item_type( $blog_ID, 'Intro-Tag' ) )
			{
				// Insert a post:
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$edited_Item->set_tags_from_string( 'widgets,intro' );
				$edited_Item->insert( $owner_ID, T_('Widgets tag &ndash; Sub Intro post'), T_('This uses post type "Intro-Tag" and is tagged with the desired Tag(s).'),
						$now, $cat_b2evo, array(), 'published', '#', '', '', 'open', array('default'), 'Intro-Tag', NULL, NULL, false );
				$item_IDs[] = array( $edited_Item->ID, $now );
			}

			if( is_available_item_type( $blog_ID, '#' ) )
			{
				// Insert a post:
				// TODO: move to Blog A
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$edited_Item->insert( $owner_ID, T_('Featured post'), T_('<p>This is a demo of a featured post.</p>

<p>It will be featured whenever we have no specific "Intro" post to display for the current request. To see it in action, try displaying the "Announcements" category.</p>

<p>Also note that when the post is featured, it does not appear in the regular post flow.</p>').get_filler_text( 'lorem_1paragraph' ),
						$now, $cat_b2evo, array( $cat_ann_b ), 'published', '#', '', '', 'open', array('default'), '#', NULL, NULL, false );
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
						$now, $cat_b2evo, array( $cat_ann_b ), 'published', '#', '', '', 'open', array('default'), '#', NULL, NULL, false );
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

<p>Either way, make sure you go to the blogs admin and set the correct access method/URL for your blog. Otherwise, the permalinks will not function properly.</p>"),
						$now, $cat_b2evo, array(), 'published', '#', '', '', 'open', array('default'), '#', NULL, NULL, false );
				$item_IDs[] = array( $edited_Item->ID, $now );

				// Insert a post:
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$edited_Item->set_tags_from_string( 'widgets' );
				$edited_Item->insert( $owner_ID, T_('About widgets...'), T_('<p>b2evolution blogs are installed with a default selection of Widgets. For example, the sidebar of this blog includes widgets like a calendar, a search field, a list of categories, a list of XML feeds, etc.</p>

<p>You can add, remove and reorder widgets from the Blog Settings tab in the admin interface.</p>

<p>Note: in order to be displayed, widgets are placed in containers. Each container appears in a specific place in an evoskin. If you change your blog skin, the new skin may not use the same containers as the previous one. Make sure you place your widgets in containers that exist in the specific skin you are using.</p>'),
						$now, $cat_b2evo, array(), 'published', '#', '', '', 'open', array('default'), '#', NULL, NULL, false );
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

<p>And, of course, read the <a href="%s" target="_blank">manual on skins</a>!</p>'), get_manual_url( 'skin-structure' ) ),
						$now, $cat_b2evo, array(), 'published', '#', '', '', 'open', array('default'), '#', NULL, NULL, false );
				$edited_Item->dbsave();
				// $edited_Item->insert_update_tags( 'update' );
				$item_IDs[] = array( $edited_Item->ID, $now );
			}
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

			if( is_available_item_type( $blog_ID, 'Standalone Page' ) )
			{
				// Insert a PAGE:
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$edited_Item->insert( $owner_ID, T_('About Photos'), sprintf( get_filler_text( 'info_page'), T_('Photos') ), $now, $cat_photo_album,
						array(), 'published', '#', '', '', 'open', array('default'), 'Standalone Page' );
			}

			if( is_available_item_type( $blog_ID, '#' ) )
			{
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
				{	// Add examples for infodots plugin
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
				$edited_Blog->set_setting( 'default_cat_ID', $cat_forums_ann );
				$edited_Blog->dbupdate();
			}


			// Sample posts

			if( is_available_item_type( $blog_ID, 'Standalone Page' ) )
			{
				// Insert a PAGE:
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$edited_Item->insert( 1, T_('About Forums'), sprintf( get_filler_text( 'info_page' ), T_('Forums') ), $now, $cat_forums_ann,
					array(), 'published', '#', '', '', 'open', array('default'), 'Standalone Page' );
			}

			if( is_available_item_type( $blog_ID, '#' ) )
			{
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
			}
			break;

		// =======================================================================================================
		case 'manual':
			$post_count = 17;
			$post_timestamp_array = get_post_timestamp_data( $post_count ) ;

			// Sample categories
			$cat_manual_intro = cat_create( T_('Introduction'), NULL, $blog_ID, NULL, true, 10 );
			$cat_manual_getstarted = cat_create( T_('Getting Started'), NULL, $blog_ID, NULL, true, 20 );
			$cat_manual_userguide = cat_create( T_('User Guide'), NULL, $blog_ID, NULL, true, 30 );
			$cat_manual_reference = cat_create( T_('Reference'), NULL, $blog_ID, NULL, true, 40, 'alpha' );

			$cat_manual_collections = cat_create( T_('Collections'), $cat_manual_reference, $blog_ID, NULL, true, 10 );
			$cat_manual_recipes = cat_create( T_('Recipes'), $cat_manual_reference, $blog_ID, NULL, true, NULL, NULL, false, 'Recipe' );
			$cat_manual_other = cat_create( T_('Other'), $cat_manual_reference, $blog_ID, NULL, true, 5 );

			$cat_manual_blogs = cat_create( T_('Blogs'), $cat_manual_collections, $blog_ID, NULL, true, 35 );
			$cat_manual_photos = cat_create( T_('Photo Albums'), $cat_manual_collections, $blog_ID, NULL, true, 25 );
			$cat_manual_forums = cat_create( T_('Forums'), $cat_manual_collections, $blog_ID, NULL, true, 5 );


			if( $edited_Blog = $BlogCache->get_by_ID( $blog_ID, false, false ) )
			{
				$edited_Blog->set_setting( 'default_cat_ID', $cat_manual_intro );
				$edited_Blog->dbupdate();
			}

			// Sample posts

			if( is_available_item_type( $blog_ID, 'Intro-Front' ) )
			{
				// Insert a main intro:
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$edited_Item->set_tags_from_string( 'intro' );
				$edited_Item->insert( $owner_ID, T_('Welcome here!'), T_('This is the main introduction for this demo online manual. It is a post using the type "Intro-Front". It will only appear on the front page of the manual.

You may delete this post if you don\'t want such an introduction.

Just to be clear: this is a **demo** of a manual. The user manual for b2evolution is here: http://b2evolution.net/man/.'), $now, $cat_manual_intro,
					array(), 'published', '#', '', '', 'open', array('default'), 'Intro-Front' );
			}

			if( is_available_item_type( $blog_ID, 'Intro-Cat' ) )
			{
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
			}

			if( is_available_item_type( $blog_ID, 'Standalone Page' ) )
			{
				// Insert a PAGE:
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$edited_Item->insert( $owner_ID, T_('About this manual'), sprintf( get_filler_text( 'info_page' ), T_('Manual') ), $now, $cat_manual_intro,
						array(), 'published', '#', '', '', 'open', array('default'), 'Standalone Page' );
			}

			if( is_available_item_type( $blog_ID, 'Manual Page' ) )
			{
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
			}

			if( is_available_item_type( $blog_ID, 'Recipe' ) )
			{
				// Insert a post:
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$edited_Item->set_tags_from_string( 'demo,photo' );
				$edited_Item->set_setting( 'custom:course', TD_('Main Course') );
				$edited_Item->set_setting( 'custom:cuisine', TD_('South African') );
				$edited_Item->set_setting( 'custom:servings', '2' );
				$edited_Item->set_setting( 'custom:prep_time', '1' );
				$edited_Item->set_setting( 'custom:cook_time', '20' );
				$edited_Item->set_setting( 'custom:passive_time', '3' );
				$edited_Item->set_setting( 'custom:ingredients', TD_('1 jar Peppedew Peppers (or piquante pepper)
4oz goat cheese (any flavor)
1 tbsp mayonnaise
1 tbsp sour cream
1 bunch of chives, chopped
hearty shot of hot sauce (Franks, Yellowbird)
hearty crack of pepper') );
				$mongolian_beef_ID = $edited_Item->insert( $owner_ID, TD_('Stuffed Peppers'),
'<p>'.TD_('We found these during Happy Hour at Chiso’s Grill in Bee Cave, Tx. We’ve since tweaked the recipe a bit. This recipe is just a starting point, add/remove anything you want (like more hot sauce if you’re into that).').'</p>'.
'[teaserbreak]'.
'<ol>'.
	'<li>'.TD_('combine goat cheese, mayo, sour cream, 2/3rds of your chives, hot sauce, black pepper').'</li>'.
	'<li>'.TD_('if you are feeling spry, beat the mixture to make it fluffy').'</li>'.
	'<li>'.TD_('put filling in a plastic bag, snip of the tip with scissors to make a piping bag').'</li>'.
	'<li>'.TD_('fill peppers, place in bowl, top with chives and hot sauce').'</li>'.
'</ol>',
						$now, $cat_manual_recipes, array(), 'published', '#', '', '', 'open', array('default'), 'Recipe' );
				$edit_File = new File( 'shared', 0, 'recipes/stuffed-peppers.jpg' );
				$LinkOwner = new LinkItem( $edited_Item );
				$edit_File->link_to_Object( $LinkOwner, 1, 'teaser' );
				$item_IDs[] = array( $edited_Item->ID, $now );

				// Insert a post:
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$edited_Item->set_tags_from_string( 'demo,photo' );
				$edited_Item->set_setting( 'custom:course', TD_('Main Course') );
				$edited_Item->set_setting( 'custom:cuisine', TD_('Mongolian') );
				$edited_Item->set_setting( 'custom:servings', '4' );
				$edited_Item->set_setting( 'custom:prep_time', '2' );
				$edited_Item->set_setting( 'custom:cook_time', '35' );
				$edited_Item->set_setting( 'custom:passive_time', '5' );
				$edited_Item->set_setting( 'custom:ingredients', TD_('vegetable oil
1⁄2 teaspoon ginger
1 tablespoon garlic
1⁄2 cup soy sauce
1⁄2 cup water
3⁄4 cup dark brown sugar
1 lb flank steak
1 yellow onion
2 large green onions') );
				$mongolian_beef_ID = $edited_Item->insert( $owner_ID, TD_('Mongolian Beef'),
'<p>'.TD_('A quick go-to dinner. Can be made with almost any meat. I often used ground. Works perfect for lettuce wraps. Try replacing the onion with thinly sliced fennel.').'</p>'.
'<p>'.TD_('Optional: spice this thing up, with a dose of your favorite chili paste/sauce.').'</p>'.
'[teaserbreak]'.
'<ol>'.
	'<li>'.TD_('Slice the beef thin and cook with a bit of oil (your choice) and the yellow onion (cut into petals) in a medium saucepan. Set aside when done.').'</li>'.
	'<li>'.TD_('Make the sauce by heating 2 tsp of vegetable oil over med/low heat in the same pan. Don’t get the oil too hot.').'</li>'.
	'<li>'.TD_('Add ginger and garlic to the pan and quickly add the soy sauce and water before the garlic scorches.').'</li>'.
	'<li>'.TD_('Dissolve the brown sugar in the sauce, then raise the heat to medium and boil the sauce for 2-3 minutes or until the sauce thickens.').'</li>'.
	'<li>'.TD_('Remove from the heat, add beef back in. Toss').'</li>'.
	'<li>'.TD_('Serve with rice, top with green onions').'</li>'.
'</ol>',
						$now, $cat_manual_recipes, array(), 'published', '#', '', '', 'open', array('default'), 'Recipe' );
				$edit_File = new File( 'shared', 0, 'recipes/mongolian-beef.jpg' );
				$LinkOwner = new LinkItem( $edited_Item );
				$edit_File->link_to_Object( $LinkOwner, 1, 'teaser' );
				$item_IDs[] = array( $edited_Item->ID, $now );
			}

			if( is_available_item_type( $blog_ID, 'Manual Page' ) )
			{
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

<p>You can add new collections of any type (blog, photos, forums, etc.), delete unwanted one and customize existing collections (title, sidebar, blog skin, widgets, etc.) from the admin interface.</p>"), $now, $cat_manual_intro, array( $cat_manual_collections ),
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
			}

			if( is_available_item_type( $blog_ID, '#' ) )
			{
				// Insert Markdown example post:
				$post_count--;
				$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );
				$edited_Item = new Item();
				$edited_Item->set_tags_from_string( 'demo' );
				$edited_Item->insert( $owner_ID, T_('Markdown examples'), get_filler_text( 'markdown_examples_content'), $now, $cat_manual_userguide );
				$item_IDs[] = array( $edited_Item->ID, $now );
			}
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
			foreach( $demo_users as $key => $demo_user )
			{
				if( $demo_user->check_perm( 'blog_can_be_assignee', 'edit', false, $blog_ID ) )
				{
					$allowed_assignee[] = $demo_user->ID;
				}
			}

			if( is_available_item_type( $blog_ID, '#' ) )
			{
				for( $i = 0, $j = 0, $k = 0, $m = 0; $i < 20; $i++ )
				{
					$post_count--;
					$now = date( 'Y-m-d H:i:s', $post_timestamp_array[$post_count] );

					$edited_Item = new Item();
					$edited_Item->set_tags_from_string( 'demo' );
					$edited_Item->set( 'priority', $priorities[$j] );

					if( $use_demo_user )
					{	// Assign task to allowed assignee
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
			}
			break;

		default:
			// do nothing
	}

	// Create demo comments
	$comment_users = array_values( $demo_users );
	if( count( $comment_users ) === 1 )
	{	// Only 1 demo user, use anonymous users:
		$comment_users = NULL;
	}
	foreach( $item_IDs as $item_ID )
	{
		$comment_timestamp = strtotime( $item_ID[1] );
		adjust_timestamp( $comment_timestamp, 30, 720 );
		create_demo_comment( $item_ID[0], $comment_users, 'published', $comment_timestamp );
		adjust_timestamp( $comment_timestamp, 30, 720 );
		create_demo_comment( $item_ID[0], $comment_users, NULL, $comment_timestamp );
	}

	if( $install_test_features && count( $additional_comments_item_IDs ) && $use_demo_user )
	{	// Create the additional comments when we install all features
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
			{	// Insert the comments from each user
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


/**
 * Create a demo poll
 *
 * @return integer ID of of created poll
 */
function create_demo_poll()
{
	global $DB;

	$demo_users = get_demo_users( false );
	$max_answers = 3;

	$demo_question = T_('What are your favorite b2evolution feature?');

	// Check if there is already a demo poll:
	$demo_poll_ID = $DB->get_var( 'SELECT pqst_ID FROM T_polls__question WHERE pqst_question_text = '.$DB->quote( $demo_question ) );

	if( empty( $demo_poll_ID ) )
	{
		// Add poll question:
		$DB->query( 'INSERT INTO T_polls__question ( pqst_owner_user_ID, pqst_question_text, pqst_max_answers )
			VALUES ( 1, '.$DB->quote( $demo_question ).', '.$max_answers.' )' );

		$demo_poll_ID = $DB->insert_id;

		// Add poll answers:
		$answer_texts = array(
				array( T_('Multiple blogs'), 1 ),
				array( T_('Photo Galleries'), 2 ),
				array( T_('Forums'), 3 ),
				array( T_('Online Manuals'), 4 ),
				array( T_('Lists / E-mailing'), 5 ),
				array( T_('Easy Maintenance'), 6 )
			);

		$answer_IDs = array();
		foreach( $answer_texts as $answer_text )
		{
			$DB->query( 'INSERT INTO T_polls__option ( popt_pqst_ID, popt_option_text, popt_order )
					VALUES ( '.$demo_poll_ID.', '.$DB->quote( $answer_text[0] ).', '.$DB->quote( $answer_text[1] ).' )' );
			$answer_IDs[] = $DB->insert_id;
		}

		// Generate answers:
		$insert_values = array();
		foreach( $demo_users as $demo_user )
		{
			$answers = $answer_IDs;
			for( $i = 0; $i < $max_answers; $i++ )
			{
				$rand_key = array_rand( $answers );
				$insert_values[] = '( '.$demo_poll_ID.', '.$demo_user->ID.', '.$answers[$rand_key].' )';
				unset( $answers[$rand_key] );
			}
		}
		if( $insert_values )
		{
			$DB->query( 'INSERT INTO T_polls__answer ( pans_pqst_ID, pans_user_ID, pans_popt_ID )
				VALUES '.implode( ', ', $insert_values ) );
		}
	}

	return $demo_poll_ID;
}


/**
 * This is called installs in the backoffice and fills the tables with
 * demo/tutorial things.
 *
 * @return integer Number of collections installed
 */
function install_demo_content()
{
	global $DB, $current_User;
	global $install_test_features;

	$create_sample_contents   = param( 'create_sample_contents', 'string', false, true );   // during auto install this param can be 'all'
	$create_demo_organization = param( 'create_demo_organization', 'boolean', false, true );
	$create_demo_users        = param( 'create_demo_users', 'boolean', false, true );
	$create_demo_messages     = param( 'create_sample_private_messages', 'boolean', false, true );
	$install_test_features    = param( 'install_test_features', 'boolean', false );

	$user_org_IDs = NULL;

	$DB->begin();
	if( $create_demo_organization )
	{
		echo get_install_format_text( '<h2>'.T_('Creating sample organization and users...').'</h2>', 'h2' );
		evo_flush();

		if( $create_demo_organization )
		{
			task_begin( 'Creating demo organization...' );
			$user_org_IDs = array( create_demo_organization( $current_User->ID )->ID );
			task_end();

			task_begin( 'Adding admin user to demo organization...' );
			$current_User->update_organizations( $user_org_IDs, array( 'King of Spades' ), array( 0 ), true );
			task_end();
		}
	}

	$demo_users = get_demo_users( $create_demo_users );

	if( $create_demo_users && $create_demo_messages )
	{
		task_begin( 'Creating demo private messages...' );
		create_demo_messages();
		task_end();
	}

	$collections_installed = 0;
	if( $create_sample_contents )
	{
		echo get_install_format_text( '<h2>'.T_('Installing sample contents...').'</h2>', 'h2' );
		evo_flush();
		$collections_installed = create_demo_contents( $demo_users, true, false );
	}

	if( $collections_installed )
	{
		echo '<br/>';
		echo get_install_format_text( '<span class="text-success">'.T_('Created sample contents.').'</span>' );
	}
	evo_flush();
	$DB->commit();

	return $collections_installed;
}