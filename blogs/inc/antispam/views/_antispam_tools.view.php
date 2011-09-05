<?php
/**
 * This file implements the UI controller for the antispam tools.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


$current_User->check_perm('options', 'edit', true);

$block_item_Widget = new Widget( 'block_item' );
$block_item_Widget->title = T_('Antispam tools');
$block_item_Widget->disp_template_replaced( 'block_start' );
// fp>sam2kb: This is a fast way to make a big mistake. Please add a confirmation, javascript at the very least, warning this CANNOT BE UNDONE.
// Ideally you should show a list of what's goign to be deleted first and let the user confirm.
// Best of all would be to be able to uncheck some deletes before validating.
echo '<ul>';
echo '<li><a href="'.regenerate_url('action', 'action=find_spam_comments&amp;'.url_crumb('antispam')).'">'.T_('Find and delete comments matching antispam blacklist').'</a></li>';
echo '<li><a href="'.regenerate_url('action', 'action=find_spam_referers&amp;'.url_crumb('antispam')).'">'.T_('Find and delete all banned hit-log entries').'</a></li>';
echo '</ul>';
$block_item_Widget->disp_template_raw( 'block_end' );


/*
 * $Log$
 * Revision 1.2  2011/09/05 23:00:25  fplanque
 * minor/doc/cleanup/i18n
 *
 * Revision 1.1  2011/09/05 14:57:53  sam2kb
 * Refactor antispam controller
 *
 */
?>