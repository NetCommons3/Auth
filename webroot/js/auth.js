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
      $scope.initialize = function(formId) {
        var $form = $('#' + formId);
        var $loading = $('#loading');

        $form.submit(function(event) {
          event.preventDefault();
          $loading.removeClass('ng-hide');

          var $inputs = $form.find('input');
          var data = {};
          for (var i = 0; i < $inputs.length; i++) {
            var name = $inputs[i].getAttribute('name');
            var parts = name.split(/[\[\]]+/).filter(s => s);
            if (parts.length > 1) {
              var lastObj = data;
              var j = 1;
              for (; j < parts.length - 1; j++) {
                var part = parts[j];
                lastObj[part] = lastObj[part] || {};
                lastObj = lastObj[part];
              }
              lastObj[parts[j]] = $inputs[i].value;
            }
          }
          $http.post(
            NC3_URL + '/auth_general/auth_general/login',
            $.param({_method: 'POST', data: data}),
            {
              cache: false,
              headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }
          ).then(
            function() {
              window.location.href = '/';
            },
            function(response) {
              var data = response.data;
              $scope.flashMessage(data.message, 'alert alert-' + data.class, data.interval);
              $scope.updateTokens(true);
            });

          return false;
        });
      };
    }]);
