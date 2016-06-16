<?php
/**
 * This file implements the UI view for the General blog properties.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;


global $action, $next_action, $blogtemplate, $blog, $tab, $admin_url, $locales;

$Form = new Form();

$form_title = '';
if( $edited_Blog->ID == 0 )
{ // "New blog" form: Display a form title and icon to close form
	global $kind;
	$kind_title = get_collection_kinds( $kind );
	$form_title = sprintf( T_('New "%s" collection'), $kind_title ).':';

	$Form->global_icon( T_('Abort creating new collection'), 'close', $admin_url.'?ctrl=dashboard', ' '.sprintf( T_('Abort new "%s" collection'), $kind_title ), 3, 3 );
}
elseif( $action == 'copy' )
{	// Copy collection form:
	$form_title = sprintf( T_('Duplicate "%s" collection'), $edited_Blog->get( 'shortname' ) ).':';

	$Form->global_icon( T_('Abort duplicating collection'), 'close', $admin_url.'?ctrl=dashboard', ' '.T_('Abort duplicating collection'), 3, 3 );
}

$Form->begin_form( 'fform', $form_title );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', $next_action );
$Form->hidden( 'tab', $tab );
if( $next_action == 'create' )
{
	$Form->hidden( 'kind', get_param('kind') );
	$Form->hidden( 'skin_ID', get_param('skin_ID') );
}
else
{
	$Form->hidden( 'blog', $blog );
}

if( ! empty( $edited_Blog->confirmation ) )
{	// Display a confirmation message:
	$form_fieldset_begin = $Form->fieldset_begin;
	$Form->fieldset_begin = str_replace( 'panel-default', 'panel-danger', $Form->fieldset_begin );
	$Form->begin_fieldset( T_('Confirmation') );

		echo '<h3 class="evo_confirm_delete__title">'.$edited_Blog->confirmation['title'].'</h3>';

		if( ! empty( $edited_Blog->confirmation['messages'] ) )
		{
			echo '<div class="log_container delete_messages"><ul>';
			foreach( $edited_Blog->confirmation['messages'] as $confirmation_message )
			{
				echo '<li>'.$confirmation_message.'</li>';
			}
			echo '</ul></div>';
		}

		echo '<p class="warning text-danger">'.T_('Do you confirm?').'</p>';
		echo '<p class="warning text-danger">'.T_('THIS CANNOT BE UNDONE!').'</p>';

		// Fake button to submit form by key "Enter" without autoconfirm this:
		$Form->button_input( array(
				'name'  => 'submit',
				'style' => 'position:absolute;left:-10000px'
			) );
		// Real button to confirm:
		$Form->button( array( 'submit', 'actionArray[update_confirm]', T_('I am sure!'), 'DeleteButton btn-danger' ) );
		$Form->button( array( 'button', '', T_('CANCEL'), 'CancelButton', 'location.href="'.$admin_url.'?ctrl=coll_settings&tab=general&blog='.$edited_Blog->ID.'"' ) );

	$Form->end_fieldset();
	$Form->fieldset_begin = $form_fieldset_begin;
}


$Form->begin_fieldset( T_('Collection type').get_manual_link( 'collection-type-panel' ) );
	$collection_kinds = get_collection_kinds();
	if( isset( $collection_kinds[ $edited_Blog->get( 'type' ) ] ) )
	{ // Display type of this blog
		echo '<p>'
			.sprintf( T_('This is a "%s" collection'), $collection_kinds[ $edited_Blog->get( 'type' ) ]['name'] )
			.' &ndash; '
			.$collection_kinds[ $edited_Blog->get( 'type' ) ]['desc']
		.'</p>';
		if( $edited_Blog->ID > 0 && $action != 'copy' )
		{	// Display a link to change collection kind:
			echo '<p><a href="'.$admin_url.'?ctrl=coll_settings&tab=general&action=type&blog='.$edited_Blog->ID.'">'
					.T_('Change collection type / Reset')
			.'</a></p>';
		}
	}
$Form->end_fieldset();


$Form->begin_fieldset( T_('General parameters').get_manual_link( 'blogs_general_parameters' ), array( 'class'=>'fieldset clear' ) );

	$name_chars_count = utf8_strlen( html_entity_decode( $edited_Blog->get( 'name' ) ) );
	$Form->text( 'blog_name', $edited_Blog->get( 'name' ), 50, T_('Title'), T_('Will be displayed on top of the blog.')
		.' ('.sprintf( T_('%s characters'), '<span id="blog_name_chars_count">'.$name_chars_count.'</span>' ).')', 255 );

	$Form->text( 'blog_shortname', $edited_Blog->get( 'shortname' ), 15, T_('Short name'), T_('Will be used in selection menus and throughout the admin interface.'), 255 );

	if( $current_User->check_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
	{ // Permission to edit advanced admin settings
		$Form->text( 'blog_urlname', $edited_Blog->get( 'urlname' ), 20, T_('URL "filename"'),
				sprintf( T_('"slug" used to uniquely identify this blog in URLs. Also used as <a %s>default media folder</a>.'),
					'href="?ctrl=coll_settings&tab=advanced&blog='.$blog.'"'), 255 );
	}
	else
	{
		$Form->info( T_('URL Name'), $edited_Blog->get( 'urlname' ), T_('Used to uniquely identify this blog in URLs.') /* Note: message voluntarily shorter than admin message */ );
	}

