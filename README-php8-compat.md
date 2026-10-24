# b2evolution 7.2.5 on PHP 8.x — the `php8-compat` branch

This branch takes the upstream tag **7.2.5** and makes it run cleanly on **PHP 8.2, 8.3, 8.4 and 8.5**
(tested on 8.5 with a real production site: ~1 000 posts, two custom skins, three custom plugins).
Starting point: 0 deprecations on the public pages, 0 on the back-office pages exercised
(dashboard, post edit, file manager, user edit, settings, widgets, maintenance) — see "What is covered".

Every commit fixes **one family** of PHP 8 incompatibility and nothing else: no reformatting,
no whitespace changes, no refactoring. `git log 7.2.5..php8-compat` reads as a changelog; each
commit can be cherry-picked on its own and reviewed in minutes.

The branch stays **PHP 7.1-compatible** (the oldest version 7.2.5 officially supports is 5.6,
but nullable types `?Type` and `#[...]` attributes — read as comments before PHP 8 — require 7.1).
Nothing else was raised: no `declare(strict_types)`, no typed properties, no `match`.

## Families of fixes (one commit each, in order)

| # | Family | Typical change |
|---|---|---|
| 1 | `E_STRICT` removed (8.0) | `E_ALL & ~E_STRICT` → `E_ALL` in the two places that referenced it |
| 2–3 | `str_replace()` with array/null subjects where a string is expected | explicit casts; new helper `str_replace_in_strings()` in `_misc.funcs.php` |
| 4 | `preg_split()` limit, bool/int casts | `preg_split( ..., -1, ...)`, `(bool)`, `(int)` where a string was implicitly converted |
| 5–6, 8 | "Trying to access array offset on null" (notice → warning) | guards on `$cache[ $coll_ID ]` style lookups (widget caches, shared containers) |
| 7, 12, 17 | **Passing null to non-nullable internal parameters** (8.1) | `trim( $x ?? '' )`, `strlen`, `htmlspecialchars`, `convert_chars()`, `clear_ids_list()`, XML handler callables |
| 9, 13 | **Dynamic property creation** (8.2) — declared properties where the class clearly owns them | `ItemLight::$objtype`, `Hit::$is_*`, `AbstractSettings`, `AdminUI_general::$mode`, `Plugin::$classfile_path`, `ItemListLight::$filterset_name` … |
| 16 | **Dynamic property creation** (8.2) — base classes that are legitimately open bags | `#[\AllowDynamicProperties]` on `DataObject`, `DataObjectCache`, `Widget`, `SQL`, `Plugin`, `Plugins`, `AbstractSettings`, `Session`, `XHTML_Validator`, `LinkOwner`, `Calendar` |
| 10, 14 | Compile-time deprecations | optional parameter before required one (8.0), `"${var}"` interpolation (8.2), `get_class()` without argument inside closures |
| 15 | Misc. compile-time | `case x;` → `case x:`, Parsedown return type `?array`, `archives_plugin` properties |
| 10, 14 | `#[\ReturnTypeWillChange]` | `ExplodeIterator`, `ValidUTF8XMLFilter` (Iterator / php_user_filter) |
| 11, 18 | Bundled libraries (`inc/_ext`) | Swift Mailer (`?Swift_AddressEncoder`, `parent::__construct(...)`), idna_convert, portable_utf8, xmlrpc (`"${var}"`, `(float)`), `curl_close()`/`xml_parser_free()` kept behind `PHP_VERSION_ID < 80000` |
| 19 | `fgetcsv()` `$escape` must be passed explicitly (8.4) | 4 calls, current default `"\\"` kept |
| 20 | `__autoload()` is a compile error (8.0) | dead PHP < 5.1.2 fallback file removed |
| 22 | `imap_open()` returns an `IMAP\Connection` object (8.1), not a resource | `=== false` test instead of `get_resource_type()` in returned-mail and post-by-mail processing |

Also fixed on the way: `intdiv()` where `/` produced floats used as array keys, `(string)` casts
on back-office columns, plugins without code no longer indexed.

## What is covered

Driven by the PHP error log of a real site under PHP 8.5 (`error_reporting=E_ALL`), all
public pages (collections, single posts, archives, tags, search, comments, feeds, sitemap) and
the back-office screens listed above. The remaining log lines are *data* problems
(`libpng warning: iCCP` on some uploaded PNGs), not code.

**imap**: the extension left PHP core in 8.4; install the PECL build (`php8.5-imap` on Debian/Surý,
`install-php-extensions imap` in Docker). Returned-mail processing (cron) and post-by-mail are fixed for
the object API (commit 22) and run in production.

**Not exercised**: the installer and upgrader (`/install/`), XML-RPC *clients*, e-mail campaigns and
automations runtime, most bundled plugins beyond rendering. Deprecations found there are welcome as issues.

## Trying it

A ready-made Docker bench (MariaDB + `php:8.5-apache`) that boots this tree with **your own**
dump, media, skins and plugins is published separately, so that this branch stays a pure patch
series: <https://github.com/DrYoEU/b2evolution-php8-bench>. Clone it, drop your site in
`overlay/`, `bin/setup.sh`, `docker compose up -d --build`, `bin/setup.sh --db`, then
`tail -f log/php-error.log`.

For production: `git fetch` this branch, check out, keep your `conf/`, `media/`, skins and
plugins untouched — the branch changes nothing under `conf/` and nothing in skins.

## Contributing

* Keep the rule: **one family per commit, zero reformatting**, commit message says *why* and
  *which PHP version* introduced the deprecation.
* Lint before pushing (note: `php -l` only reads the **first** file it is given):
  `find inc plugins skins -name '*.php' | while read f; do php -l "$f" | grep -v '^No syntax'; done`
* New deprecation seen in a log: open an issue with the exact line, the PHP version and how to
  reach it in the UI.

Related upstream discussion: b2evolution/b2evolution issue #122.
Licence unchanged: GNU GPL v2, as upstream.
