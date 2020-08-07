echo Generate file list.
# ending slash on purpose for sed to find and not confuse with /blogs/ in strings
# exclude: events module and events skin
find .. -name "*.php" -not -wholename "*/_tests/*" > files.txt

echo "Extract General strings: T_(), NT_(), TS_()"
xgettext -D . -f files.txt --package-version=7 --no-wrap --add-comments=TRANS --copyright-holder="Francois Planque" --msgid-bugs-address=http://fplanque.com/ -o messages.pot --keyword=T_ --keyword=NT_ --keyword=TS_ --package-name=b2evolution --sort-by-file
#echo Correct paths.
sed -i .bak "s#:\ \.\./#: ../../../#g" messages.pot
#echo Correct Header.
sed -i .bak s/CHARSET/UTF-8/ messages.pot
echo Copy to locales folder.
cp messages.pot ../locales/

echo "Extract Backoffice strings: TB_()"
xgettext -D . -f files.txt --package-version=7 --no-wrap --add-comments=TRANS --copyright-holder="Francois Planque" --msgid-bugs-address=http://fplanque.com/ -o messages-backoffice.pot --keyword=TB_ --package-name="b2evolution backoffice" --sort-by-file
#echo Correct paths.
sed -i .bak "s#:\ \.\./#: ../../../#g" messages-backoffice.pot
#echo Correct Header.
sed -i .bak s/CHARSET/UTF-8/ messages-backoffice.pot
echo Copy to locales folder.
cp messages-backoffice.pot ../locales/


echo "Extract Demo Content strings: TD_()"
xgettext -D . -f files.txt --package-version=7 --no-wrap --add-comments=TRANS --copyright-holder="Francois Planque" --msgid-bugs-address=http://fplanque.com/ -o messages-demo-contents.pot --keyword=TD_ --package-name="b2evolution demo contents" --sort-by-file
#echo Correct paths.
sed -i .bak "s#:\ \.\./#: ../../../#g" messages-demo-contents.pot
#echo Correct Header.
sed -i .bak s/CHARSET/UTF-8/ messages-demo-contents.pot
echo Copy to locales folder.
cp messages-demo-contents.pot ../locales/





#echo Correct Header.
#sed -i .bak s/PACKAGE/b2evolution/ ../blogs/locales/messages.pot
#sed -i .bak "s/# SOME DESCRIPTIVE TITLE./# b2evolution - Language file/" ../blogs/locales/messages.pot
#sed -i .bak s/YEAR/2016/ ../blogs/locales/messages.pot
#sed -i .bak s/CHARSET/UTF-8/ ../blogs/locales/messages.pot

#xchangecl +d! -t# ..\blogs\locales\messages.pot !#35##32#SOME#32#DESCRIPTIVE#32#TITLE.!#35##32#b2evolution#32#-#32#Language#32#file!#35##32#This#32#file#32#is#32#distributed#32#under#32#the#32#same#32#license#32#as#32#the#32#PACKAGE#32#package.!#35##32#This#32#file#32#is#32#distributed#32#under#32#the#32#same#32#license#32#as#32#the#32#b2evolution#32#package.!"Content-Type:#32#text/plain;#32#charset=CHARSET\n"!"Content-Type:#32#text/plain;#32#charset=iso-8859-1\n"!

#echo Merge with French.
#msgmerge -U --no-wrap ..\blogs\locales\fr_FR\LC_MESSAGES\messages.po ..\blogs\locales\messages.pot
#REM echo Change comments.
#REM xchangecl +d! -t# ..\blogs\locales\fr_FR\LC_MESSAGES\messages.po !#35#.#32#TRANS:!#35##32#TRANS:!
