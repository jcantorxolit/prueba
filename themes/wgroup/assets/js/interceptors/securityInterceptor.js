/**
 * declare 'clip-two' module with dependencies
 */
'use strict';
app.factory('securityInterceptor', ['$window', '$rootScope', '$q', '$timeout', 'SweetAlert', function ($window, $rootScope, $q, $timeout, SweetAlert) {
	return {
        // optional method
        'request': function (config) {
            if (config.url.indexOf("api/") > -1) {
                config.headers['X-CSRF-TOKEN'] = $('meta[name="csrf-token"]').attr('content');
                config.headers['Authorization'] = "Bearer " + $('meta[name="jwt-token"]').attr('content');
            }
            return config;
        },

        // optional method
        'requestError': function (rejection) {
            // do something on error
            return $q.reject(rejection);
        },


        // optional method
        'response': function (response) {
            // do something on success
            return response;
        },

        // optional method
        'responseError': function (rejection) {
            // NOTE: detect error because of unauthenticated user
            if ([400, 401, 403].indexOf(rejection.status) >= 0) {

                SweetAlert.swal({
                    title: "Sesión Expirada!",
                    text: "Su tiempo de sesión ha expirado, por favor inicie sesión de nuevo.",
                    type: "warning",
                    showCancelButton: false,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Salir",
                    closeOnConfirm: false},
                 function(){
                    var $logoutUrl = $rootScope.app.rootUrl + "logout";
                    $window.location.href = $logoutUrl
                 });

                return rejection;
            } else {
                return $q.reject(rejection);
            }

        }
    };
}]);