$Form->end_fieldset();


$Form->begin_fieldset( T_('Language / locale').get_manual_link( 'coll-locale-settings' ) );

	// Calculate how much locales are enabled in system
	$number_enabled_locales = 0;
	foreach( $locales as $locale_data )
	{
		if( $locale_data['enabled'] )
		{
			$number_enabled_locales++;
		}
		if( $number_enabled_locales > 1 )
		{ // We need to know we have more than 1 locale is enabled, Stop here
			break;
		}
	}

	if( $number_enabled_locales > 1 )
	{ // More than 1 locale
		$blog_locale_note = ( $current_User->check_perm( 'options', 'view' ) ) ?
			'<a href="'.$admin_url.'?ctrl=regional">'.T_('Regional settings').' &raquo;</a>' : '';
		$Form->select( 'blog_locale', $edited_Blog->get( 'locale' ), 'locale_options_return', T_('Collection Locale'), $blog_locale_note );

		$Form->radio( 'blog_locale_source', $edited_Blog->get_setting( 'locale_source' ),
				array(
					array( 'blog', T_('Always force to collection locale') ),
					array( 'user', T_('Use browser / user locale when possible') ),
			), T_('Navigation/Widget Display'), true );

		$Form->radio( 'blog_post_locale_source', $edited_Blog->get_setting( 'post_locale_source' ),
				array(
					array( 'post', T_('Always force to post locale') ),
					array( 'blog', T_('Follow navigation locale') ),
			), T_('Content Display'), true );

		$Form->radio( 'blog_new_item_locale_source', $edited_Blog->get_setting( 'new_item_locale_source' ),
				array(
					array( 'use_coll', T_('Always use collection locale') ),
					array( 'select_coll', T_('Allow select - use collection locale by default') ),
					array( 'select_user', T_('Allow select - use user locale by default') ),
			), T_('New Posts'), true );
	}
	else
	{ // Only one locale
		echo '<p>';
		echo sprintf( T_( 'This collection uses %s.' ), '<b>'.$locales[ $edited_Blog->get( 'locale' ) ]['name'].'</b>' );
		if( $current_User->check_perm( 'options', 'view' ) )
		{
			echo ' '.sprintf( T_( 'Go to <a %s>Regional Settings</a> to enable additional locales.' ), 'href="'.$admin_url.'?ctrl=regional"' );
		}
		echo '</p>';
	}

$Form->end_fieldset();


