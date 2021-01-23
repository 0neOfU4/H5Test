/**@ngInject*/
export default class MonitorMgrController {
  constructor(rpc) {
    let vm = this;
    vm.custom = [];
    vm.globalenabled = true;
    vm.dataver = 0;
    vm.mailurl = "";
    vm.newitem = newitem;
    vm.switch_buttons = switch_buttons;
    vm.save = save;
    vm.remove = remove;
    vm.add = add;
    vm.moveup = moveup;
    vm.movedown = movedown;
    vm.comparetype = [
      '<',
      '>',
      '='
    ];

    activate();

    function activate() {
      var sql = "select * from gm_global where name='monitor'";
      rpc.call("custom_dbgm", {sql: sql})
        .then(function (ret) {
          vm.dataver = ret[0].ver;
          var data = JSON.parse(decodeURI(ret[0].data));
          vm.custom = data.list;
          vm.globalenabled = data.globalenabled;
          vm.mailurl = data.mailurl;
          if (vm.custom == null)
            vm.custom = [];
        })
    }

    function newitem() {
      vm.custom.push({
        name: "item" + vm.custom.length,
        varname: "varname" + vm.custom.length,
        sql: "",
        comparetype: "<",
        value: 0,
        times: 1,
        interval: 1,
        enabled: true
      })
    }

    function save() {
      var custom = angular.copy(vm.custom);
      custom.forEach(e => e.$$showbutton = -1);
      var data = encodeURI(JSON.stringify({list: custom, globalenabled: vm.globalenabled, mailurl: vm.mailurl}));
      var sql = "update gm_global set ver=ver+1, data='" + data.replace(/'/g, "\\'") + "' where name='monitor' and ver='" + vm.dataver + "';select ROW_COUNT() as r;";
      rpc.call("custom_dbgm", {sql: sql})
        .then(function (ret) {
          if (ret[0].r == 0) {
            alert("保存失败，因为中途其他人编辑过，需要刷新页面重新修改重新保存");
            return;
          }

          vm.dataver++;
          if (rpc.getcustom != null)
            rpc.getcustom();
        })
    }

    function remove(x) {
      var index = vm.custom.indexOf(x);
      if (index > -1) {
        vm.custom.splice(index, 1);
      }
    }

    function add(item) {
      if (item == null) {
        item = ["自定义查询" + vm.custom.length, "", []];
        item.$$showbutton = 1;
        vm.custom.push(item);
      }
      else {
        item = angular.copy(item);
        item.$$showbutton = 1;
        item.name = item.name + " 的副本";
        vm.custom.push(item);
      }
    }

    function moveup(arr, item) {
      var index = arr.indexOf(item);
      if (index > -1 && index > 0) {
        var tmp = arr[index];
        arr[index] = arr[index - 1];
        arr[index - 1] = tmp;
        arr[index - 1].$$showbutton = -1;
      }
    }

    function movedown(arr, item) {
      var index = arr.indexOf(item);
      if (index > -1 && index < arr.length - 1) {
        var tmp = arr[index];
        arr[index] = arr[index + 1];
        arr[index + 1] = tmp;
        arr[index + 1].$$showbutton = -1;
      }
    }

    function switch_buttons(x) {
      if (x.$$showbutton == null)
        x.$$showbutton = 1;
      else
        x.$$showbutton *= -1;
    }

  }
}
