

headArr = Object.getOwnPropertyNames(filtered[0]);
var keys = Object.keys(filtered[0]);
var last = keys[keys.length - 1];

let table = document.querySelector("#simpleTable");

let shapePrincess = document.getElementById("shape-princess");
let shapeRadiant = document.getElementById("shape-radiant");
let shapeHeart = document.getElementById("shape-heart");
let shapeAsscher = document.getElementById("shape-asscher");
let shapeOval = document.getElementById("shape-oval");
let shapeCushion = document.getElementById("shape-cushion");
let shapeMarquise = document.getElementById("shape-marquise");
let shapeEmerald = document.getElementById("shape-emerald");
let shapePear = document.getElementById("shape-pear");

// adding selected shape style 
// var shpaeButton = document.getElementsByClassName("shape-btn");
// for (var i = 0; i < shpaeButton.length; i++) {
//     shpaeButton[i].addEventListener('click', function() { 
//       this.classList.toggle("selectedShape"); 
//   }, false);}


let minPrice = Infinity;
let maxPrice = 0;
let minCarat = 0;
let maxCarat = 0;


// comparison
filtered.forEach(
  r => {
    r.Carat = parseFloat(r.Carat);
    r.Price = parseFloat(r.Price.replace(/[$,]/g, ""));
    minPrice = (r.Price < minPrice) ? r.Price : minPrice;
    maxPrice = (r.Price > maxPrice) ? r.Price : maxPrice;
    maxCarat = (r.Carat > maxCarat) ? r.Carat : maxCarat;
  }

);

minPrice = Math.floor((minPrice / 100)) * 100 - 100;
maxPrice = Math.ceil((maxPrice / 100)) * 100 + 1000;



