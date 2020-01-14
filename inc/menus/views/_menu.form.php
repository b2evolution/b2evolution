<?php
/**
 * This file display the menu form
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $edited_SiteMenu, $locales, $AdminUI;

// Determine if we are creating or updating...
global $action;
$creating = is_create_action( $action );

$Form = new Form( NULL, 'menu_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Cancel editing').'!', 'close', regenerate_url( 'action,menu_ID,blog' ) );

if( $action == 'copy' )
{
	$fieldset_title = T_('Duplicate menu').get_manual_link( 'menu_form');
}
else
{
	$fieldset_title = $creating ?  T_('New Menu') . get_manual_link( 'menu-form' ) : T_('Menu') . get_manual_link( 'menu-form' );
}

$Form->begin_form( 'fform', $fieldset_title );

	$Form->add_crumb( 'menu' );
	if( $action == 'copy' )
	{
		$Form->hidden( 'action', 'duplicate' );
		$Form->hiddens_by_key( get_memorized( 'action' ) );
	}
	else
	{
		$Form->hidden( 'action',  $creating ? 'create' : 'update' );
		$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',menu_ID' : '' ) ) );
	}
	

	$Form->text_input( 'menu_name', $edited_SiteMenu->get( 'name' ), 50, T_('Name'), '', array( 'maxlength' => 128, 'required' => true ) );

	$parent_menu_options = array( NULL => '('.TB_('No Parent').')' );
	$SQL = new SQL('Get possible parent menus');
	$SQL->SELECT( 'menu_ID, menu_name' );
	$SQL->FROM( 'T_menus__menu' );
	$SQL->WHERE( 'menu_parent_ID IS NULL' );
	$SQL->WHERE_and( 'NOT menu_ID ='.$DB->quote( $edited_SiteMenu->ID ) );
	$SQL->ORDER_BY( 'menu_name ASC' );
	$parent_menu_options += $DB->get_assoc( $SQL->get() );
	$Form->select_input_array( 'menu_parent_ID', $edited_SiteMenu->get('parent_ID'), $parent_menu_options, T_('Parent'), NULL, array( 'force_keys_as_values' => true ) );

	$locales_options = array();
	foreach( $locales as $locale_key => $locale_data )
	{
		if( $locale_data['enabled'] || $locale_key == $edited_SiteMenu->get( 'locale' ) )
		{
			$locales_options[ $locale_key ] = $locale_key;
		}
	}
	$Form->select_input_array( 'menu_locale', $edited_SiteMenu->get( 'locale' ), $locales_options, T_('Locale') );

	if( $edited_SiteMenu->ID == 0 )
	{	// Suggest menu entries based on existing collections:
		$SectionCache = & get_SectionCache();
		$SectionCache->load_all();
		$BlogCache = & get_BlogCache();
		$suggested_menu_entries = array();
		foreach( $SectionCache->cache as $Section )
		{
			if( $Section->ID == 1 )
			{	// Skip "No Section" in order to add it at the end:
				continue;
			}
			$suggested_menu_entries[] = array( 'menu_entries[sec_'.$Section->ID.']', $Section->get( 'name' ), $Section->get( 'name' ), 1 );
			$BlogCache->clear();
			$BlogCache->load_where( 'blog_sec_ID = '.$Section->ID );
			foreach( $BlogCache->cache as $section_Blog )
			{
				$suggested_menu_entries[] = array( 'menu_entries[coll_'.$section_Blog->ID.'_'.$Section->ID.']', $section_Blog->get( 'shortname' ), $section_Blog->get( 'shortname' ), 1, NULL, NULL, NULL, NULL, array( 'style' => 'margin-left:20px' ) );
			}
		}
		// Display collections from "No Section" at the end:
		$BlogCache->clear();
		$BlogCache->load_where( 'blog_sec_ID = 1' );
		foreach( $BlogCache->cache as $section_Blog )
		{
			$suggested_menu_entries[] = array( 'menu_entries[coll_'.$section_Blog->ID.']', $section_Blog->get( 'shortname' ), $section_Blog->get( 'shortname' ), 1 );
		}
		// Contact menu entry:
		$suggested_menu_entries[] = array( 'menu_entries[#contact#]', '#contact#', T_('Contact'), 1 );
		$Form->checklist( $suggested_menu_entries, '', T_('Menu entries') );
	}

	$buttons = array();
	if( $current_User->check_perm( 'options', 'edit' ) )
	{	// Allow to save menu if current User has a permission:
		if( $action == 'copy' )
		{
			$buttons[] = array( 'submit', 'submit', sprintf( T_('Save and duplicate all settings from %s'), $edited_SiteMenu->get( 'name' ) ), 'SaveButton' );
		}
		else
		{
			$buttons[] = array( 'submit', 'submit', ( $creating ? T_('Record') : T_('Save Changes!') ), 'SaveButton' );
		}
	}

$Form->end_form( $buttons );

if( $edited_SiteMenu->ID > 0 )
{	// Display menu entries:
	$SiteMenuEntryCache = & get_SiteMenuEntryCache();

	$callbacks = array(
		'line'         => 'site_menu_entry_line',
		'no_children'  => 'site_menu_entry_no_children',
		'before_level' => 'site_menu_entry_before_level',
		'after_level'  => 'site_menu_entry_after_level'
	);

	/**
	 * Generate Site Menu Entry line when it has children
	 *
	 * @param object SiteMenuEntry we want to display
	 * @param integer Level of the Site Menu Entry in the recursive tree
	 * @return string HTML
	 */
	function site_menu_entry_line( $SiteMenuEntry, $level )
	{
		global $line_class, $current_User, $Settings, $admin_url;
		global $SiteMenuEntryCache;

		global $Session;
		$result_fadeout = $Session->get( 'fadeout_array' );

		$line_class = $line_class == 'even' ? 'odd' : 'even';

		// Check if current item's row should be highlighted:
		$is_highlighted = ( param( 'highlight_id', 'integer', NULL ) == $SiteMenuEntry->ID ) ||
			( isset( $result_fadeout ) && in_array( $SiteMenuEntry->ID, $result_fadeout ) );

		$r = '';

		// Order:
		$r .= '<td class="right">'.$SiteMenuEntry->dget( 'order' ).'</td>';

		// Name:
		if( $current_User->check_perm( 'options', 'edit' ) )
		{	// We have permission permission to edit:
			$edit_url = regenerate_url( 'action,ment_ID', 'ment_ID='.$SiteMenuEntry->ID.'&amp;action=edit_entry' );
			$r .= '<td class="nowrap">
					<strong style="padding-left: '.($level).'em;"><a href="'.$edit_url.'" title="'.T_('Edit...').'">'.$SiteMenuEntry->get_text().'</a></strong>
				</td>';
		}
		else
		{
			$r .= '<td class="nowrap">
					<strong style="padding-left: '.($level).'em;">'.$SiteMenuEntry->dget( 'text' ).'</strong>
				</td>';
		}

		// Entry type:
		$r .= '<td class="nowrap">'.get_site_menu_type_title( $SiteMenuEntry->get( 'type' ) ).'</td>';

		// Destination:
		$destination = '';
		if( $SiteMenuEntry->get( 'type' ) == 'url' )
		{	// Destination to any URL:
			$destination = get_link_tag( $SiteMenuEntry->get_url() );
		}
		elseif( $SiteMenuEntry->get_url() )
		{	// Destination to other pages:
			$destination = get_link_tag( $SiteMenuEntry->get_url(), $SiteMenuEntry->get_text() );
		}
		$r .= '<td class="nowrap">'.$destination.'</td>';

		// Actions
		$r .= '<td class="lastcol shrinkwrap">';
		if( $current_User->check_perm( 'options', 'edit' ) )
		{	// We have permission permission to edit, so display action column:
			$r .= action_icon( T_('Edit...'), 'edit', $edit_url );
			$r .= action_icon( T_('New').'...', 'new', regenerate_url( 'action,ment_ID,blog', 'ment_parent_ID='.$SiteMenuEntry->ID.'&amp;action=new_entry' ) )
						.action_icon( T_('Delete').'...', 'delete', regenerate_url( 'action,ment_ID,blog', 'ment_ID='.$SiteMenuEntry->ID.'&amp;action=delete_entry&amp;'.url_crumb( 'menuentry' ) ) );
		}
		$r .= '</td>';
		$r .=	'</tr>';

		return $r;
	}


	/**
	 * Generate Site Menu Entry line when it has no children
	 *
	 * @param object SiteMenuEntry generic Site Menu Entry we want to display
	 * @param integer Level of the Site Menu Entry in the recursive tree
	 * @return string HTML
	 */
	function site_menu_entry_no_children( $SiteMenuEntry, $level )
	{
		return '';
	}


	/**
	 * Generate code when entering a new level
	 *
	 * @param int level of the Site Menu Entry in the recursive tree
	 * @return string HTML
	 */
	function site_menu_entry_before_level( $level )
	{
		return '';
	}

	/**
	 * Generate code when exiting from a level
	 *
	 * @param int level of the Site Menu Entry in the recursive tree
	 * @return string HTML
	 */
	function site_menu_entry_after_level( $level )
	{
		return '';
	}

	$Table = new Table();

	$Table->title = T_('Menu').': '.$edited_SiteMenu->get( 'name' ).' '.locale_flag( $edited_SiteMenu->get( 'locale' ), '', 'flag', '', false ).get_manual_link( 'menu-entries-list' );

	$Table->global_icon( T_('New menu entry'), 'new', regenerate_url( 'action,blog', 'action=new_entry' ), T_('New menu entry').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );

	$Table->cols[] = array(
			'th' => T_('Order'),
			'th_class' => 'shrinkwrap',
		);
	$Table->cols[] = array(
			'th' => T_('Entry'),
			'th_class' => 'shrinkwrap',
		);
	$Table->cols[] = array(
			'th' => T_('Entry type'),
			'th_class' => 'shrinkwrap',
		);
	$Table->cols[] = array(
			'th' => T_('Destination'),
		);
	if( $current_User->check_perm( 'options', 'edit' ) )
	{	// We have permission to edit, so display action column:
		$Table->cols[] = array(
				'th' => T_('Actions'),
				'th_class' => 'shrinkwrap',
			);
	}

	$Table->display_init();

	$results_params = $AdminUI->get_template( 'Results' );

	echo $results_params['before'];

	$Table->display_head();

	echo $Table->params['content_start'];

	$Table->display_list_start();

		$Table->display_col_headers();

		$Table->display_body_start();

		echo $SiteMenuEntryCache->recurse( $callbacks, $edited_SiteMenu->ID, NULL, 0, 0, array( 'sorted' => true ) );

		$Table->display_body_end();

	$Table->display_list_end();

	echo $Table->params['content_end'];

	echo $results_params['after'];
}
?>
