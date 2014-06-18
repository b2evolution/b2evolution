<?php
/**
 * This file implements the UI view for the General blog properties.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _coll_sel_type.view.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

echo '<h2>'.T_('What kind of blog would you like to create?').get_manual_link('collection-type').'</h2>';

echo '<table class="coll_kind">';

if( $blog_kinds = get_collection_kinds() )
{
	foreach( $blog_kinds as $kind => $info )
	{
		echo '<tr>';
			echo '<td class="coll_kind"><h3><a href="?ctrl=collections&amp;action=new-selskin&amp;kind='.$kind.'">'.$info['name'].' &raquo;</a></h3></td>';
			echo '<td>'.$info['desc'].'<td>';
		echo '</tr>';
	}
}
else
{
	echo '<tr>';
		echo '<td class="coll_kind"><h3><a href="?ctrl=collections&amp;action=new-selskin&amp;kind=std">'.T_('Standard').' &raquo;</a></h3></td>';
		echo '<td>'.T_('A standard blog with the most common features.').'<td>';
	echo '</tr>';
}

echo '</table>';

echo '<p>'.T_('Your selection here will pre-configure your blog in order to optimize it for a particular use. Nothing is final though. You can change all the settings at any time and any kind of blog can be transformed into any other at any time.').'</p>';

?>