function tarjetaPagWeb() {
  domtoimage
    .toBlob(document.getElementById("tarjetaPagWeb"))
    .then(function (blob) {
      window.saveAs(blob, "tarjetaPagWeb.png");
    });
}

function fallbacks() {
  domtoimage.toBlob(document.getElementById("fallbacks")).then(function (blob) {
    window.saveAs(blob, "fallbacks.png");
  });
}

function submenu() {
  domtoimage.toBlob(document.getElementById("submenu")).then(function (blob) {
    window.saveAs(blob, "submenu.png");
  });
}

function tarjetaTelegram() {
  domtoimage
    .toBlob(document.getElementById("tarjetaTelegram"))
    .then(function (blob) {
      window.saveAs(blob, "tarjetaTelegram.png");
    });
}

function calificaciones() {
  domtoimage
    .toBlob(document.getElementById("calificaciones"))
    .then(function (blob) {
      window.saveAs(blob, "calificaciones.png");
    });
}

function accesoMenu() {
  domtoimage
    .toBlob(document.getElementById("accesoMenu"))
    .then(function (blob) {
      window.saveAs(blob, "accesoMenu.png");
    });
}

function indicadoresInteraccion(id) {
  domtoimage.toBlob(document.getElementById("indicadoresInteraccion")).then(function (blob) {
    window.saveAs(blob, "indicadoresInteraccion.png");
  });
}

function topConsultas(id) {
  domtoimage.toBlob(document.getElementById("topConsultas")).then(function (blob) {
    window.saveAs(blob, "topConsultas.png");
  });
}

function accesoMenu2() {
  domtoimage
    .toBlob(document.getElementById("accesoMenu2"))
    .then(function (blob) {
      window.saveAs(blob, "accesoMenu2.png");
    });
}

function faltaDeEnergiaGraficas(id) {
  domtoimage.toBlob(document.getElementById(id)).then(function (blob) {
    window.saveAs(blob, id + "png");
  });
}

function copiaFacturaGraficas(id) {
  domtoimage.toBlob(document.getElementById(id)).then(function (blob) {
    window.saveAs(blob, id + "png");
  });
}

function dinp(id) {
  domtoimage.toBlob(document.getElementById(id)).then(function (blob) {
    window.saveAs(blob, id + "png");
  });
}

function general(id) {
  domtoimage.toBlob(document.getElementById(id)).then(function (blob) {
    window.saveAs(blob, id + "png");
  });
}

function SegimientoGrafica(id) {
  domtoimage.toBlob(document.getElementById(id)).then(function (blob) {
    window.saveAs(blob, id + "png");
  });
}

function contact(id) {
  domtoimage.toBlob(document.getElementById(id)).then(function (blob) {
    window.saveAs(blob, id + "png");
  });
}

function downloadCanvas(canvasId, filename) {
  console.log("images");
  // Obteniendo la etiqueta la cual se desea convertir en imagen
  var domElement = document.getElementById(canvasId);

  // Utilizando la función html2canvas para hacer la conversión
  html2canvas(domElement, {
    onrendered: function (domElementCanvas) {
      // Obteniendo el contexto del canvas ya generado
      var context = domElementCanvas.getContext("2d");

      // Creando enlace para descargar la imagen generada
      var link = document.createElement("a");
      link.href = domElementCanvas.toDataURL("image/png");
      link.download = filename;

      // Chequeando para browsers más viejos
      if (document.createEvent) {
        var event = document.createEvent("MouseEvents");
        // Simulando clic para descargar
        event.initMouseEvent(
          "click",
          true,
          true,
          window,
          0,
          0,
          0,
          0,
          0,
          false,
          false,
          false,
          false,
          0,
          null
        );
        link.dispatchEvent(event);
      } else {
        // Simulando clic para descargar
        link.click();
      }
    },
  });
}

//key = OTJlNDBlZjE3NmFmNDNiMjg0YzdlNTE0ZDIxN2E4ZjY=
//secret = I2Q1PD8/V2Z8GQA/IRUcPz8lPz9ZP1g/VkM/P29FKD8=
