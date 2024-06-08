'use strict';
/**
 * controller for associatedTaskListCtrl
 */
app.controller('associatedTaskListCtrl',
    function($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $localStorage, $compile, toaster, $state, $rootScope, $timeout, $http, $aside, SweetAlert) {

        $scope.entity = {
            showDependents: false
        }

        var log = $log;
        var storeDatatable = 'associatedTaskListCtrl-' + window.currentUser.id;
        $scope.dtInstanceAssociatedtask = {};
        $scope.dtOptionsAssociatedtask = DTOptionsBuilder.newOptions()
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                data: function(d) {
                    d.gestposId = $stateParams.gestposId;
                    d.showDependents = $scope.entity.showDependents;
                    return JSON.stringify(d);
                },
                url: 'api/positiva-fgn-gestpos-activity-associated-task',
                type: 'POST',
                beforeSend: function() {},
                complete: function() {}
            })
            .withDataProp('data')
            .withOption('stateSave', true)
            .withOption('stateSaveCallback', function(settings, data) {
                $localStorage[storeDatatable] = data;
            })
            .withOption('stateLoadCallback', function() {
                return $localStorage[storeDatatable];
            })
            .withOption('order', [])
            .withOption('serverSide', true).withOption('processing', true)
            .withOption('fnPreDrawCallback', function() {
                return true;
            })
            .withOption('fnDrawCallback', function() {
                loadRow();
            })
            .withOption('language', {})
            .withPaginationType('full_numbers')
            .withOption('createdRow', function(row, data, dataIndex) {
                $compile(angular.element(row).contents())($scope);

            });;

        $scope.dtColumnsAssociatedtask = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 90).notSortable()
            .renderWith(function(data) {
                var actions = "";
                var disabled = "";

                if (data.type == "Principal") {
                    var drop = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-trash"></i></a> ';
                    actions += drop;
                }
                return actions;

            }),

            DTColumnBuilder.newColumn('number').withTitle("id").withOption('width', 200).notSortable().withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('name').withTitle("Tarea").withOption('width', 200).notSortable().withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('type').withTitle("Tipo").withOption('width', 200).notSortable().withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('mainTask').withTitle("Tarea Principal").withOption('width', 200).notSortable().withOption('defaultContent', ''),
        ];

        var loadRow = function() {
            $("#dtAssociatedtask a.delRow").on("click", function() {
                var id = $(this).data("id");
                onRemove(id);
            });

        };

        $scope.reloadData = function() {
            $scope.dtInstanceAssociatedtask.reloadData();
        };

        $scope.onViewDependents = function() {
            $scope.reloadData();
        }

        var onRemove = function(id) {
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
                function(isConfirm) {
                    if (isConfirm) {
                        var req = {};
                        req.id = id;
                        $http({
                            method: 'POST',
                            url: 'api/positiva-fgn-gestpos-activity-associated-task/delete',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            data: $.param(req)
                        }).then(function(response) {
                            SweetAlert.swal("Proceso Exitoso", "Se ha eliminado correctamente el registro.", "success");
                        }).catch(function(response) {
                            SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro por favor intentelo de nuevo", "error");
                        }).finally(function() {
                            $scope.reloadData();
                        });

                    }
                });
        }

        $scope.onAddTask = function() {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/positiva-fgn/gestpos/activity/associated-task/add_task.htm",
                placement: 'right',
                backdrop: 'static',
                size: 'lg',
                controller: 'ModalAddTaskCtrl',
                scope: $scope
            });
            modalInstance.result.then(function() {
                $scope.reloadData();
            });
        }

        $scope.$on('realoadAssociatedTask', function(event, data) {
            $scope.reloadData();
        });

    });

app.controller('ModalAddTaskCtrl', function($rootScope, $stateParams, $scope, $uibModalInstance, DTOptionsBuilder, DTColumnBuilder,
    DTColumnDefBuilder, $log, $timeout, SweetAlert, $http, $compile) {


    var initialize = function() {
        $scope.entity = {
            taskId: 0,
            gestposId: $stateParams.gestposId,
            showDependents: false,
            name: null
        }
    }
    initialize();


    var save = function() {
        var req = {};
        var data = JSON.stringify($scope.entity);
        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/positiva-fgn-gestpos-activity-associated-task/save',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function(response) {
            $scope.reloadData();
            SweetAlert.swal("Proceso Exitoso", "Se ha almacenado correctamente la información.", "success");
        }).catch(function(e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", e.data.message, "error");
        });
    };

    $scope.onViewDependents = function() {
        $scope.reloadData();
    }

    $scope.dtInstancePositivaFgnCampus = {};
    $scope.dtOptionsPositivaFgnCampus = DTOptionsBuilder.newOptions()
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            data: function(d) {
                d.gestposId = $stateParams.gestposId;
                d.showDependents = $scope.entity.showDependents;
                if ($scope.entity.name) {
                    d.search = { value: $scope.entity.name, regex: false };
                }
                return JSON.stringify(d);
            },
            url: 'api/positiva-fgn-gestpos-activity-associated-task/maintask',
            type: 'POST',
            beforeSend: function() {},
            complete: function() {}
        })
        .withDataProp('data')
        .withOption('bFilter', false)
        .withOption('order', false)
        .withOption('serverSide', true).withOption('processing', true)
        .withOption('fnPreDrawCallback', function() {
            return true;
        })
        .withOption('fnDrawCallback', function() {
            loadRow();
        })
        .withOption('language', {})
        .withPaginationType('full_numbers')
        .withOption('createdRow', function(row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);

        });;

    $scope.dtColumnsPositivaFgnCampus = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 120).notSortable()
        .renderWith(function(data) {
            var actions = "";
            if (data.type == "Principal") {
                var editTemplate = '<a class="btn btn-green btn-xs addRow lnk" href="#" uib-tooltip="Agregar"  data-id="' + data.id + '"  >' +
                    '   <i class="glyphicon glyphicon-plus"></i></a> ';
                actions += editTemplate;
            }
            return actions;
        }),

        DTColumnBuilder.newColumn('number').withTitle("id").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('name').withTitle("Tarea").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('type').withTitle("Tipo").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('mainTask').withTitle("Tarea Principal").withOption('width', 200).withOption('defaultContent', ''),
    ];

    var loadRow = function() {
        $("#dtPositivaFgnCampus a.addRow").on("click", function() {
            var id = $(this).data("id");
            $scope.entity.taskId = id;
            save();
        });
    };

    $scope.reloadData = function() {
        $scope.dtInstancePositivaFgnCampus.reloadData();
    };

    $scope.onCloseModal = function() {
        $uibModalInstance.close(1);
    }

    $scope.onSearch = function() {
        $scope.reloadData();
    }

    $scope.onClear = function() {
        $scope.entity.name = null;
        $scope.reloadData();
    }

});