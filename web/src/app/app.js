import angular from 'angular';
import uiRouter from 'angular-ui-router';
import datePicker from 'angular-daterangepicker';
import dropdownMulti from 'angularjs-dropdown-multiselect';
import uiBootstrap from 'angular-ui-bootstrap';
require('angular-ui-select/select.js');
require('angular-ui-select/select.css');

console.log("init app");

let app = () => {
  return {
    template: require('./app.html'),
    controller: 'AppCtrl',
    controllerAs: 'vm'
  }
};

/**@ngInject*/
class AppCtrl {
  constructor($location, $log) {
    $log.info("app init", $location.url());
    let vm = this;
  }
}

angular.module('web', [uiRouter, datePicker, 'angularjs-dropdown-multiselect', 'ui.bootstrap', 'ui.select'])
  .directive('app', app)
  .config(require('./route.js')["default"])

  .service("rpc", require("./util/RpcService")["default"])

  .controller('AppCtrl', AppCtrl)
  .controller('MainController', require('./main/MainController')["default"])
  .controller('LoginController', require('./login/LoginController')["default"])
  .controller('CustomController', require('./business/CustomController')["default"])
  .controller('CustomMgrController', require('./business/CustomMgrController')["default"])
  .controller('MonitorMgrController', require('./business/MonitorMgrController')["default"])
  .controller('MonitorController', require('./business/MonitorController')["default"])
  .controller('RepassController', require('./login/RepassController')["default"])
  .controller('PlayerController', require('./gm/PlayerController')["default"])
  .controller('SystemMailController', require('./gm/SystemMailController')["default"])
  .controller('SystemNoticeController', require('./gm/SystemNoticeController')["default"])
  .controller('BlockLoginController', require('./gm/BlockLoginController')["default"])

  .directive('datepicker', require('./util/DatePickerDirective')["default"])
  .constant('dateRangePickerConfig', {
    clearLabel: 'Clear',
    locale: {
      separator: '~',
      format: 'YYYY-MM-DD'
    }
  })
  .filter('trust', ['$sce', function ($sce) {
    return function (text) {
      return $sce.trustAsHtml(text);
    }
  }])

;

export default 'web';
