app.controller('ModalInternalProjectTaskViewInstanceSideCtrl', function ($scope, $uibModalInstance, project, $log, $uibModal, $timeout, SweetAlert, isView, $http, DTOptionsBuilder, DTColumnBuilder, $compile) {

    $scope.project = project;

    $scope.ok = function () {
        $uibModalInstance.close(1);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    var request = {};
    request.operation = "tracking";
    request.project_id = $scope.project.id;

    $scope.dtInstanceTask = {};
    $scope.dtOptionsTask = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: function(d) {
                d.projectId = $scope.project.id;

                return JSON.stringify(d);
            },
            url: 'api/customer-internal-project-user-task',
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
            return true;
        })
        .withOption('fnDrawCallback', function () {

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

    $scope.dtColumnsTask = [
        DTColumnBuilder.newColumn('task').withTitle("Tarea"),
        DTColumnBuilder.newColumn('shortObservation').withTitle("Descriptión"),
        DTColumnBuilder.newColumn('type').withTitle("Tipo").withOption('width', 200),
        DTColumnBuilder.newColumn('startDateTime').withTitle("Fecha Inicio").withOption('width', 150),
        DTColumnBuilder.newColumn('endDateTime').withTitle("Fecha Fin").withOption('width', 150),
        DTColumnBuilder.newColumn('duration').withTitle("Duración").withOption('width', 150),
        DTColumnBuilder.newColumn('agent').withTitle("Asesor").withOption('width', 400),
        DTColumnBuilder.newColumn(null).withTitle("Estado").withOption('width', 80)
            .renderWith(function (data, type, full, meta) {
                var label = '';
                var text = data.status;
                switch (data.statusCode) {
                    case "activo":
                        label = 'label label-success';
                       
                        break;

                    case "cancelador":
                        label = 'label label-danger';
                        
                        break;

                    case "inactivo":
                        label = 'label label-warning';
                        
                        break;
                }

                var status = '<span class="' + label + '">' + text + '</span>';


                return status;
            })
    ];

    $scope.reloadData = function () {
        $scope.dtInstanceTask.reloadData();
    };

});