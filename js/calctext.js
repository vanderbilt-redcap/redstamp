    $(document).ready(function() {
			console.log("calctextjs");
			var dialog, form,

			dialog = $( "#dialog-form" ).dialog({
				autoOpen: false,
				height: 400,
				width: 350,
				modal: true,
				buttons: {
					// "Create an account": addUser,
					Cancel: function() {
						dialog.dialog( "close" );
					}
				},
				close: function() {
					// form[ 0 ].reset();
					// allFields.removeClass( "ui-state-error" );

					// [$("#")].removeClass( "ui-state-error" );
				}
			});

      $(".map_thing_button").click(function() {
            // $("#myModal").css("display", "block");
				dialog.dialog("open");
        });

        $(".close-button").click(function() {
            $("#myModal").css("display", "none");
        });

        $(window).click(function(event) {
            if ($(event.target).is("#myModal")) {
                $("#myModal").css("display", "none");
            }
        });

        $("#myForm").submit(function(event) {
            event.preventDefault(); // Prevent default form submission
            // Handle form data, e.g., send via AJAX
            alert("Form submitted!");
            $("#myModal").css("display", "none"); // Close modal after submission
        });
    });
