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
	);


/**
 * These are the file icons. The extension is a regular expression that must match the end of the file.
 */
$this->fileicons = array(
	'.html?' => 'www.png',
	'.mp3' => 'sound.png',
	'.tar' => 'tar.png',
	'.tgz' => 'tgz.png',
	'.txt' => 'document.png',
	'.zip' => 'zip.png',

	);

?>
