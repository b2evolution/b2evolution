<?php
/**
 * This file implements the transport optimizer plugin.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2006 by Daniel HAHLER - {@link https://thequod.de/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package plugins
 *
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * The transport optimizer plugin.
 */
class transport_optimizer_plugin extends Plugin
{
	var $code = '';
	var $priority = 50;
	var $version = '1.8';


	/**
	 * Constructor
	 */
	function transport_optimizer_plugin()
	{
		$this->name = T_('Transport optimizer');
		$this->short_desc = T_('Save bandwidth on your server.');
		$this->long_desc = T_('By providing support of ETag headers and GZip compression it saves bandwidth on the server and optimizes the transport to the browser.');
	}


	/**
	 * Settings
	 */
	function GetDefaultSettings()
	{
		return array(
			'use_etags' => array(
					'label' => T_('ETag header'),
					'type' => 'checkbox',
					'defaultvalue' => '1',
					'note' => T_('This will send an ETag header with every page, so we can say "Not Modified." if exactly the same page had been sent before.'),
				),
			'use_gzipcompression' => array(
					'label' => T_('GZip compression'),
					'type' => 'checkbox',
					'defaultvalue' => '1',
					'note' => T_('If enabled, the plugin will buffer the output and compress it before sending it to the browser. It is recommened to use the php.ini option zlib.output_compression or a webserver module instead.'),
				),
			'send_last_modified' => array(
					'label' => T_('Last-Modified'),
					'type' => 'checkbox',
					'defaultvalue' => '1',
					'note' => T_('This will send a Last-Modified header with the current time.'),
				),
			);
	}


	/**
	 *
	 */
	function AppendPluginRegister()
	{
		// Store in properties, because AbstractSettings::get() uses var_export(), which does not work in an output buffer:
		$this->use_gzipcompression = $this->Settings->get('use_gzipcompression');
		$this->use_etags = $this->Settings->get('use_etags');
		$this->send_last_modified = $this->Settings->get('send_last_modified');

		return true;
	}


	/**
	 * Check conf...
	 */
	function BeforeEnable()
	{
		if( $this->Settings->get('use_gzipcompression')
			&& ! function_exists('gzencode') )
		{
			$this->msg( T_('The PHP function gzencode() is not available. GZip compression is disabled.'), 'note');
			$this->Settings->set('use_gzipcompression', '0');
			$this->Settings->dbupdate();
		}

		return true;
	}


	/**
	 * We start our output buffer here.
	 */
	function SessionLoaded()
	{
		ob_start( array(&$this, 'obhandler') );
	}



	/**
	 * The output buffer handler.
	 *
	 * It generates a md5-ETag, which is checked against the one that may have
	 * been sent by the browser, allowing us to just send a "304 Not modified" response.
	 *
	 * @param string output given by PHP
	*/
	function obhandler( $output )
	{
		global $localtimenow, $current_User;

		if( $this->use_etags )
		{ // Generating ETAG
			// prefix with PUB (public page) or AUT (private page).
			$ETag = isset($current_User) ? '"AUT' : '"PUB'; // is_logged_in() may not be available
			$ETag .= md5( $output ).'"';
			header( 'ETag: '.$ETag );

			// decide to send out or not
			if( isset($_SERVER['HTTP_IF_NONE_MATCH'])
					&& stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) === $ETag )
			{ // client has this page already

				// send 304 and exit
				#header( 'Content-Length: 0' );
				header( $_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified' );
				#$Hit->log();  // TODO: log this somehow?
				exit();
			}
		}

		// Send Last-Modified -----------------
		if( $this->send_last_modified )
		{
			$lastmodified = $localtimenow;

			header( 'Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T', $lastmodified) );
		}


		// GZIP encoding
		if( $this->use_gzipcompression
			&& ! headers_sent() // we need to send the header! As it seems, Apache2 will send all headers on flush(), though no content gets sent.. (PHP_BUG?)
			&& isset($_SERVER['HTTP_ACCEPT_ENCODING'])
			&& strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') )
		{ // requested/accepted by browser:
			$output = gzencode($output);
			header( 'Content-Encoding: gzip' );
		}

		header( 'Content-Length: '.strlen($output) );

		return $output;
	}

}


/* {{{ Revision log:
 * $Log$
 * Revision 1.3  2006/06/30 17:48:16  blueyed
 * Fix
 *
 * Revision 1.2  2006/06/24 00:03:47  blueyed
 * Fixes
 *
 * Revision 1.1  2006/06/19 21:06:55  blueyed
 * Moved ETag- and GZip-support into transport optimizer plugin.
 *
 * }}}
 */
?>