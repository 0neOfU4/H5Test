/**@ngInject*/
export default class CustomMgrController
{
  constructor(rpc) {
    let vm = this;
    vm.dataver = 0;
    vm.remove = remove;
    vm.add = add;
    vm.save = save;
    vm.addparam = addparam;
    vm.removeparam = removeparam;
    vm.moveup = moveup;
    vm.movetop = movetop;
    vm.movedown = movedown;
    vm.movebuttom = movebuttom;
    vm.switch_buttons = switch_buttons;
    vm.custom = [];
    vm.typelist = [
      '文本',
      '数字',
      '日期',
      '选项',
      '日期范围',
      '多选',
    ];

    activate();

    function activate() {
      var sql = "select * from gm_global where name='custom'";
      rpc.call("custom_dbgm", {sql: sql})
        .then(function(ret){
          vm.dataver = ret[0].ver;
          vm.custom = JSON.parse(decodeURI(ret[0].data));
        })
    }


    function remove(x)
    {
      var index = vm.custom.indexOf(x);
      if (index > -1) {
        vm.custom.splice(index, 1);
      }
    }

    function add(item)
    {
      if(item == null) {
        item =["自定义查询" + vm.custom.length, "", []];
        item.$$showbutton = 1;
        vm.custom.push(item);
      }
      else {
        item = angular.copy(item);
        item.$$showbutton = 1;
        item[0] = item[0] + " 的副本";
        vm.custom.push(item);
      }
    }

    function save()
    {
      var data = encodeURI(JSON.stringify(angular.copy(vm.custom)));
      var sql = "update gm_global set ver=ver+1, data='" + data.replace(/'/g, "\\'") + "' where name='custom' and ver='"+vm.dataver+"';select ROW_COUNT() as r;";
      rpc.call("custom_dbgm", {sql: sql})
        .then(function(ret){
          if(ret[0].r == 0)
          {
            alert("保存失败，因为中途其他人编辑过，需要刷新页面重新修改重新保存");
            return;
          }
          vm.dataver++;
          if(rpc.getcustom!=null)
            rpc.getcustom();
        })
    }

    function addparam(arr)
    {
      arr.push({name:"param" + arr.length, type:vm.typelist[0], sql:""});
    }

    function removeparam(arr, y)
    {
      var index = arr.indexOf(y);
      if (index > -1) {
        arr.splice(index, 1);
      }
    }

    function moveup(arr, item)
    {
      var index = arr.indexOf(item);
      if (index > -1 && index > 0) {
        var tmp = arr[index];
        arr[index] = arr[index - 1];
        arr[index - 1] = tmp;
        arr[index - 1].$$showbutton = -1;
      }
    }

    function movedown(arr, item)
    {
      var index = arr.indexOf(item);
      if (index > -1 && index < arr.length - 1) {
        var tmp = arr[index];
        arr[index] = arr[index + 1];
        arr[index + 1] = tmp;
        arr[index + 1].$$showbutton = -1;
      }
    }

    function movetop(arr, item)
    {
      while(arr.indexOf(item) > 0)
        moveup(arr, item);
    }

    function movebuttom(arr, item)
    {
      while(arr.indexOf(item) < arr.length - 1)
        movedown(arr, item);
    }

    function switch_buttons(x)
    {
      if(x.$$showbutton == null)
        x.$$showbutton = 1;
      else
        x.$$showbutton *= -1;
    }
  }
}
