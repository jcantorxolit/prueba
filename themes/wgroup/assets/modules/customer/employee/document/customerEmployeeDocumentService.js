'use strict';
/**
 * controller for Customers
 */
app.factory('CustomerEmployeeDocumentService', function () {
        var dataFactory = {};

        var _documents = [];
        var _currentDocumentIndex = null;

        dataFactory.setDocuments = function (documents) {
            _currentDocumentIndex = 0
            _documents = documents;
        };

        dataFactory.getCurrentDocumentId = function () {
            return _documents[_currentDocumentIndex];
        };

        dataFactory.isFirstDocument = function () {
            return _currentDocumentIndex == 0;
        };

        dataFactory.isLastDocument = function () {
            return _currentDocumentIndex == (_documents.length - 1);
        };

        dataFactory.goTo = function (index) {
            _currentDocumentIndex = index - 1;
            return _documents[_currentDocumentIndex];
        };

        dataFactory.getNextDocumentId = function () {
            _currentDocumentIndex += 1;
            return _documents[_currentDocumentIndex];
        };

        dataFactory.getPreviousDocumentId = function () {
            _currentDocumentIndex -= 1;
            return _documents[_currentDocumentIndex];
        };

        dataFactory.getCurrentDocumentOrdinal = function () {
            return _currentDocumentIndex + 1;
        };

        dataFactory.getTotalDocuments = function () {
            return _documents.length;
        };

        return dataFactory;
    }
);
