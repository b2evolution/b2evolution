<?php
/**
 * This file implements the UI view for the test a cron job execution.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $basepath, $cron_subdir, $Settings, $is_cli, $localtimenow, $time_difference, $result_status, $result_message;

$block_item_Widget = new Widget( 'block_item' );
$block_item_Widget->title = T_('Test cron job execution').get_manual_link( 'scheduled-jobs-test' );
$block_item_Widget->disp_template_replaced( 'block_start' );

echo '<h1>Cron exec</h1>'.
	'<p>This script will execute the next task in the cron queue.
		You should normally call it with the CLI (command line interface) version of PHP
		and automate that call through a cron.</p>';

require_once $basepath.$cron_subdir.'cron_exec.php';

$block_item_Widget->disp_template_raw( 'block_end' );
?>