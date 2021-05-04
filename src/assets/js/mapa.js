function initMapaGraph(data) {
  // grafico resultados
  //console.log(data);
  self = this;
  var max = data["VILLAMARIA"];
  for (const prop in data) {
    if (data[prop] > max) {
      max = data[prop];
    }
  }

  var height = 500;
  var width = 600;
  var centered;

  const zoom = d3.zoom().scaleExtent([1, 8]).on("zoom", self.zoomedMap);

  //mostrar los nombres de los municipios
  var tooltip = d3
    .select("body")
    .append("div")
    .attr("class", "tooltip")
    .style("opacity", 20);

  //poner los colores, de acuerdo a una escala
  var color = d3.scale.linear().domain([0, max]).range(["#9cc129", "#00782b"]);

  var projection = d3.geo
    .mercator()
    .scale(20000)
    // Center the Map in Colombia
    .center([-75.4, 5.4])
    .translate([width / 2, height / 2]);

  var path = d3.geo.path().projection(projection);

  // Set svg width & height
  var svg = d3.select("svg#mapa").attr("height", height).attr("width", width);
  /*.call(d3.zoom().on("zoom", function () {
        svg.attr("transform", d3.event.transform)
     }))*/

  svg.call(zoom);
  svg.on(".zoom", null);

  // Add background
  svg
    .append("rect")
    .attr("class", "background")
    .attr("height", height)
    .attr("width", width);

  svg.call(zoom);

  var g = svg.append("g");

  var effectLayer = g.append("g").classed("effect-layer", true);

  var mapLayer = g.append("g").classed("map-layer", true);

  var dummyText = g
    .append("text")
    .classed("dummy-text", true)
    .attr("x", 10)
    .attr("y", 30)
    .style("opacity", 0);

  var bigText = g
    .append("text")
    .classed("big-text", true)
    .attr("x", 20)
    .attr("y", 45);

  // Load map data
  //d3.json('map2.geojson', function(error, mapData) {
  //d3.json('Colombia.json', function(error, mapData) {

  d3.json("assets/js/map/map2.geojson", function (error, mapData) {
    var features = mapData;

    /** Códigos únicos de los municipios CHEC. Se construyó con base en el código del departamento y código del municipio.
     * `17 - Caldas`
     * `66 - Risaralda`
     */
    const municipiosChec = [
      "171",
      "1713",
      "1742",
      "1750",
      "1788",
      "17174",
      "17272",
      "17380",
      "17388",
      "17433",
      "17442",
      "17444",
      "17446",
      "17486",
      "17495",
      "17513",
      "17524",
      "17541",
      "17614",
      "17616",
      "17653",
      "17662",
      "17665",
      "17777",
      "17867",
      "17873",
      "17877",
      "6645",
      "6675",
      "6688",
      "66170",
      "66318",
      "66383",
      "66400",
      "66440",
      "66456",
      "66572",
      "66594",
      "66682",
      "66687",
    ];

    // Draw each province as a path
    mapLayer
      .selectAll("path")
      .data(
        features.filter((d) => municipiosChec.includes(d.properties.codDepMun))
      )
      .enter()
      .append("path")
      .attr("d", path)
      .attr("fill", function (d) {
        //var valor = parseInt((Math.floor(Math.random() * 10) + 0));
        return color(data[d.properties.name]);
      })
      .attr("text", function (d) {
        return data[d.properties.name];
      })
      .attr("vector-effect", "non-scaling-stroke")
      .on("mouseover", function (d) {
        tooltip.transition().duration(200).style("opacity", 0.9);
        tooltip
          .html(d.properties.name)
          .style("left", d3.event.pageX + "px")
          .style("top", d3.event.pageY - 28 + "px");
      })
      .on("mouseout", function (d) {
        tooltip.transition().duration(200).style("opacity", 0.0);
        tooltip
          .html(d.properties.name)
          .style("left", d3.event.pageX + "px")
          .style("top", d3.event.pageY - 28 + "px");
      });
    mapLayer
      .selectAll("text")
      .data(features)
      .enter()
      .append("svg:text")
      .text(function (d) {
        return data[d.properties.name];
      })
      .attr("x", function (d) {
        return path.centroid(d)[0];
      })
      .attr("y", function (d) {
        return path.centroid(d)[1];
      })
      .style("fill", "white")
      .attr("text-anchor", "middle")
      .attr("font-size", "7pt")
      .attr("color", "white");

    /*.style('fill', fillFn)
        .on('mouseover', mouseover)
        .on('mouseout', mouseout)
        .on('click', clicked);*/
  });
}
