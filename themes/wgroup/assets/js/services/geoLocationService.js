'use strict';

app
    .factory('GeoLocationService', ['$q', '$window', function ($q, $window) {

        'use strict';

        function getCurrentPosition() {
            var deferred = $q.defer();

            if (!$window.navigator.geolocation) {
                deferred.reject('Geolocation not supported.');
            } else {
                $window.navigator.geolocation.getCurrentPosition(
                    function (position) {
                        deferred.resolve(position);
                    },
                    function (err) {
                        deferred.reject(err);
                    });
            }

            return deferred.promise;
        }

        return {
            getCurrentPosition: getCurrentPosition
        };
    }]);