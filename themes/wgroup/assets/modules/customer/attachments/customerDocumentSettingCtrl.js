'use strict';
/**
 * controller for Customers
 */
app.controller('customerDocumentSettingCtrl', ['$scope', '$stateParams', '$log', '$compile', 'toaster', '$state', 
    '$rootScope', '$timeout', '$http', 'SweetAlert', 'ListService', 'DTOptionsBuilder', 'DTColumnBuilder',
function ($scope, $stateParams, $log, $compile, toaster, $state,
          $rootScope, $timeout, $http, SweetAlert, ListService, DTOptionsBuilder, DTColumnBuilder) {
    
    $scope.documentTypes = [];

    var init = function() {
        $scope.setting = {
            id: 0,
            customerId: $stateParams.customerId,            
            documentType: null,
            isPublic: false,
            isProtected: false,
            users: []
        };
    };

    init();

    getList();

    function getList() {

        var entities = [
            {name: 'customer_document_type', value: $stateParams.customerId}            
        ];

        ListService.getDataList(entities)
            .then(function (response) {                
                $scope.documentTypeList =response.data.data.customerDocumentType;
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    var onLoadRecord = function () {
            
        var req = {
            type: $scope.setting.documentType ? $scope.setting.documentType.value : null,
            origin: $scope.setting.documentType ? $scope.setting.documentType.origin : null,
            customerId: $scope.setting.customerId
        };

        $http({
            method: 'POST',
            url: 'api/customer-document-security/get-relation',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
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
                    SweetAlert.swal("Información no disponible", "Anexo no encontrado", "error");
                    $timeout(function () {
                        $state.go('app.clientes.list');
                    });
                } else {
                    SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del anexo", "error");
                }
            })
            .then(function (response) {
                $timeout(function () {
                    var $documentType = $scope.setting.documentType;

                    $scope.setting = response.data.result;
                    if ($scope.setting.documentType == null) {
                        $scope.setting.documentType = $documentType
                    }
                });

            }).finally(function () {
                $timeout(function () {                        
                    $scope.loading = false;
                }, 400);
            });
    };    

    $scope.onSave = function () {

        var req = {};
        var data = JSON.stringify($scope.setting);
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/customer-document-security/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                toaster.pop('success', 'Operación Exitosa', 'Actualización exitosa.');
                
                var $documentType = angular.copy($scope.setting.documentType);

                $scope.setting = response.data.result;
                if ($scope.setting.documentType == null) {
                    $scope.setting.documentType = $documentType
                }                
            });

        }).catch(function (e) {
            $log.error(e);
            toaster.pop("error", "Error", "Error guardando el registro. Por favor verifique los datos ingresados!");
        }).finally(function () {

        });

    };

    var onSaveDocumentSecurityUser = function (id, userId, value) {

        var req = {};
        var data = JSON.stringify(
            {
                id: id,
                customerId: $stateParams.customerId,
                userId: userId,
                documentType: $scope.setting.documentType,
                isActive: (value == 0 || !value) ? 1 : 0
            }
        );
        req.data = Base64.encode(data);

        return $http({
            method: 'POST',
            url: 'api/customer-document-security-user/save',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $timeout(function () {
                toaster.pop('success', 'Operación Exitosa', 'Actualización exitosa.');                
                $scope.reloadData();             
            });

        }).catch(function (e) {
            $log.error(e);
            toaster.pop("error", "Error", "Error guardando el registro. Por favor verifique los datos ingresados!");
        }).finally(function () {

        });

    };    

    $scope.onSelectDocumentType = function (item, model) {
        $timeout(function () {
            if ($scope.setting.documentType != null) {
                onLoadRecord();
                $scope.reloadData();
            }
        });
    };

    $scope.onClear = function()
    {
        $timeout(function () {
            init();            
        });
    }

    $scope.onCancel = function (index) {
        if ($scope.$parent != null) {
            $scope.$parent.navToSection("list", "list");
        }
    };


    $scope.dtOptionsCustomerDocumentSecurityUser = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            data: function (d) {
                d.customerId = $scope.setting.customerId;
                d.documentType = $scope.setting.documentType ? $scope.setting.documentType.value : -1;
                d.origin = $scope.setting.documentType ? $scope.setting.documentType.origin : -1;

                return JSON.stringify(d);
            },
            url: 'api/customer-document-security-user',
            contentType: "application/json",
            type: 'POST',
            beforeSend: function () {
                // Aqui inicia el loader indicator
            },
            complete: function () {
            }
        })
        .withDataProp('data')
        .withOption('order', [[0, 'desc']])
        .withOption('serverSide', true)
        .withOption('processing', true)
        .withOption('fnPreDrawCallback', function () {
            return true;
        })
        .withOption('fnDrawCallback', function () {
            loadRow()
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
    

    $scope.dtColumnsCustomerDocumentSecurityUser = [
        DTColumnBuilder.newColumn('userName').withTitle("Nombre").withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('userType').withTitle("Tipo Usuario").withOption('defaultContent', ''),        
        DTColumnBuilder.newColumn('userEmail').withTitle("E-mail").withOption('defaultContent', ''),        
        DTColumnBuilder.newColumn(null).withTitle('Permitir').withOption('width', 200).notSortable()
            .renderWith(function(data, type, full, meta) {

                var actions = "";

                var id = data.id ? data.id : 0;
                var chkId = Math.random() * (9999 - 1) + 1;
                var checked = (data.isActive == "1") ? "checked" : ""                
                var disabled = $scope.isView ? " disabled='disabled'" : ""

                var editTemplate = '<div class="checkbox clip-check check-success ">' +
                    '<input class="selectedRowSecurity" type="checkbox" id="chk_' + chkId + '" data-id="' + id + '" data-value="' + data.isActive + '" data-user="' + data.userId + '" ' + checked + disabled + ' ><label for="chk_' + chkId +'"> Seleccionar </label></div>';

                if (!data.isDeleted) {
                    actions += editTemplate;
                }

                return actions;
            }),        
        DTColumnBuilder.newColumn(null).withTitle("Fecha Modificación").withOption('width', 200)
            .renderWith(function(data, type, full, meta) {
                return data.updatedAt ? moment(data.updatedAt.date).format('DD/MM/YYYY HH:mm') : null;
            }),
        DTColumnBuilder.newColumn('updatedBy').withTitle("Usuario").withOption('width', 200).withOption('defaultContent', ''),
    ];

    var loadRow = function () {

        angular.element("#dtCustomerDocumentSecurityUser input.selectedRowSecurity").on("change", function () {
            var id = angular.element(this).data("id");
            var userId = angular.element(this).data("user");
            var value = angular.element(this).data("value");
            onSaveDocumentSecurityUser(id, userId, value);            
        });

    }

    $scope.dtInstanceCustomerDocumentSecurityUserCallback = function (instance) {
        $scope.dtInstanceCustomerDocumentSecurityUser = instance;
    };

    $scope.reloadData = function () {
        if ($scope.dtInstanceCustomerDocumentSecurityUser != null) {
            $scope.dtInstanceCustomerDocumentSecurityUser.reloadData();
        }
    };


}]);

