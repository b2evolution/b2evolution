gl2evo - Geeklog to b2evolution migration tool

gl2evo is a command line script that imports stories and
comments from Geeklog into b2evolution.

Released under the GNU Public License (GPL) http://www.gnu.org/copyleft/gpl.html
copyright (c)2004 by Jeff Bearer <mail1@jeffbearer.com>


The script was written against geeklog-1.3.9 and b2evolution-0.9.0 so be warned that
it may have problems with other version combinations.


SETUP

There are several variables that need to be set in the script prior
to execution.  There are variables for the geeklog database, b2e
database. author translations, and category translations.

$gl_dbuser	User to Connect to the Geeklog database
$gl_dbpass	Password for the user
$gl_dbhost	Host on which the database is running
$gl_database	Name of the database

$evo_dbuser	User to connect to the b2evolution database
$evo_dbpass	Password for the user
$evo_dbhost	Host on which the database is running
$evo_database	Name of the database

$evo_locale	The default locale for the stories
$evo_root	The location of the evocore directory
$evo_blog_id	The id of the blog (only needed for auto category creation)



AUTHORS

Create the appropriate authors in b2evo first, then map the authors to their geeklog 
id's in the authors array.

Example:
	$author['2']=3;

The example would map UID 2 in Geeklog to Author ID 3 in b2evo.  If assignments are not 
made for every author for which stories are imported, the script will fail.  It is ok 
to map different authors to the same b2evo author id.

CATEGORIES

There are two options for categories, you can create the appropriate categories in 
b2evo first, or use the auto category code.  The auto category code reads the categories
in Geeklog and automatically creates the same categories in b2evo.  With auto categories,
there should be no $category assignments in the configuration section.

Manual categories work like the authors, create the categories, and map each geeklog 
topic name to the b2evo category id.

Example:
	$category['News']=14;
	$category['Blogs']=12;

The example would map TID News in geeklog to Category_ID 14 in b2evo. If assignments 
are not made for every category for which stories imported, the script will fail.


IMAGES

The script translates the path for any images used in geeklog posts.  And images 
need to be copied to the new location.  Copy all files from your geeklog directory 
public_html/images/articles to the b2evo directory blogs/media.


