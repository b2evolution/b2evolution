<?php
/**
 * This file implements the file icons for the File class. {{{
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 *
 * Most of the default sets icons are from the crystal icon package {@link http://www.everaldo.com/crystal.html}
 *
 * @version $Id$ }}}
 *
 */

/**
 * Icons for special purposes
 */
$fm_fileicons_special = array(
	'unknown' => 'default.png',  // icon for unknown files
	'folder' => 'folder.png',    // icon for folders
	'parent' => 'up.png',        // go to parent directory
	'home' => 'folder_home2.png', // home folder
	'empty' => 'empty.png',      // empty file

	'ascending' => 'ascending.png',
	'descending' => 'descending.png',

	'edit' => 'edit.png',
	'copymove' => 'editcopy.png',
	'rename' => 'item_rename.png',
	'delete' => 'editdelete.png',

	'window_new' => 'window_new.png',
);


/**
 * These are the file icons. The extension is a regular expression that must match the end of the file.
 */
$fm_fileicons = array(
	'.(gif|png|jpe?g)' => 'image2.png',
	'.html?' => 'www.png',
	'.log' => 'log.png',
	'.(mp3|ogg|wav)' => 'sound.png',
	'.(mpe?g|avi)' => 'video.png',
	'.msg' => 'message.png',
	'.pdf' => 'pdf-document.png',
	'.php[34]?' => 'php.png',
	'.(pgp|gpg)' => 'encrypted.png',
	'.tar' => 'tar.png',
	'.tgz' => 'tgz.png',
	'.te?xt' => 'document.png',
	'.(zip|rar)' => 'pk.png',
);

?>
