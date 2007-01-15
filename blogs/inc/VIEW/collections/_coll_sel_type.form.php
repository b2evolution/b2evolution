<?php
/**
 * This file implements the UI view for the General blog properties.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

echo '<h2>'.T_('What kind of blog would you like to create?').'</h2>';

echo '<table cellspacing="20">';

	echo '<tr>';
		echo '<td><h3><a href="?ctrl=collections&amp;action=new&amp;kind=std">'.T_('Standard').' &raquo;</a></h3></td>';
		echo '<td>'.T_('A standard blog with the most common features.').'<td>';
	echo '</tr>';

	echo '<tr>';
		echo '<td><h3><a href="?ctrl=collections&amp;action=new&amp;kind=photo">'.T_('Photoblog').' &raquo;</a></h3></td>';
		echo '<td>'.T_('A blog optimized to publishing photos.').'<td>';
	echo '</tr>';

echo '</table>';

echo '<p>'.T_('Your selection here will pre-configure your blog in order to optimize it for a particular use. Nothing is final though. You can change all the settings at any time and any kind of blog can be transformed into any other at any time.').'</p>';


/*
 * $Log$
 * Revision 1.1  2007/01/15 00:38:06  fplanque
 * pepped up "new blog" creation a little. To be continued.
 *
 *
 */
?>