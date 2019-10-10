// table variables
filtered.forEach(
  r => {
    r.Carat = parseFloat(r.Carat);
    r.Price = parseFloat(r.Price.replace(/[$,]/g, ""));
  }
);

// selected row IDs
var rowsSelected = [];
var rowsViewed = [];

let savedRowsSelected = sessionStorage['rowsSelected'];
if (savedRowsSelected != undefined) 
  rowsSelected = JSON.parse(savedRowsSelected);

let savedRowsViewed = sessionStorage['rowsViewed'];
if (savedRowsViewed != undefined) 
  rowsViewed = JSON.parse(sessionStorage['rowsViewed']);

// tabs
$("#tabs").tabs({
  event: "mouseover"
});

// table filter
var filterFunctions = {};

const moneyFormat = wNumb({
            decimals: 0,
            thousand: ',',
            prefix: '$'
          });

// functions
function initSliders() {
  // find min/max of price, carat
  let minPrice = Infinity;
  let maxPrice = 0;
  let minCarat = Infinity;
  let maxCarat = 0;

  filtered.forEach(
    r => {
      if (r.Price < minPrice) 
        minPrice = r.Price;
      if (r.Price > maxPrice) 
        maxPrice = r.Price;
      if (r.Carat < minCarat) 
        minCarat = r.Carat;
      if (r.Carat > maxCarat) 
        maxCarat = r.Carat;
    }
  );

  minPrice = Math.floor((minPrice / 100)) * 100 - 100;
  maxPrice = Math.ceil((maxPrice / 100)) * 100 + 1000;

  // price slider
  window.priceSliderUI = noUiSlider.create(document.getElementById('price'), {
    start: [minPrice, maxPrice],
    connect: false,
    animate: false,
    range: {
      'min': minPrice,
      'max': maxPrice
    },
    tooltips: true,
    format: wNumb({
      decimals: 0,
      thousand: ',',
      prefix: '$ '
    })
  });


  // carat slider
  let caratSliderUI = noUiSlider.create(document.getElementById('carat'), {
    start: [minCarat, maxCarat],
    connect: false,
    animate: false,
    range: {
      'min': minCarat,
      'max': maxCarat
    },
    tooltips: true
  });


  // color slider
  let colorSymbol = ['J', 'I', 'H', 'G', 'F', 'E', 'D'];
  let colorsliderUI = noUiSlider.create(document.getElementById('color'), {
    start: [colorSymbol[0], colorSymbol[colorSymbol.length - 1]],
    step: 1,
    range: {
      'min': [0],
      'max': [colorSymbol.length - 1]
    },
    format: {
      // 'to' the formatted value. Receives a number.
      to: function(value) {
        return colorSymbol[parseInt(value)];
      },
      // 'from' the formatted value.
      // Receives a string, should return a number.
      from: function(value) {
        let index = colorSymbol.findIndex((v) => v === value);
        return index;
      }
    },
    tooltips: false,
    pips: {
      mode: 'steps',
      density: 10,
      format: {
        // 'to' the formatted value. Receives a number.
        to: function(value) {
          return colorSymbol[value];
        },
        // 'from' the formatted value.
        // Receives a string, should return a number.
        from: function(value) {
          let index = colorSymbol.findIndex((v) => v === value);
          return index;
        }
      },
    }
  });


  // clarity slider
  let claritySymbol = ['SI2', 'SI1', 'VS2', 'VS1', 'VVS2', 'VVS1', 'IF', 'FL'];
  let claritySliderUI = noUiSlider.create(document.getElementById('clarity'), {
    start: [claritySymbol[0], claritySymbol[claritySymbol.length - 1]],
    step: 1,
    range: {
      'min': [0],
      'max': [claritySymbol.length - 1]
    },
    format: {
      // 'to' the formatted value. Receives a number.
      to: function(value) {
        return claritySymbol[value];
      },
      // 'from' the formatted value.
      // Receives a string, should return a number.
      from: function(value) {
        let index = claritySymbol.findIndex((v) => v === value);
        return index;
      }
    },
    tooltips: false,
    pips: {
      mode: 'steps',
      density: 20,
      format: {
        // 'to' the formatted value. Receives a number.
        to: function(value) {
          return claritySymbol[value];
        },
        // 'from' the formatted value.
        // Receives a string, should return a number.
        from: function(value) {
          let index = claritySymbol.findIndex((v) => v === value);
          return index;
        }
      },
    }
  });


  // polish slider
  let polishSymbol = ['Good', 'Very Good', 'Excellent'];

  let polishSliderUI = noUiSlider.create(document.getElementById('polish'), {
    start: [polishSymbol[0], polishSymbol[polishSymbol.length - 1]],
    step: 1,
    range: {
      'min': [0],
      'max': [polishSymbol.length - 1]
    },
    format: {
      // 'to' the formatted value. Receives a number.
      to: function(value) {
        return polishSymbol[value];
      },
      // 'from' the formatted value.
      // Receives a string, should return a number.
      from: function(value) {
        let index = polishSymbol.findIndex((v) => v === value);
        return index;
      }
    },
    tooltips: false,
    pips: {
      mode: 'steps',
      density: 20,
      format: {
        // 'to' the formatted value. Receives a number.
        to: function(value) {
          return polishSymbol[value];
        },
        // 'from' the formatted value.
        // Receives a string, should return a number.
        from: function(value) {
          let index = polishSymbol.findIndex((v) => v === value);
          return index;
        }
      },
    }
  });


  // report slider
  let reportSymbol = ['GIA', 'IGI', 'HRD'];

  let reportSliderUI = noUiSlider.create(document.getElementById('report'), {
    start: [reportSymbol[0], reportSymbol[reportSymbol.length - 1]],
    step: 1,
    range: {
      'min': [0],
      'max': [reportSymbol.length - 1]
    },
    format: {
      // 'to' the formatted value. Receives a number.
      to: function(value) {
        return reportSymbol[value];
      },
      // 'from' the formatted value.
      // Receives a string, should return a number.
      from: function(value) {
        let index = reportSymbol.findIndex((v) => v === value);
        return index;
      }
    },
    tooltips: false,
    pips: {
      mode: 'steps',
      density: 20,
      format: {
        // 'to' the formatted value. Receives a number.
        to: function(value) {
          return reportSymbol[value];
        },
        // 'from' the formatted value.
        // Receives a string, should return a number.
        from: function(value) {
          let index = reportSymbol.findIndex((v) => v === value);
          return index;
        }
      },
    }
  });


  // symmetry slider
  let symmetrySymbol = ['Good', 'Very Good', 'Excellent'];

  let symmetrySliderUI = noUiSlider.create(document.getElementById('symmetry'), {
    start: [symmetrySymbol[0], symmetrySymbol[symmetrySymbol.length - 1]],
    step: 1,
    range: {
      'min': [0],
      'max': [symmetrySymbol.length - 1]
    },
    format: {
      // 'to' the formatted value. Receives a number.
      to: function(value) {
        return symmetrySymbol[value];
      },
      // 'from' the formatted value.
      // Receives a string, should return a number.
      from: function(value) {
        let index = symmetrySymbol.findIndex((v) => v === value);
        return index;
      }
    },
    tooltips: false,
    pips: {
      mode: 'steps',
      density: 10,
      format: {
        // 'to' the formatted value. Receives a number.
        to: function(value) {
          return symmetrySymbol[value];
        },
        // 'from' the formatted value.
        // Receives a string, should return a number.
        from: function(value) {
          let index = symmetrySymbol.findIndex((v) => v === value);
          return index;
        }
      },
    }
  });

  // event handlers
  let shapeActive = null;

  $('.shape-btn').on('click', function(r) {
    const shape_btn = r.target.closest("div");
    const shapeString = shape_btn.id.substring(6);

    $('.shape-btn').removeClass('selectedShape'); // clear all buttons
    if (shapeActive === shapeString) {
      filterFunctions['shape'] = null;
      shapeActive = null;
    } else {
      // unclick other shape buttons

      $(shape_btn).addClass('selectedShape');
      // shapeStates[shapeString] = !shapeStates[shapeString];
      filterFunctions['shape'] = (data) => { // condition for rejecting data
        return (data.Shape.toLowerCase() !== shapeString);
      };
      shapeActive = shapeString;
    }

    masterFilterAndRender();
  });

  const cleanPrice = /[$, ]/g;
  priceSliderUI.on("change",
    function(r) {
      let limitPrice = r;
      limitPrice[0] = parseFloat(limitPrice[0].replace(cleanPrice, ''));
      limitPrice[1] = parseFloat(limitPrice[1].replace(cleanPrice, ''));

      filterFunctions['price'] = (data) => {
        var price = moneyFormat.from(data[4]);

        var isInRange = limitPrice[0] <= price && price <= limitPrice[1];
        return isInRange;
      }

      masterFilterAndRender();
    }
  );

  caratSliderUI.on("change",
    function(r) {
      const caratMaxLimit = parseFloat(r[0]);
      filterFunctions['carat'] = (data) => parseFloat(data[1]) <= caratMaxLimit;
      masterFilterAndRender();
    }
  );


  colorsliderUI.on("change",
    function(r) {
      const value = r[0];
      filterFunctions['Color'] = (data) => data.Color == value;
      masterFilterAndRender();
    }
  );

  claritySliderUI.on("change",
    function(r) {
      const value = r[0];
      filterFunctions['Clarity'] = (data) => data[8] == value;
      masterFilterAndRender();
    }
  );


  polishSliderUI.on("change",
    function(r) {
      const value = r[0];
      filterFunctions['Polish'] = (data) => data[9] == value;
      masterFilterAndRender();
    }
  );

  reportSliderUI.on("change",
    function(r) {
      const value = r[0];
      filterFunctions['Report'] = (data) => data[7] == value;
      masterFilterAndRender();
    }
  );

  symmetrySliderUI.on("change",
    function(r) {
      const value = r[0];
      filterFunctions['Symmetry'] = (data) => data[6] == value;
      masterFilterAndRender();
    }
  );
};


