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
PHP="/home/daniel/cvs/PHP_5_2/sapi/cli/php"
export PHP


# change to script's directory
cd `dirname $0`

# Remove old generated doc
/bin/rm -rf build/*

# Generate documentation
$PHPDOC --title 'b2evolution Technical Documentation (CVS HEAD)' \
--directory `dirname $0`/.. \
--target build/ \
--output HTML:Smarty:b2evo \
--ignore _idna_convert_npdata.ser.inc,Connections/,CVS/,gettext/,simpletest/,Templates/,img/,locales/,rsc/,media/,tests/,doc/,extras/,skins/babyblues/,skins/guadeloupe/,skins/wpc_aubmach/,*.gif,*.jpg,*.png,*.css,*.po*,*.mo*,*.bak,*.html,*.sql,*.xml,*.bpd,*.mpd,*.log,*.htaccess,*_TEST.php \
--hidden off \
--parseprivate off \
--defaultpackagename main \
--defaultcategoryname Documentation \
--sourcecode on \
--readmeinstallchangelog license.txt

# Publish it:
rsync -avt  build/ doc.b2evolution.net:/var/www/vhosts/evodoc/web/HEAD/
