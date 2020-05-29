<?php
/**
 * This file implements the UI view for the general settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var GeneralSettings
 */
global $Settings;

global $admin_url;

global $collections_Module;

$Form = new Form( NULL, 'settings_checkchanges' );
$Form->begin_form( 'fform', '',
	// enable all form elements on submit (so values get sent):
	array( 'onsubmit'=>'var es=this.elements; for( var i=0; i < es.length; i++ ) { es[i].disabled=false; };' ) );

$Form->add_crumb( 'statssettings' );
$Form->hidden( 'ctrl', 'stats' );
$Form->hidden( 'tab', 'settings' );
$Form->hidden( 'action', 'update_settings' );

// --------------------------------------------

$Form->begin_fieldset( TB_('Hit & session logging').get_manual_link('hit-logging') );

	$Form->checklist( array(
			array( 'log_public_hits', 1, TB_('on every public page'), $Settings->get('log_public_hits') ),
			array( 'log_admin_hits', 1, TB_('on every admin page'), $Settings->get('log_admin_hits') ) ),
		'log_hits', TB_('Log hits') );

	// TODO: draw a warning sign if set to off
	$Form->radio_input( 'auto_prune_stats_mode', $Settings->get('auto_prune_stats_mode'), array(
			array(
				'value'=>'off',
				'label'=>TB_('Never'),
				'note'=>TB_('Not recommended! Your database will grow very large!'),
				'onclick'=>'jQuery("#auto_prune_stats_container").hide();' ),
			array(
				'value'=>'page',
				'label'=>TB_('Once per day, triggered by any pageload'),
				'note'=>TB_('This is guaranteed to work but uses extra resources with every page displayed.'),
				'onclick'=>'jQuery("#auto_prune_stats_container").show();' ),
			array(
				'value'=>'cron',
				'label'=>TB_('Once per day, with a scheduled job'),
				'note'=>TB_('Recommended if you have your scheduled jobs properly set up.'), 'onclick'=>'jQuery("#auto_prune_stats_container").show();' ) ),
		TB_('Aggregate & Prune'),
		array( 'note' => TB_('Note: Even if you don\'t log hits, you still need to prune sessions!'),
		'lines' => true ) );

	echo '<div id="auto_prune_stats_container">';
	$oldest_session_period = max( $Settings->get( 'auto_prune_stats' ) * 86400, $Settings->get( 'timeout_sessions' ) );
	$Form->text_input( 'auto_prune_stats', $Settings->get( 'auto_prune_stats' ), 5, TB_('Keep detailed logs for'), TB_('days').'. '.sprintf( TB_('Note: <a %s>logged-in Sessions</a> will be kept for %s.'), 'href="'.$admin_url.'?ctrl=usersettings"', seconds_to_period( $oldest_session_period ) ) );
	echo '</div>';

	if( $Settings->get('auto_prune_stats_mode') == 'off' )
	{ // hide the "days" input field, if mode set to off:
		echo '<script>jQuery("#auto_prune_stats_container").hide();</script>';
	}

$Form->end_fieldset();

if( check_user_perm( 'options', 'edit' ) )
{
	$Form->end_form( array( array( 'submit', 'submit', TB_('Save Changes!'), 'SaveButton' ) ) );
}

?>