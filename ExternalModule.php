<?php

namespace Vanderbilt\REDSTAMP\ExternalModule;

// require_once __DIR__ . '/src/autoload.php';
// require_once(dirname(__FILE__) . '/vendor/autoload.php');
require_once(dirname(__FILE__) . '/src/autoload.php');

use ExternalModules\AbstractExternalModule;
// use ExternalModules\ExternalModule;
// use REDCap;
use Twig\TwigFunction;
use GuzzleHttp\Client;

class ExternalModule extends AbstractExternalModule
{
	private const AJAX_ALTERABLE_PROJECT_SETTINGS_WHITELIST = [
		"active_sdtmig",
		"active_sdtmct",
		"active_study_level_domains",
		"active_subject_level_domains"
	];

	public function redcap_every_page_top($project_id) {
		$this->initializeJavascriptModuleObject();
		$this->tt_addToJavascriptModuleObject(
			"sdtm_config_page",
			$this->getUrl("interfaces/sdtm_config_page.php")
		);
		$this->addJS("js/inject_config_tab.js");
	}


	public function loaderModal() {
		$loader_path = APP_PATH_IMAGES . 'loader.gif';

		$loader_html = <<<_
			<div id="loader_modal">
					<p>Kyle is still trying to optimize load times so you have to wait  </p>
					<img id="loader_bar" src="{$loader_path}"/>
			</div>
		_;

		echo $loader_html;
	}


	public function setUpJSMO(): void {
		$this->initializeJavascriptModuleObject();

		// $param1 = [
		// 	"foo" => "bar"
		// ];

		// $this->tt_addToJavascriptModuleObject("param1", $param1);
	}


	public function addJS(string $path): void {
		// $this->initializeJavascriptModuleObject();
		echo "<script src='" . $this->getUrl($path) . "'></script>";
	}


	public function addCSS(string $path): void {
		echo "<link rel='stylesheet' href='" . $this->getUrl($path) . "'>";
	}


	public function loadTwigExtensions(): void {
		$this->initializeTwig();

		// Source - https://stackoverflow.com/a/74089647
		// Posted by RcoderNY
		// Retrieved 2026-01-30, License - CC BY-SA 4.0
		// $profile = new \Twig\Profiler\Profile();
		// $this->getTwig()->addExtension(new \Twig\Extension\ProfilerExtension($profile));
		// $GLOBALS['profile'] = $profile;

		$this->getTwig()->addFunction(new TwigFunction('loadJSBS', function () {
			return $this->framework->loadBootstrap() . $this->framework->loadREDCapJS();
		}));

		$this->getTwig()->addFunction(new TwigFunction('getCSRFToken', function () {
			return $this->getCSRFToken();
		}));

		$this->getTwig()->addFunction(new TwigFunction('addJS', function ($path) {
			return $this->addJS($path);
		}));

		$this->getTwig()->addFunction(new TwigFunction('addCSS', function ($path) {
			return $this->addCSS($path);
		}));

		$this->getTwig()->addFunction(new TwigFunction('getSDTMFields', function (array $domain_filter = null) {
			return $this->getSDTMFields($domain_filter);
		}));

		$this->getTwig()->addFunction(new TwigFunction('getFields', function (array $filter_fields = []) {
			return $this->getFields($filter_fields);
		}));

		$this->getTwig()->addFunction(new TwigFunction('getMappings', function (string $domain_filter = null) {
			return $this->getMappings($domain_filter);
		}));
	}


	public function loadIndexTwig() {
		return $this->getTwig()->render('index.html.twig', [
			'project_id' => $this->getProjectId()
		]);
	}

	public function loadRedcapTwig() {
		return $this->getTwig()->render('redcap_to_sdtm.html.twig', [
			'project_id' => $this->getProjectId()
		]);
	}

