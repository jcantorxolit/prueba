'use strict';
/**
 * controller for Customers
 */
app.controller('customerVrEmployeeRegisterEditCtrl',
    function ($scope, $stateParams, $log, toaster, $state, $rootScope, $timeout, $http, SweetAlert, $document, $filter, $aside,
        ListService, bsLoadingOverlayService, $q, DTColumnBuilder, DTOptionsBuilder, $compile, customerVrEmployeeService, moment) {

        var $formInstance = null;
        $scope.isView = $scope.$parent.editMode == "view";
        $scope.flowConfig = { target: '/api/customer-employee/upload', singleFile: true };
        customerVrEmployeeService.setId($scope.$parent.currentId || 0);

        $scope.dynamicPopover = {
            templateUrl: 'myPopoverTemplate.html'
        };

        var onInit = function () {
            $scope.entity = {
                id: $scope.$parent.currentId || 0,
                customerId: $stateParams.customerId,
                isActive: 1,
                hasConfig: false,
                employee: {
                    id: 0,
                    customerId: null,
                    documentType: null,
                    documentNumber: "",
                    firstName: "",
                    lastName: "",
                    gender: null,
                    logo: "",
                    entity: {id: null}
                },
            };

            if ($formInstance) {
                $formInstance.$setPristine(true);
            }
        };
        onInit();


        $scope.uploader = new Flow();
        if ($scope.entity.employee.logo == '') {
            $scope.noImage = true;
        }

        $scope.onLoadRecord = function () {
            if ($scope.entity.id != 0) {
                var req = {
                    id: $scope.entity.id
                };
                $http({
                    method: 'GET',
                    url: 'api/customer-vr-employee/get',
                    params: req
                })
                    .catch(function (e, code) {
                    })
                    .then(function (response) {
                        $timeout(function () {
                            $scope.entity = response.data.result;
                            customerVrEmployeeService.setEntity($scope.entity);
                            parseEmployeeInfo({ id: $scope.entity.employee.id, entity : $scope.entity.employee});
                        });
                    }).finally(function () {
                        $timeout(function () {
                            $scope.loading = false;
                        });
                    });
            } else {
                $scope.loading = false;
            }
        }

        $scope.onLoadRecord();

        $scope.$on('reloadConfigVr', function (event, data) {
            $scope.entity.hasConfig = true;
            $scope.experienceList = data;
            $scope.saveForm = true;
        });

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
                url: 'api/customer-vr-employee/save',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");

                    $scope.uploader.flow.opts.query.id = response.data.result.employee.entity.id;
                    $scope.uploader.flow.resume();

                    $scope.entity = response.data.result;
                    customerVrEmployeeService.setId($scope.entity.id);
                    customerVrEmployeeService.setEntity($scope.entity);

                    if ($scope.entity.employee.logo != null && $scope.entity.employee.logo.path != null) {
                        $scope.noImage = false;
                    } else {
                        $scope.noImage = true;
                    }

                });
            }).catch(function (response) {
                SweetAlert.swal("Error de guardado", response.data.message , "error");
            }).finally(function () {
                $timeout(function () {
                    if($scope.uploader.flow.files.length) {
                        var $logo = getBase64($scope.uploader.flow.files[0].file);
                        getBase64($logo);
                    }
                },1000);
            });
        };

        var getBase64 = function(file) {
            var reader  = new FileReader();
            reader.onloadend = function () {
                $scope.entity.employee.logo = {path: reader.result};
                $scope.noImage = false;
            }

            if (file) {
                reader.readAsDataURL(file);
            }
         }


        $scope.onSearchEmployee = function () {
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/common/modals/employee_list_modal.htm",
                placement: 'right',
                windowTopClass: 'top-modal',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideVrEmployeeListCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (response) {
                $scope.uploader.flow.cancel();
                parseEmployeeInfo(response);
            });
        };

        var parseEmployeeInfo = function (data) {

            var employee = {
                id: data.id,
                documentType: data.entity.documentType,
                documentNumber: data.entity.documentNumber,
                firstName: data.entity.firstName,
                lastName: data.entity.lastName,
                gender: data.entity.gender,
                logo: data.entity.logo,
                entity: {id: data.entity.entity ? data.entity.entity.id : data.entity.id}
            };

            $scope.entity.employee = employee;
            if ($scope.entity.employee.logo != null && $scope.entity.employee.logo.path != null) {
                $scope.noImage = false;
            } else {
                $scope.noImage = true;
            }

        }

        $scope.onCancel = function () {
            $document.scrollTop(40, 2000);
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list");
            }
        }

        $scope.removeImage = function () {
            $scope.noImage = true;
            $scope.entity.removeLogo = true;
            $scope.entity.employee.logo = null;
        };


        $scope.openCamera = function () {
          var modalInstance = $aside.open({
            templateUrl: 'ModalContentCamera.html',
            placement: 'right',
            windowTopClass: 'top-modal',
            size: 'md',
            backdrop: 'static',
            controller: 'ModalInstanceCameraCtrl'
          });

          modalInstance.result.then(function (base6aimage) {
              if(base6aimage) {
                fetch(base6aimage)
                    .then(function(res) {
                        return res.blob();
                    })
                    .then(function(blobParam) {
                        blobParam.name = "photo.png";
                        $scope.uploader.flow.addFile(blobParam)
                    });
              }
            });

        };

    }
);

