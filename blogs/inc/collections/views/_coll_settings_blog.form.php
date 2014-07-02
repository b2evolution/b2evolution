<?php
/**
 * This file implements the Form for the all blogs settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _coll_settings_blog.form.php 7043 2014-07-02 08:35:45Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var User
 */
global $current_User;
/**
 * @var GeneralSettings
 */
global $Settings;

global $dispatcher;

global $collections_Module;

$Form = new Form( NULL, 'settings_checkchanges' );
$Form->begin_form( 'fform', '',
	// enable all form elements on submit (so values get sent):
	array( 'onsubmit'=>'var es=this.elements; for( var i=0; i < es.length; i++ ) { es[i].disabled=false; };' ) );

$Form->add_crumb( 'collectionsettings' );
$Form->hidden( 'ctrl', 'collections' );
$Form->hidden( 'tab', 'blog_settings' );
$Form->hidden( 'action', 'update_settings_blog' );

// --------------------------------------------

if( isset($collections_Module) )
{
	$Form->begin_fieldset( T_('Display options').get_manual_link('collections-display-options') );

	$BlogCache = & get_BlogCache();

		$Form->select_input_object( 'default_blog_ID', $Settings->get('default_blog_ID'), $BlogCache, T_('Default blog to display'), array(
				'note' => T_('This blog will be displayed on index.php.').' <a href="'.$dispatcher.'?ctrl=collections&action=new">'.T_('Create new blog').' &raquo;</a>',
				'allow_none' => true,
				'class' => '',
				'loop_object_method' => 'get_maxlen_name',
				'onchange' => '' )  );

		$Form->select_input_options( 'blogs_order_by', array_to_option_list( get_coll_sort_options(), $Settings->get('blogs_order_by') ), T_('Order blogs by'), T_('Select default blog list order.') );

		$Form->select_input_options( 'blogs_order_dir', array_to_option_list(
				array( 'ASC' => T_('Ascending'), 'DESC' => T_('Descending') ), $Settings->get('blogs_order_dir') ), T_('Order direction'), T_('Select default blog list order direction.') );

	$Form->end_fieldset();
}

// --------------------------------------------

$Form->begin_fieldset( T_('Caching').get_manual_link('collections-caching-settings') );

	$Form->checkbox_input( 'general_cache_enabled', $Settings->get('general_cache_enabled'), T_('Enable general cache'), array( 'note'=>T_('Cache rendered pages that are not controlled by a skin. See Blog Settings for skin output caching.') ) );

	$cache_note = '('.T_( 'See Blog Settings for existing' ).')';
	$Form->checklist( array(
			array( 'newblog_cache_enabled', 1, T_( 'Enable page cache for NEW blogs' ), $Settings->get('newblog_cache_enabled'), false, $cache_note ),
			array( 'newblog_cache_enabled_widget', 1, T_( 'Enable widget cache for NEW blogs' ), $Settings->get('newblog_cache_enabled_widget'), false, $cache_note )
			), 'new_blogs_cahe', T_( 'Enable for new blogs' ) );

$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( T_('After each new post or comment...').get_manual_link('after_each_post_settings') );
	$Form->radio_input( 'outbound_notifications_mode', $Settings->get('outbound_notifications_mode'),
		array(
			array( 'value'=>'off', 'label'=>T_('Off'), 'note'=>T_('No notification about your new content will be sent out.') ),
			array( 'value'=>'immediate', 'label'=>T_('Immediate'), 'note'=>T_('This is guaranteed to work but may create an annoying delay after each post or comment publication.') ),
			array( 'value'=>'cron', 'label'=>T_('Asynchronous'), 'note'=>T_('Recommended if you have your scheduled jobs properly set up.') )
		),
		T_('Outbound pings & email notifications'),
		array( 'lines' => true ) );
