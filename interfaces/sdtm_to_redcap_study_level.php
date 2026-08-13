<?php

namespace Vanderbilt\REDSTAMP\ExternalModule;

// HACK: prevent interactions until page is loaded
$module->loaderModal();
?>


<!-- FIXME: no js in php files -->
<script>
	$("#loader_modal").dialog({
			modal: true,
			autoOpen: true,
			buttons: {}
	});
 </script>

<?php

$project_id = $_GET["pid"];
$module->initializeJavascriptModuleObject();

if (is_numeric($project_id)) {
	// FIXME: fair bit of DRY violation here
	// TODO: should just extend a class and sdtm_to_redcap_study_level.php can just define vars
	// only difference so far is the project setting key and sdt_to_redcap_{level}_level.html.twig page
	$module->loadTwigExtensions();
	$module->addJS('js/main.js');
	$module->addCSS('css/main.css');

	$sdtm_ctr = $module->getSDTMCTR();
	// $sdtm_ctr = null; // HACK: profiling an empty file

	$active_domains = $module->getProjectSetting("active_study_level_domains");
	$sdtm_domains = $module->getSDTMDomainJson($active_domains);

	$domain_filter = $_GET["domain_filter"] ?? ($active_domains[0] ?? "ERROR: define domains first");
	$sdtm_domain = [...array_filter($sdtm_domains, fn ($a) => $a['name'] === $domain_filter)][0];

	if ($sdtm_domain['datasetVariables']) {
		// TODO: if this isn't set, they shouldn't have gotten here to begin with
	}


	// HACK: bypass EM loadSdtmTwig
	$html =  $module->getTwig()->render('sdtm_to_redcap_study_level.html.twig', [
		'project_id' => $module->getProjectId(),
		'sdtm_domain' => $sdtm_domain,
		'sdtm_domains' => $sdtm_domains,
		// 'sdtm_ctr' => $sdtm_ctr, // not needed
		'get' => $_GET
	]);
	echo $html;

	// TODO: make a wrapper to pass an associative array that calls this on k => v
	$module->tt_addToJavascriptModuleObject(
		"sdtm_ctr",
		$sdtm_ctr
	);

	$module->tt_addToJavascriptModuleObject(
		"sdtm_fields",
		$module->getSDTMFields($domain_filter)
	);

	$module->tt_addToJavascriptModuleObject(
		"domain_filter",
		$domain_filter
	);

	$module->addJS('js/sdtm_to_redcap.js');

	$module->getModal("modal_info.html");

	echo "<pre>";
	echo("Page generated in " . round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4) . " seconds");
	echo "</pre>";



	// $profile = $GLOBALS['profile'];
	// $dumper = new \Twig\Profiler\Dumper\TextDumper();
	// $output_file = $module->getModulePath() . "twig-profile.prof";
	// file_put_contents($output_file, $dumper->dump($profile));
}

?>

<script>
	$("#loader_modal").dialog("destroy").remove();
</script>