	public function loadSdtmTwig(string $domain_filter = null) {
		$sdtm_fields = $this->getSDTMFields($domain_filter);

		$html =  $this->getTwig()->render('sdtm_to_redcap.html.twig', [
			'project_id' => $this->getProjectId(),
			'sdtm' => $sdtm_fields,
			'sdtm_domains' => $this->getSDTMDomains(),
			'sdtm_ctr' => $this->getSDTMCTR(),
			'get' => $_GET
		]);

		return $html;

	}

	public function loadTestTwig() {
		return $this->getTwig()->render('test_page.html.twig', [
			'project_id' => $this->getProjectId(),
			'sdtm' => $this->getSDTMFields(),
			'sdtm_ctr' => $this->getSDTMCTR()
		]);
	}


	public function loadConfigPageTwig() {
		$igs = $this->getSDTMIGs();

		$html =  $this->getTwig()->render('sdtm_config_page.html.twig', [
			'project_id' => $this->getProjectId(),
			// FIXME: this is also exposed in tt_addToJavascriptModuleObject as sdtmigs
			'sdtmigs' => $igs,
			'get' => $_GET
		]);

		// $this->tt_addToJavascriptModuleObject(
		// 	"sdtm_ctr",
		// 	$this->getSDTMCTR(),
		// 	"sdtmigs",
		// 	$igs
		// );

		return $html;
	}


	public function getFields(array $filterFields = []): array {
		$returnArray = [];

		$thisProject = new \Project($this->getProjectId());
		// $userForm = $this->getProjectSetting('user-form');
		$metadata = $thisProject->metadata;
		foreach ($metadata as $fieldName => $fieldInfo) {
			if ($fieldName == $thisProject->table_pk || (str_ends_with($fieldName, "_complete")) || (count($filterFields) !== 0 && !in_array($fieldName, $filterFields))) {
				continue;
			}

			$returnArray[$fieldInfo['field_order']] = [];
			$this_field = $fieldInfo;

			switch ($fieldInfo['element_type']) {
				case 'descriptive':
					unset($returnArray[$fieldInfo['field_order']]);
					break;
				case 'select':
					// $this_field['choices'] = $this->processFieldEnum($fieldInfo);
					$this_field['choices'] = parseEnum($fieldInfo['element_enum']);

					// 'choices' => $this->processFieldEnum($fieldInfo),
					// no break
				default:
					// $returnArray[$fieldInfo['field_order']] = [
					// 	'name' => $fieldName,
					// 	'label' => $fieldInfo['element_label'],
					// 	'type' => $fieldInfo['element_type'],
					// 	// 'choices' => $this->processFieldEnum($fieldInfo),
					// 	'required' => ($fieldInfo['field_req'] ? 'required' : '')
					// ];

					$returnArray[$fieldInfo['field_order']] = $this_field;
					break;
			}
		}

		return $returnArray;
	}

	public function getSDTMFields(string $domain_filter = null): array {
		// HACK: load csv direct from EM
		// TODO: expose as file upload setting
		$target_file = $this->getUrl("SDTMIG_v3.4.csv");
		// $target_file = "/var/www/html/modules/redstamp_v0.0.0/" . "SDTMIG_v3.4.csv";
		// TODO: allow user to provide
		$target_file = $this->getModulePath() . "SDTMIG_v3.4.csv";
		$csv_file = fopen($target_file, "r");

		$csv_data = [];
		$headers = fgetcsv($csv_file);

		while (($row = fgetcsv($csv_file)) !== false) {
			$labeled_row = array_combine($headers, $row);
			if (!is_null($domain_filter) && ($domain_filter != $labeled_row["Dataset Name"])) {
				continue;
			}
			// replace newlines with ... newlines
			// str_replace('\n', "\n", &$labeled_row["CDISC Notes"]);
			$labeled_row["CDISC Notes"] = str_replace('\n', "\n", $labeled_row["CDISC Notes"]);
			// NOTE: nl2br result in literal <br /> displayed to user
			// $labeled_row["CDISC Notes"] = nl2br($labeled_row["CDISC Notes"]);

			$csv_data[] = $labeled_row;
		}

		fclose($csv_file);

		$display_order = ["Req", "Exp", "Perm"];

		// adapted from
		// https://www.php.net/manual/en/function.usort.php
		usort($csv_data, function ($a, $b) use ($display_order) {
			$pos_a = array_search($a['Core'], $display_order);
			$pos_b = array_search($b['Core'], $display_order);


			// retain order
			if ($pos_a == $pos_b) {
				return 0;
			}
			return ($pos_a < $pos_b) ? -1 : 1;
		});

		return $csv_data;
	}


