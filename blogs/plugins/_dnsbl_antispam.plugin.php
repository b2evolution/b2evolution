<?php
/**
 * This file implements the dnsbl_antispam_plugin.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link https://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * In addition, as a special exception, the copyright holders give permission to link
 * the code of this program with the PHP/SWF Charts library by maani.us (or with
 * modified versions of this library that use the same license as PHP/SWF Charts library
 * by maani.us), and distribute linked combinations including the two. You must obey the
 * GNU General Public License in all respects for all of the code used other than the
 * PHP/SWF Charts library by maani.us. If you modify this file, you may extend this
 * exception to your version of the file, but you are not obligated to do so. If you do
 * not wish to do so, delete this exception statement from your version.
 * }}
 *
 * {@internal
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package plugins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * This plugin provides the event {@link SessionLoaded()} and checks
 * the remote IP against a list of DNS Blacklists.
 *
 * It allows the user to whitelist her/himself through the plugin interface itself, by requiring a
 * plugin that provides {@link Plugin::CaptchaValidated()} and {@link Plugin::CaptchaPayload()} events.
 * See {@link validate_dependencies()}.
 */
class dnsbl_antispam_plugin extends Plugin
{
	var $name = 'DNSBL Antispam';
	var $code = 'evo_dnsbl';
	var $priority = 40;
	var $version = '$Revision$';
	var $author = 'The b2evo Group';
	var $help_url = 'http://b2evolution.net/';


	/**
	 * Constructor
	 */
	function dnsbl_antispam_plugin()
	{
		$this->short_desc = T_("Checks the user's IP address against a list of DNS blacklists.");
		$this->long_desc = T_('If the IP address is blacklisted, the request is canceled early and the user can (optionally) whitelist his session through a Captcha plugin.');
	}


	/**
	 * Get the default settings of the plugin.
	 *
	 * @return array
	 */
	function GetDefaultSettings()
	{
		return array(
			'dnsbls' => array(
				'label' => T_('DNS Blacklists'),
				'defaultvalue' => "list.dsbl.org\nsbl-xbl.spamhaus.org",
				'type' => 'textarea',
				'size' => '50',
				'note' => T_('The list of DNS blacklists to check, seperated by whitespace.'),
			),
			'use_whitelisting' => array(
				'label' => T_('Whitelisting'),
				'defaultvalue' => '1',
				'note' => T_('Allow the user to whitelist his session by pressing a button once if his IP address is blacklisted.'),
				'type' => 'checkbox',
			),
			'timeout_whitelist' => array(
				'label' => T_('Whitelist timeout'),
				'defaultvalue' => 86400, // timeout: 1 day
				'note' => T_('in seconds. How long should a session be whitelisted?'),
				'size' => 10,
				'valid_pattern' => '~^\d{3,}$~',
			),
			'enable_stats' => array(
				'label' => T_('Statistics'),
				'defaultvalue' => '0',
				'note' => T_('Enable statistics. This generates a small overhead, but will show you how effective it is.'),
				'type' => 'checkbox',
			),
			'tooslow_tries' => array(
				'label' => T_('Retry slow lists'),
				'defaultvalue' => '5',
				'note' => T_('How often should a slow list be retried? (0 to deactivate slow list handling)'),
				'size' => 5,
				'valid_pattern' => '~\d+~',
			),
			'tooslow_limit' => array(
				'label' => T_('Timeout'),
				'defaultvalue' => '2.0',
				'note' => T_('in seconds. When is a DNSBL considered to be too slow?'),
				'size' => '5',
				'valid_pattern' => '~\d+(\.\d+)?~',
			),

		);
	}


	/**
	 * Register a tools tab.
	 */
	function AdminAfterMenuInit()
	{
		$this->register_menu_entry( T_('DNSBL') );
	}


