<?php
/**
 * This file implements the UI controler for status management.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * @package admin
 *
 * @author fplanque: François PLANQUE
 *
 * @version $Id$
 */

/**
 * Includes:
 */
require_once dirname(__FILE__). '/_header.php';

$AdminUI->setPath( 'options', 'statuses' );
$admin_pagetitle = T_('Settings').$admin_path_seprator.T_('Post statuses management');
$edited_table = 'T_poststatuses';
$edited_table_IDcol = 'pst_ID';
$edited_table_namecol = 'pst_name';
$edited_table_orderby = 'pst_ID ASC';
$edited_name_maxlen = 40;

require dirname(__FILE__). '/_listeditor.php';
?>