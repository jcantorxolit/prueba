'use strict';
/**
 * controller for Customers
 */
app.controller('customerDiagnosticExpressMatrixInterventionCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', 
    '$document', '$location', '$translate', '$aside', 'ListService', 'ExpressDashboardService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, $location, $translate, $aside, ListService, ExpressDashboardService) {


        console.log('customerDiagnosticExpressMatrixInterventionCtrl');

        $scope.isView = $scope.customer.matrixType != 'E';  
        
        $scope.currentIndex = -1;

        $scope.filter = {            
            view: 'C',
            selectedWorkplace: ExpressDashboardService.getWorkplace(),
            selectedHazard: ExpressDashboardService.getHazard()            
        }

        ExpressDashboardService.setView($scope.filter.view);

        var onDestroyRefreshHazardList$ = $rootScope.$on('refreshHazardList', function (event, args) {   
            console.log('customerDiagnosticExpressMatrixInterventionCtrl refreshHazardList');  
            onLoadRecord();
        });

        $scope.$on("$destroy", function() {
            onDestroyRefreshHazardList$();            
        });

        var init = function() {
            $scope.entity = {
                id: $scope.filter.selectedHazard ? $scope.filter.selectedHazard.id : 0, 
                name: null,
                customerId: $stateParams.customerId,            
                workplaceId: $scope.filter.selectedWorkplace ? $scope.filter.selectedWorkplace.id : 0,                
                questionList: []                
            }
        }
        
        init();

        var onLoadRecord = function () {
            if ($scope.entity.id) {
    
                var $criteria = {
                    id: $scope.entity.id,
                    customerId: $scope.entity.customerId,
                    workplaceId: $scope.entity.workplaceId,
                    isHistorical: $scope.filter.view == 'H',
                };
    
                $http({
                    method: 'POST',
                    url: 'api/customer-config-question-express-hazard-intervention',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    data: $.param({
                        data: JSON.stringify($criteria)
                    })
                }).then(function (response) {
                    $timeout(function () {       
                        if (response.data.result != null) {
                            $scope.entity = response.data.result;

                            if ($scope.entity.questionList.length > 0) {
                                if ($scope.currentIndex == -1) {                               
                                    $scope.onSelectQuestion($scope.entity.questionList[0], 0);                                
                                } else {                                    
                                    var $currentQuestion = ExpressDashboardService.getQuestion();

                                    var $indexOf = $scope.entity.questionList.findIndex(function(element) {
                                        return element.id == $currentQuestion.id;
                                    });
    
                                    if ($indexOf == -1) {
                                        $scope.onSelectQuestion($scope.entity.questionList[0], 0);
                                    }
                                }
                            } else {
                                $scope.currentIndex = -1;
                                $rootScope.$emit('clearIntervention', { message: 'onClearIntervention' });
                            }
                        } else {
                            $scope.currentIndex = -1;
                            $scope.entity.questionList = [];
                            $rootScope.$emit('clearIntervention', { message: 'onClearIntervention' });
                        }                        
                    });
                }).catch(function (e) {                
                    SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
                }).finally(function () {
        
                });
            }
        }
    
        onLoadRecord();

        $scope.onSelectQuestion = function(question, $index) {
            $scope.currentIndex = $index;
            ExpressDashboardService.setQuestion(question);
            $rootScope.$emit('selectQuestion', { message: 'onSelect Question' });
        }

        $scope.onChangeView = function() {
            $scope.currentIndex = -1;
            ExpressDashboardService.setView($scope.filter.view);
            onLoadRecord();
        }

        $scope.onBack = function() {
            if ($scope.$parent != null) {
                ExpressDashboardService.setIsBack(true);
                $scope.$parent.navToSection("hazard", "list", 0);
            }
        }

    }]);
