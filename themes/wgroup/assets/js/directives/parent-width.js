'use strict';
/** 
  * Set the Width of the parent. 
*/
// app.directive('parentWidth', function ($timeout) {
//   return function (scope, element, attr) {

//     console.log(angular.element(element));

//     var previousPosition = element.parent()[0].getBoundingClientRect();
//     var timer;

//     onFrame();

//     function onFrame() {
//       var currentPosition = element.parent()[0].getBoundingClientRect();

//       if (!angular.equals(previousPosition.width, currentPosition.width)) {
//         resiszeNotifier();
//       }

//       previousPosition = currentPosition;
//       requestAnimationFrame(onFrame);
//     }

//     function resiszeNotifier() {
//       if (!timer) setTimeout();
//     }

//     function setTimeout() {
//       console.log('onResize start')
//       timer = $timeout(function () {
//         // element.css({ 
//         //     width: previousPosition.width - 30 + 'px' 
//         // });                
//         timer = null;
//         console.log('onResize end')
//       }, 2000);
//     }

//     element.css({
//       width: element.parent()[0].offsetWidth - 30 + 'px'
//     });
//   };
// });

app.directive('parentWidth', function ($timeout) {
  return {
      link: function (scope, element, attr) {
          var timer;

          angular.element(window).on('resize', onResize);
          scope.$on('$destroy', function () {
              // Limpiar el evento de redimensionamiento cuando se destruye la directiva
              angular.element(window).off('resize', onResize);
          });

          function onResize() {
              if (!timer) {
                  timer = $timeout(function () {
                      updateWidth();
                      timer = null;
                  }, 200); // Ajusta el valor de tiempo según tus necesidades
              }
          }

          function updateWidth() {
              // Calcula el ancho del elemento padre
              var parentWidth = element.parent().width();

              // Aplica el ancho al elemento hijo (ajustando según sea necesario)
              element.css({
                  width: parentWidth - 30 + 'px'
              });
          }

          // Llama a la función de actualización al cargar la página
          updateWidth();
      }
  };
});

app.directive('parentWidthFull', function ($timeout) {
  return function (scope, element, attr) {

    console.log(angular.element(element));

    var previousPosition = element.parent()[0].getBoundingClientRect();
    var timer;

    onFrame();

    function onFrame() {
      var currentPosition = element.parent()[0].getBoundingClientRect();

      if (!angular.equals(previousPosition.width, currentPosition.width)) {
        resiszeNotifier();
      }

      previousPosition = currentPosition;
      requestAnimationFrame(onFrame);
    }

    function resiszeNotifier() {
      if (!timer) setTimeout();
    }

    function setTimeout() {
      console.log('onResize start')
      timer = $timeout(function () {
        // element.css({ 
        //     width: previousPosition.width - 20 + 'px' 
        // });                
        timer = null;
        console.log('onResize end')
      }, 2000);
    }

    element.css({
      width: element.parent()[0].offsetWidth + 'px'
    });
  };
});

app.directive('autoResizeDiv', function () {
  return {
      link: function (scope, element, attrs) {
          // Función para actualizar el ancho del div hijo
          function updateWidth() {
              var parentWidth = element.parent().width() - 5;

              console.log(parentWidth);
              element.css('width', parentWidth + 'px');
          }

          // Crear una instancia de ResizeObserver
          var resizeObserver = new ResizeObserver(function (entries) {
              // Se ejecutará cuando cambie el tamaño del padre
              updateWidth();
          });

          // Observar el elemento padre
          resizeObserver.observe(element.parent()[0]);

          // Llamar a la función de actualización cuando se carga la página
          updateWidth();
      }
  };
});