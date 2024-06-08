'use strict';
/**
 * Lazy collection that is backed by a concrete collection
 *
 * @author David Blandon <david.blandon@gmail.com>
 * @since  1.0
 */
app.controller('customerConfigWizardEditCtrl', ['$scope', '$location', '$stateParams', '$log',
    '$compile', '$state', 'SweetAlert', '$rootScope', '$http', '$timeout', '$uibModal', 'flowFactory', 'cfpLoadingBar', '$filter',
    function ($scope, $location, $stateParams, $log, $compile,  $state,
              SweetAlert, $rootScope, $http, $timeout, $uibModal, flowFactory, cfpLoadingBar, $filter) {

        var url = $location.absUrl();

        if ($rootScope.app.instance == 'isa') {
            $scope.matrixTabLabel1 = "GRUPO OCUPACIONAL O INSTALACIÓN";
            $scope.matrixTabLabel2 = "SUBESTACIÓN";
            $scope.matrixTabLabel3 = "UBICACIÓN, SITIO O ÁREA";
            $scope.matrixTabLabel4 = "LABOR / TAREA";
        } else {
            $scope.matrixTabLabel1 = "CENTROS DE TRABAJO";
            $scope.matrixTabLabel2 = "MACROPROCESOS";
            $scope.matrixTabLabel3 = "PROCESOS";
            $scope.matrixTabLabel4 = "ACTIVIDADES";
        }

        $scope.currentStep = 0;

        $scope.form = {

            next: function (form) {

                $scope.toTheTop();

                if (form.$valid) {
                    nextStep();
                } else {
                    var field = null, firstError = null;
                    for (field in form) {
                        if (field[0] != '$') {
                            if (firstError === null && !form[field].$valid) {
                                firstError = form[field].$name;
                            }

                            if (form[field].$pristine) {
                                form[field].$dirty = true;
                            }
                        }
                    }

                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                }
            },
            prev: function (form) {
                $scope.toTheTop();
                prevStep();
            },
            goTo: function (form, i) {
                if (parseInt($scope.currentStep) > parseInt(i)) {
                    $scope.toTheTop();
                    goToStep(i);

                } else {

                    //if (form.$valid) {
                    $scope.toTheTop();
                    goToStep(i);

                    //} else
                    //    errorMessage();
                }
            },
            reset: function (form) {

                $scope.report = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };

        var nextStep = function () {
            $scope.currentStep++;
        };

        var prevStep = function () {
            $scope.currentStep--;
        };

        var goToStep = function (i) {
            $scope.currentStep = i;
        };

        $scope.onCancel = function()
        {
            if($scope.$parent != null){
                $scope.$parent.navToSection("list", "list", 0);
            }
        }

    }]);
