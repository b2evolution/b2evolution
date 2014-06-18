# phpsvnclient #

**phpsvnclient** is a class that can perform read operations from a SVN server
(over WebDAV).

It can be used to retrieve files from an SVN repository in pure PHP, thus
without using separate programs or the need to have SVN support within PHP.

## Details ##

It accesses a given remote SVN repository using the WebDAV protocol and
performs several types of operations:

* List all files in a given SVN repository directory
* Retrieve a given revision of a file
* Retrieve the log of changes made in a repository or in a given file between
  two revisions
* Get the repository's latest revision

## License ##

New BSD License
http://www.opensource.org/licenses/bsd-license.php

## Authors ##

saddor
ethansmith1
deadpan110
khartnjava

## Resources ##

phpsvnclient is hosted at Google Code.

* http://code.google.com/p/phpsvnclient/
* http://code.google.com/p/phpsvnclient/wiki/phpsvnclient

## Examples ##

Fetch file contents in SVN repository:

    <?php
    require_once 'phpsvnclient.php';
    $phpsvnclient = new phpsvnclient('http://phpsvnclient.googlecode.com/svn/');
    $file_content = $phpsvnclient->getFile('trunk/phpsvnclient.php');
    echo $file_content;
    ?>

Dump raw SVN description information about a folder:

    <?php
    require_once 'phpsvnclient.php';
    $phpsvnclient = new phpsvnclient('http://phpsvnclient.googlecode.com/svn/');
    $raw_dump = $phpsvnclient->rawDirectoryDump('/trunk/');
    print_r($raw_dump);
    ?>
