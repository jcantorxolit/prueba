'use strict';
/**
 * controller for Customers
 */
app.controller('customerCovidListCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$document', 
    '$filter', '$aside', 'ListService', 'ngNotify',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, $filter, $aside, ListService, ngNotify) {

        var log = $log;
        var $exportUrl = '';

        $scope.audit = {
            fields: [],
            filters: [],
        };

        getList();

        function getList() {
            var entities = [
                {name: 'criteria_operators', value: null},
                {name: 'criteria_conditions', value: null},
                {name: 'customer_covid_filter_field', value: null},
                { name: 'export_url', value: null }
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $exportUrl = response.data.data.exportUrl.item;
                    $scope.criteria = response.data.data.criteriaOperatorList;
                    $scope.conditions = response.data.data.criteriaConditionList;
                    $scope.audit.fields = response.data.data.customerCovidFilterField;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }


        $scope.addFilter = function () {
            if ($scope.audit.filters == null) {
                $scope.audit.filters = [];
            }
            $scope.audit.filters.push(
                {
                    id: 0,
                    field: null,
                    criteria: $scope.criteria.length > 0 ? $scope.criteria[1] : null,
                    condition: $scope.conditions.length > 0 ? $scope.conditions[0] : null,
                    value: ""
                }
            );
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

        $scope.dtInstanceCustomerCovid = {};
        $scope.dtOptionsCustomerCovid = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.customerId = $stateParams.customerId;
                    if ($rootScope.isCustomerUser()) {
                        d.createdBy = $rootScope.currentUser().id;
                    }
                    
                    if (angular.isDefined($scope.audit) && angular.isDefined($scope.audit.filters)) {
                        d.filter =
                        {
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
                url: 'api/customer-covid',
                contentType: "application/json",
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function () {
                }
            })
            .withDataProp('data')
            .withOption('order', [[1, 'desc']])
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

        $scope.dtColumnsCustomerCovid = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 220).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";
                    var disabled = ""

                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-edit"></i></a> ';

                    var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#"  uib-tooltip="Ver" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-eye"></i></a> ';

                    /* var deleteTemplate = '<a class="btn btn-danger btn-xs delRow lnk" href="#"  uib-tooltip="Eliminar" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-trash-o"></i></a> '; */

                   /*  var temperatureTemplate = '<a class="btn btn-warning btn-xs temperatureRow lnk" href="#"  uib-tooltip="Adicionar temperatura" data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-medkit"></i></a> '; */

                    var exportTemplate = '<a class="btn btn-success btn-xs exportExcel lnk" href="#"  uib-tooltip="Exportar a Excel" data-id="' + data.id + '"  ' + disabled + ' >' +
                    '   <i class="fa fa-file-excel-o"></i></a> ';

                    if ($rootScope.can("clientes_view")) {
                        actions += viewTemplate;
                    }

                    if ($rootScope.can("clientes_edit")) {
                        actions += editTemplate;
                    }

                    /* if ($rootScope.can("clientes_edit")) {
                        actions += deleteTemplate;
                    }


                    if ($rootScope.can("clientes_edit")) {
                        actions += temperatureTemplate;
                    } */

                    actions += exportTemplate;
            
                    return actions;
                }),


            DTColumnBuilder.newColumn('personType').withTitle("Tipo Personal").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('documentType').withTitle("Tipo Identificación").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('documentNumber').withTitle("Identificación").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('fullName').withTitle("Nombre").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('workplace').withTitle("Centro de Trabajo").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('contractor').withTitle("Empresa Contratista").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('lastDate').withTitle("Fecha Último Registro").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn(null).withTitle("Nivel de Riesgo").withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label';
                    if(data.lastRiskLevelText != null) {
                        return '<span class="' + label + '" style="background-color:' + data.lastRiskLevelColor + '">' + data.lastRiskLevelText + '</span>';
                    }
                    return "";
                }),            
        ];

        var loadRow = function () {
            angular.element("#dtCustomerCovid a.editRow").on("click", function () {                
                var id = angular.element(this).data("id");                
                onEdit(id);
            });

            angular.element("#dtCustomerCovid a.viewRow").on("click", function () {                
                var id = angular.element(this).data("id");                
                onView(id);
            });

            angular.element("#dtCustomerCovid a.exportExcel").on("click", function () {
                var id = angular.element(this).data("id");
                onExportExcel(id);
            });

            angular.element("#dtCustomerCovid a.delRow").on("click", function () {
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
                                url: 'api/customer-covid/delete',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                data: $.param(req)
                            }).then(function (response) {
								swal("Eliminado", "Registro eliminado satisfactoriamente", "info");
                            }).catch(function (e) {
                                $log.error(e);
                                SweetAlert.swal("Error en la eliminación", "Se ha presentado un error durante la eliminación del registro. Por favor inténtelo de nuevo, si el error persiste, comuníquese con el administrador del sistema", "error");
                            }).finally(function () {

                                $scope.reloadData();
                            });

                        } else {
                            swal("Cancelación", "La operación ha sido cancelada", "error");
                        }
                    });
            });
        }
       
        $scope.dtInstanceCustomerCovidCallback = function(instance) {
            $scope.dtInstanceCustomerCovid = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceCustomerCovid.reloadData();
        };

        $scope.onCreate = function () {            
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("edit", "edit", 0);
            }
        };


        var onExportExcel = function(id)
        {
            var param = {
                customerCovidId: id,
                customerId: $stateParams.customerId,
            };

            angular.element("#downloadDocument")[0].src = "api/customer-covid/export?data=" + Base64.encode(JSON.stringify(param));
        }

        $scope.onExportExcelEmployee = function()
        {
            exportExcelReport('api/v1/customer-covid-export-employee');
        }

        $scope.onExportExcelExternal = function()
        {
            exportExcelReport('api/v1/customer-covid-export-external');
        }

        var exportExcelReport = function($endpoint) {
            ngNotify.set('<div class="row"><div class="col-sm-5"><div class="loader-spinner pull-right"></div> </div> <div class="col-sm-6 text-left">El reporte se está generando. Por favor espere!</div> </div>', {
                position: 'bottom',
                sticky: true,
                button: false,
                html: true
            });

            var param = {
                customerId: $stateParams.customerId,
                userId: $rootScope.currentUser().id,
                filter : {}
            };
            if (angular.isDefined($scope.audit) && angular.isDefined($scope.audit.filters)) {
                param.filter =
                {
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
            
            var request = { data : Base64.encode(JSON.stringify(param)) };
            
            return $http({
                method: 'POST',
                url: $exportUrl + $endpoint,
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                data: $.param(request)
            }).then(function (response) {

                var $url = $exportUrl + 'storage/' + response.data.filename;
                var $link = '<a class="btn btn-wide btn-default" href="' + $url + '" target="_self"><i class="glyphicon glyphicon-download"></i> Descargar el reporte</a>';

                if (response.data.isQueue) {
                    //$url = $state.href(app.user.messages, {}, {absolute: true});
                    $url = 'app/user/messages';
                    $link = response.data.message + ' <a  class="btn btn-wide btn-default" href="' + $url + '" translate="Ver mensajes"> Ver mensajes </a>';
                }

                ngNotify.set($link, {
                    position: 'bottom',
                    sticky: true,
                    type: response.data.isQueue ? 'info' : 'success',
                    button: true,
                    html: true
                });

            }).catch(function (response) {

                if (response.data != null && response.data.message !== undefined) {
                    ngNotify.set(response.data.message, {
                        position: 'bottom',
                        sticky: true,
                        type: 'error',
                        button: true,
                        html: true
                    });
                } else {
                    ngNotify.set("Lo sentimos, ha ocurrido un error en la generación del reporte", {
                        position: 'bottom',
                        sticky: true,
                        type: 'error',
                        button: true,
                        html: true
                    });
                }

            }).finally(function () {

            });  
        }


        var onEdit = function (id) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("edit", "edit", id);
            }
        };

        var onView = function (id) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("edit", "view", id);
            }
        };     

        // //--------------------------------------------------ADD TEMPARATURE TO EMPLOYEE
        // var onAddTemperature = function (entity) {
        //     var modalInstance = $aside.open({
        //         templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/covid-19/temperature/customer_covid_temperature modal.htm",
        //         placement: 'right',
        //         backdrop: 'static',
        //         size: 'lg',
        //         backdrop: true,
        //         controller: 'ModalInstanceSideCustomerCovidTemperatureListCtrl',
        //         scope: $scope,
        //         resolve: {
        //             entity: function () {
        //                 return entity;
        //             },
        //             isView: function () {
        //                 return $scope.isView;
        //             }
        //         }
        //     });
        //     modalInstance.result.then(function () {
                
        //     }, function() {
                
        //     });
        // };


        $scope.onUpload = function () {

            var modalInstance = $aside.open({
                //templateUrl: 'app_modal_customer_employee_import.htm',
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/occupational/covid-19/customer_covid_import_modal.htm",
                placement: 'bottom',
                size: 'lg',
                backdrop: true,
                controller: 'ModalInstanceSideCustomerCovidImportCtrl',
                scope: $scope,
            });
            modalInstance.result.then(function (response) {                
                $scope.reloadData();
            }, function() {

            });

        };
    }
]);

