#!/bin/sh
#
# This script builds the source documentation of b2evolution,
# using PhpDocumentor.
#
# 1. Adjust PHPDOC setting
# 2. Adjust PHP setting
# 3. Link "smarty_template" into PhpDocumentor's templates dir, e.g.:
#    ln -s /home/daniel/cvs/b2evo.HEAD/phpdoc/smarty_template/ /usr/local/pear/PhpDocumentor/phpDocumentor/Converters/HTML/Smarty/templates/b2evo

# The path to phpdoc executable
PHPDOC="/usr/local/pear/PhpDocumentor/phpdoc"

# Set this to the PHP interpreter to use. PHP 5.2+ highly recommended!
export PHP="php -d memory_limit=1024M"

TIMESTAMPFILE="create_new_doc.timestamp"

RSYNC_TARGET="doc.b2evolution.net:/var/www/vhosts/evodoc/web/HEAD/"

# halt on any error:
set -e

# change to script's directory
cd `dirname $0`

# Test if there are new files (newer than $TIMESTAMPFILE):
if [ -e "$TIMESTAMPFILE" ]; then
	if exec find .. -name 'CVS' -prune -o -type f -newer "$TIMESTAMPFILE" -print -quit|grep -q -v -E '.+'; then
		echo "No new files found. Exiting."
		exit
	fi
else
	echo "No timestamp file yet."
fi

# Remove old generated doc
/bin/rm -rf build/*

# Generate documentation
echo "Running phpdoc.."
$PHPDOC --title 'b2evolution Technical Documentation (CVS HEAD)' \
--directory .. \
--ignoresymlinks on \
--target build/ \
--output HTML:Smarty:b2evo \
--ignore _idna_convert_npdata.ser.inc,Connections/,CVS/,gettext/,simpletest/,Templates/,img/,locales/,rsc/,media/,tests/,doc/,extras/,skins/babyblues/,skins/guadeloupe/,skins/wpc_aubmach/,*.gif,*.jpg,*.png,*.css,*.po*,*.mo*,*.bak,*.html,*.sql,*.xml,*.bpd,*.mpd,*.log,*.htaccess,*_TEST.php \
--parseprivate off \
--defaultpackagename main \
--defaultcategoryname Documentation \
--sourcecode on \
--readmeinstallchangelog license.txt

# Publish it:
# First without source files (not so important)
rsync -avt --del build/ --exclude=__filesource/ "$RSYNC_TARGET"
rsync -avt --del build/ "$RSYNC_TARGET"

echo "Touching timestamp file"
touch "$TIMESTAMPFILE"

echo "Done."
