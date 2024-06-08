'use strict';
/**
 * controller for Customers
 */
app.controller('customerDiagnosticExpressMatrixDashboardHazardCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert',
    '$document', '$location', '$translate', '$aside', 'ListService', 'ExpressDashboardService', 'ExpressMatrixService',
    function($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
        $rootScope, $timeout, $http, SweetAlert, $document, $location, $translate, $aside, ListService, ExpressDashboardService, ExpressMatrixService) {


        $scope.tabIndex = 0;

        $scope.isView = $scope.customer.matrixType != 'E';

        $scope.filter = {
            selectedWorkPlace: null,
            selectedHazard: null,
            selectedYear: null
        }

        var onDestroyWizardNavigate$ = $rootScope.$on('wizardNavigate', function(event, args) {
            if (args.newValue == 3) {
                getList();
            }
        });

        $scope.$on("$destroy", function() {
            onDestroyWizardNavigate$();
        });

        if (ExpressDashboardService.getIsBack()) {
            getList();
        }

        function getList() {
            var entities = [{
                name: 'customer_express_matrix_workplace_with_qa',
                criteria: {
                    customerId: $stateParams.customerId
                }
            }, ];

            if (ExpressDashboardService.getWorkplace() != null) {
                $scope.filter.selectedWorkPlace = ExpressDashboardService.getWorkplace();
            }

            ListService.getDataList(entities)
                .then(function(response) {
                    $scope.workplaceList = response.data.data.customerExpressMatrixWorkplaceList;

                    $scope.isWorkplaceDisabled = ExpressMatrixService.getWorkplaceId() != null;

                    if (ExpressDashboardService.getIsBack()) {
                        ExpressDashboardService.setIsBack(null);
                        if (ExpressDashboardService.getWorkplace() != null) {

                            $scope.filter.selectedWorkPlace = ExpressDashboardService.getWorkplace();
                            $scope.hazardList = ExpressDashboardService.getHazardList();
                            $scope.tabIndex = ExpressDashboardService.getTabIndex();
                            $scope.filter.selectedHazard = $scope.hazardList[$scope.tabIndex];
                            getHazardStatsList();
                        }
                    } else {
                        if (ExpressMatrixService.getWorkplaceId() != null) {
                            var workplace = $scope.workplaceList.find(function(element) {
                                return element.id == ExpressMatrixService.getWorkplaceId();
                            });

                            if (workplace) {
                                $scope.filter.selectedWorkPlace = workplace;
                                $scope.onSelectWorkPlace();
                            }
                        }
                    }

                }, function(error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        function getHazardList() {
            var $criteria = {
                customerId: $stateParams.customerId,
                workplaceId: $scope.filter.selectedWorkPlace ? $scope.filter.selectedWorkPlace.id : 0,
                id: $scope.filter.selectedHazard ? $scope.filter.selectedHazard.id : 0,
                canExecuteBulkOperation: 0
            };

            var entities = [
                { name: 'customer_express_matrix_hazard_list', criteria: $criteria }
            ];

            ListService.getDataList(entities)
                .then(function(response) {
                    $scope.hazardList = response.data.data.customerExpressMatrixHazardList;
                    ExpressDashboardService.setHazardList(response.data.data.customerExpressMatrixHazardList);

                    if ($scope.hazardStatsList === undefined || $scope.hazardStatsList == null || $scope.hazardStatsList.length == 0) {
                        ExpressDashboardService.setTabIndex($scope.tabIndex);
                        $scope.filter.selectedHazard = $scope.hazardList[$scope.tabIndex];
                        getHazardStatsList();
                    }

                }, function(error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        function getHazardStatsList() {
            var $criteria = {
                customerId: $stateParams.customerId,
                workplaceId: $scope.filter.selectedWorkPlace ? $scope.filter.selectedWorkPlace.id : 0,
                id: $scope.filter.selectedHazard ? $scope.filter.selectedHazard.id : 0,
                year: $scope.filter.selectedYear ? $scope.filter.selectedYear.value : null
            };

            var entities = [
                { name: 'customer_express_matrix_hazard_stats_list', criteria: $criteria }
            ];

            ListService.getDataList(entities)
                .then(function(response) {
                    $scope.hazardStatsList = response.data.data.customerExpressMatrixHazardStatsList;

                    $scope.yearList = response.data.data.customerExpressMatrixQuestionInterventionYearList;

                    if ($scope.filter.selectedYear == null) {
                        $scope.filter.selectedYear = $scope.yearList.length > 0 ? $scope.yearList[0] : null;
                    }
                }, function(error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        $scope.onSelectWorkPlace = function() {
            getHazardList();
            ExpressDashboardService.setWorkplace($scope.filter.selectedWorkPlace);
        }

        $scope.onSelectYear = function() {
            getHazardStatsList();
        }

        $scope.onSelectHazard = function(hazard, $index) {
            ExpressDashboardService.setTabIndex($index);
            $scope.filter.selectedHazard = hazard;
            getHazardStatsList();
        }

        $scope.onAddIntervention = function(hazard) {
            ExpressDashboardService.setHazard(hazard);
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("intervention", "edit", 0);
            }
        }

        $scope.onViewSummary = function() {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("summary", "edit", 0);
            }
        }

        //----------------------------------------------------------------------------EXPORT
        $scope.onExportPdf = function(hazard, $index) {
            kendo.drawing.drawDOM($(".express-matrix-hazard-export-pdf-" + $index))
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
                        fileName: "TABLERO_DE_PELIGRO_" + hazard.name + ".pdf",
                        proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                    });
                });
        }

        $scope.onExportExcel = function(hazard) {
            var data = {
                customerId: $stateParams.customerId,
                workplaceId: $scope.filter.selectedWorkPlace ? $scope.filter.selectedWorkPlace.id : 0,
                id: hazard.id,
                isHistorical: 0
            };

            angular.element("#downloadHazardExcel")[0].src = "api/customer-config-question-express-intervention/export-excel?data=" + Base64.encode(JSON.stringify(data));
        }

    }
]);