'use strict';
/**
  * controller for Customers
*/
app.controller('termConditionCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder','$compile','toaster','$state','$rootScope','$timeout','$http','SweetAlert' , '$aside', '$document', 
    '$location', '$window',
function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
          $rootScope,$timeout, $http, SweetAlert, $aside, $document, $location, $window) {

        var log = $log;
        
        $scope.loading = true;
        $scope.isView = false;

        $scope.terms = $rootScope.parameters("wg_term_condition");

        var initialize = function () {
            $scope.request = {};

            $scope.parameter = {
                id: $scope.terms.length > 0 ? $scope.terms[0].id : 0,
                namespace: "wgroup",
                group: "wg_term_condition",
                item: "",
                value: "",
                type: null
            };

            $scope.privacy = {
                id: $scope.terms.length > 0 ? $scope.terms[0].id : 0,
                namespace: "wgroup",
                group: "wg_term_condition",
                item: "",
                value: "",
                type: null
            };            
        };

        initialize();

        $scope.onLoadRecord = function () {
            $http({
                method: 'GET',
                url: 'api/system-parameter/term-condition'                
            })
                .catch(function (e, code) {
  
                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.parameter = response.data.result;
                    });

                }).finally(function () {
                    $timeout(function () {
                        $scope.loading = false;
                    }, 400);

                    $timeout(function () {
                        $document.scrollTop(40, 2000);
                    });
                });
        }

        $scope.onLoadRecord();


        var onLoadPrivacyRecord = function () {
            $http({
                method: 'GET',
                url: 'api/system-parameter/privacy-policy'                
            })
                .catch(function (e, code) {
  
                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.privacy = response.data.result;
                    });

                }).finally(function () {
                    $timeout(function () {
                        $scope.loading = false;
                    }, 400);

                    $timeout(function () {
                        $document.scrollTop(40, 2000);
                    });
                });
        }

        onLoadPrivacyRecord();
        
        $scope.form = {

            submit: function (form) {
                var firstError = null;

                if (form.$invalid) {

                    var field = null, firstError = null;
                    for (field in form) {
                        if (field[0] != '$') {
                            if (firstError === null && !form[field].$valid) {
                                firstError = form[field].$name;
                            }

                            if (form[field].$pristine) {
                                form[field].$dirty = true;
                            }
                        }
                    }
                    
                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                    SweetAlert.swal("Error guardando", "Se han encontrado errores en el proceso de validación " +
                        "por favor verifique los datos del formulario y vuelva a intentarlo", "error");
                    return;

                } else {
                    SweetAlert.swal("Validación exitosa", "Guardando información de los terminos y condiciones", "success");
                    //your code for submit
                    save();
                }

            },
            reset: function (form) {                
                form.$setPristine(true);
            }
        };

        var save = function () {
            var req = {};
            var data = JSON.stringify($scope.parameter);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/system-parameter/agree',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    if (response.data.result == 'logout') {
                        $window.location.href = $rootScope.app.rootUrl + "logout";
                    } else if (response.data.result == 'app/clientes/list') {                        
                        $window.location.href = response.data.result;
                    } else if (response.data.result.indexOf('/view') != -1) {
                        var id = response.data.result.substring(response.data.result.lastIndexOf('/') + 1)
                        if ($rootScope.app.supportHelp) {                          
                            $rootScope.app.supportHelp.isTermAndCondition = true;
                        }
                        $rootScope.currentUser().wg_term_condition = '1'                        
                        $state.go("app.clientes.view", {"customerId": id});
                    }
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el proceso por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

    }
]);