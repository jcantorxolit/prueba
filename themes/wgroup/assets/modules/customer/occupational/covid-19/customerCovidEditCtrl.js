'use strict';
/**
 * controller for Customers
 */
app.controller('customerCovidEditCtrl',
    function ($scope, $stateParams, $log, toaster, $state, $rootScope, $timeout, $http, SweetAlert, $document, $filter, $aside,
        ListService, bsLoadingOverlayService, $q, DTColumnBuilder, DTOptionsBuilder, $compile, CustomerCovidService, moment) {

        var $formInstance = null;

        $scope.contractTypes = $rootScope.parameters("employee_contract_type");
        $scope.documentTypeList = $rootScope.parameters("employee_document_type");
        $scope.externalTypeList = $rootScope.parameters("customer_covid_external_type");
        $scope.extrainfo = $scope.customer.extraContactInformationList;
        $scope.employees = [];
        $scope.isContractor = false;
        $scope.disableTelephone = false;
        $scope.disableMobile = false;
        $scope.disableAddress = false;
        $scope.invalideDate = false;
        $scope.updateEmployeeBirthdate = false;

        $scope.isView = $scope.$parent.editMode == "view";
        $scope.isCreate = true;
        CustomerCovidService.setId($scope.$parent.currentId || 0);

        $scope.maxDate = new Date();
        $scope.datePickerConfig = {
            culture: "es-CO",
            format: "dd/MM/yyyy",
            change: function () {
                if ($scope.entity.isExternal || $scope.updateEmployeeBirthdate) {
                    var age = moment().diff($scope.entity.birthDate, 'years');
                    $scope.entity.age = age;
                    if ($scope.updateEmployeeBirthdate) {
                        $scope.invalideDate = false;
                    }
                }
            }
        };

        function getList() {

            var entities = [
                {name: 'customer_workplace', value: $stateParams.customerId},
                {
                    name: 'customer_contractor_simple',
                    criteria : {
                        parentId: $stateParams.customerId
                    }
                },
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.workplaceList = response.data.data.workplaceList;
                    $scope.contractorList = response.data.data.customerContractorList;
                    onInit();
                    $scope.onLoadRecord();
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        var onInit = function () {
            $scope.entity = {
                id: $scope.$parent.currentId || 0,
                customerId: $stateParams.customerId,
                employee: null,
                isExternal: false,
                customerEmployeeId: null,
                documentType: null,
                documentNumber: null,
                firstName: null,
                lastName: null,
                fullName: null,
                customerWorkplaceId: null,
                externalType: null,
                contractorId: null,
                contractor: null,
                age: null,
                birthDate: "",
                telephone: null,
                mobile: null,
                address: null,
                email: null,
                observation: null,
                contractorId: null,
                informationList: null,
                origin: "WEB"
            };

            $scope.entity.registrationDate = $scope.maxDate;

            if ($formInstance) {
                $formInstance.$setPristine(true);
            }
        };
        getList();


        $scope.onLoadRecord = function () {
            if ($scope.entity.id != 0) {

                var req = {
                    id: $scope.entity.id
                };
                $http({
                    method: 'GET',
                    url: 'api/customer-covid/get',
                    params: req
                })
                    .catch(function (e, code) {

                    })
                    .then(function (response) {
                        $timeout(function () {
                            $scope.entity = response.data.result;
                            initializeDatesAndFormats();
                            if ($scope.entity.employee != null) {
                                parseEmployeeInfo($scope.entity.employee)
                            } else {
                                valideContactInfo();
                            }
                            onSelectedExternalType();
                        });

                    }).finally(function () {
                        $timeout(function () {
                            $scope.loading = false;
                            $document.scrollTop(40, 2000);
                        }, 400);
                    });
            } else {
                $scope.loading = false;
            }
        }

        var valideContactInfo = function () {
            if ($scope.entity.telephone != null) {
                $scope.disableTelephone = true;
            }
            else {
                $scope.disableTelephone = false;
            }
            if ($scope.entity.mobile != null) {
                $scope.disableMobile = true;
            }
            else {
                $scope.disableMobile = false;
            }
            if ($scope.entity.address != null) {
                $scope.disableAddress = true;
            }
            else {
                $scope.disableAddress = false;
            }
        }

        $scope.onSelectedExternalType = function(item) {
            if (item.item == "Contratista") {
                $scope.isContractor = true;
            }
            else {
                $scope.isContractor = false;
                $scope.entity.contractorId = null;
            }
        }

        var onSelectedExternalType = function () {
            if (!$scope.entity.isExternal) {
                return;
            }
            if ($scope.entity.externalType.item == "Contratista") {
                $scope.isContractor = true;
            }
            else {
                $scope.isContractor = false;
                $scope.entity.contractorId = null;
            }
        }

        var initializeDatesAndFormats = function () {
            if ($scope.entity.registrationDate) {
                $scope.entity.registrationDate = new Date($scope.entity.registrationDate.date);
            }
            if ($scope.entity.birthDate) {
                $scope.entity.birthDate = new Date($scope.entity.birthDate.date);
            }
        }

        $scope.onChangeExternal = function() {
            $scope.entity.employee = null;
            $scope.entity.customerEmployeeId = null;

            $scope.entity.documentType = null;
            $scope.entity.documentNumber = null;
            $scope.entity.firstName = null;
            $scope.entity.lastName = null;

            $scope.entity.telephone = null;
            $scope.entity.mobile = null;
            $scope.entity.address = null;
            $scope.entity.email = null;

            $scope.entity.customerWorkplaceId = null;
            $scope.entity.externalType = null;
            $scope.entity.age = null;
            $scope.entity.birthDate = null;
            $scope.isContractor = false;

            $scope.disableTelephone = null;
            $scope.disableMobile = null;
            $scope.disableAddress = null;
            $scope.disableEmail = null;
        }

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


                    if ($scope.entity.birthDate == "" || $scope.entity.birthDate == null) {
                        $scope.invalideDate = true;
                    } else {
                        $scope.invalideDate = false;
                    }

                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");

                    return;

                } else {
                    if ($scope.entity.birthDate == "" || $scope.entity.birthDate == null) {
                        $scope.invalideDate = true;
                        angular.element('.ng-invalid[name=' + firstError + ']').focus();
                        SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");
                        return;
                    }
                    save();
                }

            },
            reset: function (form) {

            }
        };


        var save = function () {
            var req = {};
            $scope.entity.informationList = $scope.extrainfo;
            $scope.entity.updateEmployeeBirthdate = $scope.updateEmployeeBirthdate;
            var data = JSON.stringify($scope.entity);
            req.data = Base64.encode(data);

            return $http({
                method: 'POST',
                url: 'api/customer-covid/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                    $scope.entity = response.data.result;
                    initializeDatesAndFormats()
                    CustomerCovidService.setId($scope.entity.id);
                    valideContactInfo();
                    onSelectedExternalType();
                    $scope.updateEmployeeBirthdate = false;
                });
            }).catch(function (response) {
                SweetAlert.swal("Error de guardado", response.data.message , "error");
            }).finally(function () {

            });
        };

        var getEmployeeInfo = function(data, type) {
            if (data == null) {
                return null
            }

            var $value = null
            angular.forEach(data, function(info) {
                if (info.type && info.type.value == type) {
                    $value = info.value;
                }
            });

            return $value;
        }

        $scope.onSearchEmployee = function () {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/absenteeism/disability/customer_absenteeism_disability_employee_list_modal.htm",
                placement: 'right',
                windowTopClass: 'top-modal',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCovidEmployeeListCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (response) {
                parseEmployeeInfo(response);
            }, function() {

            });
        };

        var parseEmployeeInfo = function (data) {

            var format = "";
            if (!data.entity.birthDate) {
                $scope.updateEmployeeBirthdate = true;
            } else {
                format = new Date(data.entity.birthDate.date);
            }

            var employee = {
                id: data.id,
                customerId: data.customerId,
                workPlace: data.workPlace,
                documentType: data.entity.documentType,
                contractType: data.contractType,
                job: data.job,
                documentNumber: data.entity.documentNumber,
                firstName: data.entity.firstName,
                lastName: data.entity.lastName,
                fullName: data.entity.fullName,
                birthDate: format,
                age: data.entity.age,
                gender: data.entity.gender,
                entity: data.entity
            };

            $scope.employees = [];
            $scope.employees.push(employee);

            $scope.entity.employee = employee;
            $scope.entity.customerWorkplaceId = employee.workPlace;
            $scope.entity.age = employee.age;
            $scope.entity.birthDate = employee.birthDate;
            $scope.entity.firstName = employee.firstName;
            $scope.entity.lastName = employee.lastName;
            $scope.entity.documentType = employee.documentType;
            $scope.entity.documentNumber = employee.documentNumber;
            $scope.entity.telephone = getEmployeeInfo(data.entity.details, 'tel');
            $scope.entity.mobile = getEmployeeInfo(data.entity.details, 'cel');
            $scope.entity.address = getEmployeeInfo(data.entity.details, 'dir');
            $scope.entity.email = getEmployeeInfo(data.entity.details, 'email');

            valideContactInfo();

        }

        $scope.onCancel = function () {
            $document.scrollTop(40, 2000);
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list");
            }
        }

    }
);

