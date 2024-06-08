'use strict';
/**
 * controller for Customers
 */
app.controller('jobConditionsIndicatorDashboardCtrl',
    function($scope, $stateParams, $log, $compile, toaster, $state, $rootScope, $timeout, $http, ngNotify, SweetAlert, $aside, CustomerJobConditionsIndicatorService) {

        $scope.dateList = [];
        $scope.locationList = [];
        $scope.evaluation = {};

        $scope.datePickerConfig = {
            culture: "es-CO",
            format: "dd/MM/yyyy"
        };

        var initialize = function() {
            var filters = CustomerJobConditionsIndicatorService.getFilters();
            if (filters) {
                $scope.entity = filters;
                loadDates(true);
                filter();
            } else {
                $scope.entity = {
                    customerId: $stateParams.customerId,
                    employee: null,
                    date: null,
                    location: null
                };
            }
        };

        initialize();


        $scope.form = {
            submit: function(form) {
                $scope.Form = form;

                if (form.$valid) {
                    filter();
                    return;
                }

                var field = null,
                    firstError = null;
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
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e intentalo de nuevo.", "error");
            },
            reset: function() {
                $scope.Form.$setPristine(true);
                initialize();
            }
        };

        function filter() {
            var data = JSON.stringify({evaluationId: $scope.entity.location.evaluationId});
            var req = {
                data: Base64.encode(data)
            };

            return $http({
                method: 'POST',
                url: 'api/customer-jobconditions/indicators/get-indicator-by-evaluation',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function(response) {
                $scope.evaluation = response.data.result;
            }).catch(function(e) {
                $log.error(e);
                SweetAlert.swal("Error al consultar", e.data.message, "error");
            });
        };


        $scope.onSearchEmployee = function() {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/common/modals/employee_list_modal.htm",
                placement: 'right',
                windowTopClass: 'top-modal',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideJobConditionsIndicatorEmployeeListCtrl',
                scope: $scope,
            });

            modalInstance.result.then(function(response) {
                $scope.entity.employee = response.entity;
                $scope.entity.date = null;
                $scope.entity.location = null;
                $scope.evaluation.id = '';
                loadDates(false);
            });
        };

        $scope.onChangeDate = function() {
            if ($scope.entity.date) {
                $scope.evaluation.id = '';
                $scope.locationList = $scope.entity.date.locations;

                // preseleccionar si sólo hay uno
                if ($scope.locationList.length == 1) {
                    $scope.entity.location = $scope.locationList[0];
                    $scope.onChangeLocation();
                } else {
                    $scope.entity.location = null
                }
            }
        };

        $scope.onChangeLocation = function() {
            if ($scope.entity.location) {
                $scope.evaluation.id = '';
            }
        };

        $scope.clear = function() {
            $scope.evaluation.id = '';
        }

        $scope.onGoToGeneralIndicators = function() {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("indicators", "list", 0);
            }
        };

        $scope.onGoToInterventionPlans = function(evaluationId, classificationId) {
            CustomerJobConditionsIndicatorService.setFilters($scope.entity);
            CustomerJobConditionsIndicatorService.setEvaluationId(evaluationId);
            CustomerJobConditionsIndicatorService.setClassificationId(classificationId);

            if ($scope.$parent != null) {
                $scope.$parent.navToSection("intervention", "edit", 0);
            }
        };

        $scope.onGoToBoardGeneral = function() {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("general", "edit", 0);
            }
        };


        function loadDates(filterFromState) {
            var data = JSON.stringify({
                customerId: $stateParams.customerId,
                employeeId: $scope.entity.employee.id
            });
            var req = {
                data: Base64.encode(data)
            };

            return $http({
                method: 'POST',
                url: 'api/customer-jobconditions/indicators/get-dates-evaluations-by-employees',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function(response) {
                $scope.dateList = response.data.result;

                if ($scope.dateList.length == 1) {
                    $scope.entity.date = $scope.dateList[0];
                }

                if (filterFromState) {
                    $scope.locationList = $scope.entity.date.locations;
                } else {
                    $scope.onChangeDate();
                }

            }).catch(function(e) {
                $log.error(e);
                SweetAlert.swal("Error al consultar", e.data.message, "error");
            });
        };

        //----------------------------------------------------------------------------EXPORT
        $scope.onExportPdf = function(hazard, $index) {
            console.log(hazard.employee.fullName);
            kendo.drawing.drawDOM($(".job-conditions-export-pdf"))
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
                        fileName: "TABLERO_DE_CONDICIONES_INSEGURAS_" + hazard.employee.fullName + ".pdf",
                        proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                    });
                });
        }

        $scope.onExportExcel = function(hazard) {
            ngNotify.set('El archivo se está generando.', {
                position: 'bottom',
                sticky: true,
                type: 'warning',
                button: true,
                html: true
            });

            var entity = {
                customerId: hazard.customerId,
                employeeId: hazard.employee.id,
                date: hazard.date.date,
                location: hazard.location.value,
                isHistorical: 0
            };

            var req = {};
            var data = JSON.stringify(entity);
            req.data = Base64.encode(data);

            $http({
                method: 'POST',
                url: 'api/customer-jobconditions/indicators/export-excel',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function(response) {
                angular.element("#downloadHazardExcel")[0].src = "api/customer-jobconditions/indicators/export-excel?data=" + Base64.encode(data);
                ngNotify.set('Se genero correctamente el archivo', {
                    position: 'bottom',
                    sticky: true,
                    type: 'success',
                    button: true,
                    html: true
                });
            }).catch(function(response) {
                ngNotify.set('No se cuenta con información para generar el reporte', {
                    position: 'bottom',
                    sticky: false,
                    type: 'warning',
                    button: false,
                    html: true
                });
            }).finally(function() {});
        }

    });