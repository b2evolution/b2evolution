<?php
/**
 * This is the HTML template of email message for post by email report
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Default params:
$params = array_merge( array(
		'Items' => NULL,
	), $params );


$Items = $params['Items'];

echo T_('You just created the following posts:').'<br /><br />';

foreach( $Items as $Item )
{
	echo format_to_output( $Item->title ).'<br />';
	echo get_link_tag( $Item->get_permanent_url() ).'<br /><br />';
}
?>