'use strict';
/**
  * controller for campusEditCtrl
*/
app.controller('campusEditCtrl', function ($rootScope, $stateParams, $scope, $log, $timeout, SweetAlert, $http, $compile, ListService, $state, $uibModalInstance) {

    $scope.typeList = $rootScope.parameters("positiva_fgn_consultant_sectional_type");

    $scope.regionalList = [];
    $scope.sectionalList = [];
    $scope.departmentList = [];
    $scope.cityList = [];

    var initialize = function() {
        $scope.entity = {
            id: $scope.campusId || 0,
            regional: null,
            sectional: null,
            campus: null,
            department: null,
            city: null,
            address: null,
            isActive: false
        }
    }
    initialize();

    function getList() {
        var entities = [
            {name: 'positiva_fgn_consultant_sectional', criteria: { regionalId: $scope.entity.regional ? $scope.entity.regional.value : null } }
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.regionalList = response.data.data.regionalList;
                $scope.sectionalList = response.data.data.sectionalList;
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }
    getList();

    $scope.getDepartments = function () {
        var req = {
            cid: 68
        };
        $http({
            method: 'GET',
            url: 'api/states',
            params: req
        }).then(function (response) {
            $scope.departmentList = response.data.result;
        });
    };
    $scope.getDepartments();

    $scope.changeDepartment = function (item, clearCity) {
        $scope.cityList = [];

        if(clearCity){
            $scope.entity.city = null;
        }

        var req = {
            sid: item.id
        };

        $http({
            method: 'GET',
            url: 'api/towns',
            params: req
        }).then(function (response) {
            $scope.cityList = response.data.result;
        });
    };

    $scope.filterSectional = function() {
        $scope.entity.sectional = null;
        getList();
    }

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
            url: 'api/positiva-fgn-campus/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $scope.entity.id = response.data.result.id;
            SweetAlert.swal("Proceso Exitoso", "Se ha almacenado correctamente la información.", "success");
        }).catch(function(e){
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        });

    };

    $scope.onLoadRecord = function (){
        if($scope.entity.id > 0) {
            var req = {
                id: $scope.entity.id,
            };
            $http({
                method: 'GET',
                url: 'api/positiva-fgn-campus/get',
                params: req
            })
            .catch(function(e, code){
                SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información.", "error");
            })
            .then(function (response) {
                $timeout(function(){
                    $scope.entity = response.data.result;
                    getList();
                    $scope.changeDepartment($scope.entity.department, false);
                });
            });
        }
    }
    $scope.onLoadRecord();


    $scope.onBack = function () {
        $uibModalInstance.close(1);
    };

});