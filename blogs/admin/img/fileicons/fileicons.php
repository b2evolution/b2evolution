<?php
/**
 * The icons.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 *
 * Most of the default sets icons are from the crystal icon package {@link http://www.everaldo.com/crystal.html}
 *
 */


// icons for special purposes
$this->fileicons_special = array(
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
$this->fileicons = array(
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
