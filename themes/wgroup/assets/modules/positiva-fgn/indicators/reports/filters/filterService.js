'use strict';
/**
 * controller for Positiva FGN Filters
 */
app.factory('PositivaFGNIndicatorFilterService', function($rootScope, ListService, ModuleListService) {
    var dataFactory = {};

    var _division = null;
    var _regionals = [];
    var _sectionals = [];
    var _years = [];
    var _groups = [];
    var _axis = [];
    var _indicator = null;

    var monthList = [
        { item: "Enero", value: "01" },
        { item: "Febrero", value: "02" },
        { item: "Marzo", value: "03" },
        { item: "Abril", value: "04" },
        { item: "Mayo", value: "05" },
        { item: "Junio", value: "06" },
        { item: "Julio", value: "07" },
        { item: "Agosto", value: "08" },
        { item: "Septiembre", value: "09" },
        { item: "Octubre", value: "10" },
        { item: "Noviembre", value: "11" },
        { item: "Diciembre", value: "12" },
    ];

    dataFactory.setRegionals = function(regionals) {
        _regionals = regionals;
    };

    dataFactory.getRegionals = function() {
        return _regionals;
    };

    dataFactory.setSectionals = function(sectionals) {
        _sectionals = sectionals;
    };

    dataFactory.getSectionals = function() {
        return _sectionals;
    };


    dataFactory.setYears = function(years) {
        _years = years;
    };

    dataFactory.getYears = function() {
        return _years;
    };

    dataFactory.getMonths = function() {
        return monthList;
    }


    dataFactory.setGroups = function(groups) {
        _groups = groups;
    };

    dataFactory.getGroups = function() {
        if (_groups && _groups.length === 0) {
            _groups = $rootScope.parameters("positiva_fgn_activity_group");
        }

        return _groups;
    };


    dataFactory.setAxis = function(axis) {
        _axis = axis;
    };

    dataFactory.getAxis = function() {
        if (_axis && _axis.length === 0) {
            _axis = $rootScope.parameters("positiva_fgn_activity_axis");
        }

        return _axis;
    };

    dataFactory.setIndicator = function(indicator) {
        _indicator = indicator;
    }

    dataFactory.getAllIndicators = function() {
        return [
            { name: 'Cumplimiento', value: 'compliance' },
            { name: 'Cobertura', value: 'coverage' }
        ];
    }


    dataFactory.setDivision = function(division) {
        _division = division;
    };

    dataFactory.getDivision = function() {
        return _division || 'national';
    };


    // ***************  Filtered  *************
    dataFactory.filteredRegionals = function() {
        var filtered = [];
        _regionals.map(function(item) {
            if (item.isActive) {
                filtered.push(item.value);
            }
        });

        return filtered;
    }

    dataFactory.filteredSectionals = function() {
        var filtered = [];
        _sectionals.map(function(item) {
            if (item.isActive) {
                filtered.push(item.value);
            }
        });

        return filtered;
    }

    dataFactory.getPeriods = function() {
        var year = _years.find(function(item) {
            return item.isActive === item.period;
        })

        var months = monthList.filter(function(item) {
            return item.isActive === true;
        });

        if (!year || !months) {
            return [];
        }

        var periods = [];
        months.forEach(function(month) {
            periods.push(year.period.toString() + month.value);
        });

        return periods;
    }

    dataFactory.filteredGroups = function() {
        var filtered = [];
        _groups.map(function(item) {
            if (item.isActive) {
                filtered.push(item.value);
            }
        });

        return filtered;
    }

    dataFactory.filteredAxis = function() {
        var filtered = [];
        _axis.map(function(item) {
            if (item.isActive) {
                filtered.push(item.value);
            }
        });

        return filtered;
    }

    dataFactory.filteredIndicator = function() {
        return _indicator || 'compliance';
    }


    function getList() {
        var entities = [
            { name: 'positiva_fgn_consultant_sectional', criteria: { regionalId: null } },
            { name: 'positiva_fgn_all_regional_sectional', criteria: {} }
        ];

        ListService.getDataList(entities)
            .then(function(response) {
                _regionals = response.data.data.regionalList;
                _sectionals = response.data.data.sectionalList;
            }, function(error) {
                $scope.status = "Unable to load customer data: " + error.message;
            });
    }

    function getParams() {
        var entities = [
            { name: 'positiva_fgn_period_all', value: null },
        ];

        ModuleListService.getDataList("/positiva-fgn-fgn-management/config", entities)
            .then(function(response) {
                _years = response.data.result.positivaFgnPeriod;
            }, function(error) {
                $scope.status = 'Unable to load activity data: ' + error.message;
            });
    }

    getList();
    getParams();
    return dataFactory;
});