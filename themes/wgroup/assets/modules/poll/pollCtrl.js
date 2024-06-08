'use strict';
/**
 * controller for Customers
 */
app.controller('pollCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder','$compile','toaster','$state','$rootScope', 'SweetAlert','$http',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope,SweetAlert, $http) {

        var log = $log;
        var request = {};


        log.info("entrando en... pollCtrl");

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        if ($scope.isAgent) {
            $state.go("app.clientes.list");
        } else if ($scope.isCustomer) {
            log.info("Step 19")
            $state.go("app.clientes.view", {"customerId":$rootScope.currentUser().company});
        }

        // Datatable configuration
        request.operation = "poll";

        $scope.dtPollOptions = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/poll',
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

        $scope.dtPollColumns = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';
                    var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#" uib-tooltip="Ver" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-eye"></i></a> ';
                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if($rootScope.can("poll_view")){
                        actions += viewTemplate;
                    }

                    if($rootScope.can("poll_edit")){
                        actions += editTemplate;
                    }

                    if($rootScope.can("poll_delete")){
                        actions += deleteTemplate;
                    }

                    return actions;
                }),
            DTColumnBuilder.newColumn('name').withTitle("Nombre").withOption('width', 200),
            DTColumnBuilder.newColumn('description').withTitle("Descripción"),
            DTColumnBuilder.newColumn('startDateTime').withTitle("Fecha inicio", 200),
            DTColumnBuilder.newColumn('endDateTime').withTitle("Fecha fin", 200),
            DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = '';
                    var text = '';
                    if (data.isActive) {
                        label = 'label label-success';
                        text = 'Activo';
                    } else {
                        label = 'label label-danger';
                        text = 'Inactivo';
                    }

                    var status = '<span class="' + label +'">' + text + '</span>';


                    return status;
                }),
        ];

        var loadRow = function () {

            $("#dtPoll a.editRow").on("click", function () {
                var id = $(this).data("id");

                // Aqui se debe hacer la redireccion al formulario de edicion del customer
                $state.go("app.poll.edit", {"id":id});

            });

            $("#dtPoll a.viewRow").on("click", function () {
                var id = $(this).data("id");

                // Aqui se debe hacer la redireccion al formulario de edicion del customer
                $state.go("app.poll.view", {"id":id});

            });

            $("#dtPoll a.delRow").on("click", function () {
                var id = $(this).data("id");

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
                                url: 'api/poll/delete',
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





        $scope.onCreate = function(){
            $state.go("app.poll.create");
        };

        $scope.reloadData = function () {
            log.info("reloading...");
            $scope.reloadDataEvent();
        };


        $scope.dtInstanceEvent = {};
        $scope.dtEventOptions = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function ( d ) {
                    return JSON.stringify( d );
                },
                contentType: "application/json",
                url: 'apiV1/event/index',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function () {
                }
            })
            .withDataProp('data')
            .withOption('order', [[0, 'desc']])
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                //log.info("fnPreDrawCallback");
                //Pace.start();
                return true;
            })
            .withOption('fnDrawCallback', function () {
                //log.info("fnDrawCallback");
                loadRowEvent();
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

        $scope.dtEventColumns = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';
                    var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#" uib-tooltip="Ver" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-eye"></i></a> ';
                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if($rootScope.can("poll_view")){
                        actions += viewTemplate;
                    }

                    if($rootScope.can("poll_edit")){
                        actions += editTemplate;
                    }

                    if($rootScope.can("poll_delete")){
                        actions += deleteTemplate;
                    }

                    return actions;
                }),
            DTColumnBuilder.newColumn('name').withTitle("Nombre").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('description').withTitle("Descripción").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('startDateTime').withTitle("Fecha inicio", 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('endDateTime').withTitle("Fecha fin", 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = '';
                    var text = '';
                    if (data.isActive) {
                        label = 'label label-success';
                        text = 'Activo';
                    } else {
                        label = 'label label-danger';
                        text = 'Inactivo';
                    }

                    var status = '<span class="' + label +'">' + text + '</span>';


                    return status;
                }),
        ];

        var loadRowEvent = function () {

            $("#dtPoll a.editRow").on("click", function () {
                var id = $(this).data("id");

                // Aqui se debe hacer la redireccion al formulario de edicion del customer
                $state.go("app.poll.edit", {"id":id});

            });

            $("#dtPoll a.viewRow").on("click", function () {
                var id = $(this).data("id");

                // Aqui se debe hacer la redireccion al formulario de edicion del customer
                $state.go("app.poll.view", {"id":id});

            });

            $("#dtPoll a.delRow").on("click", function () {
                var id = $(this).data("id");

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
                                url: 'api/poll/delete',
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

        $scope.dtInstanceEventCallback = function(instance)
        {
            $scope.dtInstanceEvent = instance;
        }

        $scope.reloadDataEvent = function () {
            $scope.dtInstanceEvent.reloadData();
        };

    }]);
