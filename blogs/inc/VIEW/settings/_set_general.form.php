<?php
/**
 * This file implements the UI view for the general settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
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
 * @author blueyed: Daniel HAHLER.
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

$Form = & new Form( NULL, 'settings_checkchanges' );
$Form->begin_form( 'fform', T_('General Settings'),
	// enable all form elements on submit (so values get sent):
	array( 'onsubmit'=>'var es=this.elements; for( var i=0; i < es.length; i++ ) { es[i].disabled=false; };' ) );

$Form->hidden( 'ctrl', 'settings' );
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'general' );

// --------------------------------------------

$Form->begin_fieldset( T_('Default user rights') );

	$Form->checkbox( 'newusers_canregister', $Settings->get('newusers_canregister'), T_('New users can register'), T_('Check to allow new users to register themselves.' ) );
	// Note: the options below also make sense, if newusers_canregister is disabled (especially newusers_mustvalidate)

	$Form->checkbox( 'newusers_mustvalidate', $Settings->get('newusers_mustvalidate'), T_('Users must validate'), T_('Check to require users to validate their email by clicking a link sent to them.' ) );

	$Form->checkbox( 'newusers_revalidate_emailchg', $Settings->get('newusers_revalidate_emailchg'), T_('Validate email changes'), T_('Check to require users to re-validate when they change their email address.' ) );

	$GroupCache = & get_Cache( 'GroupCache' );
	$Form->select_object( 'newusers_grp_ID', $Settings->get('newusers_grp_ID'), $GroupCache, T_('Group for new users'), T_('Groups determine user roles and permissions.') );

	$Form->text_input( 'newusers_level', $Settings->get('newusers_level'), 1, T_('Level for new users'), array( 'note'=>T_('Levels determine hierarchy of users in blogs.' ), 'maxlength'=>1, 'required'=>true ) );

$Form->end_fieldset();


// --------------------------------------------

$Form->begin_fieldset( T_('Display options') );

$BlogCache = & get_Cache( 'BlogCache' );
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

$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( T_('Link options') );

$Form->radio( 'links_extrapath', $Settings->get('links_extrapath'),
							array(
								array( 'disabled', T_('Do not use extra path info'), sprintf( T_('Permalinks will look like: %s'), '\'stub?title=post-title&amp;c=1&amp;tb=1&amp;pb=1&amp;more=1\'' ) ),
								array( 'short', T_('Only use post URL title'), sprintf( T_('Permalinks will look like: %s'), '\'stub/post-title\'' ) ),
								array( 'y', T_('Use year'), sprintf( T_('Permalinks will look like: %s'), '\'stub/2006/post-title\'' ) ),
								array( 'ym', T_('Use year &amp; month'), sprintf( T_('Permalinks will look like: %s'), '\'stub/2006/12/post-title\'' ) ),
								array( 'ymd', T_('Use year, month &amp; day'), sprintf( T_('Permalinks will look like: %s'), '\'stub/2006/12/31/post-title\'' ) ),
								array( 'subchap', T_('Use sub-chapter'), sprintf( T_('Permalinks will look like: %s'), '\'stub/subchap/post-title\'' ) ),
								array( 'chapters', T_('Use chapter path'), sprintf( T_('Permalinks will look like: %s'), '\'stub/chapter/subchap/post-title\'' ) ),
								), T_('Extra path info'), true );

$Form->radio( 'permalink_type', $Settings->get('permalink_type'),
							array(  array( 'urltitle', T_('Post called up by its URL title (Recommended)'), T_('Fallback to ID when no URL title available.') ),
											array( 'pid', T_('Post called up by its ID') ),
											array( 'archive#id', T_('Post on archive page, located by its ID') ),
											array( 'archive#title', T_('Post on archive page, located by its title (for Cafelog compatibility)') )
										), T_('Permalink type'), true );

// fp> TODO: A dynamic javascript preview of how the two settings above combine

// fp> TODO: Move both of these settings to blog/collection settings

$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( T_('Security options') );

$Form->text_input( 'user_minpwdlen', (int)$Settings->get('user_minpwdlen'), 2, T_('Minimum password length'),array( 'note'=>T_('for users.'), 'maxlength'=>2, 'required'=>true ) );

$Form->end_fieldset();

$Form->begin_fieldset( T_('Miscellaneous options') );

$Form->text_input( 'reloadpage_timeout', (int)$Settings->get('reloadpage_timeout'), 5,
								T_('Reload-page timeout'), array( 'note'=>T_('Time (in seconds) that must pass before a request to the same URI from the same IP and useragent is considered as a new hit.'), 'maxlength'=>5, 'required'=>true ) );

$Form->end_fieldset();

// --------------------------------------------

if( $current_User->check_perm( 'options', 'edit' ) )
{
	$Form->end_form( array( array( 'submit', 'submit', T_('Save !'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}

/*
 * $Log$
 * Revision 1.16  2006/11/16 20:36:37  blueyed
 * Save the translators some tedious work..
 *
 * Revision 1.15  2006/09/11 19:35:35  fplanque
 * minor
 *
 * Revision 1.14  2006/09/10 23:40:47  fplanque
 * minor
 *
 * Revision 1.12  2006/09/10 20:59:18  fplanque
 * extended extra path info setting
 *
 * Revision 1.11  2006/08/19 08:50:26  fplanque
 * moved out some more stuff from main
 *
 * Revision 1.10  2006/08/19 07:56:31  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.9  2006/06/15 17:53:38  fplanque
 * minor
 *
 * Revision 1.8  2006/04/27 18:31:06  fplanque
 * no message
 *
 * Revision 1.7  2006/04/24 18:12:54  blueyed
 * Added Setting to invalidate a user account on email address change.
 *
 * Revision 1.6  2006/04/24 17:22:50  blueyed
 * Do not JS-disable options according to "newusers_canregister"
 *
 * Revision 1.5  2006/04/24 15:43:36  fplanque
 * no message
 *
 * Revision 1.4  2006/04/22 03:12:35  blueyed
 * cleanup
 *
 * Revision 1.3  2006/04/22 02:36:38  blueyed
 * Validate users on registration through email link (+cleanup around it)
 *
 * Revision 1.2  2006/04/19 20:13:52  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 */
?>