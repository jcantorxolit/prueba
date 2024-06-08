'use strict';
/**
  * controller for configEditCtrl
*/
app.controller('configEditCtrl',
function ($rootScope, $stateParams, $scope, $log, $timeout, SweetAlert, $http, $state, $uibModalInstance, moment, $localStorage) {

    $scope.isView = $localStorage.isView || false;
    $scope.maxDate = new Date();
    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy"
    };

    var initialize = function() {
        $scope.entity = {
            id: $stateParams.configId || 0,
            period: null,
            startDate: null,
            endDate: null,
            isActive: true
        }
    }
    initialize();

    $scope.$watch("entity.startDate", function () {
        if($scope.entity.startDate) {
            var check = moment($scope.entity.startDate, "DD/MM/YYYY");
            var day = check.format("D");
            var month = check.format("M");
            var thisYear = check.format("YYYY");
            if(day > 1 || month > 1) {
                $scope.entity.startDate = moment("01/01/" + thisYear).format("DD/MM/YYYY");
                $scope.entity.endDate = "31/12/" + thisYear;
                $scope.entity.period = thisYear;
            }
        }
    });


    $scope.form = {
        submit: function (form) {
            var firstError = null;
            $scope.Form = form;
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
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e intentalo de nuevo.", "error");
                return;
            } else {
                save();
            }
        },
        reset: function () {
            $scope.Form.$setPristine(true);
            initialize();
        }
    };


    var save = function () {
        var req = {};
        var data = JSON.stringify($scope.entity);
        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/positiva-fgn-fgn-config/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $scope.entity.id = response.data.result.id;
            SweetAlert.swal("Proceso Exitoso", "Se ha almacenado correctamente la información.", "success");
        }).catch(function(e){
            $log.error(e);
            SweetAlert.swal("Error de guardado", e.data.message , "error");
        });

    };


    $scope.onLoadRecord = function (){
        if($scope.entity.id > 0) {
            var req = {
                id: $scope.entity.id,
            };
            $http({
                method: 'GET',
                url: 'api/positiva-fgn-fgn-config/get',
                params: req
            })
            .catch(function(e, code){
                SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información.", "error");
            })
            .then(function (response) {
                $timeout(function(){
                    $scope.entity = response.data.result;
                });
            });
        }
    }
    $scope.onLoadRecord();


    $scope.onBack = function () {
        $uibModalInstance.close(1);
    };

    $scope.onNext = function () {
        $rootScope.isView = true;
        $state.go("app.positiva-fgn.fgn-activity-list",  { "configId": $scope.entity.id });
    };


});
