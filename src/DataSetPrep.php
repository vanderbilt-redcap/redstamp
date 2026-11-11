<?php

namespace Vanderbilt\REDSTAMP\ExternalModule;

// for setting up exports
class DataSetPrep
{
	public array $fields = [];
	public array $required_variables;
	public array $result;
	private array $always = [
		0,
		"STUDYID",
		"DOMAIN",
		"USUBJID"
	];

	public function __construct(array $result, array $fields, $return_all_fields = false) {
		$this->result = $result;
		$this->collateFields($fields, $return_all_fields);
	}

	public function collateFields($fields, $return_all_fields = false) {
		if ($return_all_fields ?? false) {
			$sdtm_fields_for_domain = array_column($fields, "Variable Name");
			$this->fields = array_values($sdtm_fields_for_domain);
			return;
		}

		// equivalent to array_filter for required then array_col for variable name
		// NOTE: these are ordinal (specified by Variable Order), but CDISC currently returns them in this order to begin with
		// so long as a reordering doesn't occur, we're getting it for free throughout its processing
		$required_variables = array_reduce(
			$fields,
			function ($retain, $item) {
				if (($item['Core'] ?? false) === "Req") {
					$retain[] = $item["Variable Name"];
				}
				return $retain;
			}
		);
		$this->required_variables = $required_variables;

		$sdtm_fields_actually_mapped = [];

		foreach (array_keys($this->result) as $r) {
			[$_, $sdtm_variable, $_] = explode("__", $r);

			$sdtm_fields_actually_mapped[$sdtm_variable] = true;
		}

		// using array_flip to mimic a set
		$return_fields = array_flip($required_variables) + $sdtm_fields_actually_mapped;

		$this->fields = array_keys($return_fields);
	}

	private function genObj() {
		// HACK: ensure each output has all fields needed to populate DataTAble
		$tmpl = [];

		// HACK: make sure globals are at front
		foreach ($this->always as $k) {
			$tmpl[$k] = "";
		}

		$vals = array_fill_keys(
			array_values($this->fields),
			""
		);

		return $tmpl + $vals;
	}

	public function prepareOutput($domain = "AE") {
		$result = $this->result;

		$df = array_filter(
			$result,
			fn ($m) => $m['domain'] === $domain
		);

		// TODO: pull studyid, usubjid, from overall settings

		$output = [];

		// FIXME: each entry must have ALL columns for the domain
		foreach ($df as $m) {
			[$uid_dom, $uid_sdtm_var, $uid_col] = explode("__", $m["uid"]);

			$this_output = $this->genObj();
			// very sparse
			// need to promote studyid and usubjid
			// need to link uid_col
			// need to ensure only 1 val is being broadcast

			// FIXME: actually source these from user input
			$hack_studyid = "placeholder studyid";
			$hack_usubjid = "placeholder usubj";
			foreach ($m['result'] as $source_event => $r) {
				$this_output[0] = "col {$uid_col}, var {$uid_sdtm_var}, broadcast from: {$source_event}"; // HACK
				$this_output["DOMAIN"] = $uid_dom;
				$this_output["{$uid_sdtm_var}"] = $r;

				$this_output["STUDYID"] = $hack_studyid;
				$this_output["USUBJID"] = $hack_usubjid;

				$output[] = $this_output;
			}
		}

		$new_output = $output;
		// clean up output by removing any sdtm variables that weren't actually mapped
		$clean_array = false;
		if ($clean_array) {
			$b = array_diff_key($this->fields, array_flip($this->always));
			$b = array_keys($b);
			$new_output = array_filter($output, function ($output_n) use ($b) {
				// Check if at least one key from $b is NOT empty
				foreach ($b as $b_m) {
					if (isset($output_n[$b_m]) && $output_n[$b_m] !== "") {
						return true; // Keep this element
					}
				}
				return false; // Remove if ALL requested keys are empty strings
			});
			// if array isn't reindexed, since it may not start at 0 it gets treated as empty
			$new_output = array_values($new_output);
		}

		return $new_output;

	}

}
