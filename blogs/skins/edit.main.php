<?php
/**
 * This file is the template that includes required css files to display edit form
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Post ID, go from $_GET when we edit post from Front-office
$post_ID = param( 'p', 'integer', 0 );

check_item_perm_edit( $post_ID );

require $ads_current_skin_path.'index.main.php';

/*
 * $Log$
 * Revision 1.1  2011/10/13 11:40:10  efy-yurybakh
 * In skin posting (permission)
 *
 *
 */
?>