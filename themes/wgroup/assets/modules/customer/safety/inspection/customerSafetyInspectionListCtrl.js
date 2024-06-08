'use strict';
/**
  * controller for Customers
*/
app.controller('customerSafetyInspectionListCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder','$compile','toaster','$state','$rootScope','$timeout','SweetAlert','$http', '$filter', '$document', '$aside',
function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder,
          $compile, toaster, $state, $rootScope,$timeout, SweetAlert, $http, $filter, $document, $aside) {

    var log = $log;

    $scope.dtInstanceSafetyInspection = {};
		$scope.dtOptionsSafetyInspection = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function(d) {
                d.operation = "safety";
                d.customer_id = $stateParams.customerId;

                return d;
            },
            url: 'api/customer/safety-inspection',
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
            //log.info("fnPreDrawCallback");
            //Pace.start();
            return true;
        })
        .withOption('fnDrawCallback', function () {
            //log.info("fnDrawCallback");
            loadRow();
            //Pace.stop();

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

    $scope.dtColumnsSafetyInspection = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {
                var actions = "";
                var disabled = "";

                var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Configurar inspección" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-cogs"></i></a> ';
                var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#" uib-tooltip="Ver inspección" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-eye"></i></a> ';
                var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar inspección" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-trash-o"></i></a> ';
                var managementTemplate = '<a class="btn btn-success btn-xs managementRow lnk" href="#" uib-tooltip="Gestionar inspección"  data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-play-circle"></i></a> ';


                actions += viewTemplate;
                actions += editTemplate;
                actions += deleteTemplate;

                if (!data.isContractor) {
                    actions += managementTemplate;
                }

                return actions;
            }),
        DTColumnBuilder.newColumn('description').withTitle("Descripción"),
        DTColumnBuilder.newColumn('isContractor').withTitle("Contratista").withOption('width', 100)
            .renderWith(function (data, type, full, meta) {
                var label = '';
                var text = '';

                if (data) {
                    label = 'label label-success';
                    text = 'Si';
                } else {
                    label = 'label label-danger';
                    text = 'No';
                }

                var status = '<span class="' + label +'">' + text + '</span>';

                return status;
            }),
        DTColumnBuilder.newColumn('contractorType.value').withTitle("Tipo de Contratista").withOption('defaultContent', ""),
        DTColumnBuilder.newColumn('dateText').withTitle("Fecha Creación").withOption('width', 150).withOption('defaultContent', ""),
        DTColumnBuilder.newColumn('version').withTitle("Version").withOption('width', 100),
        DTColumnBuilder.newColumn('isActive').withTitle("Estado").withOption('width', 100)
            .renderWith(function (data, type, full, meta) {
                var label = '';
                var text = '';

                if (data) {
                    label = 'label label-success';
                    text = 'Activo';
                } else {
                    label = 'label label-danger';
                    text = 'Inactivo';
                }

                var status = '<span class="' + label +'">' + text + '</span>';

                return status;
            })
    ];

    var loadRow = function () {

        angular.element("#dtSafetyInspection a.editRow").on("click", function () {
            var id = angular.element(this).data("id");
            $scope.onEditRecord(id);
        });

        angular.element("#dtSafetyInspection a.managementRow").on("click", function () {
            var id = angular.element(this).data("id");
            $scope.onViewSummary(id);
        });

        angular.element("#dtSafetyInspection a.viewRow").on("click", function () {
            var id = angular.element(this).data("id");
            $scope.onViewRecord(id);
        });

        angular.element("#dtSafetyInspection a.delRow").on("click", function () {
            var id = angular.element(this).data("id");

            // Aqui se debe hacer la redireccion al formulario de edicion del customer
            log.info("intenta eliminar el registro: " + id);

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
                            url: 'api/customer/safety-inspection/delete',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            data: $.param(req)
                        }).then(function (response) {
								swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                        }).catch(function(e){
                            $log.error(e);
                            SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                        }).finally(function(){

                            $scope.reloadData();
                        });

                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        });

    };

    $scope.dtInstanceSafetyInspectionCallback = function(instance){
        $scope.dtInstanceSafetyInspection = instance;
    }

    $scope.reloadData = function () {
        $scope.dtInstanceSafetyInspection.reloadData();
    };

    $scope.onCreateNew = function(id){
        openModal(0);
    };

    $scope.onEditRecord = function(id){
        openModal(id);
    };

    $scope.onViewSummary = function(id){
        if($scope.$parent != null){
            $scope.$parent.navToSection("summary", "edit", id);
        }
    };

    $scope.onViewRecord = function(id){
        if($scope.$parent != null){
            $scope.$parent.navToSection("form", "view", id);
        }
    };


    var openModal = function(id) {
        var safety = {
            id: id
        }

        var modalInstance = $aside.open({
            templateUrl: $rootScope.app.views.urlRoot + "modules/customer/safety/inspection/customer_safety_inspection_modal.htm",
            placement: 'right',
            size: 'lg',
            backdrop: true,
            controller: 'customerSafetyInspectionModalEditCtrl',
            scope: $scope,
            resolve: {
                safety: function () {
                    return safety;
                }
            }
        });
        modalInstance.result.then(function () {
            $scope.reloadData();
        }, function() {
            $scope.reloadData();
        });
    }

}]);

app.controller('customerSafetyInspectionModalEditCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, safety, $log, $timeout, SweetAlert, $http, toaster, $filter, $aside, $document, DTColumnBuilder, DTOptionsBuilder, $compile) {

    var log = $log;
    $scope.maxDate = new Date();
    $scope.datePickerConfig = {
        culture: "es-CO",
        format: "dd/MM/yyyy HH:mm"
    };

    $scope.loading = true;
    $scope.customer_id = $stateParams.customerId;

    $scope.isView = false;
    $scope.isCreate = true;
    $scope.format = 'dd-MM-yyyy';
    $scope.minDate = new Date() - 1;

    $scope.safety = {};
    $scope.agents = [];
    $scope.lists = [];
    $scope.headers = [];

    $scope.onCloseModal = function () {
        $uibModalInstance.close(1);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onLoadRecord = function () {
        if ($scope.safety.id != 0) {

            // se debe cargar primero la información actual del cliente..
            log.info("editando cliente con código: " + $scope.safety.id);
            var req = {
                id: $scope.safety.id
            };
            $http({
                method: 'GET',
                url: 'api/customer/safety-inspection',
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
                        $scope.safety = response.data.result;

                        initializeDates();
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

    var initializeDates = function() {
        if ($scope.safety.date != null) {
            $scope.safety.date =  new Date($scope.safety.date.date);
        }

        if ($scope.safety.dateFrom != null) {
            $scope.safety.dateFrom =  new Date($scope.safety.dateFrom.date);
        }

        if ($scope.safety.dateTo != null) {
            $scope.safety.dateTo =  new Date($scope.safety.dateTo.date);
        }
    }

    var loadLists = function () {

        var req = {};
        req.operation = "list";
        req.customerId = $stateParams.customerId;

        return $http({
            method: 'POST',
            url: 'api/customer/safety-inspection-config-list/list',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.lists = response.data.data;
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };

    var loadHeaders = function () {

        var req = {};
        req.operation = "list";
        req.customerId = $stateParams.customerId;

        return $http({
            method: 'POST',
            url: 'api/customer/safety-inspection-config-header/list',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.headers = response.data.data;
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };

    var loadAgents = function () {

        var req = {};
        req.operation = "list";
        req.customerId = $stateParams.customerId;

        return $http({
            method: 'POST',
            url: 'api/customer/agentList',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                $scope.agents = response.data.data;
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {

        });

    };

    var init = function () {
        $scope.safety = {
            id: safety.id,
            customerId: $stateParams.customerId,
            description: "",
            reason: "",
            date: new Date(),
            dateFrom: new Date(),
            dateTo: new Date(),
            isActive : true,
            responsible: "",
            responsibleJob: "",
            responsibleEmail: "",
            isContractor:false,
            contractorType:null,
            agent: null,
            header: null,
            lists: [],
        };
    };

    init();
    loadLists();
    loadAgents();
    loadHeaders();

    $scope.onLoadRecord();

    $scope.master = $scope.safety;
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
                log.info($scope.safety);
                angular.element('.ng-invalid[name=' + firstError + ']').focus();
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                return;

            } else {
                save();
            }

        },
        reset: function (form) {
            $scope.clear();
        }
    };

    $scope.clear = function () {
        $scope.isView = false;

        $timeout(function () {
            init();
        });

    };

    var save = function () {
        var req = {};

        $scope.safety.date = $scope.safety.date.toISOString();
        $scope.safety.dateFrom = $scope.safety.dateFrom.toISOString();
        $scope.safety.dateTo = $scope.safety.dateTo.toISOString();

        var data = JSON.stringify($scope.safety);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/customer/safety-inspection/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
            $timeout(function () {
                $scope.onCloseModal()
            });
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
        }).finally(function () {
            $scope.clear();
        });

    };

    $scope.onAddList = function () {
        if ($scope.safety.lists == null) {
            $scope.safety.lists = [];
        }
        $scope.safety.lists.push(
            {
                id: 0,
                customerSafetyInspectionId: safety.id,
                list: null,
                isActive: true
            }
        );
    };

    $scope.onRemoveList = function (index) {
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
                        if ($scope.safety.lists[index].id == 0) {
                            $scope.safety.lists.splice(index, 1);
                        } else {

                            var req = {id: $scope.safety.lists[index].id};

                            $http({
                                method: 'POST',
                                url: 'api/customer-safety-inspection-list/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {
                                $scope.safety.lists.splice(index, 1);
                            }).catch(function (e) {
                                $log.error(e);
                                toaster.pop("error", "Error", "Error guardando el registro. Por favor verifique los datos ingresados!");
                            }).finally(function () {

                            });
                        }
                    });
                } else {
                    swal("Cancelación", "La operación ha sido cancelada", "error");
                }
            });
    };
});
