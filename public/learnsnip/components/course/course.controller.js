(function () {
    'use strict';

    angular.module('learnsnip.course.controller', [])

        .controller('CourseCtrl', function ($scope, $timeout, $state, $stateParams, CourseService, Upload) {

            var currentState = $state.current.name;

            var courseData = {
                name: '',
                description: '',
                color: '#9013fe'
            };
            var itemData = {
                content: "",
                type: "paragraph"
            };
            var questionData = {
                content: "",
                display_index:null,
                answer_index:null,
                question_type:"Question",
                options:["","","",""],
                answer:"",
                credit:""
            };

            $scope.data = courseData;
            $scope.itemData = itemData;
            $scope.questionData = questionData;

            $scope.newLesson = false;
            $scope.uploadScript = false;
            $scope.newQuestion = false;

            $scope.lessonTitle = "";

            $scope.currentLessonItems = [];
            $scope.currentLessonQuestions = [];

            $scope.selectCourse = function (course) {

                if ($scope.currentCourse.id === course.id) {

                    $scope.currentCourse = {};
                    $state.go('courses', {courseId: $scope.currentCourse.id}, {notify: false});

                    return false;
                }

                $state.go('course', {courseId: course.id}, {notify: false});

                return false;

            };

            $scope.selectLesson = function (lesson) {

                var courseId = $stateParams.courseId;

                console.log('courseId', $scope.currentLesson);

                if ($scope.currentLesson.id === lesson.id) {
                    return false;
                }

                $state.go('lesson', {courseId: courseId, lessonId: lesson.id}, {notify: false});

                return false;

            };

            $scope.selectLessonById = function (lessonId) {

                CourseService.getLesson(lessonId).then(function (data) {

                    $scope.currentLesson = data.lesson;

                }, function (errResponse) {
                    console.log('error:', errResponse);
                });

            };

            $scope.selectCourseById = function (id) {

                CourseService.getCourse(id).then(function (data) {

                    console.log('data: ', data);
                    $scope.currentCourse = data.course;

                    $timeout(function () {
                        contentAreaDynamicWidth();
                    }, 100);

                }, function (errResponse) {
                    console.log('error: ', errResponse);
                });

            };

            $scope.createCourse = function () {
                $state.go('create-course', {}, {notify: false});
            };

            $scope.saveCourse = function () {

                CourseService.createCourse($scope.data).then(function (data) {
                    console.log('data', data);
                    $state.go('courses', {}, {notify: false});
                }, function (errorResponse) {
                    console.log('error', errorResponse);
                });

            };

            $scope.saveItem = function () {

                var lessonId = $scope.currentLesson.id;
                var data = $scope.itemData;

                CourseService.createItem(lessonId, data).then(function (data) {
                    console.log('data: ', data);
                    clearItemData();
                    getLessonItems(lessonId);
                }, function (errResponse) {
                    console.log('error: ', errResponse);
                });

            };

            $scope.saveQuestion = function () {

                var lessonId = $scope.currentLesson.id;
                var questionData = angular.copy($scope.questionData);

                questionData.options = JSON.stringify(questionData.options);

                CourseService.createQuestion(lessonId, questionData).then(function (data) {
                    console.log('data: ', data);
                    clearQuestionData();
                    getLessonQuestions(lessonId);
                }, function (errResponse) {
                    console.log('error: ', errResponse);
                });

            };

            $scope.setItemType = function (type) {

                $scope.itemData.type = type;

            };

            $scope.listAllCourses = function () {

                CourseService.getAllCourses().then(function (data) {
                    $scope.courses = data.courses;
                }, function (errorResponse) {
                    console.log('error', errorResponse);
                });


            };

            $scope.toggleAddLesson = function () {
                $scope.newLesson = true;
            };

            $scope.addLesson = function () {

                var courseId = $scope.currentCourse.id;

                CourseService.addLesson(courseId, $scope.lessonTitle).then(function (data) {
                    $scope.selectCourseById(courseId);
                    resetNewLesson();
                }, function (errResponse) {
                    console.log('error: ', errResponse);
                })

            };

            $scope.editItem = function (item) {

                $scope.itemData = item;

            };


            $scope.toggleUploadBox = function () {

                if ($scope.uploadScript) {
                    $scope.uploadScript = false;
                    return false;
                }

                $scope.uploadScript = true;

            };

            $scope.toggleNewQuestion = function () {

                if ($scope.newQuestion) {
                    $scope.newQuestion = false;
                    return false;
                }

                $scope.newQuestion = true;

            };

            $scope.toggleEditQuestion = function (question) {

                console.log('question: ', question);
                console.log('question type option: ', typeof question.options);

                if(typeof question.options === "string"){
                    question.options = JSON.parse(question.options);
                }

                if(question.active){
                    question.active = false;
                }else{
                    question.active = true;
                }

            };

            $scope.minimizeEditQuestion = function (question) {

                if(question.active){
                    question.active = false;
                }

            };

            $scope.updateQuestion = function (question) {

                console.log('to update question: ', question);

                var questionId = question.id;
                var questionData = angular.copy(question);

                questionData.options = JSON.stringify(questionData.options);

                CourseService.updateQuestion(questionId, questionData).then(function(data){

                }, function(errResponse){

                });

            };

            $scope.uploadFiles = function (file, errFiles) {

                var courseId = $scope.currentCourse.id;
                var lessonId = $scope.currentLesson.id;

                console.log('course id: ', courseId);
                console.log('lesson id: ', lessonId);

                $scope.file = file;
                $scope.errFile = errFiles && errFiles[0];
                $scope.fileUploadStatus = null;

                if (file) {
                    file.upload = Upload.upload({
                        url: apiUrl + '/lessons/' + lessonId + '/upload',
                        data: {script: file}
                    });

                    file.upload.then(function (response) {

                        if (response.data.type === 'error') {
                            fileUploadWentWrong();
                            return false;
                        }

                        $timeout(function () {
                            console.log('response: ', response);
                            file.result = response.data;
                            fileUploadSuccess();
                            getLessonItems(lessonId);
                        });

                    }, function (response) {
                        console.log('error', response);
                        if (response.status > 0) {
                            $scope.errorMsg = response.status + ': ' + response.data;
                        }
                    }, function (evt) {
                        file.progress = Math.min(100, parseInt(100.0 *
                            evt.loaded / evt.total));
                    });
                }
            };


            // initialize
            switch (currentState) {
                case 'course':
                    $scope.selectCourseById($stateParams.courseId);
                    $scope.listAllCourses();
                    break;
                case 'courses':
                    $scope.listAllCourses();
                    contentAreaDynamicWidth();
                    break;
                case 'create-course':
                    $scope.listAllCourses();
                    contentAreaDynamicWidth();
                    break;
                case 'lesson':
                    $scope.selectLessonById($stateParams.lessonId);
                    $scope.selectCourseById($stateParams.courseId);
                    getLessonItems($stateParams.lessonId);
                    getLessonQuestions($stateParams.lessonId);
                    $scope.listAllCourses();
                    break;
            }



            function getLessonQuestions(lessonId) {

                CourseService.getLessonQuestions(lessonId).then(function(data){
                    console.log('lesson questions: ', data);
                    $scope.currentLessonQuestions = data.questions;
                }, function(errResponse){

                });

            }

            function getLessonItems(lessonId) {

                CourseService.getLessonItems(lessonId).then(function (data) {
                    console.log('items: ', data);
                    $scope.currentLessonItems = data.items;
                }, function (errResponse) {
                    console.log('error: ', errResponse);
                })

            };



            function fileUploadWentWrong() {
                $scope.fileUploadStatus = 0;
            }

            function fileUploadSuccess() {
                $scope.fileUploadStatus = 1;
                $scope.uploadScript = false;
            }

            function resetNewLesson() {
                $scope.newLesson = false;
                $scope.lessonTitle = '';
            }

            function clearItemData() {

                $scope.itemData = itemData;

            }

            function clearQuestionData() {

                $scope.newQuestion = false;
                $scope.questionData = questionData;

            }


            function refreshContentWidth() {
                $timeout(function () {
                    setProperties($('.sidebar-wrapper').width());
                }, 100);
            }

            function contentAreaDynamicWidth() {

                refreshContentWidth();

                $(window).on('resize', function () {
                    setProperties($('.sidebar-wrapper').width());
                });
            }

            function setProperties(sidebarWidth) {
                var contentWidth = $('html').width() - sidebarWidth;
                $('.page-content-wrapper').css('width', contentWidth);
                var formTopSpacing = (($('html').height() - 100) - 477) / 2;
                //console.log('HTML: ' + $('html').height() + ' Form ' +  $('.create-course').height() + ' = ' + formTopSpacing);
                $('.page-content-wrapper .create-course').css('margin-top', formTopSpacing);

                $('.page-content-wrapper .generator-workspace').css('height', $('.page-content-wrapper').height() - 100);
                $('.page-content-wrapper .generator-workspace .lesson-container').css('height', $('.page-content-wrapper').height() - 100);

                if ($('.course-card').width() > 280) {
                    $('.course-card').css('width', '280px');
                }
            }

        });

})();