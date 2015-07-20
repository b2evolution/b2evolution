<?php
/**
 * This file implements the User class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// DEBUG: (Turn switch on or off to log debug info for specified category)
$GLOBALS['debug_perms'] = false;


load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * User Class
 *
 * @package evocore
 */
class User extends DataObject
{
	//var $postcode;
	var $age_min;
	var $age_max;
	var $login;
	var $pass;
	var $salt;
	var $firstname;
	var $lastname;
	var $nickname;
	var $locale;
	var $email;
	var $url;
	var $datecreated;
	var $lastseen_ts;
	var $level;
	var $avatar_file_ID;
	var $reg_ctry_ID;
	var $ctry_ID;
	var $rgn_ID;
	var $subrg_ID;
	var $city_ID;
	var $source;
	var $unsubscribe_key;
	var $gender;
	var $email_dom_ID;
	var $profileupdate_date;

	/**
	 * User account status
	 *
	 * 'new', 'activated', 'autoactivated', 'emailchanged', 'deactivated', 'failedactivation', 'closed'
	 *
	 * @var string
	 */
	var $status;

	/**
	 * Number of posts by this user. Use get_num_posts() to access this (lazy filled).
	 * @var array ( key = status name, value = number of posts with status, key = '' - total number with all statuses )
	 * @access protected
	 */
	var $_num_posts;

	/**
	 * Number of comments by this user. Use get_num_comments() to access this (lazy filled).
	 * @var array  ( key = status name, value = number of comments with status, key = '' - total number with all statuses )
	 * @access protected
	 */
	var $_num_comments;

	/**
	 * Number of files by this user. Use get_num_files() to access this (lazy filled).
	 * @var array  ( key = type, value = number of files with type )
	 * @access protected
	 */
	var $_num_files;

	/**
	 * The ID of the (primary, currently only) group of the user.
	 * @var integer
	 */
	var $grp_ID;

	/**
	 * Reference to group
	 * @see User::get_Group()
	 * @var Group
	 * @access protected
	 */
	var $Group;

	/**
	 * Country lazy filled
	 *
	 * @var country
	 */
	var $Country;
	var $Region;
	var $Subregion;
	var $City;

	/**
	 * Blog posts statuses permissions
	 */
	var $blog_post_statuses = array();

	/**
	 * Cache for perms.
	 * @access protected
	 * @var array
	 */
	var $cache_perms = array();


	/**
	 * User fields
	 */
	var $userfields = array();
	var $userfields_by_code = array();
	var $userfields_by_type = array();
	var $updated_fields = array();
	var $new_fields = array();

	/**
	 * Userfield defs
	 */
	var $userfield_defs;

	/**
	 * The previous values of significant fields which were changed and these changes should be notified.
	 */
	var $significant_changed_values = array();

	/**
	 * Constructor
	 *
	 * @param object DB row
	 */
	function User( $db_row = NULL )
	{
		global $default_locale, $Settings, $localtimenow;

		// Call parent constructor:
		parent::DataObject( 'T_users', 'user_', 'user_ID' );

		if( $db_row == NULL )
		{ // Setting those object properties, which are not "NULL" in DB (MySQL strict mode):

			// echo 'Creating blank user';
			$this->set( 'login', 'login' );
			$this->set_password( 'pass' );
			$this->set( 'locale',
				isset( $Settings )
					? $Settings->get('default_locale') // TODO: (settings) use "new users template setting"
					: $default_locale );
			$this->set( 'email', '' );	// fp> TODO: this is an invalid value. Saving the object without a valid email should fail! (actually: it should be fixed by providing a valid email)
			$this->set( 'level', isset( $Settings ) ? $Settings->get('newusers_level') : 0 );
			if( isset($localtimenow) )
			{
				$this->set_datecreated( $localtimenow );
				$this->set( 'profileupdate_date', date( 'Y-m-d', $localtimenow ) );
			}
			else
			{ // We don't know local time here!
				$this->set_datecreated( time() );
				$this->set( 'profileupdate_date', date( 'Y-m-d', time() ) );
			}

			if( isset( $Settings ) )
			{ // Set Group for this user:
				$GroupCache = & get_GroupCache();
				$new_user_Group = & $GroupCache->get_by_ID( $Settings->get( 'newusers_grp_ID' ) );
				$this->set_Group( $new_user_Group );
				// set status for this user
				$this->set( 'status', $Settings->get('newusers_mustvalidate') ? 'new' : 'autoactivated' );
			}
			else
			{
				// set status for this user
				$this->set( 'status', 'new' );
			}

			$this->set( 'unsubscribe_key', generate_random_key() );
		}
		else
		{
			// echo 'Instanciating existing user';
			$this->ID = $db_row->user_ID;
			$this->age_min = $db_row->user_age_min;
			$this->age_max = $db_row->user_age_max;
			$this->login = $db_row->user_login;
			$this->pass = $db_row->user_pass;
			$this->salt = $db_row->user_salt;
			$this->firstname = $db_row->user_firstname;
			$this->lastname = $db_row->user_lastname;
			$this->nickname = $db_row->user_nickname;
			$this->locale = $db_row->user_locale;
			$this->email = $db_row->user_email;
			$this->status = $db_row->user_status;
			$this->url = $db_row->user_url;
			$this->datecreated = $db_row->user_created_datetime;
			$this->lastseen_ts = $db_row->user_lastseen_ts;
			$this->level = $db_row->user_level;
			$this->unsubscribe_key = $db_row->user_unsubscribe_key;
			$this->gender = $db_row->user_gender;
			$this->avatar_file_ID = $db_row->user_avatar_file_ID;
			$this->reg_ctry_ID = $db_row->user_reg_ctry_ID;
			$this->ctry_ID = $db_row->user_ctry_ID;
			$this->rgn_ID = $db_row->user_rgn_ID;
			$this->subrg_ID = $db_row->user_subrg_ID;
			$this->city_ID = $db_row->user_city_ID;
			$this->source = $db_row->user_source;
			$this->profileupdate_date = $db_row->user_profileupdate_date;

			// Group for this user:
			$this->grp_ID = $db_row->user_grp_ID;
		}
	}


	/**
	 * Get this class db table config params
	 *
	 * @return array
	 */
	static function get_class_db_config()
	{
		static $user_db_config;

		if( !isset( $user_db_config ) )
		{
			$user_db_config = array_merge( parent::get_class_db_config(),
				array(
					'dbtablename'        => 'T_users',
					'dbprefix'           => 'user_',
					'dbIDname'           => 'user_ID',
				)
			);
		}

		return $user_db_config;
	}


	/**
	 * Get delete restriction settings
	 *
	 * @return array
	 */
	static function get_delete_restrictions()
	{
		// fp> These settings should probably be merged with the global database description used by the installer/upgrader. However I'm not sure about how compelx plugins would be able to integrate then...
		// asimo> Delete cascades and restrictions handling can't be handled globally in all circumstances because in a few places there is a logic behind the deletion:
		//        e.g. Delete user as spammer or simply delete the user
		// asimo> For sure in those cases when the cascade has no condition it would be faster to handled in on the db level.
		//        The current db_delete_where solution handles the delete cascade generally and handle the speciel cases in the cascaded classes as well ( e.g. delete files )
		return array(
				array( 'table'=>'T_blogs', 'fk'=>'blog_owner_user_ID', 'msg'=>T_('%d blogs owned by this user') ),
				//array( 'table'=>'T_items__item', 'fk'=>'post_lastedit_user_ID', 'msg'=>T_('%d posts last edited by this user') ),
				array( 'table'=>'T_items__item', 'fk'=>'post_assigned_user_ID', 'msg'=>T_('%d posts assigned to this user') ),
				// Do not delete user private messages
				//array( 'table'=>'T_messaging__message', 'fk'=>'msg_author_user_ID', 'msg'=>T_('The user has authored %d message(s)') ),
				//array( 'table'=>'T_messaging__threadstatus', 'fk'=>'tsta_user_ID', 'msg'=>T_('The user is part of %d messaging thread(s)') ),
			);
	}


	/**
	 * Get delete cascade settings
	 *
	 * @return array
	 */
	static function get_delete_cascades()
	{
		return array(
				array( 'table'=>'T_users__usersettings', 'fk'=>'uset_user_ID', 'msg'=>T_('%d user settings on collections') ),
				array( 'table'=>'T_sessions', 'fk'=>'sess_user_ID', 'msg'=>T_('%d sessions opened by this user'),
						'class'=>'Session', 'class_path'=>'sessions/model/_session.class.php' ),
				array( 'table'=>'T_coll_user_perms', 'fk'=>'bloguser_user_ID', 'msg'=>T_('%d user permissions on blogs') ),
				array( 'table'=>'T_comments__votes', 'fk'=>'cmvt_user_ID', 'msg'=>T_('%d user votes on comments') ),
				array( 'table'=>'T_subscriptions', 'fk'=>'sub_user_ID', 'msg'=>T_('%d blog subscriptions') ),
				array( 'table'=>'T_items__item', 'fk'=>'post_creator_user_ID', 'msg'=>T_('%d posts created by this user'),
						'class'=>'Item', 'class_path'=>'items/model/_item.class.php' ),
				array( 'table'=>'T_items__subscriptions', 'fk'=>'isub_user_ID', 'msg'=>T_('%d post subscriptions') ),
				array( 'table'=>'T_messaging__contact', 'fk'=>'mct_to_user_ID', 'msg'=>T_('%d contacts from other users contact list') ),
				array( 'table'=>'T_messaging__contact', 'fk'=>'mct_from_user_ID', 'msg'=>T_('%d contacts from this user contact list') ),
				array( 'table'=>'T_messaging__contact_groups', 'fk'=>'cgr_user_ID', 'msg'=>T_('%d contact groups') ),
				array( 'table'=>'T_messaging__contact_groupusers', 'fk'=>'cgu_user_ID', 'msg'=>T_('%d contacts from contact groups') ),
				array( 'table'=>'T_pluginusersettings', 'fk'=>'puset_user_ID', 'msg'=>T_('%d user settings on plugins') ),
				array( 'table'=>'T_users__fields', 'fk'=>'uf_user_ID', 'msg'=>T_('%d user fields') ),
				array( 'table'=>'T_users__postreadstatus', 'fk'=>'uprs_user_ID', 'msg'=>T_('%d recordings of a post having been read') ),
				array( 'table'=>'T_links', 'fk'=>'link_usr_ID', 'msg'=>T_('%d links to this user'),
						'class'=>'Link', 'class_path'=>'links/model/_link.class.php' ),
				array( 'table'=>'T_files', 'fk'=>'file_root_ID', 'and_condition'=>'file_root_type = "user"', 'msg'=>T_('%d files from this user file root') ),
				array( 'table'=>'T_email__campaign_send', 'fk'=>'csnd_user_ID', 'msg'=>T_('%d newsletter emails for this user') ),
				array( 'table'=>'T_users__reports', 'fk'=>'urep_target_user_ID', 'msg'=>T_('%d reports about this user') ),
				array( 'table'=>'T_users__reports', 'fk'=>'urep_reporter_ID', 'msg'=>T_('%d reports created by this user') ),
				array( 'table'=>'T_users__user_org', 'fk'=>'uorg_user_ID', 'msg'=>T_('%d organization membership') ),
			);
	}


