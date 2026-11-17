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

db_extend('packages');

$smcFunc['db_create_table']('{db_prefix}amn_multilingual_news',
	array(
		array('name' => 'id_news', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true),
		array('name' => 'position', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0),
		array('name' => 'enabled', 'type' => 'tinyint', 'size' => 1, 'unsigned' => true, 'default' => 1),
	),
	array(array('type' => 'primary', 'columns' => array('id_news'))),
	array(),
	'ignore'
);

$smcFunc['db_create_table']('{db_prefix}amn_multilingual_news_texts',
	array(
		array('name' => 'id_news', 'type' => 'int', 'size' => 10, 'unsigned' => true),
		array('name' => 'language', 'type' => 'varchar', 'size' => 64, 'default' => ''),
		array('name' => 'body', 'type' => 'mediumtext'),
	),
	array(
		array('type' => 'primary', 'columns' => array('id_news', 'language')),
		array('type' => 'index', 'columns' => array('language')),
	),
	array(),
	'ignore'
);

$smcFunc['db_create_table']('{db_prefix}amn_multilingual_news_groups',
	array(
		array('name' => 'id_news', 'type' => 'int', 'size' => 10, 'unsigned' => true),
		array('name' => 'id_group', 'type' => 'int', 'size' => 10, 'default' => 0),
	),
	array(
		array('type' => 'primary', 'columns' => array('id_news', 'id_group')),
		array('type' => 'index', 'columns' => array('id_group')),
	),
	array(),
	'ignore'
);

// Import existing SMF news once, using the forum's default language and all currently known groups.
$count = 0;
$request = $smcFunc['db_query']('', 'SELECT COUNT(*) AS total FROM {db_prefix}amn_multilingual_news', array());
if ($row = $smcFunc['db_fetch_assoc']($request))
	$count = (int) $row['total'];
$smcFunc['db_free_result']($request);

if ($count === 0 && !empty($modSettings['news']))
{
	$default_language = !empty($modSettings['language']) ? $modSettings['language'] : $language;
	$all_groups = array(-1, 0);
	$request = $smcFunc['db_query']('', 'SELECT id_group FROM {db_prefix}membergroups', array());
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$all_groups[] = (int) $row['id_group'];
	$smcFunc['db_free_result']($request);
	$all_groups = array_unique($all_groups);

	if (!$smcFunc['db_transaction']('begin'))
		throw new RuntimeException('Could not start the news import transaction.');

	try
	{
		$position = 0;
		foreach (explode("\n", $modSettings['news']) as $line)
		{
			if (trim($line) === '')
				continue;
			$position++;
			$id = $smcFunc['db_insert']('insert', '{db_prefix}amn_multilingual_news',
				array('position' => 'int', 'enabled' => 'int'), array(array($position, 1)), array('id_news'), 1);
			if (empty($id))
				throw new RuntimeException('Could not create an imported news entry.');
			$smcFunc['db_insert']('insert', '{db_prefix}amn_multilingual_news_texts',
				array('id_news' => 'int', 'language' => 'string-64', 'body' => 'string'),
				array(array((int) $id, $default_language, $line)), array('id_news', 'language'));
			$rows = array();
			foreach ($all_groups as $group)
				$rows[] = array((int) $id, (int) $group);
			$smcFunc['db_insert']('insert', '{db_prefix}amn_multilingual_news_groups',
				array('id_news' => 'int', 'id_group' => 'int'), $rows, array('id_news', 'id_group'));
		}

		if (!$smcFunc['db_transaction']('commit'))
			throw new RuntimeException('Could not commit the news import.');
	}
	catch (Throwable $e)
	{
		$smcFunc['db_transaction']('rollback');
		throw $e;
	}
}

