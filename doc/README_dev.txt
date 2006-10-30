README for (plugin) developers
==============================


Audience
--------
This document is target at (plugin) developers and documents changes in the
(Plugin) API.


1.9
---
 - Because of using PHP5's overloading mechanism for ``Plugin::Settings`` and
   ``Plugin::UserSettings``, the hackish solution of checking for
   ``isset($this->Settings)`` in ``Plugin::GetDefaultSettings()`` and
   ``isset($this->UserSettings)`` in ``Plugin::GetDefaultUserSettings()`` to see
   if the Settings get queried for being displayed for editing will not
   work anymore.
   Instead, ``$params['for_editing']``, passed to ``GetDefaultSettings()`` and
   ``GetDefaultUserSettings`` will be either true or false.

