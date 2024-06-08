'use strict';
/**
 * controller for Customers
 */
app.controller('customerHealthDamageDisabilityListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', 'SweetAlert', '$http', '$filter', '$document', '$aside',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder,
        $compile, toaster, $state, $rootScope, $timeout, SweetAlert, $http, $filter, $document, $aside) {

        var log = $log;
        var request = {};
        log.info("loading..customerHealthDamageDisabilityListCtrl ");

        //------------------------------------------------------------------------HealthDamageDisabilityList
        $scope.dtInstanceHealthDamageDisabilityList = {};
        $scope.dtOptionsHealthDamageDisabilityList = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                url: 'api/customer-absenteeism-disability-days-analysis',
                data: function (d) {
                    d.operation = "tracking";
                    d.customerId = $stateParams.customerId;
                    return JSON.stringify(d);
                    },
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

$scope.dtColumnsHealthDamageDisabilityList = [
    DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 80).notSortable()
        .renderWith(function (data, type, full, meta) {
            var actions = "";

            var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#" uib-tooltip="Ver reporte" ' +
                'data-diagnostic="' + data.diagnostic_id + '"' +
                'data-type="' + data.type + '" >' +
                '   <i class="fa fa-eye"></i></a> ';

            actions += viewTemplate;

            return actions;
        }),
    DTColumnBuilder.newColumn('disability').withTitle("Diagnóstico").withOption('width', 200).withOption('defaultContent', ''),
    DTColumnBuilder.newColumn('causeItem').withTitle("Origen").withOption('width', 200).withOption('defaultContent', ''),
    DTColumnBuilder.newColumn('startDate').withTitle("F.Inicio").withOption('width', 200).withOption('defaultContent', ''),
    DTColumnBuilder.newColumn('endDate').withTitle("F.Final").withOption('width', 200).withOption('defaultContent', ''),
    // DTColumnBuilder.newColumn('retroactive').withTitle("Retroactiva").withOption('width', 200).withOption('defaultContent', ''),
    DTColumnBuilder.newColumn('records').withTitle("Num Casos").withOption('width', 200).withOption('defaultContent', ''),
    DTColumnBuilder.newColumn('days').withTitle("Num Días Acumulados").withOption('width', 200).withOption('defaultContent', ''),
    DTColumnBuilder.newColumn('workplace').withTitle("Centro de Trabajo").withOption('width', 200).withOption('defaultContent', ''),
    /*DTColumnBuilder.newColumn('origin').withTitle("Origen").withOption('width', 200).withOption('defaultContent', ''),
     DTColumnBuilder.newColumn('extension').withTitle("Prorroga").withOption('width', 200).withOption('defaultContent', ''),
     DTColumnBuilder.newColumn('transcribed').withTitle("Transcrita").withOption('width', 200).withOption('defaultContent', ''),
     DTColumnBuilder.newColumn('rehabConcept').withTitle("Concepto Rehab").withOption('width', 200).withOption('defaultContent', ''),
     DTColumnBuilder.newColumn('conceptType').withTitle("Tipo Concepto").withOption('width', 200).withOption('defaultContent', ''),
     DTColumnBuilder.newColumn('conceptDate').withTitle("F Concepto").withOption('width', 200).withOption('defaultContent', ''),
     DTColumnBuilder.newColumn('transmitter').withTitle("Emisor").withOption('width', 200).withOption('defaultContent', ''),*/
];

var loadRow = function () {
    $("#dtHealthDamageDisabilityList a.viewRow").on("click", function () {
        var diagnosticId = $(this).data("diagnostic");
        var type = $(this).data("type");
        onViewPersonAnalysis(diagnosticId, type);
    });

};

$scope.reloadData = function () {
    $scope.dtInstanceHealthDamageDisabilityList.reloadData();
};

$scope.onExportExcel = function () {
    jQuery("#download")[0].src = "api/absenteeism-disability/days-analysis-export?id=" + $stateParams.customerId;
};

