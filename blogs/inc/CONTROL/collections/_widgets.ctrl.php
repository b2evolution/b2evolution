<?php
/**
 * This file implements the UI controller for managing widgets inside of a blog.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal Open Source relicensing agreement:
 * }}
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

load_class( 'MODEL/collections/_componentwidget.class.php' );

param( 'action', 'string', 'list' );

/*
 * Init the objects we want to work on.
 */
if( ! valid_blog_requested() )
{
	debug_die( 'Invalid blog requested' );
}
$current_User->check_perm( 'blog_properties', 'edit', true, $blog );

// Get Skin used by current Blog:
$SkinCache = & get_Cache( 'SkinCache' );
$Skin = & $SkinCache->get_by_ID( $Blog->skin_ID );
// Make sure containers are loaded for that skin:
$container_list = $Skin->get_containers();

switch( $action )
{
 	case 'nil':
 	case 'list':
		// Do nothing
		break;

	case 'create':
		param( 'type', 'string', true );
		param( 'code', 'string', true );
	case 'new':
		param( 'container', 'string', true, true );	// memorize
		break;

	default:
		debug_die( 'Init objects: unhandled action' );
}

$core_componentwidget_codes = array(
		'coll-title',
    'coll-tagline',
    'coll-longdesc',
	);


/**
 * Perform action:
 */
switch( $action )
{
 	case 'nil':
 	case 'new':
		// Do nothing
		break;

	case 'create':
		// Add a Widget to container:
		if( !in_array( $container, $container_list ) )
		{
			$Messages->add( T_('You can only add to containers of the current skin.'), 'error' );
			$action = 'list';
			break;
		}

		if( $type != 'core' )
		{
			debug_type( 'Unhandled widget type' );
		}

		if( !in_array( $code, $core_componentwidget_codes ) )
		{
			debug_type( 'Unhandled widget code' );
		}

		$sql = 'INSERT INTO T_widget( wi_coll_ID, wi_sco_name, wi_type, wi_code, wi_params )
						VALUES ( '.$Blog->ID.', '.$DB->quote($container).', '.$DB->quote($type).', '.$DB->quote($code).', NULL )';
		$DB->query( $sql, 'Insert widget' );

		$Messages->add( T_('The widget has been added to the container.'), 'success' );
		header_redirect( '?ctrl=widgets&blog='.$Blog->ID );
		break;

 	case 'list':
		break;

	default:
		debug_die( 'Action: unhandled action' );
}


/**
 * Display page header, menus & messages:
 */
$blogListButtons = $AdminUI->get_html_collection_list( 'blog_properties', 'edit',
											'?ctrl=widgets&amp;blog=%d', T_('List'), '?ctrl=collections&amp;blog=0' );

$AdminUI->set_path( 'blogs', 'widgets' );

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
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		// Display VIEW:
		$AdminUI->disp_view( 'collections/_available_widgets.view.php' );

		// End payload block:
		$AdminUI->disp_payload_end();
		break;


	case 'edit':
	case 'update':	// on error
		// Begin payload block:
		$AdminUI->disp_payload_begin();


		// End payload block:
		$AdminUI->disp_payload_end();
		break;


	case 'list':
	default:
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		// Display VIEW:
		$AdminUI->disp_view( 'collections/_widget_list.view.php' );

		// End payload block:
		$AdminUI->disp_payload_end();
		break;
}

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();


/*
 * $Log$
 * Revision 1.1  2007/01/08 21:55:42  fplanque
 * very rough widget handling
 *
 */
?>