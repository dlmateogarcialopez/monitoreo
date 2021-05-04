function fn_drawMap(data) {
  self = this;
  var data = data;
  //balboa belen de humbria mistrato

  let tamanoMapa = document.getElementById('mapa_caldas');
  var width = tamanoMapa.offsetWidth;
  var height = tamanoMapa.offsetHeight;

  const zoom = d3.zoom()
    .scaleExtent([1, 8])
    .on('zoom', self.zoomedMap);

  var color = d3.scaleLinear()
    .domain([0, 100])
    .range(["#A1E5A0", "#09008D"]);


  // Set svg width & height
  var svg = d3.select('#mapa_caldas').append("svg")
    .attr('width', width)
    .attr('height', height);

  svg.call(zoom);
  g_map = svg.append('g');

  var mapLayer = g_map.classed('map-layer', true);

  // Load map data
  //d3.json('map2.geojson', function(error, mapData) {
  //d3.json('Colombia.json', function(error, mapData) {

  d3.json('/assets/js/map/caldas.geojson').then(function (mapData) {
    console.log(mapData)
    var features = mapData;
    console.log(features);

    var projection = self.fn_fitProjection(d3.geoMercator(), features, [[0, 0], [width, height]], true)
    var path = d3.geoPath()
      .projection(projection);

    var elementosMapa = g_map.selectAll('path')
      .data(features.filter(function (d) { return data[d.properties.name] != undefined; }))

    var grupoMapa = elementosMapa.enter().append('g');

    var MapaPath = grupoMapa.append('path').classed('map-layer', true)
      .attr('d', path)
      .attr('vector-effect', 'non-scaling-stroke')
      .attr("fill", function (d) {
        //var valor = parseInt((Math.floor(Math.random() * 10) + 0));
        return self.oSentimiento[data[d.properties.name].sentimiento];
        //return color(data[d.properties.name]);
      })
      .attr("text", function (d) {
        return data[d.properties.name];
      })
      .attr('vector-effect', 'non-scaling-stroke')
      .on("mouseover", function (d) {
        tooltip.transition()
          .duration(200)
          .style("opacity", .9);
        tooltip.html(d.properties.name)
          .style("left", (d3.event.pageX) + "px")
          .style("top", (d3.event.pageY - 28) + "px");
      })
      .on("mouseout", function (d) {
        tooltip.transition()
          .duration(200)
          .style("opacity", .0);
        tooltip.html(d.properties.name)
          .style("left", (d3.event.pageX) + "px")
          .style("top", (d3.event.pageY - 28) + "px");
      });

  });
}