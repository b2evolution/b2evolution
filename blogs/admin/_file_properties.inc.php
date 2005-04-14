<?php
/**
 * This file implements the UI controller for file upload.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: François PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

if( false )
{	/**
	 * This is ugly, sorry, but I temporarily need this until NuSphere fixes their CodeInsight :'(
	 */
	include('_header.php');
	include('files.php');
}

// Begin payload block:
$AdminUI->dispPayloadBegin();

$Form = & new Form( 'files.php' );

$Form->global_icon( T_('Close properties!'), 'close',	$Fileman->getCurUrl( array( 'fm_mode' => false, 'forceFM' => 1 ) ) );

$Form->begin_form( 'fform', T_('File properties') );

$Form->end_form();



// End payload block:
$AdminUI->dispPayloadEnd();

/*
 * $Log$
 * Revision 1.1  2005/04/14 18:34:02  fplanque
 * filemanager refactoring
 *
 */
?>