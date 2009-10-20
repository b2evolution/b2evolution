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
 * @var action
 */
global $action, $updates, $upgrade_dir;

$Form = & new Form( NULL, 'upgrade_form', 'post', 'compact' );

$Form->hiddens_by_key( get_memorized( 'action' ) );

switch( $action )
{
	case 'start':
	default:

		$Form->begin_form( 'fform', T_('Check for updates') );
		if( empty( $updates ) )
		{
			$Form->info( T_( 'Updates' ), T_( 'There are no any new updates.' ) );

			$Form->end_form();
		}
		else
		{
			$update = $updates[0];

			$Form->info( T_( 'Updates' ), T_( 'There is a new update!' ), '<br/><br/><b>Name:</b> '.$update['name'].
																		'<br/><b>Description:</b> '.$update['description'].
																		'<br><b>Version:</b> '.$update['version'] );

			$Form->text_input( 'upd_url', $update['url'], 80, T_('URL'), '<br/><span style="color:red">This is a test implementation. Please enter the URL of the ZIP file to download and install !</span>', array( 'maxlength'=> 300, 'required'=>true ) );

			$Form->end_form( array( array( 'submit', 'actionArray[download]', T_('Download'), 'SaveButton' ),
														array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
		}

		break;

	case 'download':

		if( !empty( $upgrade_dir ) )
		{
			$Form->begin_form( 'fform', T_('Backup and upgrade') );

			$Form->info( '', '', 'The backup form with checkboxes can be displayed here!' );

			$Form->hidden( 'upgrade_dir', $upgrade_dir );

			$Form->end_form( array( array( 'submit', 'actionArray[upgrade]', T_('Upgrade'), 'SaveButton' ),
														array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
		}

		break;

	case 'upgrade':

		// TODO: display something here

		break;
}


/*
 * $Log$
 * Revision 1.2  2009/10/20 14:38:55  efy-maxim
 * maintenance modulde: downloading - unpacking - verifying destination files - backing up - copying new files - upgrade database using regular script (Warning: it is very unstable version! Please, don't use maintenance modulde, because it can affect your data )
 *
 * Revision 1.1  2009/10/18 20:15:51  efy-maxim
 * 1. backup, upgrade have been moved to maintenance module
 * 2. maintenance module permissions
 *
 */
?>