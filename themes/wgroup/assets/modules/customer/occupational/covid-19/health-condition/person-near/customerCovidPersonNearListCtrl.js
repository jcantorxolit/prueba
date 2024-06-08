'use strict';
/**
 * controller for Customers
 */
app.controller('customerCovidPersonNearListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$aside', 'CustomerCovidService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $aside, CustomerCovidService) {


        $scope.exportExcel = $scope.exportExcel;
        console.log($scope.exportExcel)
        var currentId = CustomerCovidService.getDailyId(); 
        $scope.dtInstanceCovid = {};
		$scope.dtOptionsCovid = DTOptionsBuilder.newOptions()
            .withBootstrap()
			.withOption('responsive', true)
            .withOption('ajax', {                
				data: function (d) {
                    d.customerCovidId = currentId;
                    return JSON.stringify(d);
                },
                url: 'api/customer-covid-person-near',
				contentType: "application/json",
                type: 'POST',
                beforeSend: function () {
                },
                complete: function () {
                }
            })
            .withDataProp('data')
            .withOption('order', [[0, 'desc']])
            .withOption('serverSide', true)
			.withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                return true;
            })
            .withOption('fnDrawCallback', function () {
            })
            .withOption('language', {
            })

            .withPaginationType('full_numbers')
            .withOption('createdRow', function (row, data, dataIndex) {
                $compile(angular.element(row).contents())($scope);
            });

        $scope.dtColumnsCovid = [
            DTColumnBuilder.newColumn('manacleId').withTitle("Id Manilla").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('distance').withTitle("Distancia").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('name').withTitle("Nombre").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('lastName').withTitle("Apellido").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('workplace').withTitle("Centro de Trabajo").withOption('width', 200).withOption('defaultContent', ''),
        ];


		$scope.dtInstanceCovidCallback = function (instance) {
            $scope.dtInstanceCovid = instance;
        };
		
        $scope.reloadData = function () {
			if ($scope.dtInstanceCovid != null) {				
				$scope.dtInstanceCovid.reloadData();
			}
        };

        $scope.onExportExcel = function() {
            var param = {
                customerCovidId: currentId
            };

            angular.element("#downloadDocument")[0].src = "api/customer-covid-person-near/export?data=" + Base64.encode(JSON.stringify(param));
        }

    }
]);