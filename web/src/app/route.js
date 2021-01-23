
function afterlogin(url, path, controller){
  return {
    url: url,
    template: path,
    controller: controller,
    controllerAs: 'vm',
    resolve: getResove()
  }
}

function beforelogin(url, path, controller){
  return {
    url: url,
    template: path,
    controller: controller,
    controllerAs: 'vm'
  }
}

function getResove() {
  return({
    /** @ngInject */
    tmp: function($q, $timeout, $rootScope, rpc, $location, $log){
      var deferred = $q.defer();

      if($rootScope.isLogin == 1){
        $timeout(function () {
          //$log.warn("login resol");
          deferred.resolve();
        }, 1);
      }
      else
      {
        rpc.call("login", {type:3})
          .then(function(res){
            if(res.result == 1)
            {
              $rootScope.isLogin = 1;
              $rootScope.userName = res.name;
              deferred.resolve();
            }
            else{
              deferred.reject();
              $rootScope.jump = $location.path();
              $location.path('/login');
            }
          })
          .catch(function(){
            document.write("post login php failed.");
          })
      }

      return deferred.promise;
    }
  })
}

/**@ngInject*/
function routeConfig($stateProvider, $urlRouterProvider) {
  $stateProvider
    //登录前
    .state('login',                 beforelogin('/login',          require('./login/login.html'),               'LoginController'))
    //登录后
    .state('main',                  afterlogin('/main',            require('./main/main.html'),                 'MainController'))
    .state('main.custom',           afterlogin('/custom/:id',      require('./business/custom.html'),           'CustomController'))
    .state('main.custommgr',        afterlogin('/custommgr',       require('./business/custommgr.html'),        'CustomMgrController'))
    .state('main.monitormgr',       afterlogin('/monitormgr',      require('./business/monitormgr.html'),       'MonitorMgrController'))
    .state('main.monitor',          afterlogin('/monitor',         require('./business/monitor.html'),          'MonitorController'))
    .state('main.repass',           afterlogin('/repass',          require('./login/repass.html'),              'RepassController'))
    .state('main.systemmail',       afterlogin('/systemmail/:type/:pid', require('./gm/systemmail.html'),       'SystemMailController'))
    .state('main.systemnotice',     afterlogin('/systemnotice',    require('./gm/systemnotice.html'),           'SystemNoticeController'))
    .state('main.blocklogin',       afterlogin('/blocklogin',      require('./gm/blocklogin.html'),             'BlockLoginController'))
    .state('main.player',           afterlogin('/player',          require('./gm/player.html'),                 'PlayerController'))
  ;

  $urlRouterProvider.otherwise(
    function($injector, $location) {
      $location.path('/main');
    });
}

export default routeConfig
