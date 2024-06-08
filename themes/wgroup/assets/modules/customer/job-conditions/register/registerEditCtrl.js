'use strict';
/**
 * controller for Customers
 */
app.controller('jobConditionsRegisterEditCtrl',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
        $rootScope, $timeout, $http, SweetAlert, ModuleListService, $aside, $localStorage, jobConditionRegisterNavigationService) {

        $scope.isView = jobConditionRegisterNavigationService.isViewRegisterEdit();
        $scope.dailyList = [];
        $scope.periods = [];
        $scope.filter = { selectedMonth: false }
        $scope.hasAutoevaluations = false;

        var initialize = function () {
            $scope.entity = {
                id: $scope.$parent.currentId || 0,
                customerId: $stateParams.customerId,
                employee: null,
                boss: null,
                period: null,
                createdBy: null
            };
        };
        initialize();


        $scope.form = {
            submit: function (form) {
                $scope.Form = form;

                if (form.$valid) {
                    save();
                    return;
                }

                var field = null,
                    firstError = null;
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
                $scope.Form.$setPristine(true);
                initialize();
            }
        };

        var save = function () {
            var data = JSON.stringify($scope.entity);
            var req = {
                data: Base64.encode(data)
            };

            return $http({
                method: 'POST',
                url: 'api/customer-jobconditions/save',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                $scope.entity = response.data.result;
                SweetAlert.swal("Proceso Exitoso", "Se ha almacenado correctamente la información.", "success");
                jobConditionRegisterNavigationService.setJobConditionId($scope.entity.id);
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error al guardar", e.data.message, "error");
            });
        };

        $scope.onBack = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "edition", 0);
            }
        }

        var load = function () {
            if ($scope.entity.id == 0) {
                $scope.entity.employee = jobConditionRegisterNavigationService.getEmployeeTemp();
                return;
            }

            var req = {};
            var data = JSON.stringify({ id: $scope.entity.id });
            req.data = Base64.encode(data);

            return $http({
                method: 'post',
                url: 'api/customer-jobconditions/show',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                $scope.entity = response.data.result;
                getList();

            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error al consultar la información.", e.data.message, "error");
            });
        }
        load();


        var getList = function () {
            if ($scope.entity.id == 0) {
                return;
            }

            var entities = [
                { name: 'periods', jobConditionId: $scope.entity.id },
            ];

            ModuleListService.getDataList('/customer-jobconditions/config', entities)
                .then(function (response) {
                    $scope.periods = response.data.result.periods;

                    $scope.entity.period = $scope.periods.length ? $scope.periods[0] : null;
                    $scope.filter.selectedMonth = true;

                    $scope.reloadData();
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        $scope.onSearchEmployee = function () {
            if ($rootScope.isCustomerUser()) {
                return;
            }

            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/common/modals/employee_list_modal.htm",
                placement: 'right',
                windowTopClass: 'top-modal',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideJobConditionsEmployeeListCtrl',
                scope: $scope,
            });

            modalInstance.result.then(function (response) {
                $scope.entity.employee = response.entity;
            });
        };

        $scope.onSearchBoss = function () {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/common/modals/employee_list_modal.htm",
                placement: 'right',
                windowTopClass: 'top-modal',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideJobConditionsEmployeeListCtrl',
                scope: $scope,
            });

            modalInstance.result.then(function (response) {
                $scope.entity.boss = response.entity;
            });
        };


        $scope.onDoSelfAssessment = function () {
            $scope.$parent.navToSection("evaluation", false, 0);
        };


        var storeDatatable = 'jobConditionsEvaluationCtrl-' + window.currentUser.id;
        $scope.dtInstanceJobConditionsSelfEvaluationsCallback = function (instance) {
            $scope.dtInstanceJobConditionsSelfEvaluations = instance;
        };
        $scope.dtOptionsJobConditionsSelfEvaluations = DTOptionsBuilder.newOptions()
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                data: function (d) {
                    d.jobConditionId = $scope.entity.id;
                    d.period = $scope.entity.period ? $scope.entity.period.date : null;
                    return JSON.stringify(d);
                },
                url: 'api/customer-jobconditions/evaluation/',
                type: 'POST',
                beforeSend: function () { },
                complete: function () { }
            })
            .withDataProp('data')
            .withOption('stateSave', true)
            .withOption('stateSaveCallback', function (settings, data) {
                $localStorage[storeDatatable] = data;
            })
            .withOption('stateLoadCallback', function () {
                return $localStorage[storeDatatable];
            })
            .withOption('order', [
                [0, 'desc']
            ])
            .withOption('serverSide', true).withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                return $scope.filter.selectedMonth;
            })
            .withOption('fnDrawCallback', function (settings) {
                $scope.hasAutoevaluations = settings._iRecordsTotal > 0;
                loadRow();
            })
            .withOption('language', {})
            .withPaginationType('full_numbers')
            .withOption('createdRow', function (row, data, dataIndex) {
                $compile(angular.element(row).contents())($scope);
            });


        $scope.dtColumnsJobConditionsSelfEvaluations = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 120).notSortable()
                .renderWith(function (data) {
                    var actions = "";
                    var disabled = ""

                    actions += '<a class="btn btn-info btn-xs viewRow lnk" href="#"  uib-tooltip="Ver" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-eye"></i></a> ';

                    if ($rootScope.isCustomerUser()) {
                        if (data.createdBy == $rootScope.currentUser().id) {
                            actions += '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                                '   <i class="fa fa-edit"></i></a> ';
                        }
                    } else {
                        if (!$scope.isView) {
                            actions += '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                                '   <i class="fa fa-edit"></i></a> ';
                        }
                    }

                    return actions;
                }),
            DTColumnBuilder.newColumn('date').withTitle("Fecha").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('workmodel').withTitle("Modelo de Trabajo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('location').withTitle("Lugar de Trabajo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('occupation').withTitle("Cargo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('workplace').withTitle("Puesto de Trabajo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('risk').withTitle("Riesgo").withOption('width', 120)
                .renderWith(function (data) {
                    var type;
                    if (data == "Alto") {
                        type = "label-danger";
                    } else if (data == "Medio") {
                        type = "label-warning";
                    } else if (data == "Bajo") {
                        type = "label-success";
                    } else {
                        type = "info";
                    }

                    if (data) {
                        return '<span class="label ' + type + '">' + data + '</span>';
                    } else {
                        return "";
                    }

                }),
            DTColumnBuilder.newColumn('state').withTitle("Estado").withOption('width', 120)
                .renderWith(function (data) {
                    var type = "label-warning";
                    if (data == "Cerrado") {
                        type = "label-success";
                    }

                    return '<span class="label ' + type + '">' + data + '</span>';
                }),
        ];

        var loadRow = function () {
            $("#dtJobConditionsSelfEvaluations a.viewRow").on("click", function () {
                var id = $(this).data("id");
                $scope.$parent.navToSection("evaluation", true, id);
            });

            $("#dtJobConditionsSelfEvaluations a.editRow").on("click", function () {
                var id = $(this).data("id");
                $scope.$parent.navToSection("evaluation", false, id);
            });
        };

        $scope.reloadData = function () {
            $scope.dtInstanceJobConditionsSelfEvaluations.reloadData();
        };

        $scope.onFilterByPeriod = function () {
            $scope.reloadData();
        };

        $scope.onClearFilter = function () {
            $scope.entity.period = null;
            $scope.reloadData();
        };

        $scope.onClear = function () {
            $scope.entity.boss = null;
        }

        $scope.hasChangeEmployee = function () {
            if ($rootScope.isCustomerUser()) {
                return false;
            }

            return !$scope.hasAutoevaluations;
        };

    });