$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( T_('Categories').get_manual_link('categories_global_settings'), array( 'id'=>'categories') );
	$Form->checkbox_input( 'allow_moving_chapters', $Settings->get('allow_moving_chapters'), T_('Allow moving categories'), array( 'note' => T_('Check to allow moving categories accross blogs. (Caution: can break pre-existing permalinks!)' ) ) );
	$Form->radio_input( 'chapter_ordering', $Settings->get('chapter_ordering'), array(
					array( 'value'=>'alpha', 'label'=>T_('Alphabetical') ),
					array( 'value'=>'manual', 'label'=>T_('Manual ') ),
			 ), T_('Ordering of categories') );
$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( T_('Cross posting').get_manual_link('collections-cross-posting-settings') );
	$Form->checklist( array(
		array( 'cross_posting', 1, T_('Allow cross-posting posts to several blogs'), $Settings->get('cross_posting'), false, T_('(Extra cats in different blogs)') ),
		array( 'cross_posting_blogs', 1, T_('Allow moving posts between different blogs'), $Settings->get('cross_posting_blogs'), false, T_('(Main cat can move to different blog)') ) ),
		'allow_cross_posting', T_('Cross Posting') );

	$Form->checkbox_input( 'redirect_moved_posts', $Settings->get('redirect_moved_posts'), T_('Redirect if post has moved'), array( 'note'=>T_('check to allow redirects to the correct blog when a post was found in a different blog.') ) );
$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( T_('Subscribing to new blogs').get_manual_link('collections-subscription-settings') );
	$Form->radio_input( 'subscribe_new_blogs', $Settings->get('subscribe_new_blogs'),
		array(
			array( 'value' => 'page', 'label' => T_('From blog page only') ),
			array( 'value' => 'public', 'label' => T_('Show a list of all <b>Public</b> blogs allowing subscriptions') ),
			array( 'value' => 'all', 'label' => T_('Show a list of <b>All</b> blogs allowing subsciptions') )
		),
		T_('Subscribing to new blogs'),
		array( 'lines' => true ) );
$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( T_('Default skins').get_manual_link('collections-default-skins') );
	$normal_skins = array();
	$mobile_skins = array( 0 => T_('Same as normal skin') );
	$tablet_skins = array( 0 => T_('Same as normal skin') );

	$SkinCache = & get_SkinCache();
	$SkinCache->load_all();
	$SkinCache->rewind();
	while( ( $iterator_Skin = & $SkinCache->get_next() ) != NULL )
	{
		switch( $iterator_Skin->get( 'type' ) )
		{
			case 'normal':
				$normal_skins[ $iterator_Skin->ID ] = $iterator_Skin->get( 'name' );
				break;

			case 'mobile':
				$mobile_skins[ $iterator_Skin->ID ] = $iterator_Skin->get( 'name' );
				break;

			case 'tablet':
				$tablet_skins[ $iterator_Skin->ID ] = $iterator_Skin->get( 'name' );
				break;

			//default: It's not a skin whit a type what we should show in these select lists ( e.g. feed )
		}
	}
	$field_params = array( 'force_keys_as_values' => true );
	$Form->select_input_array( 'def_normal_skin_ID', $Settings->get( 'def_normal_skin_ID' ), $normal_skins, T_('Default normal skin'), NULL, $field_params );
	$Form->select_input_array( 'def_mobile_skin_ID', $Settings->get( 'def_mobile_skin_ID' ), $mobile_skins, T_('Default mobile phone skin'), NULL, $field_params );
	$Form->select_input_array( 'def_tablet_skin_ID', $Settings->get( 'def_tablet_skin_ID' ), $tablet_skins, T_('Default tablet skin'), NULL, $field_params );
$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( T_('Comment recycle bin').get_manual_link('recycle-bin-settings') );

	$Form->text_input( 'auto_empty_trash', $Settings->get('auto_empty_trash'), 5, T_('Prune recycled comments after'), T_('days.') );

$Form->end_fieldset();

// --------------------------------------------

if( $current_User->check_perm( 'options', 'edit' ) )
{
	$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );
}

?>