 function initHeatMap(datos) {
  // grafico heat map

  document.getElementById('consultasHoraDia').innerHTML = '';
  var ctx = document.getElementById("consultasHoraDia");

  function generateData(count, horas) {
    var i = 0;
    var series = [];
    while (i < count) {
      var x;
      if(i < 10){
        
        x = "0" + (i).toString();
      } else {
        x = (i).toString();
      }
      var y = horas[x];

      series.push({
        x: x,
        y: y
      });
      i++;
    }
    return series;
  }

  var data = [{
      name: 'Lunes',
      data: generateData(24, datos['1'])
    },
    {
      name: 'Martes',
      data: generateData(24, datos['2'])
    },
    {
      name: 'Miercoles',
      data: generateData(24, datos['3'])
    },
    {
      name: 'Jueves',
      data: generateData(24, datos['4'])
    },
    {
      name: 'Viernes',
      data: generateData(24, datos['5'])
    },
    {
      name: 'Sábado',
      data: generateData(24, datos['6'])
    },
    {
      name: 'Domingo',
      data: generateData(24, datos['0'])
    }
  ]

  data.reverse()

  //var colors = ["#F27036", "#6A6E94", "#18D8D8",'#46AF78', '#A93F55', '#33A1FD', '#EA0000']
  var colors = ["#00D8C3", "#FFCF00", "#07CA02",'#00D5B5', '#0079D3', '#B1C400', '#A80E2E']

  colors.reverse()


  var options = {
    chart: {
      height: 450,
      type: 'heatmap',
    },
    dataLabels: {
      enabled: false
    },
    colors: colors,
    series: data,
    xaxis: {
      type: 'category',
      categories: ['00:00', '01:00', '02:00', '03:00', '04:00', '05:00', '06:00', '07:00', '08:00', '09:00', '10:00', '11:00', '12:00',
        '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00', '22:00', '23:00'
      ]
    },
    title: {
      text: ''
    },
    grid: {
      padding: {
        right: 20
      }
    }
  }

  var chart = new ApexCharts(
    ctx,
    options
  );

  chart.render();


  /* Highcharts.chart(ctx, {

    chart: {
      type: 'heatmap',
      marginTop: 40,
      marginBottom: 80,
      plotBorderWidth: 1
    },
    title: {
      text: ''
    },
  
    xAxis: {
      //categories: ['00am', '01am', '02am', '03am', '04am', '05am', '06am', '07am', '08am', '09am']
      categories: ['01am', '02am', '03am', '04am', '05am', '06am', '07am', '08am', '09am', '10am', '11am', '12m', 
      '01pm', '02pm', '03pm', '04pm', '05pm', '06pm', '07pm', '08pm', '09pm', '10pm', '11pm', '12pm']
    },
  
    yAxis: {
      categories: ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'],
      title: null
    },
  
    colorAxis: {
      min: 0,
      minColor: '#FFFFFF',
      maxColor: Highcharts.getOptions().colors[0]
    },
  
    legend: {
      align: 'right',
      layout: 'vertical',
      margin: 0,
      verticalAlign: 'top',
      y: 25,
      symbolHeight: 280
    },
  
    tooltip: {
      formatter: function () {
        return '<b>' + this.series.xAxis.categories[this.point.x] + '</b> sold <br><b>' +
          this.point.value + '</b> items on <br><b>' + this.series.yAxis.categories[this.point.y] + '</b>';
      }
    },
  
    series: [{
      name: 'Sales per employee',
      borderWidth: 1,
      data: [[0, 0, 10], [0, 1, 19], [0, 2, 8], [0, 3, 24], [0, 4, 67], [0, 3, 24], [0, 4, 67], 
      [1, 0, 92], [1, 1, 58], [1, 2, 78], [1, 3, 117], [1, 4, 48], [1, 3, 117], [1, 4, 48], 
      [2, 0, 35], [2, 1, 15], [2, 2, 123], [2, 3, 64], [2, 4, 52], [2, 3, 64], [2, 4, 52], 
      [3, 0, 72], [3, 1, 132], [3, 2, 114], [3, 3, 19], [3, 4, 16], [3, 3, 19], [3, 4, 16], 
      [4, 0, 38], [4, 1, 5], [4, 2, 8], [4, 3, 117], [4, 4, 115], [4, 3, 117], [4, 4, 115], 
      [5, 0, 88], [5, 1, 32], [5, 2, 12], [5, 3, 6], [5, 4, 120], [5, 3, 6], [5, 4, 120], 
      [6, 0, 13], [6, 1, 44], [6, 2, 88], [6, 3, 98], [6, 4, 96], [6, 3, 98], [6, 4, 96], 
      [7, 0, 31], [7, 1, 1], [7, 2, 82], [7, 3, 32], [7, 4, 30], [7, 3, 32], [7, 4, 30], 
      [8, 0, 85], [8, 1, 97], [8, 2, 123], [8, 3, 64], [8, 4, 84], [8, 3, 64], [8, 4, 84], 
      [9, 0, 47], [9, 1, 114], [9, 2, 31], [9, 3, 48], [9, 4, 91], [9, 3, 48], [9, 4, 91],
      [10, 0, 47], [10, 1, 114], [10, 2, 31], [10, 3, 48], [10, 4, 91], [10, 3, 48], [10, 4, 91],
      [11, 0, 47], [11, 1, 114], [11, 2, 31], [11, 3, 48], [11, 4, 91], [11, 3, 48], [11, 4, 91],
      [12, 0, 47], [12, 1, 114], [12, 2, 31], [12, 3, 48], [12, 4, 91], [12, 3, 48], [12, 4, 91],
      [13, 0, 47], [13, 1, 114], [13, 2, 31], [13, 3, 48], [13, 4, 91], [13, 3, 48], [13, 4, 91],
      [15, 0, 47], [15, 1, 114], [15, 2, 31], [15, 3, 48], [15, 4, 91], [15, 3, 48], [15, 4, 91],
      [16, 0, 47], [16, 1, 114], [16, 2, 31], [16, 3, 48], [16, 4, 91], [16, 3, 48], [16, 4, 91],
      [17, 0, 47], [17, 1, 114], [17, 2, 31], [17, 3, 48], [17, 4, 91], [17, 3, 48], [17, 4, 91],
      [18, 0, 47], [18, 1, 114], [18, 2, 31], [18, 3, 48], [18, 4, 91], [18, 3, 48], [18, 4, 91],
      [19, 0, 47], [19, 1, 114], [19, 2, 31], [19, 3, 48], [19, 4, 91], [19, 3, 48], [19, 4, 91],
      [20, 0, 47], [20, 1, 114], [20, 2, 31], [20, 3, 48], [20, 4, 91], [20, 3, 48], [20, 4, 91],
      [21, 0, 47], [21, 1, 114], [21, 2, 31], [21, 3, 48], [21, 4, 91], [21, 3, 48], [21, 4, 91],
      [22, 0, 47], [22, 1, 114], [22, 2, 31], [22, 3, 48], [22, 4, 91], [22, 3, 48], [22, 4, 91],
      [23, 0, 47], [23, 1, 114], [23, 2, 31], [23, 3, 48], [23, 4, 91], [23, 3, 48], [23, 4, 91],
      [24, 0, 47], [24, 1, 114], [24, 2, 31], [24, 3, 48], [24, 4, 91], [24, 3, 48], [24, 4, 91]
    ],
      dataLabels: {
        enabled: true,
        color: '#000000'
      }
    }]
  
  }); */

}