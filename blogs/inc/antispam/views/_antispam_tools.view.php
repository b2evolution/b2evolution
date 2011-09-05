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
echo '<ul>';
echo '<li><a href="'.regenerate_url('action', 'action=find_spam_comments&amp;'.url_crumb('antispam')).'">'.T_('Find and delete comments matching antispam blacklist').'</a></li>';
echo '<li><a href="'.regenerate_url('action', 'action=find_spam_referers&amp;'.url_crumb('antispam')).'">'.T_('Find and delete all banned hit-log entries').'</a></li>';
echo '</ul>';
$block_item_Widget->disp_template_raw( 'block_end' );


/*
 * $Log$
 * Revision 1.1  2011/09/05 14:57:53  sam2kb
 * Refactor antispam controller
 *
 */
?>