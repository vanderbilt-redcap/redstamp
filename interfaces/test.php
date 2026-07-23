<?php

namespace Vanderbilt\REDSTAMP\ExternalModule;

// $s = $module->getSDTMIGs();
// $s = $module->getAvailableSDTMCTs();
// var_dump($s);

// $result = $module->applyMappingToRecord(12217);
$result = $module->applyMappingToRecord(1);

// var_dump($result);

echo "result for record 1:";
foreach ($result as $r) {
	if ($r['domain'] !== "DM") {
		continue;
	}
	var_dump($r);
}

// $json_data = $module->getSDTMFieldsJson("TA") ?? 'no';


// $domain_filter = "EC";
// $mappings =  $module->getMappings($domain_filter);

// $json_data = 'foo';
// var_dump($mappings);

// $result = $module->applyMappingToRecord(1);

// var_dump($result);
