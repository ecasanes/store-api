(function () {
    'use strict';

    angular.module('learnsnip.course.services', [])

        .service('CourseService', function($http, $q) {

            this.getAllCourses = function () {

                return $http.get(apiUrl + '/courses')
                    .then(
                        function (response) {
                            return response.data;
                        },
                        function (errResponse) {
                            console.error('Error while fetching courses');
                            return $q.reject(errResponse);
                        }
                    );
            };

            this.getCourse = function (id) {

                return $http.get(apiUrl + '/courses/' + id)
                    .then(
                        function (response) {
                            return response.data;
                        },
                        function (errResponse) {
                            console.error('Error while fetching courses');
                            return $q.reject(errResponse);
                        }
                    );
            };

            this.createCourse = function (data) {

                return $http.post(apiUrl + '/courses', data)
                    .then(
                        function (response) {
                            return response.data;
                        },
                        function (errResponse) {
                            console.error('Error while creating course');
                            return $q.reject(errResponse);
                        }
                    );
            };

            this.addLesson = function (courseId, lessonTitle) {

                return $http.post(apiUrl + '/courses/'+courseId+'/lessons', {
                    name:lessonTitle
                })
                    .then(
                        function (response) {
                            return response.data;
                        },
                        function (errResponse) {
                            console.error('Error while initializing lesson');
                            return $q.reject(errResponse);
                        }
                    );
            };

            this.createLesson = function (courseId, data) {

                return $http.post(apiUrl + '/courses/'+courseId+'/lessons', data)
                    .then(
                        function (response) {
                            return response.data;
                        },
                        function (errResponse) {
                            console.error('Error while creating lesson');
                            return $q.reject(errResponse);
                        }
                    );
            };

            this.getLesson = function (lessonId) {

                return $http.get(apiUrl + '/lessons/' + lessonId)
                    .then(
                        function (response) {
                            return response.data;
                        },
                        function (errResponse) {
                            console.error('Error while fetching single lesson');
                            return $q.reject(errResponse);
                        }
                    );

            };

            this.getLessonQuestions = function (lessonId) {

                return $http.get(apiUrl + '/lessons/' + lessonId + '/questions')
                    .then(
                        function (response) {
                            return response.data;
                        },
                        function (errResponse) {
                            console.error('Error while fetching lesson questions');
                            return $q.reject(errResponse);
                        }
                    );

            };

            this.getLessonItems = function (lessonId) {

                return $http.get(apiUrl + '/lessons/' + lessonId + '/items')
                    .then(
                        function (response) {
                            return response.data;
                        },
                        function (errResponse) {
                            console.error('Error while fetching lesson items');
                            return $q.reject(errResponse);
                        }
                    );

            };

            this.createItem = function (lessonId, data) {

                return $http.post(apiUrl + '/lessons/'+lessonId+'/items', data)
                    .then(
                        function (response) {
                            return response.data;
                        },
                        function (errResponse) {
                            console.error('Error while creating item');
                            return $q.reject(errResponse);
                        }
                    );

            };

            this.createQuestion = function (lessonId, data) {

                return $http.post(apiUrl + '/lessons/'+lessonId+'/questions', data)
                    .then(
                        function (response) {
                            return response.data;
                        },
                        function (errResponse) {
                            console.error('Error while creating question');
                            return $q.reject(errResponse);
                        }
                    );

            };

            this.updateQuestion = function (questionId, data) {

                return $http.put(apiUrl + '/questions/'+questionId, data)
                    .then(
                        function (response) {
                            return response.data;
                        },
                        function (errResponse) {
                            console.error('Error while updating question');
                            return $q.reject(errResponse);
                        }
                    );

            }

        });

})();