'use strict';
/**
 * controller for Customers
 */
app.controller('customerEmployeeDemographicStagingListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$document', '$filter', '$aside', 'ListService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, $filter, $aside, ListService) {

        var log = $log;
        var $exportUrl = '';

        getList();

        function getList() {

            var entities = [
                { name: 'export_url', value: null },
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $exportUrl = response.data.data.exportUrl.item;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }


        $scope.dtInstanceEmployeeDemographicStagingDT = {};
        $scope.dtOptionsEmployeeDemographicStagingDT = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.customerId = $stateParams.customerId;
                    d.sessionId = $scope.$parent.currentEmployee;

                    return JSON.stringify(d);
                },
                url: 'api/customer-employee-demographic-staging',
                contentType: "application/json",
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function () {
                }
            })
            .withDataProp('data')
            .withOption('order', [[1, 'asc']])
            .withOption('serverSide', true).withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                //log.info("fnPreDrawCallback");
                //Pace.start();
                return true;
            })
            .withOption('fnDrawCallback', function () {
                loadRow();
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

        $scope.dtColumnsEmployeeDemographicStagingDT = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var disabled = ""

                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-edit"></i></a> ';

                    var actions = data.isValid == 0 || !data.isValid ? editTemplate : '';

                    return actions;
                }),
                //<span class="badge badge-danger"> 6</span>
            DTColumnBuilder.newColumn(null).withTitle('Fila').withOption('width', 50)
                .renderWith(function (data, type, full, meta) {

                    var $class = data.isValid == 1 || data.isValid ? 'badge badge-success' : 'badge badge-danger';
                    var $icon = data.isValid == 1 || data.isValid ? ' <i class=" fa fa-check"></i>' : ' <i class=" fa fa-ban"></i>';

                    return '<span class="'+ $class +'">'  + data.index + $icon + '</span>';
                }),
            DTColumnBuilder.newColumn('documentNumber').withTitle("Nro Identificación").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('firstName').withTitle("Nombres").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('lastName').withTitle("Apellidos").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('typeHousing').withTitle("Tipo Vivienda").withOption('width', 100).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('antiquityCompany').withTitle("Antiguedad Empresa").withOption('width', 100).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('antiquityJob').withTitle("Antiguedad Cargo").withOption('width', 100).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('averageIncome').withTitle("Ingresos").withOption('width', 100).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('stratum').withTitle("Estrato").withOption('width', 100).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('scholarship').withTitle("G. Escolaridad").withOption('width', 180).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('race').withTitle("Raza").withOption('width', 180).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('workArea').withTitle("Área de Trabajo").withOption('width', 180).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('observation').withTitle("Observación").withOption('width', 280).withOption('defaultContent', '')
        ];

        var loadRow = function () {
            angular.element("#dataEmployeeDemographicStagingDT a.editRow").on("click", function () {
                var id = angular.element(this).data("id");
                onEdit(id);
            });
        }

        $scope.dtInstanceEmployeeDemographicStagingDTCallback = function(instance) {
            $scope.dtInstanceEmployeeDemographicStagingDT = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceEmployeeDemographicStagingDT.reloadData();
        };

        var onEdit = function (id) {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/employee/customer_employee_demographic_staging_modal.htm",
                placement: 'right',
                windowTopClass: 'top-modal',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerEmployeeDemographicStagingEditCtrl',
                scope: $scope,
                resolve: {
                    dataItem: function () {
                        return { id: id ? id : 0 };
                    },
                    isView : function() {
                        return $scope.isView;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.reloadData();
            }, function() {
                $scope.reloadData();
            });
        };

        $scope.onCancel = function () {
            $document.scrollTop(40, 2000);
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list");
            }
        }

        $scope.onProcess = function () {

            SweetAlert.swal({
                title: "Confirma la importación de los registros?",
                text: "Se importarán los registros válidos. Una vez realizado este proceso no se podrán realizar cambios.",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Si, confirmar!",
                cancelButtonText: "No, cancelar!",
                closeOnConfirm: true,
                closeOnCancel: true
            },
            function (isConfirm) {
                if (isConfirm) {

                    return $http({
                        method: 'POST',
                        url: $exportUrl + 'api/v1/customer-employee-demographic-import/confirm',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        data: $.param({
                            id: $stateParams.customerId,
                            sessionId: $scope.$parent.currentEmployee
                        })
                    }).then(function (response) {
                        $timeout(function () {
                            SweetAlert.swal("Registro", "La información ha sido importada satisfactoriamente", "success");
                            $scope.onCancel();
                        });
                    }).catch(function (e) {
                        $log.error(e);
                        SweetAlert.swal("Error de guardado", e.data.message, "error");
                    }).finally(function () {

                    });
                }
            });
        }

    }
]);

app.controller('ModalInstanceSideCustomerEmployeeDemographicStagingEditCtrl', function ($rootScope, $stateParams, $scope, dataItem, isView, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, ngNotify, $document, $filter, $aside, ListService) {


        $scope.onCloseModal = function () {
            $uibModalInstance.close(null);
        };

        $scope.onCancel = function () {
            $uibModalInstance.dismiss('cancel');
        };

        var log = $log;

        $scope.employees = [];


        getList();

        function getList() {
            var entities = [
                { name: 'type_housing', value: null },
                { name: 'antiquity', value: null },
                { name: 'frequency', value: null },
                { name: 'stratum', value: null },
                { name: 'civil_status', value: null },
                { name: 'scholarship', value: null },
                { name: 'race', value: null },
                { name: 'work_area', value: null },
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.typeHousings = response.data.data.type_housing;
                    $scope.antiquities = response.data.data.antiquity;
                    $scope.frequencies = response.data.data.frequency;
                    $scope.stratumList = response.data.data.stratum;
                    $scope.civilStatusList = response.data.data.civil_status;
                    $scope.scholarshipList = response.data.data.scholarship;
                    $scope.raceList = response.data.data.race;
                    $scope.workAreaList = response.data.data.work_area;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }


        var init = function () {
            $scope.entity = {
                id: dataItem.id
            };
        };

        init();

        $scope.onLoadRecord = function () {
            if ($scope.entity.id != 0) {
                $http({
                    method: 'GET',
                    url: 'api/customer-employee-demographic-staging/get',
                    params: {
                        id: $scope.entity.id
                    }
                })
                    .catch(function (e, code) {

                    })
                    .then(function (response) {
                        $timeout(function () {
                            $scope.entity = response.data.result;
                        });
                    }).finally(function () {

                    });
            }
        }

        $scope.onLoadRecord();

        $scope.form = {

            submit: function (form) {
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
                    log.info($scope.entity);
                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                    return;

                } else {
                    save();
                }

            },
            reset: function (form) {

            }
        };

        var save = function () {

            var req = {};

            var data = JSON.stringify($scope.entity);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer-employee-demographic-staging/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                    $scope.onCloseModal();
                });
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });

        };

        $scope.onSearchEmployeeList = function () {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/absenteeism/disability/customer_absenteeism_disability_employee_list_modal.htm",
                placement: 'left',
                windowTopClass: 'top-modal',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideDisabilityEmployeeListCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (employee) {
                var result = $filter('filter')($scope.employees, {id: employee.id});

                if (result.length == 0) {
                    $scope.employees.push(employee);
                }

                $scope.entity.employee = employee;
            }, function() {

            });
        };
});
