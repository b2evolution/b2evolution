@echo off

echo Generate file list.
dir /b /s ..\blogs\*.php > files.txt

echo Extract strings.
xgettext -D ../blogs/ -f files.txt --no-wrap --add-comments=TRANS --copyright-holder="Francois PLANQUE" --msgid-bugs-address=http://fplanque.net/ --output=..\blogs\locales\messages.pot --keyword=T_ --keyword=NT_ --keyword=TS_

echo Correct paths.
xchangecl +d! ..\blogs\locales\messages.pot !D:\www\b2evo20\blogs\!..\..\..\!

echo Correct Header.
xchangecl +d! -t# ..\blogs\locales\messages.pot !#35##32#SOME#32#DESCRIPTIVE#32#TITLE.!#35##32#b2evolution#32#-#32#Language#32#file!#35##32#This#32#file#32#is#32#distributed#32#under#32#the#32#same#32#license#32#as#32#the#32#PACKAGE#32#package.!#35##32#This#32#file#32#is#32#distributed#32#under#32#the#32#same#32#license#32#as#32#the#32#b2evolution#32#package.!"Content-Type:#32#text/plain;#32#charset=CHARSET\n"!"Content-Type:#32#text/plain;#32#charset=iso-8859-1\n"!

echo Merge with French.
msgmerge -U --no-wrap ..\blogs\locales\fr_FR\LC_MESSAGES\messages.po ..\blogs\locales\messages.pot

REM echo Change comments.
REM xchangecl +d! -t# ..\blogs\locales\fr_FR\LC_MESSAGES\messages.po !#35#.#32#TRANS:!#35##32#TRANS:!