	public function getSDTMDomainJson(array $domain_filter = null): array {
		// TODO: consider just returning an entire SDTM dataset instead of the fields
		// TODO: check for cached JSON and request from API otherwise, cached ds should be stored at system level in edocs
		$target_file = $this->getModulePath() . "sdtmig_v3.4.json";
		$file_data = json_decode(file_get_contents($target_file), true);

		// $file_data['classes']['datasets']
		// find dataset where $ds['name'] == $domain_filter
		// return datasetVariables for processing
		$return_datasets = [];
		foreach ($file_data['classes'] as $class) {
			$domain = $class['datasets'];
			foreach ($domain as $ds) {
				// TODO: borked
				if (!is_null($domain_filter) && (!in_array($ds['name'], $domain_filter))
				) {
					continue;
				}
				$return_datasets[] = $ds;
			}
		}
		return $return_datasets;

		$display_order = ["Req", "Exp", "Perm"];

		// TODO: adapt display order for json data
		// adapted from
		// https://www.php.net/manual/en/function.usort.php
		usort($csv_data, function ($a, $b) use ($display_order) {
			$pos_a = array_search($a['Core'], $display_order);
			$pos_b = array_search($b['Core'], $display_order);


			// retain order
			if ($pos_a == $pos_b) {
				return 0;
			}
			return ($pos_a < $pos_b) ? -1 : 1;
		});

		return $csv_data;
	}


	public function getSDTMDomains(): array {
		// HACK: load csv direct from EM
		// Can't simply array_unique(array_column(sdtm, "Dataset Name"))
		// because filtering is applied durring array creation lol
		// TODO: expose as file upload setting
		// $target_file = "/var/www/html/modules/redstamp_v0.0.0/" . "SDTMIG_v3.4.csv";
		// TODO: allow user to provide
		$target_file = $this->getModulePath() . "SDTMIG_v3.4.csv";
		$csv_file = fopen($target_file, "r");

		$headers = fgetcsv($csv_file);
		$domains = [];

		while (($row = fgetcsv($csv_file)) !== false) {
			$labeled_row = array_combine($headers, $row);
			$domains[] = $labeled_row["Dataset Name"];
		}

		fclose($csv_file);

		return array_unique($domains);
	}


	public function getSDTMCTR(): array {
		// HACK: load csv direct from EM
		// TODO: fetch from JSON
		$target_file = $this->getModulePath() . "SDTM_CT_2025-09-26.csv";
		// $target_file = "/var/www/html/modules/redstamp_v0.0.0/" . "SDTM_CT_2025-09-26.csv";
		$csv_file = fopen($target_file, "r");

		$csv_data = [];
		$headers = fgetcsv($csv_file);

		while (($row = fgetcsv($csv_file)) !== false) {
			$csv_data[] = array_combine($headers, $row);
		}

		fclose($csv_file);

		return $csv_data;
	}


	public function getSDTMCTRJson(): array {
		// HACK: load csv direct from EM
		// TODO: expose as file upload setting
		$target_file = $this->getModulePath() . "SDTM_CT_2025-09-26.csv";
		// $target_file = "/var/www/html/modules/redstamp_v0.0.0/" . "SDTM_CT_2025-09-26.csv";
		$csv_file = fopen($target_file, "r");

		$csv_data = [];
		$headers = fgetcsv($csv_file);

		while (($row = fgetcsv($csv_file)) !== false) {
			$csv_data[] = array_combine($headers, $row);
		}

		fclose($csv_file);

		return $csv_data;
	}


