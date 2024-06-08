app.controller('ModalInstanceSideDashboardTopManagementSearchAdministratorCtrl', function ($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout,
                                                                                           SweetAlert, $http, toaster, $filter, FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile, customerId) {

    $scope.title = 'ADMINISTRADORES';

    $scope.entity = {
        id: 0,
    };

    $scope.onCloseModal = function () {
        var user = {
            id: $scope.entity.id,
            value: $scope.entity.id,
            item: $scope.entity.name,
        }

        $uibModalInstance.close(user);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.dtOptionsCommonDataTableList = DTOptionsBuilder.newOptions()
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            data: function (d) {
                d.customerId = customerId;
                return JSON.stringify(d);
            },
            url: 'api/dashboard/top-management/get-administrators',
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

        DTColumnBuilder.newColumn('name').withTitle("Nombre").withOption('width', 200),
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


    var onLoadRecord = function (id) {
        var req = {id: id};

        $http({
            method: 'GET',
            url: 'api/users/get',
            params: req
        }).then(function (response) {
            $scope.entity = response.data.result;

        }).finally(function () {
            $scope.onCloseModal();
        });
    }

});