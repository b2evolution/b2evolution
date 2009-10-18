<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * {@internal Open Source relicensing agreement:
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package maintenance
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-maxim: Evo Factory / Maxim.
 * @author fplanque: Francois Planque.
 *
 * @version $Id$
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Updater class
 *
 * @todo fp> I think we really don't need this class at all.
 */
class Updater
{
	/**
	 * URL to check available updates
	 * @var string
	 */
	var $url;

	/**
	 * Updates list
	 * @var list
	 */
	var $updates;


	/**
	 * Constructor
	 */
	function Updater()
	{
	}




	/**
	 * Start upgrade
	 *
	 * @return boolean
	 */
	function start_upgrade()
	{
		global $Messages;

		// TODO: upgrade files and database

		$Messages->add( T_('B2evolution has been successfully upgraded !'), 'success' );
		return true;
	}
}

/*
 * $Log$
 * Revision 1.4  2009/10/18 17:26:26  fplanque
 * doc
 *
 * Revision 1.3  2009/10/18 08:16:55  efy-maxim
 * log
 *
 */

?>