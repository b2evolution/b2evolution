echo Getting updates from Transifex
# tx pull -a --debug
tx pull -f -l fr_FR

echo "Copy fr_FR (transifex) to fr_FR locale (b2evo)"
cp translations/b2evolution.messages/fr_FR.po ../locales/fr_FR/LC_MESSAGES/messages.po
