echo Getting updates from Transifex
# tx pull -a --debug
tx pull -f -l de_DE

echo "Copy de_DE (transifex) to de_DE locale (b2evo)"
cp translations/b2evolution.messages/de_DE.po ../locales/de_DE/LC_MESSAGES/messages.po
cp translations/b2evolution.messages-backoffice/de_DE.po ../locales/de_DE/LC_MESSAGES/messages-backoffice.po
cp translations/b2evolution.messages-demo-contents/de_DE.po ../locales/de_DE/LC_MESSAGES/messages-demo-contents.po
