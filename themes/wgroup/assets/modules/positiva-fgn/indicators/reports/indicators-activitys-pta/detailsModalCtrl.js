app.controller('positivaFgnIndicatorsActivityPTADetailsCtrlModalInstanceSide',
    function($rootScope, $stateParams, $scope, $uibModalInstance, $log, $timeout, SweetAlert, $http, toaster, $filter,
        FileUploader, DTColumnBuilder, DTOptionsBuilder, $compile, dataSource, PositivaFGNIndicatorFilterService) {

        var initialize = function() {
            $scope.entity = {};
        };

        initialize();

        $scope.onCancel = function() {
            $uibModalInstance.dismiss('cancel');
        };

        $scope.dtInstancePositivaFngIndicatorsReportsActivitiesPTADetail = {};
        $scope.dtOptionsPositivaFngIndicatorsReportsActivitiesPTADetail = DTOptionsBuilder.newOptions()
            .withBootstrap().withOption('responsive', true)
            .withOption('searching', false)
            .withOption('ordering', false)
            .withOption('ajax', {
                data: function(d) {
                    d.customFilter = dataSource.filters
                    d.axis = dataSource.axis;
                    return JSON.stringify(d);
                },
                url: 'api/positiva-fgn-fgn-indicator/indicators/reports/activities-pta-compliance-details',
                type: 'POST',
                beforeSend: function() {},
                complete: function() {}
            })
            .withDataProp('data')
            .withOption('order', [])
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('fnPreDrawCallback', function() {
                return true;
            })
            .withOption('language', {})
            .withPaginationType('full_numbers')
            .withOption('createdRow', function(row) {
                $compile(angular.element(row).contents())($scope);
            });

        $scope.dtColumnsPositivaFngIndicatorsReportsActivitiesPTADetail = [
            DTColumnBuilder.newColumn('activity').withTitle("Actividad").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('goalCompliance').withTitle("Meta Cump.").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('countActivities').withTitle("# actividades").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('percentCompliance').withTitle("% Cumplimiento").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('goalCoverage').withTitle("Meta Cob.").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('population').withTitle("Población").withOption('width', 200).withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('percentCoverage').withTitle("% Cobertura").withOption('width', 200).withOption('defaultContent', ''),
        ];

    });