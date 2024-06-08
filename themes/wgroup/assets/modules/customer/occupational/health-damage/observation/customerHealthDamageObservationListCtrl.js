'use strict';
/**
  * controller for Customers
*/
app.controller('customerHealthDamageObservationListCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder','$compile','toaster','$state','$rootScope','$timeout','SweetAlert','$http', '$filter', '$document',
function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder,
          $compile, toaster, $state, $rootScope,$timeout, SweetAlert, $http, $filter, $document) {

    var log = $log;
    log.info("loading..customerHealthDamageObservationListCtrl ");

   // $rootScope.tabname = "tracking";

    // default view
   // $rootScope.tracking_section = "list";

    // Datatable configuration
    $scope.agents = $rootScope.agents();

    $scope.dtInstanceHealthDamageObservation = {};
		$scope.dtOptionsHealthDamageObservation = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function(d) {
                d.customerId = $stateParams.customerId;
                return JSON.stringify(d);
                },
            url: 'api/customer-health-damage-restriction-observation-all',
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

    $scope.dtColumnsHealthDamageObservation = [
        DTColumnBuilder.newColumn('restrictionDate').withTitle("Fecha restricción").withOption('width', 150).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('documentNumber').withTitle("Número Identificación").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('fullName').withTitle("Nombre Empleado").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('observationDate').withTitle("Fecha Observación").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('name').withTitle("Usuario").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('restriccion').withTitle("Restricción").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('accessLevel').withTitle("Nivel acceso").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                var label = '';

                if (data == 'Pública') {
                    label = 'label label-success';
                } else {
                    label = 'label label-danger';
                }

                return '<span class="' + label + '">' + data + '</span>';
            }),
        DTColumnBuilder.newColumn('description').withTitle("Observación").withOption('defaultContent', ''),
    ];

    var loadRow = function () {

    };

    $scope.reloadData = function () {
        $scope.dtInstanceHealthDamageObservation.reloadData();
    };


    $scope.editWorkMedicine = function(id){
        if($scope.$parent != null){
            $scope.$parent.navToSection("form", "edit", id);
        }
    };

    $scope.viewWorkMedicine = function(id){
        if($scope.$parent != null){
            $scope.$parent.navToSection("form", "view", id);
        }
    };

}]);