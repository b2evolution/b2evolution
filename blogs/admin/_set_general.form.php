<?php
/**
 * This file implements the UI view for the general settings.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

$Form = & new Form( 'settings.php', 'form' );

$Form->begin_form( 'fform', T_('General Settings') );

$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'general' );

// --------------------------------------------

$Form->fieldset( T_('Default user rights') );

	$Form->checkbox( 'newusers_canregister', $Settings->get('newusers_canregister'), T_('New users can register'), T_('Check to allow new users to register themselves.' ) );

	$Form->select_object( 'newusers_grp_ID', $Settings->get('newusers_grp_ID'), $GroupCache, T_('Group for new users'), T_('Groups determine user roles and permissions.') );

	$Form->text( 'newusers_level', $Settings->get('newusers_level'), 1, T_('Level for new users'), T_('Levels determine hierarchy of users in blogs.' ), 1 );

$Form->fieldset_end();


// --------------------------------------------

$Form->fieldset( T_('Display options') );

$Form->select_object( 'default_blog_ID', $Settings->get('default_blog_ID'), $BlogCache, T_('Default blog to display'),
											T_('This blog will be displayed on index.php .'), true );


$Form->radio( 'what_to_show', $Settings->get('what_to_show'),
							array(  array( 'days', T_('days') ),
											array( 'posts', T_('posts') ),
										), T_('Display unit') );

$Form->text( 'posts_per_page', $Settings->get('posts_per_page'), 4, T_('Posts/Days per page'), '', 4 );

$Form->radio( 'archive_mode', $Settings->get('archive_mode'),
							array(  array( 'monthly', T_('monthly') ),
											array( 'weekly', T_('weekly') ),
											array( 'daily', T_('daily') ),
											array( 'postbypost', T_('post by post') )
										), T_('Archive mode') );

$Form->checkbox( 'AutoBR', $Settings->get('AutoBR'), T_('Email/MMS Auto-BR'), T_('Add &lt;BR /&gt; tags to mail/MMS posts.') );

$Form->fieldset_end();

// --------------------------------------------

$Form->fieldset( T_('Link options') );

$Form->checkbox( 'links_extrapath', $Settings->get('links_extrapath'), T_('Use extra-path info'), sprintf( T_('Recommended if your webserver supports it. Links will look like \'stub/2003/05/20/post_title\' instead of \'stub?title=post_title&amp;c=1&amp;tb=1&amp;pb=1&amp;more=1\'.' ) ) );

$Form->radio( 'permalink_type', $Settings->get('permalink_type'),
							array(  array( 'urltitle', T_('Post called up by its URL title (Recommended)'), T_('Fallback to ID when no URL title available.') ),
											array( 'pid', T_('Post called up by its ID') ),
											array( 'archive#id', T_('Post on archive page, located by its ID') ),
											array( 'archive#title', T_('Post on archive page, located by its title (for Cafelog compatibility)') )
										), T_('Permalink type'), true );

$Form->fieldset_end();

// --------------------------------------------

$Form->fieldset( T_('Security options') );

$Form->text( 'user_minpwdlen', (int)$Settings->get('user_minpwdlen'), 1, T_('Minimum password length'), T_('for users.'), 2 );

$Form->fieldset_end();

$Form->fieldset( T_('Miscellaneous options') );

$Form->text( 'reloadpage_timeout', (int)$Settings->get('reloadpage_timeout'), 2,
								T_('Reload-page timeout'), T_('Time (in seconds) that must pass before a request to the same URI from the same IP and useragent is considered as a new hit.'), 5 );

$Form->fieldset_end();

// --------------------------------------------

if( $current_User->check_perm( 'options', 'edit' ) )
{
	$Form->end_form( array( array( 'submit', 'submit', T_('Save !'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}

?>