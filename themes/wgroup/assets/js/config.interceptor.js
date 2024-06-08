'use strict';
app.config(['$httpProvider',
    function ($httpProvider) {

        //configure Jquery Ajax Security Context
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Authorization': "Bearer " + $('meta[name="jwt-token"]').attr('content')
            }
        });

        //this line is for angularjs indicate to server that all its operations are ajax actions
        $httpProvider.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

        $httpProvider.interceptors.push('globalAppInterceptor');
        $httpProvider.interceptors.push('securityInterceptor');
    }]);
