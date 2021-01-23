/**@ngInject*/
export default () => {
  return {
    restrict: 'A',
    require: '?ngModel',
    scope: {},
    link: function (scope, element, attrs, ngModel) {
      if (!ngModel) return;
      element.on("blur", function () {
        var val = this.value;
        scope.$apply(function () {
          ngModel.$setViewValue(val);
        });
      })
    }
  };
}

