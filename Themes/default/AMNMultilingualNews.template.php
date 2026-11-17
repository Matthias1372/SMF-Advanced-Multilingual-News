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

function template_amn_multilingualnews_editnews()
{
	global $context, $txt;
	$all_group_ids = array_map('intval', array_keys($context['amn_groups']));

	echo '
	<div class="cat_bar">
		<h3 class="catbg">', $txt['amn_title'], '</h3>
	</div>
	<div class="information">', $txt['amn_description'], '</div>';

	if (!empty($context['saved_successful']))
		echo '<div class="infobox">', $txt['amn_saved'], '</div>';

	echo '
	<form action="', $context['amn_form_action'], '" method="post" accept-charset="UTF-8">';

	foreach ($context['amn_news'] as $number => $entry)
	{
		$id = (int) $entry['id_news'];
		echo '
		<div class="cat_bar" id="amn-entry-', $id, '">
			<h3 class="catbg">', sprintf($txt['amn_entry'], $number + 1), '</h3>
		</div>
		<div class="windowbg padding">
			<div class="floatright">
				<label><input type="checkbox" name="remove[]" value="', $id, '"', !empty($context['amn_remove']) && in_array($id, $context['amn_remove'], true) ? ' checked' : '', '> ', $txt['amn_delete'], '</label>
			</div>
			<p><label><input type="checkbox" name="entries[', $id, '][enabled]" value="1"', !empty($entry['enabled']) ? ' checked' : '', '> ', $txt['amn_enabled'], '</label></p>';

		foreach ($context['amn_languages'] as $lang => $label)
		{
			$value = isset($entry['texts'][$lang]) ? $entry['texts'][$lang] : '';
			$preview = isset($entry['previews'][$lang]) ? $entry['previews'][$lang] : '';
			echo '
			<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px 20px;margin-bottom:16px;">
				<div>
					<div style="margin-bottom:5px;"><strong>', htmlspecialchars($label, ENT_QUOTES, 'UTF-8'), '</strong>', $lang === $context['amn_default_language'] ? ' <span class="smalltext">(' . $txt['amn_fallback'] . ')</span>' : '', '</div>
					<textarea name="entries[', $id, '][texts][', htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'), ']" rows="6" cols="80" class="input_text" style="width:100%;box-sizing:border-box;">', $value, '</textarea>
				</div>
				<div>
					<div style="margin-bottom:5px;"><strong>', $txt['amn_preview'], '</strong></div>
					<div class="windowbg" style="min-height:9em;padding:10px;box-sizing:border-box;overflow:auto;">', $preview !== '' ? $preview : '<span class="smalltext">' . $txt['amn_preview_empty'] . '</span>', '</div>
				</div>
			</div>';
		}

		$all_groups_selected = empty(array_diff($all_group_ids, $entry['groups']));
		echo '
			<div>
				<button type="submit" name="amn_preview_news" value="', $id, '" class="button" formaction="', $context['amn_form_action'], '#amn-entry-', $id, '">', $txt['amn_preview_button'], '</button>
			</div>
			<strong>', $txt['amn_groups'], '</strong>
			<div style="margin-top:8px;">
				<label><input type="checkbox" class="amn-toggle-groups" data-group-container="amn-groups-', $id, '"', $all_groups_selected ? ' checked' : '', '> <strong>', $txt['amn_toggle_groups'], '</strong></label>
			</div>
			<div id="amn-groups-', $id, '" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:4px 16px;margin-top:8px;">';
		foreach ($context['amn_groups'] as $group_id => $group_name)
			echo '<label><input type="checkbox" name="entries[', $id, '][groups][]" value="', (int) $group_id, '"', in_array((int) $group_id, $entry['groups'], true) ? ' checked' : '', '> ', htmlspecialchars($group_name, ENT_QUOTES, 'UTF-8'), '</label>';
		echo '
			</div>
		</div><br>';
	}

	// New entry form.
	$new_entry = $context['amn_new_entry'];
	echo '
	<div class="cat_bar" id="amn-entry-new"><h3 class="catbg">', $txt['amn_add'], '</h3></div>
	<div class="windowbg padding">
		<p><label><input type="checkbox" name="new_entry[enabled]" value="1"', !empty($new_entry['enabled']) ? ' checked' : '', '> ', $txt['amn_enabled'], '</label></p>';
	foreach ($context['amn_languages'] as $lang => $label)
	{
		$value = isset($new_entry['texts'][$lang]) ? $new_entry['texts'][$lang] : '';
		$preview = isset($new_entry['previews'][$lang]) ? $new_entry['previews'][$lang] : '';
		echo '
		<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px 20px;margin-bottom:16px;">
			<div>
				<div style="margin-bottom:5px;"><strong>', htmlspecialchars($label, ENT_QUOTES, 'UTF-8'), '</strong>', $lang === $context['amn_default_language'] ? ' <span class="smalltext">(' . $txt['amn_fallback'] . ')</span>' : '', '</div>
				<textarea name="new_entry[texts][', htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'), ']" rows="6" cols="80" class="input_text" style="width:100%;box-sizing:border-box;">', $value, '</textarea>
			</div>
			<div>
				<div style="margin-bottom:5px;"><strong>', $txt['amn_preview'], '</strong></div>
				<div class="windowbg" style="min-height:9em;padding:10px;box-sizing:border-box;overflow:auto;">', $preview !== '' ? $preview : '<span class="smalltext">' . $txt['amn_preview_empty'] . '</span>', '</div>
			</div>
		</div>';
	}

	$all_new_groups_selected = empty(array_diff($all_group_ids, $new_entry['groups']));
	echo '
		<div>
			<button type="submit" name="amn_preview_news" value="new" class="button" formaction="', $context['amn_form_action'], '#amn-entry-new">', $txt['amn_preview_button'], '</button>
		</div>
		<strong>', $txt['amn_groups'], '</strong>
		<div style="margin-top:8px;">
			<label><input type="checkbox" class="amn-toggle-groups" data-group-container="amn-groups-new"', $all_new_groups_selected ? ' checked' : '', '> <strong>', $txt['amn_toggle_groups'], '</strong></label>
		</div>
		<div id="amn-groups-new" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:4px 16px;margin-top:8px;">';
	foreach ($context['amn_groups'] as $group_id => $group_name)
		echo '<label><input type="checkbox" name="new_entry[groups][]" value="', (int) $group_id, '"', in_array((int) $group_id, $new_entry['groups'], true) ? ' checked' : '', '> ', htmlspecialchars($group_name, ENT_QUOTES, 'UTF-8'), '</label>';

	echo '
		</div>
	</div>
	<div class="buttonlist righttext" style="margin-top:1em;">
		<input type="submit" name="amn_save_news" value="', $txt['save'], '" class="button">
		<input type="submit" name="amn_delete_news" value="', $txt['editnews_remove_selected'], '" class="button you_sure" data-confirm="', $txt['editnews_remove_confirm'], '">
	</div>
	<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
	<input type="hidden" name="amn_form_complete" value="1">
	</form>
	<script>
	Array.prototype.forEach.call(document.querySelectorAll(".amn-toggle-groups"), function (toggle) {
		var container = document.getElementById(toggle.getAttribute("data-group-container"));
		if (!container)
			return;

		var options = container.querySelectorAll("input[type=checkbox]");
		var syncToggle = function () {
			var checked = 0;
			Array.prototype.forEach.call(options, function (option) {
				if (option.checked)
					checked++;
			});
			toggle.checked = options.length > 0 && checked === options.length;
			toggle.indeterminate = checked > 0 && checked < options.length;
		};

		toggle.addEventListener("change", function () {
			Array.prototype.forEach.call(options, function (option) {
				option.checked = toggle.checked;
			});
			toggle.indeterminate = false;
		});

		Array.prototype.forEach.call(options, function (option) {
			option.addEventListener("change", syncToggle);
		});
		syncToggle();
	});
	</script>';
}
