'use strict';
/**
 * controller for Customers
 */
app.controller('customerDiagnosticExpressMatrixWorkplaceCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', 
    '$document', '$location', '$translate', '$aside', 'ExpressMatrixService', '$uibModal', 'ListService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, $location, $translate, $aside, ExpressMatrixService, $uibModal, ListService) {

    
        $scope.customerId = $stateParams.customerId;;
        
        $scope.isView = $scope.customer.matrixType != 'E';
        
        var onDestroyWizardNavigate$ = $rootScope.$on('wizardNavigate', function (event, args) {            
            if (args.newValue == 0) {                                
                if (!ExpressMatrixService.getIsBackInNavigation()) {                    
                    $scope.onRefresh();
                }

                ExpressMatrixService.setIsBackInNavigation(null);

                if (ExpressMatrixService.getWorkplaceId() != null) {
                    onOpenModal({id: ExpressMatrixService.getWorkplaceId()}, $scope.isView);
                }
            }
        });

        $scope.$on("$destroy", function() {
            onDestroyWizardNavigate$();            
        });

        getList();

        function getList() {
            var entities = [            
                {name: 'customer_express_matrix_workplace_list', value: null, criteria: { 
                    customerId:  $stateParams.customerId,  isFullyConfigured: 1
                }},
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.workplaceList = response.data.data.customerExpressMatrixWorkplaceList;  

                    if (ExpressMatrixService.getShouldCreateNewWorkplace()) {
                        ExpressMatrixService.setShouldCreateNewWorkplace(null);
                        $scope.onCreate();
                    }

                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

		$scope.dtOptionsExpressMatrixWorkplace = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.customerId = $stateParams.customerId;
                    return JSON.stringify(d);
                },
                url: 'api/customer-config-workplace-express',
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

        $scope.dtColumnsExpressMatrixWorkplace = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {

                    var actions = "";
                    var disabled = ""

                    var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar"  data-id="' + data.id + '"' + disabled + ' >' +
                        '   <i class="fa fa-edit"></i></a> ';

                    var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#"  uib-tooltip="Ver" data-id="' + data.id + '" >' +
                        '   <i class="fa fa-eye"></i></a> ';

                    var continueTemplate = '<a class="btn btn-success btn-xs continueRow lnk" href="#"  uib-tooltip="Continuar" data-id="' + data.id + '" data-process="' + data.hasProcess + '" data-address="' + data.address + '" >' +
                        '   <i class="fa fa-play"></i></a> ';                        

                    actions = viewTemplate + editTemplate;

                    if (data.status == 'En Proceso') {
                        actions += continueTemplate;
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

            angular.element("#dtExpressMatrixWorkplace a.editRow").on("click", function () {
                var id = angular.element(this).data("id");
                onOpenModal({id: id}, $scope.isView);
            });

            angular.element("#dtExpressMatrixWorkplace a.viewRow").on("click", function () {
                var id = angular.element(this).data("id");
                onOpenModal({id: id}, true);
            });

            angular.element("#dtExpressMatrixWorkplace a.continueRow").on("click", function () {
                var id = angular.element(this).data("id");
                var $process = angular.element(this).data("process");
                var $address = angular.element(this).data("address");
                if ($process == "Si" && $address != "") {
                    ExpressMatrixService.setWorkplaceId(id);
                    $rootScope.$emit('wizardGoTo', { newValue: 1 });
                } else {
                    onOpenModal({id: id}, $scope.isView);
                }
            });
        };        

        $scope.dtInstanceExpressMatrixWorkplaceCallback = function (instance) {
            $scope.dtInstanceExpressMatrixWorkplace = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceExpressMatrixWorkplace.reloadData();
        };

        $scope.onRefresh = function() {
            $scope.reloadData();
            getList();
        }

        $scope.onCreate = function() {
            if ($scope.workplaceList.length > 0) {
                SweetAlert.swal({
                    title: "¿Desea duplicar algún Centro de Trabajo? ",
                    text: "",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Duplicar",
                    cancelButtonText: "Cancelar",
                    closeOnConfirm: true,
                    closeOnCancel: true
                },
                function (isConfirm) {
                    if (isConfirm) {
                        onOpenDuplicateModal();
                    } else {
                        onOpenModal({id: 0}, $scope.isView);
                    }
                });
            } else {
                onOpenModal({id: 0}, $scope.isView);
            }
        }

        var onOpenModal = function(entity, isView)
        {
            var modalInstance = $aside.open({                
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/diagnostic/tab-express-matrix/work-place/customer_diagnostic_express_matrix_work_place_modal.htm",
                placement: 'right',
                size: 'lg',
                backdrop: 'static',
                controller: 'ModalInstanceSideCustomerDiagnosticExpressMatrixWorkplaceCtrl',
                scope: $scope,
                resolve: {
                    entity: function () {
                        return entity;
                    },
                    isView: function () {
                        return isView;
                    },                    
                    closeAfterCreate: function() {
                        return false;
                    }
                }
            });
            modalInstance.result.then(function () {
                $scope.onRefresh();
            }, function () {
                $scope.onRefresh();               
            });
        }

        var onOpenDuplicateModal = function() {
            var modalInstance = $uibModal.open({                
                templateUrl: $rootScope.app.views.urlRoot + "modules/customer/diagnostic/tab-express-matrix/work-place/customer_diagnostic_express_matrix_work_place_duplicate_modal.htm",
                controller: 'ModalInstanceSideCustomerDiagnosticExpressMatrixWorkplaceDuplicateCtrl',
                windowTopClass: 'top-modal',
                resolve: {    
                    isView: function () {
                        return $scope.isView;
                    },
                    workplaceList: function() {
                        return $scope.workplaceList;
                    }
                }
            });

            modalInstance.result.then(function (selectedItem) {                
                $scope.onRefresh();
            }, function () {                
                $scope.onRefresh();
            });
        }

    }]);
