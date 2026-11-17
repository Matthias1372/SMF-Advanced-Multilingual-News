<?php

/**
 * Advanced Multilingual News for SMF 2.1
 *
 * @package AdvancedMultilingualNews
 * @version 1.0.12
 * @author Matthias
 * @copyright 2026 Matthias
 * @license BSD-3-Clause
 */

if (!defined('SMF'))
	die('No direct access...');

global $smcFunc, $modSettings, $language;

// Preserve the fallback/default-language versions as ordinary SMF news.
$fallback = !empty($modSettings['language']) ? $modSettings['language'] : $language;
$news = array();
$request = $smcFunc['db_query']('', '
	SELECT t.body
	FROM {db_prefix}amn_multilingual_news AS n
	INNER JOIN {db_prefix}amn_multilingual_news_texts AS t ON (t.id_news = n.id_news AND t.language = {string:language})
	WHERE n.enabled = {int:enabled}
	ORDER BY n.position, n.id_news',
	array('language' => $fallback, 'enabled' => 1)
);
while ($row = $smcFunc['db_fetch_assoc']($request))
	$news[] = $row['body'];
$smcFunc['db_free_result']($request);
updateSettings(array('news' => implode("\n", $news)));

db_extend('packages');
$smcFunc['db_drop_table']('{db_prefix}amn_multilingual_news_groups');
$smcFunc['db_drop_table']('{db_prefix}amn_multilingual_news_texts');
$smcFunc['db_drop_table']('{db_prefix}amn_multilingual_news');