	/**
	 * Initialize relations for restrict and cascade deletion.
	 * Spammer user's deletion will cascade more items.
	 *
	 * @param boolean Is this user spammer
	 */
	function init_relations( $is_spammer = false )
	{
		if( ! is_null( $this->delete_cascades ) || ! is_null( $this->delete_restrictions ) )
		{ // Initialize the relations only once
			return;
		}

		parent::init_relations();

		if( $is_spammer )
		{
			$this->delete_cascades[] = array( 'table'=>'T_messaging__message', 'fk'=>'msg_author_user_ID', 'msg'=>T_('%d messages from this user'),
					'class'=>'Message', 'class_path'=>'messaging/model/_message.class.php' );
			$this->delete_cascades[] = array( 'table'=>'T_messaging__threadstatus', 'fk'=>'tsta_user_ID', 'msg'=>T_('%d message read statuses from this user') );
			$this->delete_cascades[] = array( 'table'=>'T_comments', 'fk'=>'comment_author_user_ID', 'msg'=>T_('%d comments by this user'),
					'class'=>'Comment', 'class_path'=>'comments/model/_comment.class.php' );
			$this->delete_cascades[] = array( 'table'=>'T_links', 'fk'=>'link_creator_user_ID', 'msg'=>T_('%d links created by this user'),
					'class'=>'Link', 'class_path'=>'links/model/_link.class.php' );
		}
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		global $DB, $Settings, $UserSettings, $GroupCache, $Messages, $action;
		global $current_User, $Session, $localtimenow;

		$is_new_user = ( $this->ID == 0 );

		// ---- Login checking / START ----
		$edited_user_login = param( 'edited_user_login', 'string' );
		if( empty( $edited_user_login ) )
		{	// Empty login
			param_error( 'edited_user_login', T_('Please enter your login.') );
		}
		param_check_valid_login( 'edited_user_login' );

		$UserCache = & get_UserCache();
		$UserLogin = $UserCache->get_by_login( $edited_user_login );
		if( $UserLogin && $UserLogin->ID != $this->ID )
		{	// The login is already registered
			$login_error_message = T_( 'This login already exists.' );
			if( $current_User->check_perm( 'users', 'edit' ) )
			{
				$login_error_message = sprintf( T_( 'This login &laquo;%s&raquo; already exists. Do you want to <a %s>edit the existing user</a>?' ),
					$edited_user_login,
					'href="'.get_user_settings_url( 'profile', $UserLogin->ID ).'"' );
			}
			param_error( 'edited_user_login', $login_error_message );
		}

		if( !param_has_error( 'edited_user_login' ) )
		{	// We want all logins to be lowercase to guarantee uniqueness regardless of the database case handling for UNIQUE indexes:
			$this->set_from_Request( 'login', 'edited_user_login', true, 'utf8_strtolower' );
		}
		// ---- Login checking / END ----

		$is_identity_form = param( 'identity_form', 'boolean', false );
		$is_admin_form = param( 'admin_form', 'boolean', false );
		$has_full_access = $current_User->check_perm( 'users', 'edit' );
		$has_moderate_access = $current_User->check_perm( 'users', 'moderate' );

		// ******* Admin form or new user create ******* //
		// In both cases current user must have users edit permission!
		if( ( $is_admin_form || ( $is_identity_form && $is_new_user ) ) && $has_moderate_access )
		{ // level/group and email options are displayed on identity form only when creating a new user.
			if( $this->ID != 1 )
			{ // the admin user group can't be changed
				param_integer_range( 'edited_user_level', 0, 10, T_('User level must be between %d and %d.') );
				$this->set_from_Request( 'level', 'edited_user_level', true );

				$edited_user_Group = $GroupCache->get_by_ID( param( 'edited_user_grp_ID', 'integer' ) );
				if( /* User can change to any group: */
				    $has_full_access ||
				    /* User can change only to group with level lower than own group level: */
				    ( $has_moderate_access && $edited_user_Group->get( 'level' ) < $current_User->get_Group()->get( 'level' ) ) )
				{
					$this->set_Group( $edited_user_Group );
				}
			}

			param( 'edited_user_source', 'string', true );
			$this->set_from_Request('source', 'edited_user_source', true);

			// set email, without changing the user status
			$edited_user_email = utf8_strtolower( param( 'edited_user_email', 'string', true ) );
			param_check_not_empty( 'edited_user_email', T_('Please enter your e-mail address.') );
			param_check_email( 'edited_user_email', true );
			$this->set_email( $edited_user_email, false );

			if( $is_admin_form )
			{	// Admin form
				$notification_sender_email = utf8_strtolower( param( 'notification_sender_email', 'string', true ) );
				param_check_email( 'notification_sender_email' );
				if( ! empty( $notification_sender_email ) )
				{ // Change a value of setting
					$UserSettings->set( 'notification_sender_email', $notification_sender_email, $this->ID );
				}
				elseif( $UserSettings->get( 'notification_sender_email' , $this->ID ) != '' )
				{ // Delete a setting record from DB
					$UserSettings->delete( 'notification_sender_email', $this->ID );
				}

				$notification_sender_name = param( 'notification_sender_name', 'string', true );
				if( ! empty( $notification_sender_name ) )
				{ // Change a value of setting
					$UserSettings->set( 'notification_sender_name', $notification_sender_name, $this->ID );
				}
				elseif( $UserSettings->get( 'notification_sender_name' , $this->ID ) != '' )
				{ // Delete a setting record from DB
					$UserSettings->delete( 'notification_sender_name', $this->ID );
				}

				if( $has_full_access && !isset( $this->dbchanges['user_email'] ) )
				{	// If email address is not changed
					// Update status of email address in the T_email_address table
					$edited_email_status = param( 'edited_email_status', 'string' );
					$EmailAddressCache = & get_EmailAddressCache();
					$EmailAddress = & $EmailAddressCache->get_by_name( $this->get( 'email' ), false, false );
					if( !$EmailAddress && $edited_email_status != 'unknown' )
					{	// Create new record in the T_email_address table
						$EmailAddress = new EmailAddress();
						$EmailAddress->set( 'address', $this->get( 'email' ) );
					}
					if( !empty( $EmailAddress ) )
					{	// Save status of an email address
						$EmailAddress->set( 'status', $edited_email_status );
						$EmailAddress->dbsave();
					}
				}

				if( $current_User->check_perm( 'spamblacklist', 'edit' ) )
				{ // User can edit IP ranges
					// Update status of IP range in DB
					$edited_iprange_status = param( 'edited_iprange_status', 'string' );
					$IPRangeCache = & get_IPRangeCache();
					$IPRange = & $IPRangeCache->get_by_ip( int2ip( $UserSettings->get( 'created_fromIPv4', $this->ID ) ) );
					if( !$IPRange && !empty( $edited_iprange_status ) )
					{ // IP range doesn't exist in DB, Create new record
						$ip_24bit_start = ip2int( preg_replace( '#\.\d{1,3}$#i', '.0', int2ip( $UserSettings->get( 'created_fromIPv4', $this->ID ) ) ) );
						$ip_24bit_end = ip2int( preg_replace( '#\.\d{1,3}$#i', '.255', int2ip( $UserSettings->get( 'created_fromIPv4', $this->ID ) ) ) );
						$IPRange = new IPRange();
						$IPRange->set( 'IPv4start', $ip_24bit_start );
						$IPRange->set( 'IPv4end', $ip_24bit_end );
						$IPRange->set( 'user_count', 1 );
					}
					if( $IPRange )
					{ // Save status of IP range
						if( $IPRange->get( 'status' ) != 'blocked' && $edited_iprange_status == 'blocked' )
						{	// Status was changed to blocked, we should increase counter
							$IPRange->set( 'block_count', $IPRange->block_count + 1 );
						}
						else if( $IPRange->get( 'status' ) == 'blocked' && $edited_iprange_status != 'blocked' )
						{ // Status was changed from blocked to another, we should decrease counter
							$IPRange->set( 'block_count', $IPRange->block_count - 1 );
						}
						$IPRange->set( 'status', $edited_iprange_status, true );
						$IPRange->dbsave();
					}
				}

				if( $current_User->check_perm( 'stats', 'edit' ) )
				{ // User can edit Domains
					$DomainCache = & get_DomainCache();

					// Update status of Domain in DB
					$edited_domain_status = param( 'edited_domain_status', 'string' );
					$user_domain = $UserSettings->get( 'user_domain', $this->ID );
					$Domain = & $DomainCache->get_by_name( $user_domain, false, false );
					if( ! $Domain && $edited_domain_status != 'unknown' && ! empty( $user_domain ) )
					{ // Domain doesn't exist in DB, Create new record
						$Domain = new Domain();
						$Domain->set( 'name', $user_domain );
					}
					if( $Domain )
					{ // Save status of Domain
						$Domain->set( 'status', $edited_domain_status, true );
						$Domain->dbsave();
					}

					// Update status of Initial referer in DB
					load_funcs('sessions/model/_hitlog.funcs.php');
					$edited_initial_referer_status = param( 'edited_initial_referer_status', 'string' );
					$initial_referer = $UserSettings->get( 'initial_referer', $this->ID );
					$initial_referer_domain = url_part( $initial_referer, 'host' );
					$Domain = & get_Domain_by_url( $initial_referer );
					if( ! $Domain && $edited_initial_referer_status != 'unknown' && ! empty( $initial_referer_domain ) )
					{ // Domain doesn't exist in DB, Create new record
						$Domain = new Domain();
						$Domain->set( 'name', $initial_referer_domain );
					}
					if( $Domain )
					{ // Save status of Domain
						$Domain->set( 'status', $edited_initial_referer_status, true );
						$Domain->dbsave();
					}
				}
			}
		}

		// ******* Identity form ******* //
		if( $is_identity_form )
		{
			$can_edit_users = $current_User->check_perm( 'users', 'edit' );
			$edited_user_perms = array( 'edited-user', 'edited-user-required' );

			global $edited_user_age_min, $edited_user_age_max;
			param( 'edited_user_age_min', 'string', true );
			param( 'edited_user_age_max', 'string', true );
			param_check_interval( 'edited_user_age_min', 'edited_user_age_max', T_('Age must be a number.'), T_('The first age must be lower than (or equal to) the second.') );
			if( !param_has_error( 'edited_user_age_min' ) && $Settings->get( 'minimum_age' ) > 0 &&
			    !empty( $edited_user_age_min ) && $edited_user_age_min < $Settings->get( 'minimum_age' ) )
			{	// Limit user by minimum age
				param_error( 'edited_user_age_min', sprintf( T_('You must be at least %d years old to use this service.'), $Settings->get( 'minimum_age' ) ) );
			}
			$this->set_from_Request( 'age_min', 'edited_user_age_min', true );
			$this->set_from_Request( 'age_max', 'edited_user_age_max', true );

			$firstname_editing = $Settings->get( 'firstname_editing' );
			if( ( in_array( $firstname_editing, $edited_user_perms ) && $this->ID == $current_User->ID ) || ( $firstname_editing != 'hidden' && $can_edit_users ) )
			{	// User has a permissions to save Firstname
				param( 'edited_user_firstname', 'string', true );
				if( $firstname_editing == 'edited-user-required' )
				{	// First name is required
					if( $can_edit_users )
					{	// Display a note message if user can edit all users
						param_add_message_to_Log( 'edited_user_firstname', T_('Please enter your first name.'), 'note' );
					}
					else
					{	// Display an error message
						param_check_not_empty( 'edited_user_firstname', T_('Please enter your first name.') );
					}
				}
				$this->set_from_Request('firstname', 'edited_user_firstname', true);
			}

			$lastname_editing = $Settings->get( 'lastname_editing' );
			if( ( in_array( $lastname_editing, $edited_user_perms ) && $this->ID == $current_User->ID ) || ( $lastname_editing != 'hidden' && $can_edit_users ) )
			{	// User has a permissions to save Lastname
				param( 'edited_user_lastname', 'string', true );
				if( $lastname_editing == 'edited-user-required' )
				{	// Last name is required
					if( $can_edit_users )
					{	// Display a note message if user can edit all users
						param_add_message_to_Log( 'edited_user_lastname', T_('Please enter last name.'), 'note' );
					}
					else
					{	// Display an error message
						param_check_not_empty( 'edited_user_lastname', T_('Please enter last name.') );
					}
				}
				$this->set_from_Request('lastname', 'edited_user_lastname', true);
			}

			$nickname_editing = $Settings->get( 'nickname_editing' );
			if( ( in_array( $nickname_editing, $edited_user_perms ) && $this->ID == $current_User->ID ) || ( $nickname_editing != 'hidden' && $can_edit_users ) )
			{	// User has a permissions to save Nickname
				param( 'edited_user_nickname', 'string', true );
				if( $nickname_editing == 'edited-user-required' )
				{	// Nickname is required
					if( $can_edit_users )
					{	// Display a note message if user can edit all users
						param_add_message_to_Log( 'edited_user_nickname', T_('Please enter your nickname.'), 'note' );
					}
					else
					{	// Display an error message
						param_check_not_empty( 'edited_user_nickname', T_('Please enter your nickname.') );
					}
				}
				$this->set_from_Request('nickname', 'edited_user_nickname', true);
			}

			param( 'edited_user_gender', 'string', '' );
			if( param_check_gender( 'edited_user_gender', $Settings->get( 'registration_require_gender' ) == 'required' ) )
			{
				$this->set_from_Request('gender', 'edited_user_gender', true);
			}

			// ---- Locations / START ----
			load_funcs( 'regional/model/_regional.funcs.php' );

			if( user_country_visible() )
			{ // Save country
				$country_ID = param( 'edited_user_ctry_ID', 'integer', true );
				$country_is_required = ( $Settings->get( 'location_country' ) == 'required' && countries_exist() );
				if( $country_is_required && $can_edit_users && $country_ID == 0 )
				{ // Display a note message if user can edit all users
					param_add_message_to_Log( 'edited_user_ctry_ID', T_('Please select a country.'), 'note' );
				}
				else
				{ // Display an error message
					param_check_number( 'edited_user_ctry_ID', T_('Please select a country.'), $country_is_required );
				}
				$this->set_from_Request( 'ctry_ID', 'edited_user_ctry_ID', true );
			}

			if( user_region_visible() )
			{ // Save region
				$region_ID = param( 'edited_user_rgn_ID', 'integer', true );
				$region_is_required = ( $Settings->get( 'location_region' ) == 'required' && regions_exist( $country_ID ) );
				if( $region_is_required && $can_edit_users && $region_ID == 0 )
				{ // Display a note message if user can edit all users
					param_add_message_to_Log( 'edited_user_rgn_ID', T_('Please select a region.'), 'note' );
				}
				else
				{ // Display an error message
					param_check_number( 'edited_user_rgn_ID', T_('Please select a region'), $region_is_required );
				}
				$this->set_from_Request( 'rgn_ID', 'edited_user_rgn_ID', true );
			}

			if( user_subregion_visible() )
			{ // Save subregion
				$subregion_ID = param( 'edited_user_subrg_ID', 'integer', true );
				$subregion_is_required = ( $Settings->get( 'location_subregion' ) == 'required' && subregions_exist( $region_ID ) );
				if( $subregion_is_required && $can_edit_users && $subregion_ID == 0 )
				{ // Display a note message if user can edit all users
					param_add_message_to_Log( 'edited_user_subrg_ID', T_('Please select a sub-region.'), 'note' );
				}
				else
				{ // Display an error message
					param_check_number( 'edited_user_subrg_ID', T_('Please select a sub-region.'), $subregion_is_required );
				}
				$this->set_from_Request( 'subrg_ID', 'edited_user_subrg_ID', true );
			}

			if( user_city_visible() )
			{ // Save city
				$city_ID = param( 'edited_user_city_ID', 'integer', true );
				$city_is_required = ( $Settings->get( 'location_city' ) == 'required' && cities_exist( $country_ID, $region_ID, $subregion_ID ) );
				if( $city_is_required && $can_edit_users && $city_ID == 0 )
				{ // Display a note message if user can edit all users
					param_add_message_to_Log( 'edited_user_city_ID', T_('Please select a city.'), 'note' );
				}
				else
				{ // Display an error message
					param_check_number( 'edited_user_city_ID', T_('Please select a city.'), $city_is_required );
				}
				$this->set_from_Request( 'city_ID', 'edited_user_city_ID', true );
			}
			// ---- Locations / END ----


			// ---- Organizations / START ----
			$organizations = param( 'organizations', 'array:string' );
			$org_roles = param( 'org_roles', 'array:string' );
			$this->update_organizations( $organizations, $org_roles );
			// ---- Organizations / END ----


			// ---- Additional Fields / START ----

			// Load all defined userfields for following checking of required fields
			$this->userfield_defs_load();

			// EXPERIMENTAL user fields & EXISTING fields:
			// Get indices of existing userfields:
			$userfield_IDs = $DB->get_results( '
						SELECT uf_ID, uf_ufdf_ID
							FROM T_users__fields
						 WHERE uf_user_ID = '.$this->ID );

			foreach( $userfield_IDs as $userfield )
			{
				$field_type = ( $this->userfield_defs[$userfield->uf_ufdf_ID][0] == 'text' ) ? 'text' : 'string';
				$uf_val = param( 'uf_'.$userfield->uf_ID, $field_type, '' );

				if( $this->userfield_defs[$userfield->uf_ufdf_ID][0] == 'list' && $uf_val == '---' )
				{	// Option list has a value '---' for empty value
					$uf_val = '';
				}

				$uf_val = trim( strip_tags( $uf_val ) );
				if( empty( $uf_val ) && $this->userfield_defs[$userfield->uf_ufdf_ID][2] == 'require' )
				{	// Display error for empty required field
					if( $current_User->check_perm( 'users', 'edit' ) )
					{	// Display a note message if user can edit all users
						param_add_message_to_Log( 'uf_'.$userfield->uf_ID, sprintf( T_('Please enter a value for the field "%s".'), $this->userfield_defs[$userfield->uf_ufdf_ID][1] ), 'note' );
					}
					else
					{	// Display an error message
						param_error( 'uf_'.$userfield->uf_ID, T_('Please enter a value.') );
					}
				}
				else
				{	// Update field
					if( $this->userfield_defs[$userfield->uf_ufdf_ID][0] == 'url' )
					{	// Check url fields
						param_check_url( 'uf_'.$userfield->uf_ID, 'commenting' );
					}
					if( $this->userfield_defs[$userfield->uf_ufdf_ID][4] == 'list' )
					{	// Option "Multiple values" == "List style"
						// Split by comma and save each phrase as separate field
						$uf_val = explode( ',', $uf_val );
						foreach( $uf_val as $v => $val )
						{
							$val = trim( $val );
							if( $v == 0 )
							{	// Update field with first value
								$this->userfield_update( $userfield->uf_ID, $val );
							}
							else if( !empty( $val ) )
							{	// Add a new field for new values
								$this->userfield_add( $userfield->uf_ufdf_ID, $val );
							}
						}
					}
					else
					{	// Forbidden & Allowed fields
						$this->userfield_update( $userfield->uf_ID, $uf_val );
					}
				}
			}

			// Duplicate fields:
			if( $is_new_user )
			{
				$user_id = param( 'orig_user_ID', 'integer', 0 );
				if( $user_id !== 0 )
				{
					$userfield_IDs = $DB->get_results( '
								SELECT uf_ID, uf_ufdf_ID
									FROM T_users__fields
								 WHERE uf_user_ID = '.$user_id );
					foreach( $userfield_IDs as $userfield_ID )
					{
						$uf_val = param( 'uf_'.$userfield_ID->uf_ID, 'string', '' );
						$uf_type = $userfield_ID->uf_ufdf_ID;
						if( !empty($uf_val) )
						{
							$this->userfield_add( $uf_type, $uf_val );
						}
					}
				}
			}

			$uf_new_fields = param( 'uf_new', 'array:array:string' );	// Recommended & required fields (it still not saved in DB)
			$uf_add_fields = param( 'uf_add', 'array:array:string' );	// Added fields

			// Add a new field: (JS is not enabled)
			if( $action == 'add_field' )
			{	// Button 'Add' new field is pressed
				$new_field_type = param( 'new_field_type', 'integer', 0 );
				if( empty( $new_field_type ) )
				{	// We cannot add a new field without type
					param_error( 'new_field_type', T_('Please select a field type.') );
				}
				else
				{	// Save an adding field(in the array) to display it again if errors will be exist
					$new_field_type_exists = false;

					if( $this->userfield_defs[$new_field_type][4] == 'allowed' || $this->userfield_defs[$new_field_type][4] == 'list' )
					{	// This field can be duplicated
						global $add_field_types;
						$add_field_types = array( $new_field_type );
					}
					else
					{	// We should to solve we can add this field or don't
						if( ! isset( $uf_new_fields[$new_field_type] ) && ! isset( $uf_add_fields[$new_field_type] ) )
						{	// User is adding this field first time
							if( is_array( $userfield_IDs ) && count( $userfield_IDs ) > 0 )
							{	// User has fields that saved in DB
								foreach( $userfield_IDs as $userfield )
								{
									if( $userfield->uf_ufdf_ID == $new_field_type )
									{	// New adding field already exists for current user in DB
										$new_field_type_exists = true;
										break;
									}
								}
							}
							if( ! $new_field_type_exists )
							{	// Field doesn't still exist for current user
								global $add_field_types;
								$add_field_types = array( $new_field_type );
							}
						}
						else
						{	// Field exists, no duplicates available
							$new_field_type_exists = true;
						}

						if( $new_field_type_exists )
						{	// Field already is added for current user, we should display error about this
							param_error( 'new_field_type', T_('You already added this field, please select another.') );
						}
					}

					if( ! $new_field_type_exists )
					{	// Mark a new field to enter a value
						param_error( 'uf_add['.$new_field_type.'][]', T_('Please enter a value in this new field.') );
					}
				}
			}

			// Save a New recommended & require fields AND Adding fields
			if( count( $uf_new_fields ) > 0 || count( $uf_add_fields ) > 0 )
			{
				$uf_fields = array(
					'new' => $uf_new_fields,
					'add' => $uf_add_fields
				);
				foreach( $uf_fields as $uf_type => $uf_new_fields )
				{
					if( $uf_type == 'add' )
					{	// Save an adding fields to display it again if errors will be exist
						global $add_field_types;
						if( ! isset( $add_field_types ) )
						{	// Don't rewrite already existing array
							$add_field_types = array();
						}
					}
					foreach( $uf_new_fields as $uf_new_id => $uf_new_vals )
					{
						foreach( $uf_new_vals as $uf_new_val )
						{
							if( $this->userfield_defs[$uf_new_id][0] == 'list' && $uf_new_val == '---' )
							{	// Option list has a value '---' for empty value
								$uf_new_val = '';
							}

							$uf_new_val = trim( strip_tags( $uf_new_val ) );
							if( $uf_new_val != '' )
							{	// Insert a new field in DB if it is filled
								if( $this->userfield_defs[$uf_new_id][0] == 'url' )
								{	// Check url fields
									param_check_url( 'uf_'.$uf_type.'['.$uf_new_id.'][]', 'commenting' );
								}
								if( $this->userfield_defs[$uf_new_id][4] == 'list' )
								{	// Option "Multiple values" == "List style"
									// Split by comma and save each phrase as separate field
									$uf_new_val = explode( ',', $uf_new_val );
									foreach( $uf_new_val as $val )
									{
										$val = trim( $val );
										if( !empty( $val ) )
										{	// Exclude empty values(spaces)
											$this->userfield_add( (int)$uf_new_id, $val );
										}
									}
								}
								else
								{	// Forbidden & Allowed fields
									$this->userfield_add( (int)$uf_new_id, $uf_new_val );
								}
							}
							elseif( empty( $uf_new_val ) && $this->userfield_defs[$uf_new_id][2] == 'require' )
							{	// Display error for empty required field & new adding field
								if( $current_User->check_perm( 'users', 'edit' ) )
								{	// Display a note message if user can edit all users
									param_add_message_to_Log( 'uf_'.$uf_type.'['.$uf_new_id.'][]', sprintf( T_('Please enter a value for the field "%s".'), $this->userfield_defs[$uf_new_id][1] ), 'note' );
								}
								else
								{	// Display an error message
									param_error( 'uf_'.$uf_type.'['.$uf_new_id.'][]', T_('Please enter a value.') );
								}
							}

							if( $uf_type == 'add' )
							{	// Save new added field, it used on the _user_identity.form
								$add_field_types[] = $uf_new_id;
							}
						}
					}
				}
			}
			// ---- Additional Fields / END ----

			// update profileupdate_date, because a publicly visible user property was changed
			$this->set_profileupdate_date();
		}

		// ******* Password form ******* //

		$is_password_form = param( 'password_form', 'boolean', false );
		if( $is_password_form || $is_new_user )
		{
			$reqID = param( 'reqID', 'string', '' );

			global $edited_user_pass1, $edited_user_pass2;

			$edited_user_pass1 = param( 'edited_user_pass1', 'string', true );
			$edited_user_pass2 = param( 'edited_user_pass2', 'string', true );

			// Remove the invalid chars from password vars
			$edited_user_pass1 = preg_replace( '/[<>&]/', '', $edited_user_pass1 );
			$edited_user_pass2 = preg_replace( '/[<>&]/', '', $edited_user_pass2 );

			if( $is_new_user || ( !empty( $reqID ) && $reqID == $Session->get( 'core.changepwd.request_id' ) ) )
			{ // current password is not required:
				//   - new user creating process
				//   - password change requested by email

				if( param_check_passwords( 'edited_user_pass1', 'edited_user_pass2', true, $Settings->get('user_minpwdlen') ) )
				{ // We can set password
					$this->set_password( $edited_user_pass2 );
				}
			}
			else
			{
				// ******* Password edit form ****** //

				$current_user_pass = param( 'current_user_pass', 'string', true );

				if( $this->ID != $current_User->ID )
				{ // Set the messages when admin changes a password of other user
					$checkpwd_params = array(
							'msg_pass_new'   => T_('Please enter new password.'),
							'msg_pass_twice' => T_('Please enter new password twice.'),
						);
				}
				else
				{ // Use default messages
					$checkpwd_params = array();
				}

				if( ! strlen( $current_user_pass ) )
				{
					param_error( 'current_user_pass' , T_('Please enter your current password.') );
					param_check_passwords( 'edited_user_pass1', 'edited_user_pass2', true, $Settings->get('user_minpwdlen'), $checkpwd_params );
				}
				else
				{
					if( $has_full_access && $this->ID != $current_User->ID )
					{ // Admin is changing a password of other user, Check a password of current admin
						$pass_to_check = $current_User->pass;
						$current_user_salt = $current_User->salt;
					}
					else
					{ // User is changing own pasword
						$pass_to_check = $this->pass;
						$current_user_salt = $this->salt;
					}
					if( $pass_to_check == md5( $current_user_salt.$current_user_pass, true ) )
					{
						if( param_check_passwords( 'edited_user_pass1', 'edited_user_pass2', true, $Settings->get('user_minpwdlen'), $checkpwd_params ) )
						{ // We can set password
							$this->set_password( $edited_user_pass2 );
						}
					}
					else
					{
						param_error('current_user_pass' , T_('Your current password is incorrect.') );
						param_check_passwords( 'edited_user_pass1', 'edited_user_pass2', true, $Settings->get('user_minpwdlen'), $checkpwd_params );
					}
				}

			}
		}


		// Used in Preferences & Notifications forms
		$has_messaging_perm = $this->check_perm( 'perm_messaging', 'reply' );

		// ******* Preferences form ******* //

		$is_preferences_form = param( 'preferences_form', 'boolean', false );

		if( $is_preferences_form )
		{
			// Other preferences
			param( 'edited_user_locale', 'string', true );
			$this->set_from_Request('locale', 'edited_user_locale', true);

			// Session timeout
			$edited_user_timeout_sessions = param( 'edited_user_timeout_sessions', 'string', NULL );
			if( isset( $edited_user_timeout_sessions ) && ( $current_User->ID == $this->ID  || $current_User->check_perm( 'users', 'edit' ) ) )
			{
				switch( $edited_user_timeout_sessions )
				{
					case 'default':
						$UserSettings->set( 'timeout_sessions', NULL, $this->ID );
						break;
					case 'custom':
						$UserSettings->set( 'timeout_sessions', param_duration( 'timeout_sessions' ), $this->ID );
						break;
				}
			}

			$UserSettings->set( 'show_online', param( 'edited_user_showonline', 'integer', 0 ), $this->ID );
		}

		// ******* Notifications form ******* //
		$is_subscriptions_form = param( 'subscriptions_form', 'boolean', false );

		if( $is_subscriptions_form )
		{
			if( $action == 'subscribe' )
			{ // Do only subscribe to new blog (Don't update the user's settings from the same form)

				// A selected blog to subscribe
				$subscribe_blog_ID = param( 'subscribe_blog', 'integer', 0 );
				// Get checkbox values:
				$sub_items    = param( 'sub_items_new',    'integer', 0 );
				$sub_comments = param( 'sub_comments_new', 'integer', 0 );

				// Note: we do not check if subscriptions are allowed here, but we check at the time we're about to send something
				if( $subscribe_blog_ID && ( $sub_items || $sub_comments ) )
				{ // We need to record values:
					$DB->query( 'REPLACE INTO T_subscriptions( sub_coll_ID, sub_user_ID, sub_items, sub_comments )
					  VALUES ( '.$DB->quote( $subscribe_blog_ID ).', '.$DB->quote( $this->ID ).', '.$DB->quote( $sub_items ).', '.$DB->quote( $sub_comments ).' )' );

					$Messages->add( T_('Subscriptions have been changed.'), 'success' );
				}
				else
				{ // Display an error message to inform user about incorrect actions
					$Messages->add( T_('Please select at least one setting to subscribe on the selected blog.'), 'error' );
				}
			}
			else
			{ // Update user's settings

				// Email communication
				$edited_user_email = utf8_strtolower( param( 'edited_user_email', 'string', true ) );
				param_check_not_empty( 'edited_user_email', T_('Please enter your e-mail address.') );
				param_check_email( 'edited_user_email', true );
				$this->set_email( $edited_user_email );

				// set messaging options
				if( $has_messaging_perm )
				{
					$UserSettings->set( 'enable_PM', param( 'PM', 'integer', 0 ), $this->ID );
				}
				$emails_msgform = $Settings->get( 'emails_msgform' );
				if( ( $emails_msgform == 'userset' ) || ( ( $emails_msgform == 'adminset' ) && ( $current_User->check_perm( 'users', 'edit' ) ) ) )
				{ // enable email option is displayed only if user can set or if admin can set and current User is an administrator
					$UserSettings->set( 'enable_email', param( 'email', 'integer', 0 ), $this->ID );
				}

				// Email format
				$UserSettings->set( 'email_format', param( 'edited_user_email_format', 'string', 'auto' ), $this->ID );

				// set notification options
				if( $has_messaging_perm )
				{ // update 'notify messages' only if user has messaging rights and this option was displayed
					$UserSettings->set( 'notify_messages', param( 'edited_user_notify_messages', 'integer', 0 ), $this->ID );
					$UserSettings->set( 'notify_unread_messages', param( 'edited_user_notify_unread_messages', 'integer', 0 ), $this->ID );
				}
				if( $this->check_role( 'post_owner' ) )
				{ // update 'notify_published_comments' only if user has at least one post or user has right to create new post
					$UserSettings->set( 'notify_published_comments', param( 'edited_user_notify_publ_comments', 'integer', 0 ), $this->ID );
				}
				$is_comment_moderator = $this->check_role( 'comment_moderator' );
				if( $is_comment_moderator || $this->check_role( 'comment_editor' ) )
				{ // update 'notify_comment_moderation' only if user is comment moderator/editor at least in one blog
					$UserSettings->set( 'notify_comment_moderation', param( 'edited_user_notify_cmt_moderation', 'integer', 0 ), $this->ID );
				}
				if( $this->check_perm( 'admin', 'restricted', false ) )
				{ // update 'notify_meta_comments' only if edited user has a permission to back-office
					$UserSettings->set( 'notify_meta_comments', param( 'edited_user_notify_meta_comments', 'integer', 0 ), $this->ID );
				}
				if( $is_comment_moderator )
				{ // update 'send_cmt_moderation_reminder' only if user is comment moderator at least in one blog
					$UserSettings->set( 'send_cmt_moderation_reminder', param( 'edited_user_send_cmt_moderation_reminder', 'integer', 0 ), $this->ID );
				}
				if( $this->check_role( 'post_moderator' ) )
				{ // update 'notify_post_moderation' and 'send_cmt_moderation_reminder' only if user is post moderator at least in one blog
					$UserSettings->set( 'notify_post_moderation', param( 'edited_user_notify_post_moderation', 'integer', 0 ), $this->ID );
					$UserSettings->set( 'send_pst_moderation_reminder', param( 'edited_user_send_pst_moderation_reminder', 'integer', 0 ), $this->ID );
				}
				if( $this->grp_ID == 1 )
				{
					$UserSettings->set( 'send_activation_reminder', param( 'edited_user_send_activation_reminder', 'integer', 0 ), $this->ID );
				}

				if( $this->check_perm( 'users', 'edit' ) )
				{ // edited user has permission to edit all users, save notification preferences
					$UserSettings->set( 'notify_new_user_registration', param( 'edited_user_notify_new_user_registration', 'integer', 0 ), $this->ID );
					$UserSettings->set( 'notify_activated_account', param( 'edited_user_notify_activated_account', 'integer', 0 ), $this->ID );
					$UserSettings->set( 'notify_closed_account', param( 'edited_user_notify_closed_account', 'integer', 0 ), $this->ID );
					$UserSettings->set( 'notify_reported_account', param( 'edited_user_notify_reported_account', 'integer', 0 ), $this->ID );
					$UserSettings->set( 'notify_changed_account', param( 'edited_user_notify_changed_account', 'integer', 0 ), $this->ID );
				}

				if( $this->check_perm( 'options', 'edit' ) )
				{ // edited user has permission to edit options, save notification preferences
					$UserSettings->set( 'notify_cronjob_error', param( 'edited_user_notify_cronjob_error', 'integer', 0 ), $this->ID );
				}

				// Newsletter
				$UserSettings->set( 'newsletter_news', param( 'edited_user_newsletter_news', 'integer', 0 ), $this->ID );
				$UserSettings->set( 'newsletter_ads', param( 'edited_user_newsletter_ads', 'integer', 0 ), $this->ID );

				// Emails limit per day
				param_integer_range( 'edited_user_notification_email_limit', 0, 999, T_('Notificaiton email limit must be between %d and %d.') );
				$UserSettings->set( 'notification_email_limit', param( 'edited_user_notification_email_limit', 'integer', 0 ), $this->ID );
				param_integer_range( 'edited_user_newsletter_limit', 0, 999, T_('Newsletter limit must be between %d and %d.') );
				$UserSettings->set( 'newsletter_limit', param( 'edited_user_newsletter_limit', 'integer', 0 ), $this->ID );

				/**
				 * Update the subscriptions:
				 */
				$subs_blog_IDs = param( 'subs_blog_IDs', 'string', true );
				$subs_item_IDs = param( 'subs_item_IDs', 'string', true );

				// Work the blogs:
				$subscription_values = array();
				$unsubscribed = array();
				$subs_blog_IDs = explode( ',', $subs_blog_IDs );
				foreach( $subs_blog_IDs as $loop_blog_ID )
				{
					// Make sure no dirty hack is coming in here:
					$loop_blog_ID = intval( $loop_blog_ID );

					// Get checkbox values:
					$sub_items    = param( 'sub_items_'.$loop_blog_ID,    'integer', 0 );
					$sub_comments = param( 'sub_comments_'.$loop_blog_ID, 'integer', 0 );

					if( $sub_items || $sub_comments )
					{	// We have a subscription for this blog
						$subscription_values[] = "( $loop_blog_ID, $this->ID, $sub_items, $sub_comments )";
					}
					else
					{	// No subscription here:
						$unsubscribed[] = $loop_blog_ID;
					}
				}

				// Note: we do not check if subscriptions are allowed here, but we check at the time we're about to send something
				if( count( $subscription_values ) )
				{	// We need to record values:
					$DB->query( 'REPLACE INTO T_subscriptions( sub_coll_ID, sub_user_ID, sub_items, sub_comments )
												VALUES '.implode( ', ', $subscription_values ) );
				}

				if( count( $unsubscribed ) )
				{	// We need to make sure some values are cleared:
					$DB->query( 'DELETE FROM T_subscriptions
												 WHERE sub_user_ID = '.$this->ID.'
													 AND sub_coll_ID IN ('.implode( ', ', $unsubscribed ).')' );
				}

				// Individual post subscriptions
				if( !empty( $subs_item_IDs ) )
				{ // user was subscribed to at least one post update notification
					$subs_item_IDs = explode( ',', $subs_item_IDs );
					$unsubscribed = array();
					foreach( $subs_item_IDs as $loop_item_ID )
					{
						if( !param( 'item_sub_'.$loop_item_ID, 'integer', 0 ) )
						{ // user wants to unsubscribe from this post notifications
							$unsubscribed[] = $loop_item_ID;
						}
					}
					if( !empty( $unsubscribed ) )
					{ // unsubscribe list is not empty, delete not wanted subscriptions
						$DB->query( 'DELETE FROM T_items__subscriptions
												 WHERE isub_user_ID = '.$this->ID.'
													 AND isub_item_ID IN ('.implode( ', ', $unsubscribed ).')' );
					}
				}
			}
		}

		// ******* Advanced form ******* //
		$is_advanced_form = param( 'advanced_form', 'boolean', false );

		if( $is_advanced_form )
		{
			$UserSettings->set( 'admin_skin', param( 'edited_user_admin_skin', 'string' ), $this->ID );

			// Action icon params:
			param_integer_range( 'edited_user_action_icon_threshold', 1, 5, T_('The threshold must be between 1 and 5.') );
			$UserSettings->set( 'action_icon_threshold', param( 'edited_user_action_icon_threshold', 'integer', true ), $this->ID );

			param_integer_range( 'edited_user_action_word_threshold', 1, 5, T_('The threshold must be between 1 and 5.') );
			$UserSettings->set( 'action_word_threshold', param( 'edited_user_action_word_threshold', 'integer'), $this->ID );

			$UserSettings->set( 'display_icon_legend', param( 'edited_user_legend', 'integer', 0 ), $this->ID );

			// Set bozo validador activation
			$UserSettings->set( 'control_form_abortions', param( 'edited_user_bozo', 'integer', 0 ), $this->ID );

			// Focus on first
			$UserSettings->set( 'focus_on_first_input', param( 'edited_user_focusonfirst', 'integer', 0 ), $this->ID );

			// Results per page
			$edited_user_results_page_size = param( 'edited_user_results_page_size', 'integer', NULL );
			if( isset( $edited_user_results_page_size ) )
			{
				$UserSettings->set( 'results_per_page', $edited_user_results_page_size, $this->ID );
			}
		}

		if( $is_preferences_form || ( $is_identity_form && $is_new_user ) )
		{	// Multiple session
			$multiple_sessions = $Settings->get( 'multiple_sessions' );
			if( ( $multiple_sessions != 'adminset_default_no' && $multiple_sessions != 'adminset_default_yes' ) || $current_User->check_perm( 'users', 'edit' ) )
			{
				$UserSettings->set( 'login_multiple_sessions', param( 'edited_user_set_login_multiple_sessions', 'integer', 0 ), $this->ID );
			}
		}

		return ! param_errors_detected();
	}


	/**
	 * Get a param
	 *
	 * @param string the parameter
	 */
	function get( $parname )
	{
		if( $this->check_status( 'is_closed' ) && ( !is_admin_page() ) && ( $parname != 'login' ) && ( $parname != 'status' ) )
		{ // if this account is closed and we are not in backoffice, don't return other information then login or status
			return NULL;
		}

		switch( $parname )
		{
			case 'fullname':
				return trim($this->firstname.' '.$this->lastname);

			case 'preferredname':
				return $this->get_preferred_name();

			case 'num_posts':
				return $this->get_num_posts();

			case 'num_comments':
				return $this->get_num_comments();

			default:
			// All other params:
				return parent::get( $parname );
		}
	}


	/**
	 * Get the name of the account with complete details for admin select lists
	 *
	 * @return string
	 */
	function get_account_name()
	{
		if( $this->check_status( 'is_closed' ) && ( !is_admin_page() ) )
		{ // don't return closed accounts information except login name
			return $this->login;
		}

		return $this->login.' - '.$this->firstname.' '.$this->lastname.' ('.$this->nickname.')';
	}


	/**
	 * Get link to User
	 *
	 * @return string
	 */
	function get_link( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'format'       => 'htmlbody',
				'link_to'      => 'userpage', // userurl userpage 'userurl>userpage'
				'link_text'    => 'preferredname', // avatar | preferredname
				'link_rel'     => '',
				'link_class'   => '',
				'thumb_size'   => 'crop-top-32x32',
				'thumb_class'  => '',
			), $params );

		if( $params['link_text'] == 'avatar' )
		{
			$r = $this->get_avatar_imgtag( $params['thumb_size'], $params['thumb_class'] );
		}
		else
		{
			$r = $this->dget( 'preferredname', $params['format'] );
		}

		switch( $params['link_to'] )
		{
			case 'userpage':
			case 'userpage>userurl':
				$url = $this->get_userpage_url();
				break;

			case 'userurl':
				$url = $this->url;
				break;

			case 'userurl>userpage':
				// We give priority to user submitted url:
				if( utf8_strlen($this->url) > 10 )
				{
					$url = $this->url;
				}
				else
				{
					$url = $this->get_userpage_url();
				}
				break;
		}

		if( !empty($url) )
		{
			$link = '<a href="'.$url.'"';
			if( !empty($params['link_rel']) )
			{
				$link .= ' rel="'.$params['link_rel'].'"';
			}
			if( !empty($params['link_class']) )
			{
				$link .= ' class="'.$params['link_class'].'"';
			}
			$r = $link.'>'.$r.'</a>';
		}

		return $r;
	}


	/**
	 * Get preferred name of the user
	 *
	 * @return string
	 */
	function get_preferred_name()
	{
		if( $this->check_status( 'is_closed' ) && ( !is_admin_page() ) )
		{ // don't return closed accounts information except login name
			return $this->login;
		}

		if( !empty( $this->nickname ) )
		{	// Nickname
			return $this->get( 'nickname' );
		}
		elseif( !empty( $this->firstname ) || !empty( $this->lastname ) )
		{	// First name
			return $this->get('fullname');
		}
		else
		{	// Login
			return $this->get( 'login' );
		}
	}


	/**
	 * Get user's login with gender color
	 *
	 * @param array Params
	 * @return string User's preferred name with gender color if this available
	 */
	function get_colored_login( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'mask'         => '$login$', // $avatar$ $login$
				'login_format' => 'htmlbody',
				'avatar_size'  => 'crop-top-15x15',
				'login_text'   => 'login', // name | login
				'use_style'    => false, // true - to use attr "style", e.g. on email templates
			), $params );

		$avatar = '';
		$login = '';
		$class = '';

		if( strpos( $params['mask'], '$login$' ) !== false )
		{ // Display login or preferred name
			if( $params['login_text'] == 'name' )
			{ // Use nickname or fullname
				$login = $this->get_username( $params['login_format'] );
			}
			else
			{ // Use a login
				$login = $this->dget( 'login', $params['login_format'] );
			}
			// Add class "login" to detect logins by js plugins
			$class = ( $login == $this->login ? 'login ' : '' );
		}

		if( strpos( $params['mask'], '$avatar$' ) !== false )
		{ // Display avatar
			$avatar = $this->get_avatar_imgtag( $params['avatar_size'], '' );
		}

		$mask = array( '$login$', '$avatar$' );
		$data = array( $login, $avatar );

		$gender_class = $this->get_gender_class();
		$attr_style = '';
		if( $params['use_style'] )
		{ // Use "style"
			$attr_style = emailskin_style( '.user+.'.str_replace( ' ', '.', $gender_class ) );
		}

		return '<span class="'.trim( $class.' '.$gender_class ).'"'.$attr_style.'>'.str_replace( $mask, $data, $params['mask'] ).'</span>';
	}


	/**
	 * Get User identity link, which is a composite of user avatar and login, both point to the specific user profile tab.
	 *
	 * @return string User avatar and login if the identity link is not available, the identity link otherwise.
	 */
	function get_identity_link( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'link_text'      => 'avatar_name', // avatar_name | avatar_login | only_avatar | name | login | nickname | firstname | lastname | fullname | preferredname
				'thumb_size'     => 'crop-top-15x15',
				'thumb_class'    => 'avatar_before_login',
				'thumb_zoomable' => false,
				'login_mask'     => '', // example: 'text $login$ text'
				'display_bubbletip' => true,
				'nowrap'         => true,
				'user_tab'       => 'profile',
				'use_style'      => false, // true - to use attr "style" instead of "class", e.g. on email templates
				'blog_ID'        => NULL,
			), $params );

		$identity_url = get_user_identity_url( $this->ID, $params['user_tab'], $params['blog_ID'] );

		$attr_bubbletip = '';
		if( $params['display_bubbletip'] )
		{	// Set attribute to initialize a bubbletip
			$attr_bubbletip = ' rel="bubbletip_user_'.$this->ID.'"';
		}

		$avatar_tag = '';
		if( strpos( $params['link_text'], 'avatar' ) !== false )
		{ // Avatar must be displayed in this link
			$avatar_tag = $this->get_avatar_imgtag( $params['thumb_size'], $params['thumb_class'], '', $params['thumb_zoomable'] );
			if( $params['thumb_zoomable'] )
			{ // User avatar is zoomable
				// Add tag param to init bubbletip
				$avatar_tag = str_replace( '<img ', '<img rel="bubbletip_user_'.$this->ID.'"', $avatar_tag );
				return $avatar_tag; // We should exit here, to avoid the double adding of tag <a>
			}
		}

		$link_login = '';
		$class = empty( $params['link_class'] ) ? '' : $params['link_class'];
		if( $params['link_text'] != 'only_avatar' )
		{ // Display a login, nickname, firstname, lastname, fullname or preferredname
			switch( $params['link_text'] )
			{
				case 'login':
				case 'avatar_login':
					$link_login = $this->login;
					break;
				case 'nickname':
					$link_login = $this->nickname;
					break;
				case 'firstname':
					$link_login = $this->firstname;
					break;
				case 'lastname':
					$link_login = $this->lastname;
					break;
				case 'fullname':
					$link_login = $this->firstname.' '.$this->lastname;
					break;
				case 'preferredname':
					$link_login = $this->get_preferred_name();
					break;
				// default: 'avatar_name' | 'avatar' | 'name'
			}
			$link_login = trim( $link_login );
			if( empty( $link_login ) )
			{ // Use a login or preferred name by default
				$link_login = $this->get_username();
			}
			// Add class "login" to detect logins by js plugins
			$class .= ( $link_login == $this->login ? ' login' : '' );
			if( $params['login_mask'] != '' )
			{ // Apply login mask
				$link_login = str_replace( '$login$', $link_login, $params['login_mask'] );
			}
		}

		$gender_class = $this->get_gender_class();
		$attr_style = '';
		if( $params['use_style'] )
		{ // Use "style"
			$attr_style = emailskin_style( '.user+.'.str_replace( ' ', '.', $gender_class ) );
		}
		$attr_style = ' class="'.trim( $class.' '.$gender_class.( $params['nowrap'] ? ' nowrap' : '' ) ).'"'.$attr_style;

		if( empty( $identity_url ) )
		{
			return '<span'.$attr_style.$attr_bubbletip.'>'.$avatar_tag.$link_login.'</span>';
		}

		$link_title = T_( 'Show the user profile' );
		return '<a href="'.$identity_url.'" title="'.$link_title.'"'.$attr_style.$attr_bubbletip.'>'.$avatar_tag.$link_login.'</a>';
	}


