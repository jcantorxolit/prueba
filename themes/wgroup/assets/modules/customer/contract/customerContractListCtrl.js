'use strict';
/**
 * controller for Customers
 */
app.controller('customerContractListCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder','$compile','toaster','$state','$rootScope','$timeout','$http','SweetAlert',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope,$timeout, $http, SweetAlert) {

        var log = $log;
        var request = {};
        log.info("loading..customerContractListCtrl ");

        $scope.isNewDiagnostic = false;
        // $rootScope.tabname = "tracking";

        // default view
        // $rootScope.tracking_section = "list";

        // Datatable configuration
        request.operation = "contract";
        request.customer_id = $stateParams.customerId;

        $scope.dtInstanceContract = {};
		$scope.dtOptionsContract = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function (d) {                    
                    d.contractorId = $stateParams.customerId;
                    return JSON.stringify(d);
                },
                url: 'api/customer-contractor-index',
                contentType: "application/json",
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

        $scope.dtColumnsContract = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";
                    var disabled = ""

                    if (!data.isActive) {
                        disabled = 'disabled="disabled"';
                    }

                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Continuar"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-play-circle"></i></a> ';


                    actions += editTemplate;

                    return actions;
                }),
            DTColumnBuilder.newColumn('documentType').withTitle("Tipo de Documento").withOption('width', 160),
            DTColumnBuilder.newColumn('documentNumber').withTitle("Documento").withOption('width', 200),
            DTColumnBuilder.newColumn('businessName').withTitle("Razón social"),
            DTColumnBuilder.newColumn('contract').withTitle("Contrato").withOption('width', 200),
            DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 60)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-danger';
                    var text = 'Inactivo';

                    if (data.isActive != null || data.isActive != undefined) {
                        if (data.isActive) {
                            label = 'label label-success';
                            text = 'Activo';
                        } else {
                            label = 'label label-danger';
                            text = 'Inactivo';
                        }
                    }

                    var status = '<span class="' + label +'">' + text + '</span>';

                    return status;
                })
        ];

        var loadRow = function () {

            $("#dtCustomerContract a.editRow").on("click", function () {

                var id = $(this).data("id");

                var req = {};
                req.id = id;
                $http({
                    method: 'POST',
                    url: 'api/customer/contract-detail/bulk',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    data: $.param(req)
                }).then(function (response) {

                }).catch(function(e){
                    $log.error(e);
                    SweetAlert.swal("Error en la carga", "Se ha presentado un error durante la carga del registro por favor intentelo de nuevo", "error");
                }).finally(function(){

                    $scope.editContract(id);
                });


            });

            $("#dtCustomerContract a.viewRow").on("click", function () {
                var id = $(this).data("id");

                // Aqui se debe hacer la redireccion al formulario de edicion del customer
                log.info("Step 11");
                $state.go("app.clientes.view", {"customerId":id});

            });
        };

        $scope.reloadData = function () {
            $scope.dtInstanceContract.reloadData();
        };


        $scope.editContract = function(id){
            if($scope.$parent != null){
                $scope.$parent.navToSection("summary", "summary", id);
            }
        };

    }]);