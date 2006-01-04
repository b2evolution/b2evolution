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
 * This plugin provides a method for {@link antispam_main_init} and checks
 * the remote IP against a list of DNS Blacklists.
 *
 * It allows the user to whitelist her/himself.
 */
class dnsbl_antispam_plugin extends Plugin
{
	var $name = 'DNSBL Antispam';
	var $code = 'evoDnsbl';
	var $priority = 10; // do this quite early
	var $version = '$Revision$';
	var $author = 'The b2evo Group';
	var $help_url = 'http://b2evolution.net/';


	/**
	 * Constructor
	 */
	function dnsbl_antispam_plugin()
	{
		$this->short_desc = T_("Checks the user's IP address against a list of DNS blacklists.");
		$this->long_desc = T_('If the IP address is blacklisted, the request is canceled early and the user can (optionally) whitelist his session by clicking a button.');
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
				'defaultvalue' => 'sbl-xbl.spamhaus.org list.dsbl.org',
				'size' => '50',
				'note' => T_('The list of DNS blacklists to check, seperated by whitespace.'),
				),
			'use_whitelisting' => array(
				'label' => T_('Whitelisting'),
				'defaultvalue' => '1',
				'note' => T_('Allow the user to whitelist his session by pressing a button once if his IP address is blacklisted.'),
				'type' => 'checkbox',
				),
		);
	}


	/**
	 * Register a tools tab.
	 */
	function AdminAfterMenuInit()
	{
		$this->register_menu_entry( T_('Check DNSBL') );
	}


	/**
	 * Method that gets invoked when we're selected in the tools menu.
	 *
	 * Catch params and do actions.
	 */
	function AdminTabAction()
	{
		global $Messages;
		$this->param_check_for = param( 'check_for' );

		if( !empty($this->param_check_for) )
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
	}


	/**
	 * Display our tool tab.
	 */
	function AdminTabPayload()
	{
		$Form = new Form();
		$Form->begin_form( 'fform' );

		$Form->text_input( 'check_for', $this->param_check_for, 0, T_('Check') );

		$Form->end_form( array(array( 'submit' )) );
	}


	/**
	 * Plugin hook.
	 *
	 * It checks if the remote IP is in the list of {@link $dnsbls DNS Blacklists} and
	 * dies with an error page then, allowing the user to whitelist her/himself for the
	 * session.
	 */
	function SessionLoaded()
	{
		global $Hit, $Session;

		$use_whitelisting = $this->Settings->get( 'use_whitelisting' );

		if( $use_whitelisting && $Session->get('antispam_whitelist_ip') )
		{
			$this->debug_log( 'User is whitelisted.' );
			return true;
		}

		if( ($ip_blocked_by = $this->is_listed( $Hit->IP )) )
		{ // the IP is blocked
			$this->debug_log( 'IP is blocked: '.$ip_blocked_by );

			if( $use_whitelisting )
			{ // check if he wants to whitelist now
				$whitelist_key = param( 'antispam_whitelist_key', 'string' );

				$this->debug_log( 'Given whitelist key: '.var_export( $whitelist_key, true ) );

				if( !empty($whitelist_key) )
				{ // User is trying to whitelist

					if( $Session->get( 'antispam_whitelist_key' ) == $whitelist_key )
					{ // Whitelisted (remember this in the session and delete obsolete data):
						$Session->set('antispam_whitelist_ip', 1);
						$Session->delete('antispam_whitelist_key');
						$Session->dbsave();

						$this->debug_log( 'User has whitelisted himself.' );

						return true;
					}
				}
			}
			else
			{
				$this->debug_log( 'Whitelisting not enabled.' );
			}

			// The error for our error page:
			$error_ip_blocked = sprintf( /* TRANS: %s is the name of a DNS blacklist */ T_('Your IP address is blocked in &laquo;%s&raquo;.'), $ip_blocked_by );

			$this->display_error_page( $error_ip_blocked );
			exit();
		}
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
		$dnsbls = preg_split( '~\s+~', $this->Settings->get( 'dnsbls' ), -1, PREG_SPLIT_NO_EMPTY );

		if( !$dnsbls )
		{
			$this->debug_log( 'No DNS blacklists given!' );
		}

		$r = array();

		foreach( $dnsbls as $blacklist )
		{
			$log_msg = 'Checking '.$ip.' in DNSBL '.$blacklist.': ';

			$listed = $this->check_dnsbl_ip( $ip, $blacklist );
			if( $listed )
			{
				$log_msg .= 'BLACKLISTED ('.$listed.')';
			}
			else
			{
				$log_msg .= 'OK (not listed).';
			}

			$this->debug_log( $log_msg );

			if( !$check_all )
			{
				return $listed ? $blacklist.': '.$listed : false;
			}

			$r[] = $blacklist.': '.( $listed ? T_('Blacklisted').' ('.$listed.')' : 'not listed' );
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
	 * Display error page that allows whitelisting the Session.
	 *
	 * @param string Additional error message.
	 */
	function display_error_page( $error_ip_blocked )
	{
		global $Session, $ReqURI;
		global $plugins_dirout, $core_subdir;

		require_once dirname(__FILE__).'/'.$plugins_dirout.$core_subdir.'_form.class.php';

		// Whitelist this session - this gets checked against the antispam_whitelist_key GET param:
		$antispam_whitelist_key = generate_random_key(50);
		$Session->set( 'antispam_whitelist_key', $antispam_whitelist_key );
		$Session->dbsave();

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

				if( $this->Settings->get('use_whitelisting') )
				{
					$Form = new Form( $ReqURI );
					$Form->switch_layout( 'none' );
					$Form->begin_form();
					$post_copy = $_POST;
					unset( $post_copy['antispam_whitelist_key'] ); // do not include a (perhaps) previously POSTed one.
					$Form->hiddens_by_key( $post_copy );
					$Form->hidden( 'antispam_whitelist_key', $antispam_whitelist_key );
					$Form->end_form( array( array('value' => T_('I am not evil! Please whitelist my session!')) ) );
				}

				debug_info();
				?>
			</body>
		</html>
		<?php
	}

}



/*
 * $Log$
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