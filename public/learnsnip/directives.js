(function () {
    'use strict';

    angular.module('learnsnip.directives', [])

        .directive('ngEnter', function () {
            //a directive to 'enter key press' in elements with the "ng-enter" attribute

            return function (scope, element, attrs) {

                element.bind("keydown keypress", function (event) {
                    if (event.which === 13) {
                        scope.$apply(function () {
                            scope.$eval(attrs.ngEnter);
                        });

                        event.preventDefault();
                    }
                });
            };
        });

})();