	/**
	 * Get Regional object
	 *
	 * @param string Object name (Country, Region, Subregion, City)
	 * @param string ID name
	 * @return object
	 */
	function & get_Regional( $Object, $ID )
	{
		if( $this->check_status( 'is_closed' ) && ( !is_admin_page() ) )
		{ // don't return closed accounts regional information to front office
			return false;
		}

		if( is_null($this->$Object) && !empty($this->$ID ) )
		{
			$Cache = & call_user_func( 'get_'.$Object.'Cache' );
			$this->$Object = $Cache->get_by_ID( $this->$ID, false );
		}

		return $this->$Object;
	}


	/**
	 * Get Regional name
	 *
	 * @param string Object name (Country, Region, Subregion, City)
	 * @param string ID name
	 * @return string Name of Country, Region, Subregion or City
	 */
	function get_regional_name( $Object, $ID )
	{
		if( $this->get_Regional( $Object, $ID ) )
		{	// We have an regional object:
			return $this->$Object->name;
		}

		return '';
	}


	/**
	 * Get Country object
	 */
	function & get_Country()
	{
		return $this->get_Regional( 'Country', 'ctry_ID' );
	}


	/**
	 * Get country name
	 */
	function get_country_name()
	{
		if( empty( $this->ctry_ID ) )
		{
			return;
		}

		load_class( 'regional/model/_country.class.php', 'Country' );
		return $this->get_regional_name( 'Country', 'ctry_ID' );
	}

	/**
	 * Get region name
	 */
	function get_region_name()
	{
		if( empty( $this->rgn_ID ) )
		{
			return;
		}

		load_class( 'regional/model/_region.class.php', 'Region' );
		return $this->get_regional_name( 'Region', 'rgn_ID' );
	}

	/**
	 * Get subregion name
	 */
	function get_subregion_name()
	{
		if( empty( $this->subrg_ID ) )
		{
			return;
		}

		load_class( 'regional/model/_subregion.class.php', 'Subregion' );
		return $this->get_regional_name( 'Subregion', 'subrg_ID' );
	}

	/**
	 * Get city name
	 *
	 * @param boolean TRUE - show postcode
	 * @return string city name
	 */
	function get_city_name( $show_postcode = true )
	{
		if( empty( $this->city_ID ) )
		{
			return;
		}

		load_class( 'regional/model/_city.class.php', 'City' );
		$city = $this->get_regional_name( 'City', 'city_ID' );
		if( $this->City && $show_postcode )
		{	// Get postcode
			$city .= ' ('.$this->City->postcode.')';
		}
		return $city;
	}


