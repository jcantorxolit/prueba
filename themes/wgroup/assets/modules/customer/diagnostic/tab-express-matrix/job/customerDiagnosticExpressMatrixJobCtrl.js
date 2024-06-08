'use strict';
/**
 * controller for Customers
 */
app.controller('customerDiagnosticExpressMatrixJobCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', 
    '$document', '$location', '$translate', '$aside', 'ListService', 'ExpressMatrixService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, $location, $translate, $aside, ListService, ExpressMatrixService) {
           

        $scope.isView = $scope.customer.matrixType != 'E';
        
        var onDestroyWizardNavigate$ = $rootScope.$on('wizardNavigate', function (event, args) {                     
            if (args.newValue == 1) {
                init()    
                getList();            
            }            
        });

        $scope.$on("$destroy", function() {
            onDestroyWizardNavigate$();            
        });
        
        $scope.filter = {
            selectedWorkPlace: null                     
        }

        var init = function() {
            $scope.entity = {
                id: 0,
                processList: []
            }
        }
    
        init()
        
        function getList() {
            var entities = [
                { name: 'customer_express_matrix_workplace_process_list',  criteria: { customerId:  $stateParams.customerId } }, 
                { name: 'customer_express_matrix_job_list',  criteria: { customerId:  $stateParams.customerId } },                 
            ];

            ListService.getDataList(entities)
                .then(function (response) {                
                    $scope.workplaceList = response.data.data.customerExpressMatrixWorkplaceList; 
                    $scope.availableJobList = response.data.data.customerExpressMatrixJobList;                     
                    
                    $scope.isWorkplaceDisabled = ExpressMatrixService.getWorkplaceId() != null; 
                
                    if (ExpressMatrixService.getWorkplaceId() != null) {
                        var workplace = $scope.workplaceList.find(function(element) {
                            return element.id == ExpressMatrixService.getWorkplaceId();
                        });
    
                        if (workplace) {
                            $scope.filter.selectedWorkPlace = workplace;
                            $scope.onSelectWorkPlace();
                        }
                    }

                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        var onLoadRecord = function ($id) {
            if ($id) {
                var req = {
                    id: $id
                };
    
                $http({
                    method: 'GET',
                    url: 'api/customer-config-workplace/get',
                    params: req
                })
                .catch(function (e, code) {
    
                })
                .then(function (response) {                    
                    $scope.entity = response.data.result;                       
                    $rootScope.$emit('loadWorkplace', { newValue: response.data.result, message: 'onLoad Workplace' });
                }).finally(function () {
    
                });
            }
        }        

        $scope.onSelectWorkPlace = function() {
            var $id = $scope.filter.selectedWorkPlace ? $scope.filter.selectedWorkPlace.id : 0;
            onLoadRecord($id);
        }



    }]);
