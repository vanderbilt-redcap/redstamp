$(document).ready(() => {

	const module = ExternalModules.Vanderbilt.REDSTAMP.ExternalModule;
	// TODO: update this when changes occur
	let module_project_settings = module.tt("project_settings");
	console.log(module_project_settings);

	// HACK: don't disable while testing
	$("button.config-button").prop('disabled', false);

	$(".config-button").on("click", (e) => {
		launchInfoPanel($(e.target));
	});

	function launchInfoPanel(target_element) {
		let task = target_element.attr('id');
		let target_location = ""; // only used in redirects

		switch (task) {
		case "select-sdtmig":
			infoPanelIG();
			break;
		case "select-sdtmct":
			infoPanelCT();
			break;
		case "select-define-xml":
			infoPanelDefineXmlVersion();
			break;
		case "id-study-level-domains":
			// TODO: domains are very similar, may just want a bool
			infoPanelDomain(true);
			break;
		case "id-subject-level-domains":
			infoPanelDomain(false);
			break;
		case "add-edit-supp-quals":
			infoPanelSuppQuals(false);
			break;
		case "add-edit-non-subject-level-info":
			// HACK
			/*
			 * TODO: check config.json for sdtm_to_redcap
			 *  use module.getUrlParameters to grab PID
			 *  restrict to subject-level info
			 */
			target_location = module.getUrl("interfaces/sdtm_to_redcap_study_level.php");
			window.open(target_location);
		break;
		case "add-edit-variable-mappings":
			target_location = module.getUrl("interfaces/sdtm_to_redcap_subject_level.php");
			// open in new tab
			window.open(target_location);
			break;
		default:
			console.log(`unfinished case: ${task}`);
		}

		// TODO: standardize this but allow override in functions?
  // $("#myModal").dialog({
		// 	title: `${uid}`,
		// 	position: {
		// 		collision: "flipfit",
		// 		// my: "left",
		// 		// at: "right+5%",
		// 		// of: ( window )
		// 	},
		// 	width: "25%",
		// 	height: "auto",
		// 	maxHeight: $(window).height() * 0.95
		// });
	}

	function infoPanelIG() {
		const project_settings_key = "active_sdtmig";

		let dropdown = `
		<label for="sdtmig-select">SDTM Version:</label>
		<select id='sdtmig-select'></select>
`;

		let html =
			$("<div class='round chklist' style='padding: 10px 20px;'></div>")
				// .append("<form></form>")
				// .append("<table style='width: 100%;' cellpadding=0 cellspacing=0></table>")
				.append(`<p>
				Select the SDTM IG version that you will be using for this project. It is recommended to use the most recent version unless there is a specific reason to use an earlier version for your project.
				</p>
`).append(dropdown);

		let uid = "Select the SDTM IG version";
		let data = module.tt("sdtmigs");

		data = data.map(item => {
			// select2 wants id and text fields
			return {
				...item,
				id: item.href,
				// text: `${item.name} - ${item.title}`
				text: item.name,
				// TODO: ensure selected is updated on rebuild
				selected: (item.href == module_project_settings["active_sdtmig"]) ? "selected" : false
			}
		});

		$("#myModal").html(html);

		$("#sdtmig-select").select2({
			dropdownAutoWidth: true,
			data: data
		})

		if (!module_project_settings["active_sdtmig"]) {
			// NOTE: this should be suitable with an ajax call for placeholder as well as default
			let placeholder = new Option('--- Select One ---', 'default_id', true, true);
			$("#sdtmig-select").prepend(placeholder).trigger('change');
			placeholder.disabled = true;
		}

		$("#myModal").dialog({
			bgiframe: true,
			modal: true,
			width: 700,
			title: `${uid}`,
			position: {
				collision: "flipfit",
				// my: "left",
				// at: "right+5%",
				// of: ( window )
			},
			open: function(){
				fitDialog(this);
			},
			height: "auto",
			maxHeight: $(window).height() * 0.95,
			buttons: [
				{
					text: "Close",
					// NOTE: arrow functions void use of "this" to close dialog
					click: function() { $(this).dialog('close'); }
				},
				{
					text: "Save",
					click: function() {
						let payload = {
							"setting": "active_sdtmig",
							"value": $("#sdtmig-select").val()
						};
						module.ajax("save_project_setting", payload).then((response) => {
							$("button.config-button").prop('disabled', false);
							$(this).dialog('close');
						});
					}
				}
			]
		});

	}


	function infoPanelCT() {
		const setting_uid = "sdtmct";
		const project_settings_key = `active_${setting_uid}`;
		const current_setting_value = module_project_settings[`active_${setting_uid}`];

		let dropdown = `
		<label for="${setting_uid}-select">Controlled Terminology Version:</label>
		<select id='${setting_uid}-select'></select>
`;

		let html =
			$("<div class='round chklist' style='padding: 10px 20px;'></div>")
				// .append("<form></form>")
				// .append("<table style='width: 100%;' cellpadding=0 cellspacing=0></table>")
				.append(`<p>
				Select the Controlled Terminology version that you will be using for this project. It is recommended to use the most recent version unless there is a specific reason to use an earlier version for your project.
				</p>
`).append(dropdown);

		let uid = "Select the SDTM CT version";
		// let data = module.tt("sdtmcts");

		// TODO: if no value should default to none
		// TODO: this should be lazy loaded
		// data = data.map(item => {
		// 	// select2 wants id and text fields
		// 	return {
		// 		...item,
		// 		id: item.href,
		// 		// text: `${item.name} - ${item.title}`
		// 		text: item.title,
		// 		// TODO: ensure selected is updated on rebuild
		// 		selected: (item.href == module_project_settings[`active_${setting_uid}`]) ? "selected" : false
		// 	}
		// });

		$("#myModal").html(html);

		// reach out to CDISC API to fetch params when dropdown is clicked
		$(`#${setting_uid}-select`).select2({
			dropdownAutoWidth: true,
			width: 'auto',
			// NOTE: ajax sourcing data probably not suitable due to lack of ability to preselect
			// data: data,
			ajax: {
				url: '/external_modules/?prefix=redstamp&page=pages%2Flazy_load',
				data: (params) => {
					let query = {
						search: params.term,
						resource: "getAvailableSDTMCTs"
					}

					debugger;
					return query;

				},
				processResults: (data) => {
					// data = JSON.parse(data);

					data = data.map(item => {
						// select2 wants id and text fields
						return {
							...item,
							id: item.href,
							// text: `${item.name} - ${item.title}`
							text: item.title,
							// TODO: ensure selected is updated on rebuild
							selected: (item.href == current_setting_value) ? "selected" : false
						}
					});

					return {
						results: data
					};
				}
			}
		})

		let placeholder = null;
		if (!module_project_settings[`active_${setting_uid}`]) {
			// NOTE: this should be suitable with an ajax call for placeholder as well as default
			placeholder = new Option('--- Select One ---', 'default_id', true, true);
			$(`#${setting_uid}-select`).prepend(placeholder).trigger('change');
			placeholder.disabled = true;
		} else {
			debugger;
			// HACK: calling the API on modal launch just to get the human readable name
			// in the event of aggressive rate limiting, store the human readable name and expose it here
			$.ajax({
				type: 'GET',
				url: '/external_modules/?prefix=redstamp&page=pages%2Flazy_load',
				data: {
					resource: "getAvailableSDTMCTs"
				}
			}).then((data) => {
				// preselect with proper title
				const match = data.find(ct => ct.href == current_setting_value);

				placeholder = new Option(match.title, current_setting_value, true, true);

				$(`#${setting_uid}-select`).prepend(placeholder).trigger('change');
				placeholder.disabled = true;
			});
			// placeholder = new Option(current_setting_value, current_setting_value, true, true);
		}


		$("#myModal").dialog({
			bgiframe: true,
			modal: true,
			width: 700,
			title: `${uid}`,
			position: {
				collision: "flipfit",
				// my: "left",
				// at: "right+5%",
				// of: ( window )
			},
			open: function(){
				fitDialog(this);
			},
			height: "auto",
			maxHeight: $(window).height() * 0.95,
			buttons: [
				{
					text: "Close",
					// NOTE: arrow functions void use of "this" to close dialog
					click: function() { $(this).dialog('close'); }
				},
				{
					text: "Save",
					click: function() {
						let payload = {
							"setting": "active_sdtmct",
							"value": $("#sdtmct-select").val()
						};
						module.ajax("save_project_setting", payload).then((response) => {
							$("button.config-button").prop('disabled', false);
							$(this).dialog('close');
						});
					}
				}
			]
		});

	}

	function infoPanelDefineXmlVersion() {
		const setting_uid = "sdtm_define_xml_version";
		const project_settings_key = `active_${setting_uid}`;
		const current_setting_value = module_project_settings[`active_${setting_uid}`];

		let radio_section = `
		<span style="font-weight: bold;">Define.xml version</span>
		<div id="radio-box" style="display: flex; align-items: center; gap: 8px;">
		<input type="radio" id='xml_2-radio'>
		<label for="${setting_uid}-select">2.1</label>
		</div>
		<div id="radio-box2" style="display: flex; align-items: center; gap: 8px;">
		<input type="radio" id='${setting_uid}2-select'>
		<label for="${setting_uid}2-select">2.0</label>
		</div>
`;

		let dropdown = `
		<label for="${setting_uid}-select">Controlled Terminology Version:</label>
		<select id='${setting_uid}-select'></select>
		</br>
`;

		let html =
			$("<div class='round chklist' style='padding: 10px 20px;'></div>")
				// .append("<form></form>")
				// .append("<table style='width: 100%;' cellpadding=0 cellspacing=0></table>")
				.append(`<p>
				Select the version of define.xml you will be using. It is recommended to use the most recent version. If using a version 2.1 you will also need to select the version of the define.xml controlled terminology that you will be using
				</p>
`)
				.append(radio_section)
				.append(dropdown);

		let uid = "Select the SDTM CT version";
		$("#myModal").html(html);

		let placeholder = null;

		$("#myModal").dialog({
			bgiframe: true,
			modal: true,
			width: 700,
			title: `${uid}`,
			position: {
				collision: "flipfit",
				// my: "left",
				// at: "right+5%",
				// of: ( window )
			},
			open: function(){
				fitDialog(this);
			},
			height: "auto",
			maxHeight: $(window).height() * 0.95,
			buttons: [
				{
					text: "Close",
					// NOTE: arrow functions void use of "this" to close dialog
					click: function() { $(this).dialog('close'); }
				},
				{
					text: "Save",
					click: function() {
						// let payload = {
						// 	"setting": "active_sdtmct",
						// 	"value": $("#sdtmct-select").val()
						// };
						// module.ajax("save_project_setting", payload).then((response) => {
						// 	$("button.config-button").prop('disabled', false);
						// 	$(this).dialog('close');
						// });
					}
				}
			]
		});

	}


	function infoPanelDomain(is_study_level = true) {
		// TODO: ensure checkboxes are populated
		let header_txt = "";
		const project_setting_key = (is_study_level) ? "active_study_level_domains" : "active_subject_level_domains";

		if (is_study_level) {
			header_txt = `<h3>Study-Level Domains</h3>
			<p>SDTM defines a number of domains that provide information about the study itself to provide context to the data collected by the study. Each domain dataset is named with a unique 2-character code. Only domain datasets that are applicable for the study should be used for your project.</p>`;
		} else {
			header_txt = `<h3>Subject-Level Domains</h3>
			<p>Data collected for study subjects are normally mapped into a series of SDTM domains. A domain is a collection of logically related observations with a common topic. Each domain dataset is named with a unique 2-character code. Only domain datasets that were collected or directly derived from the data collected should be used for your project. Each of these domains falls under one of SDTM general observation classes.</p>`;
		}

		header_txt += `<p>Select which domains are applicable for your project under each categories.</p>`;

		// header_txt = $("")
		// 	.append(`<h3>Study-Level Domains</h3>`)
		// 	.append(`<p>SDTM defines a number of domains that provide information about the study itself to provide context to the data collected by the study. Each domain dataset is named with a unique 2-character code. Only domain datasets that are applicable for the study should be used for your project.</p>`)
		// 	.append(`</br>`)
		// 	.append(`<p>Select which domains are applicable for your project under each categories.</p>`);

		let html = header_txt;
		html += `<img id='loading_gif' src='${module.tt('appPathImages')}loader_simple.gif' style='display:block; margin:auto;'>`;
		$("#myModal").html(html);
				// .append("<div class='round chklist' style='padding: 10px 20px;'></div>")
				// .append("<table style='width: 100%;' cellpadding=0 cellspacing=0></table>");

		// TODO: build table rows from JSON:

		let payload = null;
		module.ajax("get_domains", payload).then((response) => {
			for (const c of response.classes) {
				// per-subject for subject-level
				let has_per_subject = null;

				if (c.datasets === undefined) {
					continue;
				}

				let class_html =$("<div class='round chklist' style='padding: 10px 20px;'></div>")
					.append(`<h4>${c.label} Class</h4>`)
					.append(`<hr />`)
					.append(`<p>${c.description}</p>`)

				for (const ds of c.datasets) {

					// explicitly requested to skip SUPPQUAL
					if (ds.name === "SUPPQUAL") continue;

					if (ds.datasetStructure.endsWith("per subject")) {
						has_per_subject = true;
						if (is_study_level) {
							break;
						}
					}

					// TODO: add active subject domains
					// HACK: this should be a const instead of let, but if'd const def results in undefined
					// TODO: init as empty array
					let domain_selected = false;
					if (project_setting_key in module_project_settings) {
						domain_selected = module_project_settings[project_setting_key].includes(ds.name) ?? false;
					}
					let ds_html = $(`<div id="${ds.name}"></div>`)
						// NOTE: checkboxes selected based on presence in active_*_level_domains
						.append(`<input class="domain_chkbx" id="${ds.name}_chkbx" type="checkbox" name="${ds.name}_chkbx" ${domain_selected ? 'checked' : ''}>&nbsp;`)
						.append(`<u>${ds.name} - ${ds.label}</u>`)
						.append(`<p>${ds.description}</p>`)
						.append(`<i>${ds.datasetStructure}</i>`)

					class_html
						.append(ds_html)
						.append(`</br>`);
				}

				if (
					(is_study_level && has_per_subject) ||
					(!is_study_level && !has_per_subject)
				) {
					continue;
				}

				$("#myModal")
					.append(class_html)
					.append(`</br>`);
			}

			$("#loading_gif").remove();

			// TODO
		});

		let uid = "Domains";
		let data = module.tt("sdtmigs");

		// $("#sdtmig-select").select2({
		// 	data: data
		// })

		$("#myModal").dialog({
			bgiframe: true,
			modal: true,
			width: 700,
			title: `${uid}`,
			position: {
				collision: "flipfit",
			},
			open: function() { fitDialog(this); },
			height: "auto",
			maxHeight: $(window).height() * 0.95,
			buttons: [
				{
					text: "Close",
					click: function() { $(this).dialog('close'); }
				},
				{
					text: "Save",
					click: function() {
						// domains

						let active_values = [];
						$(".domain_chkbx:checked").each( (i, e) => {
							let domain_code = $(e)
								.attr('id')
								.replace('_chkbx', '');
							active_values.push(domain_code);
						});
						let payload = {
							"setting": project_setting_key,
							"value": active_values
						};
						module.ajax("save_project_setting", payload).then((response) => {
							// update module_project_settings in accordance with whatever got set here to avoid page reloads
							// TODO: verify this isn't just the additions
							module_project_settings[project_setting_key] = active_values;
							$("button.config-button").prop('disabled', false);
							$(this).dialog('close');
							ensureOrder();
						});
						$("button.config-button").prop('disabled', false);
					}
				}
			]
		});

	}


	function infoPanelSuppQuals() {
		let header_txt = `<h3>Add/Edit Supplemental Qualifiers</h3>
		<p>If you have additional data for a domain for which there are not standard variables in the SDTM domain, then a supplemental qualifier dataset corresponding to the relevant domain is needed. This is a separate dataset from the related domain and will need to have a separate set of information entered for study-level domains or separate mappings defined for a subject-level domain.</p>
		<p>The supplemental qualifier datasets contain data that is stored in a normalized vertical way and can contain multiple rows for each corresponding row in the dataset for the related domain.<p>
		<p>The name of the supplemental qualifier dataset will be “SUPP” followed by the two letter code for the related domain. For example, for the DM domain, the name of the supplemental qualifier dataset would be SUPPDM.</p>
		<p>Select the domains for which you need a supplemental qualifier dataset. The selected domains will then be available under either study-level information or subject-level mappings for you to complete their setup.</p>`;

		// header_txt = $("")
		// 	.append(`<h3>Study-Level Domains</h3>`)
		// 	.append(`<p>SDTM defines a number of domains that provide information about the study itself to provide context to the data collected by the study. Each domain dataset is named with a unique 2-character code. Only domain datasets that are applicable for the study should be used for your project.</p>`)
		// 	.append(`</br>`)
		// 	.append(`<p>Select which domains are applicable for your project under each categories.</p>`);

		let html = header_txt;
		html += `<img id='loading_gif' src='${module.tt('appPathImages')}loader_simple.gif' style='display:block; margin:auto;'>`;
		$("#myModal").html(html);
				// .append("<div class='round chklist' style='padding: 10px 20px;'></div>")
				// .append("<table style='width: 100%;' cellpadding=0 cellspacing=0></table>");

		// TODO: pull defined domains and add checkboxes for them

		let payload = null;
		module.ajax("get_domains", payload).then((response) => {
		});

		let uid = "Supplemental Qualifiers";

		$("#myModal").dialog({
			bgiframe: true,
			modal: true,
			width: 700,
			title: `${uid}`,
			position: {
				collision: "flipfit",
			},
			open: function() { fitDialog(this); },
			height: "auto",
			maxHeight: $(window).height() * 0.95,
			buttons: [
				{
					text: "Close",
					click: function() { $(this).dialog('close'); }
				},
				{
					text: "Save",
					click: function() {
						// TODO
						// module.save href
						return;
						console.log("TODO: saved");
						let payload = $("#sdtmig-select").val();

						module.ajax("", payload).then((response) => {
							// TODO
						});
						$("button.config-button").prop('disabled', false);
					}
				}
			]
		});

	}

	ensureOrder();

	// disable buttons based on status of settings
	function ensureOrder() {
		// HACK: for testing, pretend some are empty
		// module_project_settings['active_sdtmig'] = null;
		// module_project_settings['active_sdtmct'] = null;

		const mapping_page_button_ids = [
			"add-edit-non-subject-level-info",
			"add-edit-variable-mappings"
		];
		const domain_selection_ids = [
			"id-study-level-domains",
			"id-subject-level-domains",
		];

		// module setting key: html button element's ID for disabling
		// TODO: add object criteria for disabling here, not every setting is an array
		// {
		// 	"module_setting_key": {
		// 		"validation": "key_type",
		// 		"element_ids": [
		// 			list of html IDs that should be disabled
		// 		]
		// 	}
		// }
		// TODO: early exit for high-priority blockers like active_sdtmig and ct? maybe do that as a class instead?
		const checkers = {
			"active_sdtmig": {
				"validation": "populated_string",
				"element_ids": [
					"select-sdtmct",
					...domain_selection_ids,
					...mapping_page_button_ids
				]
			},
			"active_sdtmct": {
				"validation": "populated_string",
				"element_ids": [
					...domain_selection_ids,
					...mapping_page_button_ids
				]
			},
			"active_study_level_domains": {
				"validation": "populated_array",
				"element_ids": ["add-edit-non-subject-level-info"]
			},
			"active_subject_level_domains": {
				"validation": "populated_array",
				"element_ids": ["add-edit-variable-mappings"]
			}
		};

		for (const [checker_key, checker_obj] of Object.entries(checkers)) {
			let should_disable = false;

			const setting_value = module_project_settings[checker_key];
			const validation_type = checker_obj.validation;

			switch(validation_type) {
			case "populated_array":
				if (!(Array.isArray(setting_value) && setting_value.length > 0)) {
					should_disable = true;
				}
				break;
			case "populated_string":
				if (!((setting_value) && setting_value.length > 0 && setting_value !== "")) {
					should_disable = true;
				}
			}

			if (should_disable) {
				checker_obj.element_ids.forEach(
					(element_id) => {
						const element = $(`button#${element_id}`);
						element.prop("disabled", true);
					}
				);
			}
		}

	}

});
