<?php

namespace Vanderbilt\REDSTAMP\ExternalModule;

$project_id = $_GET["pid"];
$domain_filter = $_GET["domain_filter"] ?? "AG";

$module->initializeJavascriptModuleObject();

if (is_numeric($project_id)) {
	$module->loadTwigExtensions();
	$module->addJS('js/main.js');
	$module->addCSS('css/main.css');


	echo $module->loadSdtmTwig($domain_filter);

	$module->tt_addToJavascriptModuleObject(
		"sdtm_ctr",
		$module->getSDTMCTR()
	);

	$module->tt_addToJavascriptModuleObject(
		"sdtm_fields",
		$module->getSDTMFields()
	);

	$module->addJS('js/sdtm_to_redcap.js');

	$module->getModal("modal_info.html");

	// $profile = $GLOBALS['profile'];
	// $dumper = new \Twig\Profiler\Dumper\TextDumper();
	// $output_file = $module->getModulePath() . "twig-profile.prof";
	// file_put_contents($output_file, $dumper->dump($profile));
}
