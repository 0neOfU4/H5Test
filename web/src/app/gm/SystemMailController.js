/**@ngInject*/
export default class SystemMailController
{
  constructor(rpc, $stateParams) {
    let vm = this;
    vm.list = [];
    vm.itemlist = [];
    vm.mail_title = "系统奖励";
    vm.mail_body = "金币x100,请笑纳";
    vm.mail_ext = [{id:3,count:100}];
    vm.type = 1;    //1=全服 2=单人
    activate();

    function activate() {
      rpc.call("game", {method:"get", url:"/gm/player/getitemlist"})
        .then((ret)=>{
          vm.itemlist = ret;
        });
      vm.type = $stateParams.type;
      vm.pid = $stateParams.pid;
    }


    vm.getItemTitle = function(id){
      var item = vm.itemlist.filter((i) => {return i.id== id})[0];
      return item.id + "--" + item.type + "--" + item.name;
    };

    vm.removeext = function (x) {
      var index = vm.mail_ext.indexOf(x);
      if (index > -1) {
        vm.mail_ext.splice(index, 1);
      }
    };

    vm.addext = function () {
      vm.mail_ext.push({id:3,count:100})
    };


    vm.toExtStr = function(obj) {
      var j = JSON.stringify(angular.copy(obj));
      j = j.replace(/\[/g, "{");
      j = j.replace(/\]/g, "}");
      j = j.replace(/"/g, "");
      j = j.replace(/:/g, "=");
      return j;
    };

    vm.checkPid = function(pid) {
      rpc.call("game", {method:"post", url:"/gm/player/checkpid", pid: pid})
        .then((ret)=>{
          console.log(ret);
          if (ret.ret == 1) {
            alert("pid正确");
          }
          else
            alert("pid错误:" + JSON.stringify(ret.errpid));
        });
    };

    vm.get = get;
    get();

    function get(){
      rpc.call("game", {method: "post", url: "/gm/server/mail", type: "get"})
        .then((ret) => {
          vm.list = ret;
        });
    }

    vm.addMail = function (pid, mail_title, mail_body, mail_ext) {
      if (mail_title == "" || mail_body == "" || mail_ext == "" || pid == "") {
        alert("请填写完整!");
        return;
      }

      if (vm.type == 1) {     //全服邮件
        if(prompt("全服邮件一旦发送不可撤销,为防止误操作,请输入'全服邮件'4个字：","") != "全服邮件") return;
        rpc.call("game", {method: "post", url: "/gm/server/mail", type: "add", data:{title:mail_title, body:mail_body, ext:vm.toExtStr(mail_ext)}})
          .then((ret) => {
            vm.get();
          });
      }
      else {                  //单人邮件
        rpc.call("game", {
          method: "post",
          url: "/gm/player/sendmail",
          pid: pid,
          title: mail_title,
          body: mail_body,
          ext: vm.toExtStr(mail_ext)
        })
          .then((ret) => {
            console.log(ret);
            if (ret[0] == "1") {
              alert("操作成功!");
            }
            else if (ret[0] == "-2")
              alert("操作失败!玩家id不存在" + JSON.stringify(ret[1]));
            else if (ret[0] == "-1")
              alert("操作失败!附件内包含不存在道具");
            else
              alert("操作失败");
          });
      }
    };

  }
}