app.controller('ModalInstanceCameraCtrl', function ($scope, $uibModalInstance) {

    $scope.image = null;
    var streamObj;
    var captureVideoButton = function() {
        var video = angular.element('#video');
        var canvas = angular.element('#canvas');
        navigator.mediaDevices.getUserMedia({video: true}).
            then(handleSuccess).catch(handleError);
    };

    $scope.take = function() {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        $scope.image = canvas.toDataURL('image/png');
        stopCamera();
    };

    $scope.takeNew = function() {
        $scope.image = null;
        captureVideoButton();
    };

    $scope.onDissmiss = function() {
        $uibModalInstance.close($scope.image);
        stopCamera();
    };

    function handleSuccess(stream) {
        video.srcObject = stream;
        streamObj = stream;
    }

    function handleError(error) {
        console.log('Error: ', error);
    }

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
        stopCamera();
    };

    var stopCamera = function() {
        streamObj.getTracks().forEach(function(track) {
            track.stop();
        });
    }

    captureVideoButton()

})

app.controller('ModalInstanceSideVrEmployeeListCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout,
     SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile, ListService, $aside) {

    $scope.canCreate = true;
    $scope.canFilter = true;
    $scope.employee = {
        id: 0,
    };

    $scope.onCloseModal = function () {
        $uibModalInstance.close($scope.employee);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.audit = {
        fields: [],
        filters: [],
    };

    $scope.audit.fields = [
        {"alias": "Tipo de Identificación", "name": "employeeDocumentType"},
        {"alias": "Número de Identificación", "name": "documentNumber"},
        {"alias": "Nombre", "name": "firstName"},
        {"alias": "Apellidos", "name": "lastName"}
    ];

    function getList() {

        var entities = [
            {name: 'criteria_operators', value: null},
            {name: 'criteria_conditions', value: null}
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.criteria = response.data.data.criteriaOperatorList;
                $scope.conditions = response.data.data.criteriaConditionList;
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    getList();


    $scope.addFilter = function () {
        if ($scope.audit.filters == null) {
            $scope.audit.filters = [];
        }

        $scope.audit.filters.push({
            id: 0,
            field: null,
            criteria: $scope.criteria.length > 0 ? $scope.criteria[1] : null,
            condition: $scope.conditions.length > 0 ? $scope.conditions[0] : null,
            value: ""
        });
    };

    $scope.onFilter = function () {
        $scope.reloadData();
    }

    $scope.removeFilter = function (index) {
        $scope.audit.filters.splice(index, 1);
    }

    $scope.onCleanFilter = function () {
        $scope.audit.filters = [];
        $scope.reloadData()
    }


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
                        SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                        $timeout(function () {
                            $state.go(messagered);
                        });
                    } else if (code == 404) {
                        SweetAlert.swal("Información no disponible", "Diagnóstico no encontrado", "error");
                    } else {
                        SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del proceso", "error");
                    }
                })
                .then(function (response) {
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

    $scope.dtInstanceModalEmployeeList = {};
    $scope.dtOptionsModalEmployeeList = DTOptionsBuilder.newOptions()
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            data: function(d) {
                d.customerId = $stateParams.customerId;
                if (angular.isDefined($scope.audit) && angular.isDefined($scope.audit.filters)) {
                    d.filter = {
                        filters: $scope.audit.filters.filter(function (filter) {
                            return filter != null && filter.field != null && filter.criteria != null;
                        }).map(function (filter, index, array) {
                            return {
                                field: filter.field.name,
                                operator: filter.criteria.value,
                                value: filter.value,
                                condition: filter.condition.value,
                            };
                        })
                    };
                }
                return JSON.stringify(d);
            },
            url: 'api/customer-employee-modal-basic-2',
            contentType: 'application/json',
            type: 'POST',
            beforeSend: function () {
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
        .withOption('language', {
        })

        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        });
    ;

    $scope.dtColumnsModalEmployeeList = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {

                var actions = "";
                var disabled = ""

                var editTemplate = '<a class="btn btn-success btn-xs editRow lnk" href="#" uib-tooltip="Adicionar empleado" tooltip-placement="right"  data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-plus-square"></i></a> ';

                actions += editTemplate;

                return actions;
            }),

        DTColumnBuilder.newColumn('employeeDocumentType').withTitle("Tipo Identificación").withOption('width', 200),
        DTColumnBuilder.newColumn('documentNumber').withTitle("Número Identificación").withOption('width', 200),
        DTColumnBuilder.newColumn('firstName').withTitle("Nombre").withOption('width', 200),
        DTColumnBuilder.newColumn('lastName').withTitle("Apellidos").withOption('width', 200),
    ];

    var loadRow = function () {
        angular.element("#dtModalEmployeeList a.editRow").on("click", function () {
            var id = angular.element(this).data("id");
            $scope.editModalEmployeeList(id);
        });
    };

    $scope.reloadData = function () {
        $scope.dtInstanceModalEmployeeList.reloadData();
    };

    $scope.viewModalEmployeeList = function (id) {
        $scope.employee.id = id;
        $scope.isView = true;
        $scope.onLoadRecord();
    };

    $scope.editModalEmployeeList = function (id) {
        $scope.employee.id = id;
        $scope.isView = false;
        $scope.onLoadRecord();
    };

    $scope.onCreate = function () {
        var modalInstance = $aside.open({
            templateUrl: $rootScope.app.views.urlRoot + "modules/customer/common/modals/employee_create_modal.htm",
            placement: 'right',
            windowTopClass: 'top-modal',
            size: 'lg',
            backdrop: true,
            controller: 'ModalInstanceSideEmployeeCreateCtrl',
            scope: $scope,
        });
        modalInstance.result.then(function (response) {
            $scope.reloadData();
        });
    };

});

app.controller('ModalInstanceSideEmployeeCreateCtrl',
    function ($scope, $stateParams, $log, toaster, $state, $rootScope, $timeout, $http, SweetAlert, $document, $uibModalInstance) {

        var $formInstance = null;
        $scope.genders = $rootScope.parameters("gender");
        $scope.documentTypes = $rootScope.parameters("employee_document_type");

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

            },
            reset: function (form) {
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
                SweetAlert.swal("Error de guardado", response.data.message , "error");
            });
        };


        $scope.onCancel = function () {
            $uibModalInstance.dismiss('cancel');
        }


    }
);
