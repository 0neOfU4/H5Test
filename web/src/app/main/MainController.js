/**@ngInject*/
export default class MainController
{
  constructor(rpc, $rootScope, $location)
  {
    var vm = this;
    vm.logout = logout;
    vm.custom = [];
    vm.opend_node = JSON.parse(localStorage.getItem("menu_opend_node") || "{}");
    vm.getcustom = getcustom;
    vm.menuWidth = 165;

    activate();

    function activate() {
      getcustom();
      rpc.getcustom = getcustom;
      autodivheight();
      window.onresize=autodivheight;
    }

    function autodivheight() {
      console.log("autodivheight");
      var winHeight = 0;
      if (window.innerHeight)
        winHeight = window.innerHeight;
      else if ((document.body) && (document.body.clientHeight))
        winHeight = document.body.clientHeight;
      if (document.documentElement && document.documentElement.clientHeight)
        winHeight = document.documentElement.clientHeight;
      winHeight -= 20;
      document.getElementById("menu").style.height = winHeight + "px";
      document.getElementById("txt").style.height = winHeight + "px";
      document.getElementById("txt").style.maxWidth = (window.innerWidth - vm.menuWidth - 20) + "px";
    }

    function getcustom() {
      var sql = "select * from gm_global where name='custom'";
      rpc.call("custom_dbgm", {sql: sql})
        .then(function (ret) {
          vm.custom = JSON.parse(decodeURI(ret[0].data));
          for (var i = 0; i < vm.custom.length; i++)
            vm.custom[i].$$index = i;

          if ($rootScope.userName != "admin")
            vm.custom = vm.custom.filter((item) => {
              return item[0].indexOf(".") != 0;
            });


          var newarr = [];
          var fixedpre = {};
          vm.custom.forEach(i => {
            delete(i[1]);
            delete(i[2]);
            delete(i[3]);
            delete(i[4]);
            var name = i[0];
            if (name.indexOf(">") > 0) {
              var pre = name.split(">")[0];
              if (fixedpre[pre] == null) {
                newarr.push({
                  pre: pre,
                  data: vm.custom.filter((item) => {
                    return item[0].split(">")[0] == pre;
                  })
                });
                fixedpre[pre] = 1;
              }
            }
            else {
              newarr.push(i);
            }
          });
          vm.custom = newarr;
        });
      setTimeout(autodivheight, 1);
    }

    function logout() {
      rpc.call("login", {type: 2})
        .then(function () {
          $rootScope.isLogin = 0;
          $location.path('/login');
        })
    }

    vm.isleaf = function(v){
      return v.pre == null;
    };

    vm.clicknode = function(x){
      var pre = x.pre;
      if(vm.opend_node[pre] == 1)
        vm.opend_node[pre] = 0;
      else
        vm.opend_node[pre] = 1;

      //console.log(vm.opend_node,JSON.stringify(vm.opend_node));
      localStorage.setItem("menu_opend_node", JSON.stringify(vm.opend_node));
    };

    vm.isopen = function(x)
    {
      var pre = x.pre;
      return vm.opend_node[pre] == 1;
    };

    vm.hidemenu = function(){
      if(vm.menuWidth == 165)
        vm.menuWidth = 0;
      else
        vm.menuWidth = 165;
      setTimeout(autodivheight, 1);
    };

    vm.getclass = (x)=> {
      var s = decodeURI($location.absUrl()).split("/");
      if (x[0].indexOf("/") >= 0) {
        return s.join("/").indexOf(x[0]) > 0 ? "menu_selected" : "";
      }
      else {
        if (s[s.length - 1] == x[0])
          return "menu_selected";
        else
          return "";
      }
    };

    vm.goto = (x) =>{
      window.location.href = "#!/main/custom/" + x[0];
    }
  }
}
