'use strict';
/**
 * controller for Customers
 */
app.controller('customerRoadSafetyReportCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert) {


        var log = $log;
        var request = {};

        $scope.question = {
            rate: null
        };

        $scope.currentId = $scope.$parent.currentId;

        request.operation = "diagnostic";
        request.customer_id = $stateParams.customerId;
        request.road_safety_id = $scope.$parent.currentId;


        $scope.rate_id = 0;

        $scope.categories = [];

        $scope.rates = [];

        $scope.totalAvg = 0;


        var initialize = function () {
            var req = {};
            req.customer_id = $stateParams.customerId;
            req.road_safety_id = $scope.$parent.currentId;

            $http({
                method: 'POST',
                url: 'api/customer/road-safety/list-data',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $timeout(function () {
                    $scope.cycles = response.data.data.cycle;
                    $scope.rates = response.data.data.rateReal;
                });

            }).catch(function (e) {
                SweetAlert.swal("Error Cargando Reportes", "Se ha presentado un error durante la consulta de los reportes para el diagnóstico por favor intentelo de nuevo", "error");
            }).finally(function () {

            });
        }

        initialize();

        $scope.changeRate = function (item, model, question) {
            log.info(item);
            log.info(model);
            $timeout(function () {
                $scope.rate_id = item.id;
                loadData();
            });
        };

        var loadData = function () {

            var req = {};

            req.customer_id = $stateParams.customerId;
            req.road_safety_id = $scope.currentId;
            req.rate_id = $scope.rate_id;

            $http({
                method: 'POST',
                url: 'api/customer/road-safety-item-report',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                $scope.cycles = response.data.data.roadSafetyList;
                $scope.loaded = true;

            }).catch(function (e) {
                SweetAlert.swal("Error Consultando Preguntas", "Se ha presentado un error durante la consulta del cuestionario, por favor intentelo de nuevo.", "error");
            }).finally(function () {

            });
        };

        loadData();

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

        $scope.clearFilter = function()
        {
            $scope.question = {
                rate: null
            };
            $scope.rate_id = 0;
            loadData();
        }

        $scope.onSummaryExportPdf = function()
        {
            //jQuery("#downloadDocument")[0].src = "api/diagnostic/summary/export-pdf?id=" + $scope.$parent.currentDiagnostic;
            kendo.drawing.drawDOM($("#content"))
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
                        fileName: "Auto-Evaluacion-Seguridad-Vial-Reporte.pdf",
                        proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                    });
                });
        }

        $scope.onSummaryExportExcel = function()
        {
            jQuery("#downloadDocument")[0].src = "api/customer/road-safety-item/export-excel?id=" + $scope.currentId + "&rate=" + $scope.rate_id;
        }

    }]);