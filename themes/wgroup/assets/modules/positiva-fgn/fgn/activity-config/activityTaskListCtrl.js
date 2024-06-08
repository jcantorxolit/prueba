'use strict';

app.controller('PFFactivityTaskListCtrl', function($scope, $uibModalInstance, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $timeout, SweetAlert, $compile) {
    $scope.dtInstanceActivityTaskList = {};
    $scope.dtOptionsActivityTaskList = DTOptionsBuilder.newOptions()
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            data: function(d) {
                d.strategy = $scope.strategy;
                return JSON.stringify(d);
            },
            url: 'api/positiva-fgn-fgn-activity-config-activity',
            type: 'POST',
            beforeSend: function() {},
            complete: function() {}
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

    $scope.dtColumnsActivityTaskList = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 90).notSortable()
        .renderWith(function(data) {
            var actions = "";
            var editTemplate = '<a class="btn btn-green btn-xs addRow lnk" href="#" uib-tooltip="Agregar"  data-idactivity="' + data.idActivity + '" data-idtask="' + data.idTask + '"  ' +
                '   data-activity="' + data.activity + '" data-task="' + data.task + '" data-number="' + data.number + '" >' +
                '   <i class="glyphicon glyphicon-plus"></i></a> ';

            actions += editTemplate;
            return actions;
        }),

        DTColumnBuilder.newColumn('code').withTitle("Código").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('activity').withTitle("Actividad").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('task').withTitle("Tarea").withOption('width', 200).withOption('defaultContent', ''),
    ];

    var loadRow = function() {
        $("#dtActivityTaskList a.addRow").on("click", function() {
            var activity = $(this).data("activity");
            var task = $(this).data("task");
            var idactivity = $(this).data("idactivity");
            var idtask = $(this).data("idtask");
            var number = $(this).data("number");
            var res = {
                "activity": { "item": activity, "value": idactivity },
                "task": { "item": task, "value": idtask, "number": number }
            }

            $uibModalInstance.close(res);
        });
    };

    $scope.reloadData = function() {
        $scope.dtInstanceActivityTaskList.reloadData();
    };

    $scope.onCloseModal = function() {
        $uibModalInstance.dismiss();
    }

});
