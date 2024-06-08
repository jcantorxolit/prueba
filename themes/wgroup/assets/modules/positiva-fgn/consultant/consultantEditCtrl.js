'use strict';
/**
  * controller for Customers
*/
app.controller('consultantEditCtrl',
function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
          $rootScope,$timeout, $http, SweetAlert, $aside, $document,  $filter) {

    var log = $log;
    $scope.entity = {};
    $scope.typeList = $rootScope.parameters("positiva_fgn_consultant_type");
    $scope.documentTypeList = $rootScope.parameters("employee_document_type");
    $scope.genderList = $rootScope.parameters("gender");
    $scope.gradeList = $rootScope.parameters("positiva_fgn_consultant_grade");
    $scope.accountingAccountList = $rootScope.parameters("accounting_account");
    $scope.workingDayList = $rootScope.parameters("positiva_fgn_consultant_workday");
    $scope.epsList = $rootScope.parameters("eps");
    $scope.afpList = $rootScope.parameters("afp");
    $scope.ccfList = $rootScope.parameters("ccf");
    $scope.accountTypeList = $rootScope.parameters("account_type");
    $scope.contactInformationList = $rootScope.parameters("extrainfo");
    $scope.strategyList = $rootScope.parameters("positiva_fgn_consultant_strategy");
    $scope.strategyTypeList = $rootScope.parameters("positiva_fgn_strategy_type");

    $scope.maxDate = new Date();
    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy"
    };

    var initialize = function() {
        $scope.entity = {
            id: $stateParams.consultantId || 0,
            type: null,
            isActive: null,
            documentType: null,
            documentNumber: null,
            fullName: null,
            gender: null,
            birthDate: null,
            job: null,
            grade: null,
            accountingAccount: null,
            admissionDate: null,
            withdrawalDate: null,
            profession: null,
            workingDay: null,
            mainContact: null,
            telephone: null,
            eps: null,
            afp: null,
            ccf: null,
            accountType: null,
            bank: null,
            accountNumber: null,
            details: { license: [], contact: [], strategy: []}
        };

    };
    initialize();

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
                var currents = getStrategiesBase();
                if (currents.length != 1) {
                    SweetAlert.swal("El formulario contiene errores!", "Por favor indique una estrategia base.", "error");
                    return;
                }

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
            url: 'api/positiva-fgn-consultant/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $scope.entity.id = response.data.result.id;
            $scope.entity.details = response.data.result.details;
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
                url: 'api/positiva-fgn-consultant/get',
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

    $scope.onAddInfoDetailLicense = function () {
        $timeout(function () {
            $scope.entity.details.license.push({
                id: 0,
                license: null,
                expeditionDate: null,
                issuingEntity: null,
            });
        });
    };

    $scope.onRemoveInfoDetailLicense = function (index, id) {

        if(id == 0) {
            $scope.entity.details.license.splice(index, 1);
            return;
        }

        SweetAlert.swal({
            title: "Está seguro?",
            text: "Eliminará el registro seleccionado",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Si, eliminar!",
            cancelButtonText: "No, cancelar!",
            closeOnConfirm: true,
            closeOnCancel: true
        },
            function (isConfirm) {
                if (isConfirm) {
                    $timeout(function () {
                        // eliminamos el registro en la posicion seleccionada
                        var contact = $scope.entity.details.license[index];
                        $scope.entity.details.license.splice(index, 1);
                        var req = {};
                        req.id = contact.id;
                        req.detail = "license";
                        $http({
                            method: 'POST',
                            url: 'api/positiva-fgn-consultant-detail/delete',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            data: $.param(req)
                        }).then(function (response) {
                            swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                        }).catch(function (e) {
                            $log.error(e);
                            SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                        })
                    });
                }
            });
    }

    $scope.onAddInfoDetailContact = function () {
        $timeout(function () {
            $scope.entity.details.contact.push({
                id: 0,
                value: "",
                type: null
            });
        });
    };

    $scope.onRemoveInfoDetailContact = function (index, id) {

        if(id == 0) {
            $scope.entity.details.contact.splice(index, 1);
            return;
        }

        SweetAlert.swal({
            title: "Está seguro?",
            text: "Eliminará el registro seleccionado",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Si, eliminar!",
            cancelButtonText: "No, cancelar!",
            closeOnConfirm: true,
            closeOnCancel: true
        },
            function (isConfirm) {
                if (isConfirm) {
                    $timeout(function () {
                        // eliminamos el registro en la posicion seleccionada
                        var contact = $scope.entity.details.contact[index];
                        $scope.entity.details.contact.splice(index, 1);
                        var req = {};
                        req.id = contact.id;
                        req.detail = "contact";
                        $http({
                            method: 'POST',
                            url: 'api/positiva-fgn-consultant-detail/delete',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            data: $.param(req)
                        }).then(function (response) {
                            swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                        }).catch(function (e) {
                            $log.error(e);
                            SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                        });
                    });
                }
            });
    }

    $scope.onAddInfoDetailStrategy = function () {
        $timeout(function () {
            var strategies = $filter('filter')($scope.entity.details.strategy);

            var value = "STT001";
            if (strategies.length) {
                value = "STT002";
            }

            var type = $filter('filter')($scope.strategyTypeList, { value: value } );

            type = type[0] || null;
            $scope.entity.details.strategy.push({
                id: 0,
                strategy: "",
                type: type,
                isActive: true
            });
        });
    };

    $scope.onRemoveInfoDetailStrategy = function (index, id) {

        if(id == 0) {
            $scope.entity.details.strategy.splice(index, 1);
            return;
        }

        SweetAlert.swal({
            title: "Está seguro?",
            text: "Eliminará el registro seleccionado",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Si, eliminar!",
            cancelButtonText: "No, cancelar!",
            closeOnConfirm: true,
            closeOnCancel: true
        },
            function (isConfirm) {
                if (isConfirm) {
                    $timeout(function () {
                        // eliminamos el registro en la posicion seleccionada
                        var contact = $scope.entity.details.strategy[index];
                        $scope.entity.details.strategy.splice(index, 1);
                        var req = {};
                        req.id = contact.id;
                        req.detail = "strategy";
                        $http({
                            method: 'POST',
                            url: 'api/positiva-fgn-consultant-detail/delete',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            data: $.param(req)
                        }).then(function (response) {
                            swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                        }).catch(function (e) {
                            $log.error(e);
                            SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                        })
                    });
                }
            });
    }

    $scope.onBack = function() {
        $state.go("app.positiva-fgn.consultants-list");
    }

    $scope.valideSingle = function(item) {
        var currents = $filter('filter')($scope.entity.details.strategy, { strategy: item});
        if(currents.length) {
            return true;
        }
        return false;
    }

    $scope.valideSingleStrategyType = function(item) {
        if (item.value != "STT001") {
            return false;
        }

        var currents = getStrategiesBase();
        if (currents.length) {
            return true;
        }

        return false;
    }

    function getStrategiesBase() {
        var currents = $filter('filter')($scope.entity.details.strategy, function (strategy) {
            return strategy.type && strategy.type.value == "STT001";
        });

        return currents;
    }

});
