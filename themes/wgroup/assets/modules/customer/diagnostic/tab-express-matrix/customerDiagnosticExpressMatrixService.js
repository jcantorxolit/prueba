'use strict';
/**
 * controller for Customers
 */
app.factory('ExpressMatrixService', function () {              
        var dataFactory = {};

        var _workplaceId = null;
        var _isBackInNavigation = null;
        var _shouldOnCreateNewWorkplace = null;
       
        dataFactory.setWorkplaceId = function (workplaceId) {
            _workplaceId = workplaceId;
        };

        dataFactory.getWorkplaceId = function () {
            return _workplaceId;
        };


        dataFactory.setIsBackInNavigation = function (isBackInNavigation) {
            _isBackInNavigation = isBackInNavigation;
        };

        dataFactory.getIsBackInNavigation = function () {
            return _workplaceId;
        };


        dataFactory.setShouldCreateNewWorkplace = function (shouldOnCreateNewWorkplace) {
            _shouldOnCreateNewWorkplace = shouldOnCreateNewWorkplace;
        };

        dataFactory.getShouldCreateNewWorkplace = function () {
            return _shouldOnCreateNewWorkplace;
        };

        return dataFactory;
    }
);