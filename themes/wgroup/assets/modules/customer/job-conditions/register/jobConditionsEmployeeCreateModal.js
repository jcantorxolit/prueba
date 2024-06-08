app.controller('ModalInstanceSideJobConditionsEmployeeCreateCtrl',
    function ($scope, $stateParams, $log, toaster, $state, $rootScope, $timeout, $http, SweetAlert, $document, $uibModalInstance, $aside, $filter) {

        var $formInstance = null;
        $scope.genders = $rootScope.parameters("gender");
        $scope.documentTypes = $rootScope.parameters("employee_document_type");

        $scope.showWorkplaces = true;
        $scope.job = true;

        var onInit = function () {
            $scope.employee = {
                id: 0,
                customerId: $stateParams.customerId,
                isActive: false,
                contractType: null,
                occupation: '',
                job: null,
                workPlace: null,
                salary: 0,
                isAuthorized: false,
                entity: {
                    id: 0,
                    documentType: null,
                    documentNumber: "",
                    expeditionPlace: "",
                    expeditionDate: "",
                    firstName: "",
                    lastName: "",
                    birthDate: "",
                    gender: null,
                    profession: null,
                    eps: null,
                    afp: null,
                    arl: null,
                    country: null,
                    state: null,
                    city: null,
                    rh: "",
                    riskLevel: 0,
                    neighborhood: "",
                    observation: "",
                    logo: "",
                    details: [],
                    isActive: false,
                    age: null
                },
                validityList: [],
            };

            if ($formInstance) {
                $formInstance.$setPristine(true);
            }
        };
        onInit();

        var loadWorkPlace = function () {
            var req = {};
            req.operation = "diagnostic";
            req.customerId = $stateParams.customerId;

            return $http({
                method: 'POST',
                url: 'api/customer/config-sgsst/workplace/listProcess',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $scope.workPlaces = response.data.data;
            });
        };

        loadWorkPlace();


        $scope.onSearchJob = function () {
            if (!$scope.employee.workPlace) {
                toaster.pop("error", "Validación", "Debe seleccionar un centro de trabajo válido");
                return;
            }

            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/common/modals/data_table_list_modal.htm",
                placement: 'right',
                windowTopClass: 'top-modal',
                size: 'md',
                backdrop: 'static',
                resolve: {
                    workPlace: function () {
                        return $scope.employee.workPlace;
                    }
                },
                controller: 'ModalInstanceSideCustomerJobConditionsJobsListCtrl',
                scope: $scope,
            });

            modalInstance.result.then(function (job) {
                if ($scope.jobs === undefined || $scope.jobs == null) {
                    $scope.jobs = [];
                }

                var result = $filter('filter')($scope.jobs, {id: job.id});
                if (result.length == 0) {
                    $scope.jobs.push(job);
                }

                $scope.employee.job = job;
                $scope.changeJob();
            });
        }


        $scope.changeJob = function (item, model) {
            $timeout(function () {
                if ($scope.employee.job != null && $scope.employee.id != 0) {
                    var req = {};
                    req.id = $scope.employee.id;
                    req.job_id = $scope.currentJobId;
                    return $http({
                        method: 'POST',
                        url: 'api/customer-employee/critical-activity/duplicate',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        data: $.param(req)
                    }).then(function (response) {
                        loadCriticalActivity();
                        $scope.reloadData();
                    }).catch(function (e) {
                        $log.error(e);
                        toaster.pop("error", "Error", "Error guardando el registro. Por favor verifique los datos ingresados!");
                    });
                }
            }, 400);
        };


        var loadCriticalActivity = function () {
            if ($scope.employee.job != null) {
                var req = {};
                req.operation = "diagnostic";
                req.customerId = $scope.customerId;
                req.jobId = $scope.employee.job.id;
                req.id = $scope.employee.id;

                return $http({
                    method: 'POST',
                    url: 'api/customer-employee/critical-activity/list',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    data: $.param(req)
                }).then(function (response) {
                    $timeout(function () {
                        $scope.activities = response.data.data;
                    });
                });
            } else {
                $scope.activities = [];
            }
        };


        $scope.form = {
            submit: function (form) {
                $formInstance = form;
                var firstError = null;
                if (form.$invalid) {

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
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                    return;

                } else {
                    save();
                }

            }
        };


        var save = function () {
            var req = {};
            var data = JSON.stringify($scope.employee);
            req.data = Base64.encode(data);

            return $http({
                method: 'POST',
                url: 'api/customer-employee/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                    $uibModalInstance.close($scope.employee);
                });
            }).catch(function (response) {
                SweetAlert.swal("Error de guardado", response.data.message, "error");
            });
        };


        $scope.onCancel = function () {
            $uibModalInstance.dismiss('cancel');
        }

    }
);

