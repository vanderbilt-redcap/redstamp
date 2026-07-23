<?php

namespace Vanderbilt\REDSTAMP\ExternalModule;

$project_id = $_GET["pid"];

$module->initializeJavascriptModuleObject();

if (is_numeric($project_id)) {
	// $module = new FDD_INTERFACE($project_id);

	// $module->loadREDCapJS();
	// $module->loadBootstrap();

	$module->loadTwigExtensions();
	$module->addJS('js/main.js');


	echo $module->loadTestTwig();


	// $module->addCSS('node_modules/bootstrap-table/dist/bootstrap-table.min.css');
	// $module->addJS('node_modules/bootstrap-table/dist/bootstrap-table.min.js');

	// $module->addCSS('node_modules/bootstrap-table/dist/extensions/sticky-header/bootstrap-table-sticky-header.min.css');
	// $module->addJS('node_modules/bootstrap-table/dist/extensions/sticky-header/bootstrap-table-sticky-header.min.js');

	// $module->addCSS('node_modules/bootstrap-table/dist/extensions/filter-control/bootstrap-table-filter-control.min.css');
	// $module->addJS('node_modules/bootstrap-table/dist/extensions/filter-control/bootstrap-table-filter-control.min.js');


	// bootstrap-table + extensions
	$module->addJS('js/bootstrap_table_config.js');

	$module->addJS('js/sdtm_to_redcap.js');

	// $module->getModal("calctext_modal.html");

	// $module->addJS('js/calctext.js');
}
