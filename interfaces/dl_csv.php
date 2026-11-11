<?php

namespace Vanderbilt\REDSTAMP\ExternalModule;

// HACK


$test_record_id = 1;
$test_domain = "AE";

$active_domains = $module->getProjectSetting("active_subject_level_domains");
$sdtm_domains = $module->getSDTMDomainJson($active_domains);

$P = new \Project($module->getProjectId());
$record_ids = \REDCap::getData(
	[
		"fields" => [$P->table_pk],
		"returnEmptyEvents" => false
	]
);
$record_ids = array_keys($record_ids);

function convertDSToCSV(&$data, $record_id = 1, $domain = "AE") {
	global $module;
	$results = [];
	$result = $module->applyMappingToRecord($record_id, $domain);
	$fields = $module->getSDTMFields($domain);
	// FIXME: migrate this to init DSP objects in a map so it doesn't need to be redone
	$DSP = new DataSetPrep($result, $fields, true);
	$df = $DSP->prepareOutput($domain);

	if (empty($df)) return;

	$fn = $domain . ".csv";
	$data[$fn] ??= [];
	$data[$fn][$record_id] = $df;
}

$data = [];


foreach ($active_domains as $this_domain) {
	foreach ($record_ids as $record_id) {
		convertDSToCSV($data, $record_id, $this_domain);
	}
}

if (empty($data)) {
    die("No data to export.");
}

// single CSV
// FIXME: this is still fit to pre record-batching
$single_csv = false;
if ($single_csv) {
	$filename =  date('Ymd_His') . "REDSTAMP_dataset_export_.csv";

	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename="' . $filename . '"');
	header('Pragma: no-cache');
	header('Expires: 0');

	$output = fopen('php://output', 'w');

	$headers = array_keys($data[0]);
	fputcsv($output, $headers);

	foreach ($data as $row) {
		fputcsv($output, $row);
	}

	fclose($output);
}

$zip_file_name =  date('Ymd') . "_REDSTAMP_dataset_export.zip";

$zip = new \ZipArchive();
if ($zip->open($zip_file_name, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
	foreach ($data as $file_name => $records) {
		$csv_stream = fopen('php://temp', 'r+');

		$foo = $records[array_key_first($records)];
		$headers = array_keys($foo[0]);
		fputcsv($csv_stream, $headers);

		foreach ($records as $record_id => $rows) {
			foreach ($rows as $row) {
				fputcsv($csv_stream, $row);
			}
		}
		// NOTE: not sure if rewind and stream_get_contents really necessary here
		rewind($csv_stream);
		$csvContent = stream_get_contents($csv_stream);
		fclose($csv_stream);

		$zip->addFromString($file_name, $csvContent);
	}

	$zip->close();
} else {
    die("Failed to create ZIP archive.");
}

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zip_file_name . '"');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Length: ' . filesize($zip_file_name));

readfile($zip_file_name);
unlink($zip_file_name);

exit();
