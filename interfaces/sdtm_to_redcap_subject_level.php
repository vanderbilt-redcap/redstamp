<?php

namespace Vanderbilt\REDSTAMP\ExternalModule;

$module->loaderModal();
?>

<script>
	$("#loader_modal").dialog({
			modal: true,
			autoOpen: true,
			buttons: {}
	});
 </script>

<?php

$project_id = $_GET["pid"];
// $domain_filter = $_GET["domain_filter"] ?? "AG";

$module->initializeJavascriptModuleObject();

if (is_numeric($project_id)) {

	include(APP_PATH_DOCROOT . "ProjectSetup/tabs.php");
	$module->loadTwigExtensions();
	$module->addJS('js/main.js');
	$module->addCSS('css/main.css');

	$active_domains = $module->getProjectSetting("active_subject_level_domains");
	$sdtm_domains = $module->getSDTMDomainJson($active_domains);

	// FIXME: prevent users from even clicking the link to this page
	$err_string = "ERROR: define domains first";
	$domain_filter = $_GET["domain_filter"] ?? ($active_domains[0] ?? $err_string);
	if ($domain_filter === $err_string) {
		// FIXME: on local dev instance, xdebug intercepts exceptions with a misleading XML parsing error
		// throw new \Exception($err_string);
		echo $err_string;
		return;

	}
	$sdtm_fields = $module->getSDTMFields($domain_filter);
	// $sdtm_domain = array_filter($sdtm_domains, fn($a) => $a['name'] === $domain_filter)[0];
	// HACK: need to reindex array_filter
	$sdtm_domain = [...array_filter($sdtm_domains, fn ($a) => $a['name'] === $domain_filter)][0];

	// $sdtm_fields = $sdtm_domain['datasetVariables'];


	// HACK: bypass EM loadSdtmTwig
	$html =  $module->getTwig()->render('sdtm_to_redcap_subject_level.html.twig', [
		'project_id' => $module->getProjectId(),
		'sdtm_domain' => $sdtm_domain,
		'sdtm_fields' => $sdtm_fields,
		'sdtm_domains' => $sdtm_domains,
		'sdtm_ctr' => $module->getSDTMCTR(),
		'get' => $_GET
	]);
	echo $html;

	$module->tt_addToJavascriptModuleObject(
		"sdtm_ctr",
		$module->getSDTMCTR()
	);

	$module->tt_addToJavascriptModuleObject(
		"sdtm_fields",
		$module->getSDTMFields()
	);

	$module->tt_addToJavascriptModuleObject(
		"domain_filter",
		$domain_filter
	);

	$module->addJS('js/sdtm_to_redcap.js');

	$module->getModal("modal_info.html");

	// $profile = $GLOBALS['profile'];
	// $dumper = new \Twig\Profiler\Dumper\TextDumper();
	// $output_file = $module->getModulePath() . "twig-profile.prof";
	// file_put_contents($output_file, $dumper->dump($profile));

	echo "<pre>";
	echo("Page generated in " . round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4) . " seconds");
	echo "</pre>";

}

?>


<script>
	$("#loader_modal").dialog("destroy").remove();
</script>
