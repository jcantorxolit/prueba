'use strict';
/**
 * controller for Customers
 */
app.controller('customerConfigWizardSummaryCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert','$document',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document) {

        var log = $log;
        var request = {};
        log.info("loading..customerConfigWizardSummaryCtrl ");

        $scope.loading = true;
        $scope.customerId = $stateParams.customerId;;
        $scope.isView = false;
        $scope.wizards = [];

        // Chart.js Options
        $scope.optionsPie = {
            // Sets the chart to be responsive
            responsive: false,

            //Boolean - Whether we should show a stroke on each segment
            segmentShowStroke: true,

            //String - The colour of each segment stroke
            segmentStrokeColor: '#fff',

            //Number - The width of each segment stroke
            segmentStrokeWidth: 2,

            //Number - The percentage of the chart that we cut out of the middle
            percentageInnerCutout: 0, // This is 0 for Pie charts

            //Number - Amount of animation steps
            animationSteps: 100,

            //String - Animation easing effect
            animationEasing: 'easeOutBounce',

            //Boolean - Whether we animate the rotation of the Doughnut
            animateRotate: true,

            //Boolean - Whether we animate scaling the Doughnut from the centre
            animateScale: false,

            //String - A legend template
            legendTemplate: '<ul class="tc-chart-js-legend"><% for (var i=0; i<segments.length; i++){%><li><span style="background-color:<%=segments[i].fillColor%>"></span><%if(segments[i].label){%><%=segments[i].label%><%}%></li><%}%></ul>'
        };

        // Datatable configuration
        var loadList = function () {

            var req = {};
            req.operation = "diagnostic";
            req.customerId = $scope.customerId;

            return $http({
                method: 'POST',
                url: 'api/customer/config-sgsst/wizard/list',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    //$scope.wizards = response.data;


                    $scope.datasetwizardsData = [];
                    $scope.datasetwizardsLabels = [];
                    $scope.datasetwizardsValues = [];
                    $scope.datasetwizardsColors = [];

                    for (var j=0; j<response.data.data.length; j++) {
                        for (var k=0; k<response.data.data[j].data.length; k++) {
                            $scope.datasetwizardsLabels.push(response.data.data[j].data[k].label);
                            $scope.datasetwizardsValues.push(response.data.data[j].data[k].value);
                            $scope.datasetwizardsColors.push(response.data.data[j].data[k].color);
                        }
                        $scope.datasetwizardsData.push({
                            name: response.data.data[j].name,
                            created: response.data.data[j].created,
                            configured: response.data.data[j].configured,
                            pending: response.data.data[j].pending,
                            labels: $scope.datasetwizardsLabels,
                            datasets: [
                                {
                                    data: $scope.datasetwizardsValues,
                                    backgroundColor: $scope.datasetwizardsColors,
                                    hoverBackgroundColor: $scope.datasetwizardsColors
                                }
                            ]
                        });

                        $scope.datasetwizardsLabels = [];
                        $scope.datasetwizardsValues = [];
                        $scope.datasetwizardsColors = [];

                    }
                    $scope.wizards = $scope.datasetwizardsData;
                    console.log($scope.datasetwizardsData);

                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

        loadList();

        $scope.onOpenWizard = function () {
            if($scope.$parent != null){
                $scope.$parent.navToSection("form", "edit", 0);
            }
        };

        $scope.onRefresh = function () {
            loadList();
        };

    }]);