function initSankeyGraph(datos) {
  // grafico Sankey

  var ctx = document.getElementById("sankey");
  Highcharts.chart(ctx, {

    title: {
      text: ''
    },
    colors: ['#00A696', '#005BA0', '#5FA039', '#F5CF2E', '#F19800',
      '#93A400', '#049F00', '#00A38B', '#139F00'
    ],

    series: [{
      keys: ['from', 'to', 'weight'],
      data: [

        ['Cuenta', 'Si Encontró', datos.SiCuenta],
        ['Cuenta', 'No Encontró', datos.NoCuenta],
        ['Cédula', 'Si Encontró', datos.SiCedula],
        ['Cédula', 'No Encontró', datos.NoCedula],
        ['Nit', 'Si Encontró', datos.SiNit],
        ['Nit', 'No Encontró', datos.NoNit],
        ['Nombre', 'Si Encontró', datos.SiNombre],
        ['Nombre', 'No Encontró', datos.NoNombre],
        ['Dirección', 'Si Encontró', datos.SiDireccion],
        ['Dirección', 'No Encontró', datos.NoDireccion],
        ['Teléfono', 'Si Encontró', datos.SiTelefono],
        ['Teléfono', 'No Encontró', datos.NoTelefono],

        ['Si Encontró', 'Programadas', datos.Programada],
        ['Si Encontró', 'Efectivas', datos.Efectiva],
        ['Si Encontró', 'Daños', datos.Nodo],
        ['Si Encontró', 'Sin Suspensión', datos.SinIndis],

        ['Sin Suspensión', 'Si Reportaron', datos.SiReporte],
        ['Sin Suspensión', 'No Reportaron', ((datos.SinIndis) - datos.SiReporte)],
      ],
      type: 'sankey',
      name: ''
    }]

  });

  /* var data = {
    type: "sankey",
    orientation: "h",
    node: {
      pad: 15,
      thickness: 30,
      line: {
        color: "#E0EDF6",
        width: 2
      },
     label: ["Cuenta", "Cédula", "Nit", "Nombre", "Dirección", "Teléfono", //0-5
            "Si Encontró", "No Encontró", //6-7
            "Programadas", "Efectivas", "Daños", "Sin Suspensión", //8-9-10-11
            "Si Reportaron", "No Reportaron"], //12-13
     color: ["#7FC1EE", "#7FC1EE", "#7FC1EE", "#7FC1EE", "#7FC1EE", "#7FC1EE"]
        },
    
    link: {
      source: [0,0,1,1,2,2,3,3,4,4,5,5,6,6,6,6,11,11],
      target: [6,7,6,7,6,7,6,7,6,7,6,7,8,9,10,11,12,13],
      value:  [datos.SiCuenta,datos.NoCuenta,datos.SiCedula,datos.NoCedula,datos.SiNit,datos.NoNit,
        datos.SiNombre,datos.NoNombre,datos.SiDireccion,datos.NoDireccion,datos.SiTelefono,datos.NoTelefono,
        datos.Programada,datos.Efectiva,datos.Nodo,datos.SinIndis,
        datos.SiReporte,(datos.SinIndis - datos.SiReporte)]
    }
  }
  var data = [data]
  Plotly.react('sankey', data, {showSendToCloud:true}); */
}