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

$SQL = new SQL( 'Get templates' );

$SQL->SELECT( 'template.tpl_ID, template.tpl_context, template.tpl_name, template.tpl_code, template.tpl_translates_tpl_ID, template.tpl_locale,
		template.tpl_owner_grp_ID, owner.grp_name AS owner_name, base.tpl_name AS tpl_base_name' );
$SQL->FROM( 'T_templates template' );
$SQL->FROM_add( 'LEFT JOIN T_templates base ON template.tpl_translates_tpl_ID = base.tpl_ID' );
$SQL->FROM_add( 'LEFT JOIN T_groups owner ON template.tpl_owner_grp_ID = owner.grp_ID' );

$Results = new Results( $SQL->get(), 'template_', '----A' );

$Results->title = T_('Templates').' ('.$Results->get_total_rows().')' . get_manual_link( 'templates-list' );

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

$Results->display();

?>
