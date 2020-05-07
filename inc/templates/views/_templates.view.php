<?php
/**
 * This file display the templates list
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package templates
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $admin_url;

$highlight = param( 'highlight', 'string', NULL );

// Get params from request
$name_code = param( 'q', 'string', '', true );
$context = param( 'context', 'string', NULL, true );
$owner = param( 'owner', 'integer', NULL, true );

$SQL = new SQL( 'Get templates' );
$SQL->SELECT( 'template.tpl_ID, template.tpl_context, template.tpl_name, template.tpl_code, template.tpl_translates_tpl_ID, template.tpl_locale,
		template.tpl_owner_grp_ID, owner.grp_name AS owner_name, base.tpl_name AS tpl_base_name' );
$SQL->FROM( 'T_templates template' );
$SQL->FROM_add( 'LEFT JOIN T_templates base ON template.tpl_translates_tpl_ID = base.tpl_ID' );
$SQL->FROM_add( 'LEFT JOIN T_groups owner ON template.tpl_owner_grp_ID = owner.grp_ID' );

if( !empty( $name_code ) )
{
	$SQL->WHERE_and( '( template.tpl_name LIKE '.$DB->quote( '%'.$name_code.'%' ).' OR template.tpl_code LIKE '.$DB->quote( '%'.$name_code.'%').' )' );
}
if( !empty( $context ) )
{
	$SQL->WHERE_and( 'template.tpl_context = '.$DB->quote( $context ) );
}
if( !empty( $owner ) )
{
	$SQL->WHERE_and( 'template.tpl_owner_grp_ID = '.$DB->quote( $owner ) );
}
elseif( $owner === 0 )
{
	$SQL->WHERE_and( 'template.tpl_owner_grp_ID IS NULL' );
}

$Results = new Results( $SQL->get(), 'template_', '----A' );

$Results->title = T_('Templates').' ('.$Results->get_total_rows().')' . get_manual_link( 'templates-list' );

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_templates_list( & $Form )
{
	// Name / Code:
	$Form->text_input( 'q', get_param( 'q' ), 20, T_('Name / Code'), '', array( 'maxlength' => 50 ) );

	// Context:
	$field_options = array( NULL => T_('All contexts') ) + get_template_contexts();
	$Form->select_input_array( 'context', get_param( 'context' ), $field_options, T_('Context'), '', array( 'force_keys_as_values' => false ) );

	// Owner:
	$GroupCache = & get_GroupCache();
	$field_options = array( NULL => T_('All owners'), '0' => 'SYSTEM' ) + $GroupCache->get_option_array();
	$Form->select_input_array( 'owner', get_param( 'owner' ), $field_options, T_('Owner'), '', array( 'force_keys_as_values' => true ) );	
}
$Results->filter_area = array(
		'callback' => 'filter_templates_list',
		'url_ignore' => 'results_template_per_page,results_templates_page',
	);

$Results->register_filter_preset( 'all', T_('All'), '?ctrl=templates' );

$Results->cols[] = array(
		'th' => T_('Context'),
		'td' => '$tpl_context$',
		'order' => 'tpl_context, tpl_base_name, tpl_name, tpl_locale, tpl_code',
	);

$Results->cols[] = array(
		'th' => T_('Name'),
		'td' => '<a href="'.$admin_url.'?ctrl=templates&amp;action=edit&amp;tpl_ID=$tpl_ID$">$tpl_name$</a>',
		'order' => 'tpl_name, tpl_base_name, tpl_locale, tpl_code',
	);

$Results->cols[] = array(
		'th' => T_('Code'),
		'td' => '<a href="'.$admin_url.'?ctrl=templates&amp;action=edit&amp;tpl_ID=$tpl_ID$">$tpl_code$</a>',
		'order' => 'tpl_code, tpl_base_name, tpl_name, tpl_locale',
	);

function td_template_owner( $owner_name = NULL )
{
	if( is_null( $owner_name ) )
	{
		return T_('System');
	}
	else
	{
		return $owner_name;
	}
}
$Results->cols[] = array(
	'th' => T_('Owner'),
	'td' => '%td_template_owner( #owner_name# )%',
	'order' => 'owner_name, tpl_base_name, tpl_name, tpl_locale, tpl_code',
);

$Results->cols[] = array(
		'th' => T_('Translation of'),
		'td' => '<a href="'.$admin_url.'?ctrl=templates&amp;action=edit&amp;tpl_ID=$tpl_translates_tpl_ID$">$tpl_base_name$</a>',
		'order' => 'tpl_base_name, tpl_name, tpl_locale, tpl_code',
	);

$Results->cols[] = array(
		'th' => T_('Locale'),
		'td' => '%locale_flag( #tpl_locale#, "", "flag", "", false )% $tpl_locale$',
		'order' => 'tpl_locale, tpl_base_name, tpl_name, tpl_code',
		'th_class' => 'shrinkwrap',
		'td_class' => 'nowrap',
	);

function td_template_actions( & $row )
{
	global $admin_url;

	$action_icons = action_icon( TS_('Edit this template...'), 'properties', $admin_url.'?ctrl=templates&amp;action=edit&amp;tpl_ID='.$row->tpl_ID );
	$action_icons .= action_icon( TB_('Duplicate / Translate').'...', 'duplicate', $admin_url.'?ctrl=templates&amp;action=copy&amp;tpl_ID='.$row->tpl_ID.'&amp;'.url_crumb( 'template') );
	if( $row->tpl_owner_grp_ID )
	{
		$action_icons .= action_icon( T_('Delete this template!'), 'delete', regenerate_url( 'tpl_ID,action', 'tpl_ID='.$row->tpl_ID.'&amp;action=delete&amp;'.url_crumb( 'template' ) ) );
	}

	return $action_icons;
}
if( $current_User->check_perm( 'options', 'edit' ) )
{
	$Results->cols[] = array(
		'th' => T_('Actions'),
		'th_class' => 'shrinkwrap small',
		'td_class' => 'shrinkwrap',
		'td' => '%td_template_actions({row})%'
	);

	$Results->global_icon( T_('New template'), 'new', regenerate_url( 'action', 'action=new' ), T_('New template').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
}

// Highlight rows:
$highlight_fadeout = empty( $highlight ) ? array() : array( 'tpl_code' => array( $highlight ) );

$Results->display( NULL, $highlight_fadeout );

?>
