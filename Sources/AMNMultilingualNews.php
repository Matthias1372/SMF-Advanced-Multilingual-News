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

/**
 * Replace SMF's standard news editor with the multilingual/group-aware editor.
 *
 * @param array $subActions
 * @return void
 */
function amn_multilingualnews_manage_news(&$subActions)
{
	$subActions['editnews'] = array('amn_multilingualnews_edit_news', 'edit_news');
}

/**
 * Apply the multilingual/group-aware news list after SMF has finished building
 * $user_info. This timing is important for language-detection mods that change
 * the guest language from integrate_user_info.
 *
 * @return void
 */
function amn_multilingualnews_load_theme()
{
	amn_multilingualnews_apply_news();
}

/**
 * Resolve and apply the news list for the current request.
 *
 * @return void
 */
function amn_multilingualnews_apply_news()
{
	global $modSettings, $user_info, $language;

	if (defined('PACKAGE_MANAGER'))
		return;

	// At this point Browser Language Detection and all other integrate_user_info
	// callbacks have finished. $user_info['language'] is therefore authoritative.
	$current_language = !empty($user_info['language'])
		? $user_info['language']
		: (!empty($language) ? $language : (!empty($modSettings['language']) ? $modSettings['language'] : 'english'));

	$groups = !empty($user_info['groups']) ? array_map('intval', (array) $user_info['groups']) : array(-1);

	$modSettings['news'] = implode("\n", amn_multilingualnews_get_visible_news($current_language, $groups));
}

/**
 * Main admin page for multilingual news.
 *
 * @return void
 */
function amn_multilingualnews_edit_news()
{
	global $context, $txt, $scripturl, $sourcedir, $modSettings, $language;

	isAllowedTo('edit_news');
	loadLanguage('AMNMultilingualNews');
	loadTemplate('AMNMultilingualNews');
	require_once($sourcedir . '/Subs-Post.php');

	$languages = amn_multilingualnews_get_languages();
	$groups = amn_multilingualnews_get_groups();
	$news = amn_multilingualnews_get_admin_news();
	$new_entry = array(
		'enabled' => true,
		'texts' => array(),
		'groups' => array_keys($groups),
		'previews' => array(),
	);

	if (!empty($_POST['amn_save_news']))
	{
		checkSession();
		amn_multilingualnews_save_news($languages, $groups);
		$context['saved_successful'] = true;
		logAction('news');
		$news = amn_multilingualnews_get_admin_news();
	}
	elseif (!empty($_POST['amn_delete_news']) && !empty($_POST['remove']))
	{
		checkSession();
		amn_multilingualnews_delete_news((array) $_POST['remove']);
		$context['saved_successful'] = true;
		logAction('news');
		$news = amn_multilingualnews_get_admin_news();
	}
	elseif (isset($_POST['amn_preview_news']))
	{
		checkSession();
		if (empty($_POST['amn_form_complete']))
			fatal_lang_error('amn_incomplete_form', false);

		$submitted_entries = !empty($_POST['entries']) && is_array($_POST['entries']) ? $_POST['entries'] : array();
		foreach ($news as &$entry)
		{
			$id = (int) $entry['id_news'];
			if (!isset($submitted_entries[$id]) || !is_array($submitted_entries[$id]))
				continue;

			$submitted = $submitted_entries[$id];
			$entry = amn_multilingualnews_prepare_submitted_entry($entry, $submitted, $languages, $groups);
			$entry['previews'] = amn_multilingualnews_build_previews($submitted, $languages);
		}
		unset($entry);

		$submitted_new = !empty($_POST['new_entry']) && is_array($_POST['new_entry']) ? $_POST['new_entry'] : array();
		$new_entry = amn_multilingualnews_prepare_submitted_entry($new_entry, $submitted_new, $languages, $groups);
		$new_entry['previews'] = amn_multilingualnews_build_previews($submitted_new, $languages);

		$context['amn_remove'] = !empty($_POST['remove']) && is_array($_POST['remove'])
			? array_values(array_unique(array_map('intval', $_POST['remove'])))
			: array();
	}

	$context['amn_languages'] = $languages;
	$context['amn_groups'] = $groups;
	$context['amn_news'] = $news;
	$context['amn_new_entry'] = $new_entry;
	$context['page_title'] = $txt['amn_title'];
	$context['sub_template'] = 'amn_multilingualnews_editnews';

	// Keep the native admin area/subsection selected.
	if (!empty($context['admin_menu_name']))
		$context[$context['admin_menu_name']]['current_subsection'] = 'editnews';

	$context['amn_form_action'] = $scripturl . '?action=admin;area=news;sa=editnews';
	$context['amn_default_language'] = !empty($modSettings['language']) ? $modSettings['language'] : $language;
}

