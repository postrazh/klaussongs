
// convertion
filtered.forEach(
    r => {
        r.Carat = parseFloat(r.Carat);
        r.Price = parseFloat(r.Price.replace(/[$,]/g, ""));
    }
);

// tabs
$("#tabs").tabs({
    event: "mouseover"
});

// table filter
var filterFunctions = { };


// functions
var initSliders = function() {
  // find min/max of price, carat
  let minPrice = Infinity;
  let maxPrice = 0;
  let minCarat = 0;
  let maxCarat = 0;

  filtered.forEach(
      r => {
          minPrice = (r.Price < minPrice) ? r.Price : minPrice;
          maxPrice = (r.Price > maxPrice) ? r.Price : maxPrice;
          maxCarat = (r.Carat > maxCarat) ? r.Carat : maxCarat;
      }
  );

  minPrice = Math.floor((minPrice / 100)) * 100 - 100;
  maxPrice = Math.ceil((maxPrice / 100)) * 100 + 1000;

  // price slider
  let priceSliderUI = noUiSlider.create(document.getElementById('price'), {
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
      start: [maxCarat],
      connect: false,
      animate: false,
      range: {
          'min': 0,
          'max': maxCarat
      },
      tooltips: true
  });


  // color slider
  let colorSymbol = ['J', 'I', 'H', 'G', 'F', 'E', 'D'];

  let colorsliderUI = noUiSlider.create(document.getElementById('color'), {
      start: [colorSymbol[5]],
      step: 1,
      range: {
          'min': [0],
          'max': [6]
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
      start: [4],
      step: 1,
      range: {
          'min': [0],
          'max': [7]
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
      start: [polishSymbol[2]],
      step: 1,
      range: {
          'min': [0],
          'max': [2]
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
      start: [reportSymbol[1]],
      step: 1,
      range: {
          'min': [0],
          'max': [2]
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
      start: [symmetrySymbol[2]],
      step: 1,
      range: {
          'min': [0],
          'max': [2]
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
          // console.log('priceSliderUI', r)
          limitPrice[0] = parseFloat(limitPrice[0].replace(cleanPrice, ''));
          limitPrice[1] = parseFloat(limitPrice[1].replace(cleanPrice, ''));
          // function returns true for conditions that require rejecting data
          filterFunctions['price'] = (data) => data.Price < limitPrice[0] || data.Price > limitPrice[1];
          masterFilterAndRender();
      }
  );

  caratSliderUI.on("change",
      function(r) {
          // console.log('caratsliderUI', r[0]);
          const caratMaxLimit = parseFloat(r[0]);
          // function returns true for conditions that require rejecting data
          filterFunctions['carat'] = (data) => data.Carat > caratMaxLimit;
          masterFilterAndRender();
      }
  );


  colorsliderUI.on("change",
      function(r) {
          const value = r[0];
          // function returns true for conditions that require rejecting data
          filterFunctions['Color'] = (data) => data.Color != value;
          masterFilterAndRender();
      }
  );

  claritySliderUI.on("change",
      function(r) {
          const value = r[0];
          // function returns true for conditions that require rejecting data
          filterFunctions['Clarity'] = (data) => data.Clarity != value;
          masterFilterAndRender();
      }
  );


  polishSliderUI.on("change",
      function(r) {
          const value = r[0];
          // function returns true for conditions that require rejecting data
          filterFunctions['Polish'] = (data) => data.Polish != value;
          masterFilterAndRender();
      }
  );

  reportSliderUI.on("change",
      function(r) {
          const value = r[0];
          // function returns true for conditions that require rejecting data
          filterFunctions['Report'] = (data) => data.Report != value;
          masterFilterAndRender();
      }
  );

  symmetrySliderUI.on("change",
      function(r) {
          const value = r[0];
          // function returns true for conditions that require rejecting data
          filterFunctions['Symmetry'] = (data) => data.Symmetry != value;
          masterFilterAndRender();
      }
  );
};


var masterFilterAndRender = function () {
    // set all filter sub functions and render table
    console.warn('masterFilterAndRender');
    let tbody = $("#simpleTable tbody").empty();
    filtered.forEach(function(data) {
        let bool = null;
        const keys = Object.keys(filterFunctions);
        for (let i = 0, max = keys.length; i < max; i++) {
            if (filterFunctions[keys[i]] !== null && filterFunctions[keys[i]](data)) {
                bool = keys[i];
                // console.log(bool, data.Price, data.Carat);
                break;
            }
        }
        if (bool !== null) return; // one of the filters rejects this data/row
        let row = $("<tr/>").addClass("showMyRow");
        config.forEach(function(entry) {
            var cell = $("<td/>"),
                value = data[entry.attr];
            cell.html(entry.renderer(value));
            row.append(cell);
        });
        tbody.append(row);
    });
}


$(document).ready(function() {
  // slider
   initSliders();

  // debugger;

  // table
  $('#example').dataTable( {

      "ordering": false,
      "info":     false,
      "searching": false,

      /* paging mode */
      "dom": '<f<t>ip>',


      /* scroll mode */

      // "paging": false,
      // "scrollY":        "500px",
      // deferRender:    true,
      // scroller:       true,
      
      columnDefs: [ {
              orderable: false,
              className: 'select-checkbox',
              targets:   0
          } ],

      select: {
              style:    'multi',
              selector: 'td:first-child'
          },

      "columns": [
          { "title": "", "data": null, "defaultContent": "" },
          { "title": "Carat", "data": "Carat" },
          { "title": "Color", "data": "Color" },
          { "title": "Shape", "data": "Shape" },
          { "title": "Price", "data": "Price" },
          { "title": "Cut", "data": "Cut" },
          { "title": "Symmetry", "data": "Symmetry" },
          { "title": "Report", "data": "Report" },
          { "title": "Clarity", "data": "Clarity" },
          { "title": "Polish", "data": "Polish" },
          { "title": "Link", "data": "link_view" },
      ],
      
      "data": filtered
  } );
  
});