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
 * @package messaging
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-maxim: Evo Factory / Maxim.
 * @author fplanque: Francois Planque.
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/**
 * Aliases for table names:
 *
 * (You should not need to change them.
 *  If you want to have multiple b2evo installations in a single database you should
 *  change {@link $tableprefix} in _basic_config.php)
 */
$db_config['aliases']['T_messaging__thread'] = $tableprefix.'messaging__thread';
$db_config['aliases']['T_messaging__message'] = $tableprefix.'messaging__message';
$db_config['aliases']['T_messaging__threadstatus'] = $tableprefix.'messaging__threadstatus';

/**
 * Controller mappings.
 *
 * For each controller name, we associate a controller file to be found in /inc/ .
 * The advantage of this indirection is that it is easy to reorganize the controllers into
 * subdirectories by modules. It is also easy to deactivate some controllers if you don't
 * want to provide this functionality on a given installation.
 *
 * Note: while the controller mappings might more or less follow the menu structure, we do not merge
 * the two tables since we could, at any time, decide to make a skin with a different menu structure.
 * The controllers however would most likely remain the same.
 *
 * @global array
 */
$ctrl_mappings['messages'] = 'messaging/messages.ctrl.php';
$ctrl_mappings['threads'] = 'messaging/threads.ctrl.php';


/**
 * messaging_Module definition
 */
class messaging_Module
{
	/**
	 * Build the evobar menu
	 */
	function build_evobar_menu()
	{
		global $topleft_Menu;
		global $admin_url;
		global $current_User;

		$entries = array(
			'messaging' => array(
					'text' => T_('Messaging'),
					'disabled' => true,
				),
		);

		if( $current_User->check_perm( 'messaging', 'write' ) )
		{
			$entries['messaging']['disabled'] = false;
			$entries['messaging']['href'] = $admin_url.'?ctrl=threads';
		}

		$topleft_Menu->add_menu_entries( NULL, $entries );
	}

	/**
	 * Builds the 1st half of the menu. This is the one with the most important features
	 */
	function build_menu_1()
	{
	}


	/**
	 * Builds the 2nd half of the menu. This is the one with the configuration features
	 *
	 * At some point this might be displayed differently than the 1st half.
	 */
	function build_menu_2()
	{
	}


	/**
	 * Builds the 3rd half of the menu. This is the one with the configuration features
	 *
	 * At some point this might be displayed differently than the 1st half.
	 */
	function build_menu_3()
	{
		global $dispatcher;
		/**
		 * @var User
		 */
		global $current_User;

		/**
		 * @var AdminUI_general
		 */
		global $AdminUI;

		if( $current_User->check_perm( 'messaging', 'write' ) )
		{	// Permission to view messaging:
			$AdminUI->add_menu_entries( NULL, array(
						'messaging' => array(
						'text' => T_('Messaging'),
						'title' => T_('Messaging'),
						'href' => $dispatcher.'?ctrl=threads',
					),
				) );
		}
	}
}

$messaging_Module = & new messaging_Module();

/*
 * $Log$
 * Revision 1.4  2009/09/14 07:31:43  efy-maxim
 * 1. Messaging permissions have been fully implemented
 * 2. Messaging has been added to evo bar menu
 *
 * Revision 1.3  2009/09/12 18:44:11  efy-maxim
 * Messaging module improvements
 *
 * Revision 1.2  2009/09/10 18:24:07  fplanque
 * doc
 *
 */
?>