@echo off
echo Updating all .po files with latest translatable strings...
dir /b /s ..\blogs\*.php > files.txt
xgettext -D ../blogs/ -f files.txt --add-comments=TRANS --copyright-holder="Francois PLANQUE" --msgid-bugs-address=http://fplanque.net/ --output=..\blogs\locales\messages.pot --keyword=T_ --keyword=NT_
xchangecl +d! ..\blogs\locales\messages.pot !C:\www\b2evolution\blogs\!..\..\..\!
msgmerge -U ..\blogs\locales\cs_CZ\LC_MESSAGES\messages.po ..\blogs\locales\messages.pot
msgmerge -U ..\blogs\locales\de_DE\LC_MESSAGES\messages.po ..\blogs\locales\messages.pot
msgmerge -U ..\blogs\locales\es_ES\LC_MESSAGES\messages.po ..\blogs\locales\messages.pot
msgmerge -U ..\blogs\locales\fr_FR\LC_MESSAGES\messages.po ..\blogs\locales\messages.pot
msgmerge -U ..\blogs\locales\it_IT\LC_MESSAGES\messages.po ..\blogs\locales\messages.pot
msgmerge -U ..\blogs\locales\ja_JP\LC_MESSAGES\messages.po ..\blogs\locales\messages.pot
msgmerge -U ..\blogs\locales\lt_LT\LC_MESSAGES\messages.po ..\blogs\locales\messages.pot
msgmerge -U ..\blogs\locales\nb_NO\LC_MESSAGES\messages.po ..\blogs\locales\messages.pot
msgmerge -U ..\blogs\locales\nl_NL\LC_MESSAGES\messages.po ..\blogs\locales\messages.pot
msgmerge -U ..\blogs\locales\pt_BR\LC_MESSAGES\messages.po ..\blogs\locales\messages.pot
msgmerge -U ..\blogs\locales\sv_SE\LC_MESSAGES\messages.po ..\blogs\locales\messages.pot
msgmerge -U ..\blogs\locales\zh_CN\LC_MESSAGES\messages.po ..\blogs\locales\messages.pot
msgmerge -U ..\blogs\locales\zh_TW\LC_MESSAGES\messages.po ..\blogs\locales\messages.pot

