/** 
 * declare 'clip-two' module with dependencies
 */
'use strict';
app.factory('globalAppInterceptor', function (bsLoadingOverlayHttpInterceptorFactoryFactory) {
	return bsLoadingOverlayHttpInterceptorFactoryFactory({
		referenceId: 'global-app',
		requestsMatcher: function (requestConfig) {
			if (requestConfig.url.indexOf('api') !== -1) {
				//console.log('globalAppInterceptor', requestConfig.url);
			}
			return requestConfig.url.indexOf('api') !== -1;
		}
	});
});