'use strict';
/**
 * controller for Customers
 */
app.controller('customerSafetyInspectionSummaryCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert) {
        
        var log = $log;

        var currentId = $scope.$parent.currentSafetyInspectionId;
        
		$scope.dtOptionsSafetyInspectionSummary = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.operation = "diagnostic";
                    d.customerId = $stateParams.customerId;
                    d.safetyInspectionId = currentId;
                    
                    return d;
                },
                url: 'api/customer/safety-inspection/summary',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function (data) {
                    $timeout(function () {
                        //$scope.$parent.setDataSummary(data.responseJSON.data);
                    });
                }
            })
            .withDataProp('data')
            .withOption('order', [[0, 'desc']])
            .withOption('serverSide', true).withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {

                return true;
            })
            .withOption('fnDrawCallback', function () {

            })
            .withDOM('tr')
            .withOption('language', {
                //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
            })


            .withOption('createdRow', function (row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });
        ;

        $scope.dtColumnsSafetyInspectionSummary = [
            DTColumnBuilder.newColumn('name')
                .withTitle("Lista")
                .withOption('width', 400),

            DTColumnBuilder.newColumn('questions')
                .withTitle("Preguntas")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('answers')
                .withTitle("Respuestas")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('advance')
                .withTitle("Avance (%)")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('average')
                .withTitle("Promedio Total (%)")
                .withOption('width', 200),

            DTColumnBuilder.newColumn(null)
                .withTitle("Estado")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-danger';
                    var text = 'Sin Iniciar';

                    if (parseInt(data.answers) == parseInt(data.questions))
                    {
                        text = 'Completado';
                        label = 'label label-info';
                    }
                    else if (parseInt(data.answers) > 0) {
                        text = 'Iniciado';
                        label = 'label label-success';
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';

                    return status;
                })
                .notSortable()
        ];

        
        $scope.dtInstanceSafetyInspectionSummaryCallback = function (instance) {
            $scope.dtInstanceSafetyInspectionSummary = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceSafetyInspectionSummary.reloadData();
        };


        $scope.onEditSafetyInspection = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("form", "edit", currentId);
            }
        };

        $scope.cancelEdition = function (index) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list");
            }
        };

        $scope.onExportPdf = function()
        {
            kendo.drawing.drawDOM($(".safety-inspection-export-pdf"))
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
                        fileName: "Inspeccion-seguridad.pdf",
                        proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                    });
                });
        }

        $scope.onExportExcel = function()
        {
            jQuery("#downloadDocument")[0].src = "api/customer/safety-inspection/summary-export-excel?id=" + $scope.$parent.currentSafetyInspectionId;
        }

        $scope.onViewReport = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("report", "report", $scope.$parent.currentSafetyInspectionId);
            }
        };

    }]);