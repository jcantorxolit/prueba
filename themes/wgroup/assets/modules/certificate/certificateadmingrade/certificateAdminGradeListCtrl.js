'use strict';
/**
 * controller for Customers
 */
app.controller('certificateAdminGradeListCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder','$compile','toaster','$state','$rootScope', 'SweetAlert','$http', '$timeout',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope,SweetAlert, $http, $timeout) {

        var log = $log;
        var request = {};


        log.info("entrando en... certificateAdminGradeListCtrl");

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        $scope.status = $rootScope.parameters("certificate_grade_status");
        $scope.locations = $rootScope.parameters("certificate_grade_location");
        $scope.agents = $rootScope.agents();

        $scope.filter = {
            selectedStatus: null,
            selectedAgent: null,
            selectedLocation: null,
            startDate: null,
            endDate: null,
            selectedProgram: null,
        };

        $scope.request = {
            status: '',
            agentId: '',
            location: '',
            startDate: '',
            endDate: '',
            program: ''
        }


        if ($scope.isAgent) {
            $state.go("app.clientes.list");
        } else if ($scope.isCustomer) {
            log.info("Step 2")
            $state.go("app.clientes.view", {"customerId":$rootScope.currentUser().company});
        }

        var afterInit = function()
        {
            var req = {};

            req.operation = "program";

            $http({
                method: 'POST',
                url: 'api/certificate-program',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            })
                .catch(function (e, code) {

                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.programs = response.data.data;
                    });

                }).finally(function () {

                });
        }

        afterInit();


        // Datatable configuration
        $scope.request.operation = "program";

        $scope.dtGradeOptions = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: $scope.request,
                url: 'api/certificate-grade',
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

        $scope.dtGradeColumns = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';
                    var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#" uib-tooltip="Ver" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-eye"></i></a> ';
                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if($rootScope.can("certificate_program_view")){
                        actions += viewTemplate;
                    }

                    if($rootScope.can("certificate_program_edit")){
                        actions += editTemplate;
                    }

                    if($rootScope.can("certificate_program_delete")){
                        actions += deleteTemplate;
                    }

                    return actions;
                }),
            DTColumnBuilder.newColumn('name').withTitle("Nombre").withOption('width', 200),
            DTColumnBuilder.newColumn('program').withTitle("Programa").withOption('width', 200),
            DTColumnBuilder.newColumn('category').withTitle("Categoría"),
            DTColumnBuilder.newColumn('capacity').withTitle("Capacidad", 200),
            DTColumnBuilder.newColumn('registered').withTitle("Inscritos", 200),
            DTColumnBuilder.newColumn('quota').withTitle("Cupos", 200),
            DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = '';
                    switch  (data.status)
                    {
                        case "Registro":
                            label = 'label label-success';
                            break;

                        case "Cerrado":
                            label = 'label label-danger';
                            break;

                        case "Ejecución":
                            label = 'label label-warning';
                            break;

                        case "Cancelado":
                            label = 'label label-default';
                            break;
                    }

                    var status = '<span class="' + label +'">' + data.status + '</span>';


                    return status;
                }),
        ];

        var loadRow = function () {

            $("#dtCertificateGrade a.editRow").on("click", function () {
                var id = $(this).data("id");

                // Aqui se debe hacer la redireccion al formulario de edicion del customer

                $scope.onEditGrade(id);
            });

            $("#dtCertificateGrade a.viewRow").on("click", function () {
                var id = $(this).data("id");

                // Aqui se debe hacer la redireccion al formulario de edicion del customer
                $scope.onViewGrade(id);
            });

            $("#dtCertificateGrade a.delRow").on("click", function () {
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
                                url: 'api/certificate-program/delete',
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


        $scope.onCreateGrade = function(){
            if($scope.$parent != null){
                $scope.$parent.navToSection("form", "edit", 0);
            }
        };

        $scope.onEditGrade = function(id){
            if($scope.$parent != null){
                $scope.$parent.navToSection("form", "edit", id);
            }
        };

        $scope.onViewGrade = function(id){
            if($scope.$parent != null){
                $scope.$parent.navToSection("form", "view", id);
            }
        };

        $scope.reloadData = function () {
            log.info("reloading...");
            $scope.dtGradeOptions.reloadData();
        };

        $scope.onSearch = function (item, model) {
            $timeout(function () {
                $scope.request.location = $scope.filter.selectedLocation != null ? $scope.filter.selectedLocation.value : '';
                $scope.request.status = $scope.filter.selectedStatus != null ? $scope.filter.selectedStatus.value : '';
                $scope.request.program = $scope.filter.selectedProgram != null ? $scope.filter.selectedProgram.id : '';
                $scope.request.agentId = $scope.filter.selectedAgent != null ? $scope.filter.selectedAgent.id : 0;
                $scope.request.startDate = $scope.filter.startDate != null ? kendo.toString($scope.filter.startDate, 'yyyy-MM-dd') : '';
                $scope.request.endDate = $scope.filter.endDate != null ? kendo.toString($scope.filter.endDate, 'yyyy-MM-dd') : '';

                $scope.reloadData()
            });
        };

        $scope.clearLocation = function()
        {
            $timeout(function () {
                $scope.request.location = ''
                $scope.filter.selectedLocation = null;
                $scope.reloadData();
            });
        }

        $scope.clearStatus = function()
        {
            $timeout(function () {
                $scope.request.status = ''
                $scope.filter.selectedStatus = null;
                $scope.reloadData();
            });
        }

        $scope.clearProgram = function()
        {
            $timeout(function () {
                $scope.request.program = ''
                $scope.filter.selectedProgram = null;
                $scope.reloadData();
            });
        }

        $scope.clearAgent = function()
        {
            $timeout(function () {
                $scope.request.agentId = 0
                $scope.filter.selectedAgent = null;
                $scope.reloadData();
            });
        }

        $scope.clearStartDate = function()
        {
            $timeout(function () {
                $scope.request.startDate = ''
                $scope.filter.startDate = null;
                $scope.reloadData();
            });
        }

        $scope.clearEndDate = function()
        {
            $timeout(function () {
                $scope.request.endDate = ''
                $scope.filter.endDate = null;
                $scope.reloadData();
            });
        }

    }]);
