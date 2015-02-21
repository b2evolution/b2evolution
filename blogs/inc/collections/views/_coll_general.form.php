<?php
/**
 * This file implements the UI view for the General blog properties.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _coll_general.form.php 8265 2015-02-15 04:34:35Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;


global $action, $next_action, $blogtemplate, $blog, $tab, $admin_url;

$Form = new Form();

$form_title = '';
if( $edited_Blog->ID == 0 )
{ // "New blog" form: Display a form title and icon to close form
	global $kind;
	$kind_title = get_collection_kinds( $kind );
	$form_title = sprintf( T_('New %s'), $kind_title ).':';

	$Form->global_icon( T_('Abort creating new collection'), 'close', $admin_url.'?ctrl=collections&amp;tab=list', ' '.sprintf( T_('Abort New %s'), $kind_title ), 3, 3 );
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


$Form->begin_fieldset( T_('Collection type').get_manual_link('collection-type') );
	$collection_kinds = get_collection_kinds();
	if( isset( $collection_kinds[ $edited_Blog->get( 'type' ) ] ) )
	{	// Display type of this blog
		echo '<p>'
			.sprintf( T_('This is %s &ndash; '), $collection_kinds[ $edited_Blog->get( 'type' ) ]['name'] )
			.$collection_kinds[ $edited_Blog->get( 'type' ) ]['desc']
		.'</p>';
		if( $edited_Blog->ID > 0 )
		{
			echo '<p><a href="'.$admin_url.'?ctrl=coll_settings&tab=general&action=type&blog='.$edited_Blog->ID.'">'
					.T_('Change collection type / Reset')
			.'</a></p>';
		}
	}
$Form->end_fieldset();


$Form->begin_fieldset( T_('General parameters').get_manual_link('blogs_general_parameters'), array( 'class'=>'fieldset clear' ) );

	$Form->text( 'blog_name', $edited_Blog->get( 'name' ), 50, T_('Title'), T_('Will be displayed on top of the blog.'), 255 );

	$Form->text( 'blog_shortname', $edited_Blog->get( 'shortname', 'formvalue' ), 15, T_('Short name'), T_('Will be used in selection menus and throughout the admin interface.'), 255 );

	$owner_User = & $edited_Blog->get_owner_User();
	if( $current_User->check_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
	{	// Permission to edit advanced admin settings

		$Form->text( 'blog_urlname', $edited_Blog->get( 'urlname' ), 20, T_('URL "filename"'),
				sprintf( T_('"slug" used to uniquely identify this blog in URLs. Also used as <a %s>default media folder</a>.'),
					'href="?ctrl=coll_settings&tab=advanced&blog='.$blog.'"'), 255 );

		// fp> Note: There are 2 reasons why we don't provide a select here:
		// 1. If there are 1000 users, it's a pain.
		// 2. A single blog owner is not necessarily allowed to see all other users.
		$Form->username( 'owner_login', $owner_User, T_('Owner'), T_('Login of this blog\'s owner.') );
	}
	else
	{
		$Form->info( T_('URL Name'), $edited_Blog->get( 'urlname' ), T_('Used to uniquely identify this blog in URLs.') /* Note: message voluntarily shorter than admin message */ );

		$Form->info( T_('Owner'), $owner_User->login, $owner_User->dget('fullname') );
	}

	$Form->select( 'blog_locale', $edited_Blog->get( 'locale' ), 'locale_options_return', T_('Main Locale'), T_('Determines the language of the navigation links on the blog.') );

	$Form->checkbox( 'favorite', $edited_Blog->get( 'favorite' ),
						T_( 'Favorite' ), T_( 'Include in the quick blog selector at the top of the back office pages.' ) );

	$Form->text( 'blog_order', $edited_Blog->get( 'order' ), 10, T_('Order') );

$Form->end_fieldset();


$Form->begin_fieldset( T_('Description').get_manual_link('collection-description') );
	$Form->text( 'blog_tagline', $edited_Blog->get( 'tagline' ), 50, T_('Tagline'), T_('This is displayed under the blog name on the blog template.'), 250 );
	$Form->textarea( 'blog_longdesc', $edited_Blog->get( 'longdesc' ), 5, T_('Long Description'), T_('This is displayed on the blog template.'), 50 );
$Form->end_fieldset();


$Form->buttons( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );

$Form->end_form();

?>