<?php
/**
 * This file implements the controller for item statuses management.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
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
 * PROGIDISTRI S.A.S. grants Francois PLANQUE the right to license
 * PROGIDISTRI S.A.S.'s contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var AdminUI
 */
global $AdminUI;

/**
 * @var User
 */
global $current_User;

global $dispatcher;
		
// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );

$tab = param( 'tab', 'string', 'settings', true );

/**
 * We need make this call to build menu for all modules
 */
$AdminUI->set_path( 'items' );

/*
 * Add sub menu entries:
 * We do this here instead of _header because we need to include all filter params into regenerate_url()
 */
attach_browse_tabs();

$AdminUI->set_path( 'items', 'settings', 'statuses' );

$AdminUI->breadcrumbpath_init();
$AdminUI->breadcrumbpath_add( T_('Contents'), '?ctrl=items&amp;blog=$blog$&amp;tab=full&amp;filter=restore' );
$AdminUI->breadcrumbpath_add( T_('Settings'), '?ctrl=itemtypes&amp;blog=$blog$&amp;tab=settings&amp;tab3=statuses' );
$AdminUI->breadcrumbpath_add( T_('Post statuses'), '?ctrl=itemtypes&amp;blog=$blog$&amp;tab=settings&amp;tab3=statuses' );



$list_title = T_('Post statuses');
$default_col_order = 'A';
$edited_name_maxlen = 40;
$perm_name = 'options';
$perm_level = 'edit';
$form_below_list = true;

/**
 * Delete restrictions
 */
$delete_restrictions = array(
		array( 'table'=>'T_items__item', 'fk'=>'post_pst_ID', 'msg'=>T_('%d related items') ),
	);

$restrict_title = T_('Cannot delete item status');	 //&laquo;%s&raquo;

// Used to know if the element can be deleted, so to display or not display confirm delete dialog (true:display, false:not display)
// It must be initialized to false before checking the delete restrictions
$checked_delete = false;

$GenericElementCache = & new GenericCache( 'GenericElement', false, 'T_items__status', 'pst_', 'pst_ID' );

require $inc_path.'generic/inc/_generic_listeditor.php';

/*
 * $Log$
 * Revision 1.8  2010/01/19 21:10:28  efy-yury
 * update: crumbs
 *
 * Revision 1.6  2009/12/12 01:13:08  fplanque
 * A little progress on breadcrumbs on menu structures alltogether...
 *
 * Revision 1.5  2009/12/06 22:55:16  fplanque
 * Started breadcrumbs feature in admin.
 * Work in progress. Help welcome ;)
 * Also move file settings to Files tab and made FM always enabled
 *
 * Revision 1.4  2009/09/24 13:50:32  efy-sergey
 * Moved the Global Settings>Post types & Post statuses tabs to "Posts / Comments > Settings > Post types & Post statuses"
 *
 * Revision 1.3  2009/03/08 23:57:43  fplanque
 * 2009
 *
 * Revision 1.2  2008/01/21 09:35:31  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 11:00:23  fplanque
 * MODULES (refactored MVC)
 *
 */
?>