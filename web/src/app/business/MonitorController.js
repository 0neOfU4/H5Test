/**@ngInject*/
export default class MonitorController
{
  constructor(rpc) {
    let vm = this;
    vm.custom = [];
    vm.eval = eval;
    activate();

    function activate() {
      var sql = "select * from gm_global where name='monitor'";
      rpc.call("custom_dbgm", {sql: sql})
        .then(function(ret){
          var data = JSON.parse(decodeURI(ret[0].data));
          vm.custom = data.list;
          if(vm.custom == null)
            vm.custom = [];

          gethistory();
        })
    }


    function gethistory()
    {
      vm.custom.forEach(function (t) {
        console.log(t);
        var sql = "select tm, value, comment from gm_monitor_history_log where name='" + t.varname + "' order by tm desc limit 20";
        rpc.call("custom_dbgm", {sql: sql})
        .then(function(ret){
          t.history = ret;
        })
      });
    }
  }
}
