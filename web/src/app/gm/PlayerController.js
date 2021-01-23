/**@ngInject*/
export default class PlayerController
{
  constructor(rpc, $uibModal, $scope, $location, $state) {
    let vm = this;
    vm.pid = localStorage.getItem("searchpid") || "1774601";
    vm.nick = "";
    vm.playerinfo = {};
    vm.init = init;
    vm.conv = rpc.conv;

    vm.blockchat = 0;
    vm.blocklogin = 0;
    vm.blockdevice = 0;

    var customdt = [
      "", "pid", "nick", "name", "money", "gold", "rankinfo", "clientinfo",
      "items", "items_count_cache", "items_count_limit"
    ];
    var customdtk = {};
    for (var i = 0; i < customdt.length; i++)
      customdtk[customdt[i]] = i;

    init();

    function init() {

    }

    vm.search = function () {
      if (vm.pid == "")
        return;

      vm.blockchat = 0;
      vm.blocklogin = 0;
      vm.blockdevice = 0;

      rpc.call("game", {method:"get", url:"/gm/player/getinfo?pid=" + vm.pid})
        .then((ret)=>{
          localStorage.setItem("searchpid", vm.pid);

          var data = [];
          Object.keys(ret).forEach(function (k) {
            data.push({k: k, v: ret[k]})
          });

          if (data.length == 0)
            alert("找不到数据");

          data.sort(function (a, b) {
            var na = customdtk[a.k] || 1000;
            var nb = customdtk[b.k] || 1000;
            if (na == nb)
              return a.k.localeCompare(b.k);
            else
              return na - nb;
          });
          vm.playerinfo = ret;
          vm.playerinfolist = data;

          rpc.call("game", {method:"get", url:"/gm/player/getblock?pid=" + vm.playerinfo.pid})
            .then(function (response) {
              console.log(response);
              vm.blockchat = response.blockchat;
              vm.blocklogin = response.blocklogin;
              vm.blockdevice = response.blockdevice;
            });

        });
    };

    vm.sendMail = function() {
      if (vm.playerinfo.pid == "")
        return;

      console.log("xxx");
      $state.go('main.systemmail', {type: 2, pid: vm.playerinfo.pid});
    };

    vm.blockChat = function (pid) {
      var tm = prompt("给" + pid + "禁言多久(秒)?", "3600");
      if(tm && tm > 0) {
        rpc.call("game", {method:"post", url:"/gm/player/blockchat", pid: pid, tm: tm})
          .then(function (response) {
            if (response == "1")
              alert("操作成功!");
            else
              alert("操作失败");
            vm.search();
          });
      }
    };

    vm.blockLogin = function (pid) {
      var tm = prompt("给" + pid + "封号多久(秒)?", "3600");
      if (tm && tm > 0) {
        rpc.call("game", {method:"post", url:"/gm/player/blocklogin", pid: pid, tm: tm})
          .then(function (response) {
            if (response == "1")
              alert("操作成功!");
            else
              alert("操作失败");
            vm.search();
          });
      }
    };

    vm.blockDevice = function (pid) {
      var tm = prompt("给" + pid + "封设备多久(秒)?", "3600");
      if (tm && tm > 0) {
        rpc.call("game", {method:"post", url:"/gm/player/blockdevice", pid: pid, tm: tm})
          .then(function (response) {
            if (response == "1")
              alert("操作成功!");
            else
              alert("操作失败");
            vm.search();
          });
      }
    };
  }
}
