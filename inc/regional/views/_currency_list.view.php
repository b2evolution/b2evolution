<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $dispatcher;

// Get params from request
$s = param( 's', 'string', '', true );

//Create query
$SQL = new SQL();
$SQL->SELECT( '*' );
$SQL->FROM( 'T_regional__currency' );

if( !empty($s) )
{	// We want to filter on search keyword:
	// Note: we use CONCAT_WS (Concat With Separator) because CONCAT returns NULL if any arg is NULL
	$SQL->WHERE( 'CONCAT_WS( " ", curr_code, curr_name ) LIKE "%'.$DB->escape($s).'%"' );
}

// Create result set:
$Results = new Results( $SQL->get(), 'curr_', '-A');

$Results->Cache = & get_CurrencyCache();

$Results->title = T_('Currencies list').get_manual_link('currencies_list');

/*
 * STATUS TD:
 */
function curr_td_enabled( $curr_enabled, $curr_ID )
{
	global $dispatcher;

	$r = '';

	if( $curr_enabled == true )
	{
		$r .= action_icon( T_('Disable the currency!'), 'bullet_full',
										regenerate_url( 'action', 'action=disable_currency&amp;curr_ID='.$curr_ID.'&amp;'.url_crumb('currency') ) );
	}
	else
	{
		$r .= action_icon( T_('Enable the currency!'), 'bullet_empty',
										regenerate_url( 'action', 'action=enable_currency&amp;curr_ID='.$curr_ID.'&amp;'.url_crumb('currency') ) );
	}

	return $r;

}
$Results->cols[] = array(
		'th' => /* TRANS: shortcut for enabled */ T_('En'),
		'th_title' => T_('Enabled'),
		'order' => 'curr_enabled',
		'td' => '%curr_td_enabled( #curr_enabled#, #curr_ID# )%',
		'td_class' => 'center'
	);

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_currencies( & $Form )
{
	$Form->text( 's', get_param('s'), 30, T_('Search'), '', 255 );
}

$Results->filter_area = array(
	'callback' => 'filter_currencies',
	'presets' => array(
		'all' => array( T_('All'), '?ctrl=currencies' ),
		)
	);

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$Results->cols[] = array(
							'th' => T_('Code'),
							'order' => 'curr_code',
							'td' => '<strong><a href="'.$dispatcher.'?ctrl=currencies&amp;curr_ID=$curr_ID$&amp;action=edit" title="'.
											T_('Edit this currency...').'">$curr_code$</a></strong>',
							'td_class' => 'center',
						);
}
else
{	// View only:
	$Results->cols[] = array(
							'th' => T_('Code'),
							'order' => 'curr_code',
							'td' => '<strong>$curr_code$</strong>',
							'td_class' => 'center',
						);

}

$Results->cols[] = array(
						'th' => T_('Shortcut'),
						'order' => 'curr_shortcut',
						'td' => '$curr_shortcut$',
						'td_class' => 'center',
					);

$Results->cols[] = array(
						'th' => T_('Name'),
						'order' => 'curr_name',
						'td' => '$curr_name$',
					);

/*
 * ACTIONS TD:
 */
function curr_td_actions($curr_enabled, $curr_ID )
{
	global $dispatcher;

	$r = '';

	if( $curr_enabled == true )
	{
		$r .= action_icon( T_('Disable the currency!'), 'deactivate', 
										regenerate_url( 'action', 'action=disable_currency&amp;curr_ID='.$curr_ID.'&amp;'.url_crumb('currency') ) );
	}
	else
	{
		$r .= action_icon( T_('Enable the currency!'), 'activate', 
										regenerate_url( 'action', 'action=enable_currency&amp;curr_ID='.$curr_ID.'&amp;'.url_crumb('currency') ) );
	}
	$r .= action_icon( T_('Edit this currency...'), 'edit',
										regenerate_url( 'action', 'curr_ID='.$curr_ID.'&amp;action=edit' ) );
	$r .= action_icon( T_('Duplicate this currency...'), 'copy',
										regenerate_url( 'action', 'curr_ID='.$curr_ID.'&amp;action=new' ) );
	$r .= action_icon( T_('Delete this currency!'), 'delete',
										regenerate_url( 'action', 'curr_ID='.$curr_ID.'&amp;action=delete&amp;'.url_crumb('currency') ) );

	return $r;
}
if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$Results->cols[] = array(
			'th' => T_('Actions'),
			'th_class' => 'shrinkwrap',
			'td' => '%curr_td_actions( #curr_enabled#, #curr_ID# )%',
			'td_class' => 'shrinkwrap',
		);

	$Results->global_icon( T_('Create a new currency...'), 'new',
				regenerate_url( 'action', 'action=new'), T_('New currency').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
}

$Results->display();

?>