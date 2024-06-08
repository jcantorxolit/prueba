'use strict';
/**
 * controller for Customers
 */
app.factory('customerVrEmployeeService', function () {              
        var dataFactory = {};

        var _id = null;
        var _entity = null;
        var _entityExperience = null;
       
        dataFactory.setId = function (id) {
            _id = id;
        };

        dataFactory.getId = function () {
            return _id;
        };

        dataFactory.setEntity = function (entity) {
            _entity = entity;
        };

        dataFactory.getEntity = function () {
            return _entity;
        }

        dataFactory.setEntityExperience = function (entityExperience) {
            _entityExperience = entityExperience;
        };

        dataFactory.getEntityExperience = function () {
            return _entityExperience;
        };
    

        return dataFactory;
    }
);