// Table generator
$(document).ready(function () {

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
  // table config file and body
  var renderer = {
    text: function (data) {
      return data;
    },
    link: function (data) {
      return '<a href="' + data + '">View</a>';
    },
    price: function (data) {
      return moneyFormat.to(data);
    }
  };

  var config = [
    { attr: "Carat", renderer: renderer.text },
    { attr: "Color", renderer: renderer.text },
    { attr: "Shape", renderer: renderer.text },
    { attr: "Price", renderer: renderer.price },
    { attr: "Cut", renderer: renderer.text },
    { attr: "Symmetry", renderer: renderer.text },
    { attr: "Report", renderer: renderer.text },
    { attr: "Clarity", renderer: renderer.text },
    { attr: "Polish", renderer: renderer.text },
    { attr: "link_view", renderer: renderer.link }
  ];


  // Initial Render:  Loadloop through original data, display all
  let tbody = $("<tbody/>");
  filtered.forEach(function (data) {
    let row = $("<tr/>");
    row.addClass('showMyRow');
    // attach config file and loop through 
    config.forEach(function (entry) {
      let cell = $("<td/>");
      cell.html(entry.renderer(data[entry.attr]));
      row.append(cell);
    });

    tbody.append(row);
  });
  $("#simpleTable").append(tbody);

  const filterFunctions = {
    shape: null,
    price: null,
    carat: null
  };
  let shapeActive = null;


  $('.shape-btn').on('click', function (r) {
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
    function (r) {
      let limitPrice = r;
      // console.log('priceSliderUI', r)
      limitPrice[0] = parseFloat(limitPrice[0].replace(cleanPrice,''));
      limitPrice[1] = parseFloat(limitPrice[1].replace(cleanPrice,''));
      // function returns true for conditions that require rejecting data
      filterFunctions['price'] = (data) => data.Price < limitPrice[0] || data.Price > limitPrice[1];
      masterFilterAndRender();
    }
  );

  caratSliderUI.on("change",
    function (r) {
      // console.log('caratsliderUI', r[0]);
      const caratMaxLimit = parseFloat(r[0]);
      // function returns true for conditions that require rejecting data
      filterFunctions['carat'] = (data) => data.Carat > caratMaxLimit;
      masterFilterAndRender();
    }
  );


  colorsliderUI.on("change",
    function (r) {
      const value = r[0];
      // function returns true for conditions that require rejecting data
      filterFunctions['Color'] = (data) => data.Color != value;
      masterFilterAndRender();
    }
  );

  claritySliderUI.on("change",
    function (r) {
      const value = r[0];
      // function returns true for conditions that require rejecting data
      filterFunctions['Clarity'] = (data) => data.Clarity != value;
      masterFilterAndRender();
    }
  );


  polishSliderUI.on("change",
    function (r) {
      const value = r[0];
      // function returns true for conditions that require rejecting data
      filterFunctions['Polish'] = (data) => data.Polish != value;
      masterFilterAndRender();
    }
  );

  reportSliderUI.on("change",
    function (r) {
      const value = r[0];
      // function returns true for conditions that require rejecting data
      filterFunctions['Report'] = (data) => data.Report != value;
      masterFilterAndRender();
    }
  );

  symmetrySliderUI.on("change",
    function (r) {
      const value = r[0];
      // function returns true for conditions that require rejecting data
      filterFunctions['Symmetry'] = (data) => data.Symmetry != value;
      masterFilterAndRender();
    }
  );

  function masterFilterAndRender() {
    // set all filter sub functions and render table
    console.warn('masterFilterAndRender');
    let tbody = $("#simpleTable tbody").empty();
    filtered.forEach(function (data) {
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
      config.forEach(function (entry) {
        var cell = $("<td/>"),
          value = data[entry.attr];
        cell.html(entry.renderer(value));
        row.append(cell);
      });
      tbody.append(row);
    });
  }

  // end of document ready
});









$(function () { $("#tabs").tabs({ event: "mouseover" }); }); $("table").stupidtable();

let slider = document.getElementById('price');
let node = document.createElement('div');
let priceSliderUI = noUiSlider.create(slider, {
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



let caratSlider = document.getElementById('carat');

let caratSliderUI = noUiSlider.create(caratSlider, {
  start: [maxCarat],
  connect: false,
  animate: false,
  range: {
    'min': 0,
    'max': maxCarat
  },
  tooltips: true
});



let colorSlider = document.getElementById('color');
let colorsDiamond = "J,I,H,G,F,E,D";
let toSymbol = colorsDiamond.split(",");

let colorsliderUI = noUiSlider.create(colorSlider, {
            start: [toSymbol[5]],
            step: 1,
            range: {
                'min': [0],
                'max': [6]
            },
            format: {
                // 'to' the formatted value. Receives a number.
                to: function (value) {
                    return toSymbol[parseInt(value)];
                },
                // 'from' the formatted value.
                // Receives a string, should return a number.
                from: function (value) {  
                    let index = toSymbol.findIndex((v) => v === value);
                    return index;
                }
            },
            tooltips: false,
            pips: {
                mode: 'steps',
                density: 10,
                format: {
                    // 'to' the formatted value. Receives a number.
                    to: function (value) {
                        return toSymbol[value];
                    },
                    // 'from' the formatted value.
                    // Receives a string, should return a number.
                    from: function (value) {   
                        let index = toSymbol.findIndex((v) => v === value);
                        return index;
                    }
                },
            }
        });





let claritySlider = document.getElementById('clarity');
let clarityDiamond = "SI2,SI1,VS2,VS1,VVS2,VVS1,IF,FL";
let claritySymbol = clarityDiamond.split(",");

let claritySliderUI = noUiSlider.create(claritySlider, {
            start: [4],
            step: 1,
            range: {
                'min': [0],
                'max': [7]
            },
            format: {
                // 'to' the formatted value. Receives a number.
                to: function (value) {
                    return claritySymbol[value];
                },
                // 'from' the formatted value.
                // Receives a string, should return a number.
                from: function (value) {  
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
                    to: function (value) {
                        return claritySymbol[value];
                    },
                    // 'from' the formatted value.
                    // Receives a string, should return a number.
                    from: function (value) {   
                        let index = claritySymbol.findIndex((v) => v === value);
                        return index;
                    }
                },
            }
        });


let polishSlider = document.getElementById('polish');
let polishDiamond = "Good,Very Good,Excellent";
let polishSymbol = polishDiamond.split(",");

let polishSliderUI = noUiSlider.create(polishSlider, {
            start: [polishSymbol[2]],
            step: 1,
            range: {
                'min': [0],
                'max': [2]
            },
            format: {
                // 'to' the formatted value. Receives a number.
                to: function (value) {
                    return polishSymbol[value];
                },
                // 'from' the formatted value.
                // Receives a string, should return a number.
                from: function (value) {  
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
                    to: function (value) {
                        return polishSymbol[value];
                    },
                    // 'from' the formatted value.
                    // Receives a string, should return a number.
                    from: function (value) {   
                        let index = polishSymbol.findIndex((v) => v === value);
                        return index;
                    }
                },
            }
        });





let reportSlider = document.getElementById('report');
let reportDiamond = "GIA,IGI,HRD";
let reportSymbol = reportDiamond.split(",");

let reportSliderUI = noUiSlider.create(reportSlider, {
            start: [reportSymbol[1]],
            step: 1,
            range: {
                'min': [0],
                'max': [2]
            },
            format: {
                // 'to' the formatted value. Receives a number.
                to: function (value) {
                    return reportSymbol[value];
                },
                // 'from' the formatted value.
                // Receives a string, should return a number.
                from: function (value) {  
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
                    to: function (value) {
                        return reportSymbol[value];
                    },
                    // 'from' the formatted value.
                    // Receives a string, should return a number.
                    from: function (value) {   
                        let index = reportSymbol.findIndex((v) => v === value);
                        return index;
                    }
                },
            }
        });


let symmetrySlider = document.getElementById('symmetry');
let symmetryDiamond = "Good,Very Good,Excellent";
let symmetrySymbol = symmetryDiamond.split(",");

let symmetrySliderUI = noUiSlider.create(symmetrySlider, {
            start: [symmetrySymbol[2]],
            step: 1,
            range: {
                'min': [0],
                'max': [2]
            },
            format: {
                // 'to' the formatted value. Receives a number.
                to: function (value) {
                    return symmetrySymbol[value];
                },
                // 'from' the formatted value.
                // Receives a string, should return a number.
                from: function (value) {  
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
                    to: function (value) {
                        return symmetrySymbol[value];
                    },
                    // 'from' the formatted value.
                    // Receives a string, should return a number.
                    from: function (value) {   
                        let index = symmetrySymbol.findIndex((v) => v === value);
                        return index;
                    }
                },
            }
        });

