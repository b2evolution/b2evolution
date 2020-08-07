echo Getting updates from Transifex
# tx pull -a --debug
tx pull -f -l ru_RU

echo "Copy ru_RU (transifex) to ru_RU locale (b2evo)"
cp translations/b2evolution.messages/ru_RU.po ../locales/ru_RU/LC_MESSAGES/messages.po
cp translations/b2evolution.messages-backoffice/ru_RU.po ../locales/ru_RU/LC_MESSAGES/messages-backoffice.po
cp translations/b2evolution.messages-demo-contents/ru_RU.po ../locales/ru_RU/LC_MESSAGES/messages-demo-contents.po