/**
 * Preserve submitted values for a preview without writing them to the database.
 * Text is escaped for safe output inside textarea elements.
 *
 * @param array $entry
 * @param array $submitted
 * @param array $languages
 * @param array $groups
 * @return array
 */
function amn_multilingualnews_prepare_submitted_entry($entry, $submitted, $languages, $groups)
{
	$entry['enabled'] = !empty($submitted['enabled']);
	$entry['texts'] = array();
	$submitted_texts = !empty($submitted['texts']) && is_array($submitted['texts']) ? $submitted['texts'] : array();
	foreach (array_keys($languages) as $lang)
		$entry['texts'][$lang] = htmlspecialchars(isset($submitted_texts[$lang]) ? (string) $submitted_texts[$lang] : '', ENT_QUOTES, 'UTF-8');

	$entry['groups'] = amn_multilingualnews_filter_groups(isset($submitted['groups']) ? $submitted['groups'] : array(), $groups);

	return $entry;
}

/**
 * Keep only valid IDs from the groups currently selectable in SMF.
 *
 * @param mixed $submitted_groups
 * @param array $groups
 * @return array
 */
function amn_multilingualnews_filter_groups($submitted_groups, $groups)
{
	if (!is_array($submitted_groups))
		return array();

	$allowed = array_fill_keys(array_map('intval', array_keys($groups)), true);
	$filtered = array();
	foreach ($submitted_groups as $group)
	{
		if ((!is_int($group) && !is_string($group)) || !preg_match('~^-?\d+$~D', (string) $group))
			continue;

		$group = (int) $group;
		if (isset($allowed[$group]))
			$filtered[$group] = $group;
	}

	return array_values($filtered);
}

/**
 * Render every submitted language version with SMF's BBC parser.
 *
 * @param array $submitted
 * @param array $languages
 * @return array
 */
function amn_multilingualnews_build_previews($submitted, $languages)
{
	$previews = array();
	$submitted_texts = !empty($submitted['texts']) && is_array($submitted['texts']) ? $submitted['texts'] : array();
	foreach (array_keys($languages) as $lang)
	{
		$body = isset($submitted_texts[$lang]) ? trim((string) $submitted_texts[$lang]) : '';
		if ($body === '')
		{
			$previews[$lang] = '';
			continue;
		}

		$body = htmlspecialchars($body, ENT_QUOTES, 'UTF-8');
		preparsecode($body);
		$previews[$lang] = preg_replace(
			'~<([/]?)form[^>]*?[>]*>~i',
			'<em class="smalltext">&lt;$1form&gt;</em>',
			parse_bbc($body)
		);
	}

	return $previews;
}

/**
 * Return installed SMF languages in a stable, display-ready form.
 *
 * @return array
 */
function amn_multilingualnews_get_languages()
{
	global $language;

	$languages = array();
	if (function_exists('getLanguages'))
	{
		$available = getLanguages();
		foreach ($available as $key => $info)
		{
			$filename = '';
			$name = '';

			if (is_array($info))
			{
				$filename = !empty($info['filename']) ? $info['filename'] : (is_string($key) ? $key : '');
				$name = !empty($info['name']) ? $info['name'] : (!empty($info['native_name']) ? $info['native_name'] : $filename);
			}
			elseif (is_string($key))
			{
				$filename = $key;
				$name = (string) $info;
			}

			$filename = strtr((string) $filename, './\\:', '____');
			if ($filename !== '')
				$languages[$filename] = $name !== '' ? $name : $filename;
		}
	}

	if (empty($languages))
		$languages[$language] = ucfirst(str_replace('_', ' ', $language));

	natcasesort($languages);
	return $languages;
}

/**
 * Return selectable groups including SMF's special guest/member groups.
 *
 * @return array
 */
function amn_multilingualnews_get_groups()
{
	global $smcFunc, $txt;

	$groups = array(
		-1 => isset($txt['membergroups_guests']) ? $txt['membergroups_guests'] : 'Guests',
		0 => isset($txt['membergroups_members']) ? $txt['membergroups_members'] : 'Regular Members',
	);

	$request = $smcFunc['db_query']('', '
		SELECT id_group, group_name
		FROM {db_prefix}membergroups
		ORDER BY CASE WHEN id_group < {int:post_group} THEN id_group ELSE {int:post_group} END, min_posts, group_name',
		array('post_group' => 4)
	);
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$groups[(int) $row['id_group']] = $row['group_name'];
	$smcFunc['db_free_result']($request);

	return $groups;
}

/**
 * Load all entries for the admin editor.
 *
 * @return array
 */