var onViewPersonAnalysis = function (diagnosticId, type) {
    var filter = { diagnosticId: diagnosticId, type: type };
    var modalInstance = $aside.open({
        //templateUrl: 'app_modal_customer_health_damage_disability_diagnostic_analysis.htm',
        templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/health-damage/disability/customer_health_damage_disability_diagnostic_analysis_modal.htm",
        placement: 'right',
        size: 'lg',
        backdrop: true,
        controller: 'ModalInstanceSideDisabilityDiagnosticAnalysisPersonCtrl',
        scope: $scope,
        resolve: {
            filter: function () {
                return filter;
            }
        }
    });
    modalInstance.result.then(function () {
        $scope.reloadData();
    });
};

    }]);

app.controller('ModalInstanceSideDisabilityDiagnosticAnalysisPersonCtrl', function ($rootScope, $stateParams, $scope, filter, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    var request = {};

    $scope.employee = {
        id: 0,
    };

    $scope.onCloseModal = function () {
        $uibModalInstance.close($scope.employee);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onLoadRecord = function () {
        if ($scope.employee.id != 0) {
            var req = {
                id: $scope.employee.id,
            };
            $http({
                method: 'GET',
                url: 'api/customer-employee',
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
                        SweetAlert.swal("Información no disponible", "Diagnóstico no encontrado", "error");
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del proceso", "error");
                    }
                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.employee = response.data.result;
                    });

                }).finally(function () {
                    $timeout(function () {
                        $scope.onCloseModal();
                    }, 400);
                });


        } else {
            $scope.loading = false;
        }
    }

    request.operation = "diagnostic";
    request.customer_id = $stateParams.customerId;
    request.diagnostic_id = filter.diagnosticId;
    request.type = filter.type;
    request.data = "";

    $scope.dtInstanceCustomerHealthDamageDisabilityDiagnosticAnalysis = {};
    $scope.dtOptionsCustomerHealthDamageDisabilityDiagnosticAnalysis = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: request,
            url: 'api/absenteeism-disability/person-analysis',
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

    $scope.dtColumnsCustomerHealthDamageDisabilityDiagnosticAnalysis = [
        DTColumnBuilder.newColumn('employee').withTitle("Empleado").withOption('width', 200).withOption('defaultContent', ''),

        DTColumnBuilder.newColumn('startDate').withTitle("F.Inicio").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('endDate').withTitle("F.Final").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('origin').withTitle("Origen").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('type').withTitle("Tipo").withOption('width', 200).withOption('defaultContent', ''),
        // DTColumnBuilder.newColumn('retroactive').withTitle("Retroactiva").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('days').withTitle("Num Días").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('acumulateDays').withTitle("Num Días Acumulados").withOption('width', 200).withOption('defaultContent', ''),

        DTColumnBuilder.newColumn('disability').withTitle("Diagnóstico").withOption('width', 200).withOption('defaultContent', '')
        /*DTColumnBuilder.newColumn('transcribed').withTitle("Transcrita").withOption('width', 200).withOption('defaultContent', ''),
         DTColumnBuilder.newColumn('rehabConcept').withTitle("Concepto Rehab").withOption('width', 200).withOption('defaultContent', ''),
         DTColumnBuilder.newColumn('conceptType').withTitle("Tipo Concepto").withOption('width', 200).withOption('defaultContent', ''),
         DTColumnBuilder.newColumn('conceptDate').withTitle("F Concepto").withOption('width', 200).withOption('defaultContent', ''),
         DTColumnBuilder.newColumn('transmitter').withTitle("Emisor").withOption('width', 200).withOption('defaultContent', ''),*/
    ];

    var loadRow = function () {

    };

    $scope.reloadData = function () {
        $scope.dtInstanceCustomerHealthDamageDisabilityDiagnosticAnalysis.reloadData();
    };
});