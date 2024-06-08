'use strict';

//Inject cpmtrollers to app for FINDideas
var controllers = angular.module('ideasApp.controllers', []);

//For main view on privated page
controllers.controller('membersController', membersController);

//for category search
controllers.controller('categoryController', categoryController);

//For tags search
controllers.controller('tagController', tagController);

// For results of searcher
controllers.controller('searchController', searchController);

//For creation of ideas
controllers.controller('ideasController', ideasController);

//For creation of post
controllers.controller('postController', postController);

// For cart shopping
controllers.controller('cartController', cartController);

//Detail of idea
controllers.controller('detailController', detailController);

// User controller ideas
controllers.controller('userIdeasController', userIdeasController);

controllers.controller('accountController', accountController);

controllers.controller('menuController', menuController);


controllers.controller('myIdeaController', myIdeaController);

controllers.controller('editsController', editsController);

controllers.controller('myFavoritesController', myFavoritesController);

controllers.controller('contactController', contactController);

controllers.controller('inboxController', inboxController);

controllers.controller('helperController', helperController);

controllers.controller('myOrdersIdeaController', myOrdersIdeaController);

controllers.controller('inboxAdminController', inboxAdminController);

controllers.controller('usersAdminController', usersAdminController);

controllers.controller('paramsAdminController', paramsAdminController);

controllers.controller('editsadmController', editsadmController);

controllers.controller('marketController', marketController);

controllers.controller('generalController', generalController);

controllers.controller('salesController', salesController);

controllers.controller('accountingController', accountingController);

