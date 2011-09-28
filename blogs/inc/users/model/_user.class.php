<?php
/**
 * This file implements the User class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
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
	var $postcode;
	var $age_min;
	var $age_max;
	var $login;
	var $pass;
	var $firstname;
	var $lastname;
	var $nickname;
	var $idmode;
	var $locale;
	var $email;
	var $url;
	var $ip;
	var $domain;
	var $browser;
	var $datecreated;
	var $level;
	var $avatar_file_ID;
	var $ctry_ID;
	var $source;

	/**
	 * Does the user accept messages?
	 * Options:
	 *		0 - NO,
	 *		1 - only private messages,
	 *		2 - only emails,
	 *		3 - private messages and emails
	 * This attribute can be asked with get_msgform_possibility(), accepts_pm(),
	 * and accepts_email() functions to get complete sense.
	 * This is because it's possible that user allows PM, but user group does not.
	 *
	 * @var boolean
	 */
	var $allow_msgform;
	var $notify;
	var $notify_moderation;
	var $unsubscribe_key;
	var $showonline;
	var $gender;

	/**
	 * Has the user been validated (by email)?
	 * @var boolean
	 */
	var $validated;

	/**
	 * Number of posts by this user. Use get_num_posts() to access this (lazy filled).
	 * @var integer
	 * @access protected
	 */
	var $_num_posts;

	/**
	 * Number of comments by this user. Use get_num_comments() to access this (lazy filled).
	 * @var integer
	 * @access protected
	 */
	var $_num_comments;

	/**
	 * The ID of the (primary, currently only) group of the user.
	 * @var integer
	 */
	var $group_ID;

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
	var $userfields_by_type = array();
	var $updated_fields = array();
	var $new_fields = array();

	/**
	 * Userfield defs
	 */
	var $userfield_defs;

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

		// blueyed> TODO: this will never get translated for the current User if he has another locale/lang set than default, because it gets adjusted AFTER instantiating him/her..
		//       Use a callback (get_delete_restrictions/get_delete_cascades) instead? Should be also better for performance!
		// fp> These settings should probably be merged with the global database description used by the installer/upgrader. However I'm not sure about how compelx plugins would be able to integrate then...
		$this->delete_restrictions = array(
				array( 'table'=>'T_blogs', 'fk'=>'blog_owner_user_ID', 'msg'=>T_('%d blogs owned by this user') ),
				array( 'table'=>'T_items__item', 'fk'=>'post_lastedit_user_ID', 'msg'=>T_('%d posts last edited by this user') ),
				array( 'table'=>'T_items__item', 'fk'=>'post_assigned_user_ID', 'msg'=>T_('%d posts assigned to this user') ),
				array( 'table'=>'T_links', 'fk'=>'link_creator_user_ID', 'msg'=>T_('%d links created by this user') ),
				array( 'table'=>'T_links', 'fk'=>'link_lastedit_user_ID', 'msg'=>T_('%d links last edited by this user') ),
				array( 'table'=>'T_messaging__message', 'fk'=>'msg_author_user_ID', 'msg'=>T_('The user has %d authored message(s)') ),
				array( 'table'=>'T_messaging__threadstatus', 'fk'=>'tsta_user_ID', 'msg'=>T_('The user is part of %d messaging thread(s)') ),
			);

		$this->delete_cascades = array(
				array( 'table'=>'T_users__usersettings', 'fk'=>'uset_user_ID', 'msg'=>T_('%d user settings on collections') ),
				array( 'table'=>'T_sessions', 'fk'=>'sess_user_ID', 'msg'=>T_('%d sessions opened by this user') ),
				array( 'table'=>'T_coll_user_perms', 'fk'=>'bloguser_user_ID', 'msg'=>T_('%d user permissions on blogs') ),
				array( 'table'=>'T_subscriptions', 'fk'=>'sub_user_ID', 'msg'=>T_('%d subscriptions') ),
				array( 'table'=>'T_items__item', 'fk'=>'post_creator_user_ID', 'msg'=>T_('%d posts created by this user') ),
				array( 'table'=>'T_comments__votes', 'fk'=>'cmvt_user_ID', 'msg'=>T_('%d votes of comments') ),
			);

		if( $db_row == NULL )
		{ // Setting those object properties, which are not "NULL" in DB (MySQL strict mode):

			// echo 'Creating blank user';
			$this->set( 'login', 'login' );
			$this->set( 'pass', md5('pass') );
			$this->set( 'locale',
				isset( $Settings )
					? $Settings->get('default_locale') // TODO: (settings) use "new users template setting"
					: $default_locale );
			$this->set( 'email', '' );	// fp> TODO: this is an invalid value. Saving the object without a valid email should fail! (actually: it should be fixed by providing a valid email)
			$this->set( 'level', isset( $Settings ) ? $Settings->get('newusers_level') : 0 );
			if( isset($localtimenow) )
			{
				$this->set_datecreated( $localtimenow );
			}
			else
			{ // We don't know local time here!
				$this->set_datecreated( time() );
			}

			if( isset($Settings) )
			{ // Group for this user:
				$this->group_ID = $Settings->get('newusers_grp_ID');
			}

			// This attribute can be asked with get_msgform_possibility(), accepts_pm(), accepts_email() functions
			// Default value: Allow both
 			$this->set( 'allow_msgform', 3 );

 			$this->set( 'notify', 0 );
 			$this->set( 'notify_moderation', 0 );
 			$this->set( 'unsubscribe_key', generate_random_key() );
 			$this->set( 'showonline', 1 );
		}
		else
		{
			// echo 'Instanciating existing user';
			$this->ID = $db_row->user_ID;
			$this->postcode = $db_row->user_postcode;
			$this->age_min = $db_row->user_age_min;
			$this->age_max = $db_row->user_age_max;	
			$this->login = $db_row->user_login;
			$this->pass = $db_row->user_pass;
			$this->firstname = $db_row->user_firstname;
			$this->lastname = $db_row->user_lastname;
			$this->nickname = $db_row->user_nickname;
			$this->idmode = $db_row->user_idmode;
			$this->locale = $db_row->user_locale;
			$this->email = $db_row->user_email;
			$this->url = $db_row->user_url;
			$this->ip = $db_row->user_ip;
			$this->domain = $db_row->user_domain;
			$this->browser = $db_row->user_browser;
			$this->datecreated = $db_row->dateYMDhour;
			$this->level = $db_row->user_level;
			$this->allow_msgform = $db_row->user_allow_msgform;
			$this->validated = $db_row->user_validated;
			$this->notify = $db_row->user_notify;
			$this->notify_moderation = $db_row->user_notify_moderation;
			$this->unsubscribe_key = $db_row->user_unsubscribe_key;
			$this->showonline = $db_row->user_showonline;
			$this->gender = $db_row->user_gender;
			$this->avatar_file_ID = $db_row->user_avatar_file_ID;
			$this->ctry_ID = $db_row->user_ctry_ID;
			$this->source = $db_row->user_source;

			// Group for this user:
			$this->group_ID = $db_row->user_grp_ID;
		}
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		global $DB, $Settings, $UserSettings, $GroupCache, $Messages;
		global $current_User;

		$is_new_user = ( $this->ID == 0 );
		$edited_user_login = param( 'edited_user_login', 'string' );
		param_check_not_empty( 'edited_user_login', T_( 'You must provide a login!' ) );
		// We want all logins to be lowercase to guarantee uniqueness regardless of the database case handling for UNIQUE indexes:
		$this->set_from_Request( 'login', 'edited_user_login', true, 'evo_strtolower' );

		$is_identity_form = param( 'identity_form', 'boolean', false );
		$is_admin_form = param( 'admin_form', 'boolean', false );
		$has_full_access = $current_User->check_perm( 'users', 'edit' );

		// ******* Admin form or new user create ******* //
		if( $is_admin_form || ( $is_identity_form && $is_new_user && $current_User->check_perm( 'users', 'edit', true ) ) )
		{ // level/group and email options are displayed on identity form only when creating a new user.
			if( $this->ID != 1 )
			{ // the admin user group can't be changed
				param_integer_range( 'edited_user_level', 0, 10, T_('User level must be between %d and %d.') );
				$this->set_from_Request( 'level', 'edited_user_level', true );

				$edited_user_Group = $GroupCache->get_by_ID( param( 'edited_user_grp_ID', 'integer' ) );
				$this->set_Group( $edited_user_Group );
			}

			param( 'edited_user_source', 'string', true );
			$this->set_from_Request('source', 'edited_user_source', true);

			param( 'edited_user_email', 'string', true );
			param_check_not_empty( 'edited_user_email', T_('Please enter your e-mail address.') );
			param_check_email( 'edited_user_email', true );
			$this->set_from_Request('email', 'edited_user_email', true);

			param( 'edited_user_validated', 'integer', 0 );
			$this->set_from_Request( 'validated', 'edited_user_validated', true );
		}

		// ******* Identity form ******* //
		if( $is_identity_form )
		{
			// check if new login already exists for another user_ID
			$query = '
				SELECT user_ID
				  FROM T_users
				 WHERE user_login = '.$DB->quote( $edited_user_login ).'
				   AND user_ID != '.$this->ID;

			if( $q = $DB->get_var( $query ) )
			{
				$error_message = T_( 'This login already exists.' );
				if( $current_User->check_perm( 'users', 'edit' ) )
				{
					$error_message = sprintf( T_( 'This login already exists. Do you want to <a %s>edit the existing user</a>?' ),
						'href="?ctrl=user&amp;user_tab=profile&amp;user_ID='.$q.'"' );
				}
				param_error( 'edited_user_login', $error_message );
			}

			// EXPERIMENTAL user fields & EXISTING fields:
			// Get indices of existing userfields:
			$userfield_IDs = $DB->get_col( '
						SELECT uf_ID
							FROM T_users__fields
						 WHERE uf_user_ID = '.$this->ID );

			foreach( $userfield_IDs as $userfield_ID )
			{
				$uf_val = param( 'uf_'.$userfield_ID, 'string', '' );

				// TODO: type checking
				$this->userfield_update( $userfield_ID, $uf_val );
			}

			// Recommend fields:
			$userfields = $DB->get_results( '
				SELECT ufdf_ID
					FROM T_users__fielddefs
				 WHERE ufdf_required = "recommended" AND ufdf_ID NOT IN
								( SELECT uf_ufdf_ID
										FROM T_users__fields
									 WHERE uf_user_ID = '. $this->ID .'
								)
			ORDER BY ufdf_ID' );
			$i = 1;
			foreach( $userfields as $userfield )
			{
				$uf_val = param( 'uf_rec_'.$i++, 'string', '' );
				$uf_type = $userfield->ufdf_ID;
				if( !empty($uf_val) )
				{
					$this->userfield_add( $uf_type, $uf_val );
				}
			}

			// Duplicate fields:
			if( $is_new_user )
			{
				$user_id = param( 'orig_user_ID', 'string', "" );
				if ($user_id <> "")
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

			// NEW fields:
			$new_fields_num = param( 'new_fields_num', 'integer', 0 );
			$new_fields_num = ( $new_fields_num > 0 ) ? $new_fields_num : 3 ;
			for( $i=1; $i<=$new_fields_num; $i++ )
			{	// new fields:
				$new_uf_type = param( 'new_uf_type_'.$i, 'integer', '' );
				$new_uf_val = param( 'new_uf_val_'.$i, 'text', '' );
				$new_uf_val = preg_replace( "~(\n)~", "|", $new_uf_val );
				if( empty($new_uf_type) && empty($new_uf_val) )
				{
					continue;
				}

				if( empty($new_uf_type) )
				{
					param_error( 'new_uf_val_'.$i, T_('Please select a field type.') );
				}
				if( empty($new_uf_val) )
				{
					param_error( 'new_uf_val_'.$i, T_('Please enter a value.') );
				}

				// TODO: type checking
				$this->userfield_add( $new_uf_type, $new_uf_val );
			}

			param( 'edited_user_postcode', 'string', true );
			$this->set_from_Request('postcode', 'edited_user_postcode', true);
			
			param( 'edited_user_age_min', 'string', true );
			param_check_number( 'edited_user_age_min', T_('Age must be a number.') );
			$this->set_from_Request('age_min', 'edited_user_age_min', true);
			
			param( 'edited_user_age_max', 'string', true );
			param_check_number( 'edited_user_age_max', T_('Age must be a number.') );
			$this->set_from_Request('age_max', 'edited_user_age_max', true);
			
			param( 'edited_user_firstname', 'string', true );
			$this->set_from_Request('firstname', 'edited_user_firstname', true);

			param( 'edited_user_lastname', 'string', true );
			$this->set_from_Request('lastname', 'edited_user_lastname', true);

			if( $Settings->get( 'nickname_editing' ) == 'hidden' )
			{
				$this->set_from_Request('nickname', 'edited_user_login', true);
			}
			else
			{
				param( 'edited_user_nickname', 'string', true );
				param_check_not_empty( 'edited_user_nickname', T_('Please enter a nickname (can be the same as your login).') );
				$this->set_from_Request('nickname', 'edited_user_nickname', true);
			}

			param( 'edited_user_idmode', 'string', true );
			$this->set_from_Request('idmode', 'edited_user_idmode', true);

			param( 'edited_user_ctry_ID', 'integer', true );
			param_check_number( 'edited_user_ctry_ID', 'Please select a country', !$current_User->check_perm( 'users', 'edit' ) );
			$this->set_from_Request('ctry_ID', 'edited_user_ctry_ID', true);

			param( 'edited_user_gender', 'string', '' );
			if( param_check_gender( 'edited_user_gender', $Settings->get( 'registration_require_gender' ) == 'required' ) )
			{
				$this->set_from_Request('gender', 'edited_user_gender', true);
			}

			param( 'edited_user_url', 'string', true );
			param_check_url( 'edited_user_url', 'commenting' );
			$this->set_from_Request('url', 'edited_user_url', true);
		}

		// ******* Password form ******* //

		$is_password_form = param( 'password_form', 'boolean', false );

		if( $is_password_form && ( $is_identity_form || $is_new_user || $has_full_access  ))
		{
			param( 'edited_user_pass1', 'string', true );
			$edited_user_pass2 = param( 'edited_user_pass2', 'string', true );

			if( param_check_passwords( 'edited_user_pass1', 'edited_user_pass2', true, $Settings->get('user_minpwdlen') ) )
			{ 	// We can set password
				$this->set( 'pass', md5( $edited_user_pass2 ) );
			}
		}

		if( $is_password_form &&  !($is_identity_form || $is_new_user || $has_full_access) )
		{
			// ******* Password edit form ****** //
			param( 'edited_user_pass1', 'string', true );
			$edited_user_pass2 = param( 'edited_user_pass2', 'string', true );

			$current_user_pass = param( 'current_user_pass', 'string', true );

			if( ! strlen($current_user_pass) )
			{
				param_error('current_user_pass' , T_('Please enter your current password.') );
				param_check_passwords( 'edited_user_pass1', 'edited_user_pass2', true, $Settings->get('user_minpwdlen') );
			}
			else
			{

				if( $this->pass == md5($current_user_pass) )
				{
					if( param_check_passwords( 'edited_user_pass1', 'edited_user_pass2', true, $Settings->get('user_minpwdlen') ) )
					{ // We can set password
						$this->set( 'pass', md5( $edited_user_pass2 ) );
					}
				}
				else
				{
					param_error('current_user_pass' , T_('Your current password is incorrect.') );
					param_check_passwords( 'edited_user_pass1', 'edited_user_pass2', true, $Settings->get('user_minpwdlen') );
				}
			}

		}

		// ******* Preferences form ******* //

		$is_preferences_form = param( 'preferences_form', 'boolean', false );

		if( $is_preferences_form )
		{
			// Email communication
			param( 'edited_user_email', 'string', true );
			param_check_not_empty( 'edited_user_email', T_('Please enter your e-mail address.') );
			param_check_email( 'edited_user_email', true );
			$this->set_from_Request('email', 'edited_user_email', true);

			// set allow_msgform:
			// 0 - none,
			// 1 - only private message,
			// 2 - only email,
			// 3 - private message and email
			$allow_msgform = 0;
			if( param( 'PM', 'integer', 0 ) )
			{ // PM is enabled
				$allow_msgform = 1;
			}
			if( param( 'email', 'integer', 0 ) )
			{ // email is enabled
				$allow_msgform = $allow_msgform + 2;
			}
			$this->set( 'allow_msgform', $allow_msgform );

			param( 'edited_user_notify', 'integer', 0 );
			$this->set_from_Request('notify', 'edited_user_notify', true);

			param( 'edited_user_notify_moderation', 'integer', 0 );
			$this->set_from_Request('notify_moderation', 'edited_user_notify_moderation', true);

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

			param( 'edited_user_showonline', 'integer', 0 );
			$this->set_from_Request('showonline', 'edited_user_showonline', true);
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
			$edited_user_results_per_page = param( 'edited_user_results_per_page', 'integer', NULL );
			if( isset($edited_user_results_per_page) )
			{
				$UserSettings->set( 'results_per_page', $edited_user_results_per_page, $this->ID );
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
				'link_text'    => 'preferredname',
				'link_rel'     => '',
				'link_class'   => '',
				'thumb_size'   => 'crop-32x32',
				'thumb_class'  => '',
			), $params );

		if( $params['link_text'] == 'avatar' )
		{
			$r = $this->get_avatar_imgtag( $params['thumb_size'], $params['thumb_class'] );
		}
		else
		{
			$r = $this->dget( 'preferredname', $params['format'] );
			$params['link_class'] = empty( $params['link_class'] ) ? 'userbubble' : $params['link_class'].' userbubble';
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
				if( evo_strlen($this->url) > 10 )
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
	 * Get preferred name of the user, according to {@link User::$idmode}.
	 *
	 * @return string
	 */
	function get_preferred_name()
	{
		switch( $this->idmode )
		{
			case 'namefl':
				return parent::get('firstname').' '.parent::get('lastname');

			case 'namelf':
				return parent::get('lastname').' '.parent::get('firstname');

			default:
				return parent::get($this->idmode);
		}
	}


	/**
	 * Get User identity link, which is a composite of user avatar and login, both point to the specific user profile tab.
	 * 
	 * @param string On which user profile tab should this link point to
	 * @return string User avatar and login if the identity link is not available, the identity link otherwise.
	 */
	function get_identity_link( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'profile_tab'  => 'profile',
				'before'       => ' ',
				'after'        => ' ',
				'format'       => 'htmlbody',
				'link_to'      => 'userpage',
				'link_text'    => 'avatar',
				'link_rel'     => '',
				'link_class'   => '',
				'thumb_size'   => 'crop-15x15',
				'thumb_class'  => 'avatar_before_login',
			), $params );
		
		$identity_url = get_user_identity_url( $this->ID, $params['profile_tab'] );
		$avatar_tag = '';
		if( $params['link_text'] == 'avatar' || $params['link_text'] == 'only_avatar' )
		{
			$avatar_tag = $this->get_avatar_imgtag( $params['thumb_size'], $params['thumb_class'] );
		}

		$link_login = $params['link_text'] != 'only_avatar' ? $this->login : '';

		if( empty( $identity_url ) )
		{
			return $avatar_tag.$link_login;
		}

		$link_title = T_( 'Show the user profile' );
		$link_text = '<span class="nowrap">'.$avatar_tag.$link_login.'</span>';
		$link_class = $this->get_gender_class().( $avatar_tag == '' ? ' userbubble' : '' );
		return '<a id="username_'.$this->login.'"  href="'.$identity_url.'" title="'.$link_title.'" class="'.$link_class.'">'.$link_text.'</a>';
	}


	/**
	 * Get Country object
	 */
	function & get_Country()
	{
		if( is_null($this->Country) && !empty($this->ctry_ID ) )
		{
			$CountryCache = & get_CountryCache();
			$this->Country = $CountryCache->get_by_ID( $this->ctry_ID );
		}

		return $this->Country;
	}


	/**
	 * Get country name
	 */
	function get_country_name()
	{
		if( $this->get_Country() )
		{	// We have a country:
			return $this->Country->name;
		}

		return 'UNKNOWN';
	}


	/**
	 * Get the number of posts for the user.
	 *
	 * @return integer
	 */
	function get_num_posts()
	{
		global $DB;
		global $collections_Module;

		if( isset($collections_Module) )
		{
			if( is_null( $this->_num_posts ) )
			{
				$this->_num_posts = $DB->get_var( 'SELECT count(*)
																					FROM T_items__item
																					WHERE post_creator_user_ID = '.$this->ID );
			}
		}

		return $this->_num_posts;
	}


	/**
	 * Get the number of comments for the user.
	 *
	 * @return integer
	 */
	function get_num_comments()
	{
		global $DB;
		global $collections_Module;

		if( isset( $collections_Module ) )
		{
			if( is_null( $this->_num_comments ) )
			{
				$this->_num_comments = $DB->get_var( 'SELECT count(*)
															FROM T_comments
															WHERE comment_author_ID = '.$this->ID );
			}
		}

		return $this->_num_comments;
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
			return '<a href="?ctrl=stats&amp;tab=sessions&amp;tab3=sessid&amp;user='.$this->login.'">'.$num_sessions.'</a>';
		}

		return $num_sessions;
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

		$userdir = get_canonical_path( $media_path.'users/'.$this->login.'/' );

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
			elseif( !@mkdir( $userdir ) )
			{ // add error
				if( is_admin_page() )
				{
					$Messages->add( sprintf( T_("The user's media directory &laquo;%s&raquo; could not be created."), rel_path_to_base($userdir) )
							.get_manual_link('directory_creation_error'), 'error' );
				}
				return false;
			}
			else
			{ // chmod and add note:
				$chmod = $Settings->get('fm_default_chmod_dir');
				if( !empty($chmod) )
				{
					@chmod( $userdir, octdec($chmod) );
				}
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
			return $Blog->get_local_media_url().'users/'.$this->login.'/';
		}

		// System media url:
		return $media_url.'users/'.$this->login.'/';
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
	 */
	function get_userpage_url()
	{
	  /**
		 * @var Blog
		 */
		global $Blog;

		if( empty($Blog) )
		{
			return NULL;
		}

		$blogurl = $Blog->gen_blogurl();

		return url_add_param( $Blog->get('userurl'), 'user_ID='.$this->ID );
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
		switch( $parname )
		{
			case 'level':
			case 'notify':
			case 'notify_moderation':
			case 'showonline':
				return $this->set_param( $parname, 'number', $parvalue, $make_null );

			case 'validated':
				return $this->set_param( $parname, 'number', $parvalue ? 1 : 0, $make_null );	// convert boolean

			case 'ctry_ID':
			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
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
		$this->dbchange( 'dateYMDhour', 'string', 'datecreated' );
	}


	/**
	 * Set email address of the user.
	 *
	 * If the email address has changed and we're configured to invalidate the user in this case,
	 * the user's account gets invalidated here.
	 *
	 * @param string email address to set for the User
	 * @return boolean true, if set; false if not changed
	 */
	function set_email( $email )
	{
		global $Settings;

		$r = parent::set_param( 'email', 'string', $email );

		// Change "validated" status to false (if email has changed and Settings are available, which they are not during install):
		if( $r && isset($Settings) && $Settings->get('newusers_revalidate_emailchg') )
		{ // In-validate account, because (changed) email has not been verified yet:
			parent::set_param( 'validated', 'number', 0 );
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
		if( $Group !== $this->Group )
		{
			$this->Group = & $Group;

			$this->dbchange( 'user_grp_ID', 'number', 'Group->get(\'ID\')' );

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
			$this->Group = & $GroupCache->get_by_ID($this->group_ID);
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
			$pass = md5( $pass );
		}
		// echo 'pass: ', $pass, '/', $this->pass;

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
	 * @param mixed Permission target (blog ID, array of cat IDs, Item...)
	 * @return boolean 0 if permission denied
	 */
	function check_perm( $permname, $permlevel = 'any', $assert = false, $perm_target = NULL )
	{
		global $Debuglog;

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

		$pluggable_perms = array( 'admin', 'spamblacklist', 'slugs', 'templates', 'options', 'files' );
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
			case 'cats_post!protected':
			case 'cats_post!private':
			case 'cats_post!draft':
			case 'cats_post!deprecated':
			case 'cats_post!redirected':
			case 'cats_page':
			case 'cats_intro':
			case 'cats_podcast':
			case 'cats_sidebar':
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

			case 'blog_ismember':
			case 'blog_post_statuses':
			case 'blog_post!published':
			case 'blog_post!protected':
			case 'blog_post!private':
			case 'blog_post!draft':
			case 'blog_post!deprecated':
			case 'blog_post!redirected':
			case 'blog_del_post':
			case 'blog_comments':
			case 'blog_vote_spam_comments':
			case 'blog_draft_comments':
			case 'blog_published_comments':
			case 'blog_deprecated_comments':
			case 'blog_trash_comments':
			case 'blog_properties':
			case 'blog_cats':
			case 'blog_genstatic':
			case 'blog_page':
			case 'blog_intro':
			case 'blog_podcast':
			case 'blog_sidebar':
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
				if( $this->Group->check_perm( 'blogs', $permlevel ) )
				{ // If group grants a global permission:
					$perm = true;
					break;
				}

				if( $perm_target > 0 )
				{ // Check user perm for this blog:
					$perm = $this->check_perm_blogusers( $permname, $permlevel, $perm_target_ID );
					if( ! $perm )
					{ // Check groups for permissions to this specific blog:
						$perm = $this->Group->check_perm_bloggroups( $permname, $permlevel, $perm_target_ID );
					}
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
			case 'item_post!protected':
			case 'item_post!private':
			case 'item_post!draft':
			case 'item_post!deprecated':
			case 'item_post!redirected':
				// Get the Blog ID
				/**
				 * @var Item
				 */
				$Item = & $perm_target;
				$blog_ID = $Item->get_blog_ID();

				if( $this->check_perm_blogowner( $blog_ID ) )
				{	// Owner can do *almost* anything:
					$perm = true;
					break;
				}

				// Group may grant VIEW access, FULL access:
				$this->get_Group();
				if( $this->Group->check_perm( 'blogs', $permlevel ) )
				{ // If group grants a global permission:
					$perm = true;
					break;
				}

				// Check permissions at the blog level:
				$blog_permname = 'blog_'.substr( $permname, 5 );
				$perm = $this->check_perm_blogusers( $blog_permname, $permlevel, $blog_ID, $Item );
				if( ! $perm )
				{ // Check groups for permissions to this specific blog:
					$perm = $this->Group->check_perm_bloggroups( $blog_permname, $permlevel, $blog_ID, $Item, $this );
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
				$perm = Module::check_perm( $permname, $permlevel, $perm_target, 'user_func' );
				if( $perm === true || $perm === NULL )
				{	// We can check group permissions

					// Other global permissions (see if the group can handle them).
					// Forward request to group:
					$this->get_Group();
					$perm = $this->Group->check_perm( $permname, $permlevel, $perm_target );
				}
		}

		// echo "<br>Checking user perm $permname:$permlevel:$perm_target";
		$Debuglog->add( "User perm $permname:$permlevel:"
			.( is_object($perm_target) ? get_class($perm_target).'('.$perm_target_ID.')' : $perm_target ) // prevent catchable E_FATAL with PHP 5.2 (because there's no __tostring for e.g. Item)
			.' => '.($perm?'granted':'DENIED'), 'perms' );

		if( ! $perm && $assert )
		{ // We can't let this go on!
			global $app_name;
			debug_die( sprintf( /* %s is the application name, usually "b2evolution" */ T_('Group/user permission denied by %s!'), $app_name )." ($permname:$permlevel:".( is_object( $perm_target ) ? get_class( $perm_target ).'('.$perm_target_ID.')' : $perm_target ).")" );
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
		if( empty($blog_ID) )
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
	 * Check permission for this user on a specified blog
	 *
	 * This is not for direct use, please call {@link User::check_perm()} instead
	 *
	 * @see User::check_perm()
	 * @param string Permission name, can be one of the following:
	 *                  - blog_ismember
	 *                  - blog_post_statuses
	 *                  - blog_del_post
	 *                  - blog_edit_ts
	 *                  - blog_comments
	 *                  - blog_cats
	 *                  - blog_properties
	 *                  - blog_genstatic
	 * @param string Permission level
	 * @param integer Permission target blog ID
	 * @param Item Item that we want to edit
	 * @return boolean 0 if permission denied
	 */
	function check_perm_blogusers( $permname, $permlevel, $perm_target_blog, $Item = NULL )
	{
		global $DB;
		// echo "checkin for $permname >= $permlevel on blog $perm_target_blog<br />";

		$BlogCache = & get_BlogCache();
		/**
		 * @var Blog
		 */
		$Blog = & $BlogCache->get_by_ID( $perm_target_blog );
		if( ! $Blog->advanced_perms )
		{	// We do not abide to advanced perms
			return false;
		}

		if( ! isset( $this->blog_post_statuses[$perm_target_blog] ) )
		{ // Allowed blog post statuses have not been loaded yet:
			if( $this->ID == 0 )
			{ // User not in DB, nothing to load!:
				return false;	// Permission denied
			}

			// Load now:
			// echo 'loading allowed statuses';
			$query = "
				SELECT *
				  FROM T_coll_user_perms
				 WHERE bloguser_blog_ID = $perm_target_blog
				   AND bloguser_user_ID = $this->ID";
			$row = $DB->get_row( $query, ARRAY_A );

			if( empty($row) )
			{ // No rights set for this Blog/User: remember this (in order not to have the same query next time)
				$this->blog_post_statuses[$perm_target_blog] = array(
						'blog_ismember' => '0',
						'blog_post_statuses' => array(),
						'blog_edit' => 'no',
						'blog_del_post' => '0',
						'blog_edit_ts' => '0',
						'blog_comments' => '0',
						'blog_vote_spam_comments' => '0',
						'blog_draft_comments' => '0',
						'blog_published_comments' => '0',
						'blog_deprecated_comments' => '0',
						'blog_cats' => '0',
						'blog_properties' => '0',
						'blog_admin' => '0',
						'blog_page' => '0',
						'blog_intro' => '0',
						'blog_podcast' => '0',
						'blog_sidebar' => '0',
						'blog_media_upload' => '0',
						'blog_media_browse' => '0',
						'blog_media_change' => '0',
					);
			}
			else
			{ // OK, rights found:
				$this->blog_post_statuses[$perm_target_blog] = array();

				$this->blog_post_statuses[$perm_target_blog]['blog_ismember'] = $row['bloguser_ismember'];

				$bloguser_perm_post = $row['bloguser_perm_poststatuses'];
				if( empty($bloguser_perm_post ) )
					$this->blog_post_statuses[$perm_target_blog]['blog_post_statuses'] = array();
				else
					$this->blog_post_statuses[$perm_target_blog]['blog_post_statuses'] = explode( ',', $bloguser_perm_post );

				$this->blog_post_statuses[$perm_target_blog]['blog_edit'] = $row['bloguser_perm_edit'];
				$this->blog_post_statuses[$perm_target_blog]['blog_del_post'] = $row['bloguser_perm_delpost'];
				$this->blog_post_statuses[$perm_target_blog]['blog_edit_ts'] = $row['bloguser_perm_edit_ts'];
				$this->blog_post_statuses[$perm_target_blog]['blog_comments'] = $row['bloguser_perm_publ_cmts']
					+ $row['bloguser_perm_draft_cmts'] +  $row['bloguser_perm_depr_cmts'];
				$this->blog_post_statuses[$perm_target_blog]['blog_vote_spam_comments'] = $row['bloguser_perm_vote_spam_cmts'];
				$this->blog_post_statuses[$perm_target_blog]['blog_draft_comments'] = $row['bloguser_perm_draft_cmts'];
				$this->blog_post_statuses[$perm_target_blog]['blog_published_comments'] = $row['bloguser_perm_publ_cmts'];
				$this->blog_post_statuses[$perm_target_blog]['blog_deprecated_comments'] = $row['bloguser_perm_depr_cmts'];
				$this->blog_post_statuses[$perm_target_blog]['blog_cats'] = $row['bloguser_perm_cats'];
				$this->blog_post_statuses[$perm_target_blog]['blog_properties'] = $row['bloguser_perm_properties'];
				$this->blog_post_statuses[$perm_target_blog]['blog_admin'] = $row['bloguser_perm_admin'];
				$this->blog_post_statuses[$perm_target_blog]['blog_page'] = $row['bloguser_perm_page'];
				$this->blog_post_statuses[$perm_target_blog]['blog_intro'] = $row['bloguser_perm_intro'];
				$this->blog_post_statuses[$perm_target_blog]['blog_podcast'] = $row['bloguser_perm_podcast'];
				$this->blog_post_statuses[$perm_target_blog]['blog_sidebar'] = $row['bloguser_perm_sidebar'];
				$this->blog_post_statuses[$perm_target_blog]['blog_media_upload'] = $row['bloguser_perm_media_upload'];
				$this->blog_post_statuses[$perm_target_blog]['blog_media_browse'] = $row['bloguser_perm_media_browse'];
				$this->blog_post_statuses[$perm_target_blog]['blog_media_change'] = $row['bloguser_perm_media_change'];
			}
		}

		// Check if permission is granted:
		switch( $permname )
		{
			case 'stats':
				// Wiewing stats is the same perm as being authorized to edit properties: (TODO...)
				if( $permlevel == 'view' )
				{
					return $this->blog_post_statuses[$perm_target_blog]['blog_properties'];
				}
				// No other perm can be granted here (TODO...)
				return false;

			case 'blog_genstatic':
			case 'blog_post_statuses':
				// echo count($this->blog_post_statuses);
				return ( count($this->blog_post_statuses[$perm_target_blog]['blog_post_statuses']) > 0 );

			case 'blog_post!published':
			case 'blog_post!protected':
			case 'blog_post!private':
			case 'blog_post!draft':
			case 'blog_post!deprecated':
			case 'blog_post!redirected':
				// We want a specific permission:
				$subperm = substr( $permname, 10 );
				// echo "checking : $subperm - ", implode( ',', $this->blog_post_statuses[$perm_target_blog]['blog_post_statuses']  ), '<br />';
				$perm = in_array( $subperm, $this->blog_post_statuses[$perm_target_blog]['blog_post_statuses'] );

				// TODO: the following probably should be handled by the Item class!
				if( $perm && $permlevel == 'edit' && !empty($Item) )
				{	// Can we edit this specific Item?
					switch( $this->blog_post_statuses[$perm_target_blog]['blog_edit'] )
					{
						case 'own':
							// Own posts only:
							return ($Item->creator_user_ID == $this->ID);

						case 'lt':
							// Own + Lower level posts only:
							if( $Item->creator_user_ID == $this->ID )
							{
								return true;
							}
							$item_creator_User = & $Item->get_creator_User();
							return ( $item_creator_User->level < $this->level );

						case 'le':
							// Own + Lower or equal level posts only:
							if( $Item->creator_user_ID == $this->ID )
							{
								return true;
							}
							$item_creator_User = & $Item->get_creator_User();
							return ( $item_creator_User->level <= $this->level );

						case 'all':
							return true;

						case 'no':
						default:
							return false;
					}
				}

				return $perm;

			case 'files':
				switch( $permlevel )
				{
					case 'add':
						return $this->blog_post_statuses[$perm_target_blog]['blog_media_upload'];
					case 'view':
						return $this->blog_post_statuses[$perm_target_blog]['blog_media_browse'];
					case 'edit':
						return $this->blog_post_statuses[$perm_target_blog]['blog_media_change'];
					default:
						return false;
				}
				break;

			default:
				// echo $permname, '=', $this->blog_post_statuses[$perm_target_blog][$permname], ' ';
				return $this->blog_post_statuses[$perm_target_blog][$permname];
		}
	}


	/**
	 * Check if this user and his group accept receiving private messages or not
	 *
	 * @return boolean
	 */
	function accepts_pm()
	{
		if( $this->allow_msgform % 2 == 1 )
		{
			$Group = & $this->get_Group();
			return $Group->check_messaging_perm();
		}
		return false;
	}


	/**
	 * Check if this user accepts receiving emails and has an email address
	 *
	 * @return boolean
	 */
	function accepts_email()
	{
		return ( $this->allow_msgform > 1 ) && ( ! empty( $this->email ) );
	}


	/**
	 * Get messaging possibilities between current user and this user
	 *
	 * @return NULL|string allowed messaging possibility: PM > email > login > NULL
	 */
	function get_msgform_possibility( $current_User = NULL )
	{
		if( is_logged_in() )
		{ // current User is a registered user
			if( $current_User == NULL )
			{
				global $current_User;
			}
			if( $this->accepts_pm() && $current_User->accepts_pm() && ( $this->ID != $current_User->ID ) )
			{ // both user has permission to send or receive private message and not the same user
				// check if contact status is blocked between this user and current_User
				$blocked_contact = check_blocked_contacts( array( $this->ID ) );
				if( empty( $blocked_contact ) )
				{
					return 'PM';
				}
			}
			if( $this->accepts_email() )
			{ // this user allows email => send email
				return 'email';
			}
		}
		else
		{ // current User is not logged in
			if( $this->accepts_email() )
			{ // this user allows email
				return 'email';
			}
			if( $this->accepts_pm() )
			{ // no email option try to log in and send private message (just registered users can send PM)
				return 'login';
			}
		}
		// no messaging option between current_User and this user
		return NULL;
	}


	/**
	 * Insert object into DB based on previously recorded changes
	 *
	 * Triggers the plugin event AfterUserInsert.
	 *
	 * @return boolean true on success
	 */
	function dbinsert()
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
			}

			// Notify plugins:
			// A user could be created also in another DB (to synchronize it with b2evo)
			$Plugins->trigger_event( 'AfterUserInsert', $params = array( 'User' => & $this ) );

			$Group = & $this->get_Group();
			if( $Group->check_perm( 'perm_getblog', 'allowed' ) )
			{ // automatically create new blog for this user
				// TODO: dh> also set locale, or use it at least for urltitle_validate below. From the User (new blog owner)?
				$new_Blog = new Blog( NULL );
				$shortname = $this->get( 'login' );
				$new_Blog->set( 'owner_user_ID', $this->ID );
				$new_Blog->set( 'shortname', $shortname );
				$new_Blog->set( 'name', $shortname.'\'s blog' );
				$new_Blog->set( 'locale', $this->get( 'locale' ));
				$new_Blog->set( 'urlname', urltitle_validate( $shortname, $shortname, $new_Blog->ID, false, 'blog_urlname', 'blog_ID', 'T_blogs', $this->get( 'locale' ) ) );
				$new_Blog->create();
			}
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
		global $DB, $Plugins;

		$DB->begin();

		parent::dbupdate();

		// Update existing fields:
		if( !empty($this->updated_fields) )
		{
			foreach( $this->updated_fields as $uf_ID=>$uf_val )
			{
				if( empty( $uf_val ) )
				{	// Delete field:
					$DB->query( 'DELETE FROM T_users__fields
														 WHERE uf_ID = '.$uf_ID );
				}
				else
				{	// Update field:
					$DB->query( 'UPDATE T_users__fields
													SET uf_varchar = '.$DB->quote($uf_val).'
												WHERE uf_ID = '.$uf_ID );
				}
			}
		}

		// Add new fields:
		if( !empty($this->new_fields) )
		{
			$sql = 'INSERT INTO T_users__fields( uf_user_ID, uf_ufdf_ID, uf_varchar )
							VALUES ('.$this->ID.', '.implode( '), ('.$this->ID.', ', $this->new_fields ).' )';
			$DB->query( $sql, 'Insert new fields' );
		}

		// Notify plugins:
		// Example: An authentication plugin could synchronize/update the password of the user.
		$Plugins->trigger_event( 'AfterUserUpdate', $params = array( 'User' => & $this ) );

		$DB->commit();

		// This User has been modified, cached content depending on it should be invalidated:
		BlockCache::invalidate_key( 'user_ID', $this->ID );

		return true;
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

		$DB->begin();

		// Transform registered user comments to unregistered:
		$ret = $DB->query( 'UPDATE T_comments
												SET comment_author_ID = NULL,
														comment_author = '.$DB->quote( $this->get('preferredname') ).',
														comment_author_email = '.$DB->quote( $this->get('email') ).',
														comment_author_url = '.$DB->quote( $this->get('url') ).'
												WHERE comment_author_ID = '.$this->ID );
		if( is_a( $Log, 'log' ) )
		{
			$Log->add( 'Transforming user\'s comments to unregistered comments... '.sprintf( '(%d rows)', $ret ), 'note' );
		}

		// Get list of posts that are going to be deleted (3.23)
		$post_list = implode( ',', $DB->get_col( '
				SELECT post_ID
				  FROM T_items__item
				 WHERE post_creator_user_ID = '.$this->ID ) );

		if( !empty( $post_list ) )
		{
			// Delete comments
			$ret = $DB->query( "DELETE FROM T_comments
													WHERE comment_post_ID IN ($post_list)" );
			if( is_a( $Log, 'log' ) )
			{
				$Log->add( sprintf( 'Deleted %d comments on user\'s posts.', $ret ), 'note' );
			}

			// Delete post extracats
			$ret = $DB->query( "DELETE FROM T_postcats
													WHERE postcat_post_ID IN ($post_list)" );
			if( is_a( $Log, 'log' ) )
			{
				$Log->add( sprintf( 'Deleted %d extracats of user\'s posts\'.', $ret ) ); // TODO: geeky wording.
			}

			// Posts will we auto-deleted by parent method
		}
		else
		{ // no posts
			if( is_a( $Log, 'log' ) )
			{
				$Log->add( 'No posts to delete.', 'note' );
			}
		}

		// remember ID, because parent method resets it to 0
		$old_ID = $this->ID;

		// Delete main object:
		if( ! parent::dbdelete() )
		{
			$DB->rollback();

			$Log->add( 'User has not been deleted.', 'error' );
			return false;
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

		return true;
	}


	function callback_optionsForIdMode( $value )
	{
		$field_options = '';
		$idmode = $this->get( 'idmode' );

		foreach( array( 'nickname' => array( T_('Nickname') ),
										'login' => array( T_('Login') ),
										'firstname' => array( T_('First name') ),
										'lastname' => array( T_('Last name') ),
										'namefl' => array( T_('First name').' '.T_('Last name'),
																				implode( ' ', array( $this->get('firstname'), $this->get('lastname') ) ) ),
										'namelf' => array( T_('Last name').' '.T_('First name'),
																				implode( ' ', array( $this->get('lastname'), $this->get('firstname') ) ) ),
										)
							as $lIdMode => $lInfo )
		{
			$disp = isset( $lInfo[1] ) ? $lInfo[1] : $this->get($lIdMode);

			$field_options .= '<option value="'.$lIdMode.'"';
			if( $value == $lIdMode )
			{
				$field_options .= ' selected="selected"';
			}
			$field_options .= '>'.( !empty( $disp ) ? $disp.' ' : ' - ' )
												.'&laquo;'.$lInfo[0].'&raquo;'
												.'</option>';
		}

		return $field_options;
	}


	/**
	 * Send an email to the user with a link to validate/confirm his email address.
	 *
	 * If the email could get sent, it saves the used "request_id" into the user's Session.
	 *
	 * @param string URL, where to redirect the user after he clicked the validation link (gets saved in Session).
	 * @return boolean True, if the email could get sent; false if not
	 */
	function send_validate_email( $redirect_to_after = NULL )
	{
		global $app_name, $Session, $secure_htsrv_url;

		$request_id = generate_random_key(22);

		$message = T_('You need to validate your email address by clicking on the following link.')
			."\n\n"
			.T_('Login:')." $this->login\n"
			.sprintf( /* TRANS: %s gets replaced by $app_name (normally "b2evolution") */ T_('Link to validate your %s account:'), $app_name )
			."\n"
			.$secure_htsrv_url.'login.php?action=validatemail'
				.'&reqID='.$request_id
				.'&sessID='.$Session->ID  // used to detect cookie problems
			."\n\n-- \n"
			.T_('Please note:')
			.' '.T_('For security reasons the link is only valid for your current session (by means of your session cookie).');

		$r = send_mail( $this->email, NULL, sprintf( T_('Validate your email address for "%s"'), $this->login ), $message );

		if( $r )
		{ // save request_id into Session
			$request_ids = $Session->get( 'core.validatemail.request_ids' );
			if( ! is_array($request_ids) )
			{
				$request_ids = array();
			}
			$request_ids[] = $request_id;
			$Session->set( 'core.validatemail.request_ids', $request_ids, 86400 * 2 ); // expires in two days (or when clicked)
			if( isset($redirect_to_after) )
			{
				$Session->set( 'core.validatemail.redirect_to', $redirect_to_after  );
			}
			$Session->dbsave(); // save immediately
		}

		return $r;
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
		if( ! $this->get_msgform_possibility() )
		{	// There is no way this user accepts receiving messages.
			return false;
		}

		if( is_null($form_url) )
		{
			global $Blog;
			$form_url = isset($Blog) ? $Blog->get('msgformurl') : '';
		}

		$form_url = url_add_param( $form_url, 'recipient_id='.$this->ID.'&amp;redirect_to='.rawurlencode(url_rel_to_same_host(regenerate_url('','','','&'), $form_url)) );

		if( $title == '#' ) $title = T_('Send email to user');
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
				return T_('Man');

			case 'F':
				return T_('Woman');
		}

		return NULL;
	}


	/**
	 * Return attr class depending on gender of the user
	 */
	function get_gender_class()
	{
		global $Settings;

		if( ! $Settings->get('gender_colored') )
		{ // Don't set a gender class if the setting is OFF
			return '';
		}

		switch( $this->gender )
		{ // Set a class name for each gender type
			case 'M':
				$gender_class = 'user_man';
				break;
			case 'F':
				$gender_class = 'user_woman';
				break;
			default:
				$gender_class = 'user_nogender';
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
	 * Get avatar IMG tag.
	 *
	 * @param string size
	 * @param string class
	 * @param string align
	 * @param boolean true if the avatar image should be zoomed on click, false otherwise
	 * @return string
	 */
	function get_avatar_imgtag( $size = 'crop-64x64', $class = 'avatar', $align = '', $zoomable = false )
	{
		/**
		 * @var File
		 */
		if( ! $File = & $this->get_avatar_File() )
		{
			return '';
		}

		if( $zoomable )
		{ // return clickable avatar tag, zoom on click
			// set random value to link_rel, this way the pictures on the page won't be grouped
			// this is usefull because the same avatar picture may appear more times in the same page
			$link_rel = 'lightbox[f'.$File->ID.rand(0, 100000).']';
			$r = $File->get_tag( '', '', '', '', $size, 'original', $File->get_name(), $link_rel, $class );
		}
		else
		{
			$r = $File->get_thumb_imgtag( $size, $class, $align );
		}

		return $r;
	}


	/**
	 * Add a user field
	 */
	function userfield_add( $type, $val )
	{
		global $DB;
		$this->new_fields[] = $type.', '.$DB->quote($val);
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
	 * Load userfields
	 */
	function userfields_load()
	{
		global $DB;

		$userfields = $DB->get_results( '
			SELECT uf_ID, uf_ufdf_ID, uf_varchar
				FROM T_users__fields
			 WHERE uf_user_ID = '.$this->ID );

		foreach( $userfields as $userfield )
		{
			// Save all data for this field:
			$this->userfields[$userfield->uf_ID] = array( $userfield->uf_ufdf_ID, $userfield->uf_varchar);
			// Save index
			$this->userfields_by_type[$userfield->uf_ufdf_ID][] = $userfield->uf_ID;
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
				SELECT ufdf_ID, ufdf_type, ufdf_name, ufdf_required
					FROM T_users__fielddefs' );

			foreach( $userfield_defs as $userfield_def )
			{
				$this->userfield_defs[$userfield_def->ufdf_ID] = array( $userfield_def->ufdf_type, $userfield_def->ufdf_name, $userfield_def->ufdf_required ); //jamesz
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
		global $current_User, $DB, $Messages, $UserSettings;

		if( !$current_User->check_perm( 'users', 'edit' ) && $this->ID != $current_User->ID )
		{ // user is only allowed to update him/herself
			$Messages->add( T_('You are only allowed to update your own profile!') );
			return 'view';
		}

		// memorize user old login and root path, before update
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
				$DB->commit();
			}
			else
			{
				$DB->rollback();
			}
		}
		else
		{
			$DB->rollback();
			$Messages->add( 'New user creation error', 'error' );
		}

		// Update user settings:
		if( param( 'preferences_form', 'boolean', false ) )
		{
			if( $UserSettings->dbupdate() )
			{
				$Messages->add( T_('User feature settings have been changed.'), 'success');
			}
		}

		return true;
	}


	/**
	 * Update user avatar file
	 *
	 * @param integer the new avatar file ID
	 * @return mixed true on success, allowed action otherwise
	 */
	function update_avatar( $file_ID )
	{
		global $current_User, $Messages;

		if( !$current_User->check_perm( 'users', 'edit' ) && $this->ID != $current_User->ID )
		{ // user is only allowed to update him/herself
			$Messages->add( T_('You are only allowed to update your own profile!'), 'error' );
			return 'view';
		}

		if( $file_ID == NULL )
		{
			$Messages->add( T_('Your profile picture could not be changed!'), 'error' );
			return 'edit';
		}

		$this->set( 'avatar_file_ID', $file_ID, true );
		$this->dbupdate();

		$Messages->add( T_('Your profile picture has been changed.'), 'success' );
		return true;
	}


	/**
	 * Remove user avatar
	 *
	 * @return mixed true on success, false otherwise
	 */
	function remove_avatar()
	{
		global $current_User, $Messages;

		if( !$current_User->check_perm( 'users', 'edit' ) && $this->ID != $current_User->ID )
		{ // user is only allowed to update him/herself
			$Messages->add( T_('You are only allowed to update your own profile!'), 'error' );
			return false;
		}

		$this->set( 'avatar_file_ID', NULL, true );
		$this->dbupdate();

		$Messages->add( T_('Your profile picture has been removed.'), 'success' );
		return true;
	}


	/**
	 * Update user avatar file to the currently uploaded file
	 *
	 * @return mixed true on success, allowed action otherwise.
	 */
	function update_avatar_from_upload()
	{
		global $current_User, $Messages;

		if( !$current_User->check_perm( 'users', 'edit' ) && $this->ID != $current_User->ID )
		{ // user is only allowed to update him/herself
			$Messages->add( T_('You are only allowed to update your own profile!'), 'error' );
			return 'view';
		}

		// process upload
		$FileRootCache = & get_FileRootCache();
		$root = FileRoot::gen_ID( 'user', $this->ID );
		$result = process_upload( $root, 'profile_pictures', true, false, true, false );
		if( empty( $result ) )
		{
			$Messages->add( T_( 'You don\'t have permission to selected user file root.' ), 'error' );
			return 'view';
		}

		$uploadedFiles = $result['uploadedFiles'];
		if( !empty( $uploadedFiles ) )
		{ // upload was successful
			$File = $uploadedFiles[0];
			if( $File->is_image() )
			{ // set uploaded image as avatar
				$this->set( 'avatar_file_ID', $File->ID, true );
				$this->dbupdate();
				$Messages->add( T_('Your profile picture has been changed.'), 'success' );
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
}

/*
 * $Log$
 * Revision 1.140  2011/09/28 09:59:43  efy-yurybakh
 * add missing rel="lightbox" in front office
 *
 * Revision 1.139  2011/09/27 21:05:56  fplanque
 * no message
 *
 * Revision 1.138  2011/09/27 17:53:59  efy-yurybakh
 * add missing rel="lightbox" in front office
 *
 * Revision 1.137  2011/09/27 13:30:14  efy-yurybakh
 * spam vote checkbox
 *
 * Revision 1.136  2011/09/27 08:55:29  efy-yurybakh
 * Add User::get_identity_link() everywhere
 *
 * Revision 1.135  2011/09/27 06:08:15  efy-yurybakh
 * Add User::get_identity_link() everywhere
 *
 * Revision 1.134  2011/09/26 19:46:02  efy-yurybakh
 * jQuery bubble tips
 *
 * Revision 1.133  2011/09/26 14:53:27  efy-asimo
 * Login problems with multidomain installs - fix
 * Insert globals: samedomain_htsrv_url, secure_htsrv_url;
 *
 * Revision 1.132  2011/09/26 14:49:57  efy-yurybakh
 * colored usernames
 *
 * Revision 1.131  2011/09/26 12:06:39  efy-asimo
 * Unified usernames everywhere in the app - second part
 *
 * Revision 1.130  2011/09/25 08:22:47  efy-yurybakh
 * Implement new permission for spam voting
 *
 * Revision 1.129  2011/09/25 07:06:21  efy-yurybakh
 * Implement new permission for spam voting
 *
 * Revision 1.128  2011/09/24 07:38:21  efy-yurybakh
 * delete children objects from T_comments__votes
 *
 * Revision 1.127  2011/09/23 18:02:09  fplanque
 * minor
 *
 * Revision 1.126  2011/09/23 11:57:28  efy-vitalij
 * add admin functionality to password change form and edit validate messages in password edit form
 *
 * Revision 1.125  2011/09/23 07:41:57  efy-asimo
 * Unified usernames everywhere in the app - first part
 *
 * Revision 1.124  2011/09/22 12:55:56  efy-vitalij
 * add current password input
 *
 * Revision 1.123  2011/09/22 08:55:00  efy-asimo
 * Login problems with multidomain installs - fix
 *
 * Revision 1.122  2011/09/17 02:31:59  fplanque
 * Unless I screwed up with merges, this update is for making all included files in a blog use the same domain as that blog.
 *
 * Revision 1.121  2011/09/15 22:34:09  fplanque
 * cleanup
 *
 * Revision 1.120  2011/09/15 20:51:09  efy-abanipatra
 * user postcode,age_min,age_mac added.
 *
 * Revision 1.119  2011/09/14 23:42:16  fplanque
 * moved icq aim yim msn to additional userfields
 *
 * Revision 1.118  2011/09/14 22:18:10  fplanque
 * Enhanced addition user info fields
 *
 * Revision 1.117  2011/09/14 07:54:19  efy-asimo
 * User profile refactoring - modifications
 *
 * Revision 1.116  2011/09/12 07:50:57  efy-asimo
 * User gender validation
 *
 * Revision 1.115  2011/09/12 05:28:46  efy-asimo
 * User profile form refactoring
 *
 * Revision 1.114  2011/09/10 00:57:23  fplanque
 * doc
 *
 * Revision 1.113  2011/09/07 22:44:40  fplanque
 * UI cleanup
 *
 * Revision 1.112  2011/09/06 00:54:38  fplanque
 * i18n update
 *
 * Revision 1.111  2011/09/05 23:00:25  fplanque
 * minor/doc/cleanup/i18n
 *
 * Revision 1.110  2011/09/05 21:07:15  sam2kb
 * minor
 *
 * Revision 1.109  2011/09/04 22:13:21  fplanque
 * copyright 2011
 *
 * Revision 1.108  2011/09/04 21:32:16  fplanque
 * minor MFB 4-1
 *
 * Revision 1.107  2011/08/30 06:45:34  efy-james
 * User field type intelligence
 *
 * Revision 1.106  2011/08/29 08:51:14  efy-james
 * Default / mandatory additional fields
 *
 * Revision 1.105  2011/08/26 08:34:37  efy-james
 * Duplicate additional fields when duplicating user
 *
 * Revision 1.104  2011/08/26 04:06:30  efy-james
 * Add extra addional fields on user
 *
 * Revision 1.103  2011/08/22 14:38:39  efy-asimo
 * Add edit_ts permission to blog owners
 *
 * Revision 1.102  2011/08/18 11:41:51  efy-asimo
 * Send all emails from noreply and email contents review
 *
 * Revision 1.101  2011/08/11 09:05:09  efy-asimo
 * Messaging in front office
 *
 * Revision 1.100  2011/05/19 17:47:07  efy-asimo
 * register for updates on a specific blog post
 *
 * Revision 1.99  2011/05/11 07:11:51  efy-asimo
 * User settings update
 *
 * Revision 1.98  2011/04/06 13:30:56  efy-asimo
 * Refactor profile display
 *
 * Revision 1.97  2011/02/23 21:45:18  fplanque
 * minor / cleanup
 *
 * Revision 1.96  2011/02/21 15:25:26  efy-asimo
 * Display user gender
 *
 * Revision 1.95  2011/02/17 14:56:38  efy-asimo
 * Add user source param
 *
 * Revision 1.94  2011/02/15 15:37:00  efy-asimo
 * Change access to admin permission
 *
 * Revision 1.93  2011/02/15 06:13:49  sam2kb
 * strlen replaced with evo_strlen to support utf-8 logins and domain names
 *
 * Revision 1.92  2011/02/15 05:31:53  sam2kb
 * evo_strtolower mbstring wrapper for strtolower function
 *
 * Revision 1.91  2011/02/14 14:13:24  efy-asimo
 * Comments trash status
 *
 * Revision 1.90  2011/02/10 23:07:21  fplanque
 * minor/doc
 *
 * Revision 1.89  2011/01/06 14:31:47  efy-asimo
 * advanced blog permissions:
 *  - add blog_edit_ts permission
 *  - make the display more compact
 *
 * Revision 1.88  2010/12/24 01:47:12  fplanque
 * bump - changed user_notify default
 *
 * Revision 1.87  2010/11/24 14:55:30  efy-asimo
 * Add user gender
 *
 * Revision 1.86  2010/11/07 18:50:44  fplanque
 * Added Comment::author2() with skins v2 style params.
 *
 * Revision 1.85  2010/11/03 19:44:15  sam2kb
 * Increased modularity - files_Module
 * Todo:
 * - split core functions from _file.funcs.php
 * - check mtimport.ctrl.php and wpimport.ctrl.php
 * - do not create demo Photoblog and posts with images (Blog A)
 *
 * Revision 1.84  2010/10/19 02:00:53  fplanque
 * MFB
 *
 * Revision 1.83  2010/10/15 13:10:09  efy-asimo
 * Convert group permissions to pluggable permissions - part1
 *
 * Revision 1.82  2010/07/26 06:52:27  efy-asimo
 * MFB v-4-0
 *
 * Revision 1.81  2010/07/15 06:37:24  efy-asimo
 * Fix messaging warning, also fix redirect after login display
 *
 * Revision 1.80  2010/07/14 09:06:14  efy-asimo
 * todo fp>asimo modifications
 *
 * Revision 1.79  2010/07/12 09:07:37  efy-asimo
 * rename get_msgform_settings() to get_msgform_possibility
 *
 * Revision 1.78  2010/07/02 08:14:19  efy-asimo
 * Messaging redirect modification and "new user get a new blog" fix
 *
 * Revision 1.77  2010/06/24 08:54:05  efy-asimo
 * PHP 4 compatibility
 *
 * Revision 1.76  2010/06/01 11:33:20  efy-asimo
 * Split blog_comments advanced permission (published, deprecated, draft)
 * Use this new permissions (Antispam tool,when edit/delete comments)
 *
 * Revision 1.75  2010/05/07 06:12:38  efy-asimo
 * small modification about messaging
 *
 * Revision 1.74  2010/05/06 09:24:14  efy-asimo
 * Messaging options - fix
 *
 * Revision 1.73  2010/05/05 09:37:08  efy-asimo
 * add _login.disp.php and change groups&users messaging perm
 *
 * Revision 1.72  2010/04/23 11:37:57  efy-asimo
 * send messages - fix
 *
 * Revision 1.71  2010/04/16 10:42:11  efy-asimo
 * users messages options- send private messages to users from front-office - task
 *
 * Revision 1.70  2010/02/08 17:54:47  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.69  2009/12/22 23:13:39  fplanque
 * Skins v4, step 1:
 * Added new disp modes
 * Hooks for plugin disp modes
 * Enhanced menu widgets (BIG TIME! :)
 *
 * Revision 1.68  2009/12/01 03:45:37  fplanque
 * multi dimensional invalidation
 *
 * Revision 1.67  2009/11/30 23:05:30  blueyed
 * Remove dependency on Settings global out of _param.funcs. Adds min length param to param_check_passwords. Add tests.
 *
 * Revision 1.66  2009/11/30 00:22:05  fplanque
 * clean up debug info
 * show more timers in view of block caching
 *
 * Revision 1.65  2009/11/21 13:31:59  efy-maxim
 * 1. users controller has been refactored to users and user controllers
 * 2. avatar tab
 * 3. jQuery to show/hide custom duration
 *
 * Revision 1.64  2009/11/12 00:46:32  fplanque
 * doc/minor/handle demo mode
 *
 * Revision 1.63  2009/10/28 14:26:23  efy-maxim
 * allow selection of None/NULL for country
 *
 * Revision 1.62  2009/10/28 09:50:03  efy-maxim
 * Module::check_perm
 *
 * Revision 1.61  2009/10/27 16:43:34  efy-maxim
 * custom session timeout
 *
 * Revision 1.60  2009/10/26 12:59:36  efy-maxim
 * users management
 *
 * Revision 1.59  2009/10/25 20:39:09  efy-maxim
 * multiple sessions
 *
 * Revision 1.58  2009/10/25 15:22:45  efy-maxim
 * user - identity, password, preferences tabs
 *
 * Revision 1.57  2009/10/17 16:31:33  efy-maxim
 * Renamed: T_groupsettings to T_groups__groupsettings, T_usersettings to T_users__usersettings
 *
 * Revision 1.56  2009/10/08 20:05:52  efy-maxim
 * Modular/Pluggable Permissions
 *
 * Revision 1.55  2009/09/28 20:19:06  blueyed
 * Use crop-64x64 as default for User::get_avatar_imgtag, too.
 *
 * Revision 1.54  2009/09/26 12:00:44  tblue246
 * Minor/coding style
 *
 * Revision 1.53  2009/09/25 14:18:22  tblue246
 * Reverting accidental commits
 *
 * Revision 1.51  2009/09/25 07:33:14  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.50  2009/09/23 07:17:13  efy-bogdan
 *  load_from_Request added to Group class
 *
 * Revision 1.49  2009/09/23 02:56:33  fplanque
 * revert broken permissions
 *
 * Revision 1.48  2009/09/22 16:02:26  tblue246
 * User::check_perm(): Cleaner "message" perm code.
 *
 * Revision 1.47  2009/09/22 07:07:24  efy-bogdan
 * user.ctrl.php cleanup
 *
 * Revision 1.46  2009/09/20 18:13:20  fplanque
 * doc
 *
 * Revision 1.45  2009/09/20 13:46:47  blueyed
 * doc
 *
 * Revision 1.44  2009/09/20 01:35:52  fplanque
 * Factorized User::get_link()
 *
 * Revision 1.43  2009/09/19 20:31:39  efy-maxim
 * 'Reply' permission : SQL queries to check permission ; Block/Unblock functionality; Error messages on insert thread/message
 *
 * Revision 1.42  2009/09/19 01:04:06  fplanque
 * button to remove an avatar from an user profile
 *
 * Revision 1.41  2009/09/18 16:16:50  efy-maxim
 * comments tab in messaging module
 *
 * Revision 1.40  2009/09/18 15:47:11  fplanque
 * doc/cleanup
 *
 * Revision 1.39  2009/09/18 06:14:35  efy-maxim
 * fix for very very bad security issue
 *
 * Revision 1.38  2009/09/16 09:15:32  efy-maxim
 * Messaging module improvements
 *
 * Revision 1.37  2009/09/15 19:31:55  fplanque
 * Attempt to load classes & functions as late as possible, only when needed. Also not loading module specific stuff if a module is disabled (module granularity still needs to be improved)
 * PHP 4 compatible. Even better on PHP 5.
 * I may have broken a few things. Sorry. This is pretty hard to do in one swoop without any glitch.
 * Thanks for fixing or reporting if you spot issues.
 *
 * Revision 1.36  2009/09/15 15:57:05  efy-maxim
 * The admin cannot delete a user if he is part of a thread
 *
 * Revision 1.35  2009/09/14 13:46:11  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.34  2009/09/10 18:24:07  fplanque
 * doc
 *
 * Revision 1.33  2009/09/07 23:35:49  fplanque
 * cleanup
 *
 * Revision 1.32  2009/09/07 14:26:48  efy-maxim
 * Country field has been added to User form (but without updater)
 *
 * Revision 1.31  2009/09/02 23:29:34  fplanque
 * doc
 *
 * Revision 1.30  2009/09/02 18:41:51  tblue246
 * doc
 *
 * Revision 1.29  2009/09/02 17:47:24  fplanque
 * doc/minor
 *
 * Revision 1.28  2009/09/01 16:48:31  tblue246
 * doc
 *
 * Revision 1.27  2009/08/31 21:47:03  fplanque
 * no message
 *
 * Revision 1.26  2009/08/31 20:13:49  fplanque
 * fix
 *
 * Revision 1.25  2009/08/30 17:27:03  fplanque
 * better NULL param handling all over the app
 *
 * Revision 1.24  2009/08/30 00:54:46  fplanque
 * Cleaner userfield handling
 *
 * Revision 1.23  2009/08/29 12:23:56  tblue246
 * - SECURITY:
 * 	- Implemented checking of previously (mostly) ignored blog_media_(browse|upload|change) permissions.
 * 	- files.ctrl.php: Removed redundant calls to User::check_perm().
 * 	- XML-RPC APIs: Added missing permission checks.
 * 	- items.ctrl.php: Check permission to edit item with current status (also checks user levels) for update actions.
 * - XML-RPC client: Re-added check for zlib support (removed by update).
 * - XML-RPC APIs: Corrected method signatures (return type).
 * - Localization:
 * 	- Fixed wrong permission description in blog user/group permissions screen.
 * 	- Removed wrong TRANS comment
 * 	- de-DE: Fixed bad translation strings (double quotes + HTML attribute = mess).
 * - File upload:
 * 	- Suppress warnings generated by move_uploaded_file().
 * 	- File browser: Hide link to upload screen if no upload permission.
 * - Further code optimizations.
 *
 * Revision 1.22  2009/08/23 20:08:27  tblue246
 * - Check extra categories when validating post type permissions.
 * - Removed User::check_perm_catusers() + Group::check_perm_catgroups() and modified User::check_perm() to perform the task previously covered by these two methods, fixing a redundant check of blog group permissions and a malfunction introduced by the usage of Group::check_perm_catgroups().
 *
 * Revision 1.21  2009/08/23 15:37:50  tblue246
 * Fix catchable fatal error
 *
 * Revision 1.20  2009/08/23 13:42:49  tblue246
 * Doc. Please read.
 *
 * Revision 1.19  2009/08/23 12:58:49  tblue246
 * minor
 *
 * Revision 1.18  2009/08/22 20:31:01  tblue246
 * New feature: Post type permissions
 *
 * Revision 1.17  2009/03/08 23:57:46  fplanque
 * 2009
 *
 * Revision 1.16  2009/02/25 22:17:53  blueyed
 * ItemLight: lazily load blog_ID and main_Chapter.
 * There is more, but I do not want to skim the diff again, after
 * "cvs ci" failed due to broken pipe.
 *
 * Revision 1.15  2009/01/27 23:45:54  fplanque
 * minor
 *
 * Revision 1.14  2009/01/21 21:44:35  blueyed
 * TODO to add something like create_media_dir
 *
 * Revision 1.13  2009/01/13 23:45:59  fplanque
 * User fields proof of concept
 *
 * Revision 1.12  2008/09/29 08:30:40  fplanque
 * Avatar support
 *
 * Revision 1.11  2008/04/13 23:38:53  fplanque
 * Basic public user profiles
 *
 * Revision 1.10  2008/04/13 15:15:59  fplanque
 * attempt to fix email headers for non latin charsets
 *
 * Revision 1.9  2008/04/03 22:03:10  fplanque
 * added "save & edit" and "publish now" buttons to edit screen.
 *
 * Revision 1.8  2008/01/21 09:35:36  fplanque
 * (c) 2008
 *
 * Revision 1.7  2008/01/15 08:19:41  fplanque
 * blog footer text tag
 *
 * Revision 1.6  2008/01/14 07:22:07  fplanque
 * Refactoring
 *
 * Revision 1.5  2008/01/12 01:02:30  fplanque
 * minor
 *
 * Revision 1.4  2008/01/05 17:54:44  fplanque
 * UI/help improvements
 *
 * Revision 1.3  2007/11/24 17:24:50  blueyed
 * Add $media_path
 *
 * Revision 1.2  2007/08/26 17:05:58  blueyed
 * MFB: Use $media_url in get_media_url
 *
 * Revision 1.1  2007/06/25 11:01:45  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.77  2007/06/19 23:15:08  blueyed
 * doc fixes
 *
 * Revision 1.76  2007/06/18 21:14:57  fplanque
 * :/
 *
 * Revision 1.74  2007/06/11 01:55:57  fplanque
 * level based user permissions
 *
 * Revision 1.73  2007/05/31 03:02:23  fplanque
 * Advanced perms now disabled by default (simpler interface).
 * Except when upgrading.
 * Enable advanced perms in blog settings -> features
 *
 * Revision 1.72  2007/05/30 01:18:56  fplanque
 * blog owner gets all permissions except advanced/admin settings
 *
 * Revision 1.71  2007/05/29 01:17:20  fplanque
 * advanced admin blog settings are now restricted by a special permission
 *
 * Revision 1.70  2007/05/28 01:33:22  fplanque
 * permissions/fixes
 *
 * Revision 1.69  2007/05/14 02:43:05  fplanque
 * Started renaming tables. There probably won't be a better time than 2.0.
 *
 * Revision 1.68  2007/04/26 00:11:11  fplanque
 * (c) 2007
 *
 * Revision 1.67  2007/03/26 21:03:45  blueyed
 * Normalized/Whitespace
 *
 * Revision 1.66  2007/03/20 09:53:26  fplanque
 * Letting boggers view their own stats.
 * + Letthing admins view the aggregate by default.
 *
 * Revision 1.65  2007/03/07 02:34:29  fplanque
 * Fixed very sneaky bug
 *
 * Revision 1.64  2007/03/02 00:44:43  fplanque
 * various small fixes
 *
 * Revision 1.63  2007/01/23 21:45:25  fplanque
 * "enforce" foreign keys
 *
 * Revision 1.62  2007/01/23 05:00:25  fplanque
 * better user defaults
 *
 * Revision 1.61  2007/01/14 22:08:48  fplanque
 * Broadened global group blog view/edit provileges.
 * I hoipe I didn't screw up here :/
 *
 * Revision 1.60  2006/12/22 00:50:33  fplanque
 * improved path cleaning
 *
 * Revision 1.59  2006/12/13 19:16:31  blueyed
 * Fixed E_FATAL with PHP 5.2
 *
 * Revision 1.58  2006/12/12 19:39:07  fplanque
 * enhanced file links / permissions
 *
 * Revision 1.57  2006/12/07 23:13:11  fplanque
 * @var needs to have only one argument: the variable type
 * Otherwise, I can't code!
 *
 * Revision 1.56  2006/12/06 22:30:07  fplanque
 * Fixed this use case:
 * Users cannot register themselves.
 * Admin creates users that are validated by default. (they don't have to validate)
 * Admin can invalidate a user. (his email, address actually)
 *
 * Revision 1.55  2006/12/03 00:22:16  fplanque
 * doc
 *
 * Revision 1.54  2006/11/28 01:10:28  blueyed
 * doc/discussion
 *
 * Revision 1.53  2006/11/28 00:33:01  blueyed
 * Removed DB::compString() (never used) and DB::get_list() (just a macro and better to have in the 4 used places directly; Cleanup/normalization; no extended regexp, when not needed!
 *
 * Revision 1.52  2006/11/27 21:10:23  fplanque
 * doc
 *
 * Revision 1.51  2006/11/26 02:30:39  fplanque
 * doc / todo
 *
 * Revision 1.50  2006/11/24 18:27:25  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.49  2006/11/02 20:34:40  blueyed
 * MFB (the changed member order is by design, according to db_schema.inc.php)
 *
 * Revision 1.48  2006/10/23 22:19:02  blueyed
 * Fixed/unified encoding of redirect_to param. Use just rawurlencode() and no funky &amp; replacements
 *
 * Revision 1.47  2006/10/22 21:38:00  blueyed
 * getGroup() was never in 1.8, so no need to keep it for BC
 *
 * Revision 1.46  2006/10/22 21:28:41  blueyed
 * Fixes and cleanup for empty User instantiation.
 *
 * Revision 1.45  2006/10/18 00:03:51  blueyed
 * Some forgotten url_rel_to_same_host() additions
 */
?>
