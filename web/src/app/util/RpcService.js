/**@ngInject*/
export default class RpcService
{
  constructor($http, $q, $log, $timeout, $rootScope, $filter) {
    this.call = function (cmd, data, showloading) {
      var d = $q.defer();

      if($rootScope.userName == "admin")
        $log.info("rpc.call", cmd, data);

      var url = "api/api_" + cmd + ".php";
      if(showloading == null) showloading = true;
      if(showloading) $rootScope.loading = true;

      $http({
        url: url,
        method: "POST",
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        data: data
      })
        .then(function (data) {
          if (data != null && data.status == 200 && (typeof(data.data) == "object" || typeof(data.data) == "string" || data.data == "true")) {
            $timeout(function(){
              d.resolve(data.data);
              if(showloading) $rootScope.loading = false;
            },1)
          }
          else {
            $log.error(url, data.data);
            d.reject();
            if(showloading) $rootScope.loading = false;
          }
        })
        .catch(function () {
          $log.error(url, data.data);
          d.reject();
          if(showloading) $rootScope.loading = false;
        });
      return d.promise;
    };

    this.format = function(format, args) {
      var result = format;
      for (var key in args) {
        if(args[key]!=undefined){
          var reg = new RegExp("({" + key + "})", "g");
          var value = args[key];
          if(value instanceof Date) {
            value = $filter('date')(value, 'yyyy-MM-dd');
          }
          result = result.replace(reg, value);
        }
      }
      return result;
    };


    this.conv = function(v, t) {
      t = t || "";
      var j = JSON.stringify(v);
      var len = 150;
      if (typeof(v) == "string") {
        var nj = "";
        var pos = 0;
        while (true) {
          nj += v.substr(pos, len);
          pos += len;
          if (pos > v.length) break;
          nj += "\n";
        }
        return nj;
      }
      if (j.length < len)
        return j;
      var ret = t + "{\n";
      for (var k in v) {
        ret += t + ' "' + k + '":' + this.conv(v[k], t + " ") + ",\n";
      }
      ret += t + "}";
      return ret;
    }

  }
}


