<?php
/**
 * This file implements the Geo IP plugin.
 *
 * For the most recent and complete Plugin API documentation
 * see {@link Plugin} in ../inc/plugins/_plugin.class.php.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * GeoIP Plugin
 *
 * This plugin detects a country of the user at the moment the account is created
 *
 * @package plugins
 */
class geoip_plugin extends Plugin
{
	/**
	 * Variables below MUST be overriden by plugin implementations,
	 * either in the subclass declaration or in the subclass constructor.
	 */
	var $name = 'GeoIP';
	var $code = 'evo_GeoIP';
	var $priority = 45;
	var $version = '6.9.3';
	var $author = 'The b2evo Group';
	var $group = 'antispam';

	/*
	 * These variables MAY be overriden.
	 */
	var $number_of_installs = 1;


	/*
	 * Path to GeoIP Database (file GeoIP.dat)
	 */
	var $geoip_file_path = '';
	var $geoip_file_name = 'GeoIP.dat';

	/**
	 * URL to download the GeoIP.dat
	 * @var string
	 */
	var $geoip_download_url = '';

	/**
	 * URL to the GeoIP's legacy database download page
	 * @var string
	 */
	var $geoip_manual_download_url = '';

	/**
	 * Init
	 *
	 * This gets called after a plugin has been registered/instantiated.
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('GeoIP plugin to detect user\'s country by IP address');
		$this->long_desc = T_('This plugin detects user\'s country at the moment the account is created');

		$this->geoip_file_path = dirname( __FILE__ ).'/'.$this->geoip_file_name;
		$this->geoip_download_url = 'http://geolite.maxmind.com/download/geoip/database/GeoLiteCountry/GeoIP.dat.gz';
		$this->geoip_manual_download_url = 'http://dev.maxmind.com/geoip/legacy/geolite/';
	}


	/**
	 * Define the GLOBAL settings of the plugin here. These can then be edited in the backoffice in System > Plugins.
	 *
	 * @param array Associative array of parameters (since v1.9).
	 *    'for_editing': true, if the settings get queried for editing;
	 *                   false, if they get queried for instantiating {@link Plugin::$Settings}.
	 * @return array see {@link Plugin::GetDefaultSettings()}.
	 * The array to be returned should define the names of the settings as keys (max length is 30 chars)
	 * and assign an array with the following keys to them (only 'label' is required):
	 */
	function GetDefaultSettings( & $params )
	{
		global $admin_url;

		if( file_exists( $this->geoip_file_path ) )
		{
			$datfile_info = sprintf( T_('Last updated on %s'), date( locale_datetimefmt(), filemtime( $this->geoip_file_path ) ) );
		}
		else
		{
			$datfile_info = '<span class="error text-danger">'.T_('Not found').'</span>';
		}
		$datfile_info .= ' - <a href="'.$admin_url.'?ctrl=tools&amp;action=geoip_download&amp;'.url_crumb( 'tools' ).'#geoip" class="btn btn-xs btn-warning">'.T_('Download update now!').'</a>';

		return array(
			'datfile' => array(
				'label' => 'GeoIP.dat',
				'type' => 'info',
				'note' => '',
				'info' => $datfile_info,
				),
			'detect_registration' => array(
				'label' => T_('Detect country on registration'),
				'type' => 'radio',
				'options' => array(
						array( 'no', T_( 'No' ) ),
						array( 'auto', T_( 'Auto select current country in list' ) ),
						array( 'hide', T_( 'Hide country selector if a country has been detected' ) ),
					),
				'field_lines' => true,
				'note' => '',
				'defaultvalue' => 'no',
				),
			'force_account_creation' => array(
				'label' => T_('At account creation'),
				'type' => 'checkbox',
				'note' => T_('force country to the country detected by GeoIP'),
				'defaultvalue' => '1',
				),
			);
	}


	/**
	 * Check the existence of the file "GeoIP.dat" (GeoIP Country database)
	 */
	function BeforeEnable()
	{
		if( !file_exists( $this->geoip_file_path ) )
		{ // GeoIP DB doesn't exist in the right folder
			global $admin_url;


			if( is_install_page() )
			{ // Display simple warning on install pages
				return T_('WARNING: this plugin can only work once you download the GeoLite Country DB database. Go to the plugin settings to download it.');
			}
			else
			{ // Display full detailed warning on backoffice pages
				return sprintf( T_('GeoIP Country database not found. Download the <b>GeoLite Country DB in binary format</b> from here: <a %s>%s</a> and then upload the %s file to the folder: %s. Click <a href="%s">here</a> for automatic download.'),
						'href="'.$this->geoip_manual_download_url.'" target="_blank"',
						$this->geoip_manual_download_url,
						$this->geoip_file_name,
						dirname( __FILE__ ),
						$admin_url.'?ctrl=tools&amp;action=geoip_download&amp;'.url_crumb( 'tools' ).'#geoip' );
			}
		}

		return true;
	}