function amn_multilingualnews_get_admin_news()
{
	global $smcFunc;

	$entries = array();

	$request = $smcFunc['db_query']('', '
		SELECT id_news, enabled
		FROM {db_prefix}amn_multilingual_news
		ORDER BY position, id_news',
		array()
	);
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$id = (int) $row['id_news'];
		$entries[$id] = array(
			'id_news' => $id,
			'enabled' => !empty($row['enabled']),
			'texts' => array(),
			'groups' => array(),
		);
	}
	$smcFunc['db_free_result']($request);

	if (empty($entries))
		return $entries;

	$request = $smcFunc['db_query']('', '
		SELECT id_news, language, body
		FROM {db_prefix}amn_multilingual_news_texts
		WHERE id_news IN ({array_int:ids})',
		array('ids' => array_keys($entries))
	);
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$id = (int) $row['id_news'];
		if (isset($entries[$id]))
			$entries[$id]['texts'][$row['language']] = un_preparsecode($row['body']);
	}
	$smcFunc['db_free_result']($request);

	$request = $smcFunc['db_query']('', '
		SELECT id_news, id_group
		FROM {db_prefix}amn_multilingual_news_groups
		WHERE id_news IN ({array_int:ids})',
		array('ids' => array_keys($entries))
	);
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$id = (int) $row['id_news'];
		if (isset($entries[$id]))
			$entries[$id]['groups'][] = (int) $row['id_group'];
	}
	$smcFunc['db_free_result']($request);

	return array_values($entries);
}

/**
 * Save existing entries plus the optional new entry.
 *
 * @param array $languages
 * @param array $groups
 * @return void
 */
function amn_multilingualnews_save_news($languages, $groups)
{
	global $smcFunc;

	if (empty($_POST['amn_form_complete']))
		fatal_lang_error('amn_incomplete_form', false);

	$posted = !empty($_POST['entries']) && is_array($_POST['entries']) ? $_POST['entries'] : array();
	$posted_ids = array();
	foreach (array_keys($posted) as $id)
	{
		if ((!is_int($id) && !ctype_digit((string) $id)) || (int) $id <= 0)
			fatal_lang_error('amn_incomplete_form', false);
		if (!is_array($posted[$id]))
			fatal_lang_error('amn_incomplete_form', false);

		$posted_ids[] = (int) $id;
	}
	$posted_ids = array_values(array_unique($posted_ids));
	sort($posted_ids, SORT_NUMERIC);

	$current_ids = array();
	$request = $smcFunc['db_query']('', '
		SELECT id_news
		FROM {db_prefix}amn_multilingual_news
		ORDER BY id_news',
		array()
	);
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$current_ids[] = (int) $row['id_news'];
	$smcFunc['db_free_result']($request);

	if ($posted_ids !== $current_ids)
		fatal_lang_error('amn_incomplete_form', false);

	$position = 0;
	if (!$smcFunc['db_transaction']('begin'))
		fatal_lang_error('amn_database_error', false);

	try
	{
		foreach ($posted as $id => $entry)
		{
			$id = (int) $id;
			$position++;
			$enabled = !empty($entry['enabled']) ? 1 : 0;
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}amn_multilingual_news
				SET position = {int:position}, enabled = {int:enabled}
				WHERE id_news = {int:id_news}',
				array('position' => $position, 'enabled' => $enabled, 'id_news' => $id)
			);
			amn_multilingualnews_save_entry_details($id, $entry, $languages, $groups);
		}

		// Add a new item if at least one language contains text.
		$new = !empty($_POST['new_entry']) && is_array($_POST['new_entry']) ? $_POST['new_entry'] : array();
		$has_text = false;
		if (!empty($new['texts']) && is_array($new['texts']))
		{
			foreach ($new['texts'] as $body)
				if (trim((string) $body) !== '')
				{
					$has_text = true;
					break;
				}
		}

		if ($has_text)
		{
			$position++;
			$new_id = $smcFunc['db_insert']('insert',
				'{db_prefix}amn_multilingual_news',
				array('position' => 'int', 'enabled' => 'int'),
				array(array($position, !empty($new['enabled']) ? 1 : 0)),
				array('id_news'),
				1
			);
			if (empty($new_id))
				throw new RuntimeException('Could not insert news entry.');

			amn_multilingualnews_save_entry_details((int) $new_id, $new, $languages, $groups);
		}

		if (!$smcFunc['db_transaction']('commit'))
			throw new RuntimeException('Could not commit news changes.');
	}
	catch (Throwable $e)
	{
		$smcFunc['db_transaction']('rollback');
		fatal_lang_error('amn_database_error', false);
	}
}

/**
 * Save translations and allowed groups for one entry.
 *
 * @param int $id_news
 * @param array $entry
 * @param array $languages
 * @param array $groups
 * @return void
 */
