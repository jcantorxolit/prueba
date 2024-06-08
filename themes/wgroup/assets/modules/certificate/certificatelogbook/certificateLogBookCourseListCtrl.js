'use strict';
/**
 * controller for Customers
 */
app.controller('certificateLogBookCourseListCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder','$compile','toaster','$state','$rootScope', 'SweetAlert','$http', '$timeout',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope,SweetAlert, $http, $timeout) {

        var log = $log;
        var request = {};


        log.info("entrando en... certificateAdminProgramCtrl");

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";

        if ($scope.isAgent) {
            $state.go("app.clientes.list");
        } else if ($scope.isCustomer) {
            log.info("Step 4");
            $state.go("app.clientes.view", {"customerId":$rootScope.currentUser().company});
        }

        $scope.currentMonth = 0;
        $scope.currentYear = 0;
        $scope.filter = {
            selectedMonth: null,
            selectedYear: null,
        };

        $scope.request = {};

        $scope.months = [
            {
                id: "1",
                item: "Enero",
                value: "1"
            },
            {
                id: "2",
                item: "Febrero",
                value: "2"
            },
            {
                id: "3",
                item: "Marzo",
                value: "3"
            },
            {
                id: "4",
                item: "Abril",
                value: "4"
            },
            {
                id: "5",
                item: "Mayo",
                value: "5"
            },
            {
                id: "6",
                item: "Junio",
                value: "6"
            },
            {
                id: "7",
                item: "Julio",
                value: "7"
            },
            {
                id: "8",
                item: "Agosto",
                value: "8"
            },
            {
                id: "9",
                item: "Septiembre",
                value: "9"
            },
            {
                id: "10",
                item: "Octubre",
                value: "10"
            },
            {
                id: "11",
                item: "Noviembre",
                value: "11"
            },
            {
                id: "12",
                item: "Diciembre",
                value: "12"
            }
        ]

        $scope.years = [
            {
                id: 2014,
                item: "2014",
                value: "2014"
            },
            {
                id: 2015,
                item: "2015",
                value: "2015"
            },
            {
                id: 2016,
                item: "2016",
                value: "2016"
            }
        ]


        // Datatable configuration
        $scope.request.operation = "expiration";
        $scope.request.data = "";

        $scope.dtInstanceCertificateExpiration = {};
		$scope.dtOptionsCertificateExpiration = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: $scope.request,
                url: 'api/certificate-grade-participant/expiration',
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

        $scope.dtColumnsCertificateExpiration = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";

                    //var disabled = (data.hasCertificate) ? "" : "disabled";
                    var disabled = "";

                    var editTemplate = '<a target="_self" class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Descargar certificado" data-id="' + data.id + '" ' + disabled + '  >' +
                        '   <i class="fa fa-download"></i></a> ';

                    var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#"  uib-tooltip="Ver participante" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-eye"></i></a> ';

                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#"  uib-tooltip="Eliminar participante" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if ($rootScope.can("clientes_view")) {
                        //actions += viewTemplate;
                    }

                    if ($rootScope.can("clientes_edit")) {
                        actions += editTemplate;
                    }

                    if ($rootScope.can("clientes_delete")) {
                        //actions += deleteTemplate;
                    }


                    return actions;
                }),

            DTColumnBuilder.newColumn('documentType').withTitle("Tipo de Identificación").withOption('width', 200),
            DTColumnBuilder.newColumn('identificationNumber').withTitle("Identificación").withOption('width', 200),
            DTColumnBuilder.newColumn('name').withTitle("Nombres").withOption('width', 200),
            DTColumnBuilder.newColumn('lastName').withTitle("Apellidos").withOption('width', 200),
            DTColumnBuilder.newColumn('customer').withTitle("Empresa").withOption('width', 200),
            DTColumnBuilder.newColumn('grade').withTitle("Curso").withOption('width', 200),
            DTColumnBuilder.newColumn('certificateCreatedAt').withTitle("Fecha").withOption('width', 200),
            DTColumnBuilder.newColumn('certificateExpirationAt').withTitle("Fecha Vencimiento").withOption('width', 200),
        ];

        var loadRow = function () {

            $("#dtCertificateExpiration a.editRow").on("click", function () {
                var id = $(this).data("id");
                var url = $(this).data("url");
                //$scope.editTracking(id);
                if (url == "")
                {
                    SweetAlert.swal("Error en la descarga", "No existe un anexo para descargar", "error");
                }
                else
                {
                    jQuery("#downloadDocument")[0].src = "api/certificate-grade-participant-certificate/download?id=" + id;
                }
            });

            $("#dtCertificateExpiration a.viewRow").on("click", function () {
                var id = $(this).data("id");

                // Aqui se debe hacer la redireccion al formulario de edicion del customer
                $scope.onViewProgram(id);
            });

            $("#dtCertificateExpiration a.delRow").on("click", function () {
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

        $scope.changeMonth = function (item, model) {
            $timeout(function () {
                $scope.currentMonth = item.id;
                loadProjects();
            });
        };

        $scope.clearMonth= function()
        {
            $timeout(function () {
                $scope.currentMonth = 0;
                $scope.filter.selectedMonth = null;
                loadProjects();
            });
        }

        $scope.changeYear = function (item, model) {
            $timeout(function () {
                $scope.currentYear = item.id;
                loadProjects();
            });
        };

        $scope.clearYear = function()
        {
            $timeout(function () {
                $scope.currentYear = 0;
                $scope.filter.selectedYear = null;
                loadProjects();
            });
        }

        var loadProjects = function () {

            $scope.request.year = $scope.currentYear;
            $scope.request.month = $scope.currentMonth;

            $scope.reloadData();
        }


        $scope.onCreateProgram = function(){
            if($scope.$parent != null){
                $scope.$parent.navToSection("form", "edit", 0);
            }
        };

        $scope.onEditProgram = function(id){
            if($scope.$parent != null){
                $scope.$parent.navToSection("form", "edit", id);
            }
        };

        $scope.onViewProgram = function(id){
            if($scope.$parent != null){
                $scope.$parent.navToSection("form", "view", id);
            }
        };

        $scope.reloadData = function () {
            log.info("reloading...");
            $scope.dtInstanceCertificateExpiration.reloadData();
        };


    }]);
