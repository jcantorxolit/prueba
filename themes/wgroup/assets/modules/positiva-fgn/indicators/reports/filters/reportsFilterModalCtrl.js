app.controller('positivaFgnIndicatorsFiltersCtrlModalInstanceSide',
    function($scope, $uibModalInstance, dataSource, PositivaFGNIndicatorFilterService) {

        $scope.regionalList = PositivaFGNIndicatorFilterService.getRegionals();
        $scope.sectionalList = PositivaFGNIndicatorFilterService.getSectionals();
        $scope.groupList = PositivaFGNIndicatorFilterService.getGroups();
        $scope.axisList = PositivaFGNIndicatorFilterService.getAxis();
        $scope.indicators = PositivaFGNIndicatorFilterService.getAllIndicators();

        $scope.monthList = PositivaFGNIndicatorFilterService.getMonths();
        $scope.yearList = PositivaFGNIndicatorFilterService.getYears();

        $scope.filters = dataSource.filters;


        $scope.existsFilterByYear = function () {
            return $scope.yearList.some(function (year) {
                return year.isActive === year.period;
            });
        };

        var existsFilterByGroup = $scope.groupList.some(function (group) {
            return group.isActive === true;
        });

        var existsFilterByAxis = $scope.axisList.some(function (axis) {
            return axis.isActive === true;
        });


        $scope.status = {
            openDivision: true,
            openPeriods: $scope.existsFilterByYear(),
            openGroups: existsFilterByGroup,
            openAxis: existsFilterByAxis,
            openTipIndicator: true
        };

        var initialize = function() {
            $scope.entity = {
                division: PositivaFGNIndicatorFilterService.getDivision(),
                regional: null,
                sectional: null,
                periods: null,
                year: null,
                indicator: PositivaFGNIndicatorFilterService.filteredIndicator()
            };
        };

        initialize();

        $scope.onCancel = function() {
            $uibModalInstance.dismiss('cancel');
        };

        $scope.onFilter = function() {
            PositivaFGNIndicatorFilterService.setDivision($scope.entity.division);
            PositivaFGNIndicatorFilterService.setIndicator($scope.entity.indicator);
            $uibModalInstance.close(1);
        };


        $scope.onChangeDivisionRegional = function() {
            clearAllSectional();
        }

        $scope.onChangeDivisionSectional = function() {
            clearAllRegional();
        }

        function clearAllSectional() {
            $scope.sectionalList.map(function(s) {
                s.isActive = false;
            })
        }

        function clearAllRegional() {
            $scope.regionalList.map(function(s) {
                s.isActive = false;
            });
        }

        $scope.onChangeDivisionNational = function() {
            clearAllSectional();
            clearAllRegional();
        }


        $scope.allGroups = function() {
            $scope.groupList.map(function(item) {
                item.isActive = true;
            })
        }

        $scope.clearGroups = function() {
            $scope.groupList.map(function(item) {
                item.isActive = false;
            })
        }


        $scope.allAxis = function() {
            $scope.axisList.map(function(item) {
                item.isActive = true;
            })
        }

        $scope.clearAxis = function() {
            $scope.axisList.map(function(item) {
                item.isActive = false;
            })
        }


        function selectAllRegionals() {
            $scope.regionalList.map(function(item) {
                item.isActive = true;
            });
        }

        function selectAllSectional() {
            $scope.sectionalList.map(function(item) {
                item.isActive = true;
            })
        }

        $scope.allCurrentDivision = function() {
            if ($scope.entity.division === "regional") {
                selectAllRegionals();
            }

            if ($scope.entity.division === "sectional") {
                selectAllSectional();
            }
        }

        $scope.onClearDivisionSectional = function() {
            clearAllSectional();
            clearAllRegional();
        }


        $scope.clearMonths = function() {
            $scope.monthList.map(function(item) {
                item.isActive = false;
            });
        }


        $scope.onAllMonths = function() {
            if (!$scope.existsFilterByYear()) {
                return;
            }

            $scope.monthList.map(function(item) {
                item.isActive = true;
            })
        }


        $scope.onCheckMonth = function (month) {
            if (!$scope.existsFilterByYear()) {
                month.isActive = false;
            }
        };

    });