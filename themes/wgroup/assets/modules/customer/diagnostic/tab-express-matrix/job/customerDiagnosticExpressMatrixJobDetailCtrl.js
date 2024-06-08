'use strict';
/**
 * controller for Customers
 */
app.controller('customerDiagnosticExpressMatrixJobDetailCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert',
    '$document', '$location', '$translate', '$aside', 'ListService', 'ExpressMatrixService', '$filter', '$sce',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, $location, $translate, $aside, ListService, ExpressMatrixService, $filter, $sce) {

        $scope.htmlTooltip = $sce.trustAsHtml('Actividad Rutinaria: Actividad que forma parte de un proceso de la organización, que se ha planificado y es estandarizable.<br><br>Actividad No Rutinaria: Actividad que no se ha planificado ni estandarizado dentro de un proceso de la organización o actividad que la organización determine como no rutinaria por  su baja frecuencia de ejecución.');
        $scope.isLoaded = false;
        $scope.isNavigationButtonsDisabled = true;
        $scope.isContinueButtonsDisabled = true;
        $scope.isNavigationButtonsClicked = false;

        var onDestroyWizardNavigate$ = $rootScope.$on('wizardNavigate', function (event, args) {
            if (args.newValue == 1) {
                $scope.isBackNavigationVisible = ExpressMatrixService.getWorkplaceId() != null
            }
        });

        var onDestroyLoadWorkplace$ = $rootScope.$on('loadWorkplace', function (event, args) {

            $scope.workplace = args.newValue;

            $scope.availableActivityList = $scope.workplace.availableActivityList;

            var $index = $scope.workplace.processList.findIndex(function(element) {
                return element.isFullyConfigured == 0;
            });

            $timeout( function() {
                $scope.tabActive = $index != -1 ? $index : 0;
                onLoadRecord($scope.workplace.processList[$scope.tabActive].id);
            }, 100 );
            //$scope.onSelectProcess($scope.workplace.processList[$scope.tabActive]);

        });

        $scope.$on("$destroy", function() {
            onDestroyWizardNavigate$();
            onDestroyLoadWorkplace$();
        });

        var init = function() {
            $scope.entity = {
                id: 0,
                customerId: $stateParams.customerId,
                name: null,
                jobList: []
            }
        }

        init()

        var onLoadRecord = function (id) {
            $scope.isLoaded = false;
            if (id) {
                var req = {
                    id: id
                };

                $http({
                    method: 'GET',
                    url: 'api/customer-config-process-express-relation/get',
                    params: req
                })
                .catch(function (e, code) {

                })
                .then(function (response) {
                    $scope.isLoaded = true;
                    $scope.entity = response.data.result;
                    validateProcessToDuplicate();
                    validateNavigationButtonIsDisable();
                    validateContinueButtonIsDisable();
                }).finally(function () {

                });
            }
        }

        var validateProcessToDuplicate = function() {
            $scope.processListToDuplicate = $scope.workplace.processList.filter(function(element, index) {
                return element.isFullyConfigured == 1 && index < $scope.tabActive;
            });
        }


        var validateNavigationButtonIsDisable = function() {
            $scope.isNavigationButtonsDisabled = $scope.entity.jobList.length == 0;
            angular.forEach($scope.entity.jobList, function (job, key) {
                if (!$scope.isNavigationButtonsDisabled) {
                    $scope.isNavigationButtonsDisabled = job.activityList === undefined || job.activityList == null || (!job.activityList.length > 0);
                }
            });

            $scope.workplace.processList[$scope.tabActive].isFullyConfigured = $scope.isNavigationButtonsDisabled ? 0 : 1;
        }

        var validateContinueButtonIsDisable = function() {
            $scope.isContinueButtonsDisabled = $scope.workplace.processList.length == 0;
            angular.forEach($scope.workplace.processList, function (process, key) {
                if (!$scope.isContinueButtonsDisabled) {
                    $scope.isContinueButtonsDisabled = (!process.isFullyConfigured == 1);
                }
            });

        }

        var updateAvailableJobList = function(entity) {
            angular.forEach(entity.jobList, function(item) {
                var $index = $scope.availableJobList.findIndex(function(element) {
                    return element.name.trim().toUpperCase() == item.name.trim().toUpperCase();
                })

                if ($index == -1) {
                    $scope.availableJobList.push({id: 0, name: item.name});
                }

                updateAvailableActivityList(item);
            })

            $scope.availableJobList = $filter('orderBy')($scope.availableJobList, 'name');
        }

        var updateAvailableActivityList = function(entity) {
            angular.forEach(entity.activityList, function(item) {
                var $index = $scope.availableActivityList.findIndex(function(element) {
                    return element.name.trim().toUpperCase() == item.name.trim().toUpperCase();
                })

                if ($index == -1) {
                    $scope.availableActivityList.push({id: 0, name: item.name});
                }
            })

            $scope.availableActivityList = $filter('orderBy')($scope.availableActivityList, 'name');
        }

        $scope.onSelectProcess = function(process) {
            //onLoadRecord(process.id);
            console.log(process)
            $timeout( function(){
                onLoadRecord(process.id);
            }, 100 );
        }

        $scope.onAddJob = function () {
            $timeout(function () {
                if ($scope.entity.jobList == null) {
                    $scope.entity.jobList = [];
                }
                $scope.entity.jobList.push({
                        id: 0,
                        customerId: $stateParams.customerId,
                        processExpressRelationId: $scope.entity.id,
                        jobExpressId: 0,
                        name: null,
                        isActive: true
                });
                validateNavigationButtonIsDisable();
                validateContinueButtonIsDisable();
            });
        };

        $scope.onRemoveJob = function (index) {
            SweetAlert.swal({
                    title: "¿Está seguro de eliminar el registro seleccionado?",
                    text: "Esta acción no se podrá deshacer.",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Eliminar",
                    cancelButtonText: "Cancelar",
                    closeOnConfirm: true,
                    closeOnCancel: true
                },
                function (isConfirm) {
                    if (isConfirm) {
                        $timeout(function () {
                            // eliminamos el registro en la posicion seleccionada
                            var data = $scope.entity.jobList[index];

                            if (data.id != 0) {
                                var req = {
                                    id: data.id
                                };
                                $http({
                                    method: 'POST',
                                    url: 'api/customer-config-job-express-relation/delete',
                                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                    data: $.param(req)
                                }).then(function (response) {
                                    swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                                    $scope.entity.jobList.splice(index, 1);

                                    if ($scope.entity.jobList.length == 0) {
                                        $scope.workplace.processList[$scope.tabActive].isFullyConfigured = 0;
                                    }

                                    validateNavigationButtonIsDisable();
                                    validateContinueButtonIsDisable();
                                }).catch(function (e) {
                                    $log.error(e);
                                    SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                                }).finally(function () {

                                });
                            } else {
                                $scope.entity.jobList.splice(index, 1);
                                validateNavigationButtonIsDisable();
                                validateContinueButtonIsDisable();
                            }
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        }

        $scope.onAddActivity = function ($job) {
            $timeout(function () {
                if ($job.activityList == null) {
                    $job.activityList = [];
                }
                $job.activityList.push({
                        id: (Math.floor(Math.random() * Math.floor(10000)) + 1) * -1,
                        customerId: $stateParams.customerId,
                        jobExpressRelationId: $job.id,
                        activityExpressId: 0,
                        name: null,
                        isRoutine: '0'
                });

                validateNavigationButtonIsDisable();
                validateContinueButtonIsDisable();
            });
        };

        $scope.onRemoveActivity = function ($job, $index) {
            SweetAlert.swal({
                    title: "¿Está seguro de eliminar el registro seleccionado?",
                    text: "Esta acción no se podrá deshacer.",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Eliminar",
                    cancelButtonText: "Cancelar",
                    closeOnConfirm: true,
                    closeOnCancel: true
                },
                function (isConfirm) {
                    if (isConfirm) {
                        $timeout(function () {
                            // eliminamos el registro en la posicion seleccionada
                            var data = $job.activityList[$index];

                            if (data.id > 0) {
                                var req = {
                                    id: data.id
                                };
                                $http({
                                    method: 'POST',
                                    url: 'api/customer-config-activity-express-relation/delete',
                                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                    data: $.param(req)
                                }).then(function (response) {
                                    swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                                    $job.activityList.splice($index, 1);
                                    validateNavigationButtonIsDisable();
                                    validateContinueButtonIsDisable();
                                }).catch(function (e) {
                                    $log.error(e);
                                    SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                                }).finally(function () {

                                });
                            } else {
                                $job.activityList.splice($index, 1);
                                validateNavigationButtonIsDisable();
                                validateContinueButtonIsDisable();
                            }
                        });
                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        }

        $scope.onDuplicate = function(process) {

            var $entity = $scope.workplace.processList[$scope.tabActive]
            $entity.processFromId = process.id;
            $entity.module = 'A';

            var req = {
                data: Base64.encode(JSON.stringify($entity))
            };

            return $http({
                method: 'POST',
                url: 'api/customer-config-process-express-relation/copy',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    //SweetAlert.swal("Registro", "La información ha sido duplicada satisfactoriamente", "success");
                    $scope.entity = response.data.result;
                    updateProcessInList(response.data.result, $scope.tabActive);
                    validateNavigationButtonIsDisable();
                    validateContinueButtonIsDisable();
                    validateProcessToDuplicate();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });
        }

        $scope.onBack = function(form) {
            console.log(form);
            ExpressMatrixService.setIsBackInNavigation(true);
            $rootScope.$emit('wizardGoTo', { newValue: 0 });
            if (form.$dirty) {
                save(false, $scope.tabActive);
            }
        }

        $scope.onContinue = function(form) {
            console.log(form);
            if (form.$dirty) {
                save(false, $scope.tabActive);
            }
            $rootScope.$emit('wizardGoTo', { newValue: 2 });
        }

        $scope.onNext = function(form) {
            console.log(form);
            if (form.$dirty) {
                $scope.isNavigationButtonsClicked = true;
                save(false, $scope.tabActive, 'navigationButtons');
            }

            $timeout( function() {
                $scope.tabActive++;
                onLoadRecord($scope.workplace.processList[$scope.tabActive].id);
            }, 100 );
        }

        $scope.onPrevious = function(form) {
            console.log(form);
            if (form.$dirty) {
                $scope.isNavigationButtonsClicked = true;
                save(false, $scope.tabActive, 'navigationButtons');
            }

            $timeout( function() {
                $scope.tabActive--;
                onLoadRecord($scope.workplace.processList[$scope.tabActive].id);
            }, 100 );
        }

        $scope.onLeavingTab = function($event, $index, $form)
        {
            console.log($event);
            console.log($index);
            console.log($form);
            if ($form.$dirty && !$scope.isNavigationButtonsClicked) {
                save(false, $index, 'tabs');
            }
        }

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
                    save();
                }

            },
            reset: function (form) {

            }
        };

        var save = function (showMessage, index, origin) {
            var req = {};
            var data = JSON.stringify($scope.entity);
            req.data = Base64.encode(data);

            return $http({
                method: 'POST',
                url: 'api/customer-config-process-express-relation/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    showMessage = showMessage  === undefined ? true : showMessage;
                    index = index  === undefined ? true : $scope.tabActive;
                    origin = origin  === undefined ? null : origin;

                    if (origin == 'navigationButtons') {
                        $scope.isNavigationButtonsClicked = false;
                    }

                    if (showMessage) {
                        SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                        $scope.entity = response.data.result;
                    }
                    updateProcessInList(response.data.result, index);
                    updateAvailableJobList(response.data.result);
                    validateProcessToDuplicate();
                });
            }).catch(function (e) {
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });
        };

        var updateProcessInList = function(newProcess, index) {
            $scope.workplace.processList[index].isFullyConfigured = newProcess.isFullyConfigured;
        }


    }]);
