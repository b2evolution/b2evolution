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
 * @author fplanque: Francois Planque.
 *
 * @version $Id: _upgrade_svn.form.php 2193 2012-10-19 11:01:47Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var action
 */
global $action;

switch( $action )
{
	case 'start':

		global $updates, $UserSettings;

		$Form = new Form( NULL, 'upgrade_form', 'post', 'compact' );

		$Form->hiddens_by_key( get_memorized( 'action' ) );

		$Form->begin_form( 'fform', T_('Upgrade from SVN').get_manual_link( 'upgrade-from-svn' ) );

		$Form->text_input( 'svn_url', $UserSettings->get( 'svn_upgrade_url' ), 80, T_('URL of repository'), T_('e.g. https://server.com/svn/repository/'), array( 'maxlength' => 300, 'required' => true ) );
		$Form->text_input( 'svn_folder', $UserSettings->get( 'svn_upgrade_folder' ), 80, T_('SVN folder'), T_('e.g. trunk/blogs/'), array( 'maxlength' => 300 ) );
		$Form->text_input( 'svn_user', $UserSettings->get( 'svn_upgrade_user' ), 32, T_('Login'), '', array( 'maxlength' => 300 ) );
		$Form->password_input( 'svn_password', get_param( 'svn_password' ), 32, T_('Password'), '', array( 'maxlength' => 300 ) );
		$Form->text_input( 'svn_revision', $UserSettings->get( 'svn_upgrade_revision' ), 7, T_('Revision'), T_('Leave blank to get the latest revision') );

		$Form->end_form( array( array( 'submit', 'actionArray[export_svn]', T_( 'Export revision from SVN...' ), 'SaveButton' ) ) );

		break;
}

?>