/**@ngInject*/
export default class LoginController
{
  constructor($location, rpc, $log, $rootScope) {
    let vm = this;

    vm.login_failed = false;
    vm.reason = "";
    vm.onLogin = onLogin;

    activate();

    function activate(){
    }

    function onLogin(username, password){
      $rootScope.userName = "";
      rpc.call("login", {type:1, name: username, pass:password})
        .then(function(res){
          if(res.result == 1) {
            vm.login_failed = false;
            vm.reason = "";
            //$log.info(res);
            $rootScope.isLogin = 1;
            $rootScope.userName = res.name;

            console.log("jump", $rootScope.jump);
            if($rootScope.jump != null) {
              $location.path($rootScope.jump);
              $rootScope.jump = null;
            }
            else
              $location.path('/main');
          }
          else if(res.result == 2){
            vm.login_failed = true;
            vm.reason = " 找不到用户"
          }
          else if(res.result == 3){
            vm.login_failed = true;
            vm.reason = " 密码错"
          }
          else
          {
            vm.login_failed = true;
            vm.reason = ""
          }
        })
        .catch(function(){
          vm.login_failed = true;
        })
    }
  }
}
