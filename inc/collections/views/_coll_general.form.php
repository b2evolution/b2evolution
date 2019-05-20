<?php
/**
 * This file implements the UI view for the General blog properties.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
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
global $action, $next_action, $blogtemplate, $blog, $tab, $admin_url, $locales, $duplicating_collection_name;
global $Settings;

$Form = new Form();

$form_title = '';
$is_creating = ( $edited_Blog->ID == 0 || $action == 'copy' );
if( $edited_Blog->ID == 0 )
{ // "New blog" form: Display a form title and icon to close form
	global $kind;
	$kind_title = get_collection_kinds( $kind );
	$form_title = sprintf( T_('New "%s" collection'), $kind_title ).':';

	$Form->global_icon( T_('Abort creating new collection'), 'close', $admin_url.'?ctrl=collections', ' '.sprintf( T_('Abort new "%s" collection'), $kind_title ), 3, 3 );
}
elseif( $action == 'copy' )
{	// Copy collection form:
	$form_title = sprintf( T_('Duplicate "%s" collection'), $duplicating_collection_name ).':';

	$Form->global_icon( T_('Abort duplicating collection'), 'close', $admin_url.'?ctrl=collections', ' '.T_('Abort duplicating collection'), 3, 3 );
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
	$Form->hidden( 'blog', $edited_Blog->ID );
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
		if( ! $is_creating && $action != 'copy' )
		{	// Display a link to change collection kind:
			echo '<p><a href="'.$admin_url.'?ctrl=coll_settings&tab=general&action=type&blog='.$edited_Blog->ID.'">'
					.T_('Change collection type / Reset')
			.'</a></p>';
		}
	}
	if( $edited_Blog->get( 'type' ) == 'main' )
	{ // Only show when collection is of type 'Main'
		$set_as_checked = 0;
		switch( $action )
		{
			case 'edit':
				$set_as_checked = 0;
				break;

			case 'new-name':
			case 'create':
				$set_as_checked = 1;
				break;
		}

		$set_as_options = array();
		if( ! $Settings->get( 'login_blog_ID' ) )
		{
			$set_as_options[] = array( 'set_as_login_blog', 1, T_('Collection for login/registration'), param( 'set_as_login_blog', 'boolean', $set_as_checked ) );
		}
		if( ! $Settings->get( 'msg_blog_ID' ) )
		{
			$set_as_options[] = array( 'set_as_msg_blog', 1, T_('Collection for profiles/messaging'), param( 'set_as_msg_blog', 'boolean', $set_as_checked ) );
		}
		if( ! $Settings->get( 'info_blog_ID' ) )
		{
			$set_as_options[] = array( 'set_as_info_blog', 1, T_('Collection for shared content blocks'), param( 'set_as_info_blog', 'boolean', $set_as_checked ) );
		}

		if( $set_as_options )
		{
			$Form->checklist( $set_as_options, 'set_as_options', T_('Automatically set as') );
		}

		if( $is_creating )
		{
			echo '<p>'.T_('The Home collection typically aggregates the contents of all other collections on the site.').'</p>';
			$aggregate_coll_IDs = $edited_Blog->get_setting( 'aggregate_coll_IDs' );
			$Form->radio( 'blog_aggregate', empty( $aggregate_coll_IDs ) ? 0 : 1,
			array(
				array( 1, T_('Set to aggregate contents of all other collections') ),
				array( 0, T_('Do not aggregate') ),
			), T_('Aggregate'), true, '' );
		}
	}
$Form->end_fieldset();

if( in_array( $action, array( 'create', 'new-name' ) ) && $ctrl = 'collections' )
{ // Only show demo content option when creating a new collection
	$Form->begin_fieldset( T_( 'Demo contents' ).get_manual_link( 'collection-demo-content' ) );
		$Form->radio( 'create_demo_contents', param( 'create_demo_contents', 'integer', -1 ),
					array(
						array( 1, T_('Initialize this collection with some demo contents') ),
						array( 0, T_('Create an empty collection') ),
					), T_('New contents'), true, '', true );
		if( $current_User->check_perm( 'orgs', 'create', false ) && $current_User->check_perm( 'blog_admin', 'editall', false ) )
		{ // Permission to create organizations
			$Form->checkbox( 'create_demo_org', param( 'create_demo_org', 'integer', 1 ),
					T_( 'Create demo organization' ), T_( 'Create a demo organization if none exists.' ) );
		}

		if( $current_User->check_perm( 'users', 'edit', false ) && $current_User->check_perm( 'blog_admin', 'editall', false ) )
		{ // Permission to edit users
			$Form->checkbox( 'create_demo_users', param( 'create_demo_users', 'integer', 1 ),
					T_( 'Create demo users' ), T_( 'Create demo users as comment authors.' ) );
		}
	$Form->end_fieldset();
}

$Form->begin_fieldset( T_('General parameters').get_manual_link( 'blogs-general-parameters' ), array( 'class'=>'fieldset clear' ) );

	$collection_logo_params = array( 'file_type' => 'image', 'max_file_num' => 1, 'window_title' => T_('Select collection logo/image'), 'root' => 'shared_0', 'size_name' => 'fit-320x320' );
	$Form->fileselect( 'collection_logo_file_ID', $edited_Blog->get_setting( 'collection_logo_file_ID' ), T_('Collection logo/image'), NULL, $collection_logo_params );

	$name_chars_count = utf8_strlen( html_entity_decode( $edited_Blog->get( 'name' ) ) );
	$Form->text( 'blog_name', $edited_Blog->get( 'name' ), 50, T_('Title'), T_('Will be displayed on top of the blog.')
		.' ('.sprintf( T_('%s characters'), '<span id="blog_name_chars_count">'.$name_chars_count.'</span>' ).')', 255 );

	$blog_shortname = $action == 'copy' ? NULL : $edited_Blog->get( 'shortname' );
	$Form->text( 'blog_shortname', $blog_shortname, 15, T_('Short name'), T_('Will be used in selection menus and throughout the admin interface.'), 255 );

	if( $current_User->check_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) ||
	    $current_User->check_perm( 'blogs', 'create', false, $edited_Blog->sec_ID ) )
	{ // Permission to edit advanced admin settings
		$blog_urlname = $action == 'copy' ? NULL : $edited_Blog->get( 'urlname' );
		$Form->text( 'blog_urlname', $blog_urlname, 20, T_('URL "filename"'),
				sprintf( T_('"slug" used to uniquely identify this blog in URLs. Also used as <a %s>default media folder</a>.'),
					'href="?ctrl=coll_settings&tab=advanced&blog='.$blog.'"'), 255 );
	}
	else
	{
		$Form->info( T_('URL Name'), '<span id="urlname_display">'.$edited_Blog->get( 'urlname' ).'</span>', T_('Used to uniquely identify this blog in URLs.') /* Note: message voluntarily shorter than admin message */ );
		if( $is_creating )
		{
			$Form->hidden( 'blog_urlname', $edited_Blog->get( 'urlname' ) );
		}
	}

	if( $is_creating )
	{
		$blog_urlname = $action == 'copy' ? NULL : $edited_Blog->get( 'urlname' );
		?>
		<script>
		var shortNameInput = jQuery( '#blog_shortname');
		var timeoutId = 0;

		function getAvailableUrlName( urlname )
		{
			if( urlname )
			{
				var urlNameInput = jQuery( 'input#blog_urlname' );
				urlNameInput.addClass( 'loader_img' );

				evo_rest_api_request( 'tools/available_urlname',
				{
					'urlname': urlname
				},
				function( data )
				{
					jQuery( 'span#urlname_display' ).html( data.urlname );
					jQuery( 'input[name="blog_urlname"]' ).val( data.urlname );
					urlNameInput.removeClass( 'loader_img' );
				}, 'GET' );
			}
		}

		shortNameInput.on( 'keyup', function( ) {
			clearTimeout( timeoutId );
			timeoutId = setTimeout( function() { getAvailableUrlName( shortNameInput.val() ) }, 500 );
		} );

		jQuery( document ).ready( function() {
			getAvailableUrlName( '<?php echo format_to_js( $blog_urlname ); ?>' );
		} );
		</script>
		<?php
	}

	// Section:
	$blog_section_id = $action == 'copy' ? 1 : $edited_Blog->get( 'sec_ID' );
	$SectionCache = & get_SectionCache();
	$SectionCache->load_available( $blog_section_id );
	if( count( $SectionCache->cache_available ) > 1 )
	{ // If we have only one option in the list do not show select input
		$Form->select_input_object( 'sec_ID', $blog_section_id, $SectionCache, T_('Section'), array( 'required' => true ) );
	}

