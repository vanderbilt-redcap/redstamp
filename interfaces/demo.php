<?php

namespace Vanderbilt\REDSTAMP\ExternalModule;

use LogicTester;
use LogicParser;
use REDCap;
use Project;

// $module->getModal();
// $module->addJS("js/modal.js");

$project_id = $_GET["pid"];
$P = new Project($project_id);


$logic = <<<_
	calctext(concat([f1], 's2'))
	_;

$logic = <<<_
	calctext(concat([aa202], 's2'))
	_;
// missing project field aa202

$is_valid = LogicTester::isValid($logic);

$get_data_arr = [
	'project_id' => $project_id
	// 'return_format' => "json-array"
];
$data = REDCap::getData($get_data_arr);
// echo "data";
$data = $data[12217];
// var_dump($data);

$applied = LogicTester::apply($logic, $record_data = $data, $Proj = $P, $returnValue = true);

// display

echo "Mapping Logic:";
echo "<pre>";
echo $logic;
echo "</pre>";


echo "Mapping Logic valid?";
echo "<pre>";
echo $is_valid;
echo "</pre>";


echo "Mapping Logic result:";
echo "<pre>";
echo $applied;
echo "</pre>";
