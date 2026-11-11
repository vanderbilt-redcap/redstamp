<?php

namespace Vanderbilt\REDSTAMP\ExternalModule;

// FIXME: add a dropdown for these
$test_record_id = 1;
$test_domain = "AE";
$result = $module->applyMappingToRecord($test_record_id, $test_domain);

$fields = $module->getSDTMFields($test_domain);

$DSP = new DataSetPrep($result, $fields);

$df = $DSP->prepareOutput($test_domain);

$json_literal = json_encode($df);

?>

<form action="<? echo $module->getUrl('interfaces/dl_csv.php'); ?>" method="POST">
    <!-- Optional: Pass data to your PHP script dynamically -->
    <input type="hidden" name="report_type" value="monthly_summary">

    <button type="submit" style="padding: 10px 20px; cursor: pointer;">
        Generate & Download Dataset CSVs for Each Domain
    </button>
</form>

<h1>Preview</h1>

<!-- Note: Keep the <table> tag entirely empty -->
<table id="dynamicTable" class="display" style="width:100%"></table>

<script>
$(document).ready(function () {
		// FIXME: should use JSMO here
    const table_data = <?php echo $json_literal; ?>;

    if (table_data.length > 0) {

        // transform ['name', 'position'] into [{title: 'name', data: 'name'}, ...]
        const dynamic_columns = Object.keys(table_data[0]).map(key => {
            return {
                title: key,
                data: key
            };
        });

        $('#dynamicTable').DataTable({
            data: table_data,
            columns: dynamic_columns
        });
    } else {
        // Fallback fallback if there is no data to parse
        $('#dynamicTable').html('<thead><tr><th>No Data Available</th></tr></thead>');
        $('#dynamicTable').DataTable();
    }
});
</script>

<?php

exit();