$Form->begin_fieldset( T_('Collection permissions').get_manual_link( 'collection-permission-settings' ) );

	$owner_User = & $edited_Blog->get_owner_User();
	if( $current_User->check_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
	{ // Permission to edit advanced admin settings
		// fp> Note: There are 2 reasons why we don't provide a select here:
		// 1. If there are 1000 users, it's a pain.
		// 2. A single blog owner is not necessarily allowed to see all other users.
		$Form->username( 'owner_login', $owner_User, T_('Owner'), T_('Login of this blog\'s owner.') );
	}
	else
	{
		$Form->info( T_('Owner'), $owner_User->login, $owner_User->dget( 'fullname' ) );
	}

	$Form->radio( 'advanced_perms', $edited_Blog->get( 'advanced_perms' ),
			array(
				array( '0', T_('Simple permissions'), sprintf( T_('(the owner above has most permissions on this collection, except %s)'), get_admin_badge() ) ),
				array( '1', T_('Advanced permissions'), sprintf( T_('(you can assign granular <a %s>user</a> and <a %s>group</a> permissions for this collection)'),
										'href="'.$admin_url.'?ctrl=coll_settings&amp;tab=perm&amp;blog='.$edited_Blog->ID.'"',
										'href="'.$admin_url.'?ctrl=coll_settings&amp;tab=permgroup&amp;blog='.$edited_Blog->ID.'"' ) ),
		), T_('Permission management'), true );

	$Form->radio( 'blog_allow_access', $edited_Blog->get_setting( 'allow_access' ),
			array(
				array( 'public', T_('Everyone (Public Blog)') ),
				array( 'users', T_('Community only (Logged-in users only)') ),
				array( 'members',
									'<span id="allow_access_members_advanced_title"'.( $edited_Blog->get( 'advanced_perms' ) ? '' : ' style="display:none"' ).'>'.T_('Members only').'</span>'.
									'<span id="allow_access_members_simple_title"'.( $edited_Blog->get( 'advanced_perms' ) ? ' style="display:none"' : '' ).'>'.T_('Only the owner').'</span>',
									'<span id="allow_access_members_advanced_note"'.( $edited_Blog->get( 'advanced_perms' ) ? '' : ' style="display:none"' ).'>'.sprintf( T_('(Assign membership in <a %s>user</a> and <a %s>group</a> permissions for this collection)'),
										'href="'.$admin_url.'?ctrl=coll_settings&amp;tab=perm&amp;blog='.$edited_Blog->ID.'"',
										'href="'.$admin_url.'?ctrl=coll_settings&amp;tab=permgroup&amp;blog='.$edited_Blog->ID.'"' ).'</span>'.
									'<span id="allow_access_members_simple_note"'.( $edited_Blog->get( 'advanced_perms' ) ? ' style="display:none"' : '' ).'>'.T_('(Private collection)').'</span>' ),
		), T_('Allow access to'), true );

$Form->end_fieldset();


$Form->begin_fieldset( T_('Lists of collections').get_manual_link( 'collection-list-settings' ) );

	$Form->text( 'blog_order', $edited_Blog->get( 'order' ), 10, T_('Order') );

	$Form->radio( 'blog_in_bloglist', $edited_Blog->get( 'in_bloglist' ),
							array(  array( 'public', T_('Always (Public)') ),
											array( 'logged', T_('For logged-in users only') ),
											array( 'member', T_('For members only') ),
											array( 'never', T_('Never') )
										), T_('Show in front-office list'), true, T_('Select when you want this blog to appear in the list of blogs on this system.') );

$Form->end_fieldset();


$Form->begin_fieldset( T_('Description').get_manual_link( 'collection-description' ) );

	$Form->text( 'blog_tagline', $edited_Blog->get( 'tagline' ), 50, T_('Tagline'), T_('This is displayed under the blog name on the blog template.'), 250 );

	$Form->textarea( 'blog_longdesc', $edited_Blog->get( 'longdesc' ), 5, T_('Long Description'), T_('This is displayed on the blog template.'), 50 );

$Form->end_fieldset();


$Form->buttons( array( array( 'submit', 'submit', ( $action == 'copy' ? sprintf( T_('Save and duplicate all settings from %s'), $edited_Blog->get( 'shortname' ) ) : T_('Save Changes!') ), 'SaveButton' ) ) );

$Form->end_form();

?>
<script type="text/javascript">
jQuery( 'input[name=advanced_perms]' ).click( function()
{	// Display a proper label for "Allow access to" depending on selected "Permission management":
	if( jQuery( this ).val() == '1' )
	{	// If advanced permissions are selected
		jQuery( '#allow_access_members_simple_title, #allow_access_members_simple_note' ).hide();
		jQuery( '#allow_access_members_advanced_title, #allow_access_members_advanced_note' ).show();
	}
	else
	{	// If simple permissions are selected
		jQuery( '#allow_access_members_simple_title, #allow_access_members_simple_note' ).show();
		jQuery( '#allow_access_members_advanced_title, #allow_access_members_advanced_note' ).hide();
	}
} );

jQuery( '#blog_name' ).keyup( function()
{	// Count characters of collection title(each html entity is counted as single char):
	jQuery( '#blog_name_chars_count' ).html( jQuery( this ).val().replace( /&[^;\s]+;/g, '&' ).length );
} );
</script>