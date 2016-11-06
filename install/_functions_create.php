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
load_funcs( 'collections/_demo_content.funcs.php' );

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


	// Do not create organization yet
	global $user_org_IDs;
	$user_org_IDs = NULL;

	task_begin( 'Creating admin user... ' );
	global $timestamp, $admin_email, $default_locale, $default_country, $install_login, $install_password;
	global $random_password;

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

	global $admin_user;
	$admin_user = create_user( array(
			'login'     => isset( $install_login ) ? $install_login : 'admin',
			'firstname' => 'Johnny',
			'lastname'  => 'Admin',
			'level'     => 10,
			'gender'    => 'M',
			'Group'     => $admins_Group,
			'org_IDs'   => $user_org_IDs,
			'org_roles' => array( 'King of Spades' ),
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
			'allow_disabling_comments' => 1,
		);
	$post_types[] = array(
			'name'           => 'Intro-Main',
			'usage'          => 'intro-main',
			'template_name'  => NULL,
			'allow_breaks'   => 0,
			'allow_featured' => 0,
			'perm_level'     => 'restricted',
			'allow_disabling_comments' => 1,
		);
	$post_types[] = array(
			'name'           => 'Intro-Cat',
			'usage'          => 'intro-cat',
			'template_name'  => NULL,
			'allow_breaks'   => 0,
			'allow_featured' => 0,
			'perm_level'     => 'restricted',
			'allow_disabling_comments' => 1,
		);
	$post_types[] = array(
			'name'           => 'Intro-Tag',
			'usage'          => 'intro-tag',
			'template_name'  => NULL,
			'allow_breaks'   => 0,
			'allow_featured' => 0,
			'perm_level'     => 'restricted',
			'allow_disabling_comments' => 1,
		);
	$post_types[] = array(
			'name'           => 'Intro-Sub',
			'usage'          => 'intro-sub',
			'template_name'  => NULL,
			'allow_breaks'   => 0,
			'allow_featured' => 0,
			'perm_level'     => 'restricted',
			'allow_disabling_comments' => 1,
		);
	$post_types[] = array(
			'name'           => 'Intro-All',
			'usage'          => 'intro-all',
			'template_name'  => NULL,
			'allow_breaks'   => 0,
			'allow_featured' => 0,
			'perm_level'     => 'restricted',
			'allow_disabling_comments' => 1,
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
	$post_types[] = array(
			'name' => 'Bug Report',
			'allow_html'     => 0,
		);
	// Default settings:
	$post_type_default_settings = array(
			'name'                     => '',
			'description'              => NULL,
			'usage'                    => 'post',
			'template_name'            => 'single',
			'perm_level'               => 'standard',
			'allow_html'               => 1,
			'allow_breaks'             => 1,
			'allow_featured'           => 1,
			'use_text'                 => 'optional',
			'use_tags'                 => 'optional',
			'use_excerpt'              => 'optional',
			'use_url'                  => 'optional',
			'podcast'                  => 0,
			'use_parent'               => 'never',
			'use_title_tag'            => 'optional',
			'use_meta_desc'            => 'optional',
			'use_meta_keywds'          => 'optional',
			'use_comments'             => 1,
			'allow_closing_comments'   => 1,
			'allow_disabling_comments' => 0,
			'use_comment_expiration'   => 'optional',
			'use_custom_fields'        => 1,
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
						 ( 3, "First string field", "first_string_field", "varchar" ),
						 ( 3, "Define you own labels", "define_you_own_labels", "varchar" ),
						 ( 3, "Multiline plain text field", "multiline_plain_text_field", "text" ),
						 ( 3, "Multiline HTML field", "multiline_html_field", "html" )' );
	task_end();


	task_begin( 'Creating default Post Statuses... ' );
	$post_status = array( 'New', 'In Progress', 'Duplicate', 'Not A Bug', 'In Review', 'Fixed', 'Closed', 'OK' );

	$DB->query( "INSERT INTO T_items__status ( pst_name )	VALUES ( '".implode( "' ),( '", $post_status )." ')" );
	task_end();


	task_begin( 'Creating default post status and post type associations...' );
	// Enable all post statuses for post type Bug Report
	$DB->query( 'INSERT INTO T_items__status_type (its_pst_ID, its_ityp_ID)
			( SELECT pst_ID, ityp_ID FROM T_items__type, T_items__status WHERE ityp_name = "Bug Report" )' );

	// Enable post status 'New', 'Duplicate', 'In Review' and 'OK' for all post types
	$DB->query( 'INSERT IGNORE INTO T_items__status_type (its_pst_ID, its_ityp_ID)
			( SELECT pst_ID, ityp_ID FROM T_items__type, T_items__status WHERE pst_name IN ( "New", "Duplicate", "In Review", "OK" ) )' );
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
			(13, 'css', 'Style sheet', 'text/css', 'file_document', 'text', 'registered'),
			(14, 'mp3', 'MPEG audio file', 'audio/mpeg', 'file_sound', 'browser', 'registered'),
			(15, 'm4a', 'MPEG audio file', 'audio/x-m4a', 'file_sound', 'browser', 'registered'),
			(16, 'mp4 f4v', 'MPEG video', 'video/mp4', 'file_video', 'browser', 'registered'),
			(17, 'mov', 'Quicktime video', 'video/quicktime', 'file_video', 'browser', 'registered'),
			(18, 'm4v', 'MPEG video file', 'video/x-m4v', 'file_video', 'browser', 'registered'),
			(19, 'flv', 'Flash video file', 'video/x-flv', 'file_video', 'browser', 'registered'),
			(20, 'swf', 'Flash video file', 'application/x-shockwave-flash', 'file_video', 'browser', 'registered'),
			(21, 'webm', 'WebM video file', 'video/webm', 'file_video', 'browser', 'registered'),
			(22, 'ogv', 'Ogg video file', 'video/ogg', 'file_video', 'browser', 'registered'),
			(23, 'm3u8', 'M3U8 video file', 'application/x-mpegurl', 'file_video', 'browser', 'registered'),
			(24, 'xml', 'XML file', 'application/xml', 'file_www', 'browser', 'admin')
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


function create_sample_organization()
{
	global $user_org_IDs, $admin_user;

	task_begin( 'Creating sample organization...' );
	$user_org_IDs = array( create_demo_organization( 1 )->ID );
	task_end();

	task_begin( 'Adding admin user to sample organization...' );
	$admin_user->update_organizations( $user_org_IDs, array( 'King of Spades' ), true );
	task_end();
}


function create_demo_users()
{
	global $admins_Group, $moderators_Group, $editors_Group, $users_Group, $suspect_Group, $spam_Group, $blogb_Group;
	global $mary_moderator_ID, $jay_moderator_ID, $dave_blogger_ID, $paul_blogger_ID, $larry_user_ID, $kate_user_ID;
	global $user_org_IDs;

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
	$mary_moderator_ID = get_demo_user( 'mary', true, $moderators_Group, $user_org_IDs )->ID;
	task_end();

	task_begin('Creating demo user jay... ');
	$jay_moderator_ID = get_demo_user( 'jay', true, $moderators_Group, $user_org_IDs )->ID;
	task_end();

	task_begin('Creating demo user dave... ');
	$dave_blogger_ID = get_demo_user( 'dave', true, $editors_Group, $user_org_IDs )->ID;
	task_end();

	task_begin('Creating demo user paul... ');
	$paul_blogger_ID = get_demo_user( 'paul', true, $editors_Group, $user_org_IDs )->ID;
	task_end();

	task_begin('Creating demo user larry... ');
	$larry_user_ID = get_demo_user( 'larry', true, $users_Group, NULL )->ID;
	task_end();

	task_begin('Creating demo user kate... ');
	$kate_user_ID = get_demo_user( 'kate', true, $users_Group, NULL )->ID;
	task_end();
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
	global $DB;
	global $default_locale, $default_country;
	global $Plugins;
	global $install_test_features;
	global $user_org_IDs;
	global $mary_moderator_ID, $jay_moderator_ID, $dave_blogger_ID, $paul_blogger_ID, $larry_user_ID, $kate_user_ID;
	global $admin_user;
	global $create_demo_users;

	if( ! isset( $mary_moderator_ID ) )
	{
		$mary_moderator_ID = $admin_user->ID;
	}

	if( ! isset( $jay_moderator_ID ) )
	{
		$jay_moderator_ID = $admin_user->ID;
	}

	if( ! isset( $dave_blogger_ID ) )
	{
		$dave_blogger_ID = $admin_user->ID;
	}

	if( ! isset( $paul_blogger_ID ) )
	{
		$paul_blogger_ID = $admin_user->ID;
	}

	if( ! isset( $larry_user_ID ) )
	{
		$larry_user_ID = $admin_user->ID;
	}

	if( ! isset( $kate_user_ID ) )
	{
		$kate_user_ID = $admin_user->ID;
	}

	/**
	 * @var FileRootCache
	 */
	global $FileRootCache;

	load_class( 'collections/model/_blog.class.php', 'Blog' );
	load_class( 'files/model/_file.class.php', 'File' );
	load_class( 'files/model/_filetype.class.php', 'FileType' );
	load_class( 'links/model/_link.class.php', 'Link' );

	$create_sample_contents = param( 'create_sample_contents', 'string', '' );
	if( $create_sample_contents == 'all' )
	{	// Array contains which collections should be installed
		$install_collection_home =    1;
		$install_collection_bloga =   1;
		$install_collection_blogb =   1;
		$install_collection_photos =  1;
		$install_collection_forums =  1;
		$install_collection_manual =  1;
		$install_collection_tracker = 1;
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
		$install_collection_tracker = in_array( 'group', $collections );
	}

	// Store the item IDs in this array in order to create additional comments
	$additional_comments_item_IDs = array();

	// Use this var to shift the posts of the collections in time below:
	$timeshift = 0;

	if( $install_collection_home )
	{ // Install Home blog
		task_begin( 'Creating Home collection...' );
		create_demo_collection( 'main', $jay_moderator_ID, $create_demo_users, $timeshift );
		update_install_progress_bar();
		task_end();
	}

	if( $install_collection_bloga )
	{ // Install Blog A
		$timeshift += 86400;
		task_begin( 'Creating Blog A collection...' );
		create_demo_collection( 'blog_a', $jay_moderator_ID, $create_demo_users, $timeshift );
		update_install_progress_bar();
		task_end();
	}

	if( $install_collection_blogb )
	{ // Install Blog B
		$timeshift += 86400;
		task_begin( 'Creating Blog B collection...' );
		create_demo_collection( 'blog_b', $paul_blogger_ID, $create_demo_users, $timeshift );
		update_install_progress_bar();
		task_end();
	}

	if( $install_collection_photos )
	{ // Install Photos blog
		$timeshift += 86400;
		task_begin( 'Creating Photos collection...' );
		create_demo_collection( 'photo', $dave_blogger_ID, $create_demo_users, $timeshift );
		update_install_progress_bar();
		task_end();
	}

	if( $install_collection_forums )
	{ // Install Forums blog
		$timeshift += 86400;
		task_begin( 'Creating Forums collection...' );
		create_demo_collection( 'forum', $paul_blogger_ID, $create_demo_users, $timeshift );
		update_install_progress_bar();
		task_end();
	}

	if( $install_collection_manual )
	{ // Install Manual blog
		$timeshift += 86400;
		task_begin( 'Creating Manual collection...' );
		create_demo_collection( 'manual', $dave_blogger_ID, $create_demo_users, $timeshift );
		update_install_progress_bar();
		task_end();
	}

	if( $install_collection_tracker )
	{ // Install Tracker blog
		$timeshift += 86400;
		task_begin( 'Creating Tracker collection...' );
		create_demo_collection( 'group', $admin_user->ID, $create_demo_users, $timeshift );
		update_install_progress_bar();
		task_end();
	}

	task_begin( 'Setting default login and default messaging collection...' );
	$BlogCache = & get_BlogCache();
	if( $first_Blog = & $BlogCache->get_by_ID( 1, false, false ) )
	{ // Set first blog as default login and default messaging collection
		$DB->query( 'INSERT INTO T_settings ( set_name, set_value )
			VALUES ( '.$DB->quote( 'login_blog_ID' ).', '.$DB->quote( $first_Blog->ID ).' ),
						 ( '.$DB->quote( 'msg_blog_ID' ).', '.$DB->quote( $first_Blog->ID ).' )' );
	}
	update_install_progress_bar();
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