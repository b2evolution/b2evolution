<?php
/**
 * This file implements the Geo IP plugin.
 *
 * For the most recent and complete Plugin API documentation
 * see {@link Plugin} in ../inc/plugins/_plugin.class.php.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * @package plugins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @version $Id: _geoip.plugin.php 13 2011-10-24 23:42:53Z fplanque $
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
	var $priority = 50;
	var $version = '5.0.0';
	var $author = 'The b2evo Group';

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
		$this->geoip_manual_download_url = 'http://dev.maxmind.com/geoip/legacy/geolite/';
	}


	/**
	 * Get the settings that the plugin can use.
	 *
	 * Those settings are transfered into a Settings member object of the plugin
	 * and can be edited in the backoffice (Settings / Plugins).
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @see PluginSettings
	 * @see Plugin::PluginSettingsValidateSet()
	 * @return array
	 */
	function GetDefaultSettings( & $params )
	{
		return array(
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
			return sprintf( T_('GeoIP Country database not found. Download the <b>GeoLite Country DB in binary format</b> from here: <a %s>%s</a> and then upload the %s file to the folder: %s.'),
					'href="'.$this->geoip_manual_download_url.'" target="_blank"',
					$this->geoip_manual_download_url,
					$this->geoip_file_name,
					dirname( __FILE__ ) );
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
			$user_update_sql = ', user_ctry_ID = '.$DB->quote( $Country->ID );
		}
		$DB->query( 'UPDATE T_users
				  SET user_reg_ctry_ID = '.$DB->quote( $Country->ID ).
				  $user_update_sql.'
				WHERE user_ID = '.$DB->quote( $User->ID ) );

		// Move user to suspect group by Country ID
		antispam_suspect_user_by_country( $Country->ID, $User->ID );
	}


	/**
	 * Get country by IP address
	 *
	 * @param string IP in format xxx.xxx.xxx.xxx
	 * @return object Country
	 */
	function get_country_by_IP( $IP )
	{
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
	function AdminToolPayload( $params )
	{
		$Form = new Form();

		$Form->begin_form( 'fform' );

		$Form->add_crumb( 'tools' );
		$Form->hidden_ctrl(); // needed to pass the "ctrl=tools" param
		$Form->hiddens_by_key( get_memorized() ); // needed to pass all other memorized params, especially "tab"
		$Form->hidden( 'action', 'geoip_find_country' );

		echo '<p>'.T_('This tool finds all users that do not have a registration country yet and then assigns them a registration country based on their registration IP.').'</p>';

		$Form->button( array(
				'value' => T_('Find Registration Country for all Users NOW!')
			) );

		if( !empty( $this->text_from_AdminTabAction ) )
		{	// Display a report of executed action
			echo '<p><b>'.T_('Report').':</b></p>';
			echo $this->text_from_AdminTabAction;
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
		$action = param_action();

		if( !empty( $action ) )
		{	// If form is submitted
			global $DB;

			switch( $action )
			{
				case 'geoip_find_country':
				// Find and Assign Registration Country for all Users

					$SQL = new SQL( 'Find all users without registration country' );
					$SQL->SELECT( 'user_ID, uset_value' );
					$SQL->FROM( 'T_users' );
					$SQL->FROM_add( 'LEFT JOIN T_users__usersettings
						 ON user_ID = uset_user_ID
						AND uset_name = "created_fromIPv4"' );
					$SQL->WHERE( 'user_reg_ctry_ID IS NULL' );
					$users = $DB->get_assoc( $SQL->get() );

					$total_users = count( $users );
					if( $total_users == 0 )
					{	// No users
						$this->text_from_AdminTabAction = T_('No found users without registration country.');
						break;
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
							$users_report .= ' - <b class="red">'.T_('Country is not detected!').'</b><br />';
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

					$this->text_from_AdminTabAction = '<div>'.sprintf( T_('Count of users without registration country: <b>%s</b>' ), $total_users ).'</div>';
					if( $count_nofound_country > 0 )
					{	// If some users have IP address with unknown country
						$this->text_from_AdminTabAction .= '<div>'.sprintf( T_('Count of users whose country could not be identified: <b>%s</b>' ), $count_nofound_country ).'</div>';
					}
					$this->text_from_AdminTabAction .= '<div style="margin-top:20px">'.$users_report.'</div>';

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
				debug_die( 'This request has been blocked.', array(
					'debug_info' => sprintf( 'A request with [ %s ] ip addresses was blocked because of \'%s\' is blocked.', implode( ', ', $request_ip_list ), $Country->get_name() ) ) );
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

		if( $Country = & $geoip_Plugin->get_country_by_IP( $IP ) )
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