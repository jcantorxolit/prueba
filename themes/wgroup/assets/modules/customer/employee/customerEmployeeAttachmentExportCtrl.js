'use strict';
/**
 * controller for Customers
 */
app.controller('customerEmployeeAttachmentExportCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', '$q', 'SweetAlert', '$aside', 'ListService', 
    '$filter', 'ngNotify',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, $q, SweetAlert, $aside, ListService, $filter, ngNotify) {
        
        $scope.canShowDataTable = false;

        $scope.filter = {
            selectedType: null            
        };

        $scope.toggle = {
            isChecked: false,
            selectAll: false
        };

        $scope.records = {
            hasSelected: false,
            countSelected: 0,
            countSelectedAll: 0
        };

        var $selectedItems = {};
        var $uids = {};
        var $currentPageUids = {};    
        var params = null;
        
        var apiUrl = 'api/customer-employee-document-export';
   
        getList();

        function getList() {
    
            var entities = [
                {name: 'customer_employee_document_type', value: $stateParams.customerId}            
            ];
    
            ListService.getDataList(entities)
                .then(function (response) {                
                    $scope.requirements =response.data.data.customerEmployeeDocumentType;

                    initializeDatatable();
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }


        var buildDTColumns = function() {
            var $columns = [
                DTColumnBuilder.newColumn(null).withOption('width', 30)
                .notSortable()
                .withClass("center")                
                .renderWith(function (data, type, full, meta) {
                    var checkTemplate = '';               
                    var isChecked = $selectedItems[data.id].selected;
                    var checked = isChecked ? "checked" : ""

                    checkTemplate = '<div class="checkbox clip-check check-danger ">' +
                        '<input class="selectedRow" type="checkbox" id="chk_employee_document_select_' + data.id + '" data-id="' + data.id + '" ' + checked + ' ><label class="padding-left-10" for="chk_employee_document_select_' + data.id + '"> </label></div>';                        
                
                    return checkTemplate;
                })
            ];

            $columns.push(buildDTColumn('documentNumber', 'Número Identificación', '', 200));
            $columns.push(buildDTColumn('fullName', 'Nombre Empleado', '', 200));
            $columns.push(buildDTColumn('requirement', 'Tipo Documento', '', 200));
            $columns.push(buildDTColumn('description', 'Descripción', '', 200));
            $columns.push(buildDTColumn('startDate', 'Fecha Inicio', '', 200));    
            $columns.push(buildDTColumn('endDate', 'Fecha Fin', '', 200));            
            $columns.push(buildDTColumn('status', 'Estado', '', 200).notSortable().renderWith(function (data, type, full, meta) {
                    var label = '';
                    switch (data) {
                        case "Vigente":
                            label = 'label label-success';
                            break;

                        case "Anulado":
                            label = 'label label-danger';
                            break;

                        case "Vencido":
                            label = 'label label-warning';
                            break;                            
                    }

                    var status = '<span class="' + label + '">' + data + '</span>';

                    return status;
                })
            );

       
            return $columns;
        }
               
        var buildDTColumn = function(field, title, defaultContent, width) {
            return DTColumnBuilder.newColumn(field)
                .withTitle(title)
                .withOption('defaultContent', defaultContent)
                .withOption('width', width);
        };  
        
        var initializeDatatable = function() {
            $scope.canShowDataTable = true;

            var $lastSearch = '';

            $scope.dtOptionsCustomerEmployeeDocumentType = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                data: function (d) {
                    d.customerId = $stateParams.customerId;
                    d.requirementCode = $scope.filter.selectedType ? $scope.filter.selectedType.value : 0;
                    d.requirementOrigin = $scope.filter.selectedType ? $scope.filter.selectedType.origin : 0;       
                    params = d;
                    return JSON.stringify(d);
                },
                dataSrc: function (response) {
                    $currentPageUids = response.data.map(function(item, index, array) {                                                        
                        return item.id;                        
                    })

                    $uids = response.extra;

                    angular.forEach($uids, function (uid, key) {
                        if ($selectedItems[uid] === undefined || $selectedItems[uid] === null) {
                            $selectedItems[uid] = {
                                selected: false
                            };
                        }                       
                    });

                    $scope.records.currentPage = $currentPageUids.length;
                    $scope.records.total = $uids.length;

                    if ($lastSearch !== params.search.value) {
                        $scope.toggle.isChecked = false; 
                        $scope.toggle.selectAll = false; 
                        onCheck($uids, $scope.toggle.isChecked, true);
                        $lastSearch = params.search.value;
                    }

                    return response.data;
                },
                url: apiUrl,
                contentType: "application/json",
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator                    
                },
                complete: function () {
                    
                }
            })
            .withDataProp('data')            
            .withOption('serverSide', true)
            .withOption('processing', true)
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

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);
            });
        
            $scope.dtColumnsCustomerEmployeeDocumentType = buildDTColumns();
        }        

        var loadRow = function () {

            angular.element("#dtCustomerEmployeeDocumentType input.selectedRow").on("change", function () {
                var id = angular.element(this).data("id");

                if (this.className == 'selectedRow') {
                    $selectedItems[id].selected = this.checked;
                }

                $timeout(function () {
                    var countSelected = 0;

                    angular.forEach($selectedItems, function (value, key) {                        
                        countSelected += value.selected ? 1 : 0;                        
                    });

                    $scope.records.hasSelected = countSelected > 0;                    
                    $scope.records.countSelected = countSelected;
                }, 100);
            });
        };

        $scope.dtInstanceCustomerEmployeeDocumentTypeCallback = function (instance) {
            $scope.dtInstanceCustomerEmployeeDocumentType = instance;
            $scope.dtInstanceCustomerEmployeeDocumentType.DataTable.on('page', function() {                            
                $timeout(function () {
                    $scope.toggle.isChecked = $scope.toggle.selectAll; 
                }, 300);
            })

            $scope.dtInstanceCustomerEmployeeDocumentType.DataTable.on('order', function() {                 
                $timeout(function () {
                    $scope.toggle.isChecked = $scope.toggle.selectAll; 
                }, 300);
            })
        };

        $scope.reloadData = function () {
            if ($scope.dtInstanceCustomerEmployeeDocumentType != null) {                
                $scope.dtInstanceCustomerEmployeeDocumentType.reloadData(null, false);                
            }
        };      

        $scope.onSelectType = function() {
            $scope.reloadData();
            $selectedItems = {};
            $scope.toggle.isChecked = false;
            $scope.toggle.selectAll = false;
            $scope.records.hasSelected = false;
            $scope.records.countSelected = 0;                
        }

        $scope.onDownload = function (id) {
            ngNotify.set('El archivo se está generando.', {
                position: 'bottom',
                sticky: true,
                button: false,
                html: true
            });

            var entity = {
                id: Object.keys($selectedItems).filter(function(key) {
                    return $selectedItems[key].selected 
                }).map(function(item) {
                    return item;
                }),
                filename: $scope.filter.selectedType.item
            };

            var req = {};
            var data = JSON.stringify(entity);
            req.data = Base64.encode(data);

            $http({
                method: 'POST',
                url: 'api/customer-employee-document/export-by-type',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                var $url = response.data.path + response.data.filename;                                
                var $link = '<div class="row"><div class="col-sm-12 text-center">Por favor espere y verifique su correo y la bandeja de mensajes!</div> </div>';
                ngNotify.set($link, {
                    position: 'bottom',
                    sticky: true,
                    type: 'success',
                    button: true,
                    html: true
                });

            }).catch(function (response) {
                ngNotify.set(response.data.message, {
                    position: 'bottom',
                    sticky: true,
                    type: 'error',
                    button: true,
                    html: true
                });
            }).finally(function () {

            });
        };
        
        $scope.onCancel = function () {
            $scope.onToggleShowList();
        };

        $scope.onToggle = function () {
            $scope.toggle.isChecked = !$scope.toggle.isChecked;
            onCheck($currentPageUids, $scope.toggle.isChecked);            
        };

        $scope.onSelectCurrentPage = function () {
            $scope.toggle.isChecked = true;
            if ($scope.toggle.selectAll) {
                onCheck($uids, false);
                $scope.toggle.selectAll = false;            
            }
            onCheck($currentPageUids, $scope.toggle.isChecked);            
        };

        $scope.onSelectAll = function () {            
            $scope.toggle.isChecked = true;
            $scope.toggle.selectAll = true;
            onCheck($uids, $scope.toggle.selectAll);            
        };

        $scope.onDeselectAll = function () {
            $scope.toggle.isChecked = false;
            $scope.toggle.selectAll = false;
            onCheck($uids, $scope.toggle.selectAll);            
        };

        var onCheck = function($items, $isCheck, $forceUnCheck) {
            var countSelected = 0;

            angular.forEach($selectedItems, function (uid, key) {
                if ($forceUnCheck !== undefined && $forceUnCheck) {
                    $selectedItems[key].selected = false;
                }

                if ($items.indexOf(parseInt(key)) !== -1) {
                    $selectedItems[key].selected = $isCheck;
                }
                countSelected += $selectedItems[key].selected ? 1 : 0;
            });

            var $elements = angular.element('.selectedRow');
            angular.forEach($elements, function (elem, key) {                
                var $uid = angular.element(elem).data("id");
                angular.element(elem).prop( "checked", $selectedItems[$uid].selected);                
            });
            
            $scope.records.hasSelected = countSelected > 0;
            $scope.records.countSelected = countSelected;            
        }

    }
]);