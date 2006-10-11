<?php
/**
 * This file implements the ping_pingomatic_plugin.
 *
 * For the most recent and complete Plugin API documentation
 * see {@link Plugin} in ../evocore/_plugin.class.php.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
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
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Pingomatic plugin.
 *
 * @package plugins
 */
class ping_pingomatic_plugin extends Plugin
{
	/**
	 * Variables below MUST be overriden by plugin implementations,
	 * either in the subclass declaration or in the subclass constructor.
	 */
	var $code = 'ping_pingomatic';
	var $priority = 50;
	var $version = '1.9-dev';
	var $author = 'http://daniel.hahler.de/';

	/*
	 * These variables MAY be overriden.
	 */
	var $apply_rendering = 'never';
	var $group = 'ping';


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->name = T_('Ping-O-Matic plugin');
		$this->short_desc = T_('Pings the Ping-O-Matic service, which relays your ping to the most common services.');

		$this->ping_service_name = 'Ping-O-Matic';
		$this->ping_service_note = T_('Pings a service that relays the ping to the most common services.');
	}


	/**
	 * Ping the pingomatic RPC service.
	 */
	function ItemSendPing( & $params )
	{
		global $debug;

		$item_Blog = $params['Item']->get_Blog();

		$client = new xmlrpc_client( '/', 'rpc.pingomatic.com', 80 );
		$client->debug = ($debug && $params['display']);

		$message = new xmlrpcmsg("weblogUpdates.ping", array(
				new xmlrpcval( $item_Blog->get('name') ),
				new xmlrpcval( $item_Blog->get('url') ) ));
		$result = $client->send($message);

		$params['xmlrpcresp'] = $result;

		return true;
	}

}


/*
 * $Log$
 * Revision 1.2  2006/10/11 17:21:09  blueyed
 * Fixes
 *
 * Revision 1.1  2006/10/01 22:26:48  blueyed
 * Initial import of ping plugins.
 *
 */
?>