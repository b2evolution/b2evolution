<?php
/**
 * This file implements the UI controller for managing comments.
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


/*
 * Init the objects we want to work on.
 */
switch( $action )
{
	case 'edit':
	case 'update':
		param( 'comment_ID', 'integer', true );
		$edited_Comment = Comment_get_by_ID( $comment_ID );

		$edited_Comment_Item = & $edited_Comment->get_Item();
		$blog = $edited_Comment_Item->blog_ID;
		$BlogCache = & get_Cache( 'BlogCache' );
		$Blog = & $BlogCache->get_by_ID( $blog );
		break;

	default:
		debug_die( 'unhandled action 1' );
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
		// Check permission:
		$current_User->check_perm( 'blog_comments', 'any', true, $blog );

		$AdminUI->title = $AdminUI->title_titlearea = T_('Editing comment').' #'.$edited_Comment->ID;
		break;


	case 'create':
		break;


	case 'update':
		// Check permission:
		$current_User->check_perm( 'blog_comments', '', true, $blog );

		// fp> TODO: $edited_Comment->load_from_Request( true );

		if( ! $edited_Comment->get_author_User() )
		{ // If this is not a member comment
			param( 'newcomment_author', 'string', true );
			param( 'newcomment_author_email', 'string' );
			param( 'newcomment_author_url', 'string' );
			$edited_Comment->set( 'author', $newcomment_author );
			$edited_Comment->set( 'author_email', $newcomment_author_email );
			$edited_Comment->set( 'author_url', $newcomment_author_url );

			// CHECK url
			if( $error = validate_url( $newcomment_author_url, $allowed_uri_scheme ) )
			{
				$Messages->add( T_('Supplied URL is invalid: ').$error, 'error' );
			}
		}
		param( 'content', 'html' );
		param( 'post_autobr', 'integer', ($comments_use_autobr == 'always') ? 1 : 0 );

		// CHECK and FORMAT content
		$content = format_to_post( $content, $post_autobr, 0); // We are faking this NOT to be a comment
		$edited_Comment->set( 'content', $content );

		if( $current_User->check_perm( 'edit_timestamp' ))
		{ // We use user date
			param_date( 'comment_issue_date', T_('Please enter a valid comment date.'), true );
			if( strlen(get_param('comment_issue_date')) )
			{ // only set it, if a date was given:
				param_time( 'comment_issue_time' );
				$edited_Comment->set( 'date', form_date( get_param( 'comment_issue_date' ), get_param( 'comment_issue_time' ) ) ); // TODO: cleanup...
			}
		}

		param( 'comment_status', 'string', 'published' );
		$edited_Comment->set_from_Request( 'status', 'comment_status' );

		if( $Messages->count('error') )
		{	// There have been some validation errors:
			break;
		}

		// UPDATE DB:
		$edited_Comment->dbupdate();	// Commit update to the DB

		$location = url_add_param( $admin_url, 'ctrl=items&blog='.$blog.'&p='.$edited_Comment->item_ID, '&' );
		header_redirect( $location );
		exit();
		break;


	case 'publish':
		break;


	case 'deprecate':
		break;

	case 'delete':
		break;


	case 'list':
		break;


	default:
		debug_die( 'unhandled action 2' );
}


/*
 * Page navigation:
 */

$AdminUI->set_path( 'items', 'comments' );

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
	case 'create':

	case 'edit':
	case 'update':	// on error
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		// Display VIEW:
		$AdminUI->disp_view( 'comments/_comment.form.php' );


		// End payload block:
		$AdminUI->disp_payload_end();
		break;

	case 'view':
		break;

	case 'list':
	default:
		// Begin payload block:
		$AdminUI->disp_payload_begin();


		// End payload block:
		$AdminUI->disp_payload_end();
		break;
}

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();


/*
 * $Log$
 * Revision 1.1  2006/12/12 02:01:52  fplanque
 * basic comment controller
 *
 */
?>