<?php

namespace Vanderbilt\REDSTAMP\ExternalModule;

class LogicWrapper
{
	public static function foo() {
		$test_calc = "foo bar [not-a-thing][ec_ecrefid]";
		$bf = getBracketedFields(
			val: $test_calc,
			returnFieldDotEvent: true // necessary to not get an empty string
		);

		return $bf;
	}

	/**
	 * For a single unprefixed field name, determine which unique event names
	 * that field is valid on, by resolving field -> form (via project metadata)
	 * and then form -> events (via $Proj->eventsForms). Mirrors the approach
	 * REDCap core itself uses for calc fields (see Classes/Calculate.php).
	 *
	 * @param string $field         Unprefixed field name
	 * @param \Project $Proj
	 * @param array $proj_metadata  Result of $Proj->getMetadata()
	 * @param array $event_id_to_name  Result of $Proj->getUniqueEventNames(), i.e. [event_id => unique_event_name]
	 * @return array                List of unique event names this field is valid on
	 */
	private static function getEventsForField($field, $Proj, $proj_metadata, $event_id_to_name) {
		$form = $proj_metadata[$field]['form_name'] ?? null;
		if ($form === null) {
			return [];
		}

		$events = [];
		foreach ($Proj->eventsForms as $event_id => $forms) {
			if (in_array($form, $forms, true) && isset($event_id_to_name[$event_id])) {
				$events[] = $event_id_to_name[$event_id];
			}
		}
		return $events;
	}

	/**
	 * Evaluates calculation logic across events, automatically filling in missing event prefixes.
	 *
	 * If a field in the logic already has an explicit [event-name][field_name] prefix, that
	 * prefix is always honored as-is. For any field without a prefix, the logic is evaluated
	 * once per event that field is valid on, with the event name injected only for the fields
	 * that are actually valid on that event. Fields left unprefixed for a given event loop
	 * (because they don't live on that event) fall back to REDCap's own default of resolving
	 * against the project's first event (see LogicTester::buildLogicArgs()) - so mixing
	 * unprefixed fields from genuinely different events in one expression can still produce
	 * blank/incorrect values for whichever fields aren't valid on the event being evaluated.
	 *
	 * @param string $logic          The user's raw calculation logic (e.g., "[weight] / [height]")
	 * @param string $record         The record ID being evaluated
	 * @param int    $project_id     The current project ID
	 * @return array                 An associative array of [event_name => evaluation_result]
	 */
	public function evaluateLogicAcrossEvents($logic, $record, $project_id) {
		$Proj = new \Project($project_id);
		$proj_metadata = $Proj->getMetadata();
		$event_id_to_name = $Proj->getUniqueEventNames(); // [event_id => unique_event_name]

		// 1. Tokenize the logic the same way REDCap core does (LogicTester::evaluateLogicSingleRecord,
		// Calculate.php), so checkbox suffixes, smart variables, and event-prefixed fields are handled
		// consistently. Keys are "field_name" (unprefixed) or "event_name.field_name" (prefixed).
		// NOTE: getBracketedFields is from <redcap-root>/Config/init_functions.php
		$bracketed_fields = getBracketedFields(
			val: $logic,
			returnFieldDotEvent: true
		);

		// 2. Split into fields that already have an explicit event prefix (leave untouched) vs.
		// fields with no prefix (need an event resolved for them).
		$unprefixed_fields = [];
		foreach (array_keys($bracketed_fields) as $field) {
			if (strpos($field, ".") !== false) {
				// Already has an explicit event prefix - always honor it as-is.
				continue;
			}
			if (isset($proj_metadata[$field])) {
				$unprefixed_fields[] = $field;
			}
		}

		$results = [];

		// 3. If every field is already prefixed (or there are no fields at all), evaluate once as-is.
		if (empty($unprefixed_fields)) {
			$results['current'] = \LogicTester::evaluateLogicSingleRecord(
				raw_logic: $logic,
				record: $record,
				returnValue: true
			);
			return $results;
		}

		// 4. Build field -> [event_name, ...] only for the unprefixed fields actually in use.
		$field_to_events = [];
		$target_events = [];
		foreach ($unprefixed_fields as $field) {
			$field_to_events[$field] = self::getEventsForField($field, $Proj, $proj_metadata, $event_id_to_name);
			$target_events = array_merge($target_events, $field_to_events[$field]);
		}
		$target_events = array_unique($target_events);

		// 5. Loop through every event where at least one of the unprefixed fields lives, injecting
		// the event name only for the fields valid on that particular event.
		foreach ($target_events as $current_event) {
			$scopedLogic = $logic;

			foreach ($unprefixed_fields as $field) {
				if (in_array($current_event, $field_to_events[$field], true)) {
					// Negative lookbehind ensures we don't re-prefix a field that is already the tail
					// of an [event][field] pair (e.g. don't touch the "[ec_ecrefid]" inside
					// "[logs_arm_1][ec_ecrefid]" when scoping the standalone "[ec_ecrefid]").
					$scopedLogic = preg_replace(
						'/(?<!\])\[' . preg_quote($field, '/') . '\]/',
						"[$current_event][$field]",
						$scopedLogic
					);
				}
			}

			$results[$current_event] = \LogicTester::evaluateLogicSingleRecord(
				raw_logic: $scopedLogic,
				record: $record,
				returnValue: true
			);
		}

		return $results;
	}


}
