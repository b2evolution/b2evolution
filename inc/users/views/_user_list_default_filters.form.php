<?php
/**
 * This file implements the UI view to change user group membership from users list.
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


global $admin_url;

$Form = new Form( $admin_url, 'users_groups_checkchanges' );

$Form->switch_template_parts( array(
		'labelclass' => 'control-label col-sm-6',
		'inputstart' => '<div class="controls col-sm-6">',
		'inputstart_radio' => '<div class="controls col-sm-6">',
		'infostart'  => '<div class="controls col-sm-6"><div class="form-control-static">',
	) );

$Form->title_fmt = '<span style="float:right">$global_icons$</span><div>$title$</div>'."\n";

$Form->begin_form( 'fform' );

$Form->add_crumb( 'users' );
$Form->hidden( 'ctrl', 'users' );

// A link to close popup window:
$close_icon = action_icon( TB_('Close this window'), 'close', '', '', 0, 0, array( 'id' => 'close_button', 'class' => 'floatright' ) );

$Form->begin_fieldset( TB_('Change default users list filters...').get_manual_link( 'users-list-default-filters' ).$close_icon );

	// Get filters from config:
	$filters = get_userlist_filters_config();
	$filters_options = array( '' => '---' );
	foreach( $filters as $filter_key => $filter_data )
	{
		$filters_options[ $filter_key ] = $filter_data['label'];
	}

	$userlist_default_filters = $Settings->get( 'userlist_default_filters' );
	$userlist_default_filters = empty( $userlist_default_filters ) ? array() : explode( ',', $userlist_default_filters );
	for( $i = 1; $i <= 10; $i++ )
	{
		$Form->select_input_array( 'filter_'.$i, ( isset( $userlist_default_filters[ $i-1 ] ) ? $userlist_default_filters[ $i-1 ] : NULL ),
			$filters_options, sprintf( TB_('Default filter criteria %d'), $i ) );
	}

$Form->end_fieldset();

$Form->button( array( '', 'actionArray[save_default_filters]', TB_('Save defaults'), 'SaveButton' ) );

$Form->end_form();
?>