$Form->end_fieldset();

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

if( ! $is_creating )
{
	$Form->begin_fieldset( T_('Language / locale').get_manual_link( 'coll-locale-settings' ), array( 'id' => 'language' ) );
		if( $number_enabled_locales > 1 )
		{ // More than 1 locale
			$blog_locale_note = ( $current_User->check_perm( 'options', 'view' ) ) ?
				'<a href="'.$admin_url.'?ctrl=regional">'.T_('Regional settings').' &raquo;</a>' : '';
		$Form->locale_selector( 'blog_locale', $edited_Blog->get( 'locale' ), $edited_Blog->get_locales(), T_('Collection Locales'), $blog_locale_note, array( 'link_coll_ID' => $edited_Blog->ID ) );

			$Form->radio( 'blog_locale_source', $edited_Blog->get_setting( 'locale_source' ),
					array(
						array( 'blog', T_('Always force to collection locale') ),
						array( 'user', T_('Use browser / user locale when possible') ),
				), T_('Navigation/Widget Display'), true );

			$Form->radio( 'blog_post_locale_source', $edited_Blog->get_setting( 'post_locale_source' ),
					array(
						array( 'post', T_('Always force to Post locale') ),
					array( 'blog', T_('Follow navigation locale'), '('.T_('Navigation/Widget Display').')' ),
				), T_('Post Details Display'), true );

			$Form->radio( 'blog_new_item_locale_source', $edited_Blog->get_setting( 'new_item_locale_source' ),
					array(
						array( 'select_coll', T_('Default to collection\'s main locale') ),
						array( 'select_user', T_('Default to user\'s locale') ),
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
}
else
{
	if( $number_enabled_locales > 1 )
	{
		$Form->hidden( 'blog_locale', $edited_Blog->get( 'locale' ) );
		$Form->hidden( 'blog_locale_source', $edited_Blog->get_setting( 'locale_source' ) );
		$Form->hidden( 'blog_post_locale_source', $edited_Blog->get_setting( 'post_locale_source' ) );
		$Form->hidden( 'blog_new_item_locale_source', $edited_Blog->get_setting( 'new_item_locale_source' ) );
	}
}

if( $action == 'copy' )
{	// Additional options for collection duplicating:
	$Form->begin_fieldset( T_('Options').get_manual_link( 'collection-options' ) );
		$Form->checkbox( 'duplicate_items', param( 'duplicate_items', 'integer', 1 ), T_('Duplicate contents'), T_('Check to duplicate posts/items from source collection.') );
		$Form->checkbox( 'duplicate_comments', param( 'duplicate_comments', 'integer', 0 ), T_('Duplicate comments'), T_('Check to duplicate comments from source collection.'), '', 1, ! get_param( 'duplicate_items' ) );
	$Form->end_fieldset();
}

$Form->begin_fieldset( T_('Collection permissions').get_manual_link( 'collection-permission-settings' ) );

	if( $action == 'copy' )
	{
		$owner_User = $current_User;
	}
	else
	{
		$owner_User = & $edited_Blog->get_owner_User();
	}
	if( $current_User->check_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
	{ // Permission to edit advanced admin settings
		// fp> Note: There are 2 reasons why we don't provide a select here:
		// 1. If there are 1000 users, it's a pain.
		// 2. A single blog owner is not necessarily allowed to see all other users.
		$Form->username( 'owner_login', $owner_User, T_('Owner'), T_('Login of this blog\'s owner.') );
	}
	else
	{
		if( ! $is_creating )
		{
			$Form->info( T_('Owner'), $owner_User->login, $owner_User->dget( 'fullname' ) );
		}
	}

	if( ! $is_creating )
	{
		$Form->radio( 'advanced_perms', $edited_Blog->get( 'advanced_perms' ),
				array(
					array( '0', T_('Simple permissions'), sprintf( T_('(the owner above has most permissions on this collection, except %s)'), get_admin_badge() ) ),
					array( '1', T_('Advanced permissions'), sprintf( T_('(you can assign granular <a %s>user</a> and <a %s>group</a> permissions for this collection)'),
											'href="'.$admin_url.'?ctrl=coll_settings&amp;tab=perm&amp;blog='.$edited_Blog->ID.'"',
											'href="'.$admin_url.'?ctrl=coll_settings&amp;tab=permgroup&amp;blog='.$edited_Blog->ID.'"' ) ),
			), T_('Permission management'), true );
	}
	else
	{
		$Form->hidden( 'advanced_perms', $edited_Blog->get( 'advanced_perms' ) );
	}

	$blog_allow_access = $action == 'copy' ? 'public' : $edited_Blog->get_setting( 'allow_access' );
	$Form->radio( 'blog_allow_access', $blog_allow_access,
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

if( ! $is_creating )
{
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

		$collection_logo_params = array( 'file_type' => 'image', 'max_file_num' => 1, 'window_title' => T_('Select collection logo'), 'root' => 'shared_0', 'size_name' => 'fit-320x320' );
		$Form->fileselect( 'blog_logo_file_ID', $edited_Blog->get_setting( 'logo_file_ID' ), T_('Collection logo'), T_('This is used to add Structured Data to your pages.'), $collection_logo_params );

		$social_media_boilerplate_params = array( 'file_type' => 'image', 'max_file_num' => 1, 'window_title' => T_('Select logo for social media boilerplate'), 'root' => 'shared_0', 'size_name' => 'fit-320x320' );
		$Form->fileselect( 'blog_social_media_image_file_ID', $edited_Blog->get_setting( 'social_media_image_file_ID' ), T_('Social media boilerplate'), T_('This is used to add Structured Data to your pages.'), $social_media_boilerplate_params );

		$Form->text( 'blog_tagline', $edited_Blog->get( 'tagline' ), 50, T_('Tagline'), T_('This is typically displayed by a widget right under the collection name in the front-office.'), 250 );

		$shortdesc_chars_count = utf8_strlen( html_entity_decode( $edited_Blog->get( 'shortdesc' ) ) );
		$Form->text( 'blog_shortdesc', $edited_Blog->get( 'shortdesc' ), 60, T_('Short Description'), T_('This is is used in meta tag description and RSS feeds. NO HTML!')
			.' ('.sprintf( T_('%s characters'), '<span id="blog_shortdesc_chars_count">'.$shortdesc_chars_count.'</span>' ).')', 250, 'large' );

		$Form->textarea( 'blog_longdesc', $edited_Blog->get( 'longdesc' ), 5, T_('Long Description'), T_('This will be used in Open Graph tags and XML feeds. This may also be displayed by widgets in the front-office.')
			.' '.T_(' HTML markup possible but not recommended.'), 50 );

	$Form->end_fieldset();
}
else
{
	$Form->hidden( 'blog_order', $edited_Blog->get( 'order' ) );
	$Form->hidden( 'blog_in_bloglist', $edited_Blog->get( 'in_bloglist' ) );
	$Form->hidden( 'blog_tagline', $edited_Blog->get( 'tagline' ) );
	$Form->hidden( 'blog_shortdesc', $edited_Blog->get( 'shortdesc' ) );
	$Form->hidden( 'blog_longdesc', $edited_Blog->get( 'longdesc' ) );
}

if( ! $is_creating )
{
	$Form->begin_fieldset( T_('Meta data').get_manual_link('blog-meta-data') );
		$social_media_boilerplate_params = array( 'file_type' => 'image', 'max_file_num' => 1, 'window_title' => T_('Select logo for social media boilerplate'), 'root' => 'shared_0', 'size_name' => 'fit-320x320' );
		$Form->fileselect( 'social_media_image_file_ID', $edited_Blog->get_setting( 'social_media_image_file_ID' ), T_('Social media boilerplate'), NULL, $social_media_boilerplate_params );
		$Form->text( 'blog_keywords', $edited_Blog->get( 'keywords' ), 60, T_('Keywords'), T_('This is is used in meta tag keywords. NO HTML!'), 250, 'large' );
		$Form->text( 'blog_footer_text', $edited_Blog->get_setting( 'blog_footer_text' ), 60, T_('Blog footer'), sprintf(
			T_('Use &lt;br /&gt; to insert a line break. You might want to put your copyright or <a href="%s" target="_blank">creative commons</a> notice here.'),
			'http://creativecommons.org/license/' ), 1000, 'large' );
		$Form->textarea( 'single_item_footer_text', $edited_Blog->get_setting( 'single_item_footer_text' ), 2, T_('Single post footer'),
			T_('This will be displayed after each post in single post view.').' '.sprintf( T_('Available variables: %s.'), '<b>$perm_url$</b>, <b>$title$</b>, <b>$excerpt$</b>, <b>$author$</b>, <b>$author_login$</b>' ), 50 );
		$Form->textarea( 'xml_item_footer_text', $edited_Blog->get_setting( 'xml_item_footer_text' ), 2, T_('Post footer in RSS/Atom'),
			T_('This will be appended to each post in your RSS/Atom feeds.').' '.sprintf( T_('Available variables: %s.'), T_('same as above') ), 50 );
		$Form->textarea( 'blog_notes', $edited_Blog->get( 'notes' ), 5, T_('Notes'),
			T_('Additional info. Appears in the backoffice.'), 50 );
	$Form->end_fieldset();
}
else
{
	$Form->hidden( 'blog_keywords', $edited_Blog->get( 'keywords' ) );
	$Form->hidden( 'blog_footer_text', $edited_Blog->get_setting( 'blog_footer_text' ) );
	$Form->hidden( 'single_item_footer_text', $edited_Blog->get_setting( 'single_item_footer_text' ) );
	$Form->hidden( 'xml_item_footer_text', $edited_Blog->get_setting( 'xml_item_footer_text' ) );
	$Form->hidden( 'blog_notes', $edited_Blog->get( 'notes' ) );
}


$Form->buttons( array( array( 'submit', 'submit', ( $action == 'copy' ? T_('Duplicate NOW!') : T_('Save Changes!') ), 'SaveButton' ) ) );

$Form->end_form();

?>
<script>

function updateDemoContentInputs()
{
	if( jQuery( 'input[name=create_demo_contents]:checked' ).val() == '1' )
	{
		jQuery( 'input[name=create_demo_org], input[name=create_demo_users]' ).removeAttr( 'disabled' );
	}
	else
	{
		jQuery( 'input[name=create_demo_org], input[name=create_demo_users]' ).attr( 'disabled', true );
	}
}

jQuery( 'input[name=create_demo_contents]' ).click( updateDemoContentInputs );
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


updateDemoContentInputs();

jQuery( '#blog_name' ).keyup( function()
{	// Count characters of collection title(each html entity is counted as single char):
	jQuery( '#blog_name_chars_count' ).html( jQuery( this ).val().replace( /&[^;\s]+;/g, '&' ).length );
} );

jQuery( '#duplicate_items' ).click( function()
{	// Disable option for comments duplicating when items duplicating is disabled:
	jQuery( '#duplicate_comments' ).prop( 'disabled', ! jQuery( this ).is( ':checked' ) );
} );

jQuery( '#blog_shortdesc' ).keyup( function()
{	// Count characters of meta short description(each html entity is counted as single char):
	jQuery( '#blog_shortdesc_chars_count' ).html( jQuery( this ).val().replace( /&[^;\s]+;/g, '&' ).length );
} );
</script>