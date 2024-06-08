app.controller('PFmanagementModalCtrl',
    function ($scope, $stateParams, $log, $uibModalInstance, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $aside, $document, PFManagementService, $q) {

        $scope.infoBasic = PFManagementService.getInfoBasic();
        $scope.action = PFManagementService.getAction();
        $scope.adviceTypeList = $rootScope.parameters("positiva_fgn_gestpos_advice_type");

        $scope.participationPercentage = 0;
        $scope.totalValues = 0;
        $scope.totalCompliance = 0;
        $scope.totalComplianceExec = 0;
        $scope.taskInfo = {};

        $rootScope.dirty = false;

        var initialize = function () {
            $scope.entity = {
                id: $scope.modalId,
                managementType: null,
                regional: null,
                sectional: null,
                axis: null,
                action: null,
                fgnCode: null,
                activity: null,
                period: null,
                goal: null,
                type: null,
                strategy: null,
                modality: null,
                executionType: null,
                gestposCode: null,
                activityGestpos: null,
                goalCoverage: null,
                goalCompliance: null,
                providesCoverage: null,
                providesCompliance: null,
                task: null,
                consultant: null,
                adviceType: null,
                oldAdviceType: null,
                indicators: { coverage: [], compliance: [] },
                indicatorRelationId: null,
                sectionalConsultantRelationId: null
            };
        };

        initialize();


        $scope.form = {
            submit: function (form) {
                console.log('call to save request')
                console.log(form)
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

                    console.log(firstError)

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
                url: 'api/positiva-fgn-fgn-management/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                SweetAlert.swal("Proceso Exitoso", "Se ha almacenado correctamente la información.", "success");
                $scope.entity = response.data.result;
            }).catch(function(e){
                $log.error(e);
                SweetAlert.swal("Error de guardado", e.data.message , "error");
            });
        };

        $scope.onLoadRecord = function (){
            if($scope.entity.id) {
                var req = {
                    id: $scope.entity.id,
                    period: $scope.infoBasic.period.value
                };

                $http({
                    method: 'GET',
                    url: 'api/positiva-fgn-fgn-management/get',
                    params: req
                })
                .catch(function(e, code){
                    SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información.", "error");
                })
                .then(function (response) {
                    $timeout(function(){
                        $scope.entity = response.data.result;
                        $scope.entity.goalCompliance = parseInt($scope.entity.goalCompliance);
                        $scope.entity.goalCoverage = parseInt($scope.entity.goalCoverage);
                        $scope.entity.managementType = $scope.action;
                        $scope.calculeInfoCoverage();
                        $scope.calculeInfoCompliance();
                    });
                });
            }
        }
        $scope.onLoadRecord();

        $scope.onFinish = function() {
            if (($scope.Form.$dirty || $rootScope.dirty) && !$scope.Form.$submitted) {
                $scope.saveBeforeClose().then(function (response) {
                    if (response) {
                        $uibModalInstance.close(1);
                    }
                });
            } else {
                $uibModalInstance.close(1);
            }
        }

        $scope.showCompliance = function() {
            return true;
        }

        $scope.showCoverage = function() {
            return true;
        }

        $scope.valideGoalPrograming = function(item) {
            if(parseInt(item.programmed) > 0 && parseInt(item.programmed) > parseInt($scope.entity.goalCompliance)) {
                item.programmed = 0;
            }
        }

        $scope.valideGoalExecution = function(item) {
            if(parseInt(item.hourExecuted) > 0 && parseInt(item.hourExecuted) > parseInt($scope.entity.goalCompliance)) {
                item.hourExecuted = 0;
            }
        }

        $scope.calculeInfoCoverage = function () {
            $scope.totalValues = 0;
            $scope.participationPercentage = 0;
            if(!$scope.entity.indicators.coverage) {
                 return;
            }
            $scope.entity.indicators.coverage.map(function(val) {
                if($scope.action=="programming") {
                    $scope.totalValues += (parseInt(val.call) > 0 ? parseInt(val.call) : 0);
                } else {
                    $scope.totalValues += (parseInt(val.assistants) > 0 ? parseInt(val.assistants) : 0);
                    $scope.participationPercentage += (parseInt(val.call) > 0 ? parseInt(val.call) : 0);
                }
            });

            if($scope.action=="execution") {
                $scope.participationPercentage = parseFloat((parseInt($scope.totalValues) / parseInt($scope.participationPercentage)) * 100).toFixed(0)
            }
        }

        $scope.openCall = function(task) {
            $scope.taskInfo = task;
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/positiva-fgn/management/poblation_modal.htm",
                placement: 'right',
                windowTopClass: 'top-modal',
                size: 'lg',
                backdrop: 'static',
                controller: "PFmanagementPoblationModalCtrl",
                scope: $scope,
                resolve: {
                    dataSource: {
                        goalCoverage: $scope.entity.goalCoverage
                    }
                }
            });

            modalInstance.result.then(function (result) {
                task.poblation = result;
                if ($scope.action === 'execution') {
                    task.call = result.call;
                    task.assistants = result.assistants;
                } else {
                    task.call = 0;
                    task.assistants = 0;
                    result.map(function(val){
                        task.call += parseInt(val.call);
                        task.assistants += parseInt(val.assistants);
                    });
                }

                $scope.calculeInfoCoverage();
            });
        }

        $scope.calculeInfoCompliance = function () {
            $scope.totalCompliance = 0;
            $scope.totalComplianceExec = 0;
            if(!$scope.entity.indicators.compliance){
                return;
            }

            $scope.entity.indicators.compliance.map(function(val) {
                $scope.totalCompliance += (parseInt(val.programmed) > 0 ? parseInt(val.programmed) : 0);
                if($scope.action=="execution") {
                    $scope.totalComplianceExec += (parseInt(val.executed) > 0 ? parseInt(val.executed) : 0);
                }
            });
        }


        $scope.isActiveAdviceType = function(value) {
            if($scope.entity.adviceType == value){
                return true;
            }
            return false;
        }


        $scope.onAddComplianceLog = function (task) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/positiva-fgn/management/indicator-logs/compliance_modal.htm",
                placement: 'right',
                windowTopClass: 'top-modal',
                size: 'lg',
                backdrop: true,
                controller: 'positivafgnManagemenIndicatorLogstCtrlModalInstanceSide',
                scope: $scope,
                resolve: {
                    dataSource: {
                        action: $scope.action,
                        task: task,
                        period: $scope.entity.period
                    }
                }
            });

            modalInstance.result.then(function (response) {
                task.executed = response.totalExecuted;
                task.hourExecuted = response.totalHourExecuted;
            });
        };


        $scope.saveBeforeClose = function () {
            return $q(function(resolve) {
                setTimeout(function () {
                    SweetAlert.swal({
                        title: "¿Deseas salir sin guardar?",
                        text: "Tienes cambios realizados sin guardar.",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "Salir sin guardar",
                        cancelButtonText: "Cancelar",
                        closeOnConfirm: true,
                        closeOnCancel: true
                    }, function (isConfirm) {
                        resolve(isConfirm);
                    });
                }, 200);
            });
        }

    });



    app.controller('PFmanagementPoblationModalCtrl',
    function ($scope, $stateParams, $log, $uibModalInstance, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder,
              $state, $rootScope, $timeout, $http, SweetAlert, $aside, PFManagementService, $localStorage, $compile, dataSource) {

        $scope.activityStateList = $rootScope.parameters("positiva_fgn_gestpos_activity_states");
        $scope.action = PFManagementService.getAction();
        $scope.totalValues = 0;
        $scope.programmed = [];

        function defineDateLimit() {
            var period = $scope.entity.period.toString();
            var year  = period.substring(0, 4);
            var month = period.substring(4);

            $scope.datePickerConfig = {
                culture: "es-CO",
                format: "dd/MM/yyyy",
                min: new Date(year, month-1, 1),
                max: new Date(year, month, 0)
            };
        }

        defineDateLimit();

        $scope.onClose = function () {
            if ($scope.action === 'execution') {
                updateTotals()
            } else {
                $rootScope.dirty = $scope.Form.$dirty;
                $uibModalInstance.close($scope.entity.coverages);
            }
        }

        $scope.initialize = function () {
            $scope.entity = {
                id: null,
                managementType: $scope.action,
                indicatorId: $scope.taskInfo.id,
                goalCoverage: dataSource.goalCoverage,
                identityGroup: null,
                date: null,
                activityState: null,
                coverages: []
            }
        }

        $scope.initialize();


        $scope.form = {
            submit: function (form) {
                $scope.Form = form;

                if (form.$valid) {
                    save();
                    return;
                }

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
            },
            reset: function () {
                $scope.entity.id = null;
                $scope.entity.date = '';
                $scope.entity.activityState = null;
                $scope.entity.coverages = angular.copy($scope.programmed);
                $scope.Form.$setPristine(true);
            }
        };

        var save = function () {
            var data = JSON.stringify($scope.entity);
            var req = {
                data: Base64.encode(data)
            };
            return $http({
                method: 'POST',
                url: 'api/positiva-fgn-fgn-management/population/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $scope.form.reset();
                $scope.reloadData();
                var data = response.data.result;
                $scope.entity.coverages = angular.copy(data);
                $scope.programmed = angular.copy(data);
                SweetAlert.swal("Proceso Exitoso", "Se ha almacenado correctamente la información.", "success");
                $rootScope.dirty = true;
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", e.data.message, "error");
            });
        };


        $scope.onLoadRecord = function (first) {
            if (first && $scope.taskInfo.poblation) {
                $scope.entity.coverages = $scope.taskInfo.poblation;
                $scope.programmed = angular.copy($scope.taskInfo.poblation);
                return;
            }

            var req = {
                indicatorId: $scope.entity.indicatorId,
                date: $scope.entity.date,
                action: $scope.action
            };

            var url = first ? "api/positiva-fgn-fgn-management/getPoblationBase" : "api/positiva-fgn-fgn-management/getPoblation";

            $http({
                method: 'GET',
                url: url,
                params: req
            }).catch(function (e, code) {
                SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información.", "error");
            }).then(function (response) {
                var data = response.data.result;
                if (first) {
                    $scope.entity.coverages = angular.copy(data);
                    $scope.programmed = angular.copy(data);
                } else {
                    $scope.entity.activityState = data.activityState;
                    $scope.entity.coverages = data.coverages;
                    $scope.entity.date = data.date;
                }
            });
        }

        $scope.onLoadRecord(true);

        $scope.onFinish = function() {
            $uibModalInstance.dismiss('cancel');
        }

        $scope.dtInstanceIndicatorPopulationCallback = function (instance) {
            $scope.dtInstanceIndicatorPopulation = instance;
        };

        $scope.dtInstanceIndicatorPopulation = {};
        $scope.dtOptionsIndicatorPopulation = DTOptionsBuilder.newOptions()
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                data: function (d) {
                    d.indicatorId = $scope.entity.indicatorId;
                    return JSON.stringify(d);
                },
                url: 'api/positiva-fgn-fgn-management/population',
                type: 'POST',
                beforeSend: function () { },
                complete: function () { }
            })
            .withDataProp('data')
            .withOption('order', [[0, 'desc']])
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                return true;
            })
            .withOption('fnDrawCallback', function () {
                loadRow();
            })
            .withOption('language', {})
            .withPaginationType('full_numbers')
            .withOption('createdRow', function (row, data, dataIndex) {
                $compile(angular.element(row).contents())($scope);
            });

        $scope.dtColumnsIndicatorPopulation = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 120).notSortable()
                .renderWith(function (data) {
                    var actions = "";
                    var disabled = ""
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" ' +
                        ' data-id="' + data.indicatorId + '" data-date="' + data.date + '" ' +
                        '"> <i class="fa fa-edit"></i></a> ';

                    var drop = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.indicatorId + '" data-date="' + data.date + '" ' + disabled + ' >' +
                        '   <i class="fa fa-trash"></i></a> ';

                    actions += editTemplate;
                    actions += drop;
                    return actions;
                }),
            DTColumnBuilder.newColumn('date').withTitle("Fecha").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('activityState').withTitle("Estado").withOption('width', 200).withOption('defaultContent', ''),
        ];

        var loadRow = function () {
            $("#dtIndicatorPopulation a.editRow").on("click", function () {
                $scope.entity.indicatorId = $(this).data("id");
                $scope.entity.date = $(this).data("date");
                $scope.onLoadRecord(false);
            });

            $("#dtIndicatorPopulation a.delRow").on("click", function () {
                $scope.entity.indicatorId = $(this).data("id");
                $scope.entity.date = $(this).data("date");
                onRemove();
            });
        };

        $scope.reloadData = function () {
            $scope.dtInstanceIndicatorPopulation.reloadData();
        };


        var onRemove = function () {
            SweetAlert.swal({
                title: "Está seguro?",
                text: "Eliminará el registro seleccionado.",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Si, eliminar!",
                cancelButtonText: "No, continuar!",
                closeOnConfirm: true,
                closeOnCancel: true
            }, function (isConfirm) {
                if (isConfirm) {
                    var req = {
                        indicatorId: $scope.entity.indicatorId,
                        date: $scope.entity.date,
                    };

                    $http({
                        method: 'POST',
                        url: 'api/positiva-fgn-fgn-management/population/delete',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        data: $.param(req)
                    }).then(function (response) {
                        SweetAlert.swal("Proceso Exitoso", "Se ha eliminado correctamente el registro.", "success");
                        $scope.onLoadRecord(true);
                        $rootScope.dirty = true;
                    }).catch(function (response) {
                        SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro por favor intentelo de nuevo", "error");
                    }).finally(function () {
                        $scope.reloadData();
                    });
                }
            });
        }

        function updateTotals() {
            var data = JSON.stringify({ id: $scope.entity.indicatorId });
            var req = {
                data: Base64.encode(data)
            };

            $http({
                method: 'POST',
                url: 'api/positiva-fgn-fgn-management/getPoblationBase/totals',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                var data = response.data.result;
                $scope.entity.coverages.call = data.totalCalls;
                $scope.entity.coverages.assistants = data.totalAssistants;
                $uibModalInstance.close($scope.entity.coverages);
            }).catch(function (response) {
                SweetAlert.swal("Error", "Se ha presentado un error al cargar la información", "error");
            });
        }

    });
