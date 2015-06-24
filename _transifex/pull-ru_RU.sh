echo Getting updates from Transifex
# tx pull -a
tx pull -l ru_RU

echo "Copy ru_RU (transifex) to ru_RU locale (b2evo)"
cp translations/b2evolution.messages/ru_RU.po ../locales/ru_RU/LC_MESSAGES/messages.po
