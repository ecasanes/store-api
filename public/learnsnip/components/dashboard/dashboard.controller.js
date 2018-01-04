(function () {
    'use strict';

    angular.module('learnsnip.dashboard.controller', [])

        .controller('DashboardCtrl', function($scope){

            $scope.courses = [];
            $scope.currentCourse = {};
            $scope.currentLesson = {};

        });

})();