	/**
	 * Method that gets invoked when we're selected in the tools menu.
	 *
	 * Catch params and do actions.
	 */
	function AdminTabAction()
	{
		global $Messages, $Request;
		$this->param_check_for = param( 'check_for' );
		$action = param( 'dnsblaction', 'array', array() );

		if( isset($action['checklist']) && ! empty($this->param_check_for) )
		{
			if( trim( $this->Settings->get( 'dnsbls' ) ) == '' )
			{
				$Messages->add( T_('No DNS blacklists given!'), 'error' );
				return;
			}
			$Messages->add( sprintf( T_('Checking for &laquo;%s&raquo; in DNS blacklists.'), $this->param_check_for ), 'note' );
			$results = $this->is_listed( $this->param_check_for, true );
			foreach( $results as $l_result )
			{
				$Messages->add( $l_result, 'note' );
			}
		}
		elseif( isset($action['update_tooslow']) )
		{
			$shown = param( 'tooslow_shown_lists', 'array', array() );
			$deactivate = param( 'tooslow_deactivate', 'array', array() );

			$tooslow_dnsbls = $this->Settings->get_unserialized( 'tooslow_dnsbls', array() );

			foreach( $shown as $k => $blacklist )
			{
				if( ! empty($deactivate[$k]) )
				{
					if( ! isset($tooslow_dnsbls[$blacklist]) || $tooslow_dnsbls[$blacklist] < $this->Settings->get('tooslow_tries') )
					{
						$tooslow_dnsbls[$blacklist] = $this->Settings->get('tooslow_tries') + 1;
					}
				}
				else
				{
					if( isset($tooslow_dnsbls[$blacklist]) && $tooslow_dnsbls[$blacklist] > $this->Settings->get('tooslow_tries') )
					{
						unset($tooslow_dnsbls[$blacklist]);
					}
				}
			}
			if( $this->Settings->set( 'tooslow_dnsbls', serialize($tooslow_dnsbls) ) )
			{
				$this->Settings->dbupdate();
			}
		}
	}


