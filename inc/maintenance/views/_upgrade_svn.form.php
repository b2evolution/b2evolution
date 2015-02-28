<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @package maintenance
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $updates, $UserSettings;

$Form = new Form( NULL, 'upgrade_form', 'post', 'compact' );

$Form->add_crumb( 'upgrade_export' );
$Form->hiddens_by_key( get_memorized( 'action' ), array( 'svn_url', 'svn_folder', 'svn_user', 'svn_password', 'svn_revision' ) );

$Form->begin_form( 'fform', T_('Upgrade from SVN').get_manual_link( 'upgrade-from-svn' ) );

$Form->text_input( 'svn_url', $UserSettings->get( 'svn_upgrade_url' ), 80, T_('URL of repository'), T_('e.g. https://server.com/svn/repository/'), array( 'maxlength' => 300, 'required' => true ) );
$Form->text_input( 'svn_folder', $UserSettings->get( 'svn_upgrade_folder' ), 80, T_('SVN folder'), T_('e.g. trunk/blogs/'), array( 'maxlength' => 300 ) );
$Form->text_input( 'svn_user', $UserSettings->get( 'svn_upgrade_user' ), 32, T_('Login'), '', array( 'maxlength' => 300 ) );
$Form->password_input( 'svn_password', get_param( 'svn_password' ), 32, T_('Password'), '', array( 'maxlength' => 300 ) );
$Form->text_input( 'svn_revision', $UserSettings->get( 'svn_upgrade_revision' ), 7, T_('Revision'), T_('Leave blank to get the latest revision') );

$Form->end_form( array( array( 'submit', 'actionArray[export_svn]', T_( 'Export revision from SVN...' ), 'SaveButton' ) ) );

?>