	/**
	 * Get the number of blogs owned by this user
	 *
	 * @return integer
	 */
	function get_num_blogs()
	{
		global $DB;

		return $DB->get_var( 'SELECT count(*)
								FROM T_blogs
								WHERE blog_owner_user_ID = '.$this->ID );
	}


	/**
	 * Get the number of posts for the user.
	 *
	 * @param string Posts status
	 * @return integer
	 */
	function get_num_posts( $status = '' )
	{
		global $DB;
		global $collections_Module;

		if( isset( $collections_Module ) && is_null( $this->_num_posts ) )
		{
			$SQL = new SQL( 'Get the number of posts for the user' );
			$SQL->SELECT( 'post_status, COUNT(*)' );
			$SQL->FROM( 'T_items__item' );
			$SQL->WHERE( 'post_creator_user_ID = '.$this->ID );
			$SQL->GROUP_BY( 'post_status' );
			$this->_num_posts = $DB->get_assoc( $SQL->get() );

			// Calc number of posts with all statuses
			$total_num_posts = 0;
			foreach( $this->_num_posts as $status_num_posts )
			{
				$total_num_posts += $status_num_posts;
			}
			$this->_num_posts[''] = $total_num_posts;
		}

		return !empty( $this->_num_posts[ $status ] ) ? $this->_num_posts[ $status ] : 0;
	}


	/**
	 * Get the number of comments for the user.
	 *
	 * @param string Comments status
	 * @param boolean Count already recycled comments
	 * @return integer
	 */
	function get_num_comments( $status = '', $count_recycled = false )
	{
		global $DB;
		global $collections_Module;

		if( isset( $collections_Module ) && is_null( $this->_num_comments ) )
		{
			$SQL = new SQL( 'Get the number of comments for the user' );
			$SQL->SELECT( 'comment_status, COUNT(*)' );
			$SQL->FROM( 'T_comments' );
			$SQL->WHERE( 'comment_author_user_ID = '.$this->ID );
			$SQL->WHERE_and( 'comment_type IN ( "comment", "trackback", "pingback" )' );
			$SQL->GROUP_BY( 'comment_status' );
			$this->_num_comments = $DB->get_assoc( $SQL->get() );

			// Calc number of comments with all statuses
			$total_num_comments = 0;
			foreach( $this->_num_comments as $curr_status => $status_num_comments )
			{
				if( $count_recycled || ( $curr_status != 'trash' ) )
				{
					$total_num_comments += $status_num_comments;
				}
			}
			$this->_num_comments[''] = $total_num_comments;
		}

		return !empty( $this->_num_comments[ $status ] ) ? $this->_num_comments[ $status ] : 0;
	}


	/**
	 * Get the number of files for the user.
	 *
	 * @param string File type: 'image', 'audio', 'other'
	 * @return integer
	 */
	function get_num_files( $type = NULL )
	{
		global $DB;

		if( is_null( $this->_num_files ) )
		{
			$links_SQL = new SQL();
			$links_SQL->SELECT( 'file_type, COUNT( file_ID ) AS cnt' );
			$links_SQL->FROM( 'T_links' );
			$links_SQL->FROM_add( 'INNER JOIN T_files ON file_ID = link_file_ID' );
			$links_SQL->WHERE( 'link_creator_user_ID = '.$this->ID );
			$links_SQL->GROUP_BY( 'file_type' );
			$this->_num_files = $DB->get_assoc( $links_SQL->get() );
		}

		return ! empty( $this->_num_files[ $type ] ) ? $this->_num_files[ $type ] : 0;
	}


	/**
	 * Get the number of user sessions
	 *
	 * @param boolean set true to return the number of sessions as a link to the user sessions list
	 * @return integer|string number of sessions or link to user sessions where the link text is the number of sessions
	 */
	function get_num_sessions( $link_sessions = false )
	{
		global $DB;

		$num_sessions = $DB->get_var( 'SELECT count( sess_ID )
											FROM T_sessions
											WHERE sess_user_ID = '.$this->ID );

		if( $link_sessions && ( $num_sessions > 0 ) )
		{
			return $num_sessions.' - <a href="?ctrl=user&amp;user_ID='.$this->ID.'&amp;user_tab=sessions" class="'.button_class().' middle" title="'.format_to_output( T_('Go to user activity'), 'htmlattr' ).'">'.get_icon( 'magnifier', 'imgtag', array( 'title' => T_('Go to user activity') ) ).'</a>';
		}

		return $num_sessions;
	}


	/**
	 * Get the number of user messages
	 *
	 * @param string Type ( sent | received )
	 * @return integer the number of requested type of messages
	 */
	function get_num_messages( $type = 'sent' )
	{
		global $DB;

		if( $type == 'received' )
		{	// Get a count of messages received
			$SQL = new SQL();
			$SQL->SELECT( 'COUNT( msg_ID )' );
			$SQL->FROM( 'T_messaging__threadstatus' );
			$SQL->FROM_add( 'LEFT JOIN T_messaging__message ON tsta_thread_ID = msg_thread_ID' );
			$SQL->WHERE( 'tsta_user_ID = '.$DB->quote( $this->ID ) );
			$SQL->WHERE_and( 'msg_author_user_ID != '.$DB->quote( $this->ID ) );
		}
		else
		{	// Get a count of messages sent
			$SQL = new SQL();
			$SQL->SELECT( 'COUNT( msg_ID )' );
			$SQL->FROM( 'T_messaging__message' );
			$SQL->WHERE( 'msg_author_user_ID = '.$DB->quote( $this->ID ) );
		}

		return $DB->get_var( $SQL->get() );
	}


	/**
	 * Get the number of other users posts which were edited by this user
	 *
	 * @return integer the number of edited posts
	 */
	function get_num_edited_posts()
	{
		global $DB;
		global $collections_Module;

		return $DB->get_var( 'SELECT COUNT( DISTINCT( post_ID ) )
									FROM T_items__item
									INNER JOIN T_items__version ON post_ID = iver_itm_ID
									WHERE post_creator_user_ID <> '.$this->ID.' AND
										( iver_edit_user_ID = '.$this->ID.' OR post_lastedit_user_ID = '.$this->ID.' )' );
	}


	/**
	 * Get the path to the media directory. If it does not exist, it will be created.
	 *
	 * If we're {@link is_admin_page() on an admin page}, it adds status messages.
	 * @todo These status messages should rather go to a "syslog" and not be displayed to a normal user
	 * @todo dh> refactor this into e.g. create_media_dir() and use it for Blog::get_media_dir, too.
	 *
	 * @param boolean Create the directory, if it does not exist yet?
	 * @return mixed the path as string on success, false if the dir could not be created
	 */
	function get_media_dir( $create = true )
	{
		global $media_path, $Messages, $Settings, $Debuglog;

		if( ! $Settings->get( 'fm_enable_roots_user' ) )
		{	// User directories are disabled:
			$Debuglog->add( 'Attempt to access user media dir, but this feature is disabled', 'files' );
			return false;
		}

		$userdir = get_canonical_path( $media_path.$this->get_media_subpath() );

		if( $create && ! is_dir( $userdir ) )
		{
			if( ! is_writable( dirname($userdir) ) )
			{ // add error
				if( is_admin_page() )
				{
					$Messages->add( sprintf( T_("The user's media directory &laquo;%s&raquo; could not be created, because the parent directory is not writable or does not exist."), rel_path_to_base($userdir) )
							.get_manual_link('directory_creation_error'), 'error' );
				}
				return false;
			}
			elseif( ! evo_mkdir( $userdir ) )
			{ // add error
				if( is_admin_page() )
				{
					$Messages->add( sprintf( T_("The user's media directory &laquo;%s&raquo; could not be created."), rel_path_to_base($userdir) )
							.get_manual_link('directory_creation_error'), 'error' );
				}
				return false;
			}
			else
			{ // add note:
				if( is_admin_page() )
				{
					$Messages->add( sprintf( T_("The user's directory &laquo;%s&raquo; has been created with permissions %s."), rel_path_to_base($userdir), substr( sprintf('%o', fileperms($userdir)), -3 ) ), 'success' );
				}
			}
		}
		return $userdir;
	}


	/**
	 * Get the URL to the media folder
	 *
	 * @return string the URL
	 */
	function get_media_url()
	{
		global $media_url, $Settings, $Debuglog;
		global $Blog;

		if( ! $Settings->get( 'fm_enable_roots_user' ) )
		{	// User directories are disabled:
			$Debuglog->add( 'Attempt to access user media URL, but this feature is disabled', 'files' );
			return false;
		}

		if( isset($Blog) )
		{	// We are currently looking at a blog. We are going to consider (for now) that we want the users and their files
			// to appear as being part of that blog.
			return $Blog->get_local_media_url().$this->get_media_subpath();
		}

		// System media url:
		return $media_url.$this->get_media_subpath();
	}


	/**
	 * Get user media directory subpath, e.g. users/{login}/ or users/usr_{user ID}/
	 */
	function get_media_subpath()
	{
		if( is_valid_login( $this->login, true ) )
		{	// Valid ASCII login, use it as is
			return 'users/'.$this->login.'/';
		}
		else
		{	// Non-ASCII login
			return 'users/usr_'.$this->ID.'/';
		}
	}


	/**
	 * Get message form url
	 */
	function get_msgform_url( $formurl, $redirect_to = NULL )
	{
		global $ReqURI;

		if( ! $this->get_msgform_possibility() )
		{
			return NULL;
		}

		if( $redirect_to == NULL )
		{
			$redirect_to = $ReqURI;
		}

		return url_add_param( $formurl, 'recipient_id='.$this->ID.'&amp;redirect_to='.rawurlencode( $redirect_to ) );
	}


	/**
	 * Get user page url
	 *
	 * @param integer|NULL Blog ID or NULL to use current blog
	 * @param boolean TRUE to force use URL even when current user in not logged in
	 * @return string
	 */
	function get_userpage_url( $blog_ID = NULL, $force_url = false )
	{
		global $Settings;

		if( ! empty( $blog_ID ) )
		{ // Use blog from param by ID
			$BlogCache = & get_BlogCache();
			$current_Blog = & $BlogCache->get_by_ID( $blog_ID, false, false );
		}

		if( empty( $current_Blog ) )
		{ // Use current blog
			global $Blog;
			$current_Blog = & $Blog;
		}

		if( empty( $current_Blog ) || empty( $Settings ) )
		{ // Wrong request
			return NULL;
		}

		if( $force_url || is_logged_in() )
		{ // If current User is logged in
			if( $Settings->get( 'user_url_loggedin' ) == 'url' && $this->get_field_url( true ) != '' )
			{ // Use website url if it is defined and setting enables this
				return $this->get_field_url( true );
			}
			else
			{ // Use an user page if url is not defined
				return url_add_param( $current_Blog->get( 'userurl' ), 'user_ID='.$this->ID );
			}
		}
		else
		{ // For anonymous users
			$this->get_Group();
			if( $this->Group->level >= $Settings->get( 'allow_anonymous_user_level_min' ) &&
			    $this->Group->level <= $Settings->get( 'allow_anonymous_user_level_max' ) )
			{ // Check if anonymous users have an access to view this user
				if( $Settings->get( 'user_url_anonymous' ) == 'url' && $this->get_field_url( true ) != '' )
				{ // Use website url if it is defined and setting enables this
					return $this->get_field_url( true );
				}
				elseif( $Settings->get( 'allow_anonymous_user_profiles' ) )
				{ // Use an user page if url is not defined and this is enabled by setting for anonymous users
					return url_add_param( $current_Blog->get( 'userurl' ), 'user_ID='.$this->ID );
				}
			}
		}

		// return NULL if user page url is not allowed
		return NULL;
	}


	/**
	 * Get url from defined userfields
	 *
	 * @param boolean TRUE if we want get a value from url fields of the table T_users__fields, FALSE - get from $this->url
	 * @return string Url
	 */
	function get_field_url( $from_extra_fields = false )
	{
		global $DB;

		if( $from_extra_fields )
		{	// Get value from DB or from cache
			if( isset( $this->field_url ) )
			{	// Get url from variable already defined in first calling of this method
				return $this->field_url;
			}
			else
			{	// Get value from DB
				$this->field_url = (string)$DB->get_var( '
					SELECT uf_varchar
						FROM T_users__fields
							LEFT JOIN T_users__fielddefs ON uf_ufdf_ID = ufdf_ID
							LEFT JOIN T_users__fieldgroups ON ufdf_ufgp_ID = ufgp_ID
					WHERE uf_user_ID = '.$this->ID.'
						AND ufdf_type = "url"
					ORDER BY ufgp_order, ufdf_order, uf_ID
					LIMIT 1' );
				return $this->field_url;
			}
		}
		else
		{	// Get value from $this->url (T_users)
			return $this->url;
		}
	}


	/**
	 * Get link from defined userfields
	 *
	 * @param array Template params
	 * @return string Link
	 */
	function get_field_link( $params = array() )
	{
		$params = array_merge( array(
				'target' => '_blank',
				'rel'    => 'nofollow'
			), $params );

		$link = '<a href="'.$this->get_field_url().'"'.get_field_attribs_as_string( $params, false ).'>'.$this->get_field_url().'</a>';

		return $link;
	}


	/**
	 * Set param value
	 *
	 * @param string parameter name
	 * @param mixed parameter value
	 * @param boolean true to set to NULL if empty value
	 * @return boolean true, if a value has been set; false if it has not changed
	 */
	function set( $parname, $parvalue, $make_null = false )
	{
		if( ( ! isset( $this->significant_changed_values[ $parname ] ) ) && ( ( $old_value = $this->get( $parname ) ) != $parvalue )
			&& in_array( $parname, array( 'login', 'grp_ID', 'nickname', 'firstname', 'lastname', 'gender', 'ctry_ID', 'rgn_ID', 'subrg_ID', 'city_ID' ) ) )
		{ // Save previous value of significant changes for later use in send_account_changed_notifications()
			$this->significant_changed_values[ $parname ] = $old_value;
		}

		switch( $parname )
		{
			case 'level':
				return $this->set_param( $parname, 'number', $parvalue, $make_null );

			case 'ctry_ID':
			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}


	/**
	 * Set the encoded password and a new password salt from a raw password
	 *
	 * @param string the raw password
	 */
	function set_password( $raw_password )
	{
		// Generate new salt to save a password
		$new_pass_salt = generate_random_key( 8 );

		$this->set( 'pass', md5( $new_pass_salt.$raw_password, true ) );
		$this->set( 'salt', $new_pass_salt );
	}


	/**
	 * Set date created.
	 *
	 * @param integer seconds since Unix Epoch.
	 */
	function set_datecreated( $datecreated, $isYMDhour = false )
	{
		if( !$isYMDhour )
		{
			$datecreated = date('Y-m-d H:i:s', $datecreated );
		}
		// Set value:
		$this->datecreated = $datecreated;
		// Remmeber change for later db update:
		$this->dbchange( 'user_created_datetime', 'string', 'datecreated' );
	}


	/**
	 * Set email address of the user.
	 *
	 * If the email address has changed and we're configured to invalidate the user in this case,
	 * the user's account gets not active 'emaichanged' status here.
	 *
	 * @param string email address to set for the User
	 * @param boolean Set TRUE if changing of account status is required
	 * @return boolean true, if set; false if not changed
	 */
	function set_email( $email, $change_status = true )
	{
		global $Settings;

		$r = parent::set_param( 'email', 'string', utf8_strtolower( $email ) );

		if( $change_status )
		{ // Change user status to 'emailchanged' (if email has changed and Settings are available, which they are not during install):
			if( $r && ( $this->ID != 0 ) && isset($Settings) && $Settings->get('newusers_revalidate_emailchg') && ( $this->check_status( 'is_validated' ) ) )
			{ // Deactivate the account, because the changed email has not been verified yet, but the user account is active:
				$this->set( 'status', 'emailchanged' );
			}
		}

		if( preg_match( '#@(.+)#i', $email, $ematch ) )
		{	// Set email domain ID
			global $DB;

			$email_domain = $ematch[1];

			$SQL = new SQL();
			$SQL->SELECT( 'dom_ID' );
			$SQL->FROM( 'T_basedomains' );
			$SQL->WHERE( 'dom_type = \'email\'' );
			$SQL->WHERE_and( 'dom_name = '.$DB->quote( $email_domain ) );

			$dom_ID = $DB->get_var( $SQL->get() );
			if( !$dom_ID )
			{	// The email domains doesn't exist yet, Insert new record
				$DB->query( 'INSERT INTO T_basedomains ( dom_type, dom_name )
					VALUES ( \'email\', '.$DB->quote( $email_domain ).' )' );
				$dom_ID = $DB->insert_id;
			}

			$this->set( 'email_dom_ID', (int) $dom_ID );
		}

		return $r;
	}


	/**
	 * Set new Group.
	 *
	 * @param Group the Group object to put the user into
	 * @return boolean true if set, false if not changed
	 */
	function set_Group( & $Group )
	{
		if( $this->grp_ID !== $Group->ID )
		{
			$this->Group = & $Group;
			$this->set( 'grp_ID', $Group->ID );
			return true;
		}

		return false;
	}

	/**
	 * @deprecated by {@link User::set_Group()} since 1.9
	 */
	function setGroup( & $Group )
	{
		global $Debuglog;
		$Debuglog->add( 'Call to deprecated method User::setGroup(), use set_Group() instead.', 'deprecated' );
		return $this->set_Group( $Group );
	}


	/**
	 * Get the {@link Group} of the user.
	 *
	 * @return Group (by reference)
	 */
	function & get_Group()
	{
		if( ! isset($this->Group) )
		{
			$GroupCache = & get_GroupCache();
			$this->Group = & $GroupCache->get_by_ID($this->grp_ID);
		}
		return $this->Group;
	}


  /**
	 * Check password
	 *
	 * @param string password
	 * @param boolean Is the password parameter already MD5()'ed?
	 * @return boolean
	 */
	function check_password( $pass, $pass_is_md5 = false )
	{
		if( !$pass_is_md5 )
		{
			$pass = md5( $this->salt.$pass, true );
		}

		return ( $pass == $this->pass );
	}


	/**
	 * Check permission for this user
	 *
	 * @param string Permission name, can be one of:
	 *                - 'edit_timestamp'
	 *                - 'cats_post_statuses', see {@link User::check_perm_catsusers()}
	 *                - either group permission names, see {@link Group::check_perm()}
	 *                - either blogusers permission names, see {@link User::check_perm_blogusers()}
	 * @param string Permission level
	 * @param boolean Execution will halt if this is !0 and permission is denied
	 * @param mixed Permission target (blog ID, array of cat IDs, Item, Comment...)
	 * @return boolean 0 if permission denied
	 */
	function check_perm( $permname, $permlevel = 'any', $assert = false, $perm_target = NULL )
	{
		global $Debuglog, $Settings;

		if( is_object($perm_target) && isset($perm_target->ID) )
		{
			$perm_target_ID = $perm_target->ID;
		}
		elseif( !is_array($perm_target) )
		{
			$perm_target_ID = $perm_target;
		}

		if( isset($perm_target_ID)	// if it makes sense to check the cache
			&& isset($this->cache_perms[$permname][$permlevel][$perm_target_ID]) )
		{ // Permission in available in Cache:
			$Debuglog->add( "Got perm [$permname][$permlevel][$perm_target_ID] from cache", 'perms' );
			return $this->cache_perms[$permname][$permlevel][$perm_target_ID];
		}

		$pluggable_perms = array( 'admin', 'spamblacklist', 'slugs', 'templates', 'options', 'files', 'users' );
		if( in_array( $permname, $pluggable_perms ) )
		{
			$permname = 'perm_'.$permname;
		}
		//$Debuglog->add( "Querying perm [$permname][$permlevel]".( isset( $perm_target_ID ) ? '['.$perm_target_ID.']' : '' ).']', 'perms' );
		//pre_dump( 'Perm target: '.var_export( $perm_target, true ) );

		$perm = false;

		switch( $permname )
		{ // What permission do we want to check?
			case 'cats_post_statuses':
			case 'cats_post!published':
			case 'cats_post!community':
			case 'cats_post!protected':
			case 'cats_post!private':
			case 'cats_post!review':
			case 'cats_post!draft':
			case 'cats_post!deprecated':
			case 'cats_post!redirected':
			case 'cats_item_type_standard':
			case 'cats_item_type_restricted':
			case 'cats_item_type_admin':
				// Category permissions...
				if( ! is_array( $perm_target ) )
				{	// We need an array here:
					$perm_target = array( $perm_target );
				}

				// First we need to create an array of blogs, not cats
				$perm_target_blogs = array();
				foreach( $perm_target as $loop_cat_ID )
				{
					$loop_cat_blog_ID = get_catblog( $loop_cat_ID );
					// echo "cat $loop_cat_ID -> blog $loop_cat_blog_ID <br />";
					if( ! in_array( $loop_cat_blog_ID, $perm_target_blogs ) )
					{ // not already in list: add it:
						$perm_target_blogs[] = $loop_cat_blog_ID;
					}
				}

				$perm = true; // Permission granted if no blog denies it below
				$blogperm = 'blog_'.substr( $permname, 5 );
				// Now we'll check permissions for each blog:
				foreach( $perm_target_blogs as $loop_blog_ID )
				{
					if( ! $this->check_perm( $blogperm, $permlevel, false, $loop_blog_ID ) )
					{ // If at least one blog denies the permission:
						$perm = false;
						break;
					}
				}
				break;

			case 'recycle_owncmts':
				// Check permission to edit comments for own items
				$Comment = & $perm_target;
				$Item = & $Comment->get_Item();
				$blog_ID = $Item->get_blog_ID();
				if( $Item->creator_user_ID == $this->ID )
				{ // Current user is owner of this item
					if( $Item->is_locked() && !$this->check_perm( 'blog_cats', 'edit', false, $blog_ID ) )
					{ // Comment item is locked and current user is not allowed to edit locked items comment
						break;
					}

					$comment_author_User = & $Comment->get_author_User();
					if( ( empty( $comment_author_User ) || ( $comment_author_User->level <= $this->level ) )
						&& in_array( $Comment->status, array( 'published', 'community', 'protected' ) ) )
					{ // Comment author is anonymous or his level is lower than current User level, and the Comment was published with some of the above statuses
						// Check blog user perms to see if user may recycle his own posts comments
						$perm = $this->check_perm_blogusers( 'blog_recycle_owncmts', $permlevel, $blog_ID );
						if( ! $perm )
						{ // Check groups for permissions to this specific blog:
							$perm = $this->Group->check_perm_bloggroups( 'blog_recycle_owncmts', $permlevel, $blog_ID );
						}
					}
				}
				break;

			case 'blog_ismember':
			case 'blog_can_be_assignee':
			case 'blog_post_statuses':
			case 'blog_post!published':
			case 'blog_post!community':
			case 'blog_post!protected':
			case 'blog_post!private':
			case 'blog_post!review':
			case 'blog_post!draft':
			case 'blog_post!deprecated':
			case 'blog_post!redirected':
			case 'blog_del_post':
			case 'blog_edit':
			case 'blog_edit_cmt':
			case 'blog_comments':
			case 'blog_comment_statuses':
			case 'blog_del_cmts':
			case 'blog_vote_spam_comments':
			case 'blog_comment!published':
			case 'blog_comment!community':
			case 'blog_comment!protected':
			case 'blog_comment!private':
			case 'blog_comment!deprecated':
			case 'blog_comment!review':
			case 'blog_comment!draft':
			case 'blog_properties':
			case 'blog_cats':
			case 'blog_item_type_standard':
			case 'blog_item_type_restricted':
			case 'blog_item_type_admin':
			case 'blog_edit_ts':
				// Blog permission to edit its properties...
				if( $this->check_perm_blogowner( $perm_target_ID ) )
				{	// Owner can do *almost* anything:
					$perm = true;
					break;
				}
				/* continue */
			case 'blog_admin': // This is what the owner does not have access to!

				// Group may grant VIEW access, FULL access:
				$this->get_Group();
				$group_permlevel = ( $permlevel == 'view' ||  $permlevel == 'any' ) ? $permlevel : 'editall';
				if( $this->Group->check_perm( 'blogs', $group_permlevel ) )
				{ // If group grants a global permission:
					$perm = true;
					break;
				}

				if( $perm_target_ID > 0 )
				{ // Check user perm for this blog:
					$perm = $this->check_perm_blogusers( $permname, $permlevel, $perm_target_ID );
					if( ! $perm )
					{ // Check groups for permissions to this specific blog:
						$perm = $this->Group->check_perm_bloggroups( $permname, $permlevel, $perm_target_ID );
					}
				}

				break;

			case 'comment!CURSTATUS':
				/**
				 * @var Comment
				 */
				$Comment = & $perm_target;
				// Change the permname to one of the following:
				$permname = 'comment!'.$Comment->status;
			case 'comment!published':
			case 'comment!community':
			case 'comment!protected':
			case 'comment!private':
			case 'comment!review':
			case 'comment!draft':
			case 'comment!deprecated':
			case 'comment!trash':
				/**
				 * @var Comment
				 */
				$Comment = & $perm_target;
				$Item = & $Comment->get_Item();
				$blog_ID = $Item->get_blog_ID();
				$check_status = substr( $permname, 8 );

				if( $Comment->is_meta() && in_array( $permlevel, array( 'edit', 'moderate', 'delete' ) ) )
				{ // Check the permissions for meta comment with special function
					$perm = $this->check_perm( 'meta_comment', $permlevel, false, $Comment );
					break;
				}

				if( ( $permlevel != 'view' ) &&  $Item->is_locked() && !$this->check_perm( 'blog_cats', 'edit', false, $blog_ID ) )
				{ // Comment item is locked and current user is not allowed to edit/moderate locked items comment
					break;
				}

				if( $this->check_perm_blog_global( $blog_ID, $permlevel ) )
				{ // User has global permission on this blog:
					$perm = true;
					break;
				}

				if( $Comment->status == 'trash' )
				{ // only global group 'editall' perm can give rights to 'trash' status, but this is not the case
					break;
				}

				if( $permlevel == 'delete' )
				{ // permlevel is delete so we have to check the 'blog_del_cmts' permission
					$perm = $this->check_perm( 'blog_del_cmts', 'edit', false, $blog_ID )
							|| $this->check_perm( 'recycle_owncmts', $permlevel, false, $Comment );
					break;
				}

				// Check comment current status permissions at the blog level:
				$blog_permname = 'blog_comment!'.$Comment->status;
				$perm = $perm || $this->check_perm_blogusers( $blog_permname, $permlevel, $blog_ID, $Comment );
				if( ! $perm )
				{ // Check groups for permissions to this specific blog:
					$perm = $this->Group->check_perm_bloggroups( $blog_permname, $permlevel, $blog_ID, $Comment, $this );
				}
				if( $perm && ( $Comment->status != $check_status ) )
				{ // also check the requested status permissions at the blog level
					$blog_permname = 'blog_comment!'.$check_status;
					$perm = $this->check_perm_blogusers( $blog_permname, 'create', $blog_ID );
					if( ! $perm )
					{ // Check groups for permissions to this specific blog:
						$perm = $this->Group->check_perm_bloggroups( $blog_permname, 'create', $blog_ID );
					}
				}
				break;

			case 'meta_comment':
				// Check permission for meta comment:

				if( $permlevel == 'view' || $permlevel == 'add' )
				{ // Set Item from target object
					$Item = & $perm_target;
				}
				elseif( $permlevel == 'edit' || $permlevel == 'moderate' || $permlevel == 'delete' )
				{ // Set Comment from target object
					$Comment = & $perm_target;
					if( empty( $Comment ) || ! $Comment->is_meta() )
					{ // Comment must be defined and meta to check these permissions
						$perm = false;
						break;
					}
					$Item = & $Comment->get_Item();
				}
				else
				{ // Invalid permission level
					$perm = false;
					break;
				}

				if( empty( $Item ) )
				{ // Item must be defined to check these permissions
					$perm = false;
					break;
				}

				switch( $permlevel )
				{
					case 'view':
					case 'add':
						// Check perms to View/Add meta comments:
						$perm = // If User can edit or delete this Item
								$this->check_perm( 'item_post!CURSTATUS', 'edit', false, $Item )
								// OR If User can delete any Item of the Blog
								|| $this->check_perm( 'blog_del_post', '', false, $Item->get_blog_ID() );
						break;

					case 'edit':
						// Check perms to Edit meta comment
						$perm = // User can edit only own meta comment
								$Comment->author_user_ID == $this->ID
								// AND User must has a permission to view meta comments
								&& $this->check_perm( 'meta_comment', 'view', false, $Item );
						break;

					case 'moderate':
						// Moderation is not available for meta comment
						$perm = false;
						break;

					case 'delete':
						// Check perms to Delete meta comment:
						if( $this->check_perm( 'blog_del_post', '', false, $Item->get_blog_ID() ) )
						{ // If User can delete this Item
							$perm = true;
							break;
						}
						if( $this->check_perm( 'item_post!CURSTATUS', 'edit', false, $Item ) &&
						    $Comment->author_user_ID == $this->ID )
						{ // If it is own meta comment of the User
							$perm = true;
							break;
						}
						break;
				}

				break;

			case 'item_post!CURSTATUS':
				/**
				 * @var Item
				 */
				$Item = & $perm_target;
				// Change the permname to one of the following:
				$permname = 'item_post!'.$Item->status;
			case 'item_post!published':
			case 'item_post!community':
			case 'item_post!protected':
			case 'item_post!private':
			case 'item_post!review':
			case 'item_post!draft':
			case 'item_post!deprecated':
			case 'item_post!redirected':
				// Get the Blog ID
				/**
				 * @var Item
				 */
				$Item = & $perm_target;
				$blog_ID = $Item->get_blog_ID();
				$check_status = substr( $permname, 10 );

				if( ( $permlevel != 'view' ) && $Item->is_locked() && !$this->check_perm( 'blog_cats', 'edit', false, $blog_ID ) )
				{ // Item is locked and current user is not allowed to edit locked items ( only view permission is allowed by default for locked items )
					break;
				}

				if( $this->check_perm_blog_global( $blog_ID, $permlevel ) )
				{ // User has global permission on this blog:
					$perm = true;
					break;
				}

				if( $permlevel == 'delete' )
				{ // permlevel is delete so we have to check the 'blog_del_post' permission
					$perm = $this->check_perm( 'blog_del_post', 'edit', false, $blog_ID );
					break;
				}

				// Check permissions at the blog level:
				$blog_permname = 'blog_post!'.$Item->status;
				$perm = $this->check_perm_blogusers( $blog_permname, $permlevel, $blog_ID, $Item );
				if( ! $perm )
				{ // Check groups for permissions to this specific blog:
					$perm = $this->Group->check_perm_bloggroups( $blog_permname, $permlevel, $blog_ID, $Item, $this );
				}
				if( $perm && ( $Item->status != $check_status ) )
				{ // also check the requested status permissions at the blog level
					$blog_permname = 'blog_post!'.$check_status;
					$perm = $this->check_perm_blogusers( $blog_permname, 'create', $blog_ID );
					if( ! $perm )
					{ // Check groups for permissions to this specific blog:
						$perm = $this->Group->check_perm_bloggroups( $blog_permname, 'create', $blog_ID );
					}
				}
				break;

			case 'stats':
				// Blog permission to edit its properties...
				$this->get_Group();

				// Group may grant VIEW acces, FULL access:
				if( $this->Group->check_perm( $permname, $permlevel ) )
				{ // If group grants a global permission:
					$perm = true;
					break;
				}

				if( $perm_target > 0 )
				{ // Check user perm for this blog:
					$perm = $this->check_perm_blogusers( $permname, $permlevel, $perm_target );
					if ( ! $perm )
					{ // Check groups for permissions to this specific blog:
						$perm = $this->Group->check_perm_bloggroups( $permname, $permlevel, $perm_target );
					}
				}
				break;

			case 'user':
				// Check if user can 'view'/'moderate'/'edit'/'delete' another user
				$User = & $perm_target;
				if( !$this->check_status( 'can_view_user', $User->ID ) )
				{ // User can't even view the edited User
					break;
				}

				// If user has global permission on all users then everything is allowed
				if( $this->check_perm( 'users', 'edit' ) )
				{ // Check if user can edit/delete other users
					$perm = true;
					break;
				}

				if( $permlevel == 'delete' || $permlevel == 'edit' )
				{ // These permission levels are not allowed for this user
					break;
				}

				if( ( ! $Settings->get('allow_anonymous_user_profiles') ) && ( ! $this->check_perm( 'cross_country_allow_profiles' ) ) && ( empty( $this->ctry_ID ) || ( $this->ctry_ID !== $User->ctry_ID ) ) )
				{ // Users can view/browse other users only from the same country, but this user country is empty or not the same as the target user country
					// this User has no permission to even view the target user
					$User->get_Group();
					if( $User->Group->level < $Settings->get('allow_anonymous_user_level_min') ||
					    $User->Group->level > $Settings->get('allow_anonymous_user_level_max') )
					{ // The anonymous users have no access to view this user
						break;
					}
				}

				// Note: With this implementation only admin users are allowed to view users from different countries when cross country browsing is restricted
				// check if user has permission to moderate another user
				if( ( $permlevel == 'moderate' ) && $this->check_perm( 'users', 'moderate' ) )
				{ // this user has moderator permission, check if the group level is higher then the target user group level
					$this->get_Group();
					$User->get_Group;
					$perm = ( $this->Group->level > $User->Group->level );
					break;
				}

				// Only 'view' permlevel remained, and this user is allowed to view the target user
				$perm = true;
				break;

			// asimo> edit_timestamp permission was converted to blog_edit_ts permission


			// asimo> files permission was converted to pluggable permission
			/*case 'files':
				$this->get_Group();
				$perm = $this->Group->check_perm( $permname, $permlevel );*/

				/* Notes:
				 *  - $perm_target can be:
				 *    - NULL or 0: check global group permission only
				 *    - positive: check global group permission and
				 *      (if granted) if a specific blog denies it.
* fp> This is BAD BAD BAD because it's inconsistent with the other permissions
* in b2evolution. There should NEVER be a denying. ony additional allowing.
* It's also inconsistent with most other permission systems.
* The lower file permission level for groups is now called "No Access"
* This should be renamed to "Depending on each blog's permissions"
* Whatever general permissions you have on files, blog can give you additional permissions
* but they can never take a global perm away.
* Tblue> On the permissions page it says that the blog perms will be restricted
* by any global perms, which means to me that a blog cannot grant e. g.
* the files upload perm if this perm isn't granted globally... But apparently
* it shouldn't be like that?! I understand it should be like that then:
* if( ! $perm && $perm_target && in_array( $permlevel, array( 'add', 'view', 'edit' ) )
* {
* 		// check if blog grants permission.
* }
* If this is correct, we should remove the note on the blog permissions
* pages and the group properties form.
* fp> ok, I had forgotten we had that old message, but still it doesn't say it will, it says it *may* !
* To be exact the message should be "
* Note: General group permissions may further restrict or extend any permissions defined here."
* Restriction should only happen when "NO ACCESS" is selected
* But when "Depending on each blog's permissions" is selected, THEN (and I guess ONLY then) the blog permissions should be used
* Note: This is quite messy actually. maybe it would make more sense to separate group permissions by "root type":
* i-e nto use the same permission for blog roots vs user root vs shared root vs skins root
* what do you think?
* Tblue> That sounds OK. So we would add another option to the global
* 'files' group perm setting ("Depending on each blog's permissions"), right?
* fp> yes.
* tb> Regarding separation: It could make sense. The blog-specific permissions would only
* affect blog roots (and if "Depending on each blog's permissions" is selected;
* for the other roots we would add separate (global) settings...
* fp> yes.
				 *  - Only a $permlevel of 'add', 'view' or 'edit' can be
				 *    denied by blog permissions.
				 *  - If the group grants the 'all' permission, blogs cannot
				 *    deny it.
				 */
/*
				if( $perm && $perm_target && in_array( $permlevel, array( 'add', 'view', 'edit' ) )
					&& $this->Group->get( 'perm_files' ) != 'all' )
				{	// Check specific blog perms:
					$perm = $this->check_perm_blogusers( $permname, $permlevel, $perm_target );
					if ( ! $perm )
					{ // Check groups for permissions for this specific blog:
						$perm = $this->Group->check_perm_bloggroups( $permname, $permlevel, $perm_target );
					}
				}
*/
				//break;

			default:
				// Check pluggable permissions using user permission check function
				$this->get_Group();
				$perm = Module::check_perm( $permname, $permlevel, $perm_target, 'user_func', $this->Group );
				if( $perm === true || $perm === NULL )
				{ // If user_func or user perm not exists in the corresponding module then $perm value will be NULL and we have to check the group permission.
					// If user_func exists and returns true, then we have to check group permission to make sure it does not restrict the user perm.

					// Other global permissions (see if the group can handle them).
					// Forward request to group:
					$perm = $this->Group->check_perm( $permname, $permlevel, $perm_target );
				}
		}

		if( is_object($perm_target) )
		{	// Prevent catchable E_FATAL with PHP 5.2 (because there's no __tostring for e.g. Item)
			$taget_name = get_class($perm_target).'('.$perm_target_ID.')';
		}
		elseif( is_array($perm_target) )
		{	// Convert to ID list
			$taget_name = '('.implode(',', $perm_target).')';
		}
		else
		{
			$taget_name = $perm_target;
		}

		$Debuglog->add( 'User perm '.$permname.':'.$permlevel.':'.$taget_name.' => '.($perm ? 'granted' : 'DENIED'), 'perms' );

		if( ! $perm && $assert )
		{ // We can't let this go on!
			global $app_name;
			debug_die( sprintf( /* %s is the application name, usually "b2evolution" */ T_('Group/user permission denied by %s!'), $app_name )." ($permname:$permlevel:".( is_object( $perm_target ) ? get_class( $perm_target ).'('.$perm_target_ID.')' : ( is_array( $perm_target ) ? implode( ', ', $perm_target ) : $perm_target ) ).")" );
		}

		if( isset($perm_target_ID) )
		{
			// echo "cache_perms[$permname][$permlevel][$perm_target] = $perm;";
			$this->cache_perms[$permname][$permlevel][$perm_target_ID] = $perm;
		}

		return $perm;
	}


	/**
	 * Check if the user is the owner of the designated blog (which gives him a lot of permissions)
	 *
	 * @param integer
	 * @return boolean
	 */
	function check_perm_blogowner( $blog_ID )
	{
		if( empty( $blog_ID ) )
		{
			return false;
		}

		$BlogCache = & get_BlogCache();
		/**
		 * @var Blog
		 */
		$Blog = & $BlogCache->get_by_ID( $blog_ID );

		return ( $Blog->owner_user_ID == $this->ID );
	}


	/**
	 * Check if the user is the owner of the designated item (which gives him a lot of permissions)
	 *
	 * @param integer
	 * @return boolean
	 */
	function check_perm_itemowner( $item_ID )
	{
		if( empty( $item_ID ) )
		{
			return false;
		}

		$ItemCache = & get_ItemCache();
		/**
		 * @var Item
		 */
		$Item = & $ItemCache->get_by_ID( $item_ID );

		return ( $Item->creator_user_ID == $this->ID );
	}


	/**
	 * Check if user has global perms on this blog
	 *
	 * @param integer blog ID
	 * @param string permlevel
	 * @return boolean true if user is the owner, or user group has some permission on all blogs
	 */
	function check_perm_blog_global( $blog_ID, $permlevel = 'edit' )
	{
		if( $this->check_perm_blogowner( $blog_ID ) )
		{ // user is the blog owner
			return true;
		}

		// Group may grant VIEW access, FULL access:
		$this->get_Group();
		$group_permlevel = ( $permlevel == 'view' && $permlevel == 'any' ) ? 'viewall' : 'editall';
		if( $this->Group->check_perm( 'blogs', $group_permlevel ) )
		{ // If group grants a global permission:
			return true;
		}

		return false;
	}


	/**
	 * Check permission for this user on a specified blog
	 *
	 * This is not for direct use, please call {@link User::check_perm()} instead
	 *
	 * @see User::check_perm()
	 * @param string Permission name, can be one of the following:
	 *                  - blog_ismember
	 *                  - blog_can_be_assignee
	 *                  - blog_post_statuses
	 *                  - blog_del_post
	 *                  - blog_edit_ts
	 *                  - blog_comments
	 *                  - blog_cats
	 *                  - blog_properties
	 * @param string Permission level
	 * @param integer Permission target blog ID
	 * @param Item Item that we want to edit
	 * @return boolean 0 if permission denied
	 */
	function check_perm_blogusers( $permname, $permlevel, $perm_target_blog, $perm_target = NULL )
	{
		// Check if user blog advanced perms are loaded
		if( ! isset( $this->blog_post_statuses[$perm_target_blog] ) )
		{ // Blog post statuses have not been loaded yet:
			$this->blog_post_statuses[$perm_target_blog] = array();
			if( ! load_blog_advanced_perms( $this->blog_post_statuses[$perm_target_blog], $perm_target_blog, $this->ID, 'bloguser' ) )
			{ // Could not load blog advanced user perms
				return false;
			}
		}

		// Check permission and return the result
		return check_blog_advanced_perm( $this->blog_post_statuses[$perm_target_blog], $this->ID, $permname, $permlevel, $perm_target );
	}


	/**
	 * Check if user status permit the give action
	 *
	 * @param string action
	 * @param integger target ID - can be a post ID, user ID
	 * @return boolean true if the action is permitted, false otherwise
	 */
	function check_status( $action, $target = NULL )
	{
		global $Settings, $Blog;

		switch( $action )
		{
			case 'can_view_user':
				if( $Settings->get( 'allow_anonymous_user_profiles' ) || ( !empty( $target ) && ( $target == $this->ID ) ) )
				{ // even anonymous users can see users profile, or user wants to check his own profile
					return true;
				}
				return ( ( $this->status == 'activated' ) || ( $this->status == 'autoactivated' ) );
			case 'can_view_users':
				if( $Settings->get( 'allow_anonymous_user_list' ) )
				{ // even anonymous users can see users list
					return true;
				}
				return ( ( $this->status == 'activated' ) || ( $this->status == 'autoactivated' ) );
			case 'can_view_comments':
			case 'can_view_contacts':
			case 'can_view_messages':
			case 'can_view_threads':
			case 'is_validated':
			case 'can_access_admin':
			case 'can_edit_post':
			case 'can_edit_comment':
			case 'can_edit_contacts':
			case 'can_report_user':
				return ( ( $this->status == 'activated' ) || ( $this->status == 'autoactivated' ) );
			case 'can_be_validated':
				return ( ( $this->status == 'new' ) || ( $this->status == 'emailchanged' ) || ( $this->status == 'deactivated' ) || ( $this->status == 'failedactivation' ) );
			case 'can_view_msgform':
			case 'can_receive_any_message': // can this user receive emails or private messages
			case 'can_receive_pm':
			case 'can_display_avatar': // can display user's avatar for not admin users
			case 'can_display_link': // can display user's profile link for not admin users
				return ( $this->status != 'closed' );
			case 'is_closed':
				return ( $this->status == 'closed' );
			default:
				debug_die( 'This action is not handled during status check!' );
		}
	}

	/**
	 * Check if the user has the given role in any blog
	 *
	 * @param string role name, available values ( post_owner, moderator )
	 * @return mixed NULL if the given roll name is not defined or there are no blogs, true if the user is super admin, 0 if the user doesn't have the given role, positive number otherwise
	 */
	function check_role( $rolename )
	{
		global $DB;

		if( $this->check_perm( 'blogs', 'editall' ) )
		{ // if user has global editall blogs permission then it has any kind of role in all blogs
			return true;
		}

		switch( $rolename )
		{
			case 'post_owner':
				// User is considerated as a post owner, if already has at least one post, or he has right to create posts
				if( $this->get_num_posts() > 0 )
				{ // User already has at least one post
					return true;
				}
				$role_conditions = array( 'perm_poststatuses' => array( 'IS NOT NULL', '<> ""' ) );
				break;

			case 'member':
				// User has member role if is member of at least one blog
				$role_conditions = array( 'ismember' => array( 'IS NOT NULL', '<> 0' ) );
				break;

			case 'comment_editor':
				// User has permission to edit some other users comments at least in one status
				$role_conditions = array(
					'perm_edit_cmt' => array( 'IS NOT NULL', '<> "no"', '<> "own"' ),
					'perm_cmtstatuses' => array( 'IS NOT NULL', '<> 0' )
				);
				break;

			case 'comment_moderator':
				// set comment moderator perm names
				$edit_perm_name = 'perm_edit_cmt';
				$statuses_perm_name = 'perm_cmtstatuses';
			case 'post_moderator':
				if( $rolename == 'post_moderator' )
				{ // set post moderator perm names
					$edit_perm_name = 'perm_edit';
					$statuses_perm_name = 'perm_poststatuses';
				}
				// User is a moderator if has moderator permission at least in one blog
				// A moderator must have permissions to create post/comment with at least two statuses from moderation statuses + published status
				$check_statuses = get_visibility_statuses( 'moderation' );
				// Create addition of statuses perm values
				$perms_value = get_status_permvalue( 'published' );
				foreach( $check_statuses as $status )
				{
					$perms_value = $perms_value + get_status_permvalue( $status );
				}

				// Check if user has permission to edit other comments than his own and has create permission on at least two statuses defined above
				$role_conditions = array(
					$edit_perm_name => array( 'IS NOT NULL', '<> "no"', '<> "own"' ),
					$statuses_perm_name => array( 'IS NOT NULL', 'BIT_COUNT( $perm_field$ & '.$perms_value.' ) > 1' )
				);
				break;

			default: // roll with the given roll name is not defined
				return NULL;
		}

		$where_clause = '';
		$perm_prefixes = array( 'bloguser_', 'bloggroup_' );
		foreach( $perm_prefixes as $prefix )
		{ // Check requred perms on blogusers and bloggroups as well
			$where_part = '';
			foreach( $role_conditions as $perm_name => $conditions )
			{ // Go through each required permission
				$perm_field = $prefix.$perm_name;
				foreach( $conditions as $condition )
				{ // Check all defined conditions and join with 'AND' operator
					if( strpos( $condition, '$perm_field$' ) !== false )
					{ // The $perm_filed must be replaced in the middle of the condition
						$where_part .= '( '.str_replace( '$perm_field$', $perm_field, $condition ).' ) AND ';
					}
					else
					{ // The $perm_filed must be added into the beginning of the condition
						$where_part .= '( '.$perm_field.' '.$condition.' ) AND ';
					}
				}
			}
			// Remove the last ' AND ' from the end of this where clause part
			$where_part = substr( $where_part, 0, strlen( $where_part ) - 5 );
			// Add the created conditions to the final where clause
			$where_clause .= '( '.$where_part.' )';
			if( $prefix != 'bloggroup_' )
			{ // 'bloggroup_' perm check is the last, but everywhere else we need an 'OR' operator
				$where_clause .= ' OR ';
			}
		}

		// Count blog ids where this user has the required permissions for the given role
		$SQL = new SQL();
		$SQL->SELECT( 'count( blog_ID )' );
		$SQL->FROM( 'T_blogs' );
		$SQL->FROM_add( 'LEFT JOIN T_coll_user_perms ON (blog_advanced_perms <> 0 AND blog_ID = bloguser_blog_ID AND bloguser_user_ID = '.$this->ID.' )' );
		$SQL->FROM_add( 'LEFT JOIN T_coll_group_perms ON (blog_advanced_perms <> 0 AND blog_ID = bloggroup_blog_ID AND bloggroup_group_ID = '.$this->grp_ID.' )' );
		$SQL->WHERE( 'blog_owner_user_ID = '.$this->ID );
		$SQL->WHERE_or( $where_clause );

		return $DB->get_var( $SQL->get(), 0, NULL, 'Check user role in all blogs' );
	}


	/**
	 * Check if this user and his group accept receiving private messages or not
	 *
	 * @return boolean
	 */
	function accepts_pm()
	{
		global $UserSettings;
		return ( $UserSettings->get( 'enable_PM', $this->ID ) && $this->check_perm( 'perm_messaging', 'reply' ) );
	}


	/**
	 * Check if this user accepts receiving emails and has an email address
	 *
	 * @return boolean
	 */
	function accepts_email()
	{
		global $UserSettings, $Settings;
		return ( $Settings->get( 'emails_msgform') != 'never' ) && ( $UserSettings->get( 'enable_email', $this->ID ) ) && ( ! empty( $this->email ) );
	}


	/**
	 * Get messaging possibilities between current user and this user
	 *
	 * @return NULL|string allowed messaging possibility: PM > email > login > NULL
	 */
	function get_msgform_possibility( $current_User = NULL )
	{
		global $DB;

		$check_owner_SQL = new SQL();
		$check_owner_SQL->SELECT( 'blog_ID' );
		$check_owner_SQL->FROM( 'T_blogs' );
		$check_owner_SQL->WHERE( 'blog_owner_user_ID = '.$DB->quote( $this->ID ) );
		$check_owner_SQL->LIMIT( '1' );

		if( $DB->get_var( $check_owner_SQL->get() ) )
		{ // Allow to contact with this user because he is owner of at least one blog
			$force_contact_blog_owner = true;
		}
		else
		{
			$force_contact_blog_owner = false;
		}

		if( ! $force_contact_blog_owner && ! $this->check_status( 'can_receive_any_message' ) )
		{ // there is no way to send a message to a user with closed account
			return NULL;
		}

		if( is_logged_in() )
		{ // current User is a registered user
			if( $current_User == NULL )
			{
				global $current_User;
			}
			if( ! $force_contact_blog_owner && has_cross_country_restriction( 'contact' ) && ( empty( $current_User->ctry_ID ) || ( $current_User->ctry_ID !== $this->ctry_ID ) ) )
			{ // Contat to this user is not enabled
				return NULL;
			}
			if( $this->accepts_pm() && $current_User->accepts_pm() && ( $this->ID != $current_User->ID ) )
			{ // both user has permission to send or receive private message and not the same user
				// check if current_User is allowed to create a new conversation with this user.
				$blocked_contact = check_blocked_contacts( array( $this->ID ) );
				if( empty( $blocked_contact ) )
				{ // user is allowed to send pm to this user, and he didn't reached his new thread limit yet
					return 'PM';
				}
			}
			if( $force_contact_blog_owner || $this->accepts_email() )
			{ // this user allows email => send email OR Force to allow to contact with this user because he is owner of the selected blog
				return 'email';
			}
		}
		else
		{ // current User is not logged in
			if( $force_contact_blog_owner || $this->accepts_email() )
			{ // this user allows email OR Force to allow to contact with this user because he is owner of the selected blog
				return 'email';
			}
			if( $this->accepts_pm() )
			{ // no email option - try to log in and send private message (only registered users can send PM)
				return 'login';
			}
		}
		// no messaging option between current_User and this user
		return NULL;
	}


	/**
	 * Get the reason why current User is not able to make a contact with this User
	 *
	 * This is used when get_msgform_possibility returns NULL;
	 *
	 * @return string
	 */
	function get_no_msgform_reason()
	{
		global $current_User;

		if( ( is_logged_in() ) && $this->accepts_pm() )
		{
			if( has_cross_country_restriction( 'contact' ) && ( empty( $current_User->ctry_ID ) || ( $current_User->ctry_ID !== $this->ctry_ID ) ) )
			{ // Contat to this user is not enabled
				return T_( 'You are not allowed to contact with this user.' );
			}
			if( $current_User->accepts_pm() )
			{ // current User accepts private messages
				if( $current_User->ID == $this->ID )
				{ // current User and recipient user are the same
					return T_( 'You cannot send a private message to yourself.' );
				}
				// current User is not able to contact with this User because User has blocked current User or current User is not in this users contact list
				return T_( 'This user can only be contacted through private messages, but you are not allowed to send private message to this user.' );
			}
			// current User has no messaging permission or has disabled private messages in user preferences
			return T_( 'This user can only be contacted through private messages but you are not allowed to send any private messages.' );
		}
		// current User is not logged in or this User doesn't want to be contacted
		return T_( 'This user does not wish to be contacted directly.' );
	}


	/**
	 * Insert object into DB based on previously recorded changes
	 *
	 * Triggers the plugin event AfterUserInsert.
	 *
	 * @param boolean TRUE to automatically create new blog if group has permission
	 * @return boolean true on success
	 */
	function dbinsert( $create_auto_blog = true )
	{
		global $Plugins, $DB;

		$DB->begin();

		if( $result = parent::dbinsert() )
		{ // We could insert the user object..

			// Add new fields:
			if( !empty($this->new_fields) )
			{
				$sql = 'INSERT INTO T_users__fields( uf_user_ID, uf_ufdf_ID, uf_varchar )
								VALUES ('.$this->ID.', '.implode( '), ('.$this->ID.', ', $this->new_fields ).' )';
				$DB->query( $sql, 'Insert new fields' );
				// Reset new fields in object:
				$this->new_fields = array();
			}

			// Notify plugins:
			// A user could be created also in another DB (to synchronize it with b2evo)
			$Plugins->trigger_event( 'AfterUserInsert', $params = array( 'User' => & $this ) );

			$Group = & $this->get_Group();
			if( $create_auto_blog && $Group->check_perm( 'perm_getblog', 'allowed' ) )
			{ // automatically create new blog for this user
				// TODO: sam2kb> Create a blog only when this user is validated!
				$new_Blog = new Blog( NULL );
				$shortname = $this->get( 'login' );
				$new_Blog->set( 'owner_user_ID', $this->ID );
				$new_Blog->set( 'shortname', $shortname );
				$new_Blog->set( 'name', $shortname.'\'s blog' );
				$new_Blog->set( 'locale', $this->get( 'locale' ));
				$new_Blog->set( 'urlname', urltitle_validate( $shortname, $shortname, $new_Blog->ID, false, 'blog_urlname', 'blog_ID', 'T_blogs', $this->get( 'locale' ) ) );

				// Defines blog settings by its kind.
				$Plugins->trigger_event( 'InitCollectionKinds', array(
								'Blog' => & $new_Blog,
								'kind' => 'std',
							) );

				$new_Blog->create();
			}

			// Save IP Range and user counter
			antispam_increase_counter( 'user' );
		}

		$DB->commit();

		return $result;
	}


	/**
	 * Update the DB based on previously recorded changes.
	 *
	 * Triggers the plugin event AfterUserUpdate.
	 */
	function dbupdate()
	{
		global $DB, $Plugins, $current_User, $localtimenow;

		$DB->begin();

		$result = parent::dbupdate();

		// Update existing fields:
		if( !empty($this->updated_fields) )
		{
			foreach( $this->updated_fields as $uf_ID => $uf_val )
			{
				// Note the updated_fields key values must be integers, so don't need casting or DB->quote()
				if( empty( $uf_val ) )
				{	// Delete field:
					$DB->query( 'DELETE FROM T_users__fields
														 WHERE uf_ID = '.$uf_ID );
				}
				else
				{	// Update field:
					$DB->query( 'UPDATE T_users__fields
													SET uf_varchar = '.$DB->quote( $uf_val ).'
												WHERE uf_ID = '.$uf_ID );
				}
			}
			// Reset updated fields in object:
			$this->updated_fields = array();
		}

		// Add new fields:
		if( !empty($this->new_fields) )
		{
			$sql = 'INSERT INTO T_users__fields( uf_user_ID, uf_ufdf_ID, uf_varchar )
							VALUES ('.$this->ID.', '.implode( '), ('.$this->ID.', ', $this->new_fields ).' )';
			$DB->query( $sql, 'Insert new fields' );
			// Reset new fields in object:
			$this->new_fields = array();
		}

		// Notify plugins:
		// Example: An authentication plugin could synchronize/update the password of the user.
		$Plugins->trigger_event( 'AfterUserUpdate', $params = array( 'User' => & $this ) );

		$DB->commit();

		// BLOCK CACHE INVALIDATION:
		// This User has been modified, cached content depending on it should be invalidated:
		BlockCache::invalidate_key( 'user_ID', $this->ID );

		return true;
	}


	/**
	 * Delete those users from the database which corresponds to the given condition or to the given ids array
	 * Note: the delete cascade arrays are handled!
	 *
	 * @param string the name of this class
	 *   Note: This is required until min phpversion will be 5.3. Since PHP 5.3 we can use static::function_name to achieve late static bindings
	 * @param string where condition
	 * @param array object ids
	 * @return mixed # of rows affected or false if error
	 */
	static function db_delete_where( $class_name, $sql_where, $object_ids = NULL, $params = NULL )
	{
		global $DB;

		$DB->begin();

		if( ! empty( $sql_where ) )
		{
			$object_ids = $DB->get_col( 'SELECT user_ID FROM T_users WHERE '.$sql_where );
		}

		if( ! $object_ids )
		{ // There is no user to delete
			$DB->commit();
			return;
		}

		// Delete orphan threads per users
		$result = delete_orphan_threads( $object_ids );

		// Remove deleted user(s) from posts where it was as last edit user
		$user_ids = implode( ',', $object_ids );
		$result = $result && ( $DB->query( 'UPDATE T_items__item
								    SET post_lastedit_user_ID = NULL
								  WHERE post_lastedit_user_ID IN ( '.$user_ids.' )' ) !== false );
		$result = $result && ( $DB->query( 'UPDATE T_items__version
								    SET iver_edit_user_ID = NULL
								  WHERE iver_edit_user_ID IN ( '.$user_ids.' )' ) !== false );

		// Remove this user from links where it was as last edit user
		$result = $result && ( $DB->query( 'UPDATE T_links
								    SET link_lastedit_user_ID = NULL
								  WHERE link_lastedit_user_ID IN ( '.$user_ids.' )' ) !== false );

		if( $result )
		{ // Delete the user(s) with all of the cascade objects
			$params['use_transaction'] = false; // no need to start a new transaction
			$result = parent::db_delete_where( $class_name, $sql_where, $object_ids, $params );
		}

		if( $result !== false )
		{ // delete the users' media folders recursively, for all deleted users
			$FileRootCache = & get_FileRootCache();
			foreach( $object_ids as $user_ID )
			{
				if( $root_directory = $FileRootCache->get_root_dir( 'user', $user_ID ) )
				{ // Delete the folder only when it is detected:
					rmdir_r( $root_directory );
				}
			}
		}

		( $result !== false ) ? $DB->commit() : $DB->rollback();

		return $result;
	}


	/**
	 * Delete user and dependencies from database
	 *
	 * Includes WAY TOO MANY requests because we try to be compatible with MySQL 3.23, bleh!
	 *
	 * @param Log Log object where output gets added (by reference).
	 */
	function dbdelete( & $Log )
	{
		global $DB, $Plugins;

		if( $this->ID == 0 ) debug_die( 'Non persistant object cannot be deleted!' );

		$deltype = param( 'deltype', 'string', '' ); // spammer

		$DB->begin();

		if( $deltype == 'spammer' )
		{ // If we delete user as spammer we should delete private messaged of this user
			$this->init_relations( true );
		}
		else
		{ // If we delete user as not spammer we keep his comments as from anonymous user
			// Transform registered user comments to unregistered:
			$ret = $DB->query( 'UPDATE T_comments
													SET comment_author_user_ID = NULL,
															comment_author = '.$DB->quote( $this->get('preferredname') ).',
															comment_author_email = '.$DB->quote( $this->get('email') ).',
															comment_author_url = '.$DB->quote( $this->get('url') ).'
													WHERE comment_author_user_ID = '.$this->ID );
			if( is_a( $Log, 'log' ) )
			{
				$Log->add( 'Transforming user\'s comments to unregistered comments... '.sprintf( '(%d rows)', $ret ), 'note' );
			}
		}

		// remember ID, because parent method resets it to 0
		$old_ID = $this->ID;
		$old_email = $this->get( 'email' );

		// Delete main object:
		if( ! parent::dbdelete() )
		{
			$DB->rollback();

			$Log->add( 'User has not been deleted.', 'error' );
			return false;
		}

		if( $deltype == 'spammer' )
		{ // User was deleted as spammer, we should mark email of this user as 'Spammer'
			$EmailAddressCache = & get_EmailAddressCache();
			$EmailAddress = & $EmailAddressCache->get_by_name( $old_email, false, false );
			if( !$EmailAddress )
			{	// Create new record in the T_email_address table
				$EmailAddress = new EmailAddress();
				$EmailAddress->set( 'address', $old_email );
			}
			if( !empty( $EmailAddress ) )
			{ // Save status of an email address
				$EmailAddress->set( 'status', 'spammer' );
				$EmailAddress->dbsave();
			}
		}

		$DB->commit();

		if( is_a( $Log, 'log' ) )
		{
			$Log->add( 'Deleted User.', 'note' );
		}

		// Notify plugins:
		$this->ID = $old_ID;
		$Plugins->trigger_event( 'AfterUserDelete', $params = array( 'User' => & $this ) );
		$this->ID = 0;

		// BLOCK CACHE INVALIDATION:
		// This User has been modified, cached content depending on it should be invalidated:
		BlockCache::invalidate_key( 'user_ID', $old_ID );

		return true;
	}


	/**
	 * Send an email to the user with a link to validate/confirm his email address.
	 *
	 * If the email could get sent, it saves the used "request_id" into the user's Session.
	 *
	 * @param string URL, where to redirect the user after he clicked the validation link (gets saved in Session).
	 * @return boolean True, if the email could get sent; false if not
	 */
	function send_validate_email( $redirect_to_after, $blog = NULL, $email_changed = false )
	{
		global $app_name, $Session, $secure_htsrv_url, $baseurl, $servertimenow;
		global $Settings, $UserSettings;

		// Display messages depending on user email status
		display_user_email_status_message( $this->ID );

		if( $Settings->get( 'validation_process' ) == 'easy' )
		{ // validation process is set to easy, send and easy activation email
			return send_easy_validate_emails( array( $this->ID ), false, $email_changed );
		}

		if( mail_is_blocked( $this->email ) )
		{ // prevent trying to send an email to a blocked email address ( Note this is checked in the send_easy_validate_emails too )
			return false;
		}

		if( empty( $redirect_to_after ) )
		{ // redirect to was not set
			$redirect_to_after = param( 'redirect_to', 'url', '' );
			if( empty( $redirect_to_after ) )
			{
				if( is_admin_page() )
				{
					$redirect_to_after = regenerate_url( 'action' );
				}
				else
				{
					$redirect_to_after = $this->get_userpage_url();
				}
			}
		}

		$request_id = generate_random_key(22);

		$blog_param = ( empty( $blog ) ) ? '' : '&inskin=1&blog='.$blog;

		// Change locale here to localize the email subject and content
		locale_temp_switch( $this->get( 'locale' ) );
		$email_template_params = array(
				'status'     => $this->status,
				'blog_param' => $blog_param,
				'request_id' => $request_id,
			);
		$r = send_mail_to_User( $this->ID, T_('Activate your account: $login$'), 'account_activate', $email_template_params, true );
		locale_restore_previous();

		if( $r )
		{ // save request_id into Session
			$request_ids = $Session->get( 'core.validatemail.request_ids' );
			if( ( ! is_array($request_ids) ) || $email_changed )
			{ // create new request ids array if it doesn't exist yet, or if user email changed ( this way the old request into the old email address won't be valid )
				$request_ids = array();
			}
			$request_ids[] = $request_id;
			$Session->set( 'core.validatemail.request_ids', $request_ids, 86400 * 2 ); // expires in two days (or when clicked)
			// set a redirect_to session variable because this way after the account will be activated we will know where to redirect
			$Session->set( 'core.validatemail.redirect_to', $redirect_to_after  );
			$Session->dbsave(); // save immediately

			// update last activation email timestamp
			$UserSettings->set( 'last_activation_email', date2mysql( $servertimenow ), $this->ID );
			$UserSettings->dbupdate();
		}

		return $r;
	}


	/**
	 * Activate user account after user clicks on activate link from email
	 */
	function activate_from_Request()
	{
		global $DB, $Settings, $UserSettings;

		// Activate current user account:
		$this->set( 'status', 'activated' );
		$this->dbupdate();

		// clear last reminder key and last activation email date because the user was activated
		$UserSettings->delete( 'last_activation_reminder_key', $this->ID );
		$UserSettings->delete( 'last_activation_email', $this->ID );
		$UserSettings->delete( 'activation_reminder_count', $this->ID );
		$UserSettings->delete( 'send_activation_reminder', $this->ID );
		$UserSettings->dbupdate();

		if( $Settings->get( 'newusers_findcomments' ) )
		{	// We have to assign the all old comments from current user by email
			$DB->query( '
				UPDATE T_comments
				   SET comment_author_user_ID = "'.$this->ID.'"
				 WHERE comment_author_email = '.$DB->quote( $this->email ).'
				   AND comment_author_user_ID IS NULL' );
		}

		// Create a welcome private message when user's status was changed to Active
		$this->send_welcome_message();

		// Send notification email about activated account to users with edit users permission
		$email_template_params = array(
			'User' => $this,
			'login' => $this->login, // this is required in the send_admin_notification
		);
		send_admin_notification( NT_('New user account activated'), 'account_activated', $email_template_params );
	}


	// Template functions {{{

	/**
	 * Template function: display user's level
	 */
	function level()
	{
		$this->disp( 'level', 'raw' );
	}


	/**
	 * Template function: display user's login
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function login( $format = 'htmlbody' )
	{
		$this->disp( 'login', $format );
	}


	/**
	 * Template helper function: Get a link to a message form for this user.
	 *
	 * @param string url of the message form
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 */
	function get_msgform_link( $form_url = NULL, $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '' )
	{
		if( empty($this->email) )
		{ // We have no email for this User :(
			return false;
		}

		$available_msgform = $this->get_msgform_possibility();
		if( ! $available_msgform )
		{	// There is no way this user accepts receiving messages.
			return false;
		}

		if( is_null($form_url) )
		{
			global $Blog;
			$form_url = isset($Blog) ? $Blog->get('msgformurl') : '';
		}

		$form_url = url_add_param( $form_url, 'recipient_id='.$this->ID.'&amp;redirect_to='.rawurlencode(url_rel_to_same_host(regenerate_url('','','','&'), $form_url)) );

		if( $title == '#' )
		{
			switch( $available_msgform )
			{
				case 'email':
					$title = T_('Send email to user');
					break;
				case 'PM':
				case 'login':
				default:
					$title = T_('Send message to user');
					break;
			}
		}
		if( $text == '#' ) $text = get_icon( 'email', 'imgtag', array( 'class' => 'middle', 'title' => $title ) );

		$r = '';
		$r .= $before;
		$r .= '<a href="'.$form_url.'" title="'.$title.'"';
		if( !empty( $class ) )
		{
			$r .= ' class="'.$class.'"';
		}
		$r .= '>'.$text.'</a>';
		$r .= $after;

		return $r;
	}


	/**
	 * Template function: display a link to a message form for this user
	 *
	 * @param string url of the message form
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 */
	function msgform_link( $form_url = NULL, $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '' )
	{
		echo $this->get_msgform_link( $form_url, $before, $after, $text, $title, $class );
	}


	/**
	 * Template function: display user's preferred name
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function preferred_name( $format = 'htmlbody' )
	{
		echo format_to_output( $this->get_preferred_name(), $format );
	}


	/**
	 * Template function: display user's URL
	 *
	 * @param string string to display before the date (if changed)
	 * @param string string to display after the date (if changed)
	 * @param string Output format, see {@link format_to_output()}
	 */
	function url( $before = '', $after = '', $format = 'htmlbody' )
	{
		if( !empty( $this->url ) )
		{
			echo $before;
			$this->disp( 'url', $format );
			echo $after;
		}
	}


	/**
	 * Template function: display number of user's posts
	 */
	function num_posts( $format = 'htmlbody' )
	{
		echo format_to_output( $this->get_num_posts(), $format );
	}


	/**
	 * Template function: display first name of the user
	 */
	function first_name( $format = 'htmlbody' )
	{
		$this->disp( 'firstname', $format );
	}


	/**
	 * Template function: display last name of the user
	 */
	function last_name( $format = 'htmlbody' )
	{
		$this->disp( 'lastname', $format );
	}


	/**
	 * Template function: display nickname of the user
	 */
	function nick_name( $format = 'htmlbody' )
	{
		$this->disp( 'nickname', $format );
	}


	/**
	 * Return gender of the user
	 */
	function get_gender()
	{
		switch( $this->gender )
		{
			case 'M':
				return T_('A man');

			case 'F':
				return T_('A woman');
		}

		return NULL;
	}


	/**
	 * Return attr class depending on gender of the user
	 */
	function get_gender_class()
	{
		$gender_class = 'user';

		if( $this->check_status( 'is_closed' ) )
		{ // Set different gender color if user is closed
			return 'user closed';
		}

		if( ! check_setting( 'gender_colored' ) )
		{ // Don't set gender color if setting is OFF
			return $gender_class;
		}

		switch( $this->gender )
		{ // Set a class name for each gender type
			case 'M':
				$gender_class .= ' man';
				break;
			case 'F':
				$gender_class .= ' woman';
				break;
			default:
				$gender_class .= ' nogender';
				break;
		}

		return $gender_class;
	}


	/**
	 * Template function: display email of the user
	 */
	function email( $format = 'htmlbody' )
	{
		$this->disp( 'email', $format );
	}


	/**
	 * Template function: display ICQ of the user
	 * @deprecated
	 */
	function icq( $format = 'htmlbody' )
	{
	}


	/**
	 * Template function: display AIM of the user.
	 * @deprecated
	 */
	function aim( $format = 'htmlbody' )
	{
	}


	/**
	 * Template function: display Yahoo IM of the user
	 * @deprecated
	 */
	function yim( $format = 'htmlbody' )
	{
	}


	/**
	 * Template function: display MSN of the user
	 * @deprecated
	 */
	function msn( $format = 'htmlbody' )
	{
	}

	// }}}


	function has_avatar()
	{
		global $Settings;

		return ( !empty( $this->avatar_file_ID ) && $Settings->get('allow_avatars') );
	}


	/**
	 * Get {@link File} object of the user's avatar.
	 *
	 * @return File This may be NULL.
	 */
	function & get_avatar_File()
	{
		$File = NULL;

		if( $this->has_avatar() )
		{
			$FileCache = & get_FileCache();

			// Do not halt on error. A file can disappear without the profile being updated.
			/**
			 * @var File
			 */
			$File = & $FileCache->get_by_ID( $this->avatar_file_ID, false, false );
		}

		return $File;
	}


	/**
	 * Get {@link Link} object of the user's avatar.
	 *
	 * @return Link This may be NULL.
	 */
	function & get_avatar_Link()
	{
		$Link = NULL;

		if( $this->has_avatar() )
		{
			$LinkOwner = new LinkUser( $this );
			$Link = $LinkOwner->get_link_by_file_ID( $this->avatar_file_ID );
		}

		return $Link;
	}


	/**
	 * Get array of {@link Link} objects of the previously uploaded avatars.
	 *
	 * @param boolean TRUE - to exclude main picture from list
	 * @param boolean TRUE - to ignore the settings and get the avatars anyway
	 * @return array of Links
	 */
	function get_avatar_Links( $exclude_main_picture = true, $ignore_settings = false )
	{
		global $Settings;

		$avatar_Links = array();

		if( !$ignore_settings && !( $Settings->get('upload_enabled') && $Settings->get( 'fm_enable_roots_user' ) ) )
		{ // Upload is not enabled and we have no permission to use it...
			return $avatar_Links;
		}

		$LinkOwner = new LinkUser( $this );
		foreach( $LinkOwner->get_Links() as $user_Link )
		{
			$l_File = & $user_Link->get_File();
			if( ! empty( $l_File ) && $l_File->is_image() )
			{
				if( $exclude_main_picture && $l_File->ID == $this->avatar_file_ID )
				{ // Exclude the main picture from list of other pictures
					continue;
				}
				$avatar_Links[] = $user_Link;
			}
		}

		return $avatar_Links;
	}


	/**
	 * Get avatar IMG tag.
	 *
	 * @param string size
	 * @param string class
	 * @param string align
	 * @param boolean true if the avatar image should be zoomed on click, false otherwise
	 * @param string avatar overlay text
	 * @param string group name for lightbox plugin
	 * @param string Change size of the attributes "width" & "height".
	 *               Example: ( $tag_size = '160' ) => width="160" height="160"
	 *                        ( $tag_size = '160x320' ) => width="160" height="320"
	 *                        NULL - use real size
	 * @return string
	 */
	function get_avatar_imgtag( $size = 'crop-top-64x64', $class = 'avatar', $align = '', $zoomable = false, $avatar_overlay_text = '', $lightbox_group = '', $tag_size = NULL )
	{
		global $current_User;

		/**
		 * @var Link
		 */
		$Link = & $this->get_avatar_Link();

		/**
		 * @var File
		 */
		if( ! $Link || ! $File = & $Link->get_File() )
		{ // User doesn't have an avatar
			return get_avatar_imgtag_default( $size, $class, $align, array(
					'email'    => $this->get( 'email' ),
					'gender'   => $this->get( 'gender' ),
					'tag_size' => $tag_size,
				) );
		}

		if( ( !$this->check_status( 'can_display_avatar' ) ) && !( is_admin_page() && is_logged_in( false ) && ( $current_User->check_perm( 'users', 'edit' ) ) ) )
		{ // if the user status doesn't allow to display avatar and current User is not an admin in admin interface, then show default avatar
			return get_avatar_imgtag_default( $size, $class, $align, array(
					'email'    => $this->get( 'email' ),
					'gender'   => $this->get( 'gender' ),
					'tag_size' => $tag_size,
				) );
		}

		if( $zoomable )
		{ // return clickable avatar tag, zoom on click
			if( is_logged_in() )
			{ // Only logged in users can see a big picture of the avatar
				// set random value to link_rel, this way the pictures on the page won't be grouped
				// this is usefull because the same avatar picture may appear more times in the same page
				if( empty( $lightbox_group ) )
				{
					$link_rel = 'lightbox[l'.$Link->ID.rand(0, 100000).']';
				}
				else
				{
					$link_rel = 'lightbox['.$lightbox_group.']';
				}
				$r = $Link->get_tag( array(
						'before_image'        => '',
						'before_image_legend' => '',
						'after_image_legend'  => '',
						'after_image'         => '',
						'image_size'          => $size,
						'image_link_title'    => $this->login,
						'image_link_rel'      => $link_rel,
						'image_class'         => $class,
						'image_align'         => $align,
						'tag_size'            => $tag_size,
					) );
			}
			else
			{ // Anonymous user get an avatar picture with link to login page
				global $Blog;
				$redirect_to = '';
				if( isset( $Blog ) )
				{ // Redirect user after login
					$redirect_to = $Blog->get( 'userurl', array( 'glue' => '&', 'url_suffix' => 'user_ID='.$this->ID ) );
				}
				$r = '<a href="'.get_login_url( 'cannot see avatar', $redirect_to ) .'">'.$File->get_thumb_imgtag( $size, $class, $align, '', $tag_size ).'</a>';
			}
		}
		else
		{
			$r = $File->get_thumb_imgtag( $size, $class, $align, '', $tag_size );
		}

		if( $r != '' && $avatar_overlay_text != '' )
		{ // Add overlay text if it is enabled
			$r = $this->get_avatar_overlay_text( $r, $size, $avatar_overlay_text, $class, $File );
		}

		return $r;
	}


	/**
	 * Get overlay text for avatar img tag
	 *
	 * @param string Tag <img />
	 * @param array Avatar size, keys: 'width' & 'height'
	 * @param string Avatar overlay text
	 * @param string Class
	 * @param object File
	 * @return string Tag <img /> with overlay text
	 */
	function get_avatar_overlay_text( $img_tag, $thumb_size, $avatar_overlay_text, $class = '', $File = NULL )
	{
		preg_match( '/ width="(\d+)" height="(\d+)" /i', $img_tag, $img_sizes );
		if( count( $img_sizes ) == 3 )
		{ // img tag has a defined width & height
			$width = $img_sizes[1];
			$height = $img_sizes[2];
		}
		else
		{ // We try to get a sizes from config
			global $thumbnail_sizes;
			if( isset( $thumbnail_sizes[ $thumb_size ] ) )
			{ // Set a sizes
				if( ! is_null( $File ) && $thumb_sizes = $File->get_thumb_size( $thumb_size ) )
				{ // Get sizes that are used for thumbnail really
					list( $width, $height ) = $thumb_sizes;
				}
				else
				{ // Use the size of thumbnail config if File is not defined
					$width = $thumbnail_sizes[ $thumb_size ][1];
					$height = $thumbnail_sizes[ $thumb_size ][2];
				}
			}
		}

		if( empty( $width ) || empty( $height ) )
		{ // If sizes is not defined we cannot calculate a font-size for an overlay text
			return $img_tag;
		}

		$overlay_lines = explode( "\n", str_replace( "\r", '', $avatar_overlay_text ) );
		$max_line_length = 0;
		foreach( $overlay_lines as $line )
		{ // Find the most long line of the overlay text
			if( $max_line_length < strlen($line) )
			{	// Get max long line
				$max_line_length = strlen($line);
			}
		}
		if( $max_line_length > 0 )
		{ // Display an overlay text if max length is defined
			// Calculate approximate font size, 1.7 - is custom coefficient of the font
			$font_size = ceil( ( $width / $max_line_length ) * 1.7 );
			// Set line-height for centering text by vertical
			$line_height = ceil( ( $height / count( $overlay_lines ) ) * 0.82 );
			// Padding-top give us a vertical centering
			$padding_top = ceil( $line_height * 0.32 );

			$tag_is_linked = false;
			if( strpos( $img_tag, '</a>' ) !== false )
			{ // img_tag is located inside tag <a>, we should to remove a end of the tag and then add
				$img_tag = str_replace( '</a>', '', $img_tag );
				$tag_is_linked = true;
			}
			$img_tag = '<div class="bubletip_overlay_text '.$class.'">'.
					$img_tag.
					'<div style="font-size:'.$font_size.'px;line-height:'.$line_height.'px;padding-top:'.$padding_top.'px">'.
						nl2br($avatar_overlay_text).
					'</div>';
			if( $tag_is_linked )
			{ // Add end of the tag which is removed above
				$img_tag .= '</a>';
			}
			$img_tag .= '</div>';
		}

		return $img_tag;
	}


	/**
	 * Get styled avatar
	 *
	 * @param array params
	 * @return string
	 */
	function get_avatar_styled( $params = array() )
	{
		global $thumbnail_sizes;

		$params = array_merge( array(
				'block_class'  => 'avatar_rounded',
				'size'         => 'crop-top-64x64',
				'avatar_class' => 'avatar',
				'zoomable'     => false,
				'overlay_text' => '',
				'show_login'   => true,
				'bubbletip'    => true,
			), $params );

		$bubbletip_param = '';
		if( $params['bubbletip'] )
		{	// Init bubbletip param
			$bubbletip_param = 'rel="bubbletip_user_'.$this->ID.'"';
		}

		$style_width = '';
		if( isset( $thumbnail_sizes[$params['size']] ) )
		{
			$style_width = ' style="width:'.$thumbnail_sizes[$params['size']][1].'px"';
		}

		$identity_url = get_user_identity_url( $this->ID );

		if( !empty( $identity_url ) )
		{
			$r = '<a href="'.$identity_url.'" class="'.$params['block_class'].'"'.$bubbletip_param.$style_width.'>';
		}
		else
		{
			$r = '<div class="'.$params['block_class'].'"'.$bubbletip_param.$style_width.'>';
		}

		$r .= $this->get_avatar_imgtag( $params['size'], $params['avatar_class'], '', $params['zoomable'], $params['overlay_text'] );

		if( $params['show_login'] )
		{	// Display user name
			$r .= $this->get_colored_login( array( 'login_text' => 'name' ) );
		}

		$r .= !empty( $identity_url ) ? '</a>' : '</div>';

		return $r;
	}


	/**
	 * Add a user field
	 */
	function userfield_add( $type, $val )
	{
		global $DB;
		$this->new_fields[] = $type.', '.$DB->quote( $val );
	}


	/**
	 * Update an user field. Empty fields will be deleted on dbupdate.
	 */
	function userfield_update( $uf_ID, $val )
	{
		global $DB;
		$this->updated_fields[$uf_ID] = $val;
		// pre_dump( $uf_ID, $val);
	}


	/**
	 * Get all user extra fields with selected type
	 *
	 * @param string Field type: 'email', 'word', 'number', 'phone', 'text'
	 * @param boolean TRUE to get only the fields with icon
	 * @return array Fields
	 */
	function userfields_by_type( $field_type, $only_with_icon = true )
	{
		global $DB;

		$SQL = new SQL();
		$SQL->SELECT( 'uf_varchar, ufdf_ID, ufdf_icon_name, ufdf_code' );
		$SQL->FROM( 'T_users__fields' );
		$SQL->FROM_add( 'INNER JOIN T_users__fielddefs ON uf_ufdf_ID = ufdf_ID' );
		$SQL->WHERE( 'uf_user_ID = '.$this->ID );
		$SQL->WHERE_and( 'ufdf_type = '.$DB->quote( $field_type ) );
		if( $only_with_icon )
		{
			$SQL->WHERE_and( 'ufdf_icon_name IS NOT NULL' );
		}
		$SQL->ORDER_BY( 'ufdf_order' );

		return $DB->get_results( $SQL->get() );
	}


	/**
	 * Get user field value by ID
	 *
	 * @param integer Field ID
	 * @return string Field value
	 */
	function userfield_value_by_ID( $field_ID )
	{
		global $DB;

		// Load all user fields once
		$this->userfields_load();

		if( isset( $this->userfields_by_type[ $field_ID ], $this->userfields[ $this->userfields_by_type[ $field_ID ][0] ] ) )
		{ // Get value from cache
			return $this->userfields[ $this->userfields_by_type[ $field_ID ][0] ]->uf_varchar;
		}

		// No field value
		return '';
	}


	/**
	 * Get user field values by code
	 *
	 * @param integer Field code
	 * @return array Field values
	 */
	function userfield_values_by_code( $field_code )
	{
		global $DB;

		// Load all user fields once
		$this->userfields_load();

		$field_values = array();
		if( ! empty( $this->userfields_by_code[ $field_code ] ) )
		{ // Get value from cache
			foreach( $this->userfields_by_code[ $field_code ] as $userfield_ID )
			{
				$field_values[] = $this->userfields[ $userfield_ID ]->uf_varchar;
			}
		}

		return $field_values;
	}


	/**
	 * Load userfields
	 */
	function userfields_load()
	{
		global $DB;

		$userfields = $DB->get_results( '
			SELECT uf_ID, ufdf_ID, uf_varchar, ufdf_duplicated, ufdf_type, ufdf_name, ufdf_icon_name, ufdf_code, ufgp_ID, ufgp_name
				FROM T_users__fields
					INNER JOIN T_users__fielddefs ON uf_ufdf_ID = ufdf_ID
					INNER JOIN T_users__fieldgroups ON ufdf_ufgp_ID = ufgp_ID
			WHERE uf_user_ID = '.$this->ID.'
				AND ufdf_required != "hidden"
			ORDER BY ufgp_order, ufdf_order, uf_ID' );

		$userfield_lists = array();
		foreach( $userfields as $userfield )
		{
			if( $userfield->ufdf_duplicated == 'list' )
			{ // Prepare a values of list into one array
				if( !isset( $userfield_lists[$userfield->ufdf_ID] ) )
				{	// Init array
					$userfield_lists[$userfield->ufdf_ID] = array();
				}
				userfield_prepare( $userfield );
				$userfield_lists[$userfield->ufdf_ID][] = $userfield->uf_varchar;
			}
		}

		foreach( $userfields as $userfield )
		{
			if( $userfield->ufdf_duplicated == 'list' )
			{ // List style
				if( isset( $userfield_lists[$userfield->ufdf_ID] ) )
				{ // Save all data for this field:
					$userfield->uf_varchar = implode( ', ', $userfield_lists[$userfield->ufdf_ID] );
					$this->userfields[$userfield->uf_ID] = $userfield;
					$this->userfields_by_code[$userfield->ufdf_code][] = $userfield->uf_ID;
					// Unset array to avoid a duplicates
					unset( $userfield_lists[$userfield->ufdf_ID] );
				}
			}
			else
			{ // Save all data for this field:
				userfield_prepare( $userfield );
				$this->userfields[$userfield->uf_ID] = $userfield;
				$this->userfields_by_code[$userfield->ufdf_code][] = $userfield->uf_ID;
			}
			// Save index
			$this->userfields_by_type[$userfield->ufdf_ID][] = $userfield->uf_ID;
		}

		// Also make sure the definitions are loaded
		$this->userfield_defs_load();
	}


	/**
	 * Load userfields defs
	 */
	function userfield_defs_load()
	{
		global $DB;

		if( !isset($this->userfield_defs) )
		{
			$userfield_defs = $DB->get_results( '
				SELECT ufdf_ID, ufdf_type, ufdf_name, ufdf_required, ufdf_options, ufdf_duplicated
					FROM T_users__fielddefs' );

			foreach( $userfield_defs as $userfield_def )
			{
				$this->userfield_defs[$userfield_def->ufdf_ID] = array(
					$userfield_def->ufdf_type,
					$userfield_def->ufdf_name,
					$userfield_def->ufdf_required,
					$userfield_def->ufdf_options,
					$userfield_def->ufdf_duplicated
				); //jamesz
			}
		}
	}


	/**
	* Get first field for a specific type
	*
	* @return string or NULL
	*/
	function userfieldget_first_for_type( $type_ID )
	{
		if( !isset($this->userfields_by_type[$type_ID]) )
		{
			return NULL;
		}

		$idx = $this->userfields_by_type[$type_ID][0];

		return $this->userfields[$idx][1];
	}


	/**
	 * Update user data from Request form fields.
	 *
	 * @param boolean is new user
	 * @return mixed true on success, allowed action otherwise
	 */
	function update_from_request( $is_new_user = false )
	{
		global $current_User, $DB, $Messages, $UserSettings, $Settings, $blog, $admin_url;

		if( ! $current_User->can_moderate_user( $this->ID ) && $this->ID != $current_User->ID )
		{ // user is only allowed to update him/herself
			$Messages->add( T_('You are only allowed to update your own profile!') );
			return 'view';
		}

		// check if is admin form
		$is_admin_form = param( 'admin_form', 'boolean', false );

		// memorize user status ( activated or not )
		$user_was_activated = $this->check_status( 'is_validated' );
		$user_old_email = $this->email;

		// memorize user old login, status and root path, before update
		$user_old_login = $this->login;
		$user_root_path = NULL;
		$FileRootCache = & get_FileRootCache();
		if( !$is_new_user )
		{
			$user_FileRoot = & $FileRootCache->get_by_type_and_ID( 'user', $this->ID );
			if( $user_FileRoot && file_exists( $user_FileRoot->ads_path ) )
			{
				$user_root_path = $user_FileRoot->ads_path;
			}
		}

		// load data from request
		if( !$this->load_from_Request() )
		{	// We have found validation errors:
			if( $is_new_user || ( $is_admin_form && ( $this->ID != 1 ) ) )
			{ // update user status settings but not save
				$this->update_status_from_Request( false );
			}
			return 'edit';
		}

		// Update user
		$DB->begin();

		$is_password_form = param( 'password_form', 'boolean', false );
		if( $this->dbsave() )
		{
			$update_success = true;
			if( $is_new_user )
			{
				$Messages->add( T_('New user has been created.'), 'success' );
			}
			elseif( $is_password_form )
			{
				$Messages->add( T_('Password has been changed.'), 'success' );
			}
			else
			{
				if( $user_old_login != $this->login && $user_root_path != NULL )
				{ // user login changed and user has a root directory (another way $user_root_path value would be NULL)
					$FileRootCache->clear();
					$user_FileRoot = & $FileRootCache->get_by_type_and_ID( 'user', $this->ID );
					if( $user_FileRoot )
					{ // user FilerRooot exists, rename user root folder
						if( ! @rename( $user_root_path, $user_FileRoot->ads_path ) )
						{ // unsuccessful folder rename
							$Messages->add( sprintf( T_('You cannot choose the new login "%s" (cannot rename user fileroot)'), $this->login), 'error' );
							$update_success = false;
						}
					}
				}
				if( $update_success )
				{
					$Messages->add( T_('Profile has been updated.'), 'success' );
				}
			}

			if( $update_success )
			{
				$user_field_url = $this->get_field_url( true );
				if( $user_field_url != '' )
				{ // Update url from extra user fields
					$this->set( 'url', $user_field_url );
					$this->dbsave();
				}

				$DB->commit();
			}
			else
			{
				$DB->rollback();
			}

			if( count( $this->significant_changed_values ) > 0 )
			{ // Send notification email about the changes of user account
				$this->send_account_changed_notification();
			}
		}
		else
		{
			$DB->rollback();
			$update_success = false;
			$Messages->add( 'New user creation error', 'error' );
		}

		// Update user status settings
		if( ( $is_new_user || ( $is_admin_form && $this->ID != 1 ) ) && ( ! $this->update_status_from_Request( true ) ) )
		{
			$Messages->add( T_( 'User status couldn\'t be updated!' ), 'error' );
		}

		$try_to_send_validate_email = ( $is_new_user || $user_was_activated || ( $user_old_email != $this->email ) );
		if( $update_success && $try_to_send_validate_email && $this->check_status( 'can_be_validated' ) )
		{ // user was deactivated somehow ( or it was just created right now ), check if we need to send validation email
			if( ( $Settings->get( 'validation_process' ) != 'easy' ) && ( $current_User->ID != $this->ID ) )
			{ // validation process is set to secure, we may send activation email only if this user is the current_User
				$reg_settings_url = 'href="'.url_add_param( $admin_url, 'ctrl=registration' ).'#fieldset_wrapper_account_activation"';
				$Messages->add( sprintf( T_( 'Because the Account Activation Process is set to <a %s>Secure</a>, the user will have to request a new activation email next time he tries to log in.' ), $reg_settings_url ), 'note' );
			}
			else
			{ // validation process is easy, send email with permanent activation link
				if( $this->send_validate_email( NULL, $blog, ( $user_old_email != $this->email ) ) )
				{
					if( $current_User->ID == $this->ID )
					{
						$redirect_to = NULL;
						if( ( !is_admin_page() ) && ( !empty( $blog ) ) )
						{ // set where to redirect in front office, another way it would go to profile_update.php
							$BlogCache = & get_BlogCache();
							$Blog = $BlogCache->get_by_ID( $blog, false );
							$redirect_to = rawurlencode( $Blog->get( 'userprefsurl' ) );
						}
						$activate_info_link = 'href="'.get_activate_info_url( $redirect_to, '&amp;' ).'"';
						$Messages->add( sprintf( T_('An email has been sent to your email address (%s). Please click on the link therein to activate your account. <a %s>More info &raquo;</a>' ), $this->dget('email'), $activate_info_link ), 'success' );
					}
					else
					{
						$Messages->add( sprintf( T_('An account activation email has been sent to %s' ), $this->login ), 'success' );
					}
				}
				elseif( isset( $demo_mode ) && $demo_mode )
				{
					$Messages->add( 'Could not send activation email. Sending emails is disabled in demo mode.', 'note' );
				}
			}
		}

		// Update user settings:
		$is_preferences_form = param( 'preferences_form', 'boolean', false );
		$is_subscriptions_form = param( 'subscriptions_form', 'boolean', false );
		if( $is_preferences_form || $is_subscriptions_form || $is_admin_form )
		{ // Update UserSettings
			if( $UserSettings->dbupdate() && ( $is_preferences_form || $is_subscriptions_form ) )
			{ // Show feature settings update successful message on user preferences form
				$Messages->add( T_('User feature settings have been changed.'), 'success');
			}
		}

		return true;
	}


	/**
	 * Handle close user account request and update account close reason
	 *
	 * @param boolean save modifications or not. User false only in case when User and UserSettings object will be saved later.
	 * @return boolean true on success, false otherwise
	 */
	function update_status_from_Request( $dbsave, $new_status = NULL )
	{
		global $DB, $UserSettings, $current_User, $servertimenow;

		if( $dbsave )
		{ // save required
			$DB->begin();
		}

		if( empty( $new_status ) )
		{
			$new_status = param( 'edited_user_status', 'string', true );
		}

		// a close reason type thst is selected from list
		$account_close_type = param( 'account_close_type', 'string', '' );
		// a close reason text - max 255 characters
		$account_close_reason = substr( param( 'account_close_reason', 'text', '' ), 0, 255 );
		if( ( !$this->check_status( 'is_closed' ) ) && ( $new_status == 'closed' ) )
		{ // account was not closed yet
			if( empty( $account_close_reason ) && empty( $account_close_type ) )
			{ // Default value of reason
				$account_close_reason = T_( 'Other' );
			}
			$this->set( 'status', 'closed' );
			$UserSettings->set( 'account_close_ts', $servertimenow, $this->ID );
			$UserSettings->set( 'account_close_reason', trim( $account_close_type.' '.$account_close_reason ), $this->ID );
			// delete last activation email data, this user must not be allowed to reactivate the account ( only admin users may change the status again )
			$UserSettings->delete( 'last_activation_reminder_key', $this->ID );
			$UserSettings->delete( 'last_activation_email', $this->ID );
			// create query to clear all session's of the user
			$clear_sessions_query = 'UPDATE T_sessions
								SET sess_key = NULL
								WHERE sess_user_ID = '.$DB->quote( $this->ID );
			if( $dbsave && $this->dbupdate() && $UserSettings->dbupdate() && ( $DB->query( $clear_sessions_query ) !== false ) )
			{ // all db modification was successful
				$DB->commit();
				if( $current_User->ID != $this->ID )
				{	// If admin closed some user account
					// Send notification email about closed account to users with edit users permission
					$email_template_params = array(
							'login'           => $this->login,
							'email'           => $this->email,
							'reason'          => $account_close_reason,
							'user_ID'         => $this->ID,
							'closed_by_admin' => $current_User->login,
							'days_count'      => $this->get_days_count_close()
						);
					send_admin_notification( NT_('User account closed'), 'account_closed', $email_template_params );
				}
				return true;
			}
		}
		else
		{
			$new_status_is_active = ( $new_status == 'activated' || $new_status == 'autoactivated' );
			$old_status_is_not_active = false;
			if( $this->check_status( 'can_be_validated' ) && $new_status_is_active )
			{ // User was activated
				$old_status_is_not_active = true;
				// clear activation specific settings
				$UserSettings->delete( 'last_activation_reminder_key', $this->ID );
				$UserSettings->delete( 'last_activation_email', $this->ID );
				$UserSettings->delete( 'activation_reminder_count', $this->ID );
				$UserSettings->delete( 'send_activation_reminder', $this->ID );
			}
			$old_status_is_not_active = ( $old_status_is_not_active || $this->check_status( 'is_closed' ) );

			// set status
			$this->set( 'status', $new_status );
			$UserSettings->set( 'account_close_reason', $account_close_reason, $this->ID );
			if( $dbsave && $this->dbupdate() )
			{ // db update
				$UserSettings->dbupdate();
				$DB->commit();
				if( $old_status_is_not_active && $new_status_is_active )
				{ // User was activated, create a welcome private message
					$this->send_welcome_message();

					if( $current_User->ID != $this->ID )
					{	// If admin activated some user account
						// Send notification email about activated account to users with edit users permission
						$email_template_params = array(
							'User' => $this,
							'login' => $this->login, // this is required in the send_admin_notification
							'activated_by_admin' => $current_User->login,
						);
						send_admin_notification( NT_('New user account activated'), 'account_activated', $email_template_params );
					}
				}
				return true;
			}
		}

		if( $dbsave )
		{ // save was required, but wasn't successful
			$DB->rollback();
			return false;
		}

		return true;
	}


	/**
	 * Update profileupdate date. Call after a publicly visible user property was updated.
	 */
	function set_profileupdate_date()
	{
		global $current_User, $localtimenow;
		if( ( !empty( $current_User ) ) && ( $this->ID == $current_User->ID ) )
		{
			$this->set( 'profileupdate_date', date( 'Y-m-d', $localtimenow ) );
		}
	}


	/**
	 * Update user avatar file
	 *
	 * @param integer the new avatar file ID
	 * @param boolean TRUE to restore this file
	 * @return mixed true on success, allowed action otherwise
	 */
	function update_avatar( $file_ID, $restore = false )
	{
		global $current_User, $Messages;

		$can_moderate_user = $current_User->can_moderate_user( $this->ID );
		if( ! $can_moderate_user && ( $restore || $this->ID != $current_User->ID ) )
		{ // user is only allowed to update him/herself
			$Messages->add( T_('You are not allowed to update this profile!'), 'error' );
			return 'view';
		}

		if( $file_ID == NULL )
		{
			$Messages->add( T_('Profile picture could not be changed!'), 'error' );
			return 'edit';
		}

		$FileCache = & get_FileCache();
		$File = $FileCache->get_by_ID( $file_ID );

		if( ! $File->get( 'can_be_main_profile' ) && ! $can_moderate_user )
		{ // Deny to restore picture if current user has no perm
			$Messages->add( T_('Profile picture could not be changed!'), 'error' );
			return 'edit';
		}

		if( $File->_FileRoot->type == 'user' && $File->_FileRoot->in_type_ID != $this->ID )
		{ // don't allow to use pictures from other users
			$Messages->add( T_('Profile picture could not be changed!'), 'error' );
			return 'edit';
		}

		$restored_success = false;
		if( $restore && $can_moderate_user )
		{ // Restore profile picture
			$File->set( 'can_be_main_profile', '1' );
			$restored_success = $File->dbupdate();
		}

		$this->set( 'avatar_file_ID', $file_ID, true );
		// update profileupdate_date, because a publicly visible user property was changed
		$this->set_profileupdate_date();
		$this->dbupdate();

		if( $restored_success )
		{
			$Messages->add( T_('Profile picture has been restored.'), 'success' );
		}
		else
		{
			$Messages->add( T_('Profile picture has been changed.'), 'success' );
		}

		// Send notification email about the changes of user account
		$this->send_account_changed_notification( true );

		return true;
	}


	/**
	 * Get the rotate avatar icons
	 *
	 * @param integer File ID
	 * @param array Params
	 * @return string HTML text with 3 icons to rotate avatar
	 */
	function get_rotate_avatar_icons( $file_ID, $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'   => '<br />',
				'after'    => '',
				'text'     => '',
				'user_tab' => 'avatar',
			), $params );

		// Init links to rotate avatar
		if( is_admin_page() )
		{ // Back-office
			$url_rotate_90_left = regenerate_url( '', 'user_tab='.$params['user_tab'].'&user_ID='.$this->ID.'&action=rotate_avatar_90_left&file_ID='.$file_ID.'&'.url_crumb( 'user' ), '', '&' );
			$url_rotate_180 = regenerate_url( '', 'user_tab='.$params['user_tab'].'&user_ID='.$this->ID.'&action=rotate_avatar_180&file_ID='.$file_ID.'&'.url_crumb( 'user' ), '', '&') ;
			$url_rotate_90_right = regenerate_url( '', 'user_tab='.$params['user_tab'].'&user_ID='.$this->ID.'&action=rotate_avatar_90_right&file_ID='.$file_ID.'&'.url_crumb( 'user' ), '', '&' );
		}
		else
		{ // Front-office
			global $Blog;
			$url_rotate_90_left = get_secure_htsrv_url().'profile_update.php?user_tab='.$params['user_tab'].'&blog='.$Blog->ID.'&user_ID='.$this->ID.'&action=rotate_avatar_90_left&file_ID='.$file_ID.'&'.url_crumb( 'user' );
			$url_rotate_180 = get_secure_htsrv_url().'profile_update.php?user_tab='.$params['user_tab'].'&blog='.$Blog->ID.'&user_ID='.$this->ID.'&action=rotate_avatar_180&file_ID='.$file_ID.'&'.url_crumb( 'user' );
			$url_rotate_90_right = get_secure_htsrv_url().'profile_update.php?user_tab='.$params['user_tab'].'&blog='.$Blog->ID.'&user_ID='.$this->ID.'&action=rotate_avatar_90_right&file_ID='.$file_ID.'&'.url_crumb( 'user' );
		}

		$html = $params['before'];

		$html .= action_icon( T_('Rotate this picture 90&deg; to the left'), 'rotate_left', $url_rotate_90_left, '', 0, 0, array( 'style' => 'margin-right:4px' ) );
		$html .= action_icon( T_('Rotate this picture 180&deg;'), 'rotate_180', $url_rotate_180, '', 0, 0, array( 'style' => 'margin-right:4px' ) );
		$html .= action_icon( T_('Rotate this picture 90&deg; to the right'), 'rotate_right', $url_rotate_90_right, $params['text'], empty( $params['text'] ) ? 0: 3, empty( $params['text'] ) ? 0: 4 );

		$html .= $params['after'];

		return $html;
	}


	/**
	 * Get file object by ID of this user
	 *
	 * @param integer Avatar file ID
	 * @param string Error code on denied action, It is updated by reference:
	 *        'only_own_profile' - User can update only own profile
	 *        'wrong_file'       - Request with wrong file ID
	 *        'other_user'       - Restricted to edit files from other users
	 * @return object|boolean File object on success, FALSE on failed
	 */
	function & get_File_by_ID( $file_ID, & $error_code )
	{
		global $current_User, $Messages;

		$error_code = false;

		$can_moderate_user = $current_User->can_moderate_user( $this->ID );
		if( ! $can_moderate_user && $this->ID != $current_User->ID )
		{ // user is only allowed to update him/herself
			$Messages->add( T_('You are only allowed to update your own profile!'), 'error' );
			$error_code = 'only_own_profile';
			$r = false;
			return $r;
		}

		if( empty( $file_ID ) )
		{ // File ID is empty
			$Messages->add( T_('You did not specify a file.'), 'error' );
			$error_code = 'wrong_file';
			$r = false;
			return $r;
		}

		$FileCache = & get_FileCache();
		$File = & $FileCache->get_by_ID( $file_ID, false, false );
		if( empty( $File ) )
		{ // File does't exist
			$Messages->add( T_('The requested file does not exist!'), 'error' );
			$error_code = 'wrong_file';
			$r = false;
			return $r;
		}

		if( $File->_FileRoot->type != 'user' || ( $File->_FileRoot->in_type_ID != $this->ID && ! $can_moderate_user ) )
		{ // don't allow use the pictures from other users
			$Messages->add( T_('The requested file doesn\'t belong to this user.'), 'error' );
			$error_code = 'other_user';
			$r = false;
			return $r;
		}

		return $File;
	}


	/**
	 * Rotate user avatar file
	 *
	 * @param integer the new avatar file ID
	 * @param integer Degrees to rotate
	 * @return object|string File object on success;
	 *         Error code on denied action:
	 *          'only_own_profile' - User can update only own profile
	 *          'wrong_file'       - Request with wrong file ID
	 *          'other_user'       - Restricted to edit files from other users
	 *          'rotate_error'     - Some errors in rotate function
	 */
	function rotate_avatar( $file_ID, $degrees )
	{
		$File = & $this->get_File_by_ID( $file_ID, $error_code );
		if( ! $File )
		{ // The file cannot be used for this user, return error code
			return $error_code;
		}

		global $Messages;
		load_funcs( 'files/model/_image.funcs.php' );

		if( !rotate_image( $File, $degrees ) )
		{ // Some errors were during rotate the avatar
			$Messages->add( T_('Profile picture could not be rotated!'), 'error' );
			return 'rotate_error';
		}

		$Messages->add( T_('Profile picture has been rotated.'), 'success' );
		return true;
	}


	/**
	 * Rotate user avatar file
	 *
	 * @param integer the new avatar file ID
	 * @param integer X coordinate (in percents)
	 * @param integer Y coordinate (in percents)
	 * @param integer Width (in percents)
	 * @param integer Height (in percents)
	 * @return object|string File object on success;
	 *         Error code on denied action:
	 *          'only_own_profile' - User can update only own profile
	 *          'wrong_file'       - Request with wrong file ID
	 *          'other_user'       - Restricted to edit files from other users
	 *          'crop_error'       - Some errors in crop function
	 */
	function crop_avatar( $file_ID, $x, $y, $width, $height )
	{
		$File = & $this->get_File_by_ID( $file_ID, $error_code );
		if( ! $File )
		{ // The file cannot be used for this user, return error code
			return $error_code;
		}

		global $Messages, $Settings;
		load_funcs( 'files/model/_image.funcs.php' );

		if( ! crop_image( $File, $x, $y, $width, $height, intval( $Settings->get( 'min_picture_size' ) ), 1024 ) )
		{ // Some errors were during rotate the avatar
			$Messages->add( T_('Profile picture could not be cropped!'), 'error' );
			return 'crop_error';
		}

		$Messages->add( T_('Profile picture has been cropped.'), 'success' );
		return true;
	}


	/**
	 * Get the crop avatar icon
	 *
	 * @param integer File ID
	 * @param array Params
	 * @return string HTML text with icon to crop avatar
	 */
	function get_crop_avatar_icon( $file_ID, $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'   => '',
				'after'    => '',
				'text'     => '',
				'user_tab' => 'avatar',
				'onclick'  => '',
			), $params );

		// Init links to rotate avatar
		if( is_admin_page() )
		{ // Back-office
			//$url_crop = regenerate_url( 'user_tab', 'user_tab=crop&user_ID='.$this->ID.'&file_ID='.$file_ID.'&'.url_crumb( 'user' ), '', '&' );
			$url_crop = '#';
		}
		else
		{ // Front-office
			global $Blog;
			$url_crop = url_add_param( $Blog->gen_blogurl(), 'disp=avatar&action=crop&file_ID='.$file_ID );
		}

		$html = $params['before'];

		$link_params = array();
		if( ! empty( $params['onclick'] ) )
		{
			$link_params['onclick'] = $params['onclick'];
		}
		$html .= action_icon( T_('Crop this picture'), 'crop', $url_crop, $params['text'], ( empty( $params['text'] ) ? 0: 3 ), ( empty( $params['text'] ) ? 0: 4 ), $link_params );

		$html .= $params['after'];

		return $html;
	}


	/**
	 * Remove user avatar
	 *
	 * @return mixed true on success, false otherwise
	 */
	function remove_avatar( $forbid = false )
	{
		global $current_User, $Messages;

		$can_moderate_user = $current_User->can_moderate_user( $this->ID );
		if( ! $can_moderate_user && ( $forbid || $this->ID != $current_User->ID ) )
		{ // user is only allowed to update him/herself
			$Messages->add( T_('You are not allowed to update this profile!'), 'error' );
			return false;
		}

		$forbidden_success = false;
		if( $forbid && $can_moderate_user )
		{ // Forbid profile picture
			$forbidden_file_ID = $this->get( 'avatar_file_ID' );
			$FileCache = & get_FileCache();
			if( ! ( $forbidden_File = & $FileCache->get_by_ID( $forbidden_file_ID, false, false ) ) )
			{ // Broken avatar file
				$Messages->add( T_('Profile picture is not found!'), 'error' );
				return false;
			}

			$forbidden_File->set( 'can_be_main_profile', '0' );
			$forbidden_success = $forbidden_File->dbupdate();
		}

		$this->set( 'avatar_file_ID', NULL, true );
		$this->dbupdate();

		if( $forbidden_success )
		{
			$Messages->add( T_('Profile picture has been forbidden.'), 'success' );
		}
		else
		{
			$Messages->add( T_('Profile picture has been removed.'), 'success' );
		}

		return true;
	}


	/**
	 * Delete user avatar file
	 *
	 * @param integer the avatar file ID
	 * @return mixed true on success, allowed action otherwise
	 */
	function delete_avatar( $file_ID )
	{
		global $current_User, $Messages;

		if( ! $current_User->can_moderate_user( $this->ID ) && $this->ID != $current_User->ID )
		{ // user is only allowed to update him/herself
			$Messages->add( T_('You are only allowed to update your own profile!'), 'error' );
			return 'view';
		}

		if( $file_ID == NULL )
		{
			$Messages->add( T_('Profile picture could not be changed!'), 'error' );
			return 'edit';
		}

		$LinkOwner = new LinkUser( $this );
		$Link = & $LinkOwner->get_link_by_file_ID( $file_ID );
		if( $Link )
		{
			$File = $Link->get_File();
			$LinkOwner->remove_link( $Link );
		}
		else
		{
			$FileCache = & get_FileCache();
			$File = $FileCache->get_by_ID( $file_ID );
		}

		if( $file_ID == $this->avatar_file_ID )
		{	// Unset this picture from user avatar if it is used as main picture
			$this->set( 'avatar_file_ID', NULL, true );
			$this->dbupdate();
		}

		if( ( $File->_FileRoot->type == 'user' ) && ( $File->_FileRoot->in_type_ID == $this->ID )
			&& ( strpos( $File->get_rdfp_rel_path(), 'profile_pictures' ) === 0 ) )
		{ // Delete the file if it is in the current User profile_pictures folder
			$File->unlink();
		}

		$Messages->add( T_('Profile picture has been deleted.'), 'success' );
		return true;
	}


	/**
	 * Update user avatar file to the currently uploaded file
	 *
	 * @return mixed true on success, allowed action otherwise.
	 */
	function update_avatar_from_upload()
	{
		global $current_User, $Messages, $Settings;

		if( ! $current_User->can_moderate_user( $this->ID ) && $this->ID != $current_User->ID )
		{ // user is only allowed to update him/herself
			$Messages->add( T_('You are only allowed to update your own profile!'), 'error' );
			return 'view';
		}

		// process upload
		$FileRootCache = & get_FileRootCache();
		$root = FileRoot::gen_ID( 'user', $this->ID );
		$result = process_upload( $root, 'profile_pictures', true, false, true, false, $Settings->get( 'min_picture_size' ) );
		if( empty( $result ) )
		{
			$Messages->add( T_( 'You don\'t have permission to selected user file root.' ), 'error' );
			return 'view';
		}

		$uploadedFiles = $result['uploadedFiles'];
		if( !empty( $uploadedFiles ) )
		{ // upload was successful
			$File = $uploadedFiles[0];
			$duplicated_files = $File->get_duplicated_files( array( 'root_ID' => $this->ID ) );
			if( ! empty( $duplicated_files ) )
			{ // The file is the duplicate of other profile picture, we should delete it
				$File->dbdelete();
				$Messages->add( T_( 'It seems you are trying to upload the same profile picture twice.' ), 'error' );
				return 'edit';
			}
			elseif( $File->is_image() )
			{ // uploaded file is an image
				$LinkOwner = new LinkUser( $this );
				$File->link_to_Object( $LinkOwner );
				$avatar_changed = false;
				if( empty( $this->avatar_file_ID ) )
				{ // set uploaded image as avatar
					$this->set( 'avatar_file_ID', $File->ID, true );
					// update profileupdate_date, because a publicly visible user property was changed
					$this->set_profileupdate_date();
					$this->dbupdate();
					$avatar_changed = true;
					$Messages->add( T_('Profile picture has been changed.'), 'success' );
				}
				else
				{ // User already has the avatar
					$Messages->add( T_('New picture has been uploaded.'), 'success' );
				}
				// Clear previous Links to load new uploaded file
				$LinkOwner->clear_Links();
				// Send notification email about the changes of user account
				$this->send_account_changed_notification( $avatar_changed, $File->ID );
				return true;
			}
			else
			{ // uploaded file is not an image, delete the file
				$Messages->add( T_( 'The file you uploaded does not seem to be an image.' ) );
				$File->unlink();
			}
		}

		$failedFiles = $result['failedFiles'];
		if( !empty( $failedFiles ) )
		{
			$Messages->add( $failedFiles[0] );
		}

		return 'edit';
	}


	/**
	 * Get session param from the user last session
	 *
	 * @param string param name
	 * @return mixed param value
	 */
	function get_last_session_param( $parname )
	{
		global $DB;

		$parname = 'sess_'.$parname;
		$query = 'SELECT sess_ID, '.$parname.'
					FROM T_sessions
					WHERE sess_user_ID = '.$this->ID.'
					ORDER BY sess_ID DESC
					LIMIT 1';
		$result = $DB->get_row( $query );
		if( !empty( $result ) )
		{
			return format_to_output( $result->$parname );
		}

		return NULL;
	}

	/**
	 * Get session param from the user first session, hit
	 * @param integer Session Id
	 * @return object of params
	 */
	function get_first_session_hit_params( $sess_id )
	{
		global $DB;

		$query = 'SELECT *
					FROM T_hitlog
					WHERE hit_sess_ID = '.$DB->quote( $sess_id ).'
					ORDER BY hit_ID ASC
					LIMIT 1';

		$result = $DB->get_row( $query );

		if( !empty( $result ) )
		{
			return $result;
		}

		return NULL;
	}

	/**
	 * Send a welcome private message
	 */
	function send_welcome_message()
	{
		global $Settings, $UserSettings;

		if( !$Settings->get( 'welcomepm_enabled' ) )
		{ // Sending of welcome PM is disabled
			return false;
		}

		if( $UserSettings->get( 'welcome_message_sent', $this->ID ) )
		{ // User already received the welcome message
			return false;
		}

		if( !$this->accepts_pm() )
		{ // user can't read private messages, or doesn't want to receive private messages
			return false;
		}

		// Check sender user login for existing
		$UserCache = & get_UserCache();
		$User = $UserCache->get_by_login( $Settings->get( 'welcomepm_from' ) );
		if( !$User )
		{ // Don't send an welcome email if sender login is incorrect
			return false;
		}

		load_class( 'messaging/model/_thread.class.php', 'Thread' );
		load_class( 'messaging/model/_message.class.php', 'Message' );

		// Insert new thread:
		$edited_Thread = new Thread();
		$edited_Message = new Message();
		$edited_Message->Thread = & $edited_Thread;
		$edited_Message->Thread->set( 'title', $Settings->get( 'welcomepm_title' ) );
		$edited_Message->Thread->recipients_list = array( $this->ID );
		$edited_Message->set( 'author_user_ID', $User->ID );
		$edited_Message->creator_user_ID = $User->ID;
		$edited_Message->set( 'text', $Settings->get( 'welcomepm_message' ) );
		if( $edited_Message->dbinsert_individual( $User ) )
		{ // The welcome message was sent/created successfully
			// Change user setting to TRUE in order to don't send it twice
			$UserSettings->set( 'welcome_message_sent', 1, $this->ID );
			$UserSettings->dbupdate();
		}
	}


	/**
	 * Update notification sender's info from General settings
	 *
	 * @param boolean TRUE - to force an updating of the fields
	 */
	function update_sender( $force_update = false )
	{
		global $Settings, $UserSettings;

		if( $force_update || $UserSettings->get( 'notification_sender_email', $this->ID ) == '' )
		{ // Update sender's email
			$UserSettings->set( 'notification_sender_email', $Settings->get( 'notification_sender_email' ), $this->ID );
		}
		if( $force_update || ( ( $UserSettings->get( 'notification_sender_name', $this->ID ) == '' ) && ( $Settings->get( 'notification_sender_name' ) != 'b2evo mailer' ) ) )
		{ // Update sender's name if it was not set and the general settings has different value then the default
			$UserSettings->set( 'notification_sender_name', $Settings->get( 'notification_sender_name' ), $this->ID );
		}

		$UserSettings->dbupdate();
	}


	/**
	 * Get all own blogs of this user which current user can delete
	 *
	 * @return array Blogs
	 */
	function get_deleted_blogs()
	{
		global $DB, $current_User;

		// Get all own blogs of the edited user
		$BlogCache = & get_BlogCache();
		$BlogCache->ID_array = array();
		$user_Blogs = $BlogCache->load_where( 'blog_owner_user_ID = '.$DB->quote( $this->ID ) );

		$deleted_Blogs = array();
		foreach( $user_Blogs as $user_Blog )
		{
			if( $current_User->check_perm( 'blog_properties', 'edit', false, $user_Blog->ID ) )
			{	// Current user has a permission to delete this blog
				$deleted_Blogs[] = $user_Blog;
			}
		}

		return $deleted_Blogs;
	}


	/**
	 * Delete all blogs of the user recursively
	 *
	 * @return boolean True on success
	 */
	function delete_blogs()
	{
		global $DB, $UserSettings, $current_User;

		$DB->begin();

		// Get all own blogs of this user which current user can delete
		$deleted_Blogs = $this->get_deleted_blogs();

		foreach( $deleted_Blogs as $deleted_Blog )
		{
			// Delete from DB:
			$deleted_Blog->dbdelete();

			set_working_blog( 0 );
			$UserSettings->delete( 'selected_blog' );	// Needed or subsequent pages may try to access the delete blog
			$UserSettings->dbupdate();
		}

		$DB->commit();

		return true;
	}


	/**
	 * Get the posts of this user which current user can delete
	 *
	 * @param string Type of the deleted posts
	 *               'created'        - the posts created by this user
	 *               'edited'         - the posts edited by this user
	 *               'created|edited' - the posts created OR edited by this user
	 * @return array Items
	 */
	function get_deleted_posts( $type, $check_perm = true )
	{
		global $DB, $current_User;

		$ItemCache = & get_ItemCache();
		$ItemCache->ID_array = array();
		switch( $type )
		{
			case 'created':
				$user_Items = $ItemCache->load_where( 'post_creator_user_ID = '.$DB->quote( $this->ID ) );
				break;

			case 'edited':
				$user_Items = $ItemCache->load_where( 'post_lastedit_user_ID = '.$DB->quote( $this->ID ) );
				break;

			case 'created|edited':
				$user_Items = $ItemCache->load_where( 'post_lastedit_user_ID = '.$DB->quote( $this->ID ).' OR post_creator_user_ID = '.$DB->quote( $this->ID ) );
				break;
		}

		$deleted_Items = array();
		foreach( $user_Items as $user_Item )
		{
			if( ( ! $check_perm ) || $current_User->check_perm( 'item_post!CURSTATUS', 'delete', false, $user_Item ) )
			{	// Current user has a permission to delete this item
				$deleted_Items[] = $user_Item;
			}
		}

		return $deleted_Items;
	}


	/**
	 * Delete posts of the user
	 *
	 * @param string Type of the deleted posts
	 *               'created'        - the posts created by this user
	 *               'edited'         - the posts edited by this user
	 *               'created|edited' - the posts created OR edited by this user
	 * @return boolean True on success
	 */
	function delete_posts( $type )
	{
		global $DB, $Plugins, $current_User;

		$DB->begin();

		// Get the posts of this user which current user can delete.
		// Note: If current user can moderate this user then it is allowed to delete all user data even if it wouldn't be allowed otherwise.
		$check_perm = ( ( $type != 'created' ) || ( ! $current_User->can_moderate_user( $this->ID ) ) );
		$deleted_Items = $this->get_deleted_posts( $type, $check_perm );

		foreach( $deleted_Items as $deleted_Item )
		{
			$Plugins->trigger_event( 'AdminBeforeItemEditDelete', array( 'Item' => & $deleted_Item ) );

			// Delete from DB:
			$deleted_Item->dbdelete();
		}

		$DB->commit();

		return true;
	}


	/**
	 * Get the comments IDs that were created by this user
	 *
	 * @return array IDs of the own comments
	 */
	function get_own_comments_IDs()
	{
		global $DB;

		// Get the comments of the user
		$SQL = new SQL();
		$SQL->SELECT( 'comment_ID' );
		$SQL->FROM( 'T_comments' );
		$SQL->WHERE( 'comment_author_user_ID = '.$DB->quote( $this->ID ) );

		return $DB->get_col( $SQL->get() );
	}


	/**
	 * Check if current has at least one own comment to delete
	 *
	 * @return array Comments IDs
	 */
	function has_comment_to_delete()
	{
		global $current_User;

		$comments_IDs = $this->get_own_comments_IDs();

		if( ! count( $comments_IDs ) )
		{ // User has no comments
			return false;
		}

		if( $current_User->can_moderate_user( $this->ID ) )
		{ // If current user can moderate this user then it is allowed to delete all user data even if it wouldn't be allowed otherwise.
			return true;
		}

		$CommentCache = & get_CommentCache();

		foreach( $comments_IDs as $comment_ID )
		{
			if( ( $user_Comment = & $CommentCache->get_by_ID( $comment_ID, false, false ) ) &&
				  $current_User->check_perm( 'comment!CURSTATUS', 'delete', false, $user_Comment ) )
			{ // Current user has a permission to delete this comment
				return true;
			}
			// Clear a cache to avoid a memory allocation error
			$CommentCache->clear();
		}

		return false;
	}


	/**
	 * Delete comments of the user
	 *
	 * @return boolean True on success
	 */
	function delete_comments()
	{
		global $DB, $current_User;

		// If user has a huge amount of the comments it will takes many time to delete all comments
		set_max_execution_time( 900 );

		$DB->begin();

		// Get the comments of this user which current user can delete
		$comments_IDs = $this->get_own_comments_IDs();

		if( ! count( $comments_IDs ) )
		{ // User has no comments
			return false;
		}

		$CommentCache = & get_CommentCache();
		$ItemCache = & get_ItemCache();

		// If current user can moderate this user then it is allowed to delete all user data even if it wouldn't be allowed otherwise.
		$current_user_can_moderate = $current_User->can_moderate_user( $this->ID );

		$result = false;
		foreach( $comments_IDs as $comment_ID )
		{
			$deleted_Comment = & $CommentCache->get_by_ID( $comment_ID, false, false );
			if( $deleted_Comment &&
			    ( $current_user_can_moderate ||
			      $current_User->check_perm( 'comment!CURSTATUS', 'delete', false, $deleted_Comment ) ) )
			{ // Current user has a permission to delete this comment
				// Delete from DB
				$result = $deleted_Comment->dbdelete( true, false );
				if( ! $result )
				{
					break;
				}
			}
			// Clear a cache to avoid a memory allocation error
			$CommentCache->clear();
			$ItemCache->clear();
		}

		if( $result )
		{
			$DB->commit();
		}
		else
		{
			$DB->rollback();
		}

		return $result;
	}


	/**
	 * Delete private messaged of the user
	 *
	 * @return boolean True on success
	 */
	function delete_messages()
	{
		global $DB, $Plugins, $current_User;

		// Check permissions
		// Note: If current user can moderate this user then it is allowed to delete all user data even if it wouldn't be allowed otherwise
		if( ! $current_User->can_moderate_user( $this->ID ) )
		{ // Note: if users have delete messaging perms then they can delete any user messages ( Of course only if the delete action is available/displayed for them )
			$current_User->check_perm( 'perm_messaging', 'delete', true );
		}

		$DB->begin();

		$MessageCache = & get_MessageCache();
		$MessageCache->clear();
		$MessageCache->load_where( 'msg_author_user_ID = '.$this->ID );
		$message_was_deleted = false;

		while( ( $iterator_Message = & $MessageCache->get_next() ) != NULL )
		{ // Iterate through MessageCache
			// Delete a message from DB:
			$iterator_Message->dbdelete();
			$message_was_deleted = true;
		}

		if( $message_was_deleted )
		{ // at least one message was deleted
			// Delete statuses
			$DB->query( 'DELETE FROM T_messaging__threadstatus
							WHERE tsta_user_ID = '.$DB->quote( $this->ID ) );
		}

		$DB->commit();

		return true;
	}


	/**
	 * Get number of posts and percent of published posts by this user
	 *
	 * @param array Params
	 * @return string Result
	 */
	function get_reputation_posts( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'text' => T_( '%s (%s%% are public)' ),
			), $params );

		$total_num_posts = $this->get_num_posts();
		$public_num_posts = $this->get_num_posts( 'published' );
		if( $total_num_posts > 0 )
		{ // Calc percent of published posts
			$public_percent = floor( $public_num_posts / $total_num_posts * 100 );
		}
		else
		{ // To avoid devision by zero
			$public_percent = 0;
		}

		if( $total_num_posts > 0 )
		{ // Make a link to page with user's posts
			$total_num_posts = '<a href="'.get_dispctrl_url( 'useritems', 'user_ID='.$this->ID ).'"><b>'.$total_num_posts.'</b></a>';
		}
		else
		{
			$total_num_posts = '<b>'.$total_num_posts.'</b>';
		}

		return sprintf( $params['text'], $total_num_posts, $public_percent );
	}


	/**
	 * Get number of comments and percent of published comments and number of helpful votes on comments by this user
	 *
	 * @param array Params
	 * @return string Result
	 */
	function get_reputation_comments( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'view_type'     => 'simple', // 'simple', 'extended'
				'text_simple'   => T_( '%s has posted %s comments (%s%% of which are public). %s of these comments have been found useful by %s different users.' ),
				'text_extended' => T_( '%s has posted %s comments (%s%% of which are public).<br />%s voted useful by %s different users.<br />%s voted NOT useful by %s different users.<br />%s considered OK by %s different users.<br />%s considered SPAM by %s different users.' ),
			), $params );

		$total_num_comments = $this->get_num_comments();
		$public_num_comments = $this->get_num_comments( 'published' );
		if( $total_num_comments > 0 )
		{ // Calc percent of published comments
			$public_percent = floor( $public_num_comments / $total_num_comments * 100 );
		}
		else
		{ // To avoid devision by zero
			$public_percent = 0;
		}

		if( $total_num_comments > 0 )
		{ // Make a link to page with user's comments
			$total_num_comments = '<a href="'.get_dispctrl_url( 'usercomments', 'user_ID='.$this->ID ).'"><b>'.$total_num_comments.'</b></a>';
		}
		else
		{
			$total_num_comments = '<b>'.$total_num_comments.'</b>';
		}

		// Get number of helpful votes on comments for this user
		global $DB;
		$comments_SQL = new SQL();
		if( $params['view_type'] == 'simple' )
		{ // Simple view
			$comments_SQL->SELECT( 'cmvt_user_ID AS user_ID, COUNT(*) AS cnt' );
		}
		else
		{ // Extended view
			$comments_SQL->SELECT( 'cmvt_user_ID AS user_ID, cmvt_helpful, cmvt_spam' );
		}
		$comments_SQL->FROM( 'T_comments' );
		$comments_SQL->FROM_add( 'INNER JOIN T_comments__votes ON comment_ID = cmvt_cmt_ID' );
		$comments_SQL->WHERE( 'comment_author_user_ID = '.$this->ID );
		$comments_SQL->WHERE_and( 'comment_status IN ( "published", "community", "protected", "review" )' );
		if( $params['view_type'] == 'simple' )
		{ // Simple view
			$comments_SQL->WHERE_and( 'cmvt_helpful = 1' );
			$comments_SQL->GROUP_BY( 'user_ID' );
		}

		if( $params['view_type'] == 'simple' )
		{ // Simple view
			$votes = $DB->get_assoc( $comments_SQL->get() );

			// Calculate total votes from all users
			$users_count_useful = count( $votes );
			$votes_count_useful = 0;
			foreach( $votes as $user_votes )
			{
				$votes_count_useful += $user_votes;
			}

			return sprintf( $params['text_simple'],
				$this->login, $total_num_comments, $public_percent,
				'<b class="green">'.$votes_count_useful.'</b>', '<b>'.$users_count_useful.'</b>' );
		}
		else
		{ // Extended view
			$votes = $DB->get_results( $comments_SQL->get() );

			$votes_count_useful = 0;
			$users_count_useful = array();
			$votes_count_not = 0;
			$users_count_not = array();
			$votes_count_ok = 0;
			$users_count_ok = array();
			$votes_count_spam = 0;
			$users_count_spam = array();
			foreach( $votes as $vote )
			{
				if( $vote->cmvt_helpful === '1' )
				{ // Useful votes
					$votes_count_useful++;
					$users_count_useful[ $vote->user_ID ] = NULL;
				}
				elseif( $vote->cmvt_helpful === '-1' )
				{ // Not useful votes
					$votes_count_not++;
					$users_count_not[ $vote->user_ID ] = NULL;
				}
				if( $vote->cmvt_spam === '-1' )
				{ // Ok votes
					$votes_count_ok++;
					$users_count_ok[ $vote->user_ID ] = NULL;
				}
				elseif( $vote->cmvt_spam === '1' )
				{ // Spam votes
					$votes_count_spam++;
					$users_count_spam[ $vote->user_ID ] = NULL;
				}
			}

			return sprintf( $params['text_extended'],
				$this->login, $total_num_comments, $public_percent,
				'<b class="green">'.$votes_count_useful.'</b>', '<b>'.count( $users_count_useful ).'</b>',
				'<b class="red">'.$votes_count_not.'</b>', '<b>'.count( $users_count_not ).'</b>',
				'<b class="green">'.$votes_count_ok.'</b>', '<b>'.count( $users_count_ok ).'</b>',
				'<b class="red">'.$votes_count_spam.'</b>', '<b>'.count( $users_count_spam ).'</b>' );
		}
	}


	/**
	 * Get number of files (and number of helpful votes on photos only) by this user
	 *
	 * @param array Params
	 * @return string Result
	 */
	function get_reputation_files( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'view_type'           => 'simple', // 'simple', 'extended'
				'file_type'           => 'image', // 'image', 'audio', 'other'
				'text_image_simple'   => T_( '%s has posted %s photos. %s of these photos have been liked by %s different users.' ),
				'text_image_extended' => T_( '%s has posted %s photos.<br />%s voted up (liked) by %s different users.<br />%s voted down by %s different users.<br />%s considered INAPPROPRIATE by %s different users.<br />%s considered SPAM by %s different users.' ),
				'text_audio'          => T_( '%s has uploaded %s audio files.' ),
				'text_other'          => T_( '%s has uploaded %s other files.' ),
			), $params );

		switch( $params['file_type'] )
		{
			case 'image':
				// Number of photos
				global $DB;
				// Get number of helpful votes on links for this user
				$links_SQL = new SQL();
				if( $params['view_type'] == 'simple' )
				{ // Simple view
					$links_SQL->SELECT( 'lvot_user_ID AS user_ID, COUNT(*) AS cnt' );
				}
				else
				{ // Extended view
					$links_SQL->SELECT( 'lvot_user_ID AS user_ID, lvot_like, lvot_inappropriate, lvot_spam' );
				}
				$links_SQL->FROM( 'T_links' );
				$links_SQL->FROM_add( 'INNER JOIN T_files ON file_ID = link_file_ID' );
				$links_SQL->FROM_add( 'INNER JOIN T_links__vote ON link_ID = lvot_link_ID' );
				$links_SQL->WHERE( 'link_creator_user_ID = '.$this->ID );
				$links_SQL->WHERE_and( 'file_type = '.$DB->quote( $params['file_type'] ) );
				if( $params['view_type'] == 'simple' )
				{ // Simple view
					$links_SQL->WHERE_and( 'lvot_like = 1' );
					$links_SQL->GROUP_BY( 'user_ID' );
				}


				if( $params['view_type'] == 'simple' )
				{ // Simple view
					$votes = $DB->get_assoc( $links_SQL->get() );

					// Calculate total votes from all users
					$users_count_up = count( $votes );
					$votes_count_up = 0;
					foreach( $votes as $user_votes )
					{
						$votes_count_up += $user_votes;
					}
					return sprintf( $params['text_image_simple'],
						$this->login, '<b>'.$this->get_num_files( 'image' ).'</b>',
						'<b class="green">'.$votes_count_up.'</b>', '<b>'.$users_count_up.'</b>' );
				}
				else
				{ // Extended view
					$votes = $DB->get_results( $links_SQL->get() );

					$votes_count_up = 0;
					$users_count_up = array();
					$votes_count_down = 0;
					$users_count_down = array();
					$votes_count_inappropriate = 0;
					$users_count_inappropriate = array();
					$votes_count_spam = 0;
					$users_count_spam = array();
					foreach( $votes as $vote )
					{
						if( $vote->lvot_like === '1' )
						{ // Up votes
							$votes_count_up++;
							$users_count_up[ $vote->user_ID ] = NULL;
						}
						elseif( $vote->lvot_like === '-1' )
						{ // Down votes
							$votes_count_down++;
							$users_count_down[ $vote->user_ID ] = NULL;
						}
						if( $vote->lvot_inappropriate === '1' )
						{ // Inappropriate votes
							$votes_count_inappropriate++;
							$users_count_inappropriate[ $vote->user_ID ] = NULL;
						}
						if( $vote->lvot_spam === '1' )
						{ // Spam votes
							$votes_count_spam++;
							$users_count_spam[ $vote->user_ID ] = NULL;
						}
					}

					return sprintf( $params['text_image_extended'],
						$this->login, '<b>'.$this->get_num_files( 'image' ).'</b>',
						'<b class="green">'.$votes_count_up.'</b>', '<b>'.count( $users_count_up ).'</b>',
						'<b class="red">'.$votes_count_down.'</b>', '<b>'.count( $users_count_down ).'</b>',
						'<b class="red">'.$votes_count_inappropriate.'</b>', '<b>'.count( $users_count_inappropriate ).'</b>',
						'<b class="red">'.$votes_count_spam.'</b>', '<b>'.count( $users_count_spam ).'</b>' );
				}
				break;

			case 'audio':
				// Number of audio files
				return sprintf( $params['text_audio'], $this->login, '<b>'.$this->get_num_files( 'audio' ).'</b>' );

			case 'other':
				// Number of other files
				return sprintf( $params['text_other'], $this->login, '<b>'.$this->get_num_files( 'other' ).'</b>' );
		}
	}


	/**
	 * Get number of spam votes which were made by this user
	 *
	 * @param array Params
	 * @return string Result
	 */
	function get_reputation_spam( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'text' => T_( '%s successfully reported %s spams and/or spammers!' ),
			), $params );

		global $DB, $UserSettings;

		$comments_SQL = new SQL( 'Get number of spam votes on comments by this user' );
		$comments_SQL->SELECT( 'COUNT(*) AS cnt' );
		$comments_SQL->FROM( 'T_comments__votes' );
		$comments_SQL->WHERE( 'cmvt_user_ID = '.$this->ID );
		$comments_SQL->WHERE_and( 'cmvt_spam = 1' );

		$links_SQL = new SQL( 'Get number of spam votes on links by this user' );
		$links_SQL->SELECT( 'COUNT(*) AS cnt' );
		$links_SQL->FROM( 'T_links__vote' );
		$links_SQL->WHERE( 'lvot_user_ID = '.$this->ID );
		$links_SQL->WHERE_and( 'lvot_spam = 1' );

		$votes = $DB->get_var( 'SELECT SUM( cnt )
			FROM ('.$comments_SQL->get().' UNION ALL '.$links_SQL->get().') AS tbl' );

		// Get spam fighter score for the users that were reported and deleted
		$votes += intval( $UserSettings->get( 'spam_fighter_score', $this->ID ) );

		return sprintf( $params['text'], $this->login, '<b>'.$votes.'</b>' );
	}


	/**
	 * Get status of user email
	 *
	 * @return string Status: 'unknown', 'warning', 'suspicious1', 'suspicious2', 'suspicious3', 'prmerror', 'spammer', 'redemption'
	 */
	function get_email_status()
	{
		$user_email = $this->get( 'email' );

		$EmailAddressCache = & get_EmailAddressCache();
		if( !empty( $user_email ) && $EmailAddress = & $EmailAddressCache->get_by_name( $user_email, false, false ) )
		{ // The email of this user is located in the DB table
			return $EmailAddress->get( 'status' );
		}
		else
		{ // There is no email address in the DB table
			return 'unknown';
		}
	}


	/**
	 * Check if user has a permission to moderate the user
	 *
	 * @param integer User ID
	 * @return boolean TRUE on success
	 */
	function can_moderate_user( $user_ID, $assert = false )
	{
		if( $this->check_perm( 'users', 'edit' ) )
		{ // User can edit all users
			return true;
		}

		if( $this->check_perm( 'users', 'moderate', $assert ) )
		{ // User can moderate other user but we should to compare levels of users groups
			$UserCache = & get_UserCache();
			if( $target_User = $UserCache->get_by_ID( $user_ID, false, false ) )
			{
				if( $target_User->get_Group()->get( 'level' ) < $this->get_Group()->get( 'level' ) )
				{ // User can moderate only users with level lower than own level
					return true;
				}
			}
		}

		if( $assert )
		{ // We can't let this go on!
			debug_die( sprintf( T_('User #%s has no permission to edit user #%s!'), $this->ID, $user_ID ) );
		}

		return false;
	}


	/**
	 * Send an email notification when user account has been changed
	 *
	 * @param boolean true if the main profile picture was changed, false otherwise
	 * @param mixed false if there was no new profile picture upload, the newly uploaded file ID otherwise
	 */
	function send_account_changed_notification( $avatar_changed = false, $new_avatar_upload = false )
	{
		if( ! is_logged_in() )
		{ // User must be logged in for this action
			return;
		}

		if( empty( $this->significant_changed_values ) && ( ! $avatar_changed ) && ( ! $new_avatar_upload ) )
		{ // Nothing important was changed, so no need to send changed notification
			return;
		}

		$email_template_params = array(
				'user_ID' => $this->ID,
				'login'   => $this->login,
				'fields'  => array(
					'login'     => array( 'title' => NT_('Login') ),
					'grp_ID'  => array( 'title' => NT_('Group'), 'className' => 'Group' ),
					'nickname'  => array( 'title' => NT_('Nickname') ),
					'firstname' => array( 'title' => NT_('First name') ),
					'lastname'  => array( 'title' => NT_('Last name') ),
					'gender'    => array( 'title' => NT_('Gender') ),
					'ctry_ID'   => array( 'title' => NT_('Country'), 'className' => 'Country' ),
					'rgn_ID'    => array( 'title' => NT_('Region'), 'className' => 'Region' ),
					'subrg_ID'  => array( 'title' => NT_('Sub-region'), 'className' => 'Subregion' ),
					'city_ID'   => array( 'title' => NT_('City'), 'className' => 'City' ),
				),
				'avatar_changed'    => $avatar_changed,
				'new_avatar_upload' => $new_avatar_upload,
			);

		foreach( $email_template_params['fields'] as $user_field_name => $user_field_data )
		{ // Set new and previous values for email template
			$field_was_changed = ( isset( $this->significant_changed_values[ $user_field_name ] ) && ( ! empty( $this->significant_changed_values[ $user_field_name ] ) ) );
			if( isset( $user_field_data['className'] ) )
			{ // The field value is an object ID, get the object name
				$Cache = & call_user_func( 'get_'.$user_field_data['className'].'Cache' );
				$Object = & $Cache->get_by_ID( $this->get( $user_field_name ), false, false );
				$user_field_data['new'] = empty( $Object ) ? NULL : $Object->get_name();
				if( $field_was_changed )
				{ // Get the old display value of the field
					$Object = & $Cache->get_by_ID( $this->significant_changed_values[ $user_field_name ] );
					$user_field_data['old'] = $Object->get_name();
				}
			}
			elseif( $user_field_name == 'gender' )
			{ // This is the gender field, get the display name of the gender
				$user_field_data['new'] = $this->get_gender();
				if( $field_was_changed )
				{
					$user_field_data['old'] = ( $this->significant_changed_values[ $user_field_name ] == 'M' ) ? T_('A man') : T_('A woman');
				}
			}
			else
			{ // Get the field value
				$user_field_data['new'] = $this->get( $user_field_name );
				if( $field_was_changed )
				{ // The field was changed
					$user_field_data['old'] = $this->significant_changed_values[ $user_field_name ];
				}
			}

			if( !isset( $user_field_data['old'] ) )
			{ // The field was not changed or the old value was empty
				$user_field_data['old'] = array_key_exists( $user_field_name, $this->significant_changed_values ) ? NULL : $user_field_data['new'];
			}

			$email_template_params['fields'][ $user_field_name ] = $user_field_data;
		}

		send_admin_notification( NT_('User profile changed'), 'account_changed', $email_template_params );
		// Clear changed values
		$this->significant_changed_values = array();
	}


	/**
	 * Get organizations that user has selected
	 *
	 * @return array Organizations( key => org_ID, value => array( 'name', 'accepted', 'role' )
	 */
	function get_organizations_data()
	{
		if( ! isset( $this->organizations ) )
		{ // Get the organizations from DB
			global $DB;
			$SQL = new SQL();
			$SQL->SELECT( 'org_ID, org_name, uorg_accepted, uorg_role' );
			$SQL->FROM( 'T_users__user_org' );
			$SQL->FROM_add( 'INNER JOIN T_users__organization ON org_ID = uorg_org_ID' );
			$SQL->WHERE( 'uorg_user_ID = '.$DB->quote( $this->ID ) );
			$SQL->ORDER_BY( 'uorg_accepted DESC, uorg_org_ID' );
			$organizations = $DB->get_results( $SQL->get() );
			$this->organizations = array();
			foreach( $organizations as $organization )
			{
				$this->organizations[ $organization->org_ID ] = array(
						'name'     => $organization->org_name,
						'accepted' => $organization->uorg_accepted,
						'role'     => $organization->uorg_role,
					);
			}
		}

		return $this->organizations;
	}


	/**
	 * Get organizations that are accepted for this user
	 *
	 * @return array Organizations: array( 'name', 'url' )
	 */
	function get_organizations()
	{
		global $DB;

		$SQL = new SQL();
		$SQL->SELECT( 'org_name AS name, org_url AS url' );
		$SQL->FROM( 'T_users__user_org' );
		$SQL->FROM_add( 'INNER JOIN T_users__organization ON org_ID = uorg_org_ID' );
		$SQL->WHERE( 'uorg_user_ID = '.$DB->quote( $this->ID ) );
		// Organization must be accepted by Admin
		$SQL->WHERE_and( 'uorg_accepted = 1' );
		$SQL->ORDER_BY( 'org_name' );

		return $DB->get_results( $SQL->get() );
	}


	/**
	 * Update user's organizations in DB
	 *
	 * @param array Organization IDs
	 * @param array Organization roles
	 * @param boolean TRUE to auto accept user to organization (Used on install demo users)
	 */
	function update_organizations( $organization_IDs, $organization_roles = array(), $force_accept = false )
	{
		global $DB, $current_User;

		$perm_edit_users = ( is_logged_in() && $current_User->check_perm( 'users', 'edit' ) );

		$curr_orgs = $this->get_organizations_data();
		$curr_org_IDs = array_keys( $curr_orgs );
		$insert_orgs = array();
		$n = 0;
		foreach( $organization_IDs as $o => $organization_ID )
		{
			$organization_ID = intval( $organization_ID );
			if( empty( $organization_ID ) )
			{ // Organization is not selected, Skip it
				continue;
			}
			if( in_array( $organization_ID, $curr_org_IDs ) )
			{ // User is already in this organization
				if( $perm_edit_users || ! $curr_orgs[ $organization_ID ]['accepted'] )
				{ // Update if current user has permission or it is not accepted yet by admin
					$insert_orgs[ $organization_ID ] = ( empty( $organization_roles[ $n ] ) ? NULL : $organization_roles[ $n ] );
					$n++;
				}
				else
				{ // Don't updated by current user
					$insert_orgs[ $organization_ID ] = $curr_orgs[ $organization_ID ]['role'];
				}
			}
			else
			{ // Insert user in new organization
				$insert_orgs[ $organization_ID ] = ( empty( $organization_roles[ $n ] ) ? NULL : $organization_roles[ $n ] );
				$n++;
			}
		}

		if( count( $insert_orgs ) > 0 )
		{ // Insert new records with user-org relations
			$insert_org_SQL = 'REPLACE INTO T_users__user_org ( uorg_user_ID, uorg_org_ID, uorg_accepted, uorg_role ) VALUES ';
			$o = 0;
			foreach( $insert_orgs as $insert_org_ID => $insert_org_role )
			{
				if( isset( $curr_orgs[ $insert_org_ID ] ) )
				{ // If we are updating - Don't change the accept status
					$insert_orgs_accepted = $curr_orgs[ $insert_org_ID ]['accepted'];
				}
				else
				{ // If we are inserting - Set the accept status depends on user perms or func params
					$insert_orgs_accepted = '0';
					if( $force_accept || $perm_edit_users )
					{ // If admin adds new organization for other users and for himself, it must be autoaccepted
						$insert_orgs_accepted = '1';
					}
				}
				if( $o > 0 )
				{ // separator
					$insert_org_SQL .= ', ';
				}
				$insert_org_SQL .= '( '.$this->ID.', '.$DB->quote( $insert_org_ID ).', '.$insert_orgs_accepted.', '.$DB->quote( $insert_org_role ).' )';
				$o++;
			}
			$DB->query( $insert_org_SQL );
		}

		$delete_org_IDs = array_diff( $curr_org_IDs, array_keys( $insert_orgs ) );
		if( count( $delete_org_IDs ) > 0 )
		{ // Delete old records that were replaced with new
			$DB->query( 'DELETE FROM T_users__user_org
				WHERE uorg_user_ID = '.$this->ID.'
					AND uorg_org_ID IN ( '.implode( ', ', $delete_org_IDs ).' )' );
		}
	}


	/**
	 * Get user name depending on general setting 'username_display'
	 *
	 * @param string Output format, see {@link format_to_output()}
	 * @return string Login or preferred name
	 */
	function get_username( $format = NULL )
	{
		global $Settings;

		if( $Settings->get( 'username_display' ) == 'name' )
		{ // Get nickname or fullname
			$username = $this->get_preferred_name();
		}
		else
		{ // Get login
			$username = $this->get( 'login' );
		}

		if( ! is_null( $format ) )
		{ // Format output string
			$username = format_to_output( $username, $format );
		}

		return $username;
	}


	/**
	 * Calculate how much days user has after registration
	 *
	 * @return float
	 */
	function get_days_count_close()
	{
		$date_start = strtotime( $this->datecreated );
		$date_end = time();
		$days = ( $date_end - $date_start ) / 86400;

		return number_format( $days, 1, '.', '' );
	}


	/**
	 * Get count of own blogs of the user
	 *
	 * @return integer
	 */
	function get_own_blogs_count()
	{
		global $DB;

		$SQL = new SQL();
		$SQL->SELECT( 'COUNT( blog_ID )' );
		$SQL->FROM( 'T_blogs' );
		$SQL->WHERE( 'blog_owner_user_ID = '.$DB->quote( $this->ID ) );

		return $DB->get_var( $SQL->get() );
	}
}

?>