	/**
	 * Event handler: called at the end of {@link User::dbinsert() inserting
	 * an user account into the database}, which means it has been created.
	 *
	 * @since 1.8.1
	 * @param array Associative array of parameters
	 *   - 'User': the related User (by reference)
	 */
	function AfterUserInsert( & $params )
	{
		$Country = $this->get_country_by_IP( get_ip_list( true ) );

		if( !$Country )
		{	// No found country
			return false;
		}

		global $DB;

		$User = & $params['User'];

		// Update current user
		$user_update_sql = '';
		if( $this->Settings->get( 'force_account_creation' ) )
		{	// Force country to the country detected by GeoIP
			$User->set( 'ctry_ID', $Country->ID );
		}

		// Update user registration country
		$User->set( 'reg_ctry_ID', $Country->ID );
		$User->dbupdate();

		// Move user to suspect group by Country ID
		antispam_suspect_user_by_country( $Country->ID, $User->ID );
	}


	/**
	 * Event handler: Called when a new user has registered and got created.
	 *
	 * Note: if you want to modify a new user,
	 * use {@link Plugin::AppendUserRegistrTransact()} instead!
	 *
	 * @param array Associative array of parameters
	 *   - 'User': the {@link User user object} (as reference).
	 */
	function AfterUserRegistration( & $params )
	{
		$Country = $this->get_country_by_IP( get_ip_list( true ) );

		if( !$Country )
		{	// No found country
			return false;
		}

		$User = & $params['User'];

		// Move user to suspect group by Country ID, even if the current group is a trusted group
		antispam_suspect_user_by_country( $Country->ID, $User->ID, false );
	}


	/**
	 * Get country by IP address
	 *
	 * @param string IP in format xxx.xxx.xxx.xxx
	 * @return object Country
	 */
	function get_country_by_IP( $IP )
	{
		if( $this->status != 'enabled' || ! file_exists( $this->geoip_file_path ) )
		{
			return false;
		}

		if( function_exists('geoip_country_code_by_name') )
		{	// GeoIP extension

			// Get country code by user IP address
			$country_code = @geoip_country_code_by_name($IP);
		}
		else
		{	// Include GeoIP API
			require_once( dirname( __FILE__ ).'/geoip.inc' );

			// Open GeoIP database
			$GeoIP = geoip_open( $this->geoip_file_path, GEOIP_STANDARD );

			// Get country code by user IP address
			$country_code = geoip_country_code_by_addr( $GeoIP, $IP );

			// Close GeoIP DB
			geoip_close( $GeoIP );
		}

		if( ! $country_code )
		{	// No found country with current IP address
			return false;
		}

		global $DB;

		// Get country ID by code
		$SQL = new SQL();
		$SQL->SELECT( 'ctry_ID' );
		$SQL->FROM( 'T_regional__country' );
		$SQL->WHERE( 'ctry_code = '.$DB->quote( strtolower( $country_code ) ) );
		$country_ID = $DB->get_var( $SQL->get() );

		if( !$country_ID )
		{ // No found country in the b2evo DB
			return false;
		}

		// Load Country class (PHP4):
		load_class( 'regional/model/_country.class.php', 'Country' );

		$CountryCache = & get_CountryCache();
		$Country = $CountryCache->get_by_ID( $country_ID, false );

		return $Country;
	}


	/**
	 * This method should return a string that used as suffix
	 *   for the field 'From Country' on the user profile page in the BackOffice
	 *
	 * @param array Associative array of parameters
	 *   - 'User': the related User (by reference)
	 * @return string Field suffix
	 */
	function GetUserFromCountrySuffix( & $params )
	{
		$User = $params['User'];

		$reload_icon = ' '.action_icon( T_('Ask GeoIP'), 'reload', '', T_('Ask GeoIP'), 3, 4, array( 'id' => 'geoip_load_country', 'class' => 'roundbutton roundbutton_text middle' ) )

		// JavaScript to load country by IP address
?>
<script type="text/javascript">
jQuery( document ).ready( function()
{
	jQuery( '#geoip_load_country' ).click( function ()
	{
		var obj_this = jQuery( this );
		jQuery.post( '<?php echo $this->get_htsrv_url( 'load_country', array( 'user_ID' => $User->ID ), '&' ); ?>',
			function( result )
			{
				obj_this.parent().html( ajax_debug_clear( result ) );
			}
		);
		return false;
	} );
} );
</script>
<?php

		return $reload_icon;
	}

