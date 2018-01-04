// var baseUrl = "http://178.79.188.169";
var baseUrl = "http://localhost:8000";
var apiUrl = baseUrl + "/api";

(function () {
    'use strict';

    String.prototype.toController = function () {
        var camelizedString = camelize(this);
        var controllerName = camelizedString + 'Ctrl';
        controllerName = controllerName.charAt(0).toUpperCase() + controllerName.slice(1);
        controllerName = controllerName.replace("-", "");
        return controllerName;
    };

    function camelize(str) {
        return str.replace(/(?:^\w|[A-Z]|\b\w|\s+)/g, function (match, index) {
            if (+match === 0) return ""; // or if (/\s+/.test(match)) for white spaces
            return index == 0 ? match.toLowerCase() : match.toUpperCase();
        });
    }

    angular.module('learnsnip.config', [])

        .config(function ($stateProvider, $urlRouterProvider, $locationProvider) {

            $urlRouterProvider.otherwise('/');

            $stateProvider
                .state('dashboard', {
                    abstract: true,
                    //url: '/',
                    views: {

                        '' : {
                            templateUrl: 'learnsnip/components/dashboard/dashboard.view.html',
                            controller: 'DashboardCtrl'
                        },

                        'sidebar@dashboard' : {
                            templateUrl: 'learnsnip/components/sidebar/sidebar.view.html',
                            controller: 'SidebarCtrl'
                        },

                        'main@dashboard' : {
                            templateUrl: 'learnsnip/components/main/main.view.html',
                            controller: 'MainCtrl'
                        },

                    },
                })

                .state('courses', {
                    parent: 'dashboard',
                    url: '/',
                    views: {
                        'content@dashboard': {
                            templateUrl: 'learnsnip/components/course/course-list.view.html',
                            controller: 'CourseCtrl'
                        },
                        'sidebar@dashboard': {
                            templateUrl: 'learnsnip/components/course/sidebar-list.view.html',
                            controller: 'CourseCtrl'
                        },
                    }
                })

                .state('course', {
                    parent: 'dashboard',
                    url: '/courses/:courseId',
                    views: {
                        'sidebar@dashboard': {
                            templateUrl: 'learnsnip/components/course/sidebar-list.view.html',
                            controller: 'CourseCtrl'
                        },
                    }
                })


                .state('lesson', {
                    parent: 'dashboard',
                    url: '/courses/:courseId/lessons/:lessonId',
                    views: {
                        'content@dashboard': {
                         templateUrl: 'learnsnip/components/course/lesson.view.html',
                         controller: 'CourseCtrl'
                         },
                        'sidebar@dashboard': {
                            templateUrl: 'learnsnip/components/course/sidebar-list.view.html',
                            controller: 'CourseCtrl'
                        },
                    }
                })

                .state('create-course', {
                    parent: 'dashboard',
                    url: '/courses/create',
                    views: {
                        'content@dashboard': {
                            templateUrl: 'learnsnip/components/course/create-course.view.html',
                            controller: 'CourseCtrl'
                        },
                        'sidebar@dashboard': {
                            templateUrl: 'learnsnip/components/course/sidebar-list.view.html',
                            controller: 'CourseCtrl'
                        },
                    }
                })

            $locationProvider
                .hashPrefix('')
                //.html5Mode(true);

        });

})();
