'use strict';
/**
 * controller for Customers
 */
app.controller('certificateReportExpirationListCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder','$compile','toaster','$state','$rootScope', 'SweetAlert','$http', '$timeout', 'ListService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope,SweetAlert, $http, $timeout, ListService) {

        var log = $log;
     
        log.info("certificateReportExpirationListCtrl");

        $scope.isAgent = $rootScope.isAgent();
        $scope.isAdmin = $rootScope.isAdmin();
        $scope.isCustomer = $rootScope.isCustomer();

        if ($scope.isAgent) {
            $state.go("app.clientes.list");
        } else if ($scope.isCustomer) {
            //$state.go("app.clientes.view", {"customerId":$rootScope.currentUser().company});
        }

        $scope.filter = {
            selectedMonth: null,
            selectedYear: null,
        };

        getList();

        function getList() {
            var entities = [
                { name: 'month', value: null },                
                { name: 'certificate_grade_participant_year', value: null }
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.months = response.data.data.month;
                    $scope.years = response.data.data.certificateGradeParticipantYear;                    
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }
                
		$scope.dtOptionsCertificateExpiration = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    if ($scope.isCustomer) {
                        d.customerId = $rootScope.currentUser().company;
                    }
                    d.year = $scope.filter.selectedYear ? $scope.filter.selectedYear.value : null;
                    d.month = $scope.filter.selectedMonth ? $scope.filter.selectedMonth.value : null;
                    return JSON.stringify(d);
                },
                url: 'api/certificate-grade-participant/expiration-v2',
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
        ;

        $scope.dtColumnsCertificateExpiration = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";

                    var disabled = "";

                    var downloadTemplate = '<a target="_self" class="btn btn-primary btn-xs downloadRow lnk" href="#" uib-tooltip="Descargar certificado" data-id="' + data.id + '" ' + disabled + '  >' +
                        '   <i class="fa fa-download"></i></a> ';

                    actions += downloadTemplate;

                    return actions;
                }),

            DTColumnBuilder.newColumn('documentType').withTitle("Tipo de Identificación").withOption('width', 200),
            DTColumnBuilder.newColumn('identificationNumber').withTitle("Identificación").withOption('width', 200),
            DTColumnBuilder.newColumn('name').withTitle("Nombres").withOption('width', 200),
            DTColumnBuilder.newColumn('lastName').withTitle("Apellidos").withOption('width', 200),
            DTColumnBuilder.newColumn('customer').withTitle("Empresa").withOption('width', 200),
            DTColumnBuilder.newColumn('grade').withTitle("Curso").withOption('width', 200),
            DTColumnBuilder.newColumn('certificateCreatedAt').withTitle("Fecha").withOption('width', 200),
            DTColumnBuilder.newColumn('certificateExpirationAt').withTitle("Fecha Vencimiento").withOption('width', 200),
        ];

        var loadRow = function () {
            angular.element("#dtCertificateExpiration a.downloadRow").on("click", function () {
                var id = angular.element(this).data("id");                
                angular.element("#downloadDocument")[0].src = "api/certificate-grade-participant-certificate/download?id=" + id;
            });            
        };
      
        $scope.dtInstanceCertificateExpirationCallback = function(instance) {
            $scope.dtInstanceCertificateExpiration = instance;
        };

        $scope.reloadData = function () {            
            $scope.dtInstanceCertificateExpiration.reloadData();
        };

        $scope.onSelectYear = function() {
            $scope.reloadData();
        }

        $scope.onClearYear = function() {
            $scope.filter.selectedYear = null;
            $scope.reloadData();
        }

        $scope.onSelectMonth = function() {
            $scope.reloadData();
        }

        $scope.onClearMonth = function() {
            $scope.filter.selectedMonth = null;
            $scope.reloadData();
        }
    }
]);
