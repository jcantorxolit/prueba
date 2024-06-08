'use strict';
/**
 * controller for Customers
 */
app.controller('customerConfigWorkPlaceListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert',
    '$document', '$location', '$translate', 'ListService', '$filter',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, $location, $translate, ListService, $filter) {


        var $formInstance = null;

        $scope.loading = true;
        $scope.isView = $state.is("app.clientes.view");

        var init = function() {
            $scope.entity = {
                id: 0,
                customerId: $stateParams.customerId,
                country: null,
                state: null,
                city: null,
                name: null,
                address: '',
                economicActivity: 0,
                employeeDirect: 0,
                employeeContractor: 0,
                employeeMision: 0,
                risk1: 0,
                risk2: 0,
                risk3: 0,
                risk4: 0,
                risk5: 0,
                status: true
            }

            if ($formInstance) {
                $formInstance.$setPristine(true);
            }
        }

        init()

        getList();

        function getList() {
            var entities = [
                {name: 'country', value: null},
                {name: 'state', value: $scope.entity.country ? $scope.entity.country.id : 68},
                {name: 'city', value: $scope.entity.state ? $scope.entity.state.id : 0},
                {name: 'wg_structure_type', value: null},
                {name: 'config_workplace_status', value: null},
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.countryList = response.data.data.countryList;
                    $scope.stateList = response.data.data.stateList;
                    $scope.cityList = response.data.data.cityList;
                    $scope.typeList = response.data.data.wg_structure_type;
                    $scope.statusList = response.data.data.config_workplace_status;

                    if ($scope.entity.country == null) {
                        var $country = $filter('filter')($scope.countryList, {code: 'CO'}, true);
                        $scope.entity.country = $country.length > 0 ? $country[0] : null;
                    }

                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        var onLoadRecord = function (id) {
            if (id) {
                var req = {
                    id: id
                };

                $http({
                    method: 'GET',
                    url: 'api/customer-config-workplace/get-gtc',
                    params: req
                })
                .catch(function (e, code) {

                })
                .then(function (response) {
                    $scope.entity = response.data.result;
                    $scope.onChangeEmployeeNumber();
                    getList();
                }).finally(function () {

                });
            }
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
                    angular.element('.ng-invalid[name=' + firstError + ']').focus();
                    SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e Intentalo de nuevo.", "error");
                    return;
                } else {
                    save();
                }
            },
            reset: function (form) {
                init();
            }
        };

        var save = function () {
            var req = {};
            var data = JSON.stringify($scope.entity);
            req.data = Base64.encode(data);
            return $http({
                method: 'POST',
                url: 'api/customer-config-workplace/save-gtc',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    //$scope.entity = response.data.result;
                    SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");
                    $scope.reloadData();
                });
            }).catch(function (e) {
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {
                $scope.onCancel();
            });
        };

        $scope.onChangeEmployeeNumber = function() {
            $scope.entity.total = parseInt($scope.entity.risk1)
                                    + parseInt($scope.entity.risk2)
                                    + parseInt($scope.entity.risk3)
                                    + parseInt($scope.entity.risk4)
                                    + parseInt($scope.entity.risk5);
        }

        $scope.onCancel = function() {
            init();
        }

        $scope.onSelectCountry = function () {
            $scope.entity.state = null;
            $scope.entity.city = null;
            getList();
        };

        $scope.onSelectState = function () {
            $scope.entity.city = null;
            getList();
        };

		$scope.dtOptionsConfigWorkPlace = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.customerId = $stateParams.customerId;
                    return JSON.stringify(d);
                },
                url: 'api/customer-config-workplace',
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
                //log.info("fnPreDrawCallback");
                //Pace.start();
                return true;
            })
            .withOption('fnDrawCallback', function () {
                //log.info("fnDrawCallback");
                loadRow();
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

        $scope.dtColumnsConfigWorkPlace = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";
                    var disabled = ""

                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-edit"></i></a> ';

                    var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#"  uib-tooltip="Ver" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-eye"></i></a> ';

                    var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#"  uib-tooltip="Eliminar" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-trash-o"></i></a> ';

                    if ($rootScope.can("clientes_view")) {
                        actions += viewTemplate;
                    }

                    if ($rootScope.can("clientes_edit")) {
                        actions += editTemplate;

                    }

                    if ($rootScope.can("clientes_delete")) {
                        actions += deleteTemplate;
                    }

                    return !$scope.isView ? actions : viewTemplate;
                }),

                DTColumnBuilder.newColumn('name').withTitle($translate.instant('grid.matrix.WORK-PLACE')).withOption('width', 200).withOption('defaultContent', ''),
                DTColumnBuilder.newColumn('country').withTitle("País").withOption('width', 200).withOption('defaultContent', ''),
                DTColumnBuilder.newColumn('state').withTitle("Departamento").withOption('width', 200).withOption('defaultContent', ''),
                DTColumnBuilder.newColumn('city').withTitle("Ciudad").withOption('width', 200).withOption('defaultContent', ''),
                DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = '';
                    switch  (data.status)
                    {
                        case "Activo":
                            label = 'label label-success';
                            break;

                        case "Inactivo":
                            label = 'label label-danger';
                            break;

                        case "En Proceso":
                            label = 'label label-inverse';
                            break;
                    }

                    return '<span class="' + label +'">' + data.status + '</span>';
                }),
        ];


        var loadRow = function () {

            angular.element("#dtConfigWorkPlace a.editRow").on("click", function () {
                var id = angular.element(this).data("id");
                onEdit(id);
            });

            angular.element("#dtConfigWorkPlace a.viewRow").on("click", function () {
                var id = angular.element(this).data("id");
                onView(id);
            });

            angular.element("#dtConfigWorkPlace a.delRow").on("click", function () {
                var id = angular.element(this).data("id");

                SweetAlert.swal({
                        title: "Está seguro?",
                        text: "Eliminará el registro seleccionado.",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "Si, eliminar!",
                        cancelButtonText: "No, continuar!",
                        closeOnConfirm: true,
                        closeOnCancel: true
                    },
                    function (isConfirm) {
                        if (isConfirm) {
                            var req = {};
                            req.id = id;
                            $http({
                                method: 'POST',
                                url: 'api/customer-config-workplace/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {
								swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {
                                $scope.reloadData();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });
        };

        $scope.dtInstanceConfigWorkPlaceCallback = function (instance) {
            $scope.dtInstanceConfigWorkPlace = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceConfigWorkPlace.reloadData();
        };

        var onView = function (id) {
            $scope.isView = true;
            onLoadRecord(id)
        };

        var onEdit = function (id) {
            $scope.isView = false;
            onLoadRecord(id)
        };


    }
]);
