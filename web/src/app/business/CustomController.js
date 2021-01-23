/**@ngInject*/
export default class CustomController {
  constructor(rpc, $timeout, $filter, $stateParams, $scope, $interval, $rootScope) {
    let vm = this;
    vm.query = query;
    vm.delayquery = delayquery;
    vm.querying = false;
    vm.col_show = [];
    vm.col_name = [];
    vm.query_history = [];
    vm.orginal_args = {};
    vm.orginal_data = [];
    vm.table_data = [];
    vm.tab_result = [];   //导出剪切板用的数据结构
    vm.defaultDate = $filter('date')(new Date(), 'yyyy-MM-dd');
    vm.onchange = onchange;
    vm.loadingcount = 0;
    vm.colname = "";
    vm.colname2 = "";
    vm.desc = 1;
    vm.desc2 = 1;
    vm.onclick = onclick;
    vm.readycols = [];
    vm.sortfunc = sortfunc;
    vm.sortfunc2 = sortfunc2;
    vm.getready = getready;
    vm.getallready = getallready;
    vm.checker = null;
    vm.last_select = {};
    vm.dosql = rpc.call;
    vm.current_check = true;
    activate();

    vm.stringTranslate = {
      buttonDefaultText: "请选择",
      checkAll: "全选",
      uncheckAll: "取消全选",
    };
    vm.stringSettings = {
      smartButtonTextConverter(itemText, originalItem) {
        return itemText;
      },
      smartButtonMaxItems: 3
    };

    function activate() {
      var sql = "select * from gm_global where name='custom'";
      rpc.call("custom_dbgm", {sql: sql})
        .then(function (ret) {
          var customdata = JSON.parse(decodeURI(ret[0].data));
          var title = $stateParams.id;
          vm.select = customdata.filter(function (v) {
            return v[0] == title;
          })[0];
          var arr = vm.select[2];
          arr.forEach(function (params) {
            vm.readycols[params.name] = 0;
          });
          //console.log("vm.readycols", vm.readycols);
          arr.forEach(function (params) {
            var s = params.sql + params.default;

            if (params.type == "日期范围") {
              params.input = {startDate: null, endDate: null};
            }

            if (!containsParams(s))
              loadDefault(params);
          });
          vm.onchange();
          startcheck();
        });
    }

    function startcheck() {
      vm.checker = $interval(() => {
        //console.log("cc");
        var arr = vm.select[2];
        Object.keys(arr).forEach((k) => {
          var p = arr[k];
          if (p.type == "日期范围" || p.type == "多选") {
            var last = vm.last_select[k];
            if (last != null && JSON.stringify(last.input) != JSON.stringify(angular.copy(p.input))) {
              //console.log("check change", p);
              vm.onchange(p);
            }
            vm.last_select[k] = angular.copy(p);
          }
        });
      }, 100);
    }

    function containsParams(str) {
      var find = false;
      Object.keys(vm.readycols).forEach(function (k) {
        if (str.indexOf("{" + k + "}") >= 0)
          find = true;
      });
      return find;
    }

    function loadDefault(params) {
      if ($rootScope.userName == "admin")
        console.log("load", params.name);
      if (params.type == "选项") {
        vm.loadingcount++;
        vm.readycols[params.name] = 0;
        rpc.call("custom", {sql: getSql(params.sql, false)}, false)
          .then(function (ret) {
            vm.loadingcount--;
            var options = [];
            ret.forEach(function (t) {
              var c = 0;
              for (var k in t) {
                t[c++] = t[k];
              }
              if (c == 1) t[1] = t[0];
              options.push([t[0], t[1]]);
            });
            params.options = options;
            params.input = options[0];
            vm.readycols[params.name] = 1;
            vm.onchange(params);
          })
      }
      if (params.type == "多选") {
        vm.loadingcount++;
        vm.readycols[params.name] = 0;
        rpc.call("custom", {sql: getSql(params.default)}, false)
          .then(function (ret) {
            vm.loadingcount--;
            var options = [];
            ret.forEach(function (t) {
              var c = 0;
              for (var k in t) {
                t[c++] = t[k];
              }
              if (c == 1) t[1] = t[0];
              options.push({id: t[1], label: t[0]});
            });
            params.options = options;
            params.input = [options[0]];
            vm.readycols[params.name] = 1;
            vm.onchange(params);
          })
      }
      else {
        if (params.default != null && params.default != "") {
          vm.loadingcount++;
          vm.readycols[params.name] = 0;


          let onresult = (result) => {
            vm.loadingcount--;
            if (params.type == "日期")
              params.input = $filter('date')(new Date(result), 'yyyy-MM-dd');
            else if (params.type == "日期范围") {
              var arr = result.split("~");
              params.input = {startDate: new Date(arr[0]), endDate: new Date(arr[1]),};
            }
            else if (params.type == "数字")
              params.input = parseInt(result);
            else
              params.input = result;
            vm.readycols[params.name] = 1;
            vm.onchange(params);
          };

          if (params.default.indexOf("javascript:") == 0) {
            $timeout(() => {
              var script = params.default.substr(11);
              script = getSql(script);
              if ($rootScope.userName == "admin")
                console.log("script:", script);
              var result = window.eval(script);
              onresult(result == null ? "" : result);
            }, 1)
          }
          else {
            rpc.call("custom", {sql: getSql(params.default)}, false)
              .then(function (ret) {

                var options = [];
                ret.forEach(function (t) {
                  for (var k in t) {
                    options.push(t[k]);
                  }
                });
                onresult(options[0]);
              })
          }
        }
      }
    }

    function getready(params) {
      return vm.readycols[params.name] == 1;
    }

    function getallready() {
      var ret = true;
      Object.keys(vm.readycols).forEach(function (k) {
        if (vm.readycols[k] == 0)
          ret = false;
      });
      return ret;
    }

    function onchange(item) {
      if (item != null) {
        if ($rootScope.userName == "admin")
          console.log("onchange", item.name);
        var arr = vm.select[2];
        arr.forEach(function (params) {
          var sql = params.sql + params.default;
          if (sql.indexOf("{" + item.name + "}") > 0) {
            var skip = 0;
            Object.keys(vm.readycols).forEach(function (k) {
              if (vm.readycols[k] == 0 && sql.indexOf("{" + k + "}") > 0) skip = 1;
            });
            if (skip == 0)
              loadDefault(params);
          }
        });
      }
      if (vm.loadingcount == 0) {
        vm.loadingcount = -10000;
        if (vm.select[4])
          $timeout(query, 1);
      }
    }

    function getSql(sql, save_param) {
      var args = {};
      var human_args = {};
      vm.select[2].forEach(function (p) {
        var human_str = "";
        if (p.type == "日期范围") {
          args[p.name] = moment(p.input.startDate).format('YYYY-MM-DD') + "~" + moment(p.input.endDate).format('YYYY-MM-DD');
          human_str = args[p.name];
        }
        else if (p.type == "多选") {
          var tmp = "";
          var tmp2 = "";
          if (p.input != null) {
            p.input.forEach(d => tmp += d.id + ",");
            tmp = tmp.substr(0, tmp.length - 1);
            p.input.forEach(d => tmp2 += d.label + ",");
            tmp2 = tmp2.substr(0, tmp2.length - 1);
          }
          args[p.name] = tmp;
          human_str = tmp2;
        }
        else if (typeof(p.input) == "object" && p.input[1] != null) {
          args[p.name] = p.input[1];
          human_str = p.input[0];
        }
        else {
          args[p.name] = p.input;
          human_str = p.input;
        }
        if (!p.hide)
          human_args[p.name] = human_str;
      });
      if (save_param)
        vm.orginal_args = human_args;
      return rpc.format(sql, args)
    }

    function delayquery() {
      $timeout(query, 1);
    }

    function query() {
      vm.querying = true;
      if (!getallready()) {
        console.log("not ready, wait");
        $timeout(query, 100);
        return;
      }

      rpc.call("custom", {
        sql: getSql(vm.select[1], true)  //"select * from log_device_login limit 10"
      })
        .then(function (ret) {
          vm.querying = false;
          vm.orginal_data = ret;
          onQueryResult(vm.orginal_data);
          vm.query_history.forEach(x => x.check = false);
        })
    }

    vm.onSaveHistory = onSaveHistory;
    vm.seed = 1;

    function onSaveHistory() {
      if (isSaved()) {
        return;
      }
      var name = "查询" + (vm.seed++);
      //name = prompt("输入查询名称", name);
      if (name != null && name != "")
        vm.query_history.push({name: name, data: vm.orginal_data, args: vm.orginal_args, check: false});

      if (vm.seed == 11) {
        vm.query_history.forEach((r) => {
          for (var i = 1; i <= 9; i++)
            if (r.name == "查询" + i)
              r.name = "查询0" + i;
        });
      }

      vm.query_history.sort((a, b) => {
        return a.name.localeCompare(b.name);
      });
    }

    vm.isSaved = isSaved;
    function isSaved() {
      var find = false;
      vm.query_history.forEach(item =>{
        var same = true;
        Object.keys(vm.orginal_args).forEach(k =>{
          if(vm.orginal_args[k] + "" != item.args[k] + "") same = false;
        });
        if (same)
          find = true;
      });
      return find;
    }

    vm.renameQuery = renameQuery;
    function renameQuery(x) {
      var index = vm.query_history.indexOf(x);
      if (index > -1) {
        var name = prompt("输入查询名称", vm.query_history[index].name);
        if (name == null) return;
        vm.query_history[index].name = name;
        vm.query_history.sort((a, b) => {
          return a.name.localeCompare(b.name);
        });
        mergeQuery();
      }
    }

    vm.removeQuery = removeQuery;
    function removeQuery(x) {
      var index = vm.query_history.indexOf(x);
      if (index > -1) {
        vm.query_history.splice(index, 1);
        mergeQuery();
      }
    }

    vm.autoSelect = function(t){
      vm.query_history.forEach((i) =>{
        if(t == 1)
          i.check = true;
        else
          i.check = !i.check;
      })
      vm.mergeQuery();
    };

    vm.mergeQuery = mergeQuery;
    function mergeQuery() {
      setTimeout(_mergeQuery, 1);
    }

    function _mergeQuery() {
      var list = [{name: "当前", data: vm.orginal_data, args: vm.orginal_args}];
      if (!vm.current_check) list = [];
      vm.query_history.forEach(x => {
        if (x.check)
          list.push(x)
      });
      list = angular.copy(list);

      //对比多个查询 仅保留不同的参数
      Object.keys(vm.orginal_args).forEach(k => {
        var same = true;
        list.forEach(i => {
          if (i.args[k] != vm.orginal_args[k]) same = false;
        });
        if (same)
          list.forEach(i => {
            delete(i.args[k]);
          });
      });

      //统计所有结果集所有的key
      var keys = {};
      list.forEach(x => {
        x.data.forEach(r => {
          Object.keys(r).forEach(k => {
            keys[k] = 1;
          })
        });
      });

      //补结果集列,补所有其他列
      var newquery = [];
      list.forEach(x => {
        x.data.forEach(r => {
          var n = {};
          if (list.length > 1) {      //补对比列
            n["<结果集>"] = x.name;
            Object.keys(x.args).forEach(k => {
              n["<" + k + ">"] = x.args[k];
            });
          }
          Object.keys(keys).forEach(k => {
            n[k] = r[k] || "";
          });
          newquery.push(n);
        });
      });
      onQueryResult(newquery);
    }

    vm.getResultSetBg = getResultSetBg;
    vm.rsbg = [];
    vm.tmp = [];
    function getResultSetBg(r, index) {
      var setname = r[vm.col_name["<结果集>"]];
      if (setname == null) return "";
      var colors = [
        "#000000",
        "#008dff",
        "#a52197",
        "#6f4e68",
        "#009d0e",
        "#ff7978",
      ];
      if (vm.rsbg.indexOf(setname) == -1) {
        vm.rsbg.push(setname);
      }
      var ret = "color:" + colors[vm.rsbg.indexOf(setname) % colors.length];

      vm.tmp[index] = r;
      if (vm.colname != "" && (index == 0 || r[vm.colname] != vm.tmp[index - 1][vm.colname]))
        ret += ";border-top: 3px solid rgb(204,204,255)";

      return ret;
    }

    function onQueryResult(ret) {
      if (ret.length > 0) {
        vm.rsbg = [];
        vm.tab_result = ret;
        var newret = [];
        vm.col_show = [];
        vm.col_name = [];
        var i = 0;
        Object.keys(ret[0]).forEach(function (k) {
          var v = "colname_" + (i++);
          vm.col_show[v] = k;
          vm.col_name[k] = v;
        });
        ret.forEach(function (v) {
          var row = {};
          i = 0;
          Object.keys(v).forEach(function (k) {
            row["colname_" + (i++)] = v[k];
          });
          newret.push(row);
        });
        ret = newret;
      }
      vm.table_data = ret;
      translate();
      vm.colname = "";
      vm.colname2 = "";
      vm.desc = 1;
      vm.desc2 = 1;
    }

    function translate() {
      //console.log("translate");
      var count = 0;
      Object.keys(vm.table_data).forEach(function (k) {
        var line = vm.table_data[k];
        Object.keys(line).forEach(function (c) {
          var text = line[c];
          if (text != null && (text + "").indexOf("SQL:") == 0) {
            count = count + 1;
            var sql = text.substr(4);
            rpc.call("custom", {sql: sql})
              .then(function (ret) {
                count = count - 1;
                line[c] = ret[0].c;
                console.log(c + " " + ret[0].c);
                if (count == 0)
                  onTableComplete();
              });
            line[c] = "查询中";
          }
        })
      });
      if (count == 0)
        onTableComplete();
    }

    function onTableComplete() {
      console.log("table comlpete");
      if (vm.select[3] != null && vm.select[3] != "") {
        var js = vm.select[3];
        setTimeout(function () {
            //postjs();
            eval(js);
          }
          , 1);
      }
    }

    vm.onClickCopy = onClickCopy;
    function onClickCopy() {
      var str = document.querySelector("#data_table").innerHTML;
      function listener(e) {
        e.clipboardData.setData("text/html", str);
        e.clipboardData.setData("text/plain", str);
        e.preventDefault();
      }
      document.addEventListener("copy", listener);
      document.execCommand("copy");
      document.removeEventListener("copy", listener);
      alert("复制成功");
    }

    function onclick(colname) {
      if (vm.colname == "" || vm.colname == colname) {
        vm.colname2 = "";
        if (vm.desc && vm.colname != "") {
          vm.colname = "";
          return;
        }
        vm.colname = colname;
        vm.desc = !vm.desc;
      }
      else {
        vm.colname2 = colname;
        vm.desc2 = !vm.desc2;
      }
    }

    function sortfunc(op) {
      var v = op[vm.colname];
      if (v == null) return op;
      if ((v + "").lastIndexOf("%") === v.length - 1) {
        var ret = v.substr(0, v.length - 1) * 1;
        return ret;
      }
      else if (!isNaN(v))
        return v * 1;
      else
        return v;
    }

    function sortfunc2(op) {
      var v = op[vm.colname2];
      if (v == null) return op;
      if ((v + "").lastIndexOf("%") === v.length - 1) {
        var ret = v.substr(0, v.length - 1) * 1;
        return ret;
      }
      else if (!isNaN(v))
        return v * 1;
      else
        return v;
    }

    $scope.$on('$destroy', () => {
      if (vm.checker != null)
        $interval.cancel(vm.checker);
    });
  }
}
