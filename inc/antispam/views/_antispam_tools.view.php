<?php
/**
 * This file implements the UI controller for the antispam tools.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


$block_item_Widget = new Widget( 'block_item' );
$block_item_Widget->title = T_('Antispam tools').get_manual_link( 'antispam-tools' );
$block_item_Widget->disp_template_replaced( 'block_start' );
// fp>sam2kb: This is a fast way to make a big mistake. Please add a confirmation, javascript at the very least, warning this CANNOT BE UNDONE.
// Ideally you should show a list of what's goign to be deleted first and let the user confirm.
// Best of all would be to be able to uncheck some deletes before validating.
echo '<ul>';
echo '<li><a href="'.regenerate_url('action', 'action=find_spam_comments&amp;'.url_crumb('antispam')).'">'.T_('Find and delete comments matching antispam blacklist!').'</a></li>';
echo '<li><a href="'.regenerate_url('action', 'action=find_spam_referers&amp;'.url_crumb('antispam')).'">'.T_('Find and delete all banned hit-log entries!').'</a></li>';
echo '<li><a href="'.regenerate_url('action,tool', 'tool=bankruptcy').'">'.T_('Declare comment spam bankruptcy...').'</a></li>';
echo '</ul>';
$block_item_Widget->disp_template_raw( 'block_end' );

?>