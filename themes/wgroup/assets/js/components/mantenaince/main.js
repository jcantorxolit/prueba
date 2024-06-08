var app = angular.module('wgApp', ['mantenaince']);
app.run(['$rootScope', '$location', '$http', '$q', '$timeout', '$compile', '$window',
    function ($rootScope, $location, $http, $q, $timeout, $compile, $window) {

    }]);

app.config(['$locationProvider', function ($locationProvider) {
    $locationProvider.html5Mode({
        enabled: true,
        requireBase: false
    })
}]);