'use strict';
/**
  * controller for Customers
*/
app.controller('customerPollListCtrl', ['$scope', '$stateParams', '$log','DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder','$compile','toaster','$state','$rootScope','$timeout','SweetAlert','$http', '$filter', '$document',
function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder,
          $compile, toaster, $state, $rootScope,$timeout, SweetAlert, $http, $filter, $document) {

    var log = $log;
    var request = {};
        log.info("loading..customerPollListCtrl ");

   // $rootScope.tabname = "tracking";

    // default view
   // $rootScope.tracking_section = "list";

    // Datatable configuration
    request.operation = "poll";
    request.customer_id = $stateParams.customerId;

    $scope.agents = $rootScope.agents();

    $scope.dtInstancePoll = {};
		$scope.dtOptionsPoll = DTOptionsBuilder.newOptions()
        // Add Bootstrap compatibility
        .withBootstrap().withOption('responsive', true)
        .withOption('ajax', {
            // Either you specify the AjaxDataProp here
            data: request,
            url: 'api/customer/poll',
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
            //log.info("fnPreDrawCallback");
            //Pace.start();
            return true;
        })
        .withOption('fnDrawCallback', function () {
            //log.info("fnDrawCallback");
            loadRow();
            //Pace.stop();

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

    $scope.dtColumnsPoll = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 110).notSortable()
            .renderWith(function (data, type, full, meta) {
                var actions = "";
                var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Contestar encuesta" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-play-circle"></i></a> ';
                var viewTemplate = '<a class="btn btn-info btn-xs viewRow lnk" href="#" uib-tooltip="Ver encuesta" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-eye"></i></a> ';


                if($rootScope.can("seguimiento_view")){
                    actions += viewTemplate;
                }

                if($rootScope.can("seguimiento_edit")){
                    if (data.status.item == "Iniciada" || data.status.item == "Asignada") {
                        actions += editTemplate;
                    }
                }

                return actions;
            }),
        DTColumnBuilder.newColumn('poll.name').withTitle("Encuesta").withOption('width', 300),
        DTColumnBuilder.newColumn('poll.description').withTitle("Descripción").withOption('width'),
        DTColumnBuilder.newColumn('poll.endDateTime').withTitle("Fecha Vencimiento").withOption('width', 200),
        DTColumnBuilder.newColumn('answerCount').withTitle("Respuestas").withOption('width', 150),
        DTColumnBuilder.newColumn('status.item').withTitle("Estado").withOption('width', 150)
            .renderWith(function (data, type, full, meta) {
                var label = '';
                switch  (data)
                {
                    case "Iniciada":
                        label = 'label label-info';
                        break;

                    case "Completada":
                        label = 'label label-success';
                        break;

                    case "Asignada":
                        label = 'label label-warning';
                        break;

                    case "Vencida":
                        label = 'label label-danger';
                        break;
                }

                var status = '<span class="' + label +'">' + data + '</span>';


                return status;
            })
    ];

    var loadRow = function () {

        $("#dtCustomerPoll a.editRow").on("click", function () {
            var id = $(this).data("id");
            $scope.editPoll(id);
        });

        $("#dtCustomerPoll a.viewRow").on("click", function () {
            var id = $(this).data("id");
            $scope.viewPoll(id);
        });
    };

    $scope.reloadData = function () {
        $scope.dtInstancePoll.reloadData();
    };


    $scope.editPoll = function(id){
        if($scope.$parent != null){
            $scope.$parent.navToSection("form", "edit", id);
        }
    };

    $scope.viewPoll = function(id){
        if($scope.$parent != null){
            $scope.$parent.navToSection("form", "view", id);
        }
    };

}]);