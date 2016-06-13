<?php
/**
 * This file implements creation of DB tables
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004 by Vegar BERG GULDAL - {@link http://funky-m.com/}
 * Parts of this file are copyright (c)2005 by Jason EDGECOMBE
 *
 * @package install
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'users/model/_group.class.php', 'Group' );
load_funcs( 'collections/model/_category.funcs.php' );
load_class( 'users/model/_organization.class.php', 'Organization' );

/**
 * Used for fresh install
 */
function create_tables()
{
	global $inc_path;

	// Load DB schema from modules
	load_db_schema();

	// Update the progress bar status
	update_install_progress_bar();

	load_funcs('_core/model/db/_upgrade.funcs.php');

	// Alter DB to match DB schema:
	install_make_db_schema_current( true );
}


/**
 * Insert all default data:
 */
function create_default_data()
{
	global $admins_Group, $moderators_Group, $editors_Group, $users_Group, $suspect_Group, $spam_Group, $blogb_Group;
	global $DB, $locales, $current_locale, $baseurl;
	// This will install all sorts of additional things... for testing purposes:
	global $install_test_features, $create_sample_contents;

	// Inserting sample data triggers events: instead of checking if $Plugins is an object there, just use a fake one..
	load_class('plugins/model/_plugins_admin_no_db.class.php', 'Plugins_admin_no_DB' );
	global $Plugins;
	$Plugins = new Plugins_admin_no_DB(); // COPY

	// added in 0.8.7
	task_begin( 'Creating default blacklist entries... ' );
	// This string contains antispam information that is obfuscated because some hosting
	// companies prevent uploading PHP files containing "spam" strings.
	// pre_dump(get_antispam_query());
	$query = get_antispam_query();
	$DB->query( $query );
	task_end();

	task_begin( 'Creating default antispam IP ranges... ' );
	$DB->query( '
		INSERT INTO T_antispam__iprange ( aipr_IPv4start, aipr_IPv4end, aipr_status )
		VALUES ( '.$DB->quote( ip2int( '127.0.0.0' ) ).', '.$DB->quote( ip2int( '127.0.0.255' ) ).', "trusted" ),
			( '.$DB->quote( ip2int( '10.0.0.0' ) ).', '.$DB->quote( ip2int( '10.255.255.255' ) ).', "trusted" ),
			( '.$DB->quote( ip2int( '172.16.0.0' ) ).', '.$DB->quote( ip2int( '172.31.255.255' ) ).', "trusted" ),
			( '.$DB->quote( ip2int( '192.168.0.0' ) ).', '.$DB->quote( ip2int( '192.168.255.255' ) ).', "trusted" )
		' );
	task_end();

	// added in 0.8.9
	task_begin( 'Creating default groups... ' );
	$admins_Group = new Group(); // COPY !
	$admins_Group->set( 'name', 'Administrators' );
	$admins_Group->set( 'level', 10 );
	$admins_Group->set( 'perm_blogs', 'editall' );
	$admins_Group->set( 'perm_stats', 'edit' );
	$admins_Group->set( 'perm_xhtml_css_tweaks', 1 );
	$admins_Group->dbinsert();

	$moderators_Group = new Group(); // COPY !
	$moderators_Group->set( 'name', 'Moderators' );
	$moderators_Group->set( 'level', 8 );
	$moderators_Group->set( 'perm_blogs', 'viewall' );
	$moderators_Group->set( 'perm_stats', 'user' );
	$moderators_Group->set( 'perm_xhtml_css_tweaks', 1 );
	$moderators_Group->dbinsert();

	$editors_Group = new Group(); // COPY !
	$editors_Group->set( 'name', 'Editors' );
	$editors_Group->set( 'level', 6 );
	$editors_Group->set( 'perm_blogs', 'user' );
	$editors_Group->set( 'perm_stats', 'none' );
	$editors_Group->set( 'perm_xhtml_css_tweaks', 1 );
	$editors_Group->dbinsert();

	$users_Group = new Group(); // COPY !
	$users_Group->set( 'name', 'Normal Users' );
	$users_Group->set( 'level', 4 );
	$users_Group->set( 'perm_blogs', 'user' );
	$users_Group->set( 'perm_stats', 'none' );
	$users_Group->dbinsert();

	$suspect_Group = new Group(); // COPY !
	$suspect_Group->set( 'name', 'Misbehaving/Suspect Users' );
	$suspect_Group->set( 'level', 2 );
	$suspect_Group->set( 'perm_blogs', 'user' );
	$suspect_Group->set( 'perm_stats', 'none' );
	$suspect_Group->dbinsert();

	$spam_Group = new Group(); // COPY !
	$spam_Group->set( 'name', 'Spammers/Restricted Users' );
	$spam_Group->set( 'level', 1 );
	$spam_Group->set( 'perm_blogs', 'user' );
	$spam_Group->set( 'perm_stats', 'none' );
	$spam_Group->dbinsert();

	$blogb_Group = new Group(); // COPY !
	$blogb_Group->set( 'name', 'Blog B Members' );
	$blogb_Group->set( 'usage', 'secondary' );
	$blogb_Group->set( 'level', 1 );
	$blogb_Group->set( 'perm_blogs', 'user' );
	$blogb_Group->set( 'perm_stats', 'none' );
	$blogb_Group->dbinsert();
	task_end();

	task_begin( 'Creating groups for user field definitions... ' );
	$DB->query( "
		INSERT INTO T_users__fieldgroups ( ufgp_name, ufgp_order )
		VALUES ( 'About me', '1' ),
					 ( 'Instant Messaging', '2' ),
					 ( 'Phone', '3' ),
					 ( 'Web', '4' ),
					 ( 'Address', '5' )" );
	task_end();

	task_begin( 'Creating user field definitions... ' );
	// fp> Anyone, please add anything you can think of. It's better to start with a large list that update it progressively.
	$DB->query( "
		INSERT INTO T_users__fielddefs (ufdf_ufgp_ID, ufdf_type, ufdf_name, ufdf_options, ufdf_required, ufdf_duplicated, ufdf_order, ufdf_suggest, ufdf_code, ufdf_icon_name)
		 VALUES ( 1, 'text',   'Micro bio',     NULL, 'recommended', 'forbidden', '1',  '0', 'microbio',     'fa fa-info-circle' ),
						( 1, 'word',   'I like',        NULL, 'recommended', 'list',      '2',  '1', 'ilike',        'fa fa-thumbs-o-up' ),
						( 1, 'word',   'I don\'t like', NULL, 'recommended', 'list',      '3',  '1', 'idontlike',    'fa fa-thumbs-o-down' ),
						( 2, 'email',  'MSN/Live IM',   NULL, 'optional',    'allowed',   '1',  '0', 'msnliveim',    NULL ),
						( 2, 'word',   'Yahoo IM',      NULL, 'optional',    'allowed',   '2',  '0', 'yahooim',      'fa fa-yahoo' ),
						( 2, 'word',   'AOL AIM',       NULL, 'optional',    'allowed',   '3',  '0', 'aolaim',       NULL ),
						( 2, 'number', 'ICQ ID',        NULL, 'optional',    'allowed',   '4',  '0', 'icqid',        NULL ),
						( 2, 'phone',  'Skype',         NULL, 'optional',    'allowed',   '5',  '0', 'skype',        'fa fa-skype' ),
						( 3, 'phone',  'Main phone',    NULL, 'optional',    'forbidden', '1',  '0', 'mainphone',    'fa fa-phone' ),
						( 3, 'phone',  'Cell phone',    NULL, 'optional',    'allowed',   '2',  '0', 'cellphone',    'fa fa-mobile-phone' ),
						( 3, 'phone',  'Office phone',  NULL, 'optional',    'allowed',   '3',  '0', 'officephone',  'fa fa-phone' ),
						( 3, 'phone',  'Home phone',    NULL, 'optional',    'allowed',   '4',  '0', 'homephone',    'fa fa-phone' ),
						( 3, 'phone',  'Office FAX',    NULL, 'optional',    'allowed',   '5',  '0', 'officefax',    'fa fa-fax' ),
						( 3, 'phone',  'Home FAX',      NULL, 'optional',    'allowed',   '6',  '0', 'homefax',      'fa fa-fax' ),
						( 4, 'url',    'Twitter',       NULL, 'recommended', 'forbidden', '1',  '0', 'twitter',      'fa fa-twitter' ),
						( 4, 'url',    'Facebook',      NULL, 'recommended', 'forbidden', '2',  '0', 'facebook',     'fa fa-facebook' ),
						( 4, 'url',    'Google Plus',   NULL, 'optional',    'forbidden', '3',  '0', 'googleplus',   'fa fa-google-plus fa-x-google-plus--nudge' ),
						( 4, 'url',    'Linkedin',      NULL, 'optional',    'forbidden', '4',  '0', 'linkedin',     'fa fa-linkedin fa-x-linkedin--nudge' ),
						( 4, 'url',    'GitHub',        NULL, 'optional',    'forbidden', '5',  '0', 'github',       'fa fa-github-alt' ),
						( 4, 'url',    'Website',       NULL, 'recommended', 'allowed',   '6',  '0', 'website',      NULL ),
						( 4, 'url',    'Blog',          NULL, 'optional',    'allowed',   '7',  '0', 'blog',         NULL ),
						( 4, 'url',    'Myspace',       NULL, 'optional',    'forbidden', '8',  '0', 'myspace',      NULL ),
						( 4, 'url',    'Flickr',        NULL, 'optional',    'forbidden', '9',  '0', 'flickr',       'fa fa-flickr' ),
						( 4, 'url',    'YouTube',       NULL, 'optional',    'forbidden', '10', '0', 'youtube',      'fa fa-youtube' ),
						( 4, 'url',    'Digg',          NULL, 'optional',    'forbidden', '11', '0', 'digg',         'fa fa-digg' ),
						( 4, 'url',    'StumbleUpon',   NULL, 'optional',    'forbidden', '12', '0', 'stumbleupon',  'fa fa-stumbleupon' ),
						( 4, 'url',    'Pinterest',     NULL, 'optional',    'forbidden', '13', '0', 'pinterest',    'fa fa-pinterest-p' ),
						( 5, 'text',   'Main address',  NULL, 'optional',    'forbidden', '1',  '0', 'mainaddress',  'fa fa-building' ),
						( 5, 'text',   'Home address',  NULL, 'optional',    'forbidden', '2',  '0', 'homeaddress',  'fa fa-home' )" );
	task_end();


	// don't change order of the following two functions as countries has relations to currencies
	create_default_currencies();
	create_default_countries();

	create_default_regions();
	create_default_subregions();


	task_begin( 'Creating admin user... ' );
	global $timestamp, $admin_email, $default_locale, $default_country, $install_login, $install_password;
	global $random_password;

	// Create organization
	global $user_org_IDs;
	$user_org_IDs = NULL;

	// A default organization will be created so that there will be something to
	// display in the homepage's organization members widget
	$Organization = new Organization();
	$Organization->set( 'owner_user_ID', '1' );
	$Organization->set( 'name', 'Company XYZ' );
	$Organization->set( 'url', 'http://b2evolution.net/' );
	if( $Organization->dbinsert() )
	{ // Use this organization for new created users
		$user_org_IDs = array( $Organization->ID );
	}

	// Set default country from locale code
	$country_code = explode( '-', $default_locale );
	if( isset( $country_code[1] ) )
	{
		$default_country = $DB->get_var( '
			SELECT ctry_ID
			  FROM T_regional__country
			 WHERE ctry_code = '.$DB->quote( strtolower( $country_code[1] ) ) );
	}

	if( !isset( $install_password ) )
	{
		$random_password = generate_random_passwd(); // no ambiguous chars
	}
	else
	{
		$random_password = $install_password;
	}

	create_user( array(
			'login'     => isset( $install_login ) ? $install_login : 'admin',
			'firstname' => 'Johnny',
			'lastname'  => 'Admin',
			'level'     => 10,
			'gender'    => 'M',
			'Group'     => $admins_Group,
			'org_IDs'   => $user_org_IDs,
			'fields'    => array(
					'Micro bio'   => 'I am the demo administrator of this site.'."\n".'I love having so much power!',
					'Website'     => 'http://b2evolution.net/',
					'Twitter'     => 'https://twitter.com/b2evolution/',
					'Facebook'    => 'https://www.facebook.com/b2evolution',
					'Linkedin'    => 'https://www.linkedin.com/company/b2evolution-net',
					'GitHub'      => 'https://github.com/b2evolution/b2evolution',
					'Google Plus' => 'https://plus.google.com/+b2evolution/posts',
				)
		) );
	task_end();

	// Activating multiple sessions and email message form for administrator, and set other user settings
	task_begin( 'Set settings for administrator user... ' );
	$DB->query( "
		INSERT INTO T_users__usersettings ( uset_user_ID, uset_name, uset_value )
		VALUES ( 1, 'login_multiple_sessions', '1' ),
				( 1, 'enable_email', '1' ),
				( 1, 'created_fromIPv4', '".ip2int( '127.0.0.1' )."' ),
				( 1, 'user_domain', 'localhost' )" );
	task_end();


	// added in Phoenix-Alpha
	task_begin( 'Creating default Post Types... ' );
	$post_types = array();
	$post_types[] = array(
			'name'           => 'Post',
		);
	$post_types[] = array(
			'name'           => 'Podcast Episode',
			'podcast'        => 1,
		);
	$post_types[] = array(
			'name'           => 'Post with Custom Fields',
		);
	$post_types[] = array(
			'name'           => 'Child Post',
			'use_parent'     => 'required',
		);
	$post_types[] = array(
			'name'           => 'Manual Page',
			'allow_html'     => 0,
		);
	$post_types[] = array(
			'name'           => 'Forum Topic',
			'allow_html'     => 0,
		);
	$post_types[] = array(
			'name'           => 'Photo Album',
		);
	$post_types[] = array(
			'name'           => 'Standalone Page',
			'usage'          => 'page',
			'template_name'  => 'page',
			'perm_level'     => 'restricted',
		);
	$post_types[] = array(
			'name'           => 'Intro-Front',
			'usage'          => 'intro-front',
			'template_name'  => NULL,
			'allow_breaks'   => 0,
			'allow_featured' => 0,
			'perm_level'     => 'restricted',
		);
	$post_types[] = array(
			'name'           => 'Intro-Main',
			'usage'          => 'intro-main',
			'template_name'  => NULL,
			'allow_breaks'   => 0,
			'allow_featured' => 0,
			'perm_level'     => 'restricted',
		);
	$post_types[] = array(
			'name'           => 'Intro-Cat',
			'usage'          => 'intro-cat',
			'template_name'  => NULL,
			'allow_breaks'   => 0,
			'allow_featured' => 0,
			'perm_level'     => 'restricted',
		);
	$post_types[] = array(
			'name'           => 'Intro-Tag',
			'usage'          => 'intro-tag',
			'template_name'  => NULL,
			'allow_breaks'   => 0,
			'allow_featured' => 0,
			'perm_level'     => 'restricted',
		);
	$post_types[] = array(
			'name'           => 'Intro-Sub',
			'usage'          => 'intro-sub',
			'template_name'  => NULL,
			'allow_breaks'   => 0,
			'allow_featured' => 0,
			'perm_level'     => 'restricted',
		);
	$post_types[] = array(
			'name'           => 'Intro-All',
			'usage'          => 'intro-all',
			'template_name'  => NULL,
			'allow_breaks'   => 0,
			'allow_featured' => 0,
			'perm_level'     => 'restricted',
		);
	$post_types[] = array(
			'name'           => 'Sidebar link',
			'usage'          => 'special',
			'template_name'  => NULL,
			'perm_level'     => 'admin',
		);
	$post_types[] = array(
			'name'           => 'Advertisement',
			'usage'          => 'special',
			'template_name'  => NULL,
			'perm_level'     => 'admin',
		);
	$post_types[] = array(
			'name'                   => 'Terms & Conditions',
			'usage'                  => 'special',
			'template_name'          => NULL,
			'allow_breaks'           => 0,
			'allow_featured'         => 0,
			'perm_level'             => 'admin',
			'description'            => 'Use this post type for terms & conditions of the site.',
			'use_text'               => 'required',
			'use_tags'               => 'never',
			'use_excerpt'            => 'never',
			'use_url'                => 'never',
			'use_parent'             => 'never',
			'use_title_tag'          => 'never',
			'use_meta_desc'          => 'never',
			'use_meta_keywds'        => 'never',
			'use_comments'           => 0,
			'allow_closing_comments' => 0,
			'use_comment_expiration' => 'never',
			'use_custom_fields'      => 0,
		);
	// Default settings:
	$post_type_default_settings = array(
			'name'                   => '',
			'description'            => NULL,
			'usage'                  => 'post',
			'template_name'          => 'single',
			'perm_level'             => 'standard',
			'allow_html'             => 1,
			'allow_breaks'           => 1,
			'allow_featured'         => 1,
			'use_text'               => 'optional',
			'use_tags'               => 'optional',
			'use_excerpt'            => 'optional',
			'use_url'                => 'optional',
			'podcast'                => 0,
			'use_parent'             => 'never',
			'use_title_tag'          => 'optional',
			'use_meta_desc'          => 'optional',
			'use_meta_keywds'        => 'optional',
			'use_comments'           => 1,
			'allow_closing_comments' => 1,
			'use_comment_expiration' => 'optional',
			'use_custom_fields'      => 1,
		);
	$post_types_sql = 'INSERT INTO T_items__type ( ityp_'.implode( ', ityp_', array_keys( $post_type_default_settings ) ).' ) VALUES ';
	foreach( $post_types as $p => $post_type )
	{
		$post_type = array_merge( $post_type_default_settings, $post_type );
		$post_types_sql .= '( '.$DB->quote( $post_type ).' )';
		if( $p != count( $post_types ) - 1 )
		{
			$post_types_sql .= ',';
		}
	}
	// Insert item types:
	$DB->query( $post_types_sql );

	$DB->query( 'INSERT INTO T_items__type_custom_field ( itcf_ityp_ID, itcf_label, itcf_name, itcf_type )
			VALUES ( 3, "First numeric field", "first_numeric_field", "double" ),
						 ( 3, "Second numeric field", "second_numeric_field", "double" ),
						 ( 3, "First text field", "first_text_field", "varchar" ),
						 ( 3, "Define you own labels", "define_you_own_labels", "varchar" )' );
	task_end();


	task_begin( 'Creating default Post Statuses... ' );
	$DB->query( "
		INSERT INTO T_items__status ( pst_name )
		VALUES ( 'New' ),
					 ( 'In Progress' ),
					 ( 'Duplicate' ),
					 ( 'Not A Bug' ),
					 ( 'In Review' ),
					 ( 'Fixed' ),
					 ( 'Closed' )" );
	task_end();


	// added in Phoenix-Beta
	task_begin( 'Creating default file types... ' );
	// Contribs: feel free to add more types here...
	// TODO: dh> shouldn't they get localized to the app's default locale? fp> ftyp_name, yes
	$DB->query( "INSERT INTO T_filetypes
			(ftyp_ID, ftyp_extensions, ftyp_name, ftyp_mimetype, ftyp_icon, ftyp_viewtype, ftyp_allowed)
		VALUES
			(1, 'gif', 'GIF image', 'image/gif', 'file_image', 'image', 'any'),
			(2, 'png', 'PNG image', 'image/png', 'file_image', 'image', 'any'),
			(3, 'jpg jpeg', 'JPEG image', 'image/jpeg', 'file_image', 'image', 'any'),
			(4, 'txt', 'Text file', 'text/plain', 'file_document', 'text', 'registered'),
			(5, 'htm html', 'HTML file', 'text/html', 'file_www', 'browser', 'admin'),
			(6, 'pdf', 'PDF file', 'application/pdf', 'file_pdf', 'browser', 'registered'),
			(7, 'doc docx', 'Microsoft Word file', 'application/msword', 'file_doc', 'external', 'registered'),
			(8, 'xls xlsx', 'Microsoft Excel file', 'application/vnd.ms-excel', 'file_xls', 'external', 'registered'),
			(9, 'ppt pptx', 'Powerpoint', 'application/vnd.ms-powerpoint', 'file_ppt', 'external', 'registered'),
			(10, 'pps', 'Slideshow', 'pps', 'file_pps', 'external', 'registered'),
			(11, 'zip', 'ZIP archive', 'application/zip', 'file_zip', 'external', 'registered'),
			(12, 'php php3 php4 php5 php6', 'PHP script', 'application/x-httpd-php', 'file_php', 'text', 'admin'),
			(13, 'css', 'Style sheet', 'text/css', '', 'text', 'registered'),
			(14, 'mp3', 'MPEG audio file', 'audio/mpeg', 'file_sound', 'browser', 'registered'),
			(15, 'm4a', 'MPEG audio file', 'audio/x-m4a', 'file_sound', 'browser', 'registered'),
			(16, 'mp4 f4v', 'MPEG video', 'video/mp4', 'file_video', 'browser', 'registered'),
			(17, 'mov', 'Quicktime video', 'video/quicktime', 'file_video', 'browser', 'registered'),
			(18, 'm4v', 'MPEG video file', 'video/x-m4v', 'file_video', 'browser', 'registered'),
			(19, 'flv', 'Flash video file', 'video/x-flv', 'file_video', 'browser', 'registered'),
			(20, 'swf', 'Flash video file', 'application/x-shockwave-flash', 'file_video', 'browser', 'registered'),
			(21, 'webm', 'WebM video file', 'video/webm', 'file_video', 'browser', 'registered'),
			(22, 'ogv', 'Ogg video file', 'video/ogg', 'file_video', 'browser', 'registered'),
			(23, 'm3u8', 'M3U8 video file', 'application/x-mpegurl', 'file_video', 'browser', 'registered')
		" );
	task_end();

	// Insert default locales into T_locales.
	create_default_locales();

	// Insert default settings into T_settings.
	create_default_settings();

	// Create default scheduled jobs
	create_default_jobs();

	task_begin( 'Creating default "help" slug... ' );
	$DB->query( '
		INSERT INTO T_slug( slug_title, slug_type )
		VALUES( "help", "help" )', 'Add "help" slug' );
	task_end();

	// Create the 'Default' goal category which must always exists and which is not deletable
	// The 'Default' category ID will be always 1 because it will be always the first entry in the T_track__goalcat table
	task_begin( 'Creating default goal category... ' );
	$DB->query( 'INSERT INTO T_track__goalcat ( gcat_name, gcat_color )
		VALUES ( '.$DB->quote( 'Default' ).', '.$DB->quote( '#999999' ).' )' );
	task_end();


	task_begin( 'Creating default polls... ' );
	$DB->query( 'INSERT INTO T_polls__question ( pqst_owner_user_ID, pqst_question_text )
		VALUES ( 1, "What is your favorite b2evolution feature?" )' );
	$DB->query( 'INSERT INTO T_polls__option ( popt_pqst_ID, popt_option_text, popt_order )
		VALUES ( 1, "Multiple blogs",          1 ),
		       ( 1, "Photo Galleries",         2 ),
		       ( 1, "Forums",                  3 ),
		       ( 1, "Online Manuals",          4 ),
		       ( 1, "Newsletters / E-mailing", 5 ),
		       ( 1, "Easy Maintenance",        6 )' );
	$DB->query( 'INSERT INTO T_polls__answer ( pans_pqst_ID, pans_user_ID, pans_popt_ID )
		VALUES ( 1, 5, 1 ),
		       ( 1, 6, 2 ),
		       ( 1, 7, 2 ),
		       ( 1, 2, 2 ),
		       ( 1, 3, 3 ),
		       ( 1, 4, 3 ),
		       ( 1, 1, 6 )' );
	task_end();

	// Update the progress bar status
	update_install_progress_bar();

	install_basic_skins();

	install_basic_plugins();

	return true;
}

/**
 * Create default currencies
 *
 */
function create_default_currencies( $table_name = 'T_regional__currency' )
{
	global $DB;

	task_begin( 'Creating default currencies... ' );
	$DB->query( "
		INSERT INTO $table_name (curr_ID, curr_code, curr_shortcut, curr_name)
		 VALUES
			(1, 'AFN', '&#x60b;', 'Afghani'),
			(2, 'EUR', '&euro;', 'Euro'),
			(3, 'ALL', 'Lek', 'Lek'),
			(4, 'DZD', 'DZD', 'Algerian Dinar'),
			(5, 'USD', '$', 'US Dollar'),
			(6, 'AOA', 'AOA', 'Kwanza'),
			(7, 'XCD', '$', 'East Caribbean Dollar'),
			(8, 'ARS', '$', 'Argentine Peso'),
			(9, 'AMD', 'AMD', 'Armenian Dram'),
			(10, 'AWG', '&fnof;', 'Aruban Guilder'),
			(11, 'AUD', '$', 'Australian Dollar'),
			(12, 'AZN', '&#x43c;&#x430;&#x43d;', 'Azerbaijanian Manat'),
			(13, 'BSD', '$', 'Bahamian Dollar'),
			(14, 'BHD', 'BHD', 'Bahraini Dinar'),
			(15, 'BDT', 'BDT', 'Taka'),
			(16, 'BBD', '$', 'Barbados Dollar'),
			(17, 'BYR', 'p.', 'Belarussian Ruble'),
			(18, 'BZD', 'BZ$', 'Belize Dollar'),
			(19, 'XOF', 'XOF', 'CFA Franc BCEAO'),
			(20, 'BMD', '$', 'Bermudian Dollar'),
			(21, 'BAM', 'KM', 'Convertible Marks'),
			(22, 'BWP', 'P', 'Pula'),
			(23, 'NOK', 'kr', 'Norwegian Krone'),
			(24, 'BRL', 'R$', 'Brazilian Real'),
			(25, 'BND', '$', 'Brunei Dollar'),
			(26, 'BGN', '&#x43b;&#x432;', 'Bulgarian Lev'),
			(27, 'BIF', 'BIF', 'Burundi Franc'),
			(28, 'KHR', '&#x17db;', 'Riel'),
			(29, 'XAF', 'XAF', 'CFA Franc BEAC'),
			(30, 'CAD', '$', 'Canadian Dollar'),
			(31, 'CVE', 'CVE', 'Cape Verde Escudo'),
			(32, 'KYD', '$', 'Cayman Islands Dollar'),
			(33, 'CNY', '&yen;', 'Yuan Renminbi'),
			(34, 'KMF', 'KMF', 'Comoro Franc'),
			(35, 'CDF', 'CDF', 'Congolese Franc'),
			(36, 'NZD', '$', 'New Zealand Dollar'),
			(37, 'CRC', '&#x20a1;', 'Costa Rican Colon'),
			(38, 'HRK', 'kn', 'Croatian Kuna'),
			(39, 'CZK', 'K&#x10d;', 'Czech Koruna'),
			(40, 'DKK', 'kr', 'Danish Krone'),
			(41, 'DJF', 'DJF', 'Djibouti Franc'),
			(42, 'DOP', 'RD$', 'Dominican Peso'),
			(43, 'EGP', '&pound;', 'Egyptian Pound'),
			(44, 'ERN', 'ERN', 'Nakfa'),
			(45, 'EEK', 'EEK', 'Kroon'),
			(46, 'ETB', 'ETB', 'Ethiopian Birr'),
			(47, 'FKP', '&pound;', 'Falkland Islands Pound'),
			(48, 'FJD', '$', 'Fiji Dollar'),
			(49, 'XPF', 'XPF', 'CFP Franc'),
			(50, 'GMD', 'GMD', 'Dalasi'),
			(51, 'GEL', 'GEL', 'Lari'),
			(52, 'GHS', 'GHS', 'Cedi'),
			(53, 'GIP', '&pound;', 'Gibraltar Pound'),
			(54, 'GTQ', 'Q', 'Quetzal'),
			(55, 'GBP', '&pound;', 'Pound Sterling'),
			(56, 'GNF', 'GNF', 'Guinea Franc'),
			(57, 'GYD', '$', 'Guyana Dollar'),
			(58, 'HNL', 'L', 'Lempira'),
			(59, 'HKD', '$', 'Hong Kong Dollar'),
			(60, 'HUF', 'Ft', 'Forint'),
			(61, 'ISK', 'kr', 'Iceland Krona'),
			(62, 'INR', 'Rs', 'Indian Rupee'),
			(63, 'IDR', 'Rp', 'Rupiah'),
			(64, 'IRR', '&#xfdfc;', 'Iranian Rial'),
			(65, 'IQD', 'IQD', 'Iraqi Dinar'),
			(66, 'ILS', '&#x20aa;', 'New Israeli Sheqel'),
			(67, 'JMD', 'J$', 'Jamaican Dollar'),
			(68, 'JPY', '&yen;', 'Yen'),
			(69, 'JOD', 'JOD', 'Jordanian Dinar'),
			(70, 'KZT', '&#x43b;&#x432;', 'Tenge'),
			(71, 'KES', 'KES', 'Kenyan Shilling'),
			(72, 'KPW', '&#x20a9;', 'North Korean Won'),
			(73, 'KRW', '&#x20a9;', 'Won'),
			(74, 'KWD', 'KWD', 'Kuwaiti Dinar'),
			(75, 'KGS', '&#x43b;&#x432;', 'Som'),
			(76, 'LAK', '&#x20ad;', 'Kip'),
			(77, 'LVL', 'Ls', 'Latvian Lats'),
			(78, 'LBP', '&pound;', 'Lebanese Pound'),
			(79, 'LRD', '$', 'Liberian Dollar'),
			(80, 'LYD', 'LYD', 'Libyan Dinar'),
			(81, 'CHF', 'CHF', 'Swiss Franc'),
			(82, 'LTL', 'Lt', 'Lithuanian Litas'),
			(83, 'MOP', 'MOP', 'Pataca'),
			(84, 'MKD', '&#x434;&#x435;&#x43d;', 'Denar'),
			(85, 'MGA', 'MGA', 'Malagasy Ariary'),
			(86, 'MWK', 'MWK', 'Kwacha'),
			(87, 'MYR', 'RM', 'Malaysian Ringgit'),
			(88, 'MVR', 'MVR', 'Rufiyaa'),
			(89, 'MRO', 'MRO', 'Ouguiya'),
			(90, 'MUR', 'Rs', 'Mauritius Rupee'),
			(91, 'MDL', 'MDL', 'Moldovan Leu'),
			(92, 'MNT', '&#x20ae;', 'Tugrik'),
			(93, 'MAD', 'MAD', 'Moroccan Dirham'),
			(94, 'MZN', 'MT', 'Metical'),
			(95, 'MMK', 'MMK', 'Kyat'),
			(96, 'NPR', 'Rs', 'Nepalese Rupee'),
			(97, 'ANG', '&fnof;', 'Netherlands Antillian Guilder'),
			(98, 'NIO', 'C$', 'Cordoba Oro'),
			(99, 'NGN', '&#x20a6;', 'Naira'),
			(100, 'OMR', '&#xfdfc;', 'Rial Omani'),
			(101, 'PKR', 'Rs', 'Pakistan Rupee'),
			(102, 'PGK', 'PGK', 'Kina'),
			(103, 'PYG', 'Gs', 'Guarani'),
			(104, 'PEN', 'S/.', 'Nuevo Sol'),
			(105, 'PHP', 'Php', 'Philippine Peso'),
			(106, 'PLN', 'z&#x142;', 'Zloty'),
			(107, 'QAR', '&#xfdfc;', 'Qatari Rial'),
			(108, 'RON', 'lei', 'New Leu'),
			(109, 'RUB', '&#x440;&#x443;&#x431;', 'Russian Ruble'),
			(110, 'RWF', 'RWF', 'Rwanda Franc'),
			(111, 'SHP', '&pound;', 'Saint Helena Pound'),
			(112, 'WST', 'WST', 'Tala'),
			(113, 'STD', 'STD', 'Dobra'),
			(114, 'SAR', '&#xfdfc;', 'Saudi Riyal'),
			(115, 'RSD', '&#x414;&#x438;&#x43d;.', 'Serbian Dinar'),
			(116, 'SCR', 'Rs', 'Seychelles Rupee'),
			(117, 'SLL', 'SLL', 'Leone'),
			(118, 'SGD', '$', 'Singapore Dollar'),
			(119, 'SBD', '$', 'Solomon Islands Dollar'),
			(120, 'SOS', 'S', 'Somali Shilling'),
			(121, 'ZAR', 'R', 'Rand'),
			(122, 'LKR', 'Rs', 'Sri Lanka Rupee'),
			(123, 'SDG', 'SDG', 'Sudanese Pound'),
			(124, 'SRD', '$', 'Surinam Dollar'),
			(125, 'SZL', 'SZL', 'Lilangeni'),
			(126, 'SEK', 'kr', 'Swedish Krona'),
			(127, 'SYP', '&pound;', 'Syrian Pound'),
			(128, 'TWD', '$', 'New Taiwan Dollar'),
			(129, 'TJS', 'TJS', 'Somoni'),
			(130, 'TZS', 'TZS', 'Tanzanian Shilling'),
			(131, 'THB', 'THB', 'Baht'),
			(132, 'TOP', 'TOP', 'Pa'),
			(133, 'TTD', 'TT$', 'Trinidad and Tobago Dollar'),
			(134, 'TND', 'TND', 'Tunisian Dinar'),
			(135, 'TRY', 'TL', 'Turkish Lira'),
			(136, 'TMT', 'TMT', 'Manat'),
			(137, 'UGX', 'UGX', 'Uganda Shilling'),
			(138, 'UAH', '&#x20b4;', 'Hryvnia'),
			(139, 'AED', 'AED', 'UAE Dirham'),
			(140, 'UZS', '&#x43b;&#x432;', 'Uzbekistan Sum'),
			(141, 'VUV', 'VUV', 'Vatu'),
			(142, 'VEF', 'Bs', 'Bolivar Fuerte'),
			(143, 'VND', '&#x20ab;', 'Dong'),
			(144, 'YER', '&#xfdfc;', 'Yemeni Rial'),
			(145, 'ZMK', 'ZMK', 'Zambian Kwacha'),
			(146, 'ZWL', 'Z$', 'Zimbabwe Dollar'),
			(147, 'XAU', 'XAU', 'Gold'),
			(148, 'XBA', 'XBA', 'EURCO'),
			(149, 'XBB', 'XBB', 'European Monetary Unit'),
			(150, 'XBC', 'XBC', 'European Unit of Account 9'),
			(151, 'XBD', 'XBD', 'European Unit of Account 17'),
			(152, 'XDR', 'XDR', 'SDR'),
			(153, 'XPD', 'XPD', 'Palladium'),
			(154, 'XPT', 'XPT', 'Platinum'),
			(155, 'XAG', 'XAG', 'Silver'),
			(156, 'COP', '$', 'Colombian peso'),
			(157, 'CUP', '$', 'Cuban peso'),
			(158, 'SVC', 'SVC', 'Salvadoran colon'),
			(159, 'CLP', '$', 'Chilean peso'),
			(160, 'HTG', 'G', 'Haitian gourde'),
			(161, 'MXN', '$', 'Mexican peso'),
			(162, 'PAB', 'PAB', 'Panamanian balboa'),
			(163, 'UYU', '$', 'Uruguayan peso')
			" );
	task_end();
}

/**
 * Create default countries with relations to currencies
 *
 */
function create_default_countries( $table_name = 'T_regional__country', $set_preferred_country = true )
{
	global $DB, $current_locale;

	task_begin( 'Creating default countries... ' );
	$DB->query( "
		INSERT INTO $table_name ( ctry_ID, ctry_code, ctry_name, ctry_curr_ID)
		VALUES
			(1, 'af', 'Afghanistan', 1),
			(2, 'ax', 'Aland Islands', 2),
			(3, 'al', 'Albania', 3),
			(4, 'dz', 'Algeria', 4),
			(5, 'as', 'American Samoa', 5),
			(6, 'ad', 'Andorra', 2),
			(7, 'ao', 'Angola', 6),
			(8, 'ai', 'Anguilla', 7),
			(9, 'aq', 'Antarctica', NULL),
			(10, 'ag', 'Antigua And Barbuda', 7),
			(11, 'ar', 'Argentina', 8),
			(12, 'am', 'Armenia', 9),
			(13, 'aw', 'Aruba', 10),
			(14, 'au', 'Australia', 11),
			(15, 'at', 'Austria', 2),
			(16, 'az', 'Azerbaijan', 12),
			(17, 'bs', 'Bahamas', 13),
			(18, 'bh', 'Bahrain', 14),
			(19, 'bd', 'Bangladesh', 15),
			(20, 'bb', 'Barbados', 16),
			(21, 'by', 'Belarus', 17),
			(22, 'be', 'Belgium', 2),
			(23, 'bz', 'Belize', 18),
			(24, 'bj', 'Benin', 19),
			(25, 'bm', 'Bermuda', 20),
			(26, 'bt', 'Bhutan', 62),
			(27, 'bo', 'Bolivia', NULL),
			(28, 'ba', 'Bosnia And Herzegovina', 21),
			(29, 'bw', 'Botswana', 22),
			(30, 'bv', 'Bouvet Island', 23),
			(31, 'br', 'Brazil', 24),
			(32, 'io', 'British Indian Ocean Territory', 5),
			(33, 'bn', 'Brunei Darussalam', 25),
			(34, 'bg', 'Bulgaria', 26),
			(35, 'bf', 'Burkina Faso', 19),
			(36, 'bi', 'Burundi', 27),
			(37, 'kh', 'Cambodia', 28),
			(38, 'cm', 'Cameroon', 29),
			(39, 'ca', 'Canada', 30),
			(40, 'cv', 'Cape Verde', 31),
			(41, 'ky', 'Cayman Islands', 32),
			(42, 'cf', 'Central African Republic', 29),
			(43, 'td', 'Chad', 29),
			(44, 'cl', 'Chile', 159),
			(45, 'cn', 'China', 33),
			(46, 'cx', 'Christmas Island', 11),
			(47, 'cc', 'Cocos Islands', 11),
			(48, 'co', 'Colombia', 156),
			(49, 'km', 'Comoros', 34),
			(50, 'cg', 'Congo', 29),
			(51, 'cd', 'Congo Republic', 35),
			(52, 'ck', 'Cook Islands', 36),
			(53, 'cr', 'Costa Rica', 37),
			(54, 'ci', 'Cote Divoire', 19),
			(55, 'hr', 'Croatia', 38),
			(56, 'cu', 'Cuba', 157),
			(57, 'cy', 'Cyprus', 2),
			(58, 'cz', 'Czech Republic', 39),
			(59, 'dk', 'Denmark', 40),
			(60, 'dj', 'Djibouti', 41),
			(61, 'dm', 'Dominica', 7),
			(62, 'do', 'Dominican Republic', 42),
			(63, 'ec', 'Ecuador', 5),
			(64, 'eg', 'Egypt', 43),
			(65, 'sv', 'El Salvador', 158),
			(66, 'gq', 'Equatorial Guinea', 29),
			(67, 'er', 'Eritrea', 44),
			(68, 'ee', 'Estonia', 45),
			(69, 'et', 'Ethiopia', 46),
			(70, 'fk', 'Falkland Islands (Malvinas)', 47),
			(71, 'fo', 'Faroe Islands', 40),
			(72, 'fj', 'Fiji', 48),
			(73, 'fi', 'Finland', 2),
			(74, 'fr', 'France', 2),
			(75, 'gf', 'French Guiana', 2),
			(76, 'pf', 'French Polynesia', 49),
			(77, 'tf', 'French Southern Territories', 2),
			(78, 'ga', 'Gabon', 29),
			(79, 'gm', 'Gambia', 50),
			(80, 'ge', 'Georgia', 51),
			(81, 'de', 'Germany', 2),
			(82, 'gh', 'Ghana', 52),
			(83, 'gi', 'Gibraltar', 53),
			(84, 'gr', 'Greece', 2),
			(85, 'gl', 'Greenland', 40),
			(86, 'gd', 'Grenada', 7),
			(87, 'gp', 'Guadeloupe', 2),
			(88, 'gu', 'Guam', 5),
			(89, 'gt', 'Guatemala', 54),
			(90, 'gg', 'Guernsey', 55),
			(91, 'gn', 'Guinea', 56),
			(92, 'gw', 'Guinea-bissau', 19),
			(93, 'gy', 'Guyana', 57),
			(94, 'ht', 'Haiti', 160),
			(95, 'hm', 'Heard Island And Mcdonald Islands', 11),
			(96, 'va', 'Holy See (vatican City State)', 2),
			(97, 'hn', 'Honduras', 58),
			(98, 'hk', 'Hong Kong', 59),
			(99, 'hu', 'Hungary', 60),
			(100, 'is', 'Iceland', 61),
			(101, 'in', 'India', 62),
			(102, 'id', 'Indonesia', 63),
			(103, 'ir', 'Iran', 64),
			(104, 'iq', 'Iraq', 65),
			(105, 'ie', 'Ireland', 2),
			(106, 'im', 'Isle Of Man', NULL),
			(107, 'il', 'Israel', 66),
			(108, 'it', 'Italy', 2),
			(109, 'jm', 'Jamaica', 67),
			(110, 'jp', 'Japan', 68),
			(111, 'je', 'Jersey', 55),
			(112, 'jo', 'Jordan', 69),
			(113, 'kz', 'Kazakhstan', 70),
			(114, 'ke', 'Kenya', 71),
			(115, 'ki', 'Kiribati', 11),
			(116, 'kp', 'Korea', 72),
			(117, 'kr', 'Korea', 73),
			(118, 'kw', 'Kuwait', 74),
			(119, 'kg', 'Kyrgyzstan', 75),
			(120, 'la', 'Lao', 76),
			(121, 'lv', 'Latvia', 77),
			(122, 'lb', 'Lebanon', 78),
			(123, 'ls', 'Lesotho', 121),
			(124, 'lr', 'Liberia', 79),
			(125, 'ly', 'Libyan Arab Jamahiriya', 80),
			(126, 'li', 'Liechtenstein', 81),
			(127, 'lt', 'Lithuania', 82),
			(128, 'lu', 'Luxembourg', 2),
			(129, 'mo', 'Macao', 83),
			(130, 'mk', 'Macedonia', 84),
			(131, 'mg', 'Madagascar', 85),
			(132, 'mw', 'Malawi', 86),
			(133, 'my', 'Malaysia', 87),
			(134, 'mv', 'Maldives', 88),
			(135, 'ml', 'Mali', 19),
			(136, 'mt', 'Malta', 2),
			(137, 'mh', 'Marshall Islands', 5),
			(138, 'mq', 'Martinique', 2),
			(139, 'mr', 'Mauritania', 89),
			(140, 'mu', 'Mauritius', 90),
			(141, 'yt', 'Mayotte', 2),
			(142, 'mx', 'Mexico', 161),
			(143, 'fm', 'Micronesia', 2),
			(144, 'md', 'Moldova', 91),
			(145, 'mc', 'Monaco', 2),
			(146, 'mn', 'Mongolia', 92),
			(147, 'me', 'Montenegro', 2),
			(148, 'ms', 'Montserrat', 7),
			(149, 'ma', 'Morocco', 93),
			(150, 'mz', 'Mozambique', 94),
			(151, 'mm', 'Myanmar', 95),
			(152, 'na', 'Namibia', 121),
			(153, 'nr', 'Nauru', 11),
			(154, 'np', 'Nepal', 96),
			(155, 'nl', 'Netherlands', 2),
			(156, 'an', 'Netherlands Antilles', 97),
			(157, 'nc', 'New Caledonia', 49),
			(158, 'nz', 'New Zealand', 36),
			(159, 'ni', 'Nicaragua', 98),
			(160, 'ne', 'Niger', 19),
			(161, 'ng', 'Nigeria', 99),
			(162, 'nu', 'Niue', 36),
			(163, 'nf', 'Norfolk Island', 11),
			(164, 'mp', 'Northern Mariana Islands', 5),
			(165, 'no', 'Norway', 23),
			(166, 'om', 'Oman', 100),
			(167, 'pk', 'Pakistan', 101),
			(168, 'pw', 'Palau', 5),
			(169, 'ps', 'Palestinian Territory', NULL),
			(170, 'pa', 'Panama', 162),
			(171, 'pg', 'Papua New Guinea', 102),
			(172, 'py', 'Paraguay', 103),
			(173, 'pe', 'Peru', 104),
			(174, 'ph', 'Philippines', 105),
			(175, 'pn', 'Pitcairn', 36),
			(176, 'pl', 'Poland', 106),
			(177, 'pt', 'Portugal', 2),
			(178, 'pr', 'Puerto Rico', 5),
			(179, 'qa', 'Qatar', 107),
			(180, 're', 'Reunion', 2),
			(181, 'ro', 'Romania', 108),
			(182, 'ru', 'Russian Federation', 109),
			(183, 'rw', 'Rwanda', 110),
			(184, 'bl', 'Saint Barthelemy', 2),
			(185, 'sh', 'Saint Helena', 111),
			(186, 'kn', 'Saint Kitts And Nevis', 7),
			(187, 'lc', 'Saint Lucia', 7),
			(188, 'mf', 'Saint Martin', 2),
			(189, 'pm', 'Saint Pierre And Miquelon', 2),
			(190, 'vc', 'Saint Vincent And The Grenadines', 7),
			(191, 'ws', 'Samoa', 112),
			(192, 'sm', 'San Marino', 2),
			(193, 'st', 'Sao Tome And Principe', 113),
			(194, 'sa', 'Saudi Arabia', 114),
			(195, 'sn', 'Senegal', 19),
			(196, 'rs', 'Serbia', 115),
			(197, 'sc', 'Seychelles', 116),
			(198, 'sl', 'Sierra Leone', 117),
			(199, 'sg', 'Singapore', 118),
			(200, 'sk', 'Slovakia', 2),
			(201, 'si', 'Slovenia', 2),
			(202, 'sb', 'Solomon Islands', 119),
			(203, 'so', 'Somalia', 120),
			(204, 'za', 'South Africa', 121),
			(205, 'gs', 'South Georgia', NULL),
			(206, 'es', 'Spain', 2),
			(207, 'lk', 'Sri Lanka', 122),
			(208, 'sd', 'Sudan', 123),
			(209, 'sr', 'Suriname', 124),
			(210, 'sj', 'Svalbard And Jan Mayen', 23),
			(211, 'sz', 'Swaziland', 125),
			(212, 'se', 'Sweden', 126),
			(213, 'ch', 'Switzerland', 81),
			(214, 'sy', 'Syrian Arab Republic', 127),
			(215, 'tw', 'Taiwan, Province Of China', 128),
			(216, 'tj', 'Tajikistan', 129),
			(217, 'tz', 'Tanzania', 130),
			(218, 'th', 'Thailand', 131),
			(219, 'tl', 'Timor-leste', 5),
			(220, 'tg', 'Togo', 19),
			(221, 'tk', 'Tokelau', 36),
			(222, 'to', 'Tonga', 132),
			(223, 'tt', 'Trinidad And Tobago', 133),
			(224, 'tn', 'Tunisia', 134),
			(225, 'tr', 'Turkey', 135),
			(226, 'tm', 'Turkmenistan', 136),
			(227, 'tc', 'Turks And Caicos Islands', 5),
			(228, 'tv', 'Tuvalu', 11),
			(229, 'ug', 'Uganda', 137),
			(230, 'ua', 'Ukraine', 138),
			(231, 'ae', 'United Arab Emirates', 139),
			(232, 'gb', 'United Kingdom', 55),
			(233, 'us', 'United States', 5),
			(234, 'um', 'United States Minor Outlying Islands', 5),
			(235, 'uy', 'Uruguay', 163),
			(236, 'uz', 'Uzbekistan', 140),
			(237, 'vu', 'Vanuatu', 141),
			(239, 've', 'Venezuela', 142),
			(240, 'vn', 'Viet Nam', 143),
			(241, 'vg', 'Virgin Islands, British', 5),
			(242, 'vi', 'Virgin Islands, U.s.', 5),
			(243, 'wf', 'Wallis And Futuna', 49),
			(244, 'eh', 'Western Sahara', 93),
			(245, 'ye', 'Yemen', 144),
			(246, 'zm', 'Zambia', 145),
			(247, 'zw', 'Zimbabwe', 146),
			(248, 'ct', 'Catalonia', 2)" );

	if( $set_preferred_country && !empty( $current_locale ) )
	{	// Set default preferred country from current locale
		$result = array();
		preg_match('#.*?-(.*)#', strtolower($current_locale),$result);

		$DB->query( "UPDATE $table_name
			SET ctry_preferred = 1, ctry_status = 'trusted'
			WHERE ctry_code = '".$DB->escape($result[1])."'" );
	}
	task_end();
}

/**
 * Create default regions
 *
 */
function create_default_regions()
{
	global $DB, $current_charset;

	task_begin( 'Creating default regions... ' );
	$DB->query( convert_charset("
		INSERT INTO T_regional__region ( rgn_ID, rgn_ctry_ID, rgn_code, rgn_name )
		VALUES".
			/* United States */"
			(1, 233, 'AL', 'Alabama'),
			(2, 233, 'AK', 'Alaska'),
			(3, 233, 'AZ', 'Arizona'),
			(4, 233, 'AR', 'Arkansas'),
			(5, 233, 'CA', 'California'),
			(6, 233, 'CO', 'Colorado'),
			(7, 233, 'CT', 'Connecticut'),
			(8, 233, 'DE', 'Delaware'),
			(9, 233, 'FL', 'Florida'),
			(10, 233, 'GA', 'Georgia'),
			(11, 233, 'HI', 'Hawaii'),
			(12, 233, 'ID', 'Idaho'),
			(13, 233, 'IL', 'Illinois'),
			(14, 233, 'IN', 'Indiana'),
			(15, 233, 'IA', 'Iowa'),
			(16, 233, 'KS', 'Kansas'),
			(17, 233, 'KY', 'Kentucky'),
			(18, 233, 'LA', 'Louisiana'),
			(19, 233, 'ME', 'Maine'),
			(20, 233, 'MD', 'Maryland'),
			(21, 233, 'MA', 'Massachusetts'),
			(22, 233, 'MI', 'Michigan'),
			(23, 233, 'MN', 'Minnesota'),
			(24, 233, 'MS', 'Mississippi'),
			(25, 233, 'MO', 'Missouri'),
			(26, 233, 'MT', 'Montana'),
			(27, 233, 'NE', 'Nebraska'),
			(28, 233, 'NV', 'Nevada'),
			(29, 233, 'NH', 'New Hampshire'),
			(30, 233, 'NJ', 'New Jersey'),
			(31, 233, 'NM', 'New Mexico'),
			(32, 233, 'NY', 'New York'),
			(33, 233, 'NC', 'North Carolina'),
			(34, 233, 'ND', 'North Dakota'),
			(35, 233, 'OH', 'Ohio'),
			(36, 233, 'OK', 'Oklahoma'),
			(37, 233, 'OR', 'Oregon'),
			(38, 233, 'PA', 'Pennsylvania'),
			(39, 233, 'RI', 'Rhode Island'),
			(40, 233, 'SC', 'South Carolina'),
			(41, 233, 'SD', 'South Dakota'),
			(42, 233, 'TN', 'Tennessee'),
			(43, 233, 'TX', 'Texas'),
			(44, 233, 'UT', 'Utah'),
			(45, 233, 'VT', 'Vermont'),
			(46, 233, 'VA', 'Virginia'),
			(47, 233, 'WA', 'Washington'),
			(48, 233, 'WV', 'West Virginia'),
			(49, 233, 'WI', 'Wisconsin'),
			(50, 233, 'WY', 'Wyoming'),".
			/* France */"
			(51, 74, '42', 'Alsace'),
			(52, 74, '72', 'Aquitaine'),
			(53, 74, '83', 'Auvergne'),
			(54, 74, '26', 'Bourgogne'),
			(55, 74, '53', 'Bretagne'),
			(56, 74, '24', 'Centre'),
			(57, 74, '21', 'Champagne-Ardenne'),
			(58, 74, '94', 'Corse'),
			(59, 74, '43', 'Franche-Comt\xE9'),
			(60, 74, '11', '\xCEle-de-France'),
			(61, 74, '91', 'Languedoc-Roussillon'),
			(62, 74, '74', 'Limousin'),
			(63, 74, '41', 'Lorraine'),
			(64, 74, '73', 'Midi-Pyr\xE9n\xE9es'),
			(65, 74, '31', 'Nord-Pas-de-Calais'),
			(66, 74, '25', 'Basse-Normandie'),
			(67, 74, '23', 'Haute-Normandie'),
			(68, 74, '52', 'Pays de la Loire'),
			(69, 74, '22', 'Picardie'),
			(70, 74, '54', 'Poitou-Charentes'),
			(71, 74, '93', 'Provence-Alpes-C\xF4te d\'Azur'),
			(72, 74, '82', 'Rh\xF4ne-Alpes'),
			(73, 74, '01', 'Guadeloupe'),
			(74, 74, '02', 'Martinique'),
			(75, 74, '03', 'Guyane'),
			(76, 74, '04', 'La R\xE9union'),
			(77, 74, '05', 'Mayotte'),
			(78, 74, '09', 'Outre-Mer'),
			(79, 74, '99', 'Monaco')", $current_charset, 'iso-8859-1' ) );

	task_end();
}

/**
 * Create default sub-regions
 *
 */
function create_default_subregions()
{
	global $DB, $current_charset;

	task_begin( 'Creating default sub-regions... ' );
	$DB->query( convert_charset("
		INSERT INTO T_regional__subregion ( subrg_ID, subrg_rgn_ID, subrg_code, subrg_name )
		VALUES".
			/* France */"
			(1, 72, '01', 'Ain'),
			(2, 69, '02', 'Aisne'),
			(3, 53, '03', 'Allier'),
			(4, 71, '04', 'Alpes-de-Haute-Provence'),
			(5, 71, '05', 'Hautes-Alpes'),
			(6, 71, '06', 'Alpes-Maritimes'),
			(7, 72, '07', 'Ard\xE8che'),
			(8, 57, '08', 'Ardennes'),
			(9, 64, '09', 'Ari\xE8ge'),
			(10, 57, '10', 'Aube'),
			(11, 61, '11', 'Aude'),
			(12, 64, '12', 'Aveyron'),
			(13, 71, '13', 'Bouches-du-Rh\xF4ne'),
			(14, 66, '14', 'Calvados'),
			(15, 53, '15', 'Cantal'),
			(16, 70, '16', 'Charente'),
			(17, 70, '17', 'Charente-Maritime'),
			(18, 56, '18', 'Cher'),
			(19, 62, '19', 'Corr\xE8ze'),
			(20, 58, '2A', 'Corse-du-Sud'),
			(21, 58, '2B', 'Haute-Corse'),
			(22, 54, '21', 'C\xF4te-d\'Or'),
			(23, 55, '22', 'C\xF4tes-d\'Armor'),
			(24, 62, '23', 'Creuse'),
			(25, 52, '24', 'Dordogne'),
			(26, 59, '25', 'Doubs'),
			(27, 72, '26', 'Dr\xF4me'),
			(28, 67, '27', 'Eure'),
			(29, 56, '28', 'Eure-et-Loir'),
			(30, 55, '29', 'Finist\xE8re'),
			(31, 61, '30', 'Gard'),
			(32, 64, '31', 'Haute-Garonne'),
			(33, 64, '32', 'Gers'),
			(34, 52, '33', 'Gironde'),
			(35, 61, '34', 'H\xE9rault'),
			(36, 55, '35', 'Ille-et-Vilaine'),
			(37, 56, '36', 'Indre'),
			(38, 56, '37', 'Indre-et-Loire'),
			(39, 72, '38', 'Is\xE8re'),
			(40, 59, '39', 'Jura'),
			(41, 52, '40', 'Landes'),
			(42, 56, '41', 'Loir-et-Cher'),
			(43, 72, '42', 'Loire'),
			(44, 53, '43', 'Haute-Loire'),
			(45, 68, '44', 'Loire-Atlantique'),
			(46, 56, '45', 'Loiret'),
			(47, 64, '46', 'Lot'),
			(48, 52, '47', 'Lot-et-Garonne'),
			(49, 61, '48', 'Loz\xE8re'),
			(50, 68, '49', 'Maine-et-Loire'),
			(51, 66, '50', 'Manche'),
			(52, 57, '51', 'Marne'),
			(53, 57, '52', 'Haute-Marne'),
			(54, 68, '53', 'Mayenne'),
			(55, 63, '54', 'Meurthe-et-Moselle'),
			(56, 63, '55', 'Meuse'),
			(57, 55, '56', 'Morbihan'),
			(58, 63, '57', 'Moselle'),
			(59, 54, '58', 'Ni\xE8vre'),
			(60, 65, '59', 'Nord'),
			(61, 69, '60', 'Oise'),
			(62, 66, '61', 'Orne'),
			(63, 65, '62', 'Pas-de-Calais'),
			(64, 53, '63', 'Puy-de-D\xF4me'),
			(65, 52, '64', 'Pyr\xE9n\xE9es-Atlantiques'),
			(66, 64, '65', 'Hautes-Pyr\xE9n\xE9es'),
			(67, 61, '66', 'Pyr\xE9n\xE9es-Orientales'),
			(68, 51, '67', 'Bas-Rhin'),
			(69, 51, '68', 'Haut-Rhin'),
			(70, 72, '69', 'Rh\xF4ne'),
			(71, 59, '70', 'Haute-Sa\xF4ne'),
			(72, 54, '71', 'Sa\xF4ne-et-Loire'),
			(73, 68, '72', 'Sarthe'),
			(74, 72, '73', 'Savoie'),
			(75, 72, '74', 'Haute-Savoie'),
			(76, 60, '75', 'Paris'),
			(77, 67, '76', 'Seine-Maritime'),
			(78, 60, '77', 'Seine-et-Marne'),
			(79, 60, '78', 'Yvelines'),
			(80, 70, '79', 'Deux-S\xE8vres'),
			(81, 69, '80', 'Somme'),
			(82, 64, '81', 'Tarn'),
			(83, 64, '82', 'Tarn-et-Garonne'),
			(84, 71, '83', 'Var'),
			(85, 71, '84', 'Vaucluse'),
			(86, 68, '85', 'Vend\xE9e'),
			(87, 70, '86', 'Vienne'),
			(88, 62, '87', 'Haute-Vienne'),
			(89, 63, '88', 'Vosges'),
			(90, 54, '89', 'Yonne'),
			(91, 59, '90', 'Territoire de Belfort'),
			(92, 60, '91', 'Essonne'),
			(93, 60, '92', 'Hauts-de-Seine'),
			(94, 60, '93', 'Seine-Saint-Denis'),
			(95, 60, '94', 'Val-de-Marne'),
			(96, 60, '95', 'Val-d\'Oise'),
			(97, 73, '971', 'Guadeloupe'),
			(98, 74, '972', 'Martinique'),
			(99, 75, '973', 'Guyane'),
			(100, 76, '974', 'La R\xE9union'),
			(101, 77, '976', 'Mayotte'),
			(102, 78, '975', 'Saint-Pierre-et-Miquelon'),
			(103, 78, '986', 'Wallis-et-Futuna'),
			(104, 78, '987', 'Polyn\xE9sie fran\xE7aise'),
			(105, 78, '988', 'Nouvelle-Cal\xE9donie'),
			(106, 79, '99', 'Monaco')", $current_charset, 'iso-8859-1') );

	task_end();
}

/**
 * Create default scheduled jobs that don't exist yet:
 * - Prune page cache
 * - Prune hit log & session log from stats
 * - Poll antispam blacklist
 *
 * @param boolean true if it's called from the ugrade script, false if it's called from the install script
 */
function create_default_jobs( $is_upgrade = false )
{
	global $DB, $localtimenow;

	// get tomorrow date
	$date = date2mysql( $localtimenow + 86400 );
	$ctsk_params = $DB->quote( 'N;' );
	$next_sunday = date2mysql( strtotime( 'next Sunday',  $localtimenow + 86400 ) );

	$cleanup_jobs_key         = 'cleanup-scheduled-jobs';
	$heavy_db_maintenance_key = 'heavy-db-maintenance';
	$light_db_maintenance_key = 'light-db-maintenance';
	$poll_antispam_key        = 'poll-antispam-blacklist';
	$process_hitlog_key       = 'process-hit-log';
	$prune_pagecache_key      = 'prune-old-files-from-page-cache';
	$prune_sessions_key       = 'prune-old-hits-and-sessions';
	$prune_comments_key       = 'prune-recycled-comments';
	$activate_reminder_key    = 'send-non-activated-account-reminders';
	$comment_reminder_key     = 'send-unmoderated-comments-reminders';
	$messages_reminder_key    = 'send-unread-messages-reminders';
	$post_reminder_key        = 'send-unmoderated-posts-reminders';

	// init insert values
	$insert_values = array(
			// run unread messages reminder in every 29 minutes
			$messages_reminder_key    => "( ".$DB->quote( form_date( $date, '01:00:00' ) ).", 1740,  ".$DB->quote( $messages_reminder_key ).", ".$ctsk_params." )",
			// run activate account reminder in every 31 minutes
			$activate_reminder_key    => "( ".$DB->quote( form_date( $date, '01:30:00' ) ).", 1860,  ".$DB->quote( $activate_reminder_key ).", ".$ctsk_params." )",
			$prune_pagecache_key      => "( ".$DB->quote( form_date( $date, '02:00:00' ) ).", 86400, ".$DB->quote( $prune_pagecache_key ).", ".$ctsk_params." )",
			$process_hitlog_key       => "( ".$DB->quote( form_date( $date, '02:30:00' ) ).", 86400, ".$DB->quote( $process_hitlog_key ).", ".$ctsk_params." )",
			$prune_sessions_key       => "( ".$DB->quote( form_date( $date, '03:00:00' ) ).", 86400, ".$DB->quote( $prune_sessions_key ).", ".$ctsk_params." )",
			$poll_antispam_key        => "( ".$DB->quote( form_date( $date, '04:00:00' ) ).", 86400, ".$DB->quote( $poll_antispam_key ).", ".$ctsk_params." )",
			$comment_reminder_key     => "( ".$DB->quote( form_date( $date, '04:30:00' ) ).", 86400, ".$DB->quote( $comment_reminder_key ).", ".$ctsk_params." )",
			$cleanup_jobs_key         => "( ".$DB->quote( form_date( $date, '05:00:00' ) ).", 86400, ".$DB->quote( $cleanup_jobs_key ).", ".$ctsk_params." )",
			$prune_comments_key       => "( ".$DB->quote( form_date( $date, '05:30:00' ) ).", 86400, ".$DB->quote( $prune_comments_key ).", ".$ctsk_params." )",
			$light_db_maintenance_key => "( ".$DB->quote( form_date( $date, '06:00:00' ) ).", 86400, ".$DB->quote( $light_db_maintenance_key ).", ".$ctsk_params." )",
			$heavy_db_maintenance_key => "( ".$DB->quote( form_date( $next_sunday, '06:30:00' ) ).", 604800, ".$DB->quote( $heavy_db_maintenance_key ).", ".$ctsk_params." )",
			$post_reminder_key        => "( ".$DB->quote( form_date( $date, '07:00:00' ) ).", 86400, ".$DB->quote( $post_reminder_key ).", ".$ctsk_params." )",
		);
	if( $is_upgrade )
	{ // Check if these jobs already exist, and don't create another
		$SQL = new SQL();
		$SQL->SELECT( 'COUNT( ctsk_ID ) AS job_number, ctsk_key' );
		$SQL->FROM( 'T_cron__task' );
		$SQL->FROM_add( 'LEFT JOIN T_cron__log ON ctsk_ID = clog_ctsk_ID' );
		$SQL->WHERE( 'clog_status IS NULL' );
		$SQL->WHERE_and( 'ctsk_key IN ( '.$DB->quote( array_keys( $insert_values ) ).' )' );
		$SQL->GROUP_BY( 'ctsk_key' );
		$result = $DB->get_results( $SQL->get() );
		foreach( $result as $row )
		{ // clear existing jobs insert values
			unset( $insert_values[ $row->ctsk_key ] );
		}
	}

	$values = implode( ', ', $insert_values );
	if( empty( $values ) )
	{ // nothing to create
		return;
	}

	task_begin( T_( 'Creating default scheduled jobs... ' ) );
	$DB->query( '
		INSERT INTO T_cron__task ( ctsk_start_datetime, ctsk_repeat_after, ctsk_key, ctsk_params )
		VALUES '.$values, T_( 'Create default scheduled jobs' ) );
	task_end();
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
	$owner_user_ID = 1 )
{
	global $default_locale, $install_test_features, $local_installation, $Plugins;

	$Blog = new Blog( NULL );

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

	$Blog->dbupdate();

	// Insert default group permissions:
	$Blog->insert_default_group_permissions();

	return $Blog->ID;
}

/**
 * Create a new User
 *
 * @param array Params
 * @return integer User ID
 */
function create_user( $params = array() )
{
	global $timestamp;
	global $random_password, $admin_email;
	global $default_locale, $default_country;

	$params = array_merge( array(
			'login'     => '',
			'firstname' => NULL,
			'lastname'  => NULL,
			'pass'    => $random_password, // random
			'email'   => $admin_email,
			'status'  => 'autoactivated', // assume it's active
			'level'   => 0,
			'locale'  => $default_locale,
			'ctry_ID' => $default_country,
			'gender'  => 'M',
			'Group'   => NULL,
			'org_IDs' => NULL, // array of organization IDs
			'fields'  => NULL, // array of additional user fields
		), $params );

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
	$User->set_datecreated( $timestamp++ );
	$User->set_Group( $params['Group'] );
	if( ! $User->dbinsert( false ) )
	{ // Don't continue if user creating has been failed
		return false;
	}

	if( ! empty( $params['org_IDs'] ) )
	{ // Add user to organizations
		$User->update_organizations( $params['org_IDs'], array(), true );
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

	return $User->ID;
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
 * This is called only for fresh installs and fills the tables with
 * demo/tutorial things.
 */
function create_demo_contents()
{
	global $baseurl, $admin_url, $new_db_version;
	global $random_password, $query;
	global $timestamp, $admin_email;
	global $admins_Group, $moderators_Group, $editors_Group, $users_Group, $suspect_Group, $blogb_Group;
	global $blog_all_ID, $blog_home_ID, $blog_a_ID, $blog_b_ID;
	global $DB;
	global $default_locale, $default_country;
	global $Plugins, $Settings;
	global $install_test_features;
	global $user_org_IDs;

	/**
	 * @var FileRootCache
	 */
	global $FileRootCache;

	$lorem_1paragraph = "\n\n<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>";

	$lorem_2more = "\n\n<p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?</p>\n\n"
		."<p>At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus, omnis voluptas assumenda est, omnis dolor repellendus. Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet ut et voluptates repudiandae sint et molestiae non recusandae. Itaque earum rerum hic tenetur a sapiente delectus, ut aut reiciendis voluptatibus maiores alias consequatur aut perferendis doloribus asperiores repellat.</p>";

	load_class( 'collections/model/_blog.class.php', 'Blog' );
	load_class( 'files/model/_file.class.php', 'File' );
	load_class( 'files/model/_filetype.class.php', 'FileType' );
	load_class( 'links/model/_link.class.php', 'Link' );


	task_begin('Assigning avatar to Admin... ');
	$UserCache = & get_UserCache();
	$User_Admin = & $UserCache->get_by_ID( 1 );

	global $media_path;
	$src_admin_dir = $media_path.'users/admin';
	$dest_admin_dir = $media_path.'users/'.$User_Admin->login;
	if( $User_Admin->login != 'admin' )
	{ // If admin login is not "admin" we should try to rename folder of the admin avatars
		if( ! file_exists( $src_admin_dir ) ||
		    ! is_dir( $src_admin_dir ) ||
		    ! @rename( $src_admin_dir, $dest_admin_dir ) )
		{ // Impossible to rename the admin folder to another name

			// Display the errors:
			echo get_install_format_text( '<span class="text-danger"><evo:error>'.sprintf( 'ERROR: Impossible to rename <code>%s</code> to <code>%s</code>.', $src_admin_dir, $dest_admin_dir ).'</evo:error></span> ' );
			echo get_install_format_text( '<span class="text-danger"><evo:error>'.sprintf( 'ERROR: Impossible to use "%s" for the admin account. Using "admin" instead.', $User_Admin->login ).'</evo:error></span> ' );

			// Change admin login to "admin":
			$User_Admin->set( 'login', 'admin' );
			if( $User_Admin->dbupdate() )
			{ // Change global var of admin login for report:
				global $install_login;
				$install_login = 'admin';
			}
		}
	}

	if( file_exists( $media_path.'users/'.$User_Admin->login ) )
	{ // Do assign avatars to admin only if it the admin folder exists on the disk
		assign_profile_picture( $User_Admin, 'admin' );

		// Associate secondary picture:
		$File = new File( 'user', $User_Admin->ID, 'faceyourmanga_admin_boy.png' );
		// Load meta data AND MAKE SURE IT IS CREATED IN DB:
		$File->load_meta( true );
		// Set link between user and avatar file
		$LinkOwner = new LinkUser( $User_Admin );
		$File->link_to_Object( $LinkOwner );

		// Associate secondary picture:
		$File = new File( 'user', $User_Admin->ID, 'faceyourmanga_admin_girl.png' );
		// Load meta data AND MAKE SURE IT IS CREATED IN DB:
		$File->load_meta( true );
		// Set link between user and avatar file
		$LinkOwner = new LinkUser( $User_Admin );
		$File->link_to_Object( $LinkOwner );
	}

	task_end();

	task_begin('Creating demo user mary... ');
	$mary_moderator_ID = create_user( array(
			'login'     => 'mary',
			'firstname' => 'Mary',
			'lastname'  => 'Wilson',
			'level'     => 4,		// NOTE: these levels define the order of display in the Organization memebers widget
			'gender'    => 'F',
			'Group'     => $moderators_Group,
			'org_IDs'   => $user_org_IDs,
			'fields'    => array(
					'Micro bio'   => 'I am a demo moderator for this site.'."\n".'I love it when things are neat!',
					'Website'     => 'http://b2evolution.net/',
					'Twitter'     => 'https://twitter.com/b2evolution/',
					'Facebook'    => 'https://www.facebook.com/b2evolution',
					'Linkedin'    => 'https://www.linkedin.com/company/b2evolution-net',
					'GitHub'      => 'https://github.com/b2evolution/b2evolution',
					'Google Plus' => 'https://plus.google.com/+b2evolution/posts',
				)
		) );
	assign_profile_picture( $UserCache->get_by_ID( $mary_moderator_ID ) );
	task_end();

	task_begin('Creating demo user jay... ');
	$jay_moderator_ID = create_user( array(
			'login'     => 'jay',
			'firstname' => 'Jay',
			'lastname'  => 'Parker',
			'level'     => 3,
			'gender'    => 'M',
			'Group'     => $moderators_Group,
			'org_IDs'   => $user_org_IDs,
			'fields'    => array(
					'Micro bio'   => 'I am a demo moderator for this site.'."\n".'I like to keep things clean!',
					'Website'     => 'http://b2evolution.net/',
					'Twitter'     => 'https://twitter.com/b2evolution/',
					'Facebook'    => 'https://www.facebook.com/b2evolution',
					'Linkedin'    => 'https://www.linkedin.com/company/b2evolution-net',
					'GitHub'      => 'https://github.com/b2evolution/b2evolution',
					'Google Plus' => 'https://plus.google.com/+b2evolution/posts',
				)
		) );
	assign_profile_picture( $UserCache->get_by_ID( $jay_moderator_ID ) );
	task_end();

	task_begin('Creating demo user mark... ');
	$dave_blogger_ID = create_user( array(
			'login'     => 'dave',
			'firstname' => 'David',
			'lastname'  => 'Miller',
			'level'     => 2,
			'gender'    => 'M',
			'Group'     => $editors_Group,
			'org_IDs'   => $user_org_IDs,
			'fields'    => array(
					'Micro bio'   => 'I\'m a demo author.'."\n".'I like to write!',
					'Website'     => 'http://b2evolution.net/',
					'Twitter'     => 'https://twitter.com/b2evolution/',
					'Facebook'    => 'https://www.facebook.com/b2evolution',
					'Linkedin'    => 'https://www.linkedin.com/company/b2evolution-net',
					'GitHub'      => 'https://github.com/b2evolution/b2evolution',
					'Google Plus' => 'https://plus.google.com/+b2evolution/posts',
				)
		) );
	assign_profile_picture( $UserCache->get_by_ID( $dave_blogger_ID ) );
	task_end();

	task_begin('Creating demo user paul... ');
	$paul_blogger_ID = create_user( array(
			'login'     => 'paul',
			'firstname' => 'Paul',
			'lastname'  => 'Jones',
			'level'     => 1,
			'gender'    => 'M',
			'Group'     => $editors_Group,
			'org_IDs'   => $user_org_IDs,
			'fields'    => array(
					'Micro bio'   => 'I\'m a demo author.'."\n".'I like to think before I write ;)',
					'Website'     => 'http://b2evolution.net/',
					'Twitter'     => 'https://twitter.com/b2evolution/',
					'Facebook'    => 'https://www.facebook.com/b2evolution',
					'Linkedin'    => 'https://www.linkedin.com/company/b2evolution-net',
					'GitHub'      => 'https://github.com/b2evolution/b2evolution',
					'Google Plus' => 'https://plus.google.com/+b2evolution/posts',
				)
		) );
	assign_profile_picture( $UserCache->get_by_ID( $paul_blogger_ID ) );
	task_end();

	task_begin('Creating demo user larry... ');
	$larry_user_ID = create_user( array(
			'login'     => 'larry',
			'firstname' => 'Larry',
			'lastname'  => 'Smith',
			'level'     => 0,
			'gender'    => 'M',
			'Group'     => $users_Group,
			'fields'    => array(
					'Micro bio' => 'Hi there!',
				)
		) );
	$larry_User = & $UserCache->get_by_ID( $larry_user_ID );
	assign_profile_picture( $larry_User );
	assign_secondary_groups( $larry_User->ID, array( $blogb_Group->ID ) );
	task_end();

	task_begin('Creating demo user kate... ');
	$kate_user_ID = create_user( array(
			'login'     => 'kate',
			'firstname' => 'Kate',
			'lastname'  => 'Adams',
			'level'     => 0,
			'gender'    => 'F',
			'Group'     => $users_Group,
			'fields'    => array(
					'Micro bio' => 'Just me!',
				)
		) );
	assign_profile_picture( $UserCache->get_by_ID( $kate_user_ID ) );
	task_end();

	// Use only these users to create the demo comments, @see create_demo_comment()
	global $b2evo_demo_comment_users;
	$b2evo_demo_comment_users = array( $larry_user_ID, $kate_user_ID, 0 );

	task_begin( 'Set settings for demo users... ' );
	$DB->query( "
		INSERT INTO T_users__usersettings ( uset_user_ID, uset_name, uset_value )
		VALUES ( 2, 'created_fromIPv4', '".ip2int( '127.0.0.1' )."' ),
				( 2, 'user_domain', 'localhost' ),
				( 3, 'created_fromIPv4', '".ip2int( '127.0.0.1' )."' ),
				( 3, 'user_domain', 'localhost' ),
				( 4, 'created_fromIPv4', '".ip2int( '127.0.0.1' )."' ),
				( 4, 'user_domain', 'localhost' ),
				( 5, 'created_fromIPv4', '".ip2int( '127.0.0.1' )."' ),
				( 5, 'user_domain', 'localhost' ),
				( 6, 'created_fromIPv4', '".ip2int( '127.0.0.1' )."' ),
				( 6, 'user_domain', 'localhost' ),
				( 7, 'created_fromIPv4', '".ip2int( '127.0.0.1' )."' ),
				( 7, 'user_domain', 'localhost' )" );
	task_end();

	global $default_locale, $query, $timestamp;
	global $blog_all_ID, $blog_home_ID, $blog_a_ID, $blog_b_ID, $blog_photoblog_ID, $blog_forums_ID, $blog_manual_ID;

	$default_blog_longdesc = T_('This is the long description for the blog named \'%s\'. %s');
	$default_blog_access_type = 'relative';

	$create_sample_contents = param( 'create_sample_contents', 'string', '' );
	if( $create_sample_contents == 'all' )
	{	// Array contains which collections should be installed
		$install_collection_home =   1;
		$install_collection_bloga =  1;
		$install_collection_blogb =  1;
		$install_collection_photos = 1;
		$install_collection_forums = 1;
		$install_collection_manual = 1;
	}
	else
	{	// Array contains which collections should be installed
		$collections = param( 'collections', 'array:string', array() );
		$install_collection_home = in_array( 'home', $collections );
		$install_collection_bloga = in_array( 'a', $collections );
		$install_collection_blogb = in_array( 'b', $collections );
		$install_collection_photos = in_array( 'photos', $collections );
		$install_collection_forums = in_array( 'forums', $collections );
		$install_collection_manual = in_array( 'manual', $collections );
	}

	task_begin( 'Creating default blogs... ' );

	// Store the item IDs in this array in order to create additional comments
	$additional_comments_item_IDs = array();

	if( $install_collection_home )
	{ // Install Home blog
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
			$jay_moderator_ID );

		if( ! empty( $blog_home_ID ) )
		{ // Save ID of this blog in settings table, It is used on top menu, file "/skins_site/_site_body_header.inc.php"
			$DB->query( 'INSERT INTO T_settings ( set_name, set_value )
				VALUES ( '.$DB->quote( 'info_blog_ID' ).', '.$DB->quote( $blog_home_ID ).' )' );
		}
	}

	if( $install_collection_bloga )
	{ // Install Blog A
		$blog_shortname = 'Blog A';
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
			$jay_moderator_ID );
	}

	if( $install_collection_blogb )
	{ // Install Blog B
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
			$paul_blogger_ID );

		$BlogCache = & get_BlogCache();
		if( $b_Blog = $BlogCache->get_by_ID( $blog_b_ID, false, false ) )
		{
			$b_Blog->set_setting( 'front_disp', 'front' );
			$b_Blog->set_setting( 'skin2_layout', 'single_column' );
			$b_Blog->set( 'advanced_perms', 1 );
			$b_Blog->set_setting( 'allow_access', 'members' );
			$b_Blog->dbupdate();
		}
	}

	if( $install_collection_photos )
	{ // Install Photos blog
		$blog_shortname = 'Photos';
		$blog_stub = 'photos';
		$blog_more_longdesc = '<br />
<br />
<strong>'.T_("This is a photoblog, optimized for displaying photos.").'</strong>';
		$blog_photoblog_ID = create_blog(
			'Photos',
			$blog_shortname,
			$blog_stub,
			T_('This blog shows photos...'),
			sprintf( $default_blog_longdesc, $blog_shortname, $blog_more_longdesc ),
			3, // Skin ID
			'photo', '', 0, 'relative', true, 'public',
			$dave_blogger_ID );
	}

	if( $install_collection_forums )
	{ // Install Forums blog
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
			$paul_blogger_ID );
	}

	if( $install_collection_manual )
	{ // Install Manual blog
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
			$dave_blogger_ID );
	}

	$BlogCache = & get_BlogCache();
	if( $first_Blog = & $BlogCache->get_by_ID( 1, false, false ) )
	{ // Set first blog as default login and default messaging collection
		$DB->query( 'INSERT INTO T_settings ( set_name, set_value )
			VALUES ( '.$DB->quote( 'login_blog_ID' ).', '.$DB->quote( $first_Blog->ID ).' ),
						 ( '.$DB->quote( 'msg_blog_ID' ).', '.$DB->quote( $first_Blog->ID ).' )' );
	}

	task_end();

	global $query, $timestamp;

	task_begin( 'Creating sample categories... ' );

	if( $install_collection_bloga )
	{ // Create categories for blog A
		$cat_ann_a = cat_create( T_('Welcome'), 'NULL', $blog_a_ID );
		$cat_news = cat_create( T_('News'), 'NULL', $blog_a_ID );
		$cat_bg = cat_create( T_('Background'), 'NULL', $blog_a_ID );
		$cat_fun = cat_create( T_('Fun'), 'NULL', $blog_a_ID );
		$cat_life = cat_create( T_('In real life'), $cat_fun, $blog_a_ID );
		$cat_web = cat_create( T_('On the web'), $cat_fun, $blog_a_ID );
		$cat_sports = cat_create( T_('Sports'), $cat_life, $blog_a_ID );
		$cat_movies = cat_create( T_('Movies'), $cat_life, $blog_a_ID );
		$cat_music = cat_create( T_('Music'), $cat_life, $blog_a_ID );

		if( $a_Blog = $BlogCache->get_by_ID( $blog_a_ID, false, false ) )
		{
			$a_Blog->set_setting( 'default_cat_ID', $cat_ann_a );
			$a_Blog->dbupdate();
		}
	}

	if( $install_collection_blogb )
	{ // Create categories for blog B
		$cat_ann_b = cat_create( T_('Announcements'), 'NULL', $blog_b_ID );
		$cat_b2evo = cat_create( T_('b2evolution Tips'), 'NULL', $blog_b_ID );
		$cat_additional_skins = cat_create( T_('Get additional skins'), 'NULL', $blog_b_ID );

		if( $b_Blog = $BlogCache->get_by_ID( $blog_b_ID, false, false ) )
		{
			$b_Blog->set_setting( 'default_cat_ID', $cat_ann_b );
			$b_Blog->dbupdate();
		}
	}

	if( $install_collection_home )
	{ // Create categories for home blog
		$cat_home_b2evo = cat_create( 'b2evolution', 'NULL', $blog_home_ID );
		$cat_home_contrib = cat_create( T_('Contributors'), 'NULL', $blog_home_ID );

		if( $home_Blog = $BlogCache->get_by_ID( $blog_home_ID, false, false ) )
		{
			$home_Blog->set_setting( 'default_cat_ID', $cat_home_b2evo );
			$home_Blog->dbupdate();
		}
	}

	if( $install_collection_photos )
	{ // Create categories for photoblog
		$cat_photo_album = cat_create( T_('Landscapes'), 'NULL', $blog_photoblog_ID );

		if( $photoblog_Blog = $BlogCache->get_by_ID( $blog_photoblog_ID, false, false ) )
		{
			$photoblog_Blog->set_setting( 'default_cat_ID', $cat_photo_album );
			$photoblog_Blog->dbupdate();
		}
	}

	if( $install_collection_forums )
	{ // Create categories for forums
		$cat_forums_forum_group = cat_create( T_('A forum group'), 'NULL', $blog_forums_ID, NULL, false, 1, NULL, true );
			$cat_forums_ann = cat_create( T_('Welcome'), $cat_forums_forum_group, $blog_forums_ID, T_('Welcome description'), false, 1 );
			$cat_forums_aforum = cat_create( T_('A forum'), $cat_forums_forum_group, $blog_forums_ID, T_('Short description of this forum'), false, 2 );
			$cat_forums_anforum = cat_create( T_('Another forum'), $cat_forums_forum_group, $blog_forums_ID, T_('Short description of this forum'), false, 3 );
		$cat_forums_another_group = cat_create( T_('Another group'), 'NULL', $blog_forums_ID, NULL, false, 2, NULL, true );
			$cat_forums_bg = cat_create( T_('Background'), $cat_forums_another_group, $blog_forums_ID, T_('Background description'), false, 1 );
			$cat_forums_news = cat_create( T_('News'), $cat_forums_another_group, $blog_forums_ID, T_('News description'), false, 2 );
			$cat_forums_fun = cat_create( T_('Fun'), $cat_forums_another_group, $blog_forums_ID, T_('Fun description'), false, 3 );
				$cat_forums_life = cat_create( T_('In real life'), $cat_forums_fun, $blog_forums_ID, NULL, false, 4, 'alpha' );
					$cat_forums_movies = cat_create( T_('Movies'), $cat_forums_life, $blog_forums_ID );
					$cat_forums_music = cat_create( T_('Music'), $cat_forums_life, $blog_forums_ID );
					$cat_forums_sports = cat_create( T_('Sports'), $cat_forums_life, $blog_forums_ID );
				$cat_forums_web = cat_create( T_('On the web'), $cat_forums_fun, $blog_forums_ID, NULL, false, 5 );

		if( $forums_Blog = $BlogCache->get_by_ID( $blog_forums_ID, false, false ) )
		{
			$forums_Blog->set_setting( 'default_cat_ID', $cat_forums_forum_group );
			$forums_Blog->dbupdate();
		}
	}

	if( $install_collection_manual )
	{ // Create categories for manual
		$cat_manual_intro = cat_create( T_('Introduction'), NULL, $blog_manual_ID, NULL, false, 10 );
		$cat_manual_getstarted = cat_create( T_('Getting Started'), NULL, $blog_manual_ID, NULL, false, 20 );
		$cat_manual_userguide = cat_create( T_('User Guide'), NULL, $blog_manual_ID, NULL, false, 30 );
		$cat_manual_reference = cat_create( T_('Reference'), NULL, $blog_manual_ID, NULL, false, 40, 'alpha' );

		$cat_manual_everyday = cat_create( T_('Collections'), $cat_manual_reference, $blog_manual_ID, NULL, false, 10 );
		$cat_manual_advanced = cat_create( T_('Other'), $cat_manual_reference, $blog_manual_ID, NULL, false, 5 );

		$cat_manual_blogs = cat_create( T_('Blogs'), $cat_manual_everyday, $blog_manual_ID, NULL, false, 35 );
		$cat_manual_photos = cat_create( T_('Photo Albums'), $cat_manual_everyday, $blog_manual_ID, NULL, false, 25 );
		$cat_manual_forums = cat_create( T_('Forums'), $cat_manual_everyday, $blog_manual_ID, NULL, false, 5 );

		if( $manual_Blog = $BlogCache->get_by_ID( $blog_manual_ID, false, false ) )
		{
			$manual_Blog->set_setting( 'default_cat_ID', $cat_manual_intro );
			$manual_Blog->dbupdate();
		}
	}

	task_end();


	$info_page = T_('<p>This website is powered by b2evolution.</p>

<p>You are currently looking at an info page about "%s".</p>

<p>Info pages are very much like regular posts, except that they do not appear in the regular flow of posts. They appear as info pages in the menu instead.</p>

<p>If needed, skins can format info pages differently from regular posts.</p>');


	// Update the progress bar status
	update_install_progress_bar();

	// Use this var to shift the posts of the collections in time below:
	$timeshift = 0;

	if( $install_collection_home )
	{ // ---------------- Insert the POSTS for Home blog ---------------- //
		task_begin( 'Creating sample posts for Collection Home... ' );

		// Insert three ADVERTISEMENTS for home blog:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'photo' );
		$edited_Item->insert( $jay_moderator_ID, /* TRANS: sample ad content */ T_('b2evo: The software for blog pros!'), /* TRANS: sample ad content */ T_('The software for blog pros!'), $now, $cat_home_b2evo,
			array(), 'published', '#', '', 'http://b2evolution.net', 'open', array('default'), 'Advertisement' );
		$edit_File = new File( 'shared', 0, 'banners/b2evo-125-pros.png' );
		$LinkOwner = new LinkItem( $edited_Item );
		$edit_File->link_to_Object( $LinkOwner );

		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'photo' );
		$edited_Item->insert( $jay_moderator_ID, /* TRANS: sample ad content */ T_('b2evo: Better Blog Software!'), /* TRANS: sample ad content */ T_('Better Blog Software!'), $now, $cat_home_b2evo,
			array(), 'published', '#', '', 'http://b2evolution.net', 'open', array('default'), 'Advertisement' );
		$edit_File = new File( 'shared', 0, 'banners/b2evo-125-better.png' );
		$LinkOwner = new LinkItem( $edited_Item );
		$edit_File->link_to_Object( $LinkOwner );

		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'photo' );
		$edited_Item->insert( $jay_moderator_ID, /* TRANS: sample ad content */ T_('b2evo: The other blog tool!'), /* TRANS: sample ad content */ T_('The other blog tool!'), $now, $cat_home_b2evo,
			array(), 'published', '#', '', 'http://b2evolution.net', 'open', array('default'), 'Advertisement' );
		$edit_File = new File( 'shared', 0, 'banners/b2evo-125-other.png' );
		$LinkOwner = new LinkItem( $edited_Item );
		$edit_File->link_to_Object( $LinkOwner );

		// Insert a post into info blog:
		// walter : a weird line of code to create a post in the home a minute after the others.
		// It will show a bug on home agregation by category
		$timestamp++;
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->insert( $jay_moderator_ID, 'Evo Factory', '', $now, $cat_home_contrib, array(), 'published', 'en-US', '', 'http://evofactory.com/', 'disabled', array(), 'Sidebar link' );

		// Insert a post into home:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->insert( $jay_moderator_ID, 'Francois', '', $now, $cat_home_contrib, array(), 'published', 'fr-FR', '', 'http://fplanque.com/', 'disabled', array(), 'Sidebar link' );

		// Insert a post into home:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->insert( $jay_moderator_ID, 'Blog news', '', $now, $cat_home_b2evo, array(), 'published', 'en-US', '', 'http://b2evolution.net/news.php', 'disabled', array(), 'Sidebar link' );

		// Insert a post into home:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->insert( $jay_moderator_ID, 'Web hosting', '', $now, $cat_home_b2evo, array(), 'published', 'en-US', '', 'http://b2evolution.net/web-hosting/blog/', 'disabled', array(), 'Sidebar link' );

		// Insert a post into home:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->insert( $jay_moderator_ID, 'Manual', '', $now, $cat_home_b2evo, array(), 'published',	'en-US', '', get_manual_url( NULL ), 'disabled', array(), 'Sidebar link' );

		// Insert a post into home:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->insert( $jay_moderator_ID, 'Support', '', $now, $cat_home_b2evo, array(), 'published', 'en-US', '', 'http://forums.b2evolution.net/', 'disabled', array(), 'Sidebar link' );

		// Insert a PAGE:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'photo' );
		$edited_Item->insert( $jay_moderator_ID, T_("About this site"), T_("<p>This blog platform is powered by b2evolution.</p>

<p>You are currently looking at an info page about this site.</p>

<p>Info pages are very much like regular posts, except that they do not appear in the regular flow of posts. They appear as info pages in the menu instead.</p>

<p>If needed, skins can format info pages differently from regular posts.</p>"), $now, $cat_home_b2evo,
			array( $cat_home_b2evo ), 'published', '#', '', '', 'open', array('default'), 'Standalone Page' );
		$edit_File = new File( 'shared', 1, 'logos/b2evolution_1016x208_wbg.png' );
		$LinkOwner = new LinkItem( $edited_Item );
		$edit_File->link_to_Object( $LinkOwner );

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'intro' );
		$edited_Item->insert( $jay_moderator_ID, T_('Homepage post'), T_('<p>This is the Home page of this site.</p>

<p>More specifically it is the "Front page" of the first collection of this site. This first collection is called "Home". Other sample collections have been created. You can access them by clicking "Blog A", "Blog B", "Photos", etc. in the menu bar at the top of this page.</p>

<p>You can add collections at will. You can also remove them (including this "Home" collection) if you don\'t need one.</p>'),
			$now, $cat_home_b2evo, array(), 'published', '#', '', '', 'open', array( 'default' ), 'Intro-Front' );

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'intro' );
		$edited_Item->insert( $jay_moderator_ID, T_('Terms & Conditions'), '<p>Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum</p>

<p>Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum</p>',
			$now, $cat_home_b2evo, array(), 'published', '#', '', '', 'open', array( 'default' ), 'Terms & Conditions' );
		if( $edited_Item->ID > 0 )
		{	// Use this post as default terms & conditions:
			$Settings->set( 'site_terms', $edited_Item->ID );
			$Settings->dbupdate();
		}

		// Update the progress bar status
		update_install_progress_bar();

		task_end();
	}

	if( $install_collection_bloga )
	{ // ---------------- Insert the POSTS for Blog A ---------------- //
		task_begin( 'Creating sample posts for Collection Blog A... ' );

		// Set all posts of this collection with 24 hours ago relating on previous collection:
		$timeshift += 86400;

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'intro' );
		$edited_Item->insert( $jay_moderator_ID, T_('Main Intro post'), T_('This is the main intro post. It appears on the homepage only.'),
			$now, $cat_ann_a, array(), 'published', '#', '', '', 'open', array('default'), 'Intro-Main' );

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->insert( $jay_moderator_ID, T_('First Post'), T_('<p>This is the first post.</p>

<p>It appears in a single category.</p>'), $now, $cat_ann_a );

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->insert( $jay_moderator_ID, T_('Second post'), T_('<p>This is the second post.</p>

<p>It appears in multiple categories.</p>'), $now, $cat_news, array( $cat_ann_a ) );

		// Insert a PAGE:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->insert( $jay_moderator_ID, T_("About Blog A"), sprintf( $info_page, T_('Blog A') ), $now, $cat_ann_a,
			array(), 'published', '#', '', '', 'open', array('default'), 'Standalone Page' );

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'demo' );
		$edited_Item->insert( $jay_moderator_ID, T_('This is a multipage post'), T_('<p>This is page 1 of a multipage post.</p>

<blockquote><p>This is a Block Quote.</p></blockquote>

<p>You can see the other pages by clicking on the links below the text.</p>').'

[pagebreak]

'.sprintf( T_("<p>This is page %d.</p>"), 2 ).$lorem_2more.'

[pagebreak]

'.sprintf( T_("<p>This is page %d.</p>"), 3 ).$lorem_1paragraph.'

[pagebreak]

'.sprintf( T_("<p>This is page %d.</p>"), 4 ).'

'.T_('<p>It is the last page.</p>'), $now, $cat_bg );

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'demo' );
		$edited_Item->insert( $jay_moderator_ID, T_('Extended post with no teaser'), T_('<p>This is an extended post with no teaser. This means that you won\'t see this teaser any more when you click the "more" link.</p>').$lorem_1paragraph
	.'[teaserbreak]

'.T_('<p>This is the extended text. You only see it when you have clicked the "more" link.</p>').$lorem_2more, $now, $cat_bg );
		$edited_Item->set_setting( 'hide_teaser', '1' );
		$edited_Item->dbsave();

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set( 'featured', 1 );
		$edited_Item->set_tags_from_string( 'photo,demo' );
		$edited_Item->insert( $jay_moderator_ID, T_('Extended post'), T_('<p>This is an extended post. This means you only see this small teaser by default and you must click on the link below to see more.</p>').$lorem_1paragraph
	.'[teaserbreak]

'.T_('<p>This is the extended text. You only see it when you have clicked the "more" link.</p>').$lorem_2more, $now, $cat_bg );
		$LinkOwner = new LinkItem( $edited_Item );
		$edit_File = new File( 'shared', 0, 'monument-valley/john-ford-point.jpg' );
		$edit_File->link_to_Object( $LinkOwner, 1, 'cover' );
		$edit_File = new File( 'shared', 0, 'monument-valley/monument-valley-road.jpg' );
		$edit_File->link_to_Object( $LinkOwner, 2, 'teaser' );
		$edit_File = new File( 'shared', 0, 'monument-valley/monuments.jpg' );
		$edit_File->link_to_Object( $LinkOwner, 3, 'aftermore' );

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'demo' );
		$edited_Item->set_setting( 'custom_double_1', '123' );
		$edited_Item->set_setting( 'custom_double_2', '456' );
		$edited_Item->set_setting( 'custom_varchar_3', 'abc' );
		$edited_Item->set_setting( 'custom_varchar_4', 'Enter your own values' );
		$post_custom_fields_ID = $edited_Item->insert( $jay_moderator_ID, T_('Custom Fields Example'), T_('<p>This post has a special post type called "Post with custom fields".</p>')
				.T_('<p>This post type defines 4 custom fields.</p>')
				.T_('<p>This post has sample values for these for 4 fields. You can see them below</p>'),
			$now, $cat_bg, array(), 'published', '#', '', '', 'open', array('default'), 'Post with Custom Fields' );

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'demo' );
		$edited_Item->set( 'parent_ID', $post_custom_fields_ID ); // Set parent post ID
		$edited_Item->insert( $jay_moderator_ID, T_('Child Post Example'), T_('<p>This post has a special post type called "Child Post".</p>'),
			$now, $cat_bg, array(), 'published', '#', '', '', 'open', array('default'), 'Child Post' );

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'photo,demo' );
		$edited_Item->set( 'featured', 1 );
		$edited_Item->insert( $jay_moderator_ID, T_('Image post'), T_('<p>This post has several images attached to it. Each one uses a different Attachment Position. Each may be displayed differently depending on the skin they are viewed in.</p>

<p>Check out the photoblog (accessible through the links at the top) to see a completely different skin focused more on the photos than on the blog text.</p>'), $now, $cat_bg );
		$LinkOwner = new LinkItem( $edited_Item );
		$edit_File = new File( 'shared', 0, 'monument-valley/monument-valley.jpg' );
		$edit_File->link_to_Object( $LinkOwner, 1, 'cover' );
		$edit_File = new File( 'shared', 0, 'monument-valley/monuments.jpg' );
		$edit_File->link_to_Object( $LinkOwner, 2, 'teaser' );
		$edit_File = new File( 'shared', 0, 'monument-valley' );
		$edit_File->link_to_Object( $LinkOwner, 3, 'teaser' );
		$edit_File = new File( 'shared', 0, 'monument-valley/bus-stop-ahead.jpg' );
		$edit_File->link_to_Object( $LinkOwner, 4, 'aftermore' );

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'photo' );
		$additional_comments_item_IDs[] = $edited_Item->insert( $jay_moderator_ID, T_("Welcome to your b2evolution-powered website!"),
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

		// Update the progress bar status
		update_install_progress_bar();

		task_end();
	}

	if( $install_collection_blogb )
	{ // ---------------- Insert the POSTS for Blog B ---------------- //
		task_begin( 'Creating sample posts for Collection Blog B... ' );

		// Set all posts of this collection with 24 hours ago relating on previous collection:
		$timeshift += 86400;

		// Insert sidebar links into Blog B
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->insert( $paul_blogger_ID, 'Skin Faktory', '', $now, $cat_additional_skins, array(), 'published', 'en-US', '', 'http://www.skinfaktory.com/', 'open', array('default'), 'Sidebar link' );

		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->insert( $paul_blogger_ID, T_('b2evo skins repository'), '', $now, $cat_additional_skins, array(), 'published', 'en-US', '', 'http://skins.b2evolution.net/', 'open', array('default'), 'Sidebar link' );

		// Insert a PAGE:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->insert( $paul_blogger_ID, T_("About Blog B"), sprintf( $info_page, T_('Blog B') ), $now, $cat_ann_b,
			array(), 'published', '#', '', '', 'open', array('default'), 'Standalone Page' );

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'intro' );
		$edited_Item->insert( $paul_blogger_ID, T_('Welcome to Blog B'), sprintf( T_('<p>This is the intro post for the front page of Blog B.</p>

<p>Blog B is currently configured to show a front page like this one instead of directly showing the blog\'s posts.</p>

<ul>
<li>To view the blog\'s posts, click on "News" in the menu above.</li>
<li>If you don\'t want to have such a front page, you can disable it in the Blog\'s settings > Features > <a %s>Front Page</a>. You can also see an example of a blog without a Front Page in Blog A</li>
</ul>'), 'href="'.$admin_url.'?ctrl=coll_settings&amp;tab=home&amp;blog='.$blog_a_ID.'"' ),
				$now, $cat_b2evo, array(), 'published', '#', '', '', 'open', array('default'), 'Intro-Front' );

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'intro' );
		$edited_Item->insert( $paul_blogger_ID, T_("b2evolution tips category &ndash; Sub Intro post"), T_("This uses post type \"Intro-Cat\" and is attached to the desired Category(ies)."),
			$now, $cat_b2evo, array(), 'published', '#', '', '', 'open', array('default'), 'Intro-Cat' );

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'widgets,intro' );
		$edited_Item->insert( $paul_blogger_ID, T_("Widgets tag &ndash; Sub Intro post"), T_("This uses post type \"Intro-Tag\" and is tagged with the desired Tag(s)."),
			$now, $cat_b2evo, array(), 'published', '#', '', '', 'open', array('default'), 'Intro-Tag' );

		// Insert a post:
		// TODO: move to Blog A
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->insert( $paul_blogger_ID, T_("Featured post"), T_("<p>This is a demo of a featured post.</p>

<p>It will be featured whenever we have no specific \"Intro\" post to display for the current request. To see it in action, try displaying the \"Announcements\" category.</p>

<p>Also note that when the post is featured, it does not appear in the regular post flow.</p>").$lorem_1paragraph,
			$now, $cat_b2evo, array( $cat_ann_b ) );
		$edited_Item->set( 'featured', 1 );
		$edited_Item->dbsave();

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->insert( $paul_blogger_ID, T_("Apache optimization..."), sprintf( T_("<p>b2evolution comes with an <code>.htaccess</code> file destined to optimize the way b2evolution is handled by your webseerver (if you are using Apache). In some circumstances, that file may not be automatically activated at setup. Please se the man page about <a %s>Tricky Stuff</a> for more information.</p>

<p>For further optimization, please review the manual page about <a %s>Performance optimization</a>. Depending on your current configuration and on what your <a %s>web hosting</a> company allows you to do, you may increase the speed of b2evolution by up to a factor of 10!</p>"),
'href="'.get_manual_url( 'tricky-stuff' ).'"',
'href="'.get_manual_url( 'performance-optimization' ).'"',
'href="http://b2evolution.net/web-hosting/"' ),
			$now, $cat_b2evo, array( $cat_ann_b ) );

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'skins' );
		$edited_Item->insert( $paul_blogger_ID, T_("Skins, Stubs, Templates &amp; website integration..."), T_("<p>By default, blogs are displayed using an evoskin. (More on skins in another post.)</p>

<p>This means, blogs are accessed through '<code>index.php</code>', which loads default parameters from the database and then passes on the display job to a skin.</p>

<p>Alternatively, if you don't want to use the default DB parameters and want to, say, force a skin, a category or a specific linkblog, you can create a stub file like the provided '<code>a_stub.php</code>' and call your blog through this stub instead of index.php .</p>

<p>Finally, if you need to do some very specific customizations to your blog, you may use plain templates instead of skins. In this case, call your blog through a full template, like the provided '<code>a_noskin.php</code>'.</p>

<p>If you want to integrate a b2evolution blog into a complex website, you'll probably want to do it by copy/pasting code from <code>a_noskin.php</code> into a page of your website.</p>

<p>You will find more information in the stub/template files themselves. Open them in a text editor and read the comments in there.</p>

<p>Either way, make sure you go to the blogs admin and set the correct access method/URL for your blog. Otherwise, the permalinks will not function properly.</p>"), $now, $cat_b2evo );

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'widgets' );
		$edited_Item->insert( $paul_blogger_ID, T_("About widgets..."), T_('<p>b2evolution blogs are installed with a default selection of Widgets. For example, the sidebar of this blog includes widgets like a calendar, a search field, a list of categories, a list of XML feeds, etc.</p>

<p>You can add, remove and reorder widgets from the Blog Settings tab in the admin interface.</p>

<p>Note: in order to be displayed, widgets are placed in containers. Each container appears in a specific place in an evoskin. If you change your blog skin, the new skin may not use the same containers as the previous one. Make sure you place your widgets in containers that exist in the specific skin you are using.</p>'), $now, $cat_b2evo );

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'skins' );
		$edited_Item->insert( $paul_blogger_ID, T_("About skins..."), sprintf( T_('<p>By default, b2evolution blogs are displayed using an evoskin.</p>

<p>You can change the skin used by any blog by editing the blog settings in the admin interface.</p>

<p>You can download additional skins from the <a href="http://skins.b2evolution.net/" target="_blank">skin site</a>. To install them, unzip them in the /blogs/skins directory, then go to General Settings &gt; Skins in the admin interface and click on "Install new".</p>

<p>You can also create your own skins by duplicating, renaming and customizing any existing skin folder from the /blogs/skins directory.</p>

<p>To start customizing a skin, open its "<code>index.main.php</code>" file in an editor and read the comments in there. Note: you can also edit skins in the "Files" tab of the admin interface.</p>

<p>And, of course, read the <a href="%s" target="_blank">manual on skins</a>!</p>'), get_manual_url( 'skin-structure' ) ), $now, $cat_b2evo );
		$edited_Item->dbsave();
		// $edited_Item->insert_update_tags( 'update' );

		// Update the progress bar status
		update_install_progress_bar();

		task_end();
	}

	if( $install_collection_photos )
	{ // ---------------- Insert the POSTS for Photos blog ---------------- //
		task_begin( 'Creating sample posts for Collection Photos... ' );

		// Set all posts of this collection with 24 hours ago relating on previous collection:
		$timeshift += 86400;

		// Insert a PAGE:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->insert( $dave_blogger_ID, T_('About Photos'), sprintf( $info_page, T_('Photos') ), $now, $cat_photo_album,
			array(), 'published', '#', '', '', 'open', array('default'), 'Standalone Page' );

		// Insert a post into photoblog:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'photo' );
		$edited_Item->insert( $dave_blogger_ID, T_('Sunset'), '',
			$now, $cat_photo_album, array(), 'published','en-US' );
		$LinkOwner = new LinkItem( $edited_Item );
		$edit_File = new File( 'shared', 0, 'sunset/sunset.jpg' );
		$photo_link_1_ID = $edit_File->link_to_Object( $LinkOwner, 1 );

		// Insert a post into photoblog:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'photo' );
		$edited_Item->insert( $dave_blogger_ID, T_('Bus Stop Ahead'), 'In the middle of nowhere: a school bus stop where you wouldn\'t really expect it!',
			$now, $cat_photo_album, array(), 'published','en-US', '', 'http://fplanque.com/photo/monument-valley' );
		$LinkOwner = new LinkItem( $edited_Item );
		$edit_File = new File( 'shared', 0, 'monument-valley/bus-stop-ahead.jpg' );
		$photo_link_1_ID = $edit_File->link_to_Object( $LinkOwner, 1 );
		$edit_File = new File( 'shared', 0, 'monument-valley/john-ford-point.jpg' );
		$photo_link_2_ID = $edit_File->link_to_Object( $LinkOwner, 2 );
		$edit_File = new File( 'shared', 0, 'monument-valley/monuments.jpg' );
		$photo_link_3_ID = $edit_File->link_to_Object( $LinkOwner, 3 );
		$edit_File = new File( 'shared', 0, 'monument-valley/monument-valley-road.jpg' );
		$photo_link_4_ID = $edit_File->link_to_Object( $LinkOwner, 4 );
		$edit_File = new File( 'shared', 0, 'monument-valley/monument-valley.jpg' );
		$photo_link_5_ID = $edit_File->link_to_Object( $LinkOwner, 5 );

		if( $install_test_features )
		{ // Add examples for infodots plugin
			$edited_Item->set_tags_from_string( 'photo,demo' );
			$edited_Item->set( 'content', $edited_Item->get( 'content' )
				.sprintf( '
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
		}

		// Update the progress bar status
		update_install_progress_bar();

		task_end();
	}

	if( $install_collection_forums || $install_collection_manual )
	{ // Init a content for post with markdown examples
		$markdown_examples_content = T_('Heading
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
	}

	if( $install_collection_forums )
	{ // ---------------- Insert the POSTS for Forums Collection ---------------- //
		task_begin( 'Creating sample posts for Collection Forums... ' );

		// Set all posts of this collection with 24 hours ago relating on previous collection:
		$timeshift += 86400;

		// Insert a PAGE:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->insert( 1, T_("About Forums"), sprintf( $info_page, T_('Forums') ), $now, $cat_forums_ann,
			array(), 'published', '#', '', '', 'open', array('default'), 'Standalone Page' );

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->insert( $mary_moderator_ID, T_('First Topic'), T_('<p>This is the first topic.</p>

<p>It appears in a single category.</p>').$lorem_2more, $now, $cat_forums_ann );

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->insert( $jay_moderator_ID, T_('Second topic'), T_('<p>This is the second topic.</p>

<p>It appears in multiple categories.</p>').$lorem_2more, $now, $cat_forums_news, array( $cat_forums_ann ) );

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'photo' );
		$edited_Item->insert( $dave_blogger_ID, T_('Image topic'), T_('<p>This topic has an image attached to it. The image is automatically resized to fit the current blog skin. You can zoom in by clicking on the thumbnail.</p>

<p>Check out the photoblog (accessible through the links at the top) to see a completely different skin focused more on the photos than on the blog text.</p>'), $now, $cat_forums_bg );
		$edit_File = new File( 'shared', 0, 'monument-valley/monuments.jpg' );
		$LinkOwner = new LinkItem( $edited_Item );
		$edit_File->link_to_Object( $LinkOwner );

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'demo' );
		$edited_Item->insert( $paul_blogger_ID, T_('This is a multipage topic'), T_('<p>This is page 1 of a multipage topic.</p>

<blockquote><p>This is a Block Quote.</p></blockquote>

<p>You can see the other pages by clicking on the links below the text.</p>').'

[pagebreak]

'.sprintf( T_("<p>This is page %d.</p>"), 2 ).$lorem_2more.'

[pagebreak]

'.sprintf( T_("<p>This is page %d.</p>"), 3 ).$lorem_1paragraph.'

[pagebreak]

'.sprintf( T_("<p>This is page %d.</p>"), 4 ).'

'.T_('<p>It is the last page.</p>'), $now, $cat_forums_bg );

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'demo' );
		$edited_Item->insert( $larry_user_ID, T_('Extended topic with no teaser'), T_('<p>This is an extended topic with no teaser. This means that you won\'t see this teaser any more when you click the "more" link.</p>

[teaserbreak]

<p>This is the extended text. You only see it when you have clicked the "more" link.</p>'), $now, $cat_forums_bg );
		$edited_Item->set_setting( 'hide_teaser', '1' );
		$edited_Item->dbsave();

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'demo' );
		$edited_Item->insert( $kate_user_ID, T_('Extended topic'), T_('<p>This is an extended topic. This means you only see this small teaser by default and you must click on the link below to see more.</p>

[teaserbreak]

<p>This is the extended text. You only see it when you have clicked the "more" link.</p>'), $now, $cat_forums_bg );

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'photo' );
		$additional_comments_item_IDs[] = $edited_Item->insert( 1, T_("Welcome to your b2evolution-powered website!"),
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

		// Insert Markdown example post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'demo' );
		$edited_Item->insert( $mary_moderator_ID, T_('Markdown examples'), $markdown_examples_content, $now, $cat_forums_news );

		// Update the progress bar status
		update_install_progress_bar();

		task_end();
	}

	if( $install_collection_manual )
	{ // ---------------- Insert the POSTS for Manual collection ---------------- //
		task_begin( 'Creating sample posts for Collection Manual... ' );

		// Set all posts of this collection with 24 hours ago relating on previous collection:
		$timeshift += 86400;

		// Insert a main intro:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'intro' );
		$edited_Item->insert( $dave_blogger_ID, T_("Welcome here!"), T_('This is the main introduction for this demo online manual. It is a post using the type "Intro-Front". It will only appear on the front page of the manual.

You may delete this post if you don\'t want such an introduction.

Just to be clear: this is a **demo** of a manual. The user manual for b2evolution is here: http://b2evolution.net/man/.'), $now, $cat_manual_intro,
			array(), 'published', '#', '', '', 'open', array('default'), 'Intro-Front' );

		// Insert a cat intro:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'intro' );
		$edited_Item->insert( $dave_blogger_ID, T_("Chapter Intro"), T_('This is an introduction for this chapter. It is a post using the "intro-cat" type.'), $now, $cat_manual_intro,
			array(), 'published', '#', '', '', 'open', array('default'), 'Intro-Cat' );

		// Insert a cat intro:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'intro' );
		$edited_Item->insert( $dave_blogger_ID, T_("Chapter Intro"), T_('This is an introduction for this chapter. It is a post using the "intro-cat" type.')
			."\n\n".T_('Contrary to the other sections which are explictely sorted by default, this section is sorted alphabetically by default.'), $now, $cat_manual_reference,
			array(), 'published', '#', '', '', 'open', array('default'), 'Intro-Cat' );

		// Insert a PAGE:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->insert( $dave_blogger_ID, T_("About this manual"), sprintf( $info_page, T_('Manual') ), $now, $cat_manual_intro,
			array(), 'published', '#', '', '', 'open', array('default'), 'Standalone Page' );

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->insert( $dave_blogger_ID, T_('First Page'), T_('<p>This is the first page.</p>

<p>It appears in a single category.</p>'), $now, $cat_manual_intro, array(),
		'published', '#', '', '', 'open', array('default'), 'Manual Page', NULL, 10 );

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->insert( $dave_blogger_ID, T_('Second Page'), T_('<p>This is the second page.</p>

<p>It appears in multiple categories.</p>'), $now, $cat_manual_intro, array( $cat_manual_getstarted ),
		'published', '#', '', '', 'open', array('default'), 'Manual Page', NULL, 20 );

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'demo' );
		$edited_Item->insert( $dave_blogger_ID, T_('Wiki Tables'), /* DO NOT TRANSLATE - TOO COMPLEX */ '<p>This is the topic with samples of the wiki tables.</p>

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

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'photo' );
		$edited_Item->insert( $dave_blogger_ID, T_('Image topic'), T_('<p>This topic has an image attached to it. The image is automatically resized to fit the current blog skin. You can zoom in by clicking on the thumbnail.</p>

<p>Check out the photoblog (accessible through the links at the top) to see a completely different skin focused more on the photos than on the blog text.</p>'), $now, $cat_manual_getstarted, array( $cat_manual_blogs ),
			'published', '#', '', '', 'open', array('default'), 'Manual Page', NULL, 10 );
		$edit_File = new File( 'shared', 0, 'monument-valley/monuments.jpg' );
		$LinkOwner = new LinkItem( $edited_Item );
		$edit_File->link_to_Object( $LinkOwner );

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'demo' );
		$edited_Item->insert( $dave_blogger_ID, T_('This is a multipage topic'), T_('<p>This is page 1 of a multipage topic.</p>

<blockquote><p>This is a Block Quote.</p></blockquote>

<p>You can see the other pages by clicking on the links below the text.</p>').'

[pagebreak]

'.sprintf( T_("<p>This is page %d.</p>"), 2 ).$lorem_2more.'

[pagebreak]

'.sprintf( T_("<p>This is page %d.</p>"), 3 ).$lorem_1paragraph.'

[pagebreak]

'.sprintf( T_("<p>This is page %d.</p>"), 4 ).'

'.T_('<p>It is the last page.</p>'), $now, $cat_manual_userguide, array(),
			'published', '#', '', '', 'open', array('default'), 'Manual Page', NULL, 30 );

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'demo' );
		$edited_Item->insert( $dave_blogger_ID, T_('Extended topic with no teaser'), T_('<p>This is an extended topic with no teaser. This means that you won\'t see this teaser any more when you click the "more" link.</p>

[teaserbreak]

<p>This is the extended text. You only see it when you have clicked the "more" link.</p>'), $now, $cat_manual_userguide, array(),
			'published', '#', '', '', 'open', array('default'), 'Manual Page', NULL, 20 );
		$edited_Item->set_setting( 'hide_teaser', '1' );
		$edited_Item->dbsave();

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'demo' );
		$edited_Item->insert( $dave_blogger_ID, T_('Extended topic'), T_('<p>This is an extended topic. This means you only see this small teaser by default and you must click on the link below to see more.</p>

[teaserbreak]

<p>This is the extended text. You only see it when you have clicked the "more" link.</p>'), $now, $cat_manual_userguide, array(),
			'published', '#', '', '', 'open', array('default'), 'Manual Page', NULL, 10 );

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'photo' );
		$additional_comments_item_IDs[] = $edited_Item->insert( $dave_blogger_ID, T_("Welcome to your b2evolution-powered website!"),
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

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->insert( $dave_blogger_ID, T_('Sports post'), T_('<p>This is the sports post.</p>

<p>It appears in sports category.</p>'), $now, $cat_manual_blogs, array(),
		'published', '#', '', '', 'open', array('default'), 'Manual Page', NULL, 15 );

		// Insert a post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->insert( $dave_blogger_ID, T_('Second sports post'), T_('<p>This is the second sports post.</p>

<p>It appears in sports category.</p>'), $now, $cat_manual_blogs, array(),
			'published', '#', '', '', 'open', array('default'), 'Manual Page', NULL, 5 );

		// Insert Markdown example post:
		$now = date( 'Y-m-d H:i:s', ( $timestamp++ - $timeshift ) );
		$edited_Item = new Item();
		$edited_Item->set_tags_from_string( 'demo' );
		$edited_Item->insert( $dave_blogger_ID, T_('Markdown examples'), $markdown_examples_content, $now, $cat_manual_userguide );

		// Update the progress bar status
		update_install_progress_bar();

		task_end();
	}



	task_begin( 'Creating sample comments... ' );

	// Create two demo comments for each new created item
	$new_created_item_IDs = $DB->get_col( 'SELECT post_ID FROM T_items__item ORDER BY post_ID' );
	foreach( $new_created_item_IDs as $new_created_item_ID )
	{
		create_demo_comment( $new_created_item_ID, 'published' );
		create_demo_comment( $new_created_item_ID, 'draft' );
	}

	if( $install_test_features && count( $additional_comments_item_IDs ) )
	{ // Create the additional comments when we install all features
		foreach( $additional_comments_item_IDs as $additional_comments_item_ID )
		{
			for( $i_user_ID = 1; $i_user_ID <= 7; $i_user_ID++ )
			{ // Insert the comments from each user
				$now = date( 'Y-m-d H:i:s' );
				$DB->query( 'INSERT INTO T_comments( comment_item_ID, comment_author_user_ID, comment_author_IP,
						comment_date, comment_last_touched_ts, comment_content, comment_renderers, comment_notif_status, comment_notif_flags )
					VALUES( '.$DB->quote( $additional_comments_item_ID ).', '.$DB->quote( $i_user_ID ).', "127.0.0.1", '
						.$DB->quote( $now ).', '.$DB->quote( $now ).', '.$DB->quote( T_('Hi!

This is a sample comment that has been approved by default!
Admins and moderators can very quickly approve or reject comments from the collection dashboard.') ).', "default", "finished", "moderators_notified,members_notified,community_notified" )' );
			}
		}
		echo_install_log( 'TEST FEATURE: Creating additional comments on items ('.implode( ', ', $additional_comments_item_IDs ).')' );
	}

	task_end();


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

	install_basic_widgets( $new_db_version );

	load_funcs( 'tools/model/_system.funcs.php' );
	system_init_caches( true, true ); // Outputs messages
}


/**
 * Create a demo comment
 *
 * @param integer Item ID
 * @param string Comment status
 */
function create_demo_comment( $item_ID, $status )
{
	global $DB, $b2evo_demo_comment_users, $b2evo_demo_comment_user_num;

	// Get next user ID for new creating comment
	if( ! isset( $b2evo_demo_comment_user_num ) )
	{
		$b2evo_demo_comment_user_num = 0;
	}
	else
	{
		$b2evo_demo_comment_user_num++;
		if( $b2evo_demo_comment_user_num > count( $b2evo_demo_comment_users ) - 1 )
		{
			$b2evo_demo_comment_user_num = 0;
		}
	}
	$user_ID = $b2evo_demo_comment_users[ $b2evo_demo_comment_user_num ];
	if( $user_ID > 0 )
	{ // An existing user
		$author = NULL;
		$author_email = NULL;
		$author_email_url = NULL;
	}
	else
	{ // Anonymous user
		$user_ID = NULL;
		$author = 'miss b2';
		$author_email = 'missb2@example.com';
		$author_email_url = 'http://example.com';
	}

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

	$now = date( 'Y-m-d H:i:s' );

	$DB->query( 'INSERT INTO T_comments( comment_item_ID, comment_status,
			comment_author_user_ID, comment_author, comment_author_email, comment_author_url, comment_author_IP,
			comment_date, comment_last_touched_ts, comment_content, comment_renderers, comment_notif_status, comment_notif_flags )
		VALUES( '.$DB->quote( $item_ID ).', '.$DB->quote( $status ).', '
			.$DB->quote( $user_ID ).', '.$DB->quote( $author ).', '.$DB->quote( $author_email ).', '.$DB->quote( $author_email_url ).', "127.0.0.1", '
			.$DB->quote( $now ).', '.$DB->quote( $now ).', '.$DB->quote( $content ).', "default", "finished", "moderators_notified,members_notified,community_notified" )' );
}


/**
 * Create default location for all posts
 */
function create_default_posts_location()
{
	global $install_test_features;

	if( $install_test_features )
	{	// Set default location in test mode installation
		global $DB;

		$DB->query( 'UPDATE T_items__item SET
			post_ctry_ID = '.$DB->quote( '74'/* France */ ).',
			post_rgn_ID = '.$DB->quote( '60'/* Île-de-France */ ).',
			post_subrg_ID = '.$DB->quote( '76'/* Paris */ ) );

		echo_install_log( 'TEST FEATURE: Defining default location "France, Île-de-France, Paris" for all posts' );
	}
}


/**
 * Create default email campaigns
 */
function create_default_email_campaigns()
{
	global $DB, $create_sample_contents;

	task_begin( 'Creating default email campaigns... ' );

	load_class( 'email_campaigns/model/_emailcampaign.class.php', 'EmailCampaign' );
	load_funcs( 'email_campaigns/model/_emailcampaign.funcs.php' );

	if( $create_sample_contents )
	{
		$EmailCampaign = new EmailCampaign();
		$EmailCampaign->set( 'name', T_('Markdown Example') );
		$EmailCampaign->set( 'email_title', T_('Markdown Example') );
		$EmailCampaign->set( 'email_text', T_('Heading
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

The rain---not the reign---in Spain.') );

		if( $EmailCampaign->dbinsert() )
		{	// Add recipients after successfull email campaign creating:
			$user_IDs = $DB->get_col( 'SELECT user_ID FROM T_users' );
			if( ! empty( $user_IDs ) )
			{	// Only if we have found the users in DB
				$EmailCampaign->add_users( $user_IDs );
			}
		}
	}

	task_end();
}
?>