	public function getAvailableSDTMCTs(): array {
		// $api_path = "mdr/products/DataTabulation?expand=true";
		$api_path = "mdr/ct/packages";
		$response = $this->makeRequest($api_path);

		$stds = $response["_links"]["packages"];

		$search = "SDTM Controlled Terminology";

		$stds = array_filter($stds, function ($item) use ($search) {
			return isset($item['title']) && str_starts_with($item['title'], $search);
		});

		usort($stds, fn ($a, $b) => $b['title'] <=> $a['title']);

		return $stds;

		// HACK: load csv direct from EM
		// TODO: expose as file upload setting
		$target_file = $this->getModulePath() . "SDTM_CT_2025-09-26.csv";
		// $target_file = "/var/www/html/modules/redstamp_v0.0.0/" . "SDTM_CT_2025-09-26.csv";
		$csv_file = fopen($target_file, "r");

		$csv_data = [];
		$headers = fgetcsv($csv_file);

		while (($row = fgetcsv($csv_file)) !== false) {
			$csv_data[] = array_combine($headers, $row);
		}

		fclose($csv_file);

		return $csv_data;
	}



	public function getSDTMMappingsFromFields(): array {
		$fields = $this->getFields();


		// $name_col = array_column($fields, 'field_name');
		// $misc_col = array_column($fields, 'misc');

		$df = [];

		$idx = 0;
		foreach ($fields as &$row) {
			if (empty($row['misc'])) {
				continue;
			}
			// $this_s = new SDTMMapping($row);
			$df[] = new SDTMMapping($row);
			$idx++;
		}

		return $df;
	}


	public function getModal($custom = null) {
		if ($custom === null) {
			$custom = "modal_calc.html";
		}
		include($custom);
	}

	// public function redcap_module_ajax($action, $payload, $project_id, $record, $instrument, $event_id, $repeat_instance, $survey_hash, $response_id, $survey_queue_hash, $page, $page_full, $user_id, $group_id) {
	public function redcap_module_ajax($action, $payload) {

		$response = null;

		switch ($action) {
			case "store_calc_mapping":
				$response = $payload;
				$sdtm = new SDTMMapping($payload);
				$this->saveSDTM($sdtm);
				$response = $sdtm->uid;
				break;
			case "check_logic":
				return \LogicTester::isValid($payload['calc_text']);
				break;
			case "save_project_setting":
				if (!in_array($payload["setting"], self::AJAX_ALTERABLE_PROJECT_SETTINGS_WHITELIST)) {
					$response = "disallowed setting";
					// TODO: log?
					break;
				}
				$stored_value = $payload["value"];
				// if (is_array($stored_value)) {
				// arrays result in duplication of fields
				// $stored_value = json_encode($stored_value);
				// }
				// NOTE: backend stores this as a single field, but manager config shows indepent json fields
				$this->setProjectSetting($payload["setting"], $payload["value"]);
				break;
			case "get_domains":
				$response = $this->getDomains();
				break;
			case "get_sdtm_ctr":
				$domain_filter = $payload["domain_filter"];
				$response = $this->getSDTMCTR($domain_filter);
				break;
			case "lazy_load":
				$payload['resource'];
				$response = json_encode($this->getAvailableSDTMCTs());
				break;
			default:
				$response = "no room";
		}

		return $response;
	}

	public function saveSDTM(SDTMMapping $sdtm) {
		$db_file_name = PROJECT_ID . "_sdtm_mappings.db";
		$db_full_path = $this->getModulePath() . $db_file_name;

		$sdti = new SDTMInterface($db_full_path);
		$result = $sdti->storeMapping($sdtm);

		return $result;
	}

