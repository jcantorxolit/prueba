app.controller('ModalInstanceSideDashboardTopManagementSearchCustomerCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.title = 'CLIENTES DISPONIBLES';

    var isAgent = $rootScope.isAgent();

    $scope.entity = {
        id: 0,
    };

    $scope.onCloseModal = function () {
        var customer = {
            id: $scope.entity.id,
            item: $scope.entity.businessName,
            value: $scope.entity.id,
            arl: $scope.entity.arl ? $scope.entity.arl.item : null,
        }
        $uibModalInstance.close(customer);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    var onLoadRecord = function (id) {
        if (id != 0) {
            var req = {
                id: id,
            };
            $http({
                method: 'GET',
                url: 'api/customer',
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

                $timeout(function () {
                    $scope.entity = response.data.result;
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

    $scope.dtOptionsCommonDataTableList = DTOptionsBuilder.newOptions()
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            data: function(d) {
                d.operation = "customer";
                return JSON.stringify(d);
            },
            url: 'api/dashboard/top-management/get-customers',
            contentType: 'application/json',
            type: 'POST'
        })
        .withDataProp('data')
        .withOption('order', [[0, 'desc']])
        .withOption('serverSide', true)
        .withOption('processing', true)
        .withOption('fnDrawCallback', function () {
            loadRow();
        })
        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        });


    $scope.dtColumnsCommonDataTableList = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 80).notSortable()
            .renderWith(function (data) {

                var actions = "";
                var disabled = ""

                var editTemplate = '<a class="btn btn-success btn-xs editRow lnk" href="#" uib-tooltip="Adicionar causa"  data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-plus-square"></i></a> ';

                actions += editTemplate;

                return actions;
            }),

        DTColumnBuilder.newColumn('documentType').withTitle("Tipo de Documento").withOption('width', 200),
        DTColumnBuilder.newColumn('documentNumber').withTitle("Nro Documento").withOption('width', 200),
        DTColumnBuilder.newColumn('businessName').withTitle("Razón Social").withOption('width', 200),
        DTColumnBuilder.newColumn('type').withTitle("Tipo de Cliente").withOption('width', 200),
        DTColumnBuilder.newColumn('classification').withTitle("Clasificación").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {

                if (data == null || data == undefined)
                    return "";

                return data;
            }),
        DTColumnBuilder.newColumn('status').withTitle("Estado").withOption('width', 200)
            .renderWith(function (data, type, full, meta) {
                var label = '';
                switch  (data)
                {
                    case "Activo":
                        label = 'label label-success';
                        break;

                    case "Inactivo":
                        label = 'label label-danger';
                        break;

                    case "Retirado":
                        label = 'label label-warning';
                        break;
                }

                var status = '<span class="' + label +'">' + data + '</span>';


                return status;
            }),
    ];

    var loadRow = function () {
        angular.element("#dtCommonDataTableList a.editRow").on("click", function () {
            var id = angular.element(this).data("id");
            onLoadRecord(id);
        });
    };

    $scope.dtInstanceCommonDataTableListCallback = function (instance) {
        $scope.dtInstanceCommonDataTableList = instance;
    };

    $scope.reloadData = function () {
        $scope.dtInstanceCommonDataTableList.reloadData();
    };

});