app.controller('ModalInstanceSideCovidEmployeeListCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.employee = {
        id: 0,
    };

    $scope.onCloseModal = function () {
        $uibModalInstance.close($scope.employee);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onLoadRecord = function () {
        if ($scope.employee.id != 0) {
            var req = {
                id: $scope.employee.id,
            };
            $http({
                method: 'GET',
                url: 'api/customer-employee',
                params: req
            })
                .catch(function (e, code) {
                    if (code == 403) {
                        var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.clientes.list';
                        // forbbiden
                        // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                        SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                        $timeout(function () {
                            $state.go(messagered);
                        }, 3000);
                    } else if (code == 404) {
                        SweetAlert.swal("Información no disponible", "Diagnóstico no encontrado", "error");
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del proceso", "error");
                    }
                })
                .then(function (response) {
                    console.log(response);

                    $timeout(function () {
                        $scope.employee = response.data.result;
                    });

                }).finally(function () {
                    $timeout(function () {
                        $scope.onCloseModal();
                    }, 400);
                });


        } else {
            $scope.loading = false;
        }
    }

    $scope.dtInstanceDisabilityEmployeeList = {};
    $scope.dtOptionsDisabilityEmployeeList = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function(d) {
                d.customerId = $stateParams.customerId;
                return JSON.stringify(d);
            },
            url: 'api/customer-employee-modal-basic',
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

    $scope.dtColumnsDisabilityEmployeeList = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {

                var actions = "";
                var disabled = ""

                var editTemplate = '<a class="btn btn-success btn-xs editRow lnk" href="#" uib-tooltip="Adicionar empleado" tooltip-placement="right"  data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-plus-square"></i></a> ';

                actions += editTemplate;

                return actions;
            }),

        DTColumnBuilder.newColumn('documentNumber').withTitle("Número Identificación").withOption('width', 200),
        DTColumnBuilder.newColumn('firstName').withTitle("Nombre").withOption('width', 200),
        DTColumnBuilder.newColumn('lastName').withTitle("Apellidos").withOption('width', 200),
        DTColumnBuilder.newColumn('workPlace').withTitle("Centro de Trabajo").withOption('width', 200),
        DTColumnBuilder.newColumn('job').withTitle("Cargo").withOption('width', 200),
        DTColumnBuilder.newColumn('neighborhood').withTitle("Centro de Costos").withOption('width', 200),
        DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                var label = 'label label-danger';
                var text = 'Inactivo';

                if (data.isActiveCode != null || data.isActiveCode != undefined) {
                    if (data.isActiveCode == 'Activo') {
                        label = 'label label-success';
                        text = 'Activo';
                    } else {
                        label = 'label label-danger';
                        text = 'Inactivo';
                    }
                }

                var status = '<span class="' + label + '">' + text + '</span>';

                return status;
            }),
        DTColumnBuilder.newColumn(null).withTitle("Autorización").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                var label = 'label label-danger';
                var text = 'Inactivo';

                if (data.isAuthorized != null || data.isAuthorized != undefined) {
                    if (data.isAuthorized == 'Autorizado') {
                        label = 'label label-success';
                        text = 'Autorizado';
                    } else if (data.isAuthorized == 'No Autorizado') {
                        label = 'label label-danger';
                        text = 'No Autorizado';
                    } else {
                        label = 'label label-info';
                        text = 'N/A';
                    }
                }

                var status = '<span class="' + label + '">' + text + '</span>';

                return status;
            })
    ];

    var loadRow = function () {
        angular.element("#dtDisabilityEmployeeList a.editRow").on("click", function () {
            var id = angular.element(this).data("id");
            $scope.editDisabilityEmployee(id);
        });
    };

    $scope.reloadData = function () {
        $scope.dtInstanceDisabilityEmployeeList.reloadData();
    };

    $scope.viewDisabilityEmployee = function (id) {
        $scope.employee.id = id;
        $scope.isView = true;
        $scope.onLoadRecord();
    };

    $scope.editDisabilityEmployee = function (id) {
        $scope.employee.id = id;
        $scope.isView = false;
        $scope.onLoadRecord();
    };

});
