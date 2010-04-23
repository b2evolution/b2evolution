<?php
/**
 * This file display the slugs list
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author evfy-asimo: Attila Simo.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Slug
 */
global $Sug, $current_User;

$SQL = new SQL();

$SQL->SELECT( 'slug_ID, slug_title, slug_type, slug_itm_ID as target_ID, post_title as target_title' );
$SQL->FROM( 'T_slug LEFT OUTER JOIN T_items__item ON slug_itm_ID = post_ID' );

if( get_param( 'slug_filter' ) )
{ // add slug_title filter
	$SQL->WHERE_and( 'slug_title LIKE '.$DB->quote( '%'.get_param('slug_filter').'%' ) );
}
switch( get_param( 'slug_ftype' ) )
{ // add filter for item type
	case 'item':
		if( get_param( 'slug_fobject') )
		{ // add item object filter
			$SQL->WHERE_and( 'slug_itm_ID = '.get_param( 'slug_fobject' ) );
		}
		else
		{
			$SQL->WHERE_and( 'slug_type = '.get_param( 'slug_ftype' ) );
		}
	break;
}

// Create result set:
$Results = new Results( $SQL->get(), 'slug_', 'A' );

$Results->title = T_('Slugs').' ('.$Results->total_rows.')';

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_slugs( & $Form )
{
	$Form->text_input( 'slug_filter', get_param('slug_filter'), 40, T_('Slug'), '', array( 'maxlength'=>253 ) );
}
$Results->filter_area = array(
	'callback' => 'filter_slugs',
	'url_ignore' => 'slug_filter',
	'presets' => array(
		'all' => array( T_('All'), '?ctrl=slugs' ),
		)
	);

$Results->cols[] = array(
			'th' => T_('Slug title'),
			'th_class' => 'small',
			'td_class' => 'small',
			'order' => 'slug_title',
			'td' => '$slug_title$',
		);

$Results->cols[] = array(
			'th' => T_('Type'),
			'th_class' => 'small',
			'order' => 'slug_type',
			'td' => '$slug_type$',
			'td_class' => 'small',
		);

/**
 * Get a link to the target object 
 * 
 * @param integer target object ID
 * @param string target object name or title
 * @param string target object type
 * @return string target link if exists, target title otherwise
 */
function get_target_link( $target_ID, $target_title, $type )
{
	global $admin_url;

	if( $type == 'item' )
	{
		return sprintf( '<a href="'.$admin_url.'?ctrl=items&action=edit&p=%d">%s</a>', $target_ID, $target_title );
	}
	return $target_title;
}
$Results->cols[] = array(
			'th' => T_('Target'),
			'th_class' => 'small',
			'order' => 'target_title',
			'td' => '%get_target_link(#target_ID#,#target_title#,#slug_type#)%',
			'td_class' => 'small center',
		);

if( $current_User->check_perm( 'slugs', 'edit' ) )
{
	$Results->cols[] = array(
				'th' => T_('Actions'),
				'th_class' => 'shrinkwrap small',
				'td_class' => 'shrinkwrap',
				'td' => action_icon( TS_('Edit this slug...'), 'properties',
		        		'admin.php?ctrl=slugs&amp;slug_ID=$slug_ID$&amp;action=edit' )
		                 .action_icon( T_('Delete this slug!'), 'delete',
		                  regenerate_url( 'slug_ID,action', 'slug_ID=$slug_ID$&amp;action=delete&amp;'.url_crumb('slug') ) ),
						);

	$Results->global_icon( T_('Add a new slug...'), 'new', regenerate_url( 'action', 'action=new'), T_('New slug').' &raquo;', 3, 4  );
}

$Results->display();
?>