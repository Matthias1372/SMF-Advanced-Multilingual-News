# Changelog

All notable changes to Advanced Multilingual News are documented in this file.

## 1.0.12

- Added a direct-access guard to the template file for consistency and defense in depth.

## 1.0.11

- Removed obsolete Preview-target validation and an unused template class.
- Removed the redundant global pre-include hook; functional hooks load their source file directly.
- Restricts submitted group IDs to groups currently available in SMF.
- Made the initial import of existing SMF news transactional.

## 1.0.10

- Removed the separators below the Preview buttons and all CSS added for their spacing.

## 1.0.9

- Uses a dedicated padded separator wrapper so SMF theme rules cannot collapse the spacing below Preview buttons.

## 1.0.8

- Increased the spacing between each Preview button and the separator below it.

## 1.0.7

- Keeps every populated preview visible when another news item's Preview button is used.
- Places the separator clearly below each Preview button.
- Adds a select/deselect-all checkbox to every news item's group selection.

## 1.0.6

- Moved each language's news text field into the left column below its language label.
- Added a matching BBC preview field in the right column.
- Added a per-news-item Preview button that renders all language versions without saving and preserves unsaved form values.

## 1.0.5

- Made saving and deleting news entries atomic by using database transactions.
- Rejects incomplete or manipulated admin form submissions before any data is changed.
- Removed the ineffective SSI hook and its unused wrapper functions.
- Removed unused query fields, dead conditions, and redundant includes.
- Kept this release upgrade-free because the AMN-prefixed package is a new modification.

## 1.0.4

- Standardized the internal prefix as `AMN`/`amn` in files, functions, hooks, templates, language keys, request fields, context values, CSS classes, and database tables.
- Added this GitHub-oriented `readme.md`.
- Removed the version history from `readme.txt` and moved it into `changelog.md`.

## 1.0.3

- Moved integration-hook registration and removal completely into `package-info.xml`.
- Removed all upgrade blocks and the obsolete upgrade script.
- Replaced the inline package description with the Package Manager reference to `readme.txt`.

## 1.0.2

- Moved language-dependent news selection to `integrate_load_theme`, after SMF has completed user-language detection.
- Fixed guest news when a browser-language detection extension selects a language different from the forum default.

## 1.0.1

- Changed news selection to use SMF's active runtime language.
- Added fallback behavior for missing translations.

## 1.0.0

- Initial release with multilingual news text, per-group visibility, SMF news import, and restoration of standard SMF news during uninstallation.