function amn_multilingualnews_save_entry_details($id_news, $entry, $languages, $groups)
{
	global $smcFunc;

	$smcFunc['db_query']('', 'DELETE FROM {db_prefix}amn_multilingual_news_texts WHERE id_news = {int:id_news}', array('id_news' => $id_news));
	$smcFunc['db_query']('', 'DELETE FROM {db_prefix}amn_multilingual_news_groups WHERE id_news = {int:id_news}', array('id_news' => $id_news));

	$text_rows = array();
	$texts = !empty($entry['texts']) && is_array($entry['texts']) ? $entry['texts'] : array();
	foreach (array_keys($languages) as $lang)
	{
		$body = isset($texts[$lang]) ? trim((string) $texts[$lang]) : '';
		if ($body === '')
			continue;

		$body = htmlspecialchars($body, ENT_QUOTES, 'UTF-8');
		preparsecode($body);
		$text_rows[] = array($id_news, $lang, $body);
	}
	if (!empty($text_rows))
		$smcFunc['db_insert']('insert', '{db_prefix}amn_multilingual_news_texts',
			array('id_news' => 'int', 'language' => 'string-64', 'body' => 'string'),
			$text_rows,
			array('id_news', 'language')
		);

	$group_rows = array();
	$selected_groups = amn_multilingualnews_filter_groups(isset($entry['groups']) ? $entry['groups'] : array(), $groups);
	foreach ($selected_groups as $group)
		$group_rows[] = array($id_news, $group);
	if (!empty($group_rows))
		$smcFunc['db_insert']('insert', '{db_prefix}amn_multilingual_news_groups',
			array('id_news' => 'int', 'id_group' => 'int'),
			$group_rows,
			array('id_news', 'id_group')
		);
}

/**
 * Delete selected entries.
 *
 * @param array $ids
 * @return void
 */
function amn_multilingualnews_delete_news($ids)
{
	global $smcFunc;
	$ids = array_values(array_filter(array_unique(array_map('intval', $ids)), function ($id) {
		return $id > 0;
	}));
	if (empty($ids))
		return;

	if (!$smcFunc['db_transaction']('begin'))
		fatal_lang_error('amn_database_error', false);

	try
	{
		$smcFunc['db_query']('', 'DELETE FROM {db_prefix}amn_multilingual_news_texts WHERE id_news IN ({array_int:ids})', array('ids' => $ids));
		$smcFunc['db_query']('', 'DELETE FROM {db_prefix}amn_multilingual_news_groups WHERE id_news IN ({array_int:ids})', array('ids' => $ids));
		$smcFunc['db_query']('', 'DELETE FROM {db_prefix}amn_multilingual_news WHERE id_news IN ({array_int:ids})', array('ids' => $ids));

		if (!$smcFunc['db_transaction']('commit'))
			throw new RuntimeException('Could not commit news deletion.');
	}
	catch (Throwable $e)
	{
		$smcFunc['db_transaction']('rollback');
		fatal_lang_error('amn_database_error', false);
	}
}

/**
 * Get news visible to the supplied language/groups.
 *
 * @param string $current_language
 * @param array $groups
 * @return array
 */
function amn_multilingualnews_get_visible_news($current_language, $groups)
{
	global $smcFunc, $modSettings, $language;

	$current_language = strtr((string) $current_language, './\\:', '____');
	$fallback = !empty($modSettings['language']) ? strtr($modSettings['language'], './\\:', '____') : $language;
	$groups = array_values(array_unique(array_map('intval', (array) $groups)));
	if (empty($groups))
		$groups = array(-1);

	// An entry with no group rows is deliberately visible to nobody.
	$request = $smcFunc['db_query']('', '
		SELECT n.id_news, n.position,
			COALESCE(cur.body, def.body) AS body
		FROM {db_prefix}amn_multilingual_news AS n
		INNER JOIN {db_prefix}amn_multilingual_news_groups AS g ON (g.id_news = n.id_news)
		LEFT JOIN {db_prefix}amn_multilingual_news_texts AS cur ON (cur.id_news = n.id_news AND cur.language = {string:current_language})
		LEFT JOIN {db_prefix}amn_multilingual_news_texts AS def ON (def.id_news = n.id_news AND def.language = {string:fallback_language})
		WHERE n.enabled = {int:enabled}
			AND g.id_group IN ({array_int:groups})
			AND COALESCE(cur.body, def.body) IS NOT NULL
		GROUP BY n.id_news, n.position, cur.body, def.body
		ORDER BY n.position, n.id_news',
		array(
			'current_language' => $current_language,
			'fallback_language' => $fallback,
			'enabled' => 1,
			'groups' => $groups,
		)
	);

	$news = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$news[] = $row['body'];
	$smcFunc['db_free_result']($request);

	return $news;
}
