echo Getting updates from Transifex
# tx pull -a
tx pull -l de_DE

echo "Copy de_DE (transifex) to de_DE locale (b2evo)"
cp translations/b2evolution.messages/de_DE.po ../locales/de_DE/LC_MESSAGES/messages.po
