'use strict';
app.directive('onCarouselChange', function ($parse) {
    return {
        require: 'uib-carousel',
        link: function (scope, element, attrs, carouselCtrl) {            
            var fn = $parse(attrs.onCarouselChange);
            var origSelect = carouselCtrl.select;                   
            carouselCtrl.select = function (nextSlide, direction) {                
                if (nextSlide !== this.currentSlide) {
                    fn(scope, {
                        nextSlide: nextSlide,
                        direction: direction,
                    });
                }
                return origSelect.apply(this, arguments);
            };
        }
    };
});

app.directive('fallbackSrc', function () {
    var fallbackSrc = {
        link: function postLink(scope, iElement, iAttrs) {            
            iElement.on('error', function () {                
                angular.element(this).attr("src", iAttrs.fallbackSrc);
            });
        }
    }
    return fallbackSrc;
});