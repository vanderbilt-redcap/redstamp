$(document).ready(() => {

	const module = ExternalModules.Vanderbilt.REDSTAMP.ExternalModule;
	const em_page = module.tt('sdtm_config_page');

	let config_link = $(`
	<li>
	<a href="${em_page}" style="font-size:13px;color:#393733;padding:7px 9px;">
	<i class="fas fa-tasks"></i>
	RE<b style="color: #A00000">DST</b>A<b style="color: #A00000;">M</b>P Setup
	</a>
	</li>
`);

	$("#sub-nav ul")
		.children("li")
		.eq(1) // after project setup
		.after(config_link);
});
