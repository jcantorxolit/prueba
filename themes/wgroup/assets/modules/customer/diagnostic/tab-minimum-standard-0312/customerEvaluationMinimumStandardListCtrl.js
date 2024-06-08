'use strict';
/**
 * controller for Customers
 */
app.controller('customerEvaluationMinimumStandardList0312Ctrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', 'ChartService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, ChartService) {

        var log = $log;
        
        $scope.canCreate = false;
             
        $scope.dtOptionsEvaluationMinimumStandardList0312 = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {
                    d.operation = "standard";
                    d.customerId = $stateParams.customerId;
                    return JSON.stringify(d);
                },
                dataSrc: function (response) {
                    $scope.canCreate = response.extra;
                    return response.data;
                },                    
                url: 'api/customer-evaluation-minimum-standard-0312',
                contentType: 'application/json',
                type: 'POST',
                beforeSend: function () {
                },
                complete: function (data) {                    
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
            .withPaginationType('full_numbers')
            .withOption('createdRow', function (row, data, dataIndex) {
              
                $compile(angular.element(row).contents())($scope);
            });        

        $scope.dtColumnsEvaluationMinimumStandardList0312 = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
                .renderWith(function (data, type, full, meta) {
                    var actions = '';
                    var continueTemplate = '<a class="btn btn-success btn-xs continueRow lnk" href="#" uib-tooltip="Continuar"  data-id="' + data.id + '" >' +
                        '   <i class="fa fa-play"></i></a> ';

                    var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#" uib-tooltip="Ver"  data-id="' + data.id + '" >' +
                    '   <i class="fa fa-eye"></i></a> ';      
                    
                    var migrateTemplate = ' | <a class="btn btn-dark-azure btn-xs migrateRow lnk" href="#" uib-tooltip="Migrar Calificaciones Periodo Anterior"  data-id="' + data.id + '" data-from-id="' + data.migrateFromId + '" >' +
                    '   <i class="fa fa-share-square-o"></i></a> ';
                    
                    if ($rootScope.can("em_0312_historico_view")) {
                        actions += viewTemplate;
                    }

                    if ($rootScope.can("em_0312_historico_continue") && data.statusCode == 'A') {
                        actions += continueTemplate;
                    }

                    if (data.canMigrate == 1) {
                        actions += migrateTemplate;
                    }

                    return actions;
                }),

            DTColumnBuilder.newColumn('unique').withTitle("ID").withOption('width', 100),
            DTColumnBuilder.newColumn('period').withTitle("Periodo").withOption('width', 100),
            DTColumnBuilder.newColumn('createdAt').withTitle("Fecha Creación").withOption('width', 150),            
            DTColumnBuilder.newColumn(null)
                .withTitle("Estado")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-warning';
                    
                    if (data.statusCode == "A") {                        
                        label = 'label label-success';
                    } else if (data.statusCode == "C") {                        
                        label = 'label label-danger';
                    }

                    return '<span class="' + label + '">' + data.status + '</span>';                    
                })
                .notSortable()
        ];

        var loadRow = function () {

            angular.element("#dtEvaluationMinimumStandardList0312 a.continueRow").on("click", function () {
                var id = angular.element(this).data("id");
                $scope.onContinue(id);
            });

            angular.element("#dtEvaluationMinimumStandardList0312 a.viewRow").on("click", function () {
                var id = angular.element(this).data("id");
                $scope.onView(id);
            });

            angular.element("#dtEvaluationMinimumStandardList0312 a.migrateRow").on("click", function () {
                var id = angular.element(this).data("id");
                var fromId = angular.element(this).data("from-id");
                $scope.onMigrate({
                    id: id,
                    fromId: fromId,
                    customerId: $stateParams.customerId
                });
            });
        };        

        $scope.dtInstanceEvaluationMinimumStandardList0312Callback = function (instance) {
            $scope.dtInstanceEvaluationMinimumStandardList0312 = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceEvaluationMinimumStandardList0312.reloadData();
        };

        $scope.onContinue = function (id) {
            if ($scope.$parent != null) {
                $scope.setEditMode('continue');
                $scope.$parent.navToSection("summary", "summary", id);
            }
        };

        $scope.onView = function (id) {
            if ($scope.$parent != null) {
                $scope.setEditMode('view');
                $scope.$parent.navToSection("summary", "summary", id);
            }
        };

        $scope.onCreate = function () {                        
            var data = JSON.stringify({
                customerId: $stateParams.customerId
            });
            var req = {
                data: Base64.encode(data)
            };

            return $http({
                method: 'POST',
                url: 'api/customer-evaluation-minimum-standard-0312/save',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {    
                SweetAlert.swal("Registro", "La información ha sido guardada satisfactoriamente", "success");  
                
                if ($rootScope.app.supportHelp) {                    
                    $rootScope.app.supportHelp.hasMinimumStandard0312 = true;
                }

                $scope.reloadData();                    
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });
    
            
        };

        $scope.onMigrate = function (entity) {                        
            var data = JSON.stringify(entity);
            var req = {
                data: Base64.encode(data)
            };

            return $http({
                method: 'POST',
                url: 'api/customer-evaluation-minimum-standard-0312/migrate',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {    
                SweetAlert.swal("Registro", "La información ha sido migrada satisfactoriamente", "success");                   
                $scope.reloadData();                    
            }).catch(function (e) {
                $log.error(e);
                SweetAlert.swal("Error de guardado", "Error guardando el registro. Por favor verifique los datos ingresados!", "error");
            }).finally(function () {

            });
    
            
        };
     
    }]);