	public function getMappings(string $domain_filter = null) {
		$db_file_name = PROJECT_ID . "_sdtm_mappings.db";
		$db_full_path = $this->getModulePath() . $db_file_name;

		$sdti = new SDTMInterface($db_full_path);

		$mappings = $sdti->getMappings($domain_filter);

		// HACK: add instance from uid
		// future, storing this in table until table schema done
		// array_walk(
		// 	$mappings,
		// 	function (&$input) {
		// 		$variable = $input["uid"];
		// 		$instance = (int) explode("__", $variable)[1] ?? 0;
		// 		$input["instance"] = $instance;
		// 	}
		// );

		return $mappings;
	}

	public function redcap_module_link_check_display($project_id, $link) {
		$public_pages = ["configPage", "sdtmToRedcapStudyLevel", "sdtmToRedcapSubjectLevel"];
		$cur_user = $this->getUser()->getUsername();
		// HACK: keep my beta testers form seeing my hacky test pages replace with actual e2e tests
		$is_admin = in_array($cur_user, ["admin", "kyle.chesney@vumc.org"]);
		// HACK: hide pages that are angry
		//$public_pages = ["twigpage1"];
		if (
			!in_array($link['key'], $public_pages) &&
			!$is_admin) {
			return null;
		}
		return $link;
	}


	/////////////////////////////////////////////////////////////////////////////
	//                              API Interface                              //
	/////////////////////////////////////////////////////////////////////////////


	public function makeRequest(string $api_path) {
		$api_key = $this->getSystemSetting("cdisc_api_key");

		// Create a client instance
		$client = new Client([
			// Base URI is used with relative requests
			'base_uri' => 'https://api.library.cdisc.org/api/',
			'headers' => [
				'Authorization' => "Subscription key",
				'Cache-Control' => "no-cache",
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
				'api-key' => $api_key
			]
		]);

		// Send a GET request to a specific URI
		try {
			// $response = $client->request('GET', 'mdr/products/DataTabulation');
			$response = $client->get($api_path);
			// or use the shortcut:
			// $response = $client->get('user/123');

			// Get the response body as a string
			$body = $response->getBody()->getContents();

			// Decode JSON response into a PHP array or object
			$data = json_decode($body, true);

			// Print the data (for demonstration)
			return $data;
		} catch (GuzzleHttp\Exception\RequestException $e) {
			// Handle exceptions (e.g., 4xx or 5xx errors)
			echo 'Error: ' . $e->getMessage();
		}
	}

	public function getSDTMIGs() {
		// Always makes an API call because this must be up to date
		$api_path = "mdr/products/DataTabulation?expand=true";
		$stds = $this->makeRequest($api_path);

		// filter for registrationStatus = Final
		$igs = array_filter(
			$stds["_links"]["sdtmig"],
			fn ($ig) => (
				$ig['registrationStatus'] === "Final" &&
								 $ig['type'] === "Implementation Guide" &&
								 str_starts_with($ig['name'], "SDTMIG ") // exclude -MD and -AP
			)
		);

		// descending release date order
		usort(
			$igs,
			function ($a, $b) {
				$date_a = new \DateTime($a['effectiveDate']);
				$date_b = new \DateTime($b['effectiveDate']);

				// edge case, shouldn't happen
				if ($date_a == $date_b) {
					return 0;
				}

				return $date_a > $date_b ? -1 : 1;
			}
		);

		// order by effectiveDate

		return $igs;
	}

	public function getInfo() {
		$api_path = "mdr/sdtmig/3-4?expand=true";
		$stds = $this->makeRequest($api_path);

		// TODO:
		// filter for registrationStatus = Final
		// order by effectiveDate

		return $stds;
	}


