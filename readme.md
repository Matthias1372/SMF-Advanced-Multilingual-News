# Advanced Multilingual News

Advanced Multilingual News adds language-specific and member-group-aware forum news to Simple Machines Forum 2.1 without modifying SMF core files.

## Compatibility

- SMF 2.1.0–2.1.99
- PHP versions supported by the installed SMF release

## Features

- Separate text for every installed SMF language
- Visibility rules for guests, regular members, normal member groups, and post-count-based groups
- News selection based on SMF's active runtime language, including browser-detected guest languages
- Fallback to the forum's default language when a translation is empty
- BBC support
- Per-language BBC preview beside every news text field
- One-click selection or deselection of all groups per news item
- Integration-hook-based implementation without core or theme source modifications
- Import of existing SMF news during installation
- Restoration of enabled default-language entries to SMF's standard news setting during uninstallation

## Installation

1. Open **Admin Center → Package Manager → Download Packages**.
2. Upload `AdvancedMultilingualNews-1.0.12.zip`.
3. Install the package.

Existing SMF news is imported on the first installation and assigned to all known groups.

## Administration

Open **Admin Center → News and Newsletters → News**.

For each news item, enter the desired translations and select every group allowed to see it. An item with no selected groups is visible to nobody.

## Language behavior

The displayed news follows the language active in the current SMF request. For guests, this includes languages selected by browser-language detection extensions. When the selected language has no text, the forum default language is used as a fallback.

## Uninstallation

Before its tables are removed, the package writes enabled entries in the forum's default language back to SMF's standard news setting.

## Changelog

See [changelog.md](changelog.md).

## License

BSD 3-Clause License. See [LICENSE](LICENSE).
