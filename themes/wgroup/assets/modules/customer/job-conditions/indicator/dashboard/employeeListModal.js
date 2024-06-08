app.controller('ModalInstanceSideJobConditionsIndicatorEmployeeListCtrl', function($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout,
    SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.canCreate = false;
    $scope.title = 'Listado Empleados';
    $scope.canFilter = false;
    $scope.employee = {
        id: 0,
    };

    $scope.onCloseModal = function() {
        $uibModalInstance.close($scope.employee);
    };

    $scope.onCancel = function() {
        $uibModalInstance.dismiss('cancel');
    };


    $scope.onLoadRecord = function() {
        if ($scope.employee.id == 0) {
            $scope.loading = false;
            return;
        }

        var req = {
            id: $scope.employee.id,
        };

        $http({
                method: 'GET',
                url: 'api/customer-employee',
                params: req
            })
            .catch(function(e, code) {
                if (code == 403) {
                    var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.clientes.list';
                    SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                    $timeout(function() {
                        $state.go(messagered);
                    });
                } else if (code == 404) {
                    SweetAlert.swal("Información no disponible", "Empleado no encontrado", "error");
                } else {
                    SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del proceso", "error");
                }
            })
            .then(function(response) {
                $timeout(function() {
                    $scope.employee = response.data.result;
                });
            }).finally(function() {
                $timeout(function() {
                    $scope.onCloseModal();
                }, 400);
            });
    }

    $scope.dtInstanceModalEmployeeList = {};
    $scope.dtOptionsModalEmployeeList = DTOptionsBuilder.newOptions()
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            data: function(d) {
                d.customerId = $stateParams.customerId;
                d.jobConditions = true;
                return JSON.stringify(d);
            },
            url: 'api/customer-employee-modal-basic',
            contentType: 'application/json',
            type: 'POST'
        })
        .withDataProp('data')
        .withOption('order', [
            [0, 'desc']
        ])
        .withOption('serverSide', true).withOption('processing', true)
        .withOption('fnPreDrawCallback', function() {
            return true;
        })
        .withOption('fnDrawCallback', function() {
            loadRow();
        })
        .withOption('language', {})
        .withPaginationType('full_numbers')
        .withOption('createdRow', function(row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        });


    $scope.dtColumnsModalEmployeeList = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
        .renderWith(function(data, type, full, meta) {
            var disabled = ""

            return '<a class="btn btn-success btn-xs editRow lnk" href="#" uib-tooltip="Adicionar empleado" tooltip-placement="right"  data-id="' + data.id + '"' + disabled + ' >' +
                '   <i class="fa fa-plus-square"></i></a> ';
        }),
        DTColumnBuilder.newColumn('documentNumber').withTitle("Número Identificación").withOption('width', 200),
        DTColumnBuilder.newColumn('firstName').withTitle("Nombre").withOption('width', 200),
        DTColumnBuilder.newColumn('lastName').withTitle("Apellidos").withOption('width', 200),
        DTColumnBuilder.newColumn('workPlace').withTitle("Centro Trabajo").withOption('width', 200),
        DTColumnBuilder.newColumn('job').withTitle("Cargo").withOption('width', 200),
    ];

    var loadRow = function() {
        angular.element("#dtModalEmployeeList a.editRow").on("click", function() {
            var id = angular.element(this).data("id");
            $scope.editModalEmployeeList(id);
        });
    };

    $scope.reloadData = function() {
        $scope.dtInstanceModalEmployeeList.reloadData();
    };

    $scope.editModalEmployeeList = function(id) {
        $scope.employee.id = id;
        $scope.isView = false;
        $scope.onLoadRecord();
    };

});