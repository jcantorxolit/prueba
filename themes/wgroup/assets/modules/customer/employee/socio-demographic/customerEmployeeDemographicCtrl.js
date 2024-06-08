'use strict';
/**
 * controller for Customers
 */
app.controller('customerEmployeeDemographicCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder','$compile','toaster','$state','$rootScope', 'SweetAlert','$http', '$timeout', '$aside',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope,SweetAlert, $http, $timeout, $aside) {

        var log = $log;

        var request = {
            employee_id: $scope.$parent.currentEmployee
        }

        var requestHobbies = {
            employee_id: $scope.$parent.currentEmployee
        }

        var requestDiseases = {
            employee_id: $scope.$parent.currentEmployee
        }

        $scope.dateConfig = {
            culture: "es-CO",
            format: "dd/MM/yyyy"
        };

        $scope.isView = $scope.$parent.isCustomerContractor || $scope.$parent.editMode == "view";

        $scope.demographic = {
            id : $scope.$parent.currentEmployee,
            averageIncome: '',
            typeHousing: null,
            antiquityCompany: null,
            antiquityJob: null,
            hasPeopleInCharge : false,
            qtyPeopleInCharge : 0,
            hasChildren : false,
            isPracticeSports : false,
            frequencyPracticeSports : null,
            isDrinkAlcoholic : false,
            frequencyDrinkAlcoholic : null,
            isSmokes : false,
            frequencySmokes : null,
            isDiagnosedDisease : true,
            gender: null,
            stratum: null,
            civilStatus: null,
            scholarship: null,
            race: null,
            workingHoursPerDay: null,
            workArea: null,
            age: null,
            country: null,
            state: null,
            city: null,
            illnesses: []
        };

        var initialize = function() {
            $scope.hobby = {
                name: '',
            };

            $scope.disease = {
                name: '',
            };

            $scope.child = {
                id: 0,
                employeeId : $scope.$parent.currentEmployee,
                name: '',
                lastName: '',
                age: 0,
            };

            $scope.info = {
                id: 0,
                employeeId : $scope.$parent.currentEmployee,
                category: '',
                item: '',
                value: '',
            };
        }

        initialize();

        getList();

        function getList() {

            $scope.typeHousings = $rootScope.parameters("type_housing");
            $scope.antiquities = $rootScope.parameters("antiquity");
            $scope.frequencies = $rootScope.parameters("frequency");

            $scope.genders = $rootScope.parameters("gender");
            $scope.stratumList = $rootScope.parameters("stratum");
            $scope.civilStatusList = $rootScope.parameters("civil_status");
            $scope.scholarshipList = $rootScope.parameters("scholarship");
            $scope.raceList = $rootScope.parameters("race");
            $scope.workAreaList = $rootScope.parameters("work_area");
        }


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
                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                    return;

                } else {
                    //your code for submit
                    onSave();
                }

            },
            reset: function (form) {
                //$scope.employee = angular.copy($scope.master);
                form.$setPristine(true);

            }
        };


        var onSave = function () {
            var req = {};
            var data = JSON.stringify($scope.demographic);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer-employee/save-demographic',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
            }).catch(function (e) {
                $log.error(e);
                toaster.pop("error", "Error", "Error guardando el registro. Por favor verifique los datos ingresados!");
            }).finally(function () {
                $scope.onClear();
            });

        };

        var onLoadRecord = function(id) {

            if (id) {

                var req = {
                    id: id
                };
                $http({
                    method: 'GET',
                    url: 'api/customer-employee',
                    params: req
                })
                    .catch(function (e, code) {
                        if (code == 403) {
                            // forbbiden
                            // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                            toaster.pop("error", "No Autorizado", "No esta autorizado para ver esta informaci�n.");

                            $timeout(function () {
                                $scope.onCancel();
                            }, 3000);
                        } else if (code == 404) {
                            toaster.pop("error", "Informaci�n no disponible", "Registro no encontrado.");

                            $timeout(function () {
                                $scope.onCancel();
                            });
                        } else {
                            toaster.pop("error", "Error", "Se ha presentado un error al intentar acceder a la informaci�n.");
                        }
                    })
                    .then(function (response) {

                        $timeout(function () {
                            $scope.demographic = response.data.result.entity;
                            $scope.demographic.occupation = response.data.result.occupation;
                        });

                    }).finally(function () {
                        $scope.onClear();

                    });
            } else {

            }
        }

        $scope.onClear = function(){
            initialize();
        };

        onLoadRecord($scope.$parent.currentEmployee);

        $scope.onCancel = function () {
            $state.go("app.employee");
        }


        $scope.onSaveChild = function () {

            if ($scope.child.name == '' || $scope.child.lastName == '') {
                SweetAlert.swal("El formulario contiene errores!", "Por favor ingrese el nombre y/o apellido de la persona a cargo.", "error");
                return;
            }

            var req = {};
            var data = JSON.stringify($scope.child);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/employee-children/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
            }).catch(function (e) {
                $log.error(e);
                toaster.pop("error", "Error", "Error guardando el registro. Por favor verifique los datos ingresados!");
            }).finally(function () {
                $scope.onClear();
                $scope.reloadDataPeopleInCharge();
            });
        };

        $scope.onSaveHobby = function () {
            var req = {};

            if ($scope.hobby.name == '') {
                SweetAlert.swal("El formulario contiene errores!", "Por favor ingrese la actividad.", "error");
                return;
            }

            $scope.info.category = 'hobby';
            $scope.info.value = $scope.hobby.name;

            var data = JSON.stringify($scope.info);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/employee-demographic/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
            }).catch(function (e) {
                $log.error(e);
                toaster.pop("error", "Error", "Error guardando el registro. Por favor verifique los datos ingresados!");
            }).finally(function () {
                $scope.onClear();
                $scope.reloadDataHobbies();
            });
        };

        $scope.onSaveDisease = function () {
            var req = {};

            if ($scope.disease.name == '') {
                SweetAlert.swal("El formulario contiene errores!", "Por favor ingrese la enfermedad.", "error");
                return;
            }

            $scope.info.category = 'disease';
            $scope.info.value = $scope.disease.name;

            var data = JSON.stringify($scope.info);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/employee-demographic/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
            }).catch(function (e) {
                $log.error(e);
                toaster.pop("error", "Error", "Error guardando el registro. Por favor verifique los datos ingresados!");
            }).finally(function () {
                $scope.onClear();
                $scope.reloadDataDiseases();
            });
        };



        //------------------------------------------------------------- PEOPLE IN CHARGE

        $scope.dtInstanceEmployeePeopleInCharge = {};
		$scope.dtOptionsEmployeePeopleInCharge = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: request,
                url: 'api/employee-children',
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
                loadRowPeopleInCharge();
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

        $scope.dtColumnsEmployeePeopleInCharge = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";

                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar registro" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if (!$scope.isView) {
                        actions += deleteTemplate;
                    }

                    return actions;
                }),
            DTColumnBuilder.newColumn('name').withTitle("Nombre"),
            DTColumnBuilder.newColumn('lastName').withTitle("Apellidos"),
            DTColumnBuilder.newColumn('age').withTitle("Edad").withOption('width', 200)
        ];

        var loadRowPeopleInCharge = function () {
            $("#dtEmployeePeopleInCharge a.delRow").on("click", function () {
                var id = $(this).data("id");
                $scope.onDeleteChildren(id);
            });
        };

        $scope.dtInstanceEmployeePeopleInChargeCallback = function (instance) {
            $scope.dtInstanceEmployeePeopleInCharge = instance;
        };

        $scope.reloadDataPeopleInCharge = function () {
            $scope.dtInstanceEmployeePeopleInCharge.reloadData();
        };


        //------------------------------------------------------------- HOBBIES
        requestHobbies.category = 'hobby';

        $scope.dtInstanceEmployeeHobbies = {};
		$scope.dtOptionsEmployeeHobbies = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: requestHobbies,
                url: 'api/employee-demographic',
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
                loadRowHobby();
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

        $scope.dtColumnsEmployeeHobbies = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";

                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar registro" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if (!$scope.isView) {
                        actions += deleteTemplate;
                    }

                    return actions;
                }),
            DTColumnBuilder.newColumn('value').withTitle("Actividad"),
        ];

        var loadRowHobby = function () {
            $("#dtEmployeeHobbies a.delRow").on("click", function () {
                var id = $(this).data("id");
                $scope.onDeleteDemographic(id);
            });
        };

        $scope.dtInstanceEmployeeHobbiesCallback = function (instance) {
            $scope.dtInstanceEmployeeHobbies = instance;
        };

        $scope.reloadDataHobbies = function () {
            $scope.dtInstanceEmployeeHobbies.reloadData();
        };

        //------------------------------------------------------------- DISEASES
        requestDiseases.category = 'disease';

        $scope.dtInstanceEmployeeDiseases = {};
		$scope.dtOptionsEmployeeDiseases = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: requestDiseases,
                url: 'api/employee-demographic',
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
                loadRowDisease();
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

        $scope.dtColumnsEmployeeDiseases = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = "";

                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#" uib-tooltip="Eliminar registro" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if (!$scope.isView) {
                        actions += deleteTemplate;
                    }

                    return actions;
                }),
            DTColumnBuilder.newColumn('value').withTitle("Actividad"),
        ];

        var loadRowDisease = function () {
            $("#dtEmployeeDiseases a.delRow").on("click", function () {
                var id = $(this).data("id");
                $scope.onDeleteDemographic(id);
            });
        };

        $scope.dtInstanceEmployeeDiseasesCallback = function (instance) {
            $scope.dtInstanceEmployeeDiseases = instance;
        };

        $scope.reloadDataDiseases = function () {
            $scope.dtInstanceEmployeeDiseases.reloadData();
        };


        $scope.onDeleteChildren = function (id) {
            SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Eliminará el registro seleccionado.",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Si, eliminar!",
                    cancelButtonText: "No, cancelar!",
                    closeOnConfirm: true,
                    closeOnCancel: true
                },
                function (isConfirm) {
                    if (isConfirm) {
                        var req = {};
                        req.id = id;
                        $http({
                            method: 'POST',
                            url: 'api/employee-children/delete',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            data: $.param(req)
                        }).then(function (response) {
							swal("Eliminado", 'Registro eliminado satisfactoriamente');
                            $scope.reloadDataPeopleInCharge();
                        }).catch(function(e){
                            $log.error(e);
                            toaster.pop("error", "Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema");
                        }).finally(function(){

                        });

                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        }

        $scope.onDeleteDemographic = function (id) {
            SweetAlert.swal({
                    title: "Está seguro?",
                    text: "Eliminará el registro seleccionado.",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Si, eliminar!",
                    cancelButtonText: "No, cancelar!",
                    closeOnConfirm: true,
                    closeOnCancel: true
                },
                function (isConfirm) {
                    if (isConfirm) {
                        var req = {};
                        req.id = id;
                        $http({
                            method: 'POST',
                            url: 'api/employee-demographic/delete',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            data: $.param(req)
                        }).then(function (response) {
							swal("Eliminado", 'Registro eliminado satisfactoriamente');
                            $scope.reloadDataHobbies();
                            $scope.reloadDataDiseases();
                        }).catch(function(e){
                            $log.error(e);
                            toaster.pop("error", "Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema");
                        }).finally(function(){

                        });

                    } else {
                        swal("Cancelación", "La operación ha sido cancelada", "error");
                    }
                });
        };

        $scope.$watch("demographic.hasPeopleInCharge", function () {
            if (!$scope.demographic.hasPeopleInCharge) {
                $scope.demographic.qtyPeopleInCharge = 0;
            }
        });

    }]);
