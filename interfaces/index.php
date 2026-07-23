<?php

namespace Vanderbilt\REDSTAMP\ExternalModule;

$project_id = $_GET["pid"];

if (is_numeric($project_id)) {
	// $module = new FDD_INTERFACE($project_id);
	$module->loadTwigExtensions();
	$module->addJS('js/main.js');

	echo $module->loadIndexTwig();
}
