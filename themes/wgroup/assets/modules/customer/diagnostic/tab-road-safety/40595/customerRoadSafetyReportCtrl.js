'use strict';
/**
 * controller for Customers
 */
app.controller('customerRoadSafetyReport40595Ctrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', 'ListService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, ListService) {

        $scope.filter = {
            selectedRate: null
        };

        $scope.rates = [];

        $scope.currentId = $scope.$parent.currentId;

        getList();

        function getList() {

            var $criteria = {
                customerId: $stateParams.customerId,
                customerRoadSafetyId: $scope.currentId,
                rateId: $scope.filter.selectedRate ? $scope.filter.selectedRate.id : null
            };

            var entities = [
                {name: 'customer_road_safety_report_40595', value: null, criteria: $criteria},
                {name: 'road_safety_rate_40595', value: $stateParams.customerId},
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.rates = response.data.data.rateReal;
                    $scope.cycles = response.data.data.customerRoadSafetyReport;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        $scope.onSelectRate = function () {
            getList();
        };

        $scope.onClearRate = function()
        {
            $scope.filter.selectedRate = null;
            getList();
        }

        $scope.onContinue = function (index) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("edit", "edit", $scope.currentId);
            }
        };

        $scope.cancelEdition = function (index) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("summary", "summary", $scope.currentId);
            }
        };

        $scope.onSummaryExportPdf = function()
        {
            //jQuery("#downloadDocument")[0].src = "api/diagnostic/summary/export-pdf?id=" + $scope.$parent.currentDiagnostic;
            kendo.drawing.drawDOM($(".export-pdf-em-report-40595"))
                .then(function(group) {
                    // Render the result as a PDF file
                    return kendo.drawing.exportPDF(group, {
                        paperSize: "auto",
                        margin: { left: "1cm", top: "1cm", right: "1cm", bottom: "1cm" }
                    });
                })
                .done(function(data) {
                    // Save the PDF file
                    kendo.saveAs({
                        dataURI: data,
                        fileName: "Auto-Evaluacion-Estandares-Minimos-Reporte.pdf",
                        proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                    });
                });
        }

        $scope.onSummaryExportExcel = function()
        {
            var data = JSON.stringify({
                customerId: $stateParams.customerId,
                customerRoadSafetyId: $scope.currentId,
                rateId: $scope.filter.selectedRate ? $scope.filter.selectedRate.id : null
            });
            angular.element("#downloadDocument")[0].src = "api/customer-road-safety-item-40595/export-excel?data=" + Base64.encode(data);
        }

    }]);