// custom filtering fuction
$.fn.dataTable.ext.search.push(
  function(settings, data, dataIndex) {

    var rowId = data[0];

    if (settings.nTable.id == 'results-table') {

      let matched = true;

      // match fail if one of filters is failed.
      for (var key in filterFunctions) {
        if (!filterFunctions[key](data)) {
          matched = false;
          break;
        }
      }

      return matched;

    } else if (settings.nTable.id == 'recently-viewed-table') {
      
      // debugger;

      if ($.inArray(rowId, rowsViewed) != -1)
        return true;
      return false;

    } else if (settings.nTable.id == 'comparison-table') {
      
      if ($.inArray(rowId, rowsSelected) != -1)
        return true;
      return false;

    }

    return true;
  }
);

// refresh the table according to the filter
function masterFilterAndRender() {

  var table = $('#results-table').DataTable();

  table.draw();

  // results table count
  $('#total-results').html( resultsTable.rows({filter:'applied'}).count() );
  $('#recently-views').html( recentlyViewedTable.rows({filter:'applied'}).count() );
  $('#comparison-views').html( comparisonTable.rows({filter:'applied'}).count() );
}


var editor; // use a global for the submit and return data rendering in the examples

function initTable() {

  // results table
  window.resultsTable = $('#results-table').DataTable({

    "ordering": false,
    "info": false,

    /* paging mode */
    "dom": 'tp',


    /* scroll mode */

    // "paging": false,
    // "scrollY": "500px",
    // "deferRender": true,
    // "scroller": true,

    "columns": [
      { 
        "title": "", 
        "data": "Product ID", 
        "defaultContent": "",
        "orderable": false,
        "className": 'dt-body-center',
        "width": "1%",
        "render": function(data, type, row, meta) {
          if (type === 'display')
            return '<input type="checkbox" />';

          return data;
        }
      },
      { "title": "Carat", "data": "Carat" },
      { "title": "Color", "data": "Color" },
      { "title": "Shape", "data": "Shape" },

      {
        "title": "Price",
        "data": "Price",
        "render": function(data, type, row, meta) {
          const moneyFormat = wNumb({
            decimals: 0,
            thousand: ',',
            prefix: '$'
          });

          return moneyFormat.to(data);
        }
      },

      { "title": "Cut", "data": "Cut" },
      { "title": "Symmetry", "data": "Symmetry" },
      { "title": "Report", "data": "Report" },
      { "title": "Clarity", "data": "Clarity" },
      { "title": "Polish", "data": "Polish" },
      {
        "title": "Link",
        "data": "link_view",
        "render": function(data, type, row, meta) {
          return '<a href="' + data + '">View</a>';
        }
      }
    ],

    "rowCallback": function (row, data, dataIndex) {
      var rowId = data['Product ID'];

      // if row ID is in the list of selected row IDs
      if ($.inArray(rowId, rowsSelected) != -1) {
        $(row).find('input[type="checkbox"]').prop('checked', true);
      } else {
        $(row).find('input[type="checkbox"]').prop('checked', false);
      }
    },

    "data": filtered
  });


  // recently viewed table
  window.recentlyViewedTable = $('#recently-viewed-table').DataTable({

    "ordering": false,
    "info": false,

    /* paging mode */
    "dom": 'tp',


    /* scroll mode */

    // "paging": false,
    // "scrollY": "500px",
    // "deferRender": true,
    // "scroller": true,

    "columns": [
      { 
        "title": "", 
        "data": "Product ID", 
        "defaultContent": "",
        "orderable": false,
        "className": 'dt-body-center',
        "width": "1%",
        "render": function(data, type, row, meta) {
          if (type === 'display')
            return '<input type="checkbox" />';

          return data;
        }
      },
      { "title": "Carat", "data": "Carat" },
      { "title": "Color", "data": "Color" },
      { "title": "Shape", "data": "Shape" },

      {
        "title": "Price",
        "data": "Price",
        "render": function(data, type, row, meta) {
          const moneyFormat = wNumb({
            decimals: 0,
            thousand: ',',
            prefix: '$'
          });

          return moneyFormat.to(data);
        }
      },

      { "title": "Cut", "data": "Cut" },
      { "title": "Symmetry", "data": "Symmetry" },
      { "title": "Report", "data": "Report" },
      { "title": "Clarity", "data": "Clarity" },
      { "title": "Polish", "data": "Polish" },
      {
        "title": "Link",
        "data": "link_view",
        "render": function(data, type, row, meta) {
          return '<a href="' + data + '">View</a>';
        }
      }
    ],

    "rowCallback": function (row, data, dataIndex) {
      var rowId = data['Product ID'];

      // if row ID is in the list of selected row IDs
      if ($.inArray(rowId, rowsSelected) != -1) {
        $(row).find('input[type="checkbox"]').prop('checked', true);
      } else {
        $(row).find('input[type="checkbox"]').prop('checked', false);
      }
    },

    "data": filtered
  });


  // comparison table
  window.comparisonTable = $('#comparison-table').DataTable({

    "ordering": false,
    "info": false,

    /* paging mode */
    "dom": 'tp',


    /* scroll mode */

    // "paging": false,
    // "scrollY": "500px",
    // "deferRender": true,
    // "scroller": true,

    "columns": [
      { 
        "title": "", 
        "data": "Product ID", 
        "defaultContent": "",
        "orderable": false,
        "className": 'dt-body-center',
        "width": "1%",
        "render": function(data, type, row, meta) {
          if (type === 'display')
            return '<input type="checkbox" />';

          return data;
        }
      },
      { "title": "Carat", "data": "Carat" },
      { "title": "Color", "data": "Color" },
      { "title": "Shape", "data": "Shape" },

      {
        "title": "Price",
        "data": "Price",
        "render": function(data, type, row, meta) {
          const moneyFormat = wNumb({
            decimals: 0,
            thousand: ',',
            prefix: '$'
          });

          return moneyFormat.to(data);
        }
      },

      { "title": "Cut", "data": "Cut" },
      { "title": "Symmetry", "data": "Symmetry" },
      { "title": "Report", "data": "Report" },
      { "title": "Clarity", "data": "Clarity" },
      { "title": "Polish", "data": "Polish" },
      {
        "title": "Link",
        "data": "link_view",
        "render": function(data, type, row, meta) {
          return '<a href="' + data + '">View</a>';
        }
      }
    ],

    "rowCallback": function (row, data, dataIndex) {
      var rowId = data['Product ID'];

      // if row ID is in the list of selected row IDs
      if ($.inArray(rowId, rowsSelected) != -1) {
        $(row).find('input[type="checkbox"]').prop('checked', true);
      } else {
        $(row).find('input[type="checkbox"]').prop('checked', false);
      }
    },

    "data": filtered
  });

  // initial filter
  masterFilterAndRender();


  // link event handler
  $('table tbody').on('click', 'tr a', function(e) {
    var table = $(this).closest('table').DataTable();
    var $row = $(this).closest('tr');
    var data = table.row($row).data();

    var rowId = data['Product ID'];

    var index = $.inArray(rowId, rowsViewed);
    if (index === -1) {
      rowsViewed.push(rowId);
      sessionStorage.setItem('rowsViewed', JSON.stringify(rowsViewed));

      recentlyViewedTable.draw();
      $('#recently-views').html(rowsViewed.length);
    }
  });

  
  // checkbox event handler
  $('#results-table tbody, #recently-viewed-table tbody, #comparison-table tbody').on('change', 'input[type="checkbox"]', function(e) {
    var $row = $(this).closest('tr');

    var table = $(this).closest('table').DataTable();
    // get row data
    var data = table.row($row).data();
    
    // get row ID
    var rowId = data['Product ID'];

    // check whether row ID is in the listof selected row IDs
    var index = $.inArray(rowId, rowsSelected);

    // if checkbox is checked and row ID is not in list of selected row IDs
    if (this.checked && index === -1) {
      rowsSelected.push(rowId);
    } else if (!this.checked && index !== -1) {
      rowsSelected.splice(index, 1);
    }
    sessionStorage.setItem('rowsSelected', JSON.stringify(rowsSelected));

    // redraw the tables
    resultsTable.draw();
    recentlyViewedTable.draw();
    comparisonTable.draw();    

    $('#comparison-views').html(rowsSelected.length);

    // prevent click event from propagating to parent
    e.stopPropagation();
  });
}


$(document).ready(function() {
  // slider
  initSliders();

  // table
  initTable();
});