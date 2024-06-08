'use strict';
/**
 * controller for Customers
 */
app.controller('customerDiagnosticExpressMatrixHazardCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', 
    '$document', '$location', '$translate', '$aside', 'ListService', 'ExpressMatrixService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, $location, $translate, $aside, ListService, ExpressMatrixService) {

            
        $scope.isView = $scope.customer.matrixType != 'E'; 

        $scope.isButtonContinueDisable = true;        

        var onDestroyWizardNavigate$ = $rootScope.$on('wizardNavigate', function (event, args) {                     
            if (args.newValue == 2) {
                getList();       
                $scope.isBackNavigationVisible = ExpressMatrixService.getWorkplaceId() != null 
            }            
        }); 
                
        var onDestroyModalClosedEvent$ = $rootScope.$on('modalClosedEvent', function (event) {                     
            if ($scope.filter.selectedWorkPlace != null) {
                $scope.onSelectWorkPlace();           
            }
        });

        $scope.$on("$destroy", function() {
            onDestroyWizardNavigate$();
            onDestroyModalClosedEvent$();
        });

    
        $scope.filter = {
            selectedWorkPlace: null                     
        }
        
        $scope.options = {
            unit: "%",
            //size: 300,
            readOnly: true,
            displayPrevious: true,
            barCap: 25,
            trackWidth: 20,
            barWidth: 20,
            trackColor: 'rgba(92,184,92,.1)',
            barColor: '#5BC01E',
            //prevBarColor: 'rgba(92,184,92,.2)',
            textColor: '#000'
        };

        var init = function() {
            $scope.entity = {
                id: 0,
                processList: []
            }
        }
    
        init()        

        function getList() {
            var entities = [
                {
                    name: 'customer_express_matrix_workplace_list', 
                    criteria: { 
                        customerId:  $stateParams.customerId,  isFullyConfigured: 1 
                    }
                }, 
            ];

            ListService.getDataList(entities)
                .then(function (response) {                
                    $scope.workplaceList = response.data.data.customerExpressMatrixWorkplaceList; 
                    
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

        function getHazardList() {
            var $criteria = { 
                customerId:  $stateParams.customerId,
                workplaceId: $scope.filter.selectedWorkPlace ? $scope.filter.selectedWorkPlace.id : 0,
                canExecuteBulkOperation: $scope.filter.selectedWorkPlace ? $scope.filter.selectedWorkPlace.canExecuteBulkOperation : 0,
            };
                        
            var entities = [
                { name: 'customer_express_matrix_hazard_list', criteria: $criteria }, 
                { name: 'customer_express_matrix_workplace_stats', criteria: $criteria },
            ];

            ListService.getDataList(entities)
                .then(function (response) {                
                    $scope.hazardList = response.data.data.customerExpressMatrixHazardList;         
                    $scope.stats = response.data.data.customerExpressMatrixWorkplaceStats; 
                    
                    var $hazardIsActiveList = $scope.hazardList.filter(function(hazard) {
                        return hazard.isActive && hazard.questions == hazard.answers;
                    })

                    $scope.isButtonContinueDisable = $hazardIsActiveList.length != $scope.hazardList.length;

                    if ($scope.filter.selectedWorkPlace) {
                        $scope.filter.selectedWorkPlace.canExecuteBulkOperation = 0;
                    }

                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });            
        }

        $scope.onSelectWorkPlace = function() {            
            getHazardList();
        }

        $scope.onAnswerQuestions = function(entity)
        {
            if (entity.isActive) {
                var modalInstance = $aside.open({                
                    templateUrl: $rootScope.app.views.urlRoot + "modules/customer/diagnostic/tab-express-matrix/hazard/customer_diagnostic_express_matrix_hazard_modal.htm",
                    placement: 'right',
                    //size: 'lg',
                    backdrop: 'static',
                    controller: 'ModalInstanceSideCustomerDiagnosticExpressMatrixHazardCtrl',
                    scope: $scope,
                    resolve: {
                        entity: function () {
                            return entity;
                        },
                        isView: function () {
                            return $scope.isView;
                        },
                        hazardList: function() {
                            return $scope.hazardList;
                        }
                    }
                });
                modalInstance.result.then(function () {
                    console.info('Modal closed at: ' + new Date());  
                }, function () {
                    console.info('Modal dismissed at: ' + new Date());                   
                });
            }
        }       
        
        $scope.onBack = function() {
            $rootScope.$emit('wizardGoTo', { newValue: 1 });
        }

        $scope.onContinue = function() {
            $rootScope.$emit('wizardGoTo', { newValue: 3 });
        }

    }]);
