$(document).ready(() => {

	let cur_prog = 0;
	// const cboxes = 1;

	const cboxes = $(".prog_cbox").length


	$(".prog_cbox").change((element) => {
		// console.log(element);
		let is_checked = $(element.target).is(":checked");
		// console.log(is_checked);
		updateProgressBar(cboxes, is_checked);
	});

	function updateProgressBar(n_steps = 4, increase = true) {
		// console.log(`expect to complete: ${n_steps}`);
		let increment = 100 / n_steps;

		if (increase) {
			cur_prog += increment;
		} else {
			cur_prog -= increment;
		}

    // console.log(increment);

		let element = $("#mapping-progress-bar");
		element
			.css('width', `${cur_prog}%`)
			.attr("aria-valuenow", cur_prog);

		// console.log(cur_prog);

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
