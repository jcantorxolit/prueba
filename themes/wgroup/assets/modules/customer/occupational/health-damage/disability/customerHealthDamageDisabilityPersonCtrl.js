'use strict';
/**
  * controller for Customers
*/
app.controller('customerHealthDamageDisabilityPersonCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder','$compile','toaster','$state','$rootScope','$timeout','SweetAlert','$http', '$filter', '$document',
function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder,
          $compile, toaster, $state, $rootScope,$timeout, SweetAlert, $http, $filter, $document) {

    var log = $log;
    log.info("loading..customerHealthDamageDisabilityPersonCtrl ");


    //------------------------------------------------------------------------HealthDamageDisabilityList
    $scope.dtInstanceHealthDamageDisabilityPerson = {};
    $scope.dtOptionsHealthDamageDisabilityPerson = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function(d) {
                d.operation = "tracking";
                d.customerId = $stateParams.customerId;
                d.category = "Incapacidad";    
                return JSON.stringify(d);
            },                
            url: 'api/customer-absenteeism-disability-person-analysis',
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


    $scope.dtColumnsHealthDamageDisabilityPerson = [
        DTColumnBuilder.newColumn('employee').withTitle("Empleado").withOption('width', 200).withOption('defaultContent', ''),

        DTColumnBuilder.newColumn('startDate').withTitle("F.Inicio").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('endDate').withTitle("F.Final").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('origin').withTitle("Origen").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('type').withTitle("Tipo").withOption('width', 200).withOption('defaultContent', ''),
        // DTColumnBuilder.newColumn('retroactive').withTitle("Retroactiva").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('numberDays').withTitle("Num Días").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('acumulateDays').withTitle("Num Días Acumulados").withOption('width', 200).withOption('defaultContent', ''),

        DTColumnBuilder.newColumn('description').withTitle("Diagnóstico").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('workplace').withTitle("Centro de Trabajo").withOption('width', 200).withOption('defaultContent', ''),
        /*DTColumnBuilder.newColumn('transcribed').withTitle("Transcrita").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('rehabConcept').withTitle("Concepto Rehab").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('conceptType').withTitle("Tipo Concepto").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('conceptDate').withTitle("F Concepto").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('transmitter').withTitle("Emisor").withOption('width', 200).withOption('defaultContent', ''),*/
    ];

    var loadRow = function () {


    };

    $scope.reloadData = function () {
        $scope.dtInstanceHealthDamageDisabilityPerson.reloadData();
    };

    $scope.onExportExcel = function () {
        jQuery("#download")[0].src = "api/customer-absenteeism-disability/export-person-analysis?id=" + $stateParams.customerId;
    };

}]);