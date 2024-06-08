'use strict';
/**
 * controller for Customers
 */
app.controller('customerInternalCertificateAdminGradeListCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder','$compile','toaster','$state','$rootScope', 'SweetAlert','$http', '$timeout', 'ListService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope,SweetAlert, $http, $timeout, ListService) {

        var log = $log;

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        $scope.status = $rootScope.parameters("certificate_grade_status");
        $scope.locations = $rootScope.parameters("certificate_grade_location");
        $scope.agents = $rootScope.agents();

        getList();

        function getList() {

            var $criteria = {
                customerId: $stateParams.customerId
            };

            var entities = [                                      
                {name: 'customer_internal_certificate_program', value: null, criteria: $criteria},                
            ];

            ListService.getDataList(entities)
                .then(function (response) {                                    
                    $scope.programs = response.data.data.customerInternalCertificateProgram;               
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        $scope.filter = {
            selectedStatus: null,
            selectedAgent: null,
            selectedLocation: null,
            startDate: null,
            endDate: null,
            selectedProgram: null,
        };

        if ($scope.isAgent) {
            $state.go("app.clientes.list");
        } else if ($scope.isCustomer) {            
            $state.go("app.clientes.view", {"customerId":$rootScope.currentUser().company});
        }

        $scope.dtOptionsCustomerInternalGrade = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.customerId = $stateParams.customerId;
                    d.location = $scope.filter.selectedLocation ? $scope.filter.selectedLocation.value : null;
                    d.statusCode = $scope.filter.selectedStatus ? $scope.filter.selectedStatus.value : null;
                    d.programId = $scope.filter.selectedProgram ? $scope.filter.selectedProgram.id : null;
                    d.agentId = $scope.filter.selectedAgent ? $scope.filter.selectedAgent.id : null;
                    d.startDate = $scope.filter.startDate ? kendo.toString($scope.filter.startDate, 'yyyy-MM-dd') : null;
                    d.endDate = $scope.filter.endDate ? kendo.toString($scope.filter.endDate, 'yyyy-MM-dd') : null;
                    return JSON.stringify(d);
                },
                url: 'api/customer-internal-certificate-grade/v2',
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

        $scope.dtColumnsCustomerInternalGrade = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";
                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '" >' +
                        '   <i class="fa fa-edit"></i></a> ';
                    var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#" uib-tooltip="Ver" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-eye"></i></a> ';
                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if ($rootScope.can("certificate_program_view")) {
                        actions += viewTemplate;
                    }

                    if($rootScope.can("certificate_program_edit")){
                        actions += editTemplate;
                    }

                    if ($rootScope.can("certificate_program_delete")) {
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

            $("#dtCustomerInternalCertificateGrade a.editRow").on("click", function () {
                var id = $(this).data("id");

                // Aqui se debe hacer la redireccion al formulario de edicion del customer

                $scope.onEdit(id);
            });

            $("#dtCustomerInternalCertificateGrade a.viewRow").on("click", function () {
                var id = $(this).data("id");

                // Aqui se debe hacer la redireccion al formulario de edicion del customer
                $scope.onView(id);
            });

            $("#dtCustomerInternalCertificateGrade a.delRow").on("click", function () {
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
                                url: 'api/customer-internal-certificate-program/delete',
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
                        }
                    });


            });
        };

        $scope.dtInstanceCustomerInternalGradeCallback = function (instance) {
            $scope.dtInstanceCustomerInternalGrade = instance;
        };

        $scope.reloadData = function () {            
            $scope.dtInstanceCustomerInternalGrade.reloadData();
        };


        $scope.onCreate = function(){
            if($scope.$parent != null){
                $scope.$parent.navToSection("form", "edit", 0);
            }
        };

        $scope.onEdit = function(id){
            if($scope.$parent != null){
                $scope.$parent.navToSection("form", "edit", id);
            }
        };

        $scope.onView = function(id){
            if($scope.$parent != null){
                $scope.$parent.navToSection("form", "view", id);
            }
        };

        $scope.onSearch = function () {
            $timeout(function () {
                $scope.reloadData()
            });
        };

        $scope.onClearLocation = function()
        {
            $timeout(function () {                
                $scope.filter.selectedLocation = null;
                $scope.reloadData();
            });
        }

        $scope.onClearStatus = function()
        {
            $timeout(function () {                
                $scope.filter.selectedStatus = null;
                $scope.reloadData();
            });
        }

        $scope.onClearProgram = function()
        {
            $timeout(function () {                
                $scope.filter.selectedProgram = null;
                $scope.reloadData();
            });
        }

        $scope.onClearAgent = function()
        {
            $timeout(function () {                
                $scope.filter.selectedAgent = null;
                $scope.reloadData();
            });
        }

        $scope.onClearStartDate = function()
        {
            $timeout(function () {                
                $scope.filter.startDate = null;
                $scope.reloadData();
            });
        }

        $scope.onClearEndDate = function()
        {
            $timeout(function () {                
                $scope.filter.endDate = null;
                $scope.reloadData();
            });
        }

    }
]);
