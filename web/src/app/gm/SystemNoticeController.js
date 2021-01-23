/**@ngInject*/
export default class SystemNoticeController
{
  constructor(rpc) {
    let vm = this;
    vm.list = [];
    vm.cols = [];
    vm.enum_yesno = [
      {id: 1, txt: "是"},
      {id: 0, txt: "否"},
    ];

    vm.newitem = {
      tm_from: moment().format('YYYY-MM-DD HH:mm:ss'),
      tm_to: moment().add(30, 'm').format('YYYY-MM-DD HH:mm:ss'),
      show_time: 15,
      in_battle: 0,
      interval: 20,
      txtdesc: "系统将于" + moment().add(30, 'm').format('YYYY-MM-DD HH:mm:00') + "开始维护,预计时间30分钟",
    };

    vm.get = get;
    get();

    function get(){
      rpc.call("game", {method: "post", url: "/gm/server/notice", type: "get"})
        .then((ret) => {
          vm.list = ret;
        });
    }

    vm.add = function()
    {

      rpc.call("game", {method: "post", url: "/gm/server/notice", type: "add", data:vm.newitem})
        .then((ret) => {
          vm.get();
        });
    };

    vm.del = function(x)
    {
      rpc.call("game", {method: "post", url: "/gm/server/notice", type: "del", id:x.id})
        .then((ret) => {
          vm.get();
        });
    };




  }
}
