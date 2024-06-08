app.controller('ModalInstanceSideInternalProjectTaskCtrl',
    function (
        $rootScope, $scope, $uibModalInstance, customer, project, $log, $uibModal, $timeout, SweetAlert, isView, $http,
        DTOptionsBuilder, DTColumnBuilder, $compile, toaster) {

    var $formInstance = null;

    $scope.taskTypes = $rootScope.parameters("project_task_type");

    $scope.project = project;

    $scope.maxDate = new Date();
    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy"
    };

    var onInit = function() {
        $scope.entity = {
            id: 0,
            projectAgentId: project.projectAgentId,
            type: null,
            task: "",
            observation: "",
            startDateTime: new Date(),
            endDateTime: new Date(),
            duration: null,
            status: "activo"
        };

        $scope.entity.tracking = {
            action: "",
            description: "",
        };

        if ($formInstance) {
            $formInstance.$setPristine(true);
        }
    }

    onInit();

    $scope.isView = isView;
    $scope.reschedule = false;

    var loadList = function () {
        var req = {};
        req.customerId = customer.id;
        return $http({
            method: 'POST',
            url: 'api/internal-project/listCustomer',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.taskTypes = response.data.data.taskType;
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });
    }

    loadList();

    $scope.ok = function () {
        $uibModalInstance.close(1);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss('cancel');
    };


    $scope.cancelTask = function (id) {

        var task = {
            id: id,
            status: "cancelador",
            tracking: {
                action: "",
                description: ""
            }
        }

        var modalInstance = $uibModal.open({
            templateUrl: $rootScope.app.views.urlRoot + "modules/internalproject/project_task_tracking_modal.htm",
            controller: 'ModalInstanceTrackingCtrl',
            windowTopClass: 'top-modal',
            resolve: {
                task: function () {
                    return task;
                },
                action: function () {
                    return "Cancelar";
                }
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
            $scope.reloadData();
        }, function () {
            $log.info('Modal dismissed at: ' + new Date());
            $scope.reloadData();
        });
    };

    $scope.completeTask = function (id) {
        var task = {
            id: id,
            status: "inactivo",
            tracking: {
                action: "",
                description: ""
            }
        }
        //task.status = "inactivo";
        update(task);
    };

    $scope.reloadlTask = function (id) {

        $scope.reschedule = true;

        var task = {
            id: id,
            status: "inactivo",
            tracking: {
                action: "Reprogramar",
                description: ""
            }
        }

        loadProjectTaskModel(task.id)
    };

    // $scope.$watch("entity.endDateTime - entity.startDateTime", function () {
    //     //console.log('new result',result);
    //     var end = new moment($scope.entity.endDateTime);
    //     var start = new moment($scope.entity.startDateTime);
    //     $scope.entity.duration = end.diff(start, 'hours');
    // });

    $scope.onChangeStartDate = function(e) {
        $scope.entity.endDateTime = new Date($scope.entity.startDateTime);
        $scope.maxDate = new Date($scope.entity.startDateTime);
    }

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

                $timeout(function () {
                    toaster.pop("error", "Error", "Por favor verifique los datos requeridos del formulario y vuelva a intentarlo");
                }, 500);

                return;

            } else {
                save();
            }

        },
        reset: function (form) {
            form.$setPristine(true);
        }
    };

    var save = function () {
        var req = {};

        $scope.entity.startDateTime = $scope.entity.startDateTime.toISOString();
        $scope.entity.endDateTime = $scope.entity.endDateTime.toISOString();

        var data = JSON.stringify($scope.entity);
        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/internal-project/task/save',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function (response) {

            $timeout(function () {
                SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                $scope.entity = response.data.result;
                initializeDates();
                $scope.refresAll();
                $scope.ok();
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };

    var update = function (task) {
        var req = {};

        //$scope.entity.startDateTime = new Date($scope.entity.startDateTime);
        //$scope.entity.endDateTime = new Date($scope.entity.endDateTime);

        var data = JSON.stringify(task);

        req.data = Base64.encode(data);
        return $http({
            method: 'POST',
            url: 'api/internal-project/task/update',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function (response) {

            $timeout(function () {
                SweetAlert.swal("Validación exitosa", "Procediendo con el guardado...", "success");
                $scope.entity = response.data.result;
                $scope.refresAll();
                $scope.reloadData();
                $scope.ok();
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };


    var loadProjectTaskModel = function (idProjectTask) {
        // se debe cargar primero la información actual del cliente..
        var req = {
            id: idProjectTask
        };

        $http({
            method: 'GET',
            url: 'api/internal-project/task',
            params: req
        })
            .catch(function (e, code) {
                if (code == 403) {
                    var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.clientes.list';
                    // forbbiden
                    // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                    SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                    $timeout(function () { $state.go(messagered); }, 3000);
                } else if (code == 404) {
                    SweetAlert.swal("Información no disponible", "Seguimiento no encontrado", "error");
                    $timeout(function () {
                        $state.go('app.clientes.list');
                    });
                } else {
                    SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del seguimiento", "error");
                }
            })
            .then(function (response) {

                $timeout(function () {
                    $scope.entity = response.data.result;

                    initializeDates();

                    if ($scope.reschedule) {
                        $scope.entity.tracking = {
                            action: "Reprogramar",
                            description: "",
                        }
                    }
                });

            }).finally(function () {
                $timeout(function () {
                    $scope.loading = false;
                    $scope.isView = true;
                }, 400);
            });


    };

    var initializeDates = function() {
        if ($scope.entity.startDateTime) {
            $scope.entity.startDateTime = new Date($scope.entity.startDateTime.date);
        }

        if ($scope.entity.endDateTime) {
            $scope.entity.endDateTime = new Date($scope.entity.endDateTime.date);
        }
    }

    $scope.dtInstanceTask = {};
    $scope.dtOptionsTask = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function(d) {
                d.projectAgentId = project.projectAgentId;

                return JSON.stringify(d);
            },
            url: 'api/customer-internal-project-user-task-agent',
            contentType: 'application/json',
            type: 'POST',
            beforeSend: function () {
                // Aqui inicia el loader indicator
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

        })
        /*.withDOM("<'row'<'col-xs-5'l><'col-xs-7'f>r><'row'<'col-xs-12't>><'row'<'col-xs-3'i><'col-xs-9'p>>")*/
        .withOption('language', {
            //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
        })

        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row, data, dataIndex) {

            // Recompiling so we can bind Angular directive to the DT
            $compile(angular.element(row).contents())($scope);

        });
    ;

    $scope.dtColumnsTask = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 80).notSortable()
            .renderWith(function (data, type, full, meta) {
                var actions = "";
                var editTemplate = '<a class="btn btn-primary btn-xs" href="#" uib-tooltip="Completar tarea" ng-click="completeTask(' + data.id + ')" >' +
                    '   <i class="fa fa-check-circle-o"></i></a> ';
                var viewTemplate = '<a class="btn btn-info btn-xs" href="#" tooltip-placement="right" uib-tooltip="Reprogramar tarea" ng-click="reloadlTask(' + data.id + ')" >' +
                    '   <i class="fa fa-clock-o"></i></a> ';
                var deleteTemplate = '<a class="btn btn-danger btn-xs" href="#" uib-tooltip="Cancelar tarea" ng-click="cancelTask(' + data.id + ')">' +
                    '   <i class="fa fa-trash-o"></i></a> ';

                if (data.statusCode == "activo") {
                    actions += viewTemplate;
                    actions += editTemplate;
                    actions += deleteTemplate;
                }
                return actions;
            }),
        DTColumnBuilder.newColumn('task').withTitle("Tarea").withOption('width', 100),
        DTColumnBuilder.newColumn('type').withTitle("Tipo").withOption('width', 100),
        DTColumnBuilder.newColumn('startDateTime').withTitle("Fecha Inicio").withOption('width', 100),
        DTColumnBuilder.newColumn('endDateTime').withTitle("Fecha Fin").withOption('width', 100),
        DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 80)
            .renderWith(function (data, type, full, meta) {
                var label = '';
                var text = data.status;
                switch (data.statusCode) {
                    case "activo":
                        label = 'label label-success';

                        break;

                    case "cancelador":
                        label = 'label label-danger';

                        break;

                    case "inactivo":
                        label = 'label label-warning';

                        break;
                }

                var status = '<span class="' + label + '">' + text + '</span>';


                return status;
            })
    ];

    $scope.reloadData = function () {
        $scope.dtInstanceTask.reloadData();
    };

});