	public function getDomains($level = "study") {
		// HACK: why no use stored?

		// HACK: replace entirely with API pull
		$target_file = $this->getModulePath() . "sdtmig_v3.4.json";
		$file_data = json_decode(file_get_contents($target_file), true);
		return $file_data;

		// Always calls API because this is used in config, where up to date IGs must be sourced
		$active_sdtmig = $this->getProjectSetting("active_sdtmig");
		$api_path = $active_sdtmig . "?expand=true";
		// TODO: above no work :(
		$api_path = "mdr/sdtmig/3-4?expand=true";


		$stds = $this->makeRequest($api_path);

		// TODO:
		// filter for registrationStatus = Final
		// order by effectiveDate

		return $stds;
	}

	/////////////////////////////////////////////////////////////////////////////
	//                                  Other                                  //
	/////////////////////////////////////////////////////////////////////////////


	// TODO: store and fetch from a reserved file repository
	// see crispi clone EM for this
	private function writeToEdocs() {
		// TODO: saveFile in EMFW needs to exist again

		$stds = $this->getInfo();

		// TODO: tmpfile
		$file_path = $this->getModulePath() . 'sdtmig_v3.4.json';

		$file_handle = fopen($file_path, 'w');

		if ($file_handle) {
			fwrite($file_handle, json_encode($stds));

			fclose($file_handle);
			echo "Data written using fopen/fwrite/fclose.";
		} else {
			echo "Could not open file for writing.";
		}
	}

	public function applyMappingToRecord($record_id) {
		$db_file_name = PROJECT_ID . "_sdtm_mappings.db";
		$db_full_path = $this->getModulePath() . $db_file_name;

		$sdtmi = new SDTMInterface($db_full_path);

		$mappings = $sdtmi->getMappings();

		$result = [];

		foreach ($mappings as $m) {
			// TODO: process conditional logic for an instance prior to attempting any other calcs
			$is_valid_logic = \LogicTester::isValid($m['calc_text']);
			if (!$is_valid_logic) {
				$result[$m['uid']] = ["invalid logic"];
				continue;
			}
			// var_dump($is_valid_logic);


			$r = \LogicTester::evaluateLogicSingleRecord(
				raw_logic: $m['calc_text'],
				// record: 12217,
				record: $record_id,
				// repeat_instance: 1,
				returnValue: true
			);



			$result[$m['uid']] = [
				...$m,
				"result" => $r
			];
		}

		return $result;

		$record_data = \REDCap::getData([
			// 'return_format' => "json-array",
			"records" => [$record_id]
		]);

		// order by domain and instance
		// if conditional logic blocks processing, just skip it

		$LP = new \LogicParser();

		$logic_basic = "[record_id:value] = 12217";
		$lp_result = $LP->parse(
			str: "[record_id] = 12217",
			isCalcField: true
		);

		// FIXME: record ID when first form is repeating may get missed
		// NOTE: logic string must be:
		// [$field-on-repeat][$idx]
		// hdr_date is LBW0150 Hematologies Date of Patient Visit
		$logic_basic = "[hdr_date][first-instance]";

		$logic_basic = "[record_id] = 12217";
		$logic_basic = "[record_id] = 1";
		$logic_basic = "[prim_scryn]";
		// $logic_basic = "[hdr_date][2]";
		// NOTE: look at the source code of buildLogicArgs, repeat detection is unreliable
		// need to force instanceVar to be set in the logic string
		// TODO: incorporate record_data param to try to process arms separately?
		$r = \LogicTester::evaluateLogicSingleRecord(
			raw_logic: $logic_basic,
			// record: 12217,
			record: 1,
			// repeat_instance: 1,
			returnValue: true
		);
		// NOTE: 2nd arg is event_id if providing record_data?
		// $r = \LogicTester::evaluateLogicSingleRecord($logic_basic, 12217, $record_data);

		// $r = \LogicTester::evaluateCondition($logic_basic, $record_data);

		return $r;

		return $mappings;
	}

	public function validateLogic($calc_text) {
		return \LogicTester::isValid($calc_text);
	}

	public function storeEdoc() {
		// TODO: json returned from API should be stored as an edoc

	}
}
