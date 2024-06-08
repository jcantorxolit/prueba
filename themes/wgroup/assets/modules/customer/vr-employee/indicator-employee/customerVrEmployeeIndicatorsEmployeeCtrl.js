'use strict';
/**
  * controller for Customers
*/
app.controller('customerVrEmployeeIndicatorsEmployeeCtrl',
function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
    $rootScope, $timeout, $http, SweetAlert, $document, $filter, $aside, ListService, ChartService) {

    $scope.periodList = [];
    $scope.entity = {
        customerId: $stateParams.customerId,
        selectedYear: null,
        employee: {
            id: 0,
            documentNumber: "",
            firstName: "",
            lastName: ""
        },
    };

    $scope.chart = {
        line: {options: null},
        data: {
            experienceByEmployee: null,
        }
    };

    $scope.openedGrids = {};

    function getList() {
        var entities = [
            { name: 'customer_vr_employee_historical_period_list', criteria: {employeeId: $scope.entity.employee.id} },
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.periodList = response.data.data.periodList;
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    $scope.onSelectMonth = function() {
        $scope.reloadData();
        getCharts();
    }

    $scope.onExportPdf = function () {
        // $scope.reloadDataAllGrids();
        kendo.drawing.drawDOM($(".customer-vr-employee-indicators-export-pdf"))
            .then(function (group) {
                // Render the result as a PDF file
                return kendo.drawing.exportPDF(group, {
                    paperSize: "auto",
                    margin: {left: "1cm", top: "1cm", right: "1cm", bottom: "1cm"}
                });
            })
            .done(function (data) {
                // Save the PDF file
                kendo.saveAs({
                    dataURI: data,
                    fileName: "INDICADORES_REALIDAD_VIRTUAL_" + $scope.entity.employee.firstName + '_' + $scope.entity.employee.lastName + ".pdf",
                    proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                });

            });
    }

    $scope.dtInstanceVrEmployeeIE = {};
    $scope.dtOptionsVrEmployeeIE = DTOptionsBuilder.newOptions()
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            data: function (d) {
                d.customerId = $stateParams.customerId;
                if($scope.entity.employee.id) {
                    d.customerEmployeeId = $scope.entity.employee.id;
                }

                if($scope.entity.selectedYear) {
                    d.selectedYear = $scope.entity.selectedYear.value;
                }
                return JSON.stringify(d);
            },
            url: 'api/customer-vr-employee-scene-answer/summary-all',
            contentType: "application/json",
            type: 'POST',
            beforeSend: function (instance) {
                if(!$scope.entity.selectedYear) {
                    instance.abort();
                }
            }
        })
        .withDataProp('data')
        .withOption('order', [ [0, 'desc'], [1, 'asc'] ])
        .withOption('serverSide', true).withOption('processing', true)
        .withOption('fnPreDrawCallback', function () {
            if(!$scope.entity.selectedYear) {
                return false;
            }
            return true;
        })
        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        });


    $scope.dtColumnsVrEmployeeIE = [
            DTColumnBuilder.newColumn('date').withTitle("Fecha").withOption('width', 220).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('experience').withTitle("Experiencia").withOption('width', 220).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('scene').withTitle("Escena").withOption('width', 220).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('question').withTitle("Indicador").withOption('width', 220).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn(null).withTitle("Valoración").withOption('width', 120)
            .renderWith(function (data, type, full, meta) {

                var icon = "";
                if(data.answer == "SI") {
                    icon = '<i class=" text-success fa fa-2x fa-check-circle-o" aria-hidden="true"></i>';
                }
                else if(data.answer == "NO") {
                    icon = '<i class=" text-danger fa fa-2x fa-ban" aria-hidden="true"></i>';
                }
                else if(data.answer == "NO APLICA") {
                    icon = '<i class=" text-inverse fa fa-2x fa-minus-circle" aria-hidden="true"></i>';
                }
                else if(data.answer == "NEGACIÓN DEL USUARIO") {
                    icon = '<i class=" text-warning fa fa-2x fa-exclamation-circle" aria-hidden="true"></i>';
                }
                else {
                    icon = '<i class=" text-warning fa fa-2x fa-question-circle" aria-hidden="true"></i>';
                    data.question = "SELECCIONE";
                }

                var status = '<span class="vertical-align-sub">' + icon + ' </span>' + data.answer;
                return status;
            }),
    ];


    $scope.dtInstanceVrEmployeeIECallback = function (instance) {
        $scope.dtInstanceVrEmployeeIE = instance;
    };

    $scope.reloadData = function () {
        $scope.dtInstanceVrEmployeeIE.reloadData();
    };


    $scope.dtInstanceCustomerVrExperienceByEmployeeIndicator = {};


    $scope.dtInstanceCustomerVrExperienceByEmployeeIndicatorCallback = function (instance) {
        $scope.dtInstanceCustomerVrExperienceByEmployeeIndicator[instance.id] = instance;
    }

    $scope.reloadDataAllGrids = function () {
        for (var key in $scope.dtInstanceCustomerVrExperienceByEmployeeIndicator) {
            var id = $scope.dtInstanceCustomerVrExperienceByEmployeeIndicator[key].id
            var experience = id.split("-")[1] || 0;

            Object.keys($scope.openedGrids).forEach(function (experience) {
                $scope.openedGrids[experience] = false;
            })

            var url = 'api/customer-vr-employee-scene-answer/grid/';
            $scope.dtInstanceCustomerVrExperienceByEmployeeIndicator[key].DataTable.ajax.url(url + experience);
            $scope.dtInstanceCustomerVrExperienceByEmployeeIndicator[key].reloadData();
        }
    }


    $scope.onOpenGridByExperience = function (experience) {
        // hideGridsExcept(experience);
        $scope.openedGrids[experience] = !$scope.openedGrids[experience];

        if ($scope.openedGrids[experience]) {
            return;
        }

        var url = 'api/customer-vr-employee-scene-answer/grid/';
        var key = "dtCustomerVrExperienceByEmployeeIndicator-" + experience;
        $scope.dtInstanceCustomerVrExperienceByEmployeeIndicator[key].DataTable.ajax.url(url + experience);
        $scope.dtInstanceCustomerVrExperienceByEmployeeIndicator[key].reloadData();
    };


    $scope.dtOptionsCustomerVrExperienceByEmployeeIndicator = DTOptionsBuilder.newOptions()
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            data: function (d) {
                d.customerEmployeeId = $scope.entity.employee.id;
                d.year = $scope.entity.selectedYear.value;
                return JSON.stringify(d);
            },
            url: 'api/customer-vr-employee-scene-answer/grid/0',
            type: 'POST',
        })
        .withDataProp('data')
        .withOption('order', [
            [0, 'asc    ']
        ])
        .withOption('serverSide', true)
        .withOption('processing', true)
        .withOption('searching', false)
        .withOption('ordering', false)
        .withOption('paging', false)
        .withOption('paginate', false)
        .withOption('info', false)
        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row) {
            $compile(angular.element(row).contents())($scope);
        });


    $scope.dtColumnsCustomerVrExperienceByEmployeeIndicator = [
        DTColumnBuilder.newColumn('date').withTitle("Fecha"),
        //DTColumnBuilder.newColumn('percent').withTitle("Porcentaje"),
        DTColumnBuilder.newColumn(null).withTitle("Porcentaje")
        .renderWith(function(data, type, full, meta) {
            return data.percent + '%'
        })
    ];


    $scope.onSearchEmployee = function () {
        var modalInstance = $aside.open({
            templateUrl: $rootScope.app.views.urlRoot + "modules/customer/common/modals/employee_list_modal.htm",
            placement: 'right',
            windowTopClass: 'top-modal',
            size: 'lg',
            backdrop: true,
            controller: 'ModalInstanceSideEmployeeListCtrl',
            scope: $scope,
        });
        modalInstance.result.then(function (response) {
            parseEmployeeInfo(response);
        });
    };

    var parseEmployeeInfo = function (data) {

        var employee = {
            id: data.id,
            documentNumber: data.entity.documentNumber,
            firstName: data.entity.firstName,
            lastName: data.entity.lastName
        };

        $scope.entity.employee = employee;
        $scope.selectedYear = null;
        $scope.chart.data.experienceByEmployee = [];
        getList();
        $scope.reloadData();
    }


    function getCharts() {
        var entities = [
            {name: 'chart_line_options', criteria: null},
            {
                name: 'customer_vr_experience_employee_indicators', criteria: {
                    customerEmployeeId: $scope.entity.employee.id,
                    year: $scope.entity.selectedYear.value
                }
            },
        ];

        ChartService.getDataChart(entities)
            .then(function (response) {
                $scope.chart.line.options = angular.copy(response.data.data.chartLineOptions);
                $scope.chart.line.options.legend.display = false;

                // set data
                $scope.chart.data.experienceByEmployee = response.data.data.customerVrExperienceEmployeeIndicators;

                $scope.chart.data.experienceByEmployee.forEach(function (indicator) {
                    $scope.openedGrids[indicator.experienceCode] = true;
                });

            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    function hideGridsExcept(except) {
        Object.keys($scope.openedGrids).forEach(function (experience) {
            if (experience != except) {
                $scope.openedGrids[experience] = true;
            }
        })
    }

});


app.controller('ModalInstanceSideEmployeeListCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout,
    SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile, ListService, $aside) {

   $scope.canCreate = false;
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
           type: 'POST'
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
       .withPaginationType('full_numbers')
       .withOption('createdRow', function (row, data, dataIndex) {
           $compile(angular.element(row).contents())($scope);
       });


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
