    $(document).ready(function() {
        $("#openModalButton").click(function() {
            $("#myModal").css("display", "block");
        });

      $(".map_thing_button").click(function() {
        $("#logic_builder").show();
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
