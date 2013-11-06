<?php
/**
 * This is the HTML template of email message for newsletter
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

global $htsrv_url;

// Default params:
$params = array_merge( array(
		'message' => '',
	), $params );

echo nl2br( $params['message'] );

echo '<br /><br />';
echo T_( 'If you don\'t want to receive this newsletter anymore, please click here:' ).' ';
echo get_link_tag( $htsrv_url.'quick_unsubscribe.php?type=newsletter&user_ID=$user_ID$&key=$unsubscribe_key$' );
?>
