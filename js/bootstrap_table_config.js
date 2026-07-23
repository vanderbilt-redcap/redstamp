 $(function() {

	 function filterKC() {
		 console.log("filterKC")

	 }

   $('#table').bootstrapTable(
     {
       stickyHeader: true
			 // onCreatedControls() {},
			 // showRefresh: true,
			 // filterControlContainer: ".bootstrap-table-filter-control-class",
			 // filterDataCollector: "foo,bar,bang",
			 // disabled_onColumnSearch: (colname, value, data) => {
			 // 	 console.log(`column: ${colname}`)
			 // 	 console.log(`value: ${value}`)
			 // 	 console.log(data)

			 // 	 console.log("---")
			 // 	 console.log(data.options.data)
			 // 	 console.log(data.data)

			 // 	 // data.data = ["foo", "bar"];

			 // 	 // data.data = data.options.data;

			 // 	 // data.unsortedData = data.data;

			 // 	 // console.log("clearing other selections")
			 // 	 // target_e = $("select.bootstrap-table-filter-control-dataset-name")
			 // 	 // console.log(target_e)
			 // 	 // target_e.empty();
			 // 	 // debugger;
			 // 	 // note that this updates on resize

			 // 	 // data.options.data = data.data;

			 // 	 // return ["foo", "bar", "bang"]
			 // }
       // search: true
     }
   )

	 // HACK: adjust icons since 1.19.1 doesn't properly scope
	 $("i[class*=bi-]").each((i, e) => {
		 let foo = $(e).attr('class').split(' ').filter((cname) => {
			 return cname.startsWith('bi-')
		 })
		 let fa_class = foo[0].replace('bi-', 'fa-');
		 $(e).addClass(fa_class);

	 })

	 // $('#table').on('editable-init.bs.table', function(e){
	 // 	 console.log("oogabooga");
	 // 	 var $els =  $('#table').find('.editable');
	 // 	 $els.each(function(index,value){
	 // 		 $(this).editable('option', 'source', data[index])
	 // 	 });
	 // });


   // have to enter this again after page load for some reason
   // document.querySelectorAll('.toggle-link').forEach(link => {
   //   link.addEventListener('click', function(e) {
   //     console.log("clicked the thing")
   //     e.preventDefault();
   //     const container = this.closest('.text-container');
   //     container.querySelector('.truncated').style.display = 'none';
   //     container.querySelector('.ellipsis').style.display = 'none';
   //     container.querySelector('.full-text').style.display = 'inline';
   //     this.style.display = 'none'; // Hide the "Read more" link
   //   });
   // });

	 // jQueryfied of the above
   // $('.toggle-link').each((i, link) => {
   //   link.addEventListener('click', function(e) {
   //     e.preventDefault();
   //     const container = this.closest('.text-container');
   //     container.querySelector('.truncated').style.display = 'none';
   //     container.querySelector('.ellipsis').style.display = 'none';
   //     container.querySelector('.full-text').style.display = 'inline';
   //     this.style.display = 'none'; // Hide the "Read more" link
   //   });
   // });

	 // TODO: force this to run after bootstrapTable completes
     $(".toggle-link").click((link) => {
       link.preventDefault();
       const container = link.target.closest('.text-container');
       container.querySelector('.truncated').style.display = 'none';
       container.querySelector('.ellipsis').style.display = 'none';
       container.querySelector('.full-text').style.display = 'inline';
       link.target.style.display = 'none'; // Hide the "Read more" link
     });

	 // $('#table').on('filter-change.bs.table', function (e, field, value) {
	 // // $('#table').on('filter-change', function (e, field, value) {
	 // 	 // When a filter changes, this event fires.
	 // 	 // You can then access the *currently filtered data*
	 // 	 var currentData = $('#myTable').bootstrapTable('getData');
	 // 	 console.log("doing sth");

   //   // Process currentData to find unique values for other columns (e.g., 'column2')
   //   var uniqueColumn2Values = [...new Set(currentData.map(item => item.column2))];

   //   // Dynamically update the select options for the other column's filter
   //   var $selectControl = $('[data-field="column2"] .filter-control');
   //   $selectControl.empty(); // Clear existing options
   //   $selectControl.append($('<option></option>').val('').text('All')); // Add default
   //   uniqueColumn2Values.forEach(function(item) {
   //     $selectControl.append($('<option></option>').val(item).text(item));
   //   });

   //   // The table automatically re-filters after the original change event,
   //   // ensuring the new options reflect the current data state.
	 // });


function uniqueValuesCollector(value, row, index, field) {
    // 'this' refers to the column object
    // You can collect unique values from the *currently* displayed rows here.
    // This usually involves accessing the table's internal data.
    // A simple approach is to use a global object to store unique values.
	console.log("uvc")
}
 })

function fcsFunc(text, value, field, data) {
	console.log("fcsF")
	console.log(`text: ${text}`);
	console.log(`value: ${value}`);
	console.log(`field: ${field}`);
	console.log(`data`);
	console.log(data)
	return null;
}

function dfdFunc(a, b, c, d) {
	console.log("dfdFunc");
	console.log(`a: ${a}`);
	console.log(`b: ${b}`);
	console.log(`c: ${c}`);
	console.log(`d: ${d}`);

	return $('#table').bootstrapTable('getData');

	// return ['foo', 'bar'].sort();
}