	/**
	 * Display our tool tab with query action and statistics if enabled.
	 */
	function AdminTabPayload()
	{
		global $DB;

		$Form = new Form();
		$Form->begin_form( 'fform' );

		$Form->text_input( 'check_for', $this->param_check_for, 0, T_('Check') );
		$Form->buttons( array(
				array(
					'name' => 'dnsblaction[checklist]',
					'value' => T_('Check list'),
				),
				array(
					'name' => 'dnsblaction[checkopenrbl]',
					'value' => T_('Check at openrbl.org'),
					'onclick' => 'return pop_up_window( "http://openrbl.org/query?"+document.getElementById("check_for").value, "dnsbl_check" );',
				)
			) );

		$Form->end_form();


		if( $this->Settings->get('tooslow_tries')
		    && ( $dnsbls = preg_split( '~\s+~', $this->Settings->get( 'dnsbls' ), -1, PREG_SPLIT_NO_EMPTY ) ) )
		{
			$Form->begin_form('fform');
			$Form->begin_fieldset( T_('Too slow lists') );

			$tooslow_dnsbls = $this->Settings->get_unserialized( 'tooslow_dnsbls', array() );

			foreach( $dnsbls as $blacklist )
			{
				$Form->hidden( 'tooslow_shown_lists[]', $blacklist );
				$field_params = array();
				if( ! empty($tooslow_dnsbls[$blacklist]) )
				{
					$field_params['note'] = sprintf( T_('This blacklist was %d times too slow.'), $tooslow_dnsbls[$blacklist] );
				}
				$Form->checkbox_input( 'tooslow_deactivate[]',
					( isset( $tooslow_dnsbls[$blacklist] ) && $tooslow_dnsbls[$blacklist] > $this->Settings->get('tooslow_tries') ),
					$blacklist, $field_params );
			}
			$Form->end_fieldset();
			$Form->end_form( array( array('name'=>'dnsblaction[update_tooslow]', 'value'=>T_('Update')) ) );
		}


		if( $this->Settings->get('enable_stats') )
		{
			$c_blocked = $DB->get_var( '
				SELECT COUNT(*)
				  FROM '.$this->get_table_prefix().'log
				 WHERE log_type = "blocked"' );
			$c_notblocked = $DB->get_var( '
				SELECT COUNT(*)
				  FROM '.$this->get_table_prefix().'log
				 WHERE log_type = "not_blocked"' );
			$c_whitelisted = $DB->get_var( '
				SELECT COUNT(*)
				  FROM '.$this->get_table_prefix().'log
				 WHERE log_type = "whitelisted"' );

			$Form->begin_form( 'fform' );
			$Form->begin_fieldset( T_('Statistics') );

			$c_total = $c_blocked + $c_whitelisted + $c_notblocked;
			$Form->info_field( T_('Blocked requests'), $c_blocked.( $c_total ? ' ('.round((100/$c_total)*$c_blocked).'%)' : '' ) );
			$Form->info_field( T_('Whitelisted requests'), $c_whitelisted.( $c_total ? ' ('.round((100/$c_total)*$c_whitelisted).'%)' : '' ) );
			$Form->info_field( T_('Not blocked requests'), $c_notblocked.( $c_total ? ' ('.round((100/$c_total)*$c_notblocked).'%)' : '' ) );

			$stats_from = $DB->get_row( '
				SELECT UNIX_TIMESTAMP(MAX(hit_datetime)) AS end, UNIX_TIMESTAMP(MIN(hit_datetime)) AS start FROM T_hitlog, '.$this->get_table_prefix().'log
				 WHERE hit_ID = log_hit_ID
				 ORDER BY hit_datetime DESC' );

			if( $stats_from )
			{
				$date_fmt = locale_datefmt().' '.locale_timefmt();
				printf( T_( 'The above statistics are from %s to %s.' ), date( $date_fmt, $stats_from->start ), date( $date_fmt, $stats_from->end ) );
			}

			$Form->end_fieldset();
			$Form->end_form();
		}
	}


	/**
	 * If statistics get enabled, create the necessary DB tables.
	 */
	function PluginSettingsBeforeSet( & $params )
	{
		global $DB;

		if( $params['name'] == 'enable_stats' && $params['value'] )
		{
			if( ! $DB->get_var( 'SHOW TABLES LIKE "'.$this->get_table_prefix().'log"' ) )
			{
				$q1 = $DB->query( '
					CREATE TABLE IF NOT EXISTS '.$this->get_table_prefix().'log (
					log_type ENUM( "blocked", "not_blocked", "whitelisted" ) NOT NULL,
					log_hit_ID INT UNSIGNED NULL,
					log_data VARCHAR(255) NULL )' );

				$this->msg( sprintf(T_('Plugin table &laquo;%s&raquo; has been created.'), $this->get_table_prefix().'log') );

				/*
				Might be used later to store total numbers of purged data..
				$q2 = $DB->query( '
					CREATE TABLE IF NOT EXISTS '.$this->get_table_prefix().'log_total (
					logt_blocked INT UNSIGNED NOT NULL,
					logt_not_blocked INT UNSIGNED NOT NULL,
					logt_whitelisted INT UNSIGNED NOT NULL )' );
				*/
			}
		}
		elseif( $params['name'] == 'use_whitelisting' && $params['value'] )
		{
			if( $error = $this->validate_dependencies() )
			{
				$this->msg( $error, 'error' );
				return false;
			}
		}

		return true;
	}


	/**
	 * Pass Uninstall event to parent class, which asks eventually to drop
	 * our tables.
	 *
	 * @return boolean
	 */
	function BeforeUninstall( & $params )
	{
		$params['handles_display'] = true; // we handle display in AdminBeginPayload
		return parent::BeforeUninstall( $params );
	}


	/**
	 * Handle display of the necessary {@link Uninstall()} payload.
	 *
	 * @return boolean
	 */
	function AdminBeginPayload()
	{
		return parent::AdminBeginPayload();
	}


	/**
	 * Check dependency on Captcha plugin and add note in case it's missing.
	 *
	 * @return true
	 */
	function BeforeInstall()
	{
		if( $error = $this->validate_dependencies() )
		{
			$this->msg( $error );
			$this->use_whitelisting = false;
		}
		return true; // Just a note.
	}


	/**
	 * Add message when editing settings about missing dependencies.
	 */
	function PluginSettingsEditAction()
	{
		if( ! $this->use_whitelisting )
		{
			return;
		}

		if( $error = $this->validate_dependencies() )
		{
			$this->msg( $error, 'error' );
			$this->use_whitelisting = false;
			$this->Settings->set('use_whitelisting', 0);
			$this->Settings->dbupdate();
		}
	}


	/**
	 * Get $use_whitelisting property, if not set before (e.g. in {@link Install()}).
	 */
	function PluginSettingsInstantiated()
	{
		if( ! isset($this->use_whitelisting) )
		{
			$this->use_whitelisting = $this->Settings->get('use_whitelisting');
		}
	}


	/**
	 * Plugin's main action hook.
	 *
	 * It checks if the remote IP is in the list of DNS Blacklists and
	 * dies with an error page then, allowing the user to whitelist her/himself for the
	 * session if a plugin is installed for it.
	 *
	 * @return boolean
	 */
	function SessionLoaded()
	{
		global $Hit, $Plugins;

		if( is_admin_page() )
		{
			return false;
		}

		if( $this->use_whitelisting && $this->session_get( 'whitelisted' ) )
		{
			$this->debug_log( 'User is whitelisted.' );
			$this->update_stats( 'whitelisted' );
			return true;
		}

		if( ($ip_blocked_by = $this->is_listed( $Hit->IP )) )
		{ // the IP is blocked
			$this->debug_log( 'IP is blocked: '.$ip_blocked_by );

			if( $this->use_whitelisting )
			{ // check if he wants to whitelist now
				if( $Plugins->trigger_event_first_true( 'CaptchaValidated', array( 'key' => 'dnsbl_'.$this->ID ) ) )
				{
					#echo 'WHITE';
					$this->session_set( 'whitelisted', 1, $this->Settings->get('timeout_whitelisted') );
					$this->update_stats( 'whitelisted' );
					return true;
				}
			}
			else
			{
				$this->debug_log( 'Whitelisting is disabled.' );
			}
			$this->update_stats( 'blocked' );

			// The error for our error page:
			$error_ip_blocked = sprintf( /* TRANS: %s is the name of a DNS blacklist */ T_('Your IP address is blocked in &laquo;%s&raquo;.'), $ip_blocked_by );

			$this->display_error_page( $error_ip_blocked );
			exit();
		}

		// IP not blocked
		$this->update_stats( 'not_blocked' );
	}


	/**
	 * Check if an IP is blacklisted.
	 *
	 * @param string IP address / host name
	 * @param boolean Check all given lists, or return error on first match?
	 * @return false|string|array
	 *   false: IP is ok
	 *   string: The value of the lookup when blacklisted ("blacklist: reason")
	 *   array: of strings if $check_all
	 */
	function is_listed( $ip, $check_all = false )
	{
		global $Timer;

		$dnsbls = preg_split( '~\s+~', $this->Settings->get( 'dnsbls' ), -1, PREG_SPLIT_NO_EMPTY );

		if( !$dnsbls )
		{
			$this->debug_log( 'No DNS blacklists given!' );
		}

		$tooslow_tries = $this->Settings->get( 'tooslow_tries' );
		$tooslow_needs_update = false;
		if( $tooslow_tries )
		{
			$tooslow_dnsbls = $this->Settings->get_unserialized( 'tooslow_dnsbls', array() );
		}
		else
		{
			$tooslow_dnsbls = array();
		}

		$r = array();

		foreach( $dnsbls as $blacklist )
		{
			if( isset($tooslow_dnsbls[$blacklist]) && $tooslow_dnsbls[$blacklist] > $tooslow_tries )
			{
				$this->debug_log( 'Skipping '.$blacklist.', because it is marked as too slow.' );
				continue;
			}
			$log_msg = 'Checking '.$ip.' in DNSBL '.$blacklist.': ';

			$Timer->start( 'check_dnsbl_ip_'.$blacklist, false );
			$listed = $this->check_dnsbl_ip( $ip, $blacklist );
			$Timer->stop( 'check_dnsbl_ip_'.$blacklist );
			$time_taken = $Timer->get_duration('check_dnsbl_ip_'.$blacklist);

			$log_msg .= $listed ? 'BLACKLISTED ('.$listed.')' : 'OK (not listed).';
			$log_msg .= ' ('.$time_taken.'s)';

			if( $tooslow_tries && $time_taken > $this->Settings->get('tooslow_limit') )
			{
				$tooslow_dnsbls[$blacklist] = isset($tooslow_dnsbls[$blacklist]) ? ($tooslow_dnsbls[$blacklist]+1) : 1;
				$tooslow_needs_update = true;
				$this->debug_log( 'Increased tooslow-counter on '.$blacklist.' to '.$tooslow_dnsbls[$blacklist].'.' );
			}

			$this->debug_log( $log_msg );

			if( ! $check_all )
			{
				if( $listed )
				{
					$r = $blacklist.': '.$listed;
					break;
				}
			}
			else
			{
				$r[] = $blacklist.': '.( $listed ? T_('Blacklisted').' ('.$listed.')' : 'not listed' );
			}
		}

		if( $tooslow_needs_update )
		{ // might have changed
			$this->Settings->set( 'tooslow_dnsbls', serialize($tooslow_dnsbls) );
			$this->Settings->dbupdate();
		}

		if( empty($r) )
		{
			return false;
		}

		return $r;
	}


	/**
	 * Check a given IP on a given blacklist.
	 *
	 * @return false|string false if not listed, the resulting string otherwise.
	 */
	function check_dnsbl_ip( $ip, $blacklist )
	{
		// If ipaddr is 1.2.3.4, then lookup format is: 4.3.2.1.sbl-xbl.spamhaus.org
		$rev = array_reverse( explode('.', $ip) );
		$lookup = implode( '.', $rev ).'.'.$blacklist;
		$result = gethostbyname( $lookup );
		if( $lookup != $result )
		{
			return $result;
		}

		return false;
	}


	/**
	 * Display error page that may allow whitelisting the Session.
	 *
	 * @param string Additional error message.
	 */
	function display_error_page( $error_ip_blocked )
	{
		global $Plugins, $plugins_dirout, $core_subdir;

		require_once dirname(__FILE__).'/'.$plugins_dirout.$core_subdir.'_form.class.php';

		header('HTTP/1.0 403 Forbidden');
		?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
				<title>403 Forbidden (IP blocked)</title>
			</head>

			<body>
				<h1><?php echo T_('Your IP address is blocked.') ?></h1>

				<?php
				if( !empty($error_ip_blocked) )
				{
					echo '<p>'.$error_ip_blocked.'</p>';
				}

				if( ! $this->use_whitelisting
				 || ! $Plugins->trigger_event_first_true( 'CaptchaPayload', array( 'key' => 'dnsbl_'.$this->ID ) ) )
				{
					echo '<p>'.T_("Whitelisting is not enabled. You cannot access this site with your current IP address. Your only hope might be to get a new IP address by re-connecting to your Internet Service Provider.").'</p>';
				}

				debug_info();
				?>
			</body>
		</html>
		<?php
	}


	/**
	 * Update our statistics.
	 *
	 * We call ourself here as shutdown_function because we want {@link $Hit::ID}.
	 *
	 * @param string Type ('blocked', 'not_blocked', 'whitelisted')
	 * @return boolean
	 */
	function update_stats( $type, $data = NULL, $doit = false )
	{
		global $DB, $Hit;

		if( ! $this->Settings->get( 'enable_stats' ) )
		{
			return false;
		}

		if( ! $doit )
		{
			register_shutdown_function( array(&$this, 'update_stats'), $type, $data, true );
			return;
		}

		$hit_ID = isset( $Hit->ID ) ? $Hit->ID : NULL;

		$DB->query( '
			INSERT INTO '.$this->get_table_prefix().'log
			( log_type, log_hit_ID, log_data )
			VALUES ( "'.$type.'", '.$DB->quote($hit_ID).', '.$DB->quote($data).' )' );

		return true;
	}


	/**
	 * Internal method to check dependencies on Captcha plugin.
	 *
	 * @return string|true
	 */
	function validate_dependencies()
	{
		global $Plugins;

		if( ! $Plugins->get_list_by_all_events( array('CaptchaValidated', 'CaptchaPayload') ) )
		{
			return T_('There is no Captcha plugin installed. Whitelisting is disabled.');
		}
	}

}



/*
 * $Log$
 * Revision 1.5  2006/01/28 21:13:19  blueyed
 * Use helpers for Session data handling.
 *
 * Revision 1.4  2006/01/26 23:08:36  blueyed
 * Plugins enhanced.
 *
 * Revision 1.3  2006/01/04 15:05:16  fplanque
 * minor
 *
 * Revision 1.2  2005/12/22 23:13:40  blueyed
 * Plugins' API changed and handling optimized
 *
 * Revision 1.1.2.5  2005/12/07 23:38:04  blueyed
 * Just to demo the new Plugins class. Nothing finished.
 *
 * Revision 1.1.2.4  2005/11/26 07:05:08  blueyed
 * Merged from HEAD
 *
 * Revision 1.1.2.3  2005/11/18 08:26:25  blueyed
 * fixed code to 8 chars
 *
 * Revision 1.1.2.2  2005/11/16 23:42:03  blueyed
 * Fix whitelisting.. :/
 *
 * Revision 1.1.2.1  2005/11/16 22:45:32  blueyed
 * DNS Blacklist antispam plugin; T_pluginsettings; Backoffice editing for plugins settings; $Plugin->Settings; MERGE from HEAD;
 *
 */
?>