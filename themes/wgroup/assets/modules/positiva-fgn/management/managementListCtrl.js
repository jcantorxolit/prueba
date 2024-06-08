app.controller('PFManagementListCtrl',
    function($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $localStorage, $compile, $aside, $state, $rootScope, PFManagementService) {

        if (!PFManagementService.getInfoBasic()) {
            $state.go("app.positiva-fgn.fgn-management-axis-programming");
        }

        $scope.documentTypeList = $rootScope.parameters("employee_document_type");
        $scope.genderList = $rootScope.parameters("gender");

        $scope.entity = PFManagementService.getInfoBasic();
        $scope.action = PFManagementService.getAction();

        var storeDatatable = 'managementListCtrl-' + window.currentUser.id;
        $scope.dtInstancePositivaFgn = {};
        $scope.dtOptionsPositivaFgn = DTOptionsBuilder.newOptions()
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                data: function(d) {
                    if ($scope.entity.axis.axis) {
                        d.axisVal = $scope.entity.axis.axis;
                    }
                    if ($scope.entity.period.value) {
                        d.periodVal = $scope.entity.period.value;
                    }
                    if ($scope.entity.period.config) {
                        d.configVal = $scope.entity.period.config;
                    }
                    if ($scope.entity.sectional.value) {
                        d.sectionalVal = $scope.entity.sectional.value;
                    }
                    if ($scope.entity.id) {
                        d.consultantVal = $scope.entity.id;
                    }
                    d.action = $scope.action;
                    return JSON.stringify(d);
                },
                url: 'api/positiva-fgn-fgn-management/activitiesProgrammingExecution',
                type: 'POST',
                beforeSend: function() {},
                complete: function() {}
            })
            .withDataProp('data')
            .withOption('stateSave', true)
            .withOption('stateSaveCallback', function(settings, data) {
                $localStorage[storeDatatable] = data;
            })
            .withOption('stateLoadCallback', function() {
                return $localStorage[storeDatatable];
            })
            .withOption('order', [
                [0, 'desc']
            ])
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('fnPreDrawCallback', function() {
                return true;
            })
            .withOption('fnDrawCallback', function() {
                loadRow();
            })
            .withOption('language', {})
            .withPaginationType('full_numbers')
            .withOption('createdRow', function(row) {
                $compile(angular.element(row).contents())($scope);

            });

        $scope.dtInstancePositivaFgnCallback = function(instance) {
            $scope.dtInstancePositivaFgn = instance;
        };

        $scope.dtColumnsPositivaFgn = [
            DTColumnBuilder.newColumn(null).withTitle("Acciones").withOption('width', 120).notSortable()
            .renderWith(function(data) {
                var actions = "";
                var disabled = ""
                var config = '<a class="btn btn-blue btn-xs configRow lnk" href="#"  uib-tooltip="Configurar" data-id="' + data.id + '"' + disabled + ' >' +
                    '   <i class="fa fa-cog"></i></a> ';

                actions += config;
                return actions;
            }),

            DTColumnBuilder.newColumn('action').withTitle("Acción").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('activity').withTitle("Actividad").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('strategy').withTitle("Estrategia").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('modality').withTitle("Modalidad").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('activityCode').withTitle("Cod Act").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('activityGestpos').withTitle("Actividad CREA").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('task').withTitle("Tarea").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('goalCompliance').withTitle("Meta Cumplimiento").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('programCompliance').withTitle("Programado").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('programPercentCompliance').withTitle("% Programación").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('goalCoverage').withTitle("Meta Cobertura").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('programCoverage').withTitle("Programado").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('programPercentCoverage').withTitle("% Programación").withOption('width', 200).withOption('defaultContent', ''),
        ];

        if ($scope.action == "execution") {
            $scope.dtColumnsPositivaFgn[9] = DTColumnBuilder.newColumn('executedCompliance').withTitle("Ejecutado").withOption('width', 200).withOption('defaultContent', '');
            $scope.dtColumnsPositivaFgn[10] = DTColumnBuilder.newColumn('executedPercentCompliance').withTitle("% Ejecución").withOption('width', 200).withOption('defaultContent', '');
            $scope.dtColumnsPositivaFgn[12] = DTColumnBuilder.newColumn('executedCoverage').withTitle("Ejecutado").withOption('width', 200).withOption('defaultContent', '');
            $scope.dtColumnsPositivaFgn[13] = DTColumnBuilder.newColumn('executedPercentCoverage').withTitle("% Ejecución").withOption('width', 200).withOption('defaultContent', '');
        }

        $scope.reloadData = function() {
            $scope.dtInstancePositivaFgn.reloadData();
        };

        var loadRow = function() {
            $("#dtPositivaFgn a.configRow").on("click", function() {
                var id = $(this).data("id");
                $scope.onConfig(id);
            });
        };

        $scope.onBack = function() {
            if ($scope.action == "programming") {
                $state.go("app.positiva-fgn.fgn-management-axis-programming");
            } else {
                $state.go("app.positiva-fgn.fgn-management-axis-execution");
            }
        };

        $scope.onConfig = function(id) {
            $scope.modalId = id;
            var modalInstance = $aside.open({
                templateUrl: $rootScope.app.views.urlRoot + "modules/positiva-fgn/management/management_modal.htm",
                placement: 'right',
                windowTopClass: 'top-modal',
                size: 'lg',
                backdrop: 'static',
                controller: "PFmanagementModalCtrl",
                scope: $scope
            });
            modalInstance.result.then(function() {
                $scope.reloadData();
            });
        }

        $scope.onExec = function() {
            PFManagementService.setAction("execution");
            $state.reload();
        }

        $scope.onProgram = function() {
            PFManagementService.setAction("programming");
            $state.reload();
        }

    });