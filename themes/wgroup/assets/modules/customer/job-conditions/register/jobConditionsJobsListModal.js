app.controller('ModalInstanceSideCustomerJobConditionsJobsListCtrl', function ($rootScope, $stateParams, $scope, workPlace, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile) {

    $scope.title = 'CARGOS DISPONIBLES'

    $scope.entity = {};

    $scope.onCloseModal = function () {
        $uibModalInstance.close($scope.entity);
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
                url: 'api/customer-config-job',
                params: req
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

    $scope.dtInstanceCommonDataTableList = {};
    $scope.dtOptionsCommonDataTableList = DTOptionsBuilder.newOptions()
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            data: function (d) {
                d.customerId = $stateParams.customerId;
                d.workPlaceId = workPlace.id;
                return JSON.stringify(d);
            },
            url: 'api/customer-config-job',
            contentType: "application/json",
            type: 'POST'
        })
        .withDataProp('data')
        .withOption('order', [
            [0, 'desc']
        ])
        .withOption('serverSide', true).withOption('processing', true)
        .withOption('fnPreDrawCallback', function () {
            return true;
        })
        .withOption('fnDrawCallback', function () {
            loadRow();
        })
        .withOption('language', {})
        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row, data, dataIndex) {
            $compile(angular.element(row).contents())($scope);
        });


    $scope.dtColumnsCommonDataTableList = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 150).notSortable()
            .renderWith(function (data, type, full, meta) {
                var disabled = ""

                return '<a class="btn btn-success btn-xs editRow lnk" href="#" uib-tooltip="Adicionar"  data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-plus-square"></i></a> ';
            }),
        DTColumnBuilder.newColumn('work_place').withTitle("Centro de Trabajo").withOption('width', 200),
        DTColumnBuilder.newColumn('macro_process').withTitle("Macro Proceso"),
        DTColumnBuilder.newColumn('process').withTitle("Proceso"),
        DTColumnBuilder.newColumn('job').withTitle("Cargo")
    ];

    var loadRow = function () {
        $("#dtCommonDataTableList a.editRow").on("click", function () {
            var id = $(this).data("id");
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
