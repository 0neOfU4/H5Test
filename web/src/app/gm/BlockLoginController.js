/**@ngInject*/
export default class BlockLoginController
{
  constructor(rpc) {
    let vm = this;
    vm.pid = "";
    vm.tm = 3600;

    vm.batchBlockLogin = function (blockpid, tm, isdevice) {
      blockpid = blockpid.replace(/\n/g, "");
      blockpid = blockpid.trim();
      blockpid = blockpid.replace(/^,+/g, "");
      blockpid = blockpid.replace(/,+$/g, "");

      if (tm <= 0) {
        alert("时间不正确,必须大于0");
        return;
      }

      rpc.call("game", {method: "post", url: "/gm/player/checkpid", pid: blockpid})
        .then((ret) => {
          console.log(ret);
          if (ret.ret == 1) {
            var list = (blockpid + "").split(",");
            var count = 0;
            var count_fail = 0;
            var fail_pid = "";
            for (var i = 0; i < list.length; i++) {
              count++;
              var pid = list[i];
              var url = "/gm/player/blocklogin";
              if (isdevice == 1) {
                url = "/gm/player/blockdevice";
              }
              rpc.call("game", {method: "post", url: url, pid: pid, tm: tm})
                .then(function (response) {
                  if (response == "1")
                    ;
                  else {
                    fail_pid += pid + ",";
                    count_fail++;
                  }
                  count--;
                  if (count == 0) {
                    if (count_fail == 0)
                      alert("成功,共" + list.length + "个");
                    else {
                      alert("失败,共" + list.length + "个, 失败" + count_fail + "个. " + fail_pid);
                    }
                  }
                });
            }
          }
          else
            alert("pid错误:" + JSON.stringify(ret.errpid));
        });
    };
  }
}
