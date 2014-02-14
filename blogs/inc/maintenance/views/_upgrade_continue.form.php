<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009-2013 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $block_item_Widget, $action;

if( isset( $block_item_Widget ) )
{
	$block_item_Widget->disp_template_replaced( 'block_end' );
}

if( ( $action !== 'backup_and_overwrite' ) && ( $action !== 'backup_and_overwrite_svn' ) )
{
	debug_die('Unhandled upgrade action!');
}

$Form = new Form( NULL, 'upgrade_form', 'post', 'compact' );

$Form->hiddens_by_key( get_memorized( 'action' ) );

$Form->begin_form( 'fform', T_('Upgrade') );

echo '<p><b>'.T_('We are ready to perform the upgrade.').'</b></p>';

$Form->end_form( array( array( 'submit', 'actionArray['.$action.']', T_('Backup & Overwrite source files!'), 'SaveButton' ) ) );

?>