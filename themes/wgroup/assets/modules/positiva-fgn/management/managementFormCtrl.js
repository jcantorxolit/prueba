app.controller('positivafgnManagementFormCtrl',
    function ($scope, $stateParams, $log,  $state,
              $rootScope, $timeout, $http, SweetAlert, ListService, ModuleListService, PFManagementService, $cookies) {

        $scope.documentTypeList = $rootScope.parameters("employee_document_type");
        $scope.genderList = $rootScope.parameters("gender");
        $scope.action = $state.is("app.positiva-fgn.fgn-management-axis-programming") ? "programming" : "execution";
        PFManagementService.setAction($scope.action);

        $scope.sectionalList = [];
        $scope.periodoList = [];
        $scope.axisList = [];
        $scope.axisStats = {
            currentHours: 0,
            pendingHours: 0,
            hourPercentage: 0,
            currentActivitiesCom: 0,
            pendingActivitiesCom: 0,
            activityPercentageCom: 0,
            currentActivitiesCov: 0,
            pendingActivitiesCov: 0,
            activityPercentageCov: 0
        }

        $scope.infoBasic = {
            documentNumber: null,
            documentType: null,
            fullName: null,
            gender: null,
            id: null,
            sectional: null,
            period: null,
            axis: null
        };

        $scope.options = {
            unit: "%",
            readOnly: true,
            displayPrevious: true,
            barCap: 25,
            trackWidth: 20,
            barWidth: 20,
            trackColor: 'rgba(92,184,92,.1)',
            barColor: '#5BC01E',
            textColor: '#000'
        };

        function getSectionals() {
            var entities = [
                {name: 'positiva_fgn_consultant_all_sectional', criteria: {userId: window.currentUser.id}}
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.sectionalList = response.data.data.sectionalList;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        function getParams() {
            var entities = [
                {name: 'positiva_fgn_period', value: null },
                {name: 'info_basic', value: window.currentUser.id }
            ];

            ModuleListService.getDataList("/positiva-fgn-fgn-management/config", entities)
                .then(function (response) {
                    $timeout(function () {
                        $scope.periodoList = response.data.result.positivaFgnPeriod;
                        $scope.infoBasic = response.data.result.infoBasic;


                        var dataFormCached = $cookies.getObject('pfManagementForm-'+$scope.action+window.currentUser.id);
                        if(dataFormCached && dataFormCached.id === $scope.infoBasic.id){
                            $scope.infoBasic = dataFormCached;
                            $scope.getAxis();
                        }

                    });
                }, function (error) {
                    $scope.status = 'Unable to load activity data: ' + error.message;
                });
        }

        getSectionals();
        getParams();

        $scope.getAxis = function() {
            if($scope.infoBasic.id && $scope.infoBasic.period) {
                var entities = [
                    {name: 'axis_list', value: $scope.infoBasic.id, config: $scope.infoBasic.period.config, sectionalId: $scope.infoBasic.sectional.value },
                    {name: 'axis_stats', 
                        consultantId: $scope.infoBasic.id,
                        sectionalId: $scope.infoBasic.sectional.value,
                        period: $scope.infoBasic.period.value,
                        config: $scope.infoBasic.period.config,
                        action: $scope.action
                    },
                ];

                $cookies.putObject('pfManagementForm-'+$scope.action+window.currentUser.id, $scope.infoBasic);

                ModuleListService.getDataList("/positiva-fgn-fgn-management/config", entities)
                    .then(function (response) {
                        $timeout(function () {
                            $scope.axisList = response.data.result.axisList;
                            $scope.axisStats = response.data.result.axisStats;
                        });
                    }, function (error) {
                        $scope.status = 'Unable to load activity data: ' + error.message;
                    });
            }
        }

        $scope.openAxis = function (axis) {
            $scope.infoBasic.axis = axis;
            PFManagementService.setInfoBasic($scope.infoBasic);
            $state.go("app.positiva-fgn.fgn-management-activity");
        }

        $scope.onContinue = function () {
            $scope.infoBasic.axis = $scope.axisList[0];
            PFManagementService.setInfoBasic($scope.infoBasic);
            $state.go("app.positiva-fgn.fgn-management-activity");
        };


    });