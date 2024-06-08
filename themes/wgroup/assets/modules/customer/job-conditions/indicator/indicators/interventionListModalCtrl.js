'use strict';
/**
 * controller for JobConditionIndicator
 */
app.controller('JobConditionsIndicatorIndicatorsInterventionsListCtrlModalInstanceSide',
    function($rootScope, $stateParams, $scope, $uibModalInstance, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder,
             $localStorage, $log, $timeout, SweetAlert, $http, $compile, toaster, $document, $aside, dataSource) {

    var initialize = function() {
        $scope.entity = {
            customerId: dataSource.customerId,
            classificationId: dataSource.classificationId,
            questionId: dataSource.questionId
        }
    }
    initialize();

    $scope.onClose = function() {
        $uibModalInstance.close(1);
    }

    $scope.onFinish = function() {
        $uibModalInstance.dismiss('cancel');
    }


    var storeDatatable = 'jobConditionIndicatorIndicatorsInterventionListListCtrl-' + window.currentUser.id;
    $scope.dtInstanceIndicatorsInterventionList = {};
	$scope.dtOptionsIndicatorsInterventionList = DTOptionsBuilder.newOptions()
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            data: function(d) {
                d.customerId = $scope.entity.customerId;
                d.classificationId = $scope.entity.classificationId;
                d.questionId = $scope.entity.questionId;
                return JSON.stringify(d);
            },
            url: 'api/customer-jobconditions/indicators/interventions-questions-historical',
            type: 'POST',
            beforeSend: function () {},
            complete: function () {}
        })
        .withDataProp('data')
        .withOption('stateSave', true)
        .withOption('stateSaveCallback', function (settings, data) {
            $localStorage[storeDatatable] = data;
        })
        .withOption('stateLoadCallback', function () {
            return $localStorage[storeDatatable];
        })
        .withOption('order', [[0, 'desc']])
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


    $scope.dtColumnsIndicatorsInterventionList = [
        DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 120).notSortable()
            .renderWith(function (data) {
                var actions = "";
                var editTemplate = '<a class="btn btn-primary btn-xs editRow lnk" href="#" uib-tooltip="Editar" data-id="' + data.id + '" >' +
                    '   <i class="fa fa-edit"></i></a> ';

                var close = '<a class="btn btn-danger btn-xs closeRow lnk" href="#" uib-tooltip="Cerrar" data-id="' + data.id + '" >' +
                '   <i class="fa fa-close"></i></a> ';

                actions += editTemplate;
                actions += close;
                return actions;
            }),

            DTColumnBuilder.newColumn('employee').withTitle("Nombre Empleado").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('intervention').withTitle("Nombre Intervención").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('responsibleName').withTitle("Responsable").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('date').withTitle("Fecha Ejecución").withOption('width', 200).withOption('defaultContent', ''),

    ];

    var loadRow = function () {

        $("#indicatorsInterventionList a.editRow").on("click", function () {
            var id = $(this).data("id");
            editIntervention(id);
        });

        $("#indicatorsInterventionList a.closeRow").on("click", function () {
            var id = $(this).data("id");
            closeIntervention(id);
        });

    };


    $scope.reloadData = function () {
        $scope.dtInstanceIndicatorsInterventionList.reloadData();
    };


    var closeIntervention = function(id) {
        var data = JSON.stringify({id: id});
        var req = {
            data: Base64.encode(data)
        }

        $http({
            method: 'POST',
            url: 'api/customer-jobconditions/intervention/close',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            data: $.param(req)
        }).then(function(response) {
            SweetAlert.swal("Proceso Exitoso", "Se ha cerrado el plan de intervención correctamente.", "success");
        }).catch(function(response) {
            SweetAlert.swal("Error al cerrar", "Se ha presentado un error durante el cierre del plan de intervención, por favor intentelo de nuevo", "error");
        }).finally(function() {
            $scope.reloadData();
        });
    }

    function editIntervention(id) {
        var modalInstance = $aside.open({
            templateUrl: $rootScope.app.views.urlRoot + "modules/customer/job-conditions/indicator/intervention/intervention_detail_edit_modal.htm",
            placement: 'right',
            windowTopClass: 'top-modal',
            size: 'lg',
            backdrop: 'static',
            controller: 'ModalInstanceSideCustomerJobConditionsIndicatorInterventionDetailEditCtrl',
            scope: $scope,
            resolve: {
                entity: function () {
                    return id;
                },
                isView: function () {
                    return false;
                }
            }
        });

        modalInstance.result.then(function () {
            $scope.reloadData();
        });
    }

});