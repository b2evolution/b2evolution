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
global $action;

switch( $action )
{
	case 'start':

		global $updates;

		$Form = & new Form( NULL, 'upgrade_form', 'post', 'compact' );

		$Form->hiddens_by_key( get_memorized( 'action' ) );

		$Form->begin_form( 'fform', T_('Check for updates') );
		if( empty( $updates ) )
		{
			?><div class="action_messages">
				<div class="log_error" style="text-align:center;font-weight:bold"><?php echo T_( 'There are no any new updates.' ); ?></div>
			</div><?php

			$Form->end_form();
		}
		else
		{
			$update = $updates[0];

			$Form->info( T_( 'Update' ), $update['name'] );
			$Form->info( T_( 'Description' ), $update['description'] );
			$Form->info( T_( 'Version' ), $update['version'] );

			$Form->text_input( 'upd_url', $update['url'], 80, T_('URL'), '<br/><span style="color:red">This is a test implementation. Please enter the URL of the ZIP file to download and install !</span>', array( 'maxlength'=> 300, 'required'=>true ) );

			$upgrade_action = get_upgrade_action( $update['url'] );
			switch( $upgrade_action['action'] )
			{
				case 'download':
					$button = T_( 'Download, unzip & install now!' );
					break;

				case 'unzip':
					$button = T_( 'Unzip & install now!' );
					break;

				case 'install':
					$button = T_( 'Install now!' );
					break;
			}

			global $debug;

			if( array_key_exists( 'status', $upgrade_action ) )
			{
				?><div class="action_messages">
					<div class="log_error" style="text-align:center;font-weight:bold"><?php echo $upgrade_action['status']; ?></div>
				</div><?php

				if( $debug > 0 )
				{
					$button = T_( 'Force \'upgrade\' nonetheless' );
				}
			}

			if( $debug > 0 || !array_key_exists( 'status', $upgrade_action ) )
			{
				$Form->hidden( 'upd_name', $upgrade_action['name'] );

				$Form->end_form( array( array( 'submit', 'actionArray['.$upgrade_action['action'].']', $button, 'SaveButton' ) ) );
			}
		}

		break;
}


/*
 * $Log$
 * Revision 1.6  2009/11/19 12:10:53  efy-maxim
 * Force 'upgrade' for debug mode
 *
 * Revision 1.5  2009/11/15 19:44:02  fplanque
 * minor
 *
 * Revision 1.4  2009/10/22 10:52:57  efy-maxim
 * upgrade - messages
 *
 * Revision 1.3  2009/10/21 14:27:39  efy-maxim
 * upgrade
 *
 * Revision 1.2  2009/10/20 14:38:55  efy-maxim
 * maintenance modulde: downloading - unpacking - verifying destination files - backing up - copying new files - upgrade database using regular script (Warning: it is very unstable version! Please, don't use maintenance modulde, because it can affect your data )
 *
 * Revision 1.1  2009/10/18 20:15:51  efy-maxim
 * 1. backup, upgrade have been moved to maintenance module
 * 2. maintenance module permissions
 *
 */
?>