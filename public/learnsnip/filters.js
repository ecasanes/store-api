(function () {
    'use strict';

    angular.module('learnsnip.filters', [])

        .filter('unsafe', function ($sce) {
            return $sce.trustAsHtml;
        })

        .filter('htmlize', ['$sce', function ($sce) {
            return function (val) {
                return $sce.trustAsHtml(val);
            };
        }])

        .filter('trusted', function ($sce) {
            return function (url) {
                return $sce.trustAsResourceUrl(url);
            };
        })

})();