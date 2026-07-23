<?php

namespace Vanderbilt\REDSTAMP\ExternalModule;

use ProjectSetup;

$project_id = $_GET["pid"];
$module->initializeJavascriptModuleObject();

if (is_numeric($project_id)) {
	// FIXME: this page is not able to render at all if your API key is invalid
	// since I store data locally, it should simply alert them that their key has gone stale so it doesn't block work
	$module->loadTwigExtensions();

	include(APP_PATH_DOCROOT . "ProjectSetup/tabs.php");
	// TODO: lazy load these on click of launcher
	$sdtmigs = $module->getSDTMIGs();

	$module->addJS("js/config_page.js");

	// FIXME: this is also exposed in twig as sdtmigs
	$module->tt_addToJavascriptModuleObject(
		"sdtmigs",
		$module->getSDTMIGs()
	);

	$module->tt_addToJavascriptModuleObject(
		"project_settings",
		$module->getProjectSettings()
	);

	$module->tt_addToJavascriptModuleObject(
		"appPathImages",
		APP_PATH_IMAGES
	);

	$module->getModal("modal_info.html");


	// echo $module->loadConfigPageTwig();
	$html =  $module->getTwig()->render('sdtm_config_page.html.twig', [
		'project_id' => $module->getProjectId(),
		// FIXME: this is also exposed in tt_addToJavascriptModuleObject as sdtmigs
		'sdtmigs' => $sdtmigs,
		// 'sdtmcts' => $sdtmcts,
		'get' => $_GET
	]);
	echo $html;

	// awful but consistent
	// how badly do they want to mimic REDCap?
	$chklst = [
		[
			'name' => 'sdtmig',
			'status' => '0',
			'header' => 'Select the SDTM Implementation Guide (IG) and Controlled Terminology (CT) versions to use',
			'text' => "You will need to determine which version of SDTM IG and CT you would like to use for this project." .
								"</br>" .
								'<button id="select-sdtmig" class="config-button btn btn-defaultrc btn-xs fs13">Select SDTM IG Version</button>' .
								"</br>" .
								'<button id="select-sdtmct" class="config-button btn btn-defaultrc btn-xs fs13">Select CT Version</button>'
		],
		[
			'name' => 'sdtm_domains',
			'status' => '0',
			'header' => 'Identify your domains',
			// buttons here launch modal similar to additional customizations
			'text' => 'SDTM defines domains that are study-level which describe the details of the study itself as well as subject-level domains which are used for data collected about each participant. You will need to determine which SDTM domains are needed based on the types of data you are collecting.' .
								"</br>" .
								'<button disabled id="id-study-level-domains" class="config-button btn btn-defaultrc btn-xs fs13">Identify study-level domains</button>' .
								"</br>" .
								'<button disabled id="id-subject-level-domains" class="config-button btn btn-defaultrc btn-xs fs13">Identify subject-level domains</button>'
		],
		[
			'name' => 'sdtm_study',
			'status' => '0',
			'header' => 'Enter your study-level information',
			'text' => 'There are SDTM domains which contain information that is not related to a specific subject such as trial design. You will need to enter the relevant information for this domains.' .
								"</br>" .
								'<div class="chklistbtn">Go to <button disabled id="add-edit-non-subject-level-info" class="config-button btn btn-defaultrc btn-xs fs13">Add or edit non-subject level information</button></div>'
		],
		[
			'name' => 'sdtm_subject',
			'status' => '0',
			'header' => 'Define your subject-level domain mappings',
			'text' => 'You will need to map your data collection instrument variables to domain variables, specify literal values, map values to controlled terminology, and set up calculations.' .
								"</br>" .
								'<div class="chklistbtn">Go to <button disabled id="add-edit-variable-mappings" class="config-button btn btn-defaultrc btn-xs fs13">Add or edit variable mappings</button></div>'
		],
		[
			'name' => 'sdtm_supp',
			// 'status' => '0',
			'header' => 'Define supplemental qualifiers',
			'text' => 'You may need to create supplemental qualifiers to hold data that doesn’t fit into the existing SDTM variables for a domain.' .
								"</br>" .
								'<button disabled id="add-edit-supp-quals" class="config-button btn btn-defaultrc btn-xs fs13">Add or edit supplemental qualifiers</button>'
		],
		[
			'name' => 'sdtm_annot',
			// 'status' => '0',
			'header' => 'Complete your annotated forms',
			'text' => 'Your data collection instruments need to be annotated with target SDTM domains and variables to provide traceability between the data as collected and the SDTM datasets as well as identifying data points that are not included in the SDTM' .
								"</br>" .
								'<button disabled id="add-edit-annotations" class="config-button btn btn-defaultrc btn-xs fs13">Add or edit annotations</button>'
		],
		[
			'name' => 'sdtm_define_xml',
			// 'status' => '0',
			'header' => 'Create Define.xml',
			'text' => 'This file contains metadata at the study, dataset, and variable levels, including controlled terminology and validation rules for datasets using the SDTM standard as well as pointers to annotated forms. It is required by regulatory studies in each electronic submission to inform the regulatory authorities which datasets, variables, controlled terms, and other specified metadata were used. REDSTAMP will generate most of the content, but there are some elements you will need to provide' .
								"</br>" .
								"</br>" .

								'If you used any external code lists, for example MedDRA, you will need to provide the corresponding details.' .
								"</br>" .
								'<button disabled id="enter-external-cl-info" class="config-button btn btn-defaultrc btn-xs fs13">Enter external code list information</button>' .
								"</br>" .

								'If you extended any CDISC controlled terminology with additional items, these need to be defined.' .
								'<button disabled id="define-extended-ctr-items" class="config-button btn btn-defaultrc btn-xs fs13">Define extended controlled terminology items</button>' .
								"</br>" .

								'You may have comments to explain your approach to how your data is mapped to an SDTM domain or unusual aspects of your study which affect the SDTM data. These should be documented in Define.xml.' .
								"</br>" .
								'<button disabled id="add-edit-comments" class="config-button btn btn-defaultrc btn-xs fs13">Add or edit comments</button>' .
								"</br>"
		],
		[
			'name' => 'sdtm_csdrg',
			// 'status' => '0',
			'header' => 'Create your Clinical Study Data Reviewer\'s Guide (cSDRG)',
			'text' => "The Clinical Study Data Reviewer's Guide (cSDRG) is a key document in pharmaceutical regulatory submissions for understanding trial data. It helps regulatory agencies like the FDA understand the structure, content, and quality of submitted clinical trial data by providing context beyond the data definitions." .
								"</br>" .
								"</br>" .
								'<button disabled id="add-edit-csdrg-details" class="config-button btn btn-defaultrc btn-xs fs13">Add or edit cSDRG details</button>'
		]

	];

	ProjectSetup::renderSetupCheckList($chklst, []);

	// HACK: this is pretty stupid, but iframe config page

	$path_to_thing = "https://www.openstreetmap.org/export/embed.html?bbox=-0.004017949104309083%2C51.47612752641776%2C0.00030577182769775396%2C51.478569861898606&amp;layer=mapnik";
	$path_to_thing = $module->getUrl('interfaces/sdtm_to_redcap_study_level.php');

	$iframe_html = <<<_if
	<iframe
		id="inlineFrameExample"
		title="Inline Frame Example"
		src="$path_to_thing">
	</iframe>
	_if;

	// echo $iframe_html;

}
