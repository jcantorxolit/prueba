'use strict';
/**
 * controller for IndicatorLogs
 */
app.controller('positivafgnManagemenIndicatorLogstCtrlModalInstanceSide',
    function ($rootScope, $stateParams, $scope, $uibModalInstance, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder,
              $localStorage, $log, $timeout, SweetAlert, $http, $compile, dataSource) {

        $scope.activityStateList = $rootScope.parameters("positiva_fgn_gestpos_activity_states");

        var initialize = function () {
            $scope.entity = {
                id: null,
                indicatorId: dataSource.task.id,
                code: dataSource.task.code,
                name: dataSource.task.name,
                type: dataSource.task.type,
                date: null,
                activityState: null,
                programmed: dataSource.task.programmed,
                executed: 0,
                hourProgrammed: dataSource.task.hourProgrammed,
                hourExecuted: 0,
                totalExecuted: 0,
                totalProgrammed: 0,
                satisfactionIndicator45: null,
                satisfactionIndicator123: null,
                observation: null
            }
        }

        initialize();

       $scope.onClose = function () {
            updateTotals();
        }

        $scope.onFinish = function () {
            $uibModalInstance.dismiss('cancel');
        }

        function defineDateLimit() {
            var period = dataSource.period.toString();
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
                url: 'api/positiva-fgn-fgn-management/compliance-logs/store',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $scope.form.reset();
                $scope.reloadData();
                SweetAlert.swal("Proceso Exitoso", "Se ha almacenado correctamente la información.", "success");
                $rootScope.dirty = true;
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", e.data.message, "error");
            });
        };


        $scope.dtInstanceIndicatorLogsComplianceModal = {};
        $scope.dtOptionsIndicatorLogsComplianceModal = DTOptionsBuilder.newOptions()
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                data: function (d) {
                    d.indicatorId = $scope.entity.indicatorId
                    return JSON.stringify(d);
                },
                url: 'api/positiva-fgn-fgn-management/compliance-logs',
                type: 'POST',
                beforeSend: function () {
                },
                complete: function () {
                }
            })
            .withDataProp('data')
            .withOption('order', [[0, 'desc']])
            .withOption('serverSide', true).withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                return true;
            })
            .withOption('fnDrawCallback', function () {
                loadRow();
            })
            .withOption('language', {})
            .withPaginationType('full_numbers')
            .withOption('createdRow', function (row) {
                $compile(angular.element(row).contents())($scope);
            });


        $scope.dtColumnsIndicatorLogsModal = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 120).notSortable()
                .renderWith(function (data) {
                    var actions = "";
                    var disabled = ""
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" ' + 'data-id="' + data.id + '" ' +
                        '> <i class="fa fa-edit"></i></a> ';

                    var drop = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-trash"></i></a> ';

                    actions += editTemplate;
                    actions += drop;
                    return actions;
                }),

            DTColumnBuilder.newColumn('date').withTitle("Fecha").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('activityState').withTitle("Estado").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('executed').withTitle("Ejecutado").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('hour_executed').withTitle("Horas Ejecutadas").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('satisfactionIndicator45').withTitle("Calif. 4 y 5").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('satisfactionIndicator123').withTitle("Calif. 1,2 y 3").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('observation').withTitle("Observación").withOption('width', 200).withOption('defaultContent', ''),
        ];


        var loadRow = function () {
            $("#dtIndicatorLogsModal a.editRow").on("click", function () {
                var id = $(this).data("id");
                load(id);
            });

            $("#dtIndicatorLogsModal a.delRow").on("click", function () {
                var id = $(this).data("id");
                onRemove(id);
            });
        };


        $scope.reloadData = function () {
            $scope.dtInstanceIndicatorLogsComplianceModal.reloadData();
        };


        var onRemove = function (id) {
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
                },
                function (isConfirm) {
                    if (isConfirm) {
                        var req = {};
                        req.id = id;
                        $http({
                            method: 'POST',
                            url: 'api/positiva-fgn-fgn-management/compliance-logs/delete',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            data: $.param(req)
                        }).then(function () {
                            SweetAlert.swal("Proceso Exitoso", "Se ha eliminado correctamente el registro.", "success");
                            $rootScope.dirty = true;
                        }).catch(function () {
                            SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro por favor intentelo de nuevo", "error");
                        }).finally(function () {
                            $scope.reloadData();
                        });

                    }
                });
        }


        function load(id) {
            var data = JSON.stringify({ id: id });
            var req = {
                data: Base64.encode(data)
            };

            $http({
                method: 'POST',
                url: 'api/positiva-fgn-fgn-management/compliance-logs/show',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                var data = response.data.result;

                $scope.entity.id = data.id;
                $scope.entity.date = data.date;
                $scope.entity.activityState = data.activityState;
                $scope.entity.executed = data.executed;
                $scope.entity.hourExecuted = data.hourExecuted;
                $scope.entity.observation = data.observation;
                $scope.entity.satisfactionIndicator45 = data.satisfactionIndicator45;
                $scope.entity.satisfactionIndicator123 = data.satisfactionIndicator123;

            }).catch(function () {
                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error al cargar la información", "error");
            });
        }


        function updateTotals() {
            var data = JSON.stringify({ id: $scope.entity.indicatorId });
            var req = {
                data: Base64.encode(data)
            };

            $http({
                method: 'POST',
                url: 'api/positiva-fgn-fgn-management/compliance-logs/totals',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                var data = response.data.result;
                $uibModalInstance.close({
                    totalExecuted: data.totalExecuted,
                    totalHourExecuted: data.totalHourExecuted
                });

            }).catch(function () {
                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error al cargar la información", "error");
            });
        }

    });
