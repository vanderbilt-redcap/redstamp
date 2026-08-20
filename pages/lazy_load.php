<?php

namespace Vanderbilt\REDSTAMP\ExternalModule;

$resource = $_GET["resource"];

function rename_keys($input, $replacement_map) {
	foreach ($replacement_map as $old => $new) {
		foreach ($input as &$array) {
			if (isset($array[$old])) {
				$array[$new] = $array[$old];
				unset($array[$old]);
			}
		}
	}
	return $input;
}



switch ($resource) {
	case "getAvailableSDTMCTs":
		// TODO: support fetching human redable if already defined
		$response = $module->getAvailableSDTMCTs();
		break;
	case "getActiveSDTMCT":
		$response = $module->getSDTMCTRJson();
		break;
	default:
		$response = null;
}

$replacement_map = [
	"href" => "id",
	"title" => "text"
];

// $ret = rename_keys($response, $replacement_map);


header("Content-Type: application/json");
echo json_encode($response);
