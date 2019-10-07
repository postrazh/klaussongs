
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

// table config file and body
var renderer = {
    text: function(data) {
        return data;
    },
    link: function(data) {
        return '<a href="' + data + '">View</a>';
    },
    price: function(data) {
        return moneyFormat.to(data);
    }
};

var config = [{
        attr: "Carat",
        renderer: renderer.text
    },
    {
        attr: "Color",
        renderer: renderer.text
    },
    {
        attr: "Shape",
        renderer: renderer.text
    },
    {
        attr: "Price",
        renderer: renderer.price
    },
    {
        attr: "Cut",
        renderer: renderer.text
    },
    {
        attr: "Symmetry",
        renderer: renderer.text
    },
    {
        attr: "Report",
        renderer: renderer.text
    },
    {
        attr: "Clarity",
        renderer: renderer.text
    },
    {
        attr: "Polish",
        renderer: renderer.text
    },
    {
        attr: "link_view",
        renderer: renderer.link
    }
];

var filterFunctions = { };

// functions
let table = document.querySelector("#simpleTable");
$("table").stupidtable();

// table head
var header = table.createTHead();
var row = header.insertRow(0);
row.classList.add("headRow")
var headCell0 = row.insertCell(0);
headCell0.innerHTML = "Carat";
var headCell1 = row.insertCell(1);
headCell1.innerHTML = "Color";
var headCell2 = row.insertCell(2);
headCell2.innerHTML = "Shape";
var headCell33 = row.insertCell(3);
headCell33.innerHTML = "Price";
var headCell3 = row.insertCell(4);
headCell3.innerHTML = "Cut";
var headCell4 = row.insertCell(5);
headCell4.innerHTML = "Symmetry";
var headCell5 = row.insertCell(6);
headCell5.innerHTML = "Report";
var headCell5 = row.insertCell(7);
headCell5.innerHTML = "Clarity";
var headCell5 = row.insertCell(8);
headCell5.innerHTML = "Polish";
var headCell5 = row.insertCell(9);
headCell5.innerHTML = "Link";

const moneyFormat = wNumb({
    decimals: 0,
    thousand: ',',
    prefix: '$'
});

// Initial Render:  Loadloop through original data, display all
let tbody = $("<tbody/>");
filtered.forEach(function(data) {
    let row = $("<tr/>");
    row.addClass('showMyRow');
    // attach config file and loop through 
    config.forEach(function(entry) {
        let cell = $("<td/>");
        cell.html(entry.renderer(data[entry.attr]));
        row.append(cell);
    });

    tbody.append(row);
});

$("#simpleTable").append(tbody);


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
  initSliders();
});