/**
 * @fileoverview Auth Javascript
 * @author exkazuu@gmail.com (Kazunori Sakamoto)
 */


/**
 * Auth Controller Javascript
 *
 * @param {string} Controller name
 * @param {function($scope, $http, NC3_URL)} Controller
 */
NetCommonsApp.controller('Auth',
    ['$scope', '$http', 'NC3_URL', function($scope, $http, NC3_URL) {

      /**
       * initialize
       *
       * @return {void}
       */
      $scope.initialize = function() {
        console.log('initialized');
      };

      $scope.test = function() {
        $scope.flashMessage('test', 'alert alert-danger', 5000);
      };
    }]);
