$(document).ready(() => {

	const module = ExternalModules.Vanderbilt.REDSTAMP.ExternalModule;
	const codelist_code_colname = "Codelist Code";
	const ct_codelist_code_colname = "CDISC CT Codelist Code(s)";

	let step_map = {
		"Req": {
			"total": $(".req-Req").length,
			"checked": 0
		},
		"Exp": {
			"total": $(".req-Exp").length,
			"checked": 0
		},
		"Perm": {
			"total": $(".req-Perm").length,
			"checked": 0
		}
	}

	$("select#domain_filter").select2();

	let just_changed_uid = "";

	$("#hackprog").click(() => {hackprog()});

	// HACK: bootstrap table redraws all elements and loses jquery clicks
	// FIXME: moved away from bst, don't need anymore?
 // this reapplies this
	$('#table').on('post-body.bs.table', function (e, data) {
		applyClicks();
	});

	applyClicks();

	function applyClicks() {

		applyMapClick();

		applyInput();

		const debounceNewCol = debounce(
			function(event) {
				addOutputColumn()
			},
			750
		)

		// $(".add-output-row").on('click', (event) => {
		// HACK: rather than have to reapply this to every newly appended element
		// apply to parent
		$("#table").on('click', 'button.add-output-row', (event) => {
			addOutputColumn();
			applyInput();
		});

		$("#table").on('click', 'button.add-conditional-logic', (event) => {
			event;
			const input_id = $(event.currentTarget).data('notes-input');

			openLogicEditor($(`#${input_id}`));
		});


		$("#table").on('click', 'button.row-notes', (event) => {
			event;
			const input_id = $(event.currentTarget).data('notes-input');
			openLogicEditor($(`#${input_id}`));

		});


		// $(".calc_text_input").on("click", (e) => {
		// HACK: rather than have to reapply this to every newly appended element
		// apply to parent
		$("#table").on("click", '.calc_text_input', (e) => {
			let target_uid = $(e.target).parents("tr")[0].id
			launchInfoPanel(target_uid);
		});
	}

	function applyInput() {
		const debouncedInput = debounce(
			function(event) {
				let calc_text = $(event.target).val();
				let target_uid = $(event.target).attr('id').substring("input_".length)

				saveValAjax(target_uid, calc_text, event.target);
			}, 500
		);

		// TODO: should this be a focus detection instead of input
		// would remove need for debounce
		$(".calc_text_input").on('input', (event) => {
		// HACK: rather than have to reapply this to every newly appended element, apply to parent
		// $("#table").on('input', '.calc_text_input', (event) => {
			// debouncedInput(event);

			// debounce not needed with logicEditor as input only fires on save
				let calc_text = $(event.target).val();
				let target_uid = $(event.target).attr('id').substring("input_".length)

			saveValAjax(target_uid, calc_text, event.target);
			// NOTE: if switching save back to every keystroke, this will need to be called on a mut observer watching the tinymce element
			closeInfoModals();
		});
	}


	function saveValAjax(uid, calc_text, trigger_input_element) {
		console.log(trigger_input_element);
		// FIXME: default should never be empty!!
		let domain = module.tt("domain_filter") ?? "";
		let instance = 0;
		const idx_pfx = "col_instance_";
		trigger_input_element.className.split(" ").forEach((class_name) => {
			if (class_name.startsWith(idx_pfx)) {
				instance = class_name.substr(idx_pfx.length)
			}
		});

		debugger;
		let payload = {
			"uid": uid,
			"domain": domain,
			"instance": instance,
			"calc_text": calc_text
		};
		let highlight_element = $(trigger_input_element).parent("td") ?? null;
		if (uid.includes("__conditional_logic__")) {
			const el_find = $(trigger_input_element).attr("data-notes-input");
			highlight_element = $(`[data-notes-input='${el_find}']`);
		}

		// notes need to bypass calc vaidation since this is free text
		if (uid.includes("__row_notes__")) {
			module.ajax("store_calc_mapping", payload).then((response) => {
				const button_element_data_attr =
					$(trigger_input_element).attr("data-notes-input")
				highlight_element = $(`[data-notes-input='${button_element_data_attr}']`).parent("td");
				highlight_element.effect('highlight', {}, 2000);
			});
			return;
		}

		module.ajax("check_logic", payload).then((response_valid) => {
			console.log(response_valid);
			highlight_element.removeClass("failed-logic");
			const highlight_time = 2000;

			if (!response_valid) {
				highlight_element.addClass("failed-logic");
				// TODO: tt_language this
				alert("The logic entered for this field is invalid and was not saved!\nIf you leave this page, your entry will be erased.");
			} else {

				module.ajax("store_calc_mapping", payload).then((response) => {
					// highlight input element to show save occurred
					// adapted from REDCap core Resources/js/base.js highlightTable
					highlight_element.effect('highlight', {}, highlight_time);
				});
			}

		});


	}

	function debounce(func, wait = 100) {
		let timeout;
		return function(...args) {
			const context = this;
			clearTimeout(timeout);
			timeout = setTimeout(() => {
				func.apply(context, args);
			}, wait);
		};
	}

	function addOutputColumn(column_title = null) {
		// TODO: consider numbering these
		debugger;
		let col_html = $("#table thead tr th").last().prop("outerHTML");
		$("#table thead tr").append(col_html);

		// Iterate through each body row and append a cell with unique content
		$('#table tbody tr').each(function(idx) {
			let last_col = $(this).children("td").last()
			let new_col = last_col.clone()

			let new_input = new_col.children("input")
			let row_input_id = new_input.attr("id")
			let row_id_components = row_input_id.split("__");
			let prev_idx = parseInt(row_id_components[row_id_components.length - 1]);

			let n = prev_idx + 1;

			let new_row_input_id = row_id_components.slice(0, -1).join("__") + `__${n}`;

			new_col.children("input")
				.attr("id", new_row_input_id)
				.attr("name", new_row_input_id)
				.removeClass(`col_instance_${prev_idx}`)
				.addClass(`col_instance_${n}`)
				.attr("value", "")
				.val('') // attr alone does not remove freshly entered values

			$(this).append(new_col);
		});

		// HACK: new map buttons don't operate, reapply their click
		applyMapClick();

		// HACK
		// FIXME


	}

	$("#transpose-table").click(() => { transposeTable(); })
	function transposeTable() {
		const t_class = "transposed";
		let target = $("#table");

		if (target.hasClass(t_class)) {
			target.removeClass(t_class);
		} else {
			target.addClass(t_class);
		}
	}

	// deprecated
	function applyMapClick() {
		// $(".map_thing_button").click((element) => {
		$(".map_thing_button").click((element) => {
			let this_button = element.target;
			let t_row = $(this_button).parents(".map_row")

			// NOTE: UID for row is Dataset name + variable name
			let row_uid = t_row.attr("id");

			let input_element = $(this_button).siblings("input")
			let input_uid = input_element.attr("id").substr("input_".length);
			row_uid = input_uid;

			// TODO: store
			just_changed_uid = row_uid;

			// HACK: jQuery very mad about dots
			let escaped_selector = $.escapeSelector(row_uid);

			// HACK: add logic suggest div to select from
			let logic_select_html = `<div id="LSC_id_${input_element.prop('id')}" class="fs-item fs-item-parent" style="display: none;"></div>`;
			input_element.after(logic_select_html);

			openLogicEditor($(`#input_${escaped_selector}`));
			// logicSuggestSearchTip($(`#input_${escaped_selector}`), null, false, true, 0);
			input_element.keydown(
				() => {
					logicSuggestSearchTip(input_element, null, false, true, 0);
				}
			)

			// HACK: first class is req-foo
			let req_class = t_row.attr('class').split(/\s+/)[0]
			let n_steps = $(`.${req_class}`).length;
			let is_checked = true;

			// FIXME: repeatable makes this bad
			updateProgressBar(t_row, is_checked);
		});
	}

	async function launchInfoPanel(uid) {
		// const codelist_code_colname = "Codelist Code";
		// const ct_codelist_code_colname = "CDISC CT Codelist Code(s)";
		let info_content = "<p>placeholder info</p>";

		// method 2, use jsmo
		let field_info = module.tt("sdtm_fields").find((e) => {
			const dataset_name = uid.split("__")[0];
			const variable_name = uid.split("__")[1];
			return (
				(e["Variable Name"] == variable_name) &&
				(e["Dataset Name"] == dataset_name)
			);
		})

		let ctr_info = null;
		if (!uid.endsWith("conditional_logic")) {
			ctr_info = getCTR(field_info);
			info_content = buildInfoPanel(ctr_info);
		} else {
			info_content = "Conditional logic which determines if this row should be present in output.";
		}

		$("#myModal").html(info_content);
		const existing_dialog = await waitForElementToExist('#rc-ace-editor-dialog');


		// let existing_dialog = ($("#rc-ace-editor-dialog").length !== 0) ? $("#rc-ace-editor-dialog") : false;
		$("#myModal").dialog({
			title: `${uid}`,
			position: {
				collision: "flipfit",
				my: "left",
				at: "right+5%",
				of: ( existing_dialog ?? window )
			},
			width: "25%",
			height: "auto",
			maxHeight: $(window).height() * 0.95,
			buttons: [
				{
					text: "Show factors",
					// icon: "ui-icon-trash", // Example of adding a jQuery UI icon
					click: function() {
						// TODO: shove this under buildFactorTable
						buildFactorTable(ctr_info.factor_matches);
						$("#factor_tbl_div").dialog({
							title: `${uid} factors`,
							position: {
								collision: "flipfit",
								my: "left",
								at: "left center",
								of: ( window )
							},
							width: "40%",
							height: "auto",
							maxHeight: $(window).height() * 0.95
						});

					},
					class: "show-factor-button" // Add a custom CSS class
				}
			]
		});

		if (ctr_info.factor_matches.length === 0) {
			$(".show-factor-button").hide();
		}

		// HACK: couple closing of rc-ace-editor to this dialog
		// FIXME: only applies on 2nd instance
		$(".ui-dialog[aria-describedby='rc-ace-editor-dialog']")
			.on(
				"dialogbeforeclose",
				// HACK: if not wrapped in an anon function, it's called immediately
				() => { closeInfoModals() }
			);

		// NOTE: dialogclose is NOT submitted by tinyMCE when user saves
		$(existing_dialog).on("dialogclose", () => { closeInfoModals() });
	}

	// https://bobbyhadz.com/blog/javascript-wait-for-element-to-exist
	function waitForElementToExist(selector) {
		return new Promise(resolve => {
			if (document.querySelector(selector)) {
				return resolve(document.querySelector(selector));
			}

			const observer = new MutationObserver(() => {
				if (document.querySelector(selector)) {
					resolve(document.querySelector(selector));
					observer.disconnect();
				}
			});

			observer.observe(document.body, {
				subtree: true,
				childList: true,
			});
		});
	}

	function getCTR(sdtm_field_obj) {
		const codelist_code = sdtm_field_obj[ct_codelist_code_colname];
		const ctr_code_colname = "Code";
		const fields = module.tt('sdtm_fields');
		let ctr = module.tt('sdtm_ctr');
		if (!ctr) {
			$.ajax(
				{
					type: 'GET',
					url: '/external_modules/?prefix=redstamp&page=pages%2Flazy_load&pid=44',
					data: {
						resource: "getActiveSDTMCT"
					}
				}
			).then((data) => {
				module.tt_add('sdtm_ctr', data);
				ctr = data;
			})
			return;
		}
		// const ctr = module.tt('sdtm_ctr');

		// detail for the focused field
		// fetched to ensure match
		const sdtm_info = fields.find(
			x => x[ct_codelist_code_colname] === codelist_code
		);
		const is_match = (sdtm_field_obj === sdtm_info);
		// TODO: check if this is ever false and act on it

		const ctr_info = ctr.find(
			x => x[ctr_code_colname] === codelist_code
		);

		// detail for allowable factors for the focused field
		const factor_matches =
			(ctr_info === undefined) ? [] :
				ctr.filter(
					x => x[codelist_code_colname] === codelist_code
				);

		return {
			"sdtm_info": sdtm_field_obj,
			"ctr_info": ctr_info,
			"factor_matches": factor_matches
		}
	}

	function buildInfoPanel(combo_obj) {
		const hidden_cols = [
			"Standard and Date"
		];
		let info_content = "";

		info_content += combo_obj.sdtm_info['CDISC Notes'];

		if (combo_obj.ctr_info === undefined) {
			return info_content;
		}
		info_content += "<hr>";
		// info_content += "<h8 style='display: grid; place-items: center;'>CTR</h8>";

		let $table = $('<table>')
			.addClass('table table-striped table-responsive table-hover');
		let $thead = $('<thead>')
			// TODO: move this to css, perhaps a sticky-header class and apply to main table
			.addClass("sticky-header")
		let $tbody = $('<tbody>');
		let $header_row = $('<tr>')
			.append(
				$('<th>')
					.text('th1')
			);

		for (const [k, v] of Object.entries(combo_obj.ctr_info)) {
			if (hidden_cols.includes(k)) {
				continue;
			}
			// info_content += `</br>${k}: ${v}`;
			let $row = $('<tr>');
			$row.append($('<td>').text(k));
			$row.append($('<td>').text(v));
			$tbody.append($row);
		}

		$table.append($thead).append($tbody);
		info_content += $table[0].outerHTML

		if (combo_obj.factor_matches.length == 0) {
			return info_content;
		}

		info_content += "</br>";
		// TODO: add as a button for the info dialog instead
		const btn = $("<button>")
			.attr("id", "show_factor_tbl")
			.attr("type", "button")
			.addClass("btn btn-sm btn-secondary")
			.attr("style", "display: grid; place-items: right;")
			.text("Show factors")

		// info_content += btn[0].outerHTML;

		// info_content += "<button id='show_factor_tbl' type=''>Factors</button>"
		info_content += "</br>";
		return info_content;

		// TODO: if this is the same for all
		// and present in parent
		// add here
		const factor_hidden_cols = [
			"Codelist Code",
			"Codelist Name"
		];

		info_content += "</br>";
		info_content += "</br>";
		info_content += "===FACTORS===";
		info_content += "</br>";


		combo_obj.factor_matches.forEach((factor) => {
			info_content += "</br>";
			for (const [k, v] of Object.entries(factor)) {
				if (
					hidden_cols.includes(k) ||
					factor_hidden_cols.includes(k)
				) {
					continue;
				}
				info_content += `</br>${k}: ${v}`;
			}
		});
		return info_content;
	}

	function buildFactorTable(data) {
		const factor_table_id = "factor_tbl_div";
		if (data.length == 0) return; // this should never happen

		$(`#${factor_table_id}`).html("");
		// TODO: detect if this column is empty/identical for all factors
		// if so, exclude, put value above table
		const exclude_cols = [
			"Codelist Code",
			"Codelist Name",
			"Code",
			"Codelist Extensible (Yes/No)",
			"Standard and Date"
		];

		let $table = $('<table>')
			.addClass('table table-striped table-responsive table-hover');
		let $thead = $('<thead>')
			// TODO: move this to css, perhaps a sticky-header class and apply to main table
			.addClass("sticky-header")
		let $tbody = $('<tbody>');

		let $header_row = $('<tr>');
		Object.keys(data[0]).forEach((colname) => {
			if (exclude_cols.includes(colname)) return;
			$header_row.append(
				$('<th>')
					.text(colname)
			);
		});
		$thead.append($header_row);

		data.forEach((factor) => {
			let $row = $('<tr>');
			for (const [colname, v] of Object.entries(factor)) {
				if (exclude_cols.includes(colname)) continue;
				$row.append($('<td>').text(v));
			}
			$tbody.append($row);
		})

		// add search box
		$(`#${factor_table_id}`)
			.append($('<input type="text" id="search" class="form-control sticky-header" placeholder="Filter results">'));

		// Append the header and body to the table, then the table to the container
		$table.append($thead).append($tbody);
		$(`#${factor_table_id}`).append($table);

		// apply search filter to current table
		// Adapted from: https://stackoverflow.com/a/9127872
		// Posted by dfsq, modified by community. See post 'Timeline' for change history
		// Retrieved 2026-02-12, License - CC BY-SA 3.0
		let $rows = $(`#${factor_table_id} table tbody tr`);
		$('#search').keyup(function() {
			let val = $.trim($(this).val()).replace(/ +/g, ' ').toLowerCase();

			$rows.show().filter(function() {
				let text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
				return !~text.indexOf(val);
			}).hide();
		});
	}

	function closeInfoModals() {
		$("#myModal").dialog("close")
		$(".sdtm-modal").each((idx, e) => {
			try {
				$(e).dialog("close");
			} catch (err) {
				// HACK: if factor table never launched, trying to close it throws an error
			}
		});
	}


	function updateProgressBar(input_element, increase = true) {
		let req_class = input_element.attr('class').split(/\s+/)[0]
		// let rc_root = req_class.substring(4);
		let rc_root = input_element.attr('id').substring(-3);
		rc_root = "Req";
		let n_steps = step_map[rc_root].total;

		let increment = 100 / n_steps;

		if (increase) {
			step_map[rc_root].checked += increment;
		} else {
			step_map[rc_root].checked -= increment;
		}

		let cur_prog = step_map[rc_root].checked

		let element = $(`#mapping-progress-bar-${rc_root}`);
		element
			.css('width', `${cur_prog}%`)
			.attr("aria-valuenow", cur_prog);

		// HACK: workaround floating point issues accumulating in entire integers being missed
		if (cur_prog >= 99) {
			element
				.css('width', "100%")
				.attr("aria-valuenow", 100)
				.removeClass("progress-bar-animated")
				.removeClass("progress-bar-striped")
				.addClass("bg-success");

			doConfetti();

			// set up for another run without page reload
			// cur_prog = 0;
		} else {
			element
				.removeClass("bg-success")
				.addClass("progress-bar-animated")
				.addClass("progress-bar-striped");
		}
	}

function hackprog() {


	let targ_elem = $("#mapping-progress-bar-Req");

	for (var i = 0; i < 15; ++i) {
		updateProgressBar(targ_elem, true);
	}

}

	function doConfetti() {

		let confetti_config = {
				particleCount: 350,
			spread: 180 + 22.5,
				// origin: { y: 0.5 },
				drift: -5,
			startVelocity: 90
		};

		// center
		// confetti(confetti_config);

		confetti_config.spread = 45*3;
		// top left
		confetti_config.origin = {x: 0, y: 0};
		confetti_config.angle = 0 - 45;
		confetti_config.drift = 0;
		confetti(confetti_config);
		// top right
		confetti_config.origin = {x: 1, y: 0};
		confetti_config.drift = -1;
		confetti_config.angle = 180 + 45;
		confetti(confetti_config);

		// confetti_config.gravity = -1;
		// bottom left
		confetti_config.origin = {x: 0, y: 1};
		confetti_config.drift = 0;
		confetti_config.angle = 0 + 45;
		confetti(confetti_config);
		// top right
		confetti_config.origin = {x: 1, y: 1};
		confetti_config.drift = -1;
		confetti_config.angle = 180 - 90 + 45;
		confetti(confetti_config);

		// custom text

			let scalar = 5;
			var c = confetti.shapeFromText({
				text: 'SDTM mapping complete!',
				scalar: scalar,
				color: "#eb4034"
			});

			confetti({
				shapes: [c],
				particleCount: 1,
				spread: 360,
				origin: { y: 0.6 },
				drift: 0,
				gravity: -1,
				flat: true,
				startVelocity: 12,
				scalar: scalar
			});

	}


});
