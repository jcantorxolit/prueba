'use strict';
/**
 * Lazy collection that is backed by a concrete collection
 *
 * @author David Blandon <david.blandon@gmail.com>
 * @since  1.0
 */
app.controller('customerMatrixDataTabEditCtrl', ['$scope', '$stateParams', '$log',
    '$compile', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter', '$aside', '$document', 'FileUploader', '$localStorage', 'toaster',
    function ($scope, $stateParams, $log, $compile, $state,
              SweetAlert, $rootScope, $http, $timeout, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $uibModal, flowFactory,
              cfpLoadingBar, $filter, $aside, $document, FileUploader, $localStorage, toaster) {

        var log = $log;

        var currentId = $scope.$parent.$parent.$parent.currentId;
        var currentParentId = $scope.$parent.$parent.$parent.$parent.currentId
        console.log($scope.$parent);

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";


        $scope.environmentalImpactInList = $rootScope.parameters("matrix_environmental_impact_in");
        $scope.environmentalImpactExList = $rootScope.parameters("matrix_environmental_impact_ex");
        $scope.environmentalImpactPrList = $rootScope.parameters("matrix_environmental_impact_pr");
        $scope.environmentalImpactReList = $rootScope.parameters("matrix_environmental_impact_re");
        $scope.environmentalImpactRvList = $rootScope.parameters("matrix_environmental_impact_rv");
        $scope.environmentalImpactSeList = $rootScope.parameters("matrix_environmental_impact_se");
        $scope.environmentalImpactFrList = $rootScope.parameters("matrix_environmental_impact_fr");
        $scope.legalImpactEList = $rootScope.parameters("matrix_legal_impact_e");
        $scope.legalImpactCList = $rootScope.parameters("matrix_legal_impact_c");
        $scope.interestedPartAcList = $rootScope.parameters("matrix_interested_part_ac");
        $scope.interestedPartGeList = $rootScope.parameters("matrix_interested_part_ge");
        $scope.natureList = $rootScope.parameters("matrix_nature");
        $scope.emergencyConditionInList = $rootScope.parameters("matrix_environmental_impact_in");
        $scope.emergencyConditionExList = $rootScope.parameters("matrix_environmental_impact_ex");
        $scope.emergencyConditionPrList = $rootScope.parameters("matrix_environmental_impact_pr");
        $scope.emergencyConditionReList = $rootScope.parameters("matrix_environmental_impact_re");
        $scope.emergencyConditionRvList = $rootScope.parameters("matrix_environmental_impact_rv");
        $scope.emergencyConditionSeList = $rootScope.parameters("matrix_environmental_impact_se");
        $scope.emergencyConditionFrList = $rootScope.parameters("matrix_environmental_impact_fr");
        $scope.typeList = $rootScope.parameters("matrix_control_type");
        $scope.responsibleList = $rootScope.parameters("matrix_responsible");
        $scope.scopeList = $rootScope.parameters("matrix_scope");


        $scope.projectList = [];
        $scope.activityList = [];
        $scope.environmentalAspectList = [];
        $scope.environmentalImpactList = [];

        $scope.isView = $scope.$parent.modeDsp == "view";
        $scope.minDateCurrent = new Date();

        $scope.maxDate = new Date();
        $scope.datePickerConfig = {
            culture: "es-CO",
            format: "dd/MM/yyyy HH:mm"
        };

        $scope.onLoadRecord = function () {
            if ($scope.matrix.id != 0) {

                // se debe cargar primero la información actual del cliente..
                log.info("editando cliente con código: " + $scope.matrix.id);
                var req = {
                    id: $scope.matrix.id
                };
                $http({
                    method: 'GET',
                    url: 'api/customer/matrix-data',
                    params: req
                })
                    .catch(function (e, code) {
                        if (code == 403) {
                            var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.clientes.list';
                            // forbbiden
                            // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                            SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                            $timeout(function () {
                                $state.go(messagered);
                            }, 3000);
                        } else if (code == 404) {
                            SweetAlert.swal("Información no disponible", "Centro de trabajo no encontrado", "error");

                        } else {
                            SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del centro de trabajo", "error");
                        }
                    })
                    .then(function (response) {

                        $timeout(function () {
                            $scope.matrix = response.data.result;
                        });

                    }).finally(function () {
                        $timeout(function () {
                            $scope.loading = false;
                        }, 400);

                        $timeout(function () {
                            $document.scrollTop(40, 2000);
                        });

                    });
            } else {
                //Se creara nuevo cliente
                log.info("creacion de nuevo cliente");
                $scope.loading = false;
            }
        }

        var init = function () {
            $scope.matrix = {
                id: currentId,
                customerMatrixId: currentParentId,
                project: null,
                activity: null,
                environmentalAspect: null,
                environmentalImpact: null,
                environmentalImpactIn: null,
                environmentalImpactEx: null,
                environmentalImpactPr: null,
                environmentalImpactRe: null,
                environmentalImpactRv: null,
                environmentalImpactSe: null,
                environmentalImpactFr: null,
                nia: 0,
                niaDescription: '',
                legalImpactE: null,
                legalImpactC: null,
                legalImpactCriterion: null,
                interestedPartAc: null,
                interestedPartGe: null,
                interestedPartCriterion: null,
                totalAspect: 0,
                totalAspectDescription: '',
                nature: null,
                emergencyConditionIn: null,
                emergencyConditionEx: null,
                emergencyConditionPr: null,
                emergencyConditionRe: null,
                emergencyConditionRv: null,
                emergencyConditionSe: null,
                emergencyConditionFr: null,
                emergencyNia: 0,
                emergencyNiaDescription: '',
                controlList: [],
                responsibleList: [],
                associateProgram: '',
                scope: '',
            };
        }

        var loadList = function () {

            var req = {};
            req.operation = "diagnostic";
            req.customerMatrixId = currentParentId;
            console.log(currentParentId);

            return $http({
                method: 'POST',
                url: 'api/customer/matrix-data/list',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.projectList = response.data.data.projectList;
                    $scope.activityList = response.data.data.activityList;
                    $scope.environmentalAspectList = response.data.data.aspectList;
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });
        };

        $scope.refreshProject = function () {
          loadList();
        };

        var loadImpacttList = function (aspectId) {

            var req = {};
            req.operation = "diagnostic";
            req.customerMatrixId = currentParentId;
            req.aspectId = aspectId;

            return $http({
                method: 'POST',
                url: 'api/customer/matrix-data/impact-list',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.environmentalImpactList = response.data.data;
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });
        };

        init();
        loadList();

        $scope.onLoadRecord();

        $scope.master = $scope.matrix;

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
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");
                    return;

                } else {
                    SweetAlert.swal("Validación exitosa", "Procediendo con el guardado...", "success");
                    //your code for submit
                    save();
                }

            },
            reset: function (form) {

                $scope.matrix = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var save = function () {
            var req = {};

            var data = JSON.stringify($scope.matrix);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer/matrix-data/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    $scope.matrix = response.data.result;
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
            });
        };

        $scope.cancelEdition = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list");
            }
        };

        //----------------------------------------------------------------CONTROLS
        $scope.onAddControlType = function () {

            $timeout(function () {
                if ($scope.matrix.controlList == null) {
                    $scope.matrix.controlList = [];
                }
                $scope.matrix.controlList.push(
                    {
                        id: 0,
                        type: null,
                        description: ''
                    }
                );
            });
        };

        $scope.onRemoveControlType = function (index) {
            SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Eliminará el registro seleccionado.",
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
                            var date = $scope.matrix.controlList[index];

                            $scope.matrix.controlList.splice(index, 1);

                            if (date.id != 0) {
                                var req = {};
                                req.id = date.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer/matrix-data-control/delete',
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

        //----------------------------------------------------------------RESPONSIBLE
        $scope.onAddResponsible = function () {

            $timeout(function () {
                if ($scope.matrix.responsibleList == null) {
                    $scope.matrix.responsibleList = [];
                }
                $scope.matrix.responsibleList.push(
                    {
                        id: 0,
                        responsible: null
                    }
                );
            });
        };

        $scope.onRemoveImpact = function (index) {
            SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Eliminará el registro seleccionado.",
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
                            var date = $scope.matrix.responsibleList[index];

                            $scope.matrix.responsibleList.splice(index, 1);

                            if (date.id != 0) {
                                var req = {};
                                req.id = date.id;
                                $http({
                                    method: 'POST',
                                    url: 'api/customer/matrix-data-responsible/delete',
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

        //----------------------------------------------------------------REFRESH LIST
        $scope.onRefreshImpact = function () {
            loadList();
        }


        //----------------------------------------------------------------WATCHERS

        $scope.$watch("matrix.environmentalAspect", function (newValue, oldValue, scope) {

            $scope.environmentalImpactList = [];

            if (oldValue != null && !angular.equals(newValue, oldValue)) {
                $scope.matrix.environmentalImpact = null;
            }

            if ($scope.matrix.environmentalAspect != null) {

                angular.forEach($scope.matrix.environmentalAspect.impacts, function (value, key) {
                    $scope.environmentalImpactList.push(value.impact);
                });

            }

        });


        $scope.$watch("matrix.environmentalImpactIn", function () {
            calculateNia();
        });

        $scope.$watch("matrix.environmentalImpactEx", function () {
            calculateNia();
        });

        $scope.$watch("matrix.environmentalImpactPr", function () {
            calculateNia();
        });

        $scope.$watch("matrix.environmentalImpactRe", function () {
            calculateNia();
        });

        $scope.$watch("matrix.environmentalImpactRv", function () {
            calculateNia();
        });

        $scope.$watch("matrix.environmentalImpactSe", function () {
            calculateNia();
        });

        $scope.$watch("matrix.environmentalImpactFr", function () {
            calculateNia();
        });



        $scope.$watch("matrix.legalImpactE", function () {
            calculateLegalImpact();
        });

        $scope.$watch("matrix.legalImpactC", function () {
            calculateLegalImpact();
        });



        $scope.$watch("matrix.interestedPartAc", function () {
            calculateInterestedPart();
        });

        $scope.$watch("matrix.interestedPartGe", function () {
            calculateInterestedPart();
        });



        $scope.$watch("matrix.emergencyConditionIn", function () {
            calculateEmergencyNia();
        });

        $scope.$watch("matrix.emergencyConditionEx", function () {
            calculateEmergencyNia();
        });

        $scope.$watch("matrix.emergencyConditionPr", function () {
            calculateEmergencyNia();
        });

        $scope.$watch("matrix.emergencyConditionRe", function () {
            calculateEmergencyNia();
        });

        $scope.$watch("matrix.emergencyConditionRv", function () {
            calculateEmergencyNia();
        });

        $scope.$watch("matrix.emergencyConditionSe", function () {
            calculateEmergencyNia();
        });

        $scope.$watch("matrix.emergencyConditionFr", function () {
            calculateEmergencyNia();
        });

        //----------------------------------------------------------------CALCULATES

        var calculateNia = function () {

            var environmentalImpactIn = $scope.matrix.environmentalImpactIn ? parseFloat($scope.matrix.environmentalImpactIn.value) : 0;
            var environmentalImpactEx = $scope.matrix.environmentalImpactEx ? parseFloat($scope.matrix.environmentalImpactEx.value) : 0;
            var environmentalImpactPr = $scope.matrix.environmentalImpactPr ? parseFloat($scope.matrix.environmentalImpactPr.value) : 0;
            var environmentalImpactRe = $scope.matrix.environmentalImpactRe ? parseFloat($scope.matrix.environmentalImpactRe.value) : 0;
            var environmentalImpactRv = $scope.matrix.environmentalImpactRv ? parseFloat($scope.matrix.environmentalImpactRv.value) : 0;
            var environmentalImpactSe = $scope.matrix.environmentalImpactSe ? parseFloat($scope.matrix.environmentalImpactSe.value) : 0;
            var environmentalImpactFr = $scope.matrix.environmentalImpactFr ? parseFloat($scope.matrix.environmentalImpactFr.value) : 0;

            $scope.matrix.nia = (3 * environmentalImpactIn) + (2 * environmentalImpactEx) + environmentalImpactPr + environmentalImpactRe + environmentalImpactRv + environmentalImpactSe + environmentalImpactFr;

            if ($scope.matrix.nia >= 9 && $scope.matrix.nia <= 18) {
                $scope.matrix.niaDescription = 'BAJO';
            } else if ($scope.matrix.nia >= 19 && $scope.matrix.nia <= 28) {
                $scope.matrix.niaDescription = 'MEDIO';
            } else if ($scope.matrix.nia >= 29 && $scope.matrix.nia <= 36) {
                $scope.matrix.niaDescription = 'ALTO';
            } else {
                $scope.matrix.niaDescription = '';
            }
        }

        var calculateLegalImpact = function () {

            var legalImpactE = $scope.matrix.legalImpactE ? parseFloat($scope.matrix.legalImpactE.value) : 0;
            var legalImpactC = $scope.matrix.legalImpactC ? parseFloat($scope.matrix.legalImpactC.value) : 0;

            $scope.matrix.legalImpactCriterion = legalImpactE + legalImpactC;

            calculateTotalAspect();
        }

        var calculateInterestedPart = function () {

            var interestedPartAc = $scope.matrix.interestedPartAc ? parseFloat($scope.matrix.interestedPartAc.value) : 0;
            var interestedPartGe = $scope.matrix.interestedPartGe ? parseFloat($scope.matrix.interestedPartGe.value) : 0;

            $scope.matrix.interestedPartCriterion = interestedPartAc + interestedPartGe;

            calculateTotalAspect();
        }

        var calculateTotalAspect = function () {

            var nia = $scope.matrix.nia ? parseFloat($scope.matrix.nia) : 0;
            var legalImpactCriterion = $scope.matrix.legalImpactCriterion ? parseFloat($scope.matrix.legalImpactCriterion) : 0;
            var interestedPartCriterion = $scope.matrix.interestedPartCriterion ? parseFloat($scope.matrix.interestedPartCriterion) : 0;

            $scope.matrix.totalAspect = nia + legalImpactCriterion + interestedPartCriterion;

            if ($scope.matrix.totalAspect >= 13 && $scope.matrix.totalAspect <= 26) {
                $scope.matrix.totalAspectDescription = 'BAJO';
            } else if ($scope.matrix.totalAspect >= 27 && $scope.matrix.totalAspect <= 39) {
                $scope.matrix.totalAspectDescription = 'MEDIO';
            } else if ($scope.matrix.totalAspect >= 40) {
                $scope.matrix.totalAspectDescription = 'ALTO';
            } else {
                $scope.matrix.emergencyNiaDescription = '';
            }
        }

        var calculateEmergencyNia = function () {

            var emergencyConditionIn = $scope.matrix.emergencyConditionIn ? parseFloat($scope.matrix.emergencyConditionIn.value) : 0;
            var emergencyConditionEx = $scope.matrix.emergencyConditionEx ? parseFloat($scope.matrix.emergencyConditionEx.value) : 0;
            var emergencyConditionPr = $scope.matrix.emergencyConditionPr ? parseFloat($scope.matrix.emergencyConditionPr.value) : 0;
            var emergencyConditionRe = $scope.matrix.emergencyConditionRe ? parseFloat($scope.matrix.emergencyConditionRe.value) : 0;
            var emergencyConditionRv = $scope.matrix.emergencyConditionRv ? parseFloat($scope.matrix.emergencyConditionRv.value) : 0;
            var emergencyConditionSe = $scope.matrix.emergencyConditionSe ? parseFloat($scope.matrix.emergencyConditionSe.value) : 0;
            var emergencyConditionFr = $scope.matrix.emergencyConditionFr ? parseFloat($scope.matrix.emergencyConditionFr.value) : 0;

            $scope.matrix.emergencyNia = (3 * emergencyConditionIn) + (2 * emergencyConditionEx) + emergencyConditionPr + emergencyConditionRe + emergencyConditionRv + emergencyConditionSe + emergencyConditionFr;

            if ($scope.matrix.emergencyNia >= 9 && $scope.matrix.emergencyNia <= 18) {
                $scope.matrix.emergencyNiaDescription = 'BAJO';
            } else if ($scope.matrix.emergencyNia >= 19 && $scope.matrix.emergencyNia <= 28) {
                $scope.matrix.emergencyNiaDescription = 'MEDIO';
            } else if ($scope.matrix.emergencyNia >= 29 && $scope.matrix.emergencyNia <= 36) {
                $scope.matrix.emergencyNiaDescription = 'ALTO';
            } else {
                $scope.matrix.emergencyNiaDescription = '';
            }
        }

    }]);
