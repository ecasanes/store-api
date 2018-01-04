(function () {
    'use strict';

    angular.module('learnsnip', [
        // Angular modules
        //'ngRoute',
        'ui.router',
        'ngCookies',
        'ngSanitize',

        // 3rd Party Modules
        'ui.bootstrap',
        'ngFileUpload',

        // Learnsnip Modules
        'learnsnip.config',
        'learnsnip.directives',
        'learnsnip.filters',

        'learnsnip.dashboard.controller',
        'learnsnip.main.controller',
        'learnsnip.sidebar.controller',

        'learnsnip.course.controller',
        'learnsnip.course.services'


    ]);

})();