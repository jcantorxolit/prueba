'use strict';
/**
 * controller for Customers
 */
app.controller('customerSafetyInspectionReportCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert) {


        var log = $log;
        var request = {};

        $scope.question = {
            rate: null
        };

        request.operation = "diagnostic";
        request.customer_id = $stateParams.customerId;
        request.safety_inspection_id = $scope.$parent.currentSafetyInspectionId;

        $scope.action = null;
        $scope.categories = [];

        $scope.rates = $rootScope.parameters("wg_safety_inspection_action");

        $scope.changeRate = function (item, model, question) {
            $timeout(function () {
                $scope.action = item.value;
                loadData();
            });
        };

        var loadData = function () {

            var req = {};
            req.safetyInspectionId = $scope.$parent.currentSafetyInspectionId;
            req.action = $scope.action;

            $http({
                method: 'POST',
                url: 'api/customer/safety-inspection-list-item/report',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {

                // Actualiza categorias

                $scope.categories = response.data.data.wizard;

                $scope.loaded = true;

            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error Consultando Preguntas", "Se ha presentado un error durante la consulta del cuestionario, por favor intentelo de nuevo.", "error");
            }).finally(function () {

            });
        };


        loadData();

        $scope.onEditSafetyInspection = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("form", "edit", $scope.$parent.currentSafetyInspectionId);
            }
        };

        $scope.cancelEdition = function (index) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("summary", "summary", $scope.$parent.currentSafetyInspectionId);
            }
        };

        $scope.clearFilter = function () {
            $scope.question = {
                rate: null
            };

            $scope.action = null;
            loadData();
        }

        $scope.onSummaryExportPdf = function () {
            //jQuery("#downloadDocument")[0].src = "api/diagnostic/summary/export-pdf?id=" + $scope.$parent.currentSafetyInspectionId;
            kendo.drawing.drawDOM($(".export-pdf"))
                .then(function (group) {
                    // Render the result as a PDF file
                    return kendo.drawing.exportPDF(group, {
                        paperSize: "auto",
                        margin: {left: "1cm", top: "1cm", right: "1cm", bottom: "1cm"}
                    });
                })
                .done(function (data) {
                    // Save the PDF file
                    kendo.saveAs({
                        dataURI: data,
                        fileName: "InspeccionesSeguridadReporte.pdf",
                        proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                    });
                });
        }

        $scope.onSummaryExportExcel = function () {
            jQuery("#downloadDocument")[0].src = "api/customer/safety-inspection/export-excel?id=" + $scope.$parent.currentSafetyInspectionId;
        }

    }]);