app.controller('ModalInstanceSideCustomerCovidImportCtrl', function ($rootScope, $stateParams, $scope, FileUploader, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, ngNotify, ListService) {

    var $exportUrl = '';
    var $lastResponse = null;

    var uploader = $scope.uploader = new FileUploader({
        url: $exportUrl + 'api/v1/customer-covid-import',
        formData: []
    });

    getList();

    function getList() {

        var entities = [
            { name: 'export_url', value: null },
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $exportUrl = response.data.data.exportUrl.item;
                uploader.url = $exportUrl + 'api/v1/customer-covid-import';
                $scope.uploader.url = $exportUrl + 'api/v1/customer-covid-import';
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    $scope.download = function () {
        angular.element("#downloadDocument")[0].src = "api/customer-covid/download-template?customerId=" + $stateParams.customerId;
    }

    uploader.filters.push({
        name: 'customFilter',
        fn: function (item/*{File|FileLikeObject}*/, options) {
            return this.queue.length < 10;
        }
    });

    uploader.onWhenAddingFileFailed = function (item/*{File|FileLikeObject}*/, filter, options) {
        console.info('onWhenAddingFileFailed', item, filter, options);
    };
    uploader.onAfterAddingFile = function (fileItem) {
        console.info('onAfterAddingFile', fileItem);
    };
    uploader.onAfterAddingAll = function (addedFileItems) {
        console.info('onAfterAddingAll', addedFileItems);
    };
    uploader.onBeforeUploadItem = function (item) {
        console.info('onBeforeUploadItem', item);
        var formData = { id: $stateParams.customerId, user: $rootScope.currentUser().id };
        item.formData.push(formData);
    };
    uploader.onProgressItem = function (fileItem, progress) {
        console.info('onProgressItem', fileItem, progress);
    };
    uploader.onProgressAll = function (progress) {
        console.info('onProgressAll', progress);
    };
    uploader.onSuccessItem = function (fileItem, response, status, headers) {
        console.info('onSuccessItem', fileItem, response, status, headers);
    };
    uploader.onErrorItem = function (fileItem, response, status, headers) {
        console.info('onErrorItem', fileItem, response, status, headers);
    };
    uploader.onCancelItem = function (fileItem, response, status, headers) {
        console.info('onCancelItem', fileItem, response, status, headers);
    };
    uploader.onCompleteItem = function (fileItem, response, status, headers) {
        console.info('onCompleteItem', fileItem, response, status, headers);
        $lastResponse = response;
    };
    uploader.onCompleteAll = function () {
        console.info('onCompleteAll');
        $uibModalInstance.close($lastResponse);
    };

});