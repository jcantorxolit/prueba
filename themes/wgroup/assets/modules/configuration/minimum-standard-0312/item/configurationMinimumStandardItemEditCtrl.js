'use strict';
/**
 * controller for Customers
 */
app.controller('configurationMinimumStandardItemEdit0312Ctrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder', 'DTColumnDefBuilder',
    '$compile', 'toaster', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter',
    '$document', 'FileUploader', '$localStorage', '$aside', 'ListService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter, $document, FileUploader,
              $localStorage, $aside, ListService) {

        var $formInstance = null;

        $scope.isCreate = $scope.$parent.currentId == 0;

        $scope.parentList = [];
        $scope.standardList = [];
        $scope.standardListAll = [];

        getList();

        function getList() {
            var entities = [
                { name: 'minimum-standard-parent-0312', value: null },
                { name: 'minimum-standard-0312', value: null },
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.parentList = response.data.data.minimum_standard_parent_0312;
                    $scope.standardListAll = response.data.data.minimum_standard_0312;

                    getListParent();
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        var onInit = function () {
            $scope.standard = {
                id: $scope.$parent.currentId ? $scope.$parent.currentId : 0,
                minimumStandard: null,
                minimumStandardParent: null,
                numeral: "",
                description: "",
                value: 0,
                criterion: '',
                isActive: true,
                legalFrameworkList: []
            };

            if ($formInstance != null) {
                $formInstance.$setPristine(true);
            }
        }

        onInit();

        $scope.onSelectParent = function() {
            getListParent();
            $scope.standard.minimumStandard = null;
        }

        var getListParent = function() {
            if ($scope.standard.minimumStandardParent != null) {
                $scope.standardList = $filter('filter')($scope.standardListAll, {parentId: $scope.standard.minimumStandardParent.id}, true);
            }
        }

        var onLoadRecord = function () {
            // se debe cargar primero la información actual del cliente..

            if ($scope.standard.id) {
                var req = {
                    id: $scope.standard.id
                };

                $http({
                    method: 'GET',
                    url: 'api/minimum-standard-item-0312/get',
                    params: req
                })
                    .catch(function (e, code) {

                    })
                    .then(function (response) {

                        $timeout(function () {
                            $scope.standard = response.data.result;

                            getListParent();

                            onNotify();
                        });

                    }).finally(function () {

                    });
            }
        };

        onLoadRecord();

        $scope.form = {

            submit: function (form) {

                $formInstance = form;

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
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");
                    return;

                } else {
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
            var data = JSON.stringify($scope.standard);
            req.data = Base64.encode(data);

            return $http({
                method: 'POST',
                url: 'api/minimum-standard-item-0312/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                $timeout(function () {
                    $scope.standard = response.data.result;
                    onNotify();
                });
            }).catch(function (e) {
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });
        };

        var onNotify = function() {
            $rootScope.$emit('onMinimumStandardChanged', { newValue: $scope.standard.id, message: 'Minimum standard 0312 has changed' });
        }

        $scope.onCancel = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list", 0);
            }
        };


        //----------------------------------------------------------------LEGAL FRAMEWORK
        $scope.onAddLegalFramework = function () {

            $timeout(function () {
                if ($scope.standard.legalFrameworkList == null) {
                    $scope.standard.legalFrameworkList = [];
                }
                $scope.standard.legalFrameworkList.push(
                    {
                        id: 0,
                        minimumStandardItemId: 0,
                        type: 'legal-framework',
                        description: "",
                    }
                );
            });
        };

        $scope.onRemoveLegalFramework = function (index) {
            SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Confirma que desea elimintar el registro seleccionado?",
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
                            var date = $scope.standard.legalFrameworkList[index];

                            $scope.standard.legalFrameworkList.splice(index, 1);

                            if (date.id != 0) {
                                var req = {};
                                req.id = date.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/minimum-standard-item-detail-0312/delete',
                                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                    data: $.param(req)
                                }).then(function (response) {
								swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                                }).catch(function (e) {
                                    $log.error(e);
                                    SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                                }).finally(function () {

                                });
                            }
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        }
    }
]);
