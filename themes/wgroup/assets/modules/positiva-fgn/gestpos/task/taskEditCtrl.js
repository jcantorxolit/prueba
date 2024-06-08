'use strict';
/**
  * controller for taskEditCtrl
*/
app.controller('taskEditCtrl',
function ($rootScope, $stateParams, $scope, $log, $timeout, SweetAlert, $http, $compile, ListService, $state, $uibModalInstance, ModuleListService) {

    $scope.sectorList = $rootScope.parameters("positiva_fgn_gestpos_sector");
    $scope.programList = $rootScope.parameters("positiva_fgn_gestpos_program");
    $scope.planList = $rootScope.parameters("positiva_fgn_gestpos_plan");
    $scope.actionLineList = $rootScope.parameters("positiva_fgn_gestpos_action_line");

    $scope.mainTaskList = [];
    $scope.subTaskList = [];

    $scope.main = true;
    $scope.subtask = false;
    $scope.dependenTask = false;

    var initialize = function() {
        $scope.entity = {
            id: $scope.campusId || 0,
            type: "main",
            name: null,
            isActive: true,
            addCode: false,
            sector: null,
            program: null,
            plan: null,
            actionLine: null,
            consecutive: null,
            mainTask: null,
            subTask: null
        }

        $scope.isManual = false;
    }
    initialize();

    $scope.onChangeType = function(type, reset) {
        switch (type) {
            case "main":
                    $scope.main = true;
                    $scope.subtask = false;
                    $scope.dependenTask = false;
                break;
            case "subtask":
                    $scope.main = false;
                    $scope.subtask = true;
                    $scope.dependenTask = false;
                    break;
            case "dependenTask":
                    $scope.main = false;
                    $scope.subtask = false;
                    $scope.dependenTask = true;
                break;
        }

        if(reset){
            initialize();
        }
        $scope.entity.type = type;
    }

    $scope.clearCode = function() {
        if(!$scope.entity.addCode) {
            $scope.entity.sector = null;
            $scope.entity.program = null;
            $scope.entity.plan = null;
            $scope.entity.actionLine = null;
            $scope.entity.consecutive = null;
        }
    }

    function getConfig() {
        var entities = [
            {name: 'main_task_list'},
            {name: 'subtask_list'},
        ];

        ModuleListService.getDataList('/positiva-fgn-gestpos/config',entities)
            .then(function (response) {
                $scope.mainTaskList = response.data.result.mainTaskList;
                $scope.subTaskList = response.data.result.subTaskList;
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }
    getConfig();


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
            url: 'api/positiva-fgn-gestpos/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            if($scope.entity.id == 0){
                $scope.onBack();
            }
            SweetAlert.swal("Proceso Exitoso", "Se ha almacenado correctamente la información.", "success");
        }).catch(function(e){
            $log.error(e);
            SweetAlert.swal("Error de guardado", e.data.message , "error");
        });

    };

    $scope.setManual = function() {
        $scope.isManual = true;
        $scope.entity.subTask = null;
    }

    $scope.onLoadRecord = function (){
        if($scope.entity.id > 0) {
            var req = {
                id: $scope.entity.id,
            };
            $http({
                method: 'GET',
                url: 'api/positiva-fgn-gestpos/get',
                params: req
            })
            .catch(function(e, code){
                SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información.", "error");
            })
            .then(function (response) {
                $timeout(function(){
                    $scope.entity = response.data.result;
                    $scope.onChangeType($scope.entity.type, false);
                });
            });
        }
    }
    $scope.onLoadRecord();


    $scope.onBack = function () {
        $uibModalInstance.close(1);
    };

});