/**@ngInject*/
export default class RepassController
{
  constructor(rpc) {
    let vm = this;
    vm.oldpass = "";
    vm.newpass = "";
    vm.newpass2 = "";
    vm.repass = repass;
    activate();

    function activate() {

    }

    function repass() {
      rpc.call("repass", { oldpass: vm.oldpass, newpass:vm.newpass})
        .then(function(res){
          if(res.result == 1) {
            alert("修改成功!")
          }
          else if(res.result == 2) {
            alert("密码错误！")
          }
          else
          {
            alert("修改失败!")
          }
        })
        .catch(function(){
          alert("修改失败!")
        })
    }

  }
}
