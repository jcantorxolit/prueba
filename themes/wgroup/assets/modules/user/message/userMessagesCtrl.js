'use strict';
/** 
  * controller for Messages
*/
app.controller('userMessagesCtrl', ["$scope", "$state", '$rootScope', '$http', '$timeout',
    function ($scope, $state, $rootScope, $http, $timeout) {
    $scope.noAvatarImg = "assets/images/default-user.png";
    $scope.filters = null;
    /*$scope.messages = [{
        "from": "John Stark",
        "date": 1400956671914,
        "subject": "Reference Request - Nicole Bell",
        "email": "stark@example.com",
        "avatar": "assets/images/avatar-6.jpg",
        "starred": false,
        "sent": false,
        "spam": false,
        "content": "<p>Hi Peter, <br>Thanks for the e-mail. It is always nice to hear from people, especially from you, Scott.</p> <p>I have not got any reply, a positive or negative one, from Seibido yet.<br>Let's wait and hope that it will make a BOOK.</p> <p>Have you finished your paperwork for Kaken and writing academic articles?<br>If you have some free time in the near future, I want to meet you and explain to you our next project.</p> <p>Why not drink out in Hiroshima if we are accepted?<br>We need to celebrate ourselves, don't we?<br>Let's have a small end-of-the-year party!</p> <p>Sincerely, K. Nakagawa</p>",
        "id": 50223456
    }];*/

    $scope.onLoad = function () {

        $http({
            method: 'POST',
            url: 'api/user-message',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        })
            .catch(function (response) {
    
            })
            .then(function (response) {

                $timeout(function () {
                    $scope.messages = response.data.data;    

                    /*$scope.messages = [{
                        "from": "John Stark",
                        "date": 1400956671914,
                        "subject": "Reference Request - Nicole Bell",
                        "email": "stark@example.com",
                        "avatar": "assets/images/avatar-6.jpg",
                        "starred": false,
                        "sent": false,
                        "spam": false,
                        "content": "<p>Hi Peter, <br>Thanks for the e-mail. It is always nice to hear from people, especially from you, Scott.</p> <p>I have not got any reply, a positive or negative one, from Seibido yet.<br>Let's wait and hope that it will make a BOOK.</p> <p>Have you finished your paperwork for Kaken and writing academic articles?<br>If you have some free time in the near future, I want to meet you and explain to you our next project.</p> <p>Why not drink out in Hiroshima if we are accepted?<br>We need to celebrate ourselves, don't we?<br>Let's have a small end-of-the-year party!</p> <p>Sincerely, K. Nakagawa</p>",
                        "id": 50223456
                    }];        */            
                });

            }).finally(function () {

            });
        
    }

    $scope.onLoad();

}]);

app.controller('ViewMessageCrtl', ['$scope', '$stateParams', '$http', '$timeout',
function ($scope, $stateParams, $http, $timeout) {

	function getById(arr, id) {
		for (var d = 0, len = arr.length; d < len; d += 1) {
			if (arr[d].id == id) {

				return arr[d];
			}
		}
	}
        
    $scope.onLoadRecord = function () {
        if ($stateParams.inboxID != 0) {                
            var req = {
                id: $stateParams.inboxID
            };
            $http({
                method: 'GET',
                url: 'api/user-message/get',
                params: req
            })
                .catch(function (response) {
                })
                .then(function (response) {

                    $timeout(function () {
                        $scope.message = response.data.result;                        
                    });

                }).finally(function () {

                });
        }
    }

    if ($scope.messages && $scope.messages.length > 0) {
        $scope.message = getById($scope.messages, $stateParams.inboxID);
        if (!$scope.message || !$scope.message.isReaded) {
            $scope.onLoadRecord();
        }
    } else {
        $scope.onLoadRecord();
    }

}]);