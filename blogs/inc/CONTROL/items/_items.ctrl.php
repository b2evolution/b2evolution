<?php
/**
 * This file implements the UI controller for the browsing posts.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var AdminUI
 */
global $AdminUI;

/**
 * @var UserSettings
 */
global $UserSettings;


param( 'action', 'string', 'list' );

// Autoselect a blog where we have permission to browse (preferably the last used blog):
$blog = autoselect_blog( param( 'blog', 'integer', 0 ), 'blog_ismember', 1 );

if( ! $blog  )
{ // No blog could be selected
	$Messages->add( sprintf( T_('Since you\'re a newcomer, you\'ll have to wait for an admin to authorize you to post. You can also <a %s>e-mail the admin</a> to ask for a promotion. When you\'re promoted, just reload this page and you\'ll be able to blog. :)'),
									 'href="mailto:'.$admin_email.'?subject=b2evo-promotion"' ), 'error' );
	$action = 'nil';
}
else
{ // We could select a valid blog which we have permission to access:
	$BlogCache = & get_Cache( 'BlogCache' );
	$Blog = & $BlogCache->get_by_ID( $blog );
}


/**
 * Perform action:
 */
switch( $action )
{
 	case 'nil':
		// Do nothing
		break;

	case 'new':
		break;


	case 'edit':
		break;


	case 'create':
		break;


	case 'update':
		break;


	case 'delete':
		break;


	case 'list':
		$AdminUI->title = $AdminUI->title_titlearea = T_('Browse blog:');

		/*
		 * Add sub menu entries:
		 * We do this here instead of _header because we need to include all filter params into regenerate_url()
		 */
		$AdminUI->add_menu_entries(
				'items',
				array(
						'posts' => array(
							'text' => T_('Full posts'),
							'href' => regenerate_url( 'tab', 'tab=posts&amp;filter=restore' ),
							),
						'postlist2' => array(
							'text' => T_('Post list'),
							'href' => regenerate_url( 'tab', 'tab=postlist2&amp;filter=restore' ),
							),
					)
			);

		if( $Blog->get_setting( 'use_workflow' ) )
		{	// We want to use workflow properties for this blog:
			$AdminUI->add_menu_entries(
					'items',
					array(
							'tracker' => array(
								'text' => T_('Tracker'),
								'href' => regenerate_url( 'tab', 'tab=tracker&amp;filter=restore' ),
								),
						)
				);
		}

		$AdminUI->add_menu_entries(
				'items',
				array(
					/*	'commentlist' => array(
							'text' => T_('Comment list'),
							'href' => 'tab=commentlist ), */
						'comments' => array(
							'text' => T_('Comments'),
							'href' => regenerate_url( 'tab', 'tab=comments' ),
							),
					)
			);

		// Store/retrieve preferred tab from UserSettings:
		$tab = $UserSettings->param_Request( 'tab', 'pref_browse_tab', 'string', NULL, true /* memorize */ );

		// Generate available blogs list:
		$blogListButtons = $AdminUI->get_html_collection_list( 'blog_ismember', 1, 'admin.php?ctrl=items&amp;blog=%d&amp;tab='.$tab.'&amp;filter=restore' );

		break;


	default:
		debug_die( 'unhandled action' );
}


$AdminUI->set_path( 'items', !empty($tab) ? $tab : NULL );

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

/**
 * Display payload:
 */
switch( $action )
{
	case 'nil':
		// Do nothing
		break;

	case 'new':
	case 'copy':
	case 'create':
	case 'edit':
	case 'update':
	case 'delete':
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		//$AdminUI->disp_view( '.form.php' );

		// End payload block:
		$AdminUI->disp_payload_end();
		break;

	case 'list':
	default:
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		//$AdminUI->disp_view( '.form.php' );

		// End payload block:
		$AdminUI->disp_payload_end();
		break;
}

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();


/*
 * $Log$
 * Revision 1.1  2006/12/11 18:04:52  fplanque
 * started clean "1-2-3-4" item editing
 *
 */
?>