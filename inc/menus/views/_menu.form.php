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

$Form->begin_form( 'fform', $creating ?  T_('New Menu') . get_manual_link( 'menu-form' ) : T_('Menu') . get_manual_link( 'menu-form' ) );

	$Form->add_crumb( 'menu' );
	$Form->hidden( 'action',  $creating ? 'create' : 'update' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',menu_ID' : '' ) ) );

	$Form->text_input( 'menu_name', $edited_SiteMenu->get( 'name' ), 50, T_('Name'), '', array( 'maxlength' => 128, 'required' => true ) );

	$locales_options = array();
	foreach( $locales as $locale_key => $locale_data )
	{
		if( $locale_data['enabled'] || $locale_key == $edited_SiteMenu->get( 'locale' ) )
		{
			$locales_options[ $locale_key ] = $locale_key;
		}
	}
	$Form->select_input_array( 'menu_locale', $edited_SiteMenu->get( 'locale' ), $locales_options, T_('Locale') );

	$buttons = array();
	if( $current_User->check_perm( 'options', 'edit' ) )
	{	// Allow to save menu if current User has a permission:
		$buttons[] = array( 'submit', 'submit', ( $creating ? T_('Record') : T_('Save Changes!') ), 'SaveButton' );
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
	 * Generate category line when it has children
	 *
	 * @param object Chapter we want to display
	 * @param integer Level of the category in the recursive tree
	 * @return string HTML
	 */
	function site_menu_entry_line( $SiteMenuEntry, $level )
	{
		global $line_class, $current_User, $Settings, $admin_url;
		global $SiteMenuEntryCache, $current_default_cat_ID;
		global $number_of_posts_in_cat;

		global $Session;
		$result_fadeout = $Session->get( 'fadeout_array' );

		$line_class = $line_class == 'even' ? 'odd' : 'even';

		// Check if current item's row should be highlighted:
		$is_highlighted = ( param( 'highlight_id', 'integer', NULL ) == $SiteMenuEntry->ID ) ||
			( isset( $result_fadeout ) && in_array( $SiteMenuEntry->ID, $result_fadeout ) );

		$r = '';

		// Name
		if( $current_User->check_perm( 'options', 'edit' ) )
		{	// We have permission permission to edit:
			$edit_url = regenerate_url( 'action,ment_ID', 'ment_ID='.$SiteMenuEntry->ID.'&amp;action=edit_entry' );
			$r .= '<td>
					<strong style="padding-left: '.($level).'em;"><a href="'.$edit_url.'" title="'.T_('Edit...').'">'.$SiteMenuEntry->dget( 'text' ).'</a></strong>
				</td>';
		}
		else
		{
			$r .= '<td>
					<strong style="padding-left: '.($level).'em;">'.$SiteMenuEntry->dget( 'text' ).'</strong>
				</td>';
		}

		// Entry type:
		$r .= '<td>'.$SiteMenuEntry->dget( 'type' ).'</td>';

		// Actions
		$r .= '<td class="lastcol shrinkwrap">';
		if( $current_User->check_perm( 'options', 'edit' ) )
		{	// We have permission permission to edit, so display action column:
			$r .= action_icon( T_('Edit...'), 'edit', $edit_url );
			if( $Settings->get('allow_moving_chapters') )
			{ // If moving cats between blogs is allowed:
				$r .= action_icon( T_('Move to a different blog...'), 'file_move', regenerate_url( 'action,cat_ID', 'cat_ID='.$SiteMenuEntry->ID.'&amp;action=move' ), T_('Move') );
			}
			$r .= action_icon( T_('New').'...', 'new', regenerate_url( 'action,ment_ID,blog', 'ment_parent_ID='.$SiteMenuEntry->ID.'&amp;action=new_entry' ) )
						.action_icon( T_('Delete').'...', 'delete', regenerate_url( 'action,ment_ID,blog', 'ment_ID='.$SiteMenuEntry->ID.'&amp;action=delete_entry&amp;'.url_crumb( 'menuentry' ) ) );
		}
		$r .= '</td>';
		$r .=	'</tr>';

		return $r;
	}


	/**
	 * Generate category line when it has no children
	 *
	 * @param object Chapter generic category we want to display
	 * @param integer Level of the category in the recursive tree
	 * @return string HTML
	 */
	function site_menu_entry_no_children( $SiteMenuEntry, $level )
	{
		return '';
	}


	/**
	 * Generate code when entering a new level
	 *
	 * @param int level of the category in the recursive tree
	 * @return string HTML
	 */
	function site_menu_entry_before_level( $level )
	{
		return '';
	}

	/**
	 * Generate code when exiting from a level
	 *
	 * @param int level of the category in the recursive tree
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
			'th' => T_('Entry'),
		);
	$Table->cols[] = array(
			'th' => T_('Entry type'),
			'th_class' => 'shrinkwrap',
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