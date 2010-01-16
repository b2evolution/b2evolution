<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-maxim: Evo Factory / Maxim.
 * @author fplanque: Francois Planque.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $dispatcher;

// Get params from request
$s = param( 's', 'string', '', true );

//Create query
$SQL = new SQL();
$SQL->SELECT( '*' );
$SQL->FROM( 'T_currency' );

if( !empty($s) )
{	// We want to filter on search keyword:
	// Note: we use CONCAT_WS (Concat With Separator) because CONCAT returns NULL if any arg is NULL
	$SQL->WHERE( 'CONCAT_WS( " ", curr_code, curr_name ) LIKE "%'.$DB->escape($s).'%"' );
}

// Create result set:
$Results = new Results( $SQL->get(), 'curr_', '-A');

$Results->Cache = & get_CurrencyCache();

$Results->title = T_('Currencies list');

/*
 * STATUS TD:
 */
function curr_td_enabled( $curr_enabled, $curr_ID )
{

	if( $curr_enabled == true )
	{
		return get_icon('enabled', 'imgtag', array('title'=>T_('The currency is enabled.')) );
	}
	else
	{
		return get_icon('disabled', 'imgtag', array('title'=>T_('The currency is disabled.')) );
	}
}
$Results->cols[] = array(
		'th' => /* TRANS: shortcut for enabled */ T_('En'),
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
		);

	$Results->global_icon( T_('Create a new currency ...'), 'new',
				regenerate_url( 'action', 'action=new'), T_('New currency').' &raquo;', 3, 4  );
}

$Results->display();

/*
 * $Log$
 * Revision 1.10  2010/01/16 14:16:32  efy-asimo
 * Currencies/Countries cosmetics and regenerate_url after Enable/Disable
 *
 * Revision 1.9  2010/01/15 17:27:33  efy-asimo
 * Global Settings > Currencies - Add Enable/Disable column
 *
 * Revision 1.8  2010/01/03 12:03:17  fplanque
 * More crumbs...
 *
 * Revision 1.7  2009/09/26 12:00:43  tblue246
 * Minor/coding style
 *
 * Revision 1.6  2009/09/25 07:33:14  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.5  2009/09/12 18:57:27  efy-sergey
 * Added a search field to the currency tables
 *
 * Revision 1.4  2009/09/12 18:52:05  efy-sergey
 * Changed query creation to using an SQL object
 *
 * Revision 1.3  2009/09/02 23:29:34  fplanque
 * doc
 *
 */
?>