	/**
	 * Return the list of Htsrv (HTTP-Services) provided by the plugin.
	 *
	 * This implements the plugin interface for the list of methods that are valid to
	 * get called through htsrv/call_plugin.php.
	 *
	 * @return array
	 */
	function GetHtsrvMethods()
	{
		return array( 'load_country' );
	}

	/**
	 * AJAX callback to load country.
	 *
	 * @param array Associative array of parameters
	 *   - 'user_ID': User ID
	 */
	function htsrv_load_country( $params )
	{
		global $debug, $debug_jslog;
		// Do not append Debuglog to response!
		$debug = false;
		// Do not append Debug JSlog to response!
		$debug_jslog = false;

		$user_ID = $params['user_ID'];

		if( empty( $user_ID ) )
		{	// Bad request
			return;
		}

		$UserCache = & get_UserCache();
		if( ! ( $User = & $UserCache->get_by_ID( $user_ID ) ) )
		{	// No user exists
			return;
		}

		global $UserSettings;
		// Get Country by IP address
		$Country = $this->get_country_by_IP( int2ip( $UserSettings->get( 'created_fromIPv4', $User->ID ) ) );

		if( empty( $Country ) )
		{	// No found country
			echo sprintf( T_('No country found for IP address %s'), int2ip( $UserSettings->get( 'created_fromIPv4', $User->ID ) ) );
		}
		else
		{	// Display country name with flag and Update user's field 'From Country'
			load_funcs( 'regional/model/_regional.funcs.php' );
			country_flag( $Country->get( 'code' ), $Country->get_name() );
			echo ' '.$Country->get_name();

			// Update user
			global $DB;
			$DB->query( 'UPDATE T_users
					  SET user_reg_ctry_ID = '.$DB->quote( $Country->ID ).'
					WHERE user_ID = '.$DB->quote( $User->ID ) );

			// Move user to suspect group by Country ID
			antispam_suspect_user_by_country( $Country->ID, $User->ID );
		}
	}


	/**
	 * Event handler: called at the end of {@link Comment::dbinsert() inserting
	 * a comment into the database}, which means it has been created.
	 *
	 * @param array Associative array of parameters
	 *   - 'Comment': the related Comment (by reference)
	 *   - 'dbchanges': array with DB changes; a copy of {@link Comment::dbchanges()},
	 *                  before they got applied (since 1.9)
	 */
	function AfterCommentInsert( & $params )
	{
		$Country = $this->get_country_by_IP( get_ip_list( true ) );

		if( !$Country )
		{	// No found country
			return false;
		}

		global $DB;

		$Comment = $params['Comment'];

		// Update current comment
		$DB->query( 'UPDATE T_comments
				  SET comment_IP_ctry_ID = '.$DB->quote( $Country->ID ).'
				WHERE comment_ID = '.$DB->quote( $Comment->ID ) );
	}


	/**
	 * Event handler: Called at the begining of the "Register as new user" form.
	 *
	 * You might want to use this to inject antispam payload to use
	 * in {@link Plugin::RegisterFormSent()}.
	 *
	 * @param array Associative array of parameters
	 *   - 'Form': the comment form generating object (by reference)
	 *   - 'inskin': boolean true if the form is displayed in skin
	 */
	function DisplayRegisterFormBefore( & $params )
	{
		global $Settings;

		$registration_require_country = (bool)$Settings->get('registration_require_country');
		if( !$registration_require_country )
		{	// Country is not required on registration form. Exit here.
			return;
		}

		$detect_registration = $this->Settings->get( 'detect_registration' );
		if( $detect_registration == 'no' )
		{	// No detect country on registration
			return;
		}

		$Country = $this->get_country_by_IP( get_ip_list( true ) );

		if( !$Country )
		{	// No found country by IP address
			return;
		}

		switch( $detect_registration )
		{
			case 'auto':
				// Auto select current country in list
				$country = param( 'country', 'integer', 0 );
				if( empty( $country ) )
				{	// Set country ID if user didn't select country yet
					set_param( 'country', $Country->ID );
				}
				break;

			case 'hide':
				// Hide country selector if a country has been detected
				if( ! isset( $params['Form'] ) )
				{	// there's no Form where we add to, but we create our own form:
					$Form = new Form( regenerate_url() );

				}
				else
				{
					$Form = & $params['Form'];
				}

				// Disable this setting temporary to hide a select list with countries
				$Settings->set( 'registration_require_country', 0 );

				// Append a hidden input element with autodetected country ID
				$Form->hidden( 'country', $Country->ID );
				break;
		}
	}


	/**
	 * This method initializes an array that used as additional columns
	 *   for the results table in the BackOffice
	 *
	 * @param array Associative array of parameters
	 *   'table'   - Special name that used to know what plugin must use current table
	 *   'column'  - DB field which contains IP address
	 *   'Results' - Object
	 */
	function GetAdditionalColumnsTable( & $params )
	{
		$params = array_merge( array(
				'table'   => '', // sessions, activity, ipranges
				'column'  => '', // sess_ipaddress, comment_author_IP, aipr_IPv4start, hit_remote_addr
				'Results' => NULL, // object Results
				'order'   => true, // TRUE - to make a column sortable
			), $params );

		if( is_null( $params['Results'] ) || !is_object( $params['Results'] ) )
		{ // Results must be object
			return;
		}

		if( in_array( $params['table'], array( 'sessions', 'activity', 'ipranges', 'top_ips' ) ) )
		{ // Display column only for required tables by GeoIP plugin
			$column = array(
				'th' => T_('Country'),
				'td' => '%geoip_get_country_by_IP( #'.$params['column'].'# )%',
			);
			if( $params['order'] )
			{
				$column['order'] = $params['column'];
			}
			$params['Results']->cols[] = $column;
		}
	}


	/**
	 * Event handler: Called when displaying the block in the "Tools" menu.
	 *
	 * @see Plugin::AdminToolPayload()
	 */
	function AdminToolPayload()
	{
		$action = param_action();

		echo '<a name="geoip" style="position:relative;top:-60px"></a>';

		// Display a form to find countries for users:
		$this->display_tool_form();
		return true;
	}


	/**
	 * Display a form with tool buttons like "Find Registration Country for all Users NOW!" or "Fix for Profile Country for all Users Now"
	 */
	function display_tool_form( $params = array() )
	{
		if( $this->status != 'enabled' )
		{ // Don't allow use this tool when GeoIP plugin is not enabled
			echo '<p class="error">'.T_('You must enable the GeoIP plugin before to use this tool.').'</p>';
			return;
		}

		global $current_User;
		if( ! is_logged_in() || ! $current_User->check_perm( 'options', 'edit' ) )
		{	// Current User must has a permission to run tools:
			return;
		}

		$params = array_merge( array(
				'display_info'        => true,
				'display_button_find' => true,
				'before_button_find'  => '<p>',
				'after_button_find'   => '</p>',
				'display_button_fix'  => false,
				'before_button_fix'   => '<p>',
				'after_button_fix'    => '</p>',
				'display_version'     => true,
			), $params );

		$Form = new Form();

		$Form->begin_form( 'fform' );

		$Form->add_crumb( 'tools' );
		$Form->hidden( 'ctrl', 'tools' );
		$Form->hiddens_by_key( get_memorized() ); // needed to pass all other memorized params, especially "tab"

		if( $params['display_info'] )
		{	// Display info about this form:
			echo '<p>'.T_('This tool finds all users that do not have a registration country yet and then assigns them a registration country based on their registration IP.').
					 get_manual_link('geoip-plugin').'</p>';
		}

		if( $params['display_button_find'] )
		{	// Display a button to find country for users:
			echo $params['before_button_find'];
			$Form->button( array(
					'name'  => 'actionArray[geoip_find_country]',
					'value' => T_('Find Registration Country for all Users NOW!')
				) );
			echo $params['after_button_find'];
		}

		if( $params['display_button_fix'] )
		{	// Display a button to fix country for users:
			echo $params['before_button_fix'];
			$Form->button( array(
					'name'  => 'actionArray[geoip_fix_country]',
					'value' => T_('Fix for Profile Country for all Users Now')
				) );
			echo $params['after_button_fix'];
		}

		if( $params['display_version'] )
		{	// Display plugin version and links to update:
			global $admin_url;
			if( file_exists( $this->geoip_file_path ) )
			{
				$datfile_info = sprintf( T_('Last updated on %s'), date( locale_datetimefmt(), filemtime( $this->geoip_file_path ) ) );
			}
			else
			{
				$datfile_info = '<span class="error text-danger">'.T_('Not found').'</span>';
			}
			$datfile_info .= ' - <a href="'.$admin_url.'?ctrl=tools&amp;action=geoip_download&amp;'.url_crumb( 'tools' ).'#geoip" class="btn btn-warning">'.T_('Download update now!').'</a>';
			echo '<p><b>GeoIP.dat:</b> '.$datfile_info.'</p>';
		}

		$Form->end_form();
	}


	/**
	 * Event handler: Called when handling actions for the "Tools" menu.
	 *
	 * Use {@link $Messages} to add Messages for the user.
	 *
	 * @see Plugin::AdminToolAction()
	 */
	function AdminToolAction()
	{
		global $template_action;
		global $deferred_AdminToolActions;

		$action = param_action();

		if( !empty( $action ) )
		{	// If form is submitted
			switch( $action )
			{
				case 'geoip_download':
					$deferred_AdminToolActions[$this->ID] = 'download_geoip_data';
					$template_action = 'deferred_admin_tool_action';
					break;

				case 'geoip_find_country':
					$deferred_AdminToolActions[$this->ID] = 'geoip_find_country';
					$template_action = 'deferred_admin_tool_action';
					break;

				case 'geoip_fix_country':
					$deferred_AdminToolActions[$this->ID] = 'geoip_fix_country';
					$template_action = 'deferred_admin_tool_action';
					break;
			}
		}
	}


	/**
	 * Event handler: Gets invoked when an action request was called which should be blocked in specific cases
	 *
	 * Blocakble actions: comment post, user login/registration, email send/validation, account activation
	 */
	function BeforeBlockableAction()
	{
		// Get request Ip addresses
		$request_ip_list = get_ip_list();
		foreach( $request_ip_list as $IP_address )
		{
			$Country = $this->get_country_by_IP( $IP_address );

			if( ! $Country )
			{ // Country not found
				continue;
			}

			if( antispam_block_by_country( $Country->ID, false ) )
			{ // Block the action if the country is blocked
				$log_message = sprintf( 'A request with [ %s ] ip addresses was blocked because of \'%s\' is blocked.', implode( ', ', $request_ip_list ), $Country->get_name() );
				exit_blocked_request( 'Country', $log_message, 'plugin', $this->ID ); // WILL exit();
			}
		}
	}


	/**
	 * Event handler: Called at the end of the login procedure, if the
	 * {@link $current_User current User} is set and the user is therefor registered.
	 */
	function AfterLoginRegisteredUser( & $params )
	{
		$Country = $this->get_country_by_IP( get_ip_list( true ) );

		if( ! $Country )
		{	// Country not found
			return false;
		}

		// Check if the currently logged in user should be moved into the suspect group based on the Country ID
		antispam_suspect_user_by_country( $Country->ID );
	}


	/**
	 * Print out a log message
	 *
	 * @param string Message text
	 * @param string Type of message: 'info', 'error', 'warning'
	 */
	function print_tool_log( $message, $type = 'info' )
	{
		switch( $type )
		{
			case 'success':
				$before = '<br /><span class="text-success">';
				$after = '</span>';
				break;

			case 'error':
				$before = '<br /><span class="text-danger">';
				$after = '</span>';
				break;

			case 'warning':
				$before = '<br /><span class="text-warning">';
				$after = '</span>';
				break;

			default:
				$before = '';
				$after = '';
				break;
		}

		echo $before.$message.$after;
		evo_flush();
	}


	/**
	 * Event handler: Called right after displaying the admin users list.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we do something?
	 */
	function AdminAfterUsersList( & $params )
	{
		// Display a form to find countries for users:
		$this->display_tool_form( array(
				'display_info'       => false,
				'before_button_find' => '<p class="center">',
				'after_button_find'  => ' ',
				'display_button_fix' => true,
				'before_button_fix'  => '',
				'after_button_fix'   => '</p>',
				'display_version'    => false,
			) );
	}


	/**
	 * Download GeoIP.dat
	 */
	function download_geoip_data()
	{
		// Display a process of downloading of GeoIP.dat
		global $admin_url;

		$this->print_tool_log( sprintf( T_('Downloading GeoIP.dat file from the url: %s ...'),
			'<a href="'.$this->geoip_download_url.'" target="_blank">'.$this->geoip_download_url.'</a>' ) );

		// DOWNLOAD:
		$gzip_contents = fetch_remote_page( $this->geoip_download_url, $info, 1800 );
		if( $gzip_contents === false || $info['status'] != 200 )
		{ // Downloading is Failed
			if( empty( $info['error'] ) )
			{ // Some unknown error
				$this->print_tool_log( T_( 'The URL is not available. It may correspond to an old version of the GeoIP.dat file.' ), 'error' );
			}
			else
			{ // Display an error of request
				$this->print_tool_log( T_( $info['error'] ), 'error' );
			}
			return;
		}
		$this->print_tool_log( ' OK.<br />' );

		$plugin_dir = dirname( __FILE__ );
		$geoip_dat_file = $plugin_dir.'/'.$this->geoip_file_name;
		// Check if GeoIP.dat file already exists
		if( file_exists( $geoip_dat_file ) )
		{
			if( ! is_writable( $geoip_dat_file ) )
			{
				$this->print_tool_log( sprintf( T_('File %s must be writable to update it. Please fix the write permissions and try again.'), '<b>'.$geoip_dat_file.'</b>' ), 'error' );
				return;
			}
		}
		elseif( ! is_writable( $plugin_dir ) )
		{ // Check the write rights
			$this->print_tool_log( sprintf( T_('Plugin folder %s must be writable to receive GeoIP.dat. Please fix the write permissions and try again.'), '<b>'.$plugin_dir.'</b>' ), 'error' );
			return;
		}

		$gzip_file_name = explode( '/', $this->geoip_download_url );
		$gzip_file_name = $gzip_file_name[ count( $gzip_file_name ) - 1 ];
		$gzip_file_path = sys_get_temp_dir().'/'.$gzip_file_name;

		if( ! save_to_file( $gzip_contents, $gzip_file_path, 'w' ) )
		{ // Impossible to save file...
			$this->print_tool_log( sprintf( T_( 'Unable to create file: %s' ), '<b>'.$gzip_file_path.'</b>' ), 'error' );

			if( file_exists( $gzip_file_path ) )
			{ // Remove file from disk
				if( ! @unlink( $gzip_file_path ) )
				{ // File exists without the write rights
					$this->print_tool_log( sprintf( T_( 'Unable to remove file: %s' ), '<b>'.$gzip_file_path.'</b>' ), 'error' );
				}
			}
			return;
		}

		// UNPACK:
		$this->print_tool_log( sprintf( T_('Extracting of the file %s...'), '<b>'.$gzip_file_path.'</b>' ) );

		if( ! function_exists( 'gzopen' ) )
		{ // No extension
			$this->print_tool_log( T_( 'There is no \'zip\' or \'zlib\' extension installed!' ), 'error' );
			return;
		}

		if( ! ( $gzip_handle = @gzopen( $gzip_file_path, 'rb' ) ) )
		{ // Try to open gzip file
			$this->print_tool_log( T_('Could not open the source file!'), 'error' );
			return;
		}

		if( ! ( $out_handle = @fopen( $plugin_dir.'/'.str_replace( '.gz', '', $gzip_file_name ), 'w' ) ) )
		{
			$this->print_tool_log( sprintf( T_('The file %s cannot be written to disk. Please check the filesystem permissions.'), '<b>'.$plugin_dir.'/'.str_replace( '.gz', '', $gzip_file_name ).'</b>' ), 'error' );
			return;
		}

		$i = 0;
		while( ! gzeof( $gzip_handle ) )
		{ // Extract file by 4Kb
			fwrite( $out_handle, gzread( $gzip_handle, 4096 ) );
			if( $i == 100 )
			{ // Display the process dots after each 400Kb
				$this->print_tool_log( ' .' );
				$i = 0;
			}
			$i++;
		}
		$this->print_tool_log( ' OK.<br />' );

		fclose( $out_handle );
		gzclose( $gzip_handle );

		$this->print_tool_log( sprintf( T_('Remove gzip file %s...'), '<b>'.$gzip_file_path.'</b>' ) );
		if( @unlink( $gzip_file_path ) )
		{
			$this->print_tool_log( ' OK.<br />' );
		}
		else
		{ // Failed on removing
			$this->print_tool_log( sprintf( T_('Impossible to remove the file %s. You can do it manually.'), $gzip_file_path ), 'warning' );
		}

		// Success message
		$this->print_tool_log( sprintf( T_('%s file was downloaded successfully.'), 'GeoIP.dat' ), 'success' );

		// Try to enable plugin automatically:
		global $Plugins;
		$enable_return = $this->BeforeEnable();
		if( $enable_return === true )
		{ // Success enabling
			$this->print_tool_log( T_('The plugin has been enabled.'), 'success' );

			if( $this->status != 'enabled' )
			{ // Enable this plugin automatically:
				$Plugins->set_Plugin_status( $this, 'enabled' );
			}
		}
		else
		{ // Some restriction for enabling
			$this->print_tool_log( T_('The plugin could not be automatically enabled.'), 'warning' );

			if( $this->status != 'needs_config' )
			{ // Make this plugin incomplete because it cannot be enabled:
				$Plugins->set_Plugin_status( $this, 'needs_config' );
			}
		}

		return;
	}


	/**
	 * Find and Assign Registration Country for all Users
	 */
	function geoip_find_country()
	{
		global $DB, $Messages;

		$SQL = new SQL( 'GeoIP plugin #'.$this->ID.': Find all users without registration country' );
		$SQL->SELECT( 'user_ID, uset_value' );
		$SQL->FROM( 'T_users' );
		$SQL->FROM_add( 'LEFT JOIN T_users__usersettings
				ON user_ID = uset_user_ID
			AND uset_name = "created_fromIPv4"' );
		$SQL->WHERE( 'user_reg_ctry_ID IS NULL' );
		$users = $DB->get_assoc( $SQL->get(), $SQL->title );

		$total_users = count( $users );
		if( $total_users == 0 )
		{	// No users
			$this->print_tool_log( T_('No found users without registration country.'), 'warning' );
			return;
		}
		$count_nofound_country = 0;

		$users_report = '';
		foreach( $users as $user_ID => $created_fromIPv4 )
		{
			$users_report .= sprintf( T_('User #%s, IP:%s' ), $user_ID, int2ip( $created_fromIPv4 ) );
			if( empty( $created_fromIPv4 ) )
			{	// No defined IP, Skip this user
				$count_nofound_country++;
				$users_report .= ' - <b class="orange">'.T_('IP is not defined!').'</b><br />';
				continue;
			}

			// Get Country by IP address
			$Country = $this->get_country_by_IP( int2ip( $created_fromIPv4 ) );

			if( !$Country )
			{	// No found country by IP address
				$count_nofound_country++;
				$users_report .= ' - <b class="text-danger">'.T_('Country is not detected!').'</b><br />';
				continue;
			}

			// Update user's registration country
			$DB->query( 'UPDATE T_users
						SET user_reg_ctry_ID = '.$DB->quote( $Country->ID ).'
					WHERE user_ID = '.$DB->quote( $user_ID ) );

			// Move user to suspect group by Country ID
			antispam_suspect_user_by_country( $Country->ID, $user_ID );

			$users_report .= ' - '.sprintf( T_('Country: <b>%s</b>'), $Country->get( 'name' ) ).'<br />';
		}

		$this->print_tool_log( '<div>'.sprintf( T_('Count of users without registration country: <b>%s</b>' ), $total_users ).'</div>' );
		if( $count_nofound_country > 0 )
		{	// If some users have IP address with unknown country
			$this->print_tool_log( '<div>'.sprintf( T_('Count of users whose country could not be identified: <b>%s</b>' ), $count_nofound_country ).'</div>' );
		}
		$this->print_tool_log( '<div style="margin-top:20px;">'.$users_report.'</div>' );

		$this->print_tool_log( '<div>'.T_('Finished searching for registration country of all users').'</div>', 'success' );
	}


	/**
	 * Find and Assign Profile Country for all Users:
	 */
	function geoip_fix_country()
	{
		global $DB;

		$SQL = new SQL( 'GeoIP plugin #'.$this->ID.': Find all users without profile country' );
		$SQL->SELECT( 'user_ID AS ID, user_reg_ctry_ID AS reg_ctry_ID, uset_value AS reg_IP, sess_ipaddress AS session_IP' );
		$SQL->FROM( 'T_users' );
		$SQL->FROM_add( 'LEFT JOIN T_users__usersettings
				ON user_ID = uset_user_ID
			AND uset_name = "created_fromIPv4"' );
		$SQL->FROM_add( 'LEFT JOIN T_sessions
				ON user_ID = sess_user_ID' );
		$SQL->WHERE( 'user_ctry_ID IS NULL' );
		$SQL->ORDER_BY( 'sess_ID DESC' );
		$SQL->GROUP_BY( 'user_ID' );
		$users = $DB->get_results( $SQL->get(), OBJECT, $SQL->title );

		$total_users = count( $users );
		if( $total_users == 0 )
		{	// No users
			$this->print_tool_log( T_('All users already have a profile country.'), 'warning' );
			return;
		}
		$count_nofound_country = 0;

		$users_report = '';
		foreach( $users as $user )
		{
			$Country = false;

			$users_report .= sprintf( T_('User: %s'), '#'.$user->ID ).': ';

			// STEP 1: Get profile Country from IP address of last session:
			if( empty( $user->session_IP ) )
			{	// No defined session IP:
				$users_report .= '<b class="orange">'.T_('Session IP address is not set!').'</b>';
			}
			else
			{	// Get Country by session IP address:
				$users_report .= T_('Session IP address:').' '.$user->session_IP;
				$Country = $this->get_country_by_IP( $user->session_IP );
			}

			// STEP 2: Get profile Country from registration Country:
			if( ! $Country )
			{
				$users_report .= ' - ';
				if( empty( $user->reg_ctry_ID ) )
				{	// No defined registration country:
					$users_report .= '<b class="orange">'.T_('Registration country is not set!').'</b>';
				}
				else
				{	// Get Country by registration IP address:
					$CountryCache = & get_CountryCache();
					$Country = & $CountryCache->get_by_ID( $user->reg_ctry_ID, false, false );
					$users_report .= T_('Registration country:').' '.( $Country ? $Country->get_name() : '#'.$user->reg_ctry_ID );
				}
			}

			// STEP 3: Get profile Country from registration IP address:
			if( ! $Country )
			{
				$users_report .= ' - ';
				if( empty( $user->reg_IP ) )
				{	// No defined registration IP:
					$users_report .= '<b class="orange">'.T_('Registration IP address is not set!').'</b>';
				}
				else
				{	// Get Country by registration IP address:
					$users_report .= T_('Registration IP address:').' '.int2ip( $user->reg_IP );
					$Country = $this->get_country_by_IP( int2ip( $user->reg_IP ) );
				}
			}

			if( ! $Country )
			{	// No found country in 3 steps above:
				$count_nofound_country++;
				$users_report .= ' - <b class="text-danger">'.T_('Country is not detected!').'</b><br />';
				continue;
			}

			// Update user's registration country
			$DB->query( 'UPDATE T_users
						SET user_ctry_ID = '.$DB->quote( $Country->ID ).'
					WHERE user_ID = '.$DB->quote( $user->ID ) );

			// Move user to suspect group by Country ID
			antispam_suspect_user_by_country( $Country->ID, $user->ID );

			$users_report .= ' - '.sprintf( T_('Country: <b>%s</b>'), $Country->get( 'name' ) ).'<br />';
		}

		$this->print_tool_log( '<div>'.sprintf( T_('Count of users without profile country: %s' ), '<b>'.$total_users.'</b>' ).'</div>' );
		if( $count_nofound_country > 0 )
		{	// If some users have IP address with unknown country
			$this->print_tool_log( '<div>'.sprintf( T_('Count of users whose country could not be identified: <b>%s</b>' ), $count_nofound_country ).'</div>' );
		}
		$this->print_tool_log( '<div style="margin-top:20px">'.$users_report.'</div>' );
	}
}


/**
 * Get country by IP address (Used in the method GetAdditionalColumnsTable() in the table column)
 *
 * @param string|integer IP address
 * @return string Country with flag
 */
function geoip_get_country_by_IP( $IP )
{
	global $Plugins, $Timer;

	$Timer->resume( 'plugin_geoip' );

	$country = '';

	if( $Plugins && $geoip_Plugin = & $Plugins->get_by_code( 'evo_GeoIP' ) )
	{
		if( strlen( intval( $IP ) ) == strlen( $IP ) )
		{ // IP is in integer format, We should convert it to normal IP
			$IP = int2ip( $IP );
		}

		if( $Country = $geoip_Plugin->get_country_by_IP( $IP ) )
		{ // Get country flag + name
			load_funcs( 'regional/model/_regional.funcs.php' );
			$country = country_flag( $Country->get( 'code' ), $Country->get_name(), 'w16px', 'flag', '', false ).
				' '.$Country->get_name();
		}
	}

	$Timer->pause( 'plugin_geoip' );

	return $country;
}

?>