/* JavaScript pour le frontend BIAQuiz */

jQuery(document).ready(function($) {
    'use strict';
    
    // Variables globales
    var currentQuiz = null;
    var currentQuestionIndex = 0;
    var userAnswers = {};
    var startTime = null;
    var timerInterval = null;
    var retryMode = false;
    var retryQuestions = [];
    
    // Initialiser les quiz sur la page
    $('.biaquiz-container').each(function() {
        initQuiz($(this));
    });
    
    /**
     * Initialiser un quiz
     */
    function initQuiz($container) {
        var quizId = $container.data('quiz-id');
        if (!quizId) return;
        
        // Événements
        $container.find('.biaquiz-start-btn').on('click', function() {
            startQuiz($container, quizId);
        });
        
        $container.find('.biaquiz-prev-btn').on('click', function() {
            previousQuestion($container);
        });
        
        $container.find('.biaquiz-next-btn').on('click', function() {
            nextQuestion($container);
        });
        
        $container.find('.biaquiz-submit-btn').on('click', function() {
            submitQuiz($container, quizId);
        });
        
        $container.find('.biaquiz-retry-btn').on('click', function() {
            retryIncorrectQuestions($container, quizId);
        });
        
        $container.find('.biaquiz-restart-btn').on('click', function() {
            restartQuiz($container, quizId);
        });
        
        // Événements pour les réponses
        $container.on('change', '.answer-option input[type="radio"]', function() {
            var questionIndex = $(this).closest('.biaquiz-question').data('question-index');
            var answerIndex = parseInt($(this).val());
            
            userAnswers[questionIndex] = answerIndex;
            
            // Mettre à jour l'apparence
            var $question = $(this).closest('.biaquiz-question');
            $question.find('.answer-option').removeClass('selected');
            $(this).closest('.answer-option').addClass('selected');
            
            // Activer le bouton suivant
            updateNavigationButtons($container);
        });
        
        // Événements clavier
        $(document).on('keydown', function(e) {
            if ($container.find('.biaquiz-quiz-screen').is(':visible')) {
                switch(e.which) {
                    case 37: // Flèche gauche
                        e.preventDefault();
                        previousQuestion($container);
                        break;
                    case 39: // Flèche droite
                        e.preventDefault();
                        nextQuestion($container);
                        break;
                    case 13: // Entrée
                        e.preventDefault();
                        if ($container.find('.biaquiz-submit-btn').is(':visible')) {
                            submitQuiz($container, quizId);
                        } else {
                            nextQuestion($container);
                        }
                        break;
                }
            }
        });
    }
    
    /**
     * Démarrer un quiz
     */
    function startQuiz($container, quizId) {
        showLoading($container.find('.biaquiz-start-btn'));
        
        $.ajax({
            url: biaquiz_frontend.ajax_url,
            type: 'POST',
            data: {
                action: 'biaquiz_get_quiz',
                quiz_id: quizId,
                nonce: biaquiz_frontend.nonce
            },
            success: function(response) {
                if (response.success) {
                    currentQuiz = response.data;
                    currentQuestionIndex = 0;
                    userAnswers = {};
                    retryMode = false;
                    retryQuestions = [];
                    
                    renderQuiz($container);
                    showQuizScreen($container);
                    startTimer($container);
                } else {
                    showError($container, response.data || 'Erreur lors du chargement du quiz');
                }
            },
            error: function() {
                showError($container, 'Erreur de connexion');
            },
            complete: function() {
                hideLoading($container.find('.biaquiz-start-btn'));
            }
        });
    }
    
    /**
     * Rendu du quiz
     */
    function renderQuiz($container) {
        var $questionsContainer = $container.find('.biaquiz-questions');
        $questionsContainer.empty();
        
        var questionsToRender = retryMode ? retryQuestions : currentQuiz.questions;
        
        questionsToRender.forEach(function(question, index) {
            var originalIndex = retryMode ? question.originalIndex : index;
            var $question = $('<div class="biaquiz-question" data-question-index="' + originalIndex + '"></div>');
            
            // Header de la question
            var $header = $('<div class="question-header"></div>');
            $header.append('<div class="question-number">Question ' + (index + 1) + ' / ' + questionsToRender.length + '</div>');
            $header.append('<div class="question-text">' + escapeHtml(question.question_text) + '</div>');
            $question.append($header);
            
            // Réponses
            var $answers = $('<div class="question-answers"></div>');
            question.answers.forEach(function(answer, answerIndex) {
                var answerId = 'quiz_' + currentQuiz.id + '_q' + originalIndex + '_a' + answerIndex;
                var answerLetter = String.fromCharCode(65 + answerIndex); // A, B, C, D
                
                var $answerOption = $('<div class="answer-option"></div>');
                var $label = $('<label for="' + answerId + '"></label>');
                
                $label.append('<span class="answer-letter">' + answerLetter + '</span>');
                $label.append('<span class="answer-text">' + escapeHtml(answer.text) + '</span>');
                
                var $input = $('<input type="radio" name="question_' + originalIndex + '" value="' + answerIndex + '" id="' + answerId + '">');
                
                $answerOption.append($input);
                $answerOption.append($label);
                $answers.append($answerOption);
            });
            
            $question.append($answers);
            $questionsContainer.append($question);
        });
        
        // Mettre à jour les totaux
        $container.find('.total-questions').text(questionsToRender.length);
        
        // Afficher la première question
        showQuestion($container, 0);
    }
    
    /**
     * Afficher l'écran de quiz
     */
    function showQuizScreen($container) {
        $container.find('.biaquiz-start-screen').hide();
        $container.find('.biaquiz-results-screen').hide();
        $container.find('.biaquiz-quiz-screen').show();
    }
    
    /**
     * Démarrer le timer
     */
    function startTimer($container) {
        startTime = new Date();
        
        timerInterval = setInterval(function() {
            var elapsed = Math.floor((new Date() - startTime) / 1000);
            var minutes = Math.floor(elapsed / 60);
            var seconds = elapsed % 60;
            
            var timeString = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            $container.find('.timer-value').text(timeString);
        }, 1000);
    }
    
    /**
     * Arrêter le timer
     */
    function stopTimer() {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
    }
    
    /**
     * Afficher une question
     */
    function showQuestion($container, index) {
        var $questions = $container.find('.biaquiz-question');
        var totalQuestions = $questions.length;
        
        if (index < 0 || index >= totalQuestions) return;
        
        currentQuestionIndex = index;
        
        // Masquer toutes les questions
        $questions.removeClass('active').hide();
        
        // Afficher la question courante
        $questions.eq(index).addClass('active').show();
        
        // Mettre à jour la progression
        var progress = ((index + 1) / totalQuestions) * 100;
        $container.find('.progress-fill').css('width', progress + '%');
        $container.find('.current-question').text(index + 1);
        
        // Mettre à jour les boutons de navigation
        updateNavigationButtons($container);
        
        // Restaurer la réponse sélectionnée
        var questionIndex = $questions.eq(index).data('question-index');
        if (userAnswers.hasOwnProperty(questionIndex)) {
            var answerIndex = userAnswers[questionIndex];
            var $input = $questions.eq(index).find('input[value="' + answerIndex + '"]');
            $input.prop('checked', true);
            $input.closest('.answer-option').addClass('selected');
        }
    }
    
    /**
     * Question précédente
     */
    function previousQuestion($container) {
        if (currentQuestionIndex > 0) {
            showQuestion($container, currentQuestionIndex - 1);
        }
    }
    
    /**
     * Question suivante
     */
    function nextQuestion($container) {
        var totalQuestions = $container.find('.biaquiz-question').length;
        
        if (currentQuestionIndex < totalQuestions - 1) {
            showQuestion($container, currentQuestionIndex + 1);
        }
    }
    
    /**
     * Mettre à jour les boutons de navigation
     */
    function updateNavigationButtons($container) {
        var $prevBtn = $container.find('.biaquiz-prev-btn');
        var $nextBtn = $container.find('.biaquiz-next-btn');
        var $submitBtn = $container.find('.biaquiz-submit-btn');
        
        var totalQuestions = $container.find('.biaquiz-question').length;
        var isLastQuestion = currentQuestionIndex === totalQuestions - 1;
        
        // Bouton précédent
        $prevBtn.prop('disabled', currentQuestionIndex === 0);
        
        // Boutons suivant/terminer
        if (isLastQuestion) {
            $nextBtn.hide();
            $submitBtn.show();
        } else {
            $nextBtn.show();
            $submitBtn.hide();
        }
    }
    
    /**
     * Soumettre le quiz
     */
    function submitQuiz($container, quizId) {
        // Vérifier que toutes les questions ont une réponse
        var questionsToAnswer = retryMode ? retryQuestions : currentQuiz.questions;
        var unansweredQuestions = [];
        
        questionsToAnswer.forEach(function(question, index) {
            var originalIndex = retryMode ? question.originalIndex : index;
            if (!userAnswers.hasOwnProperty(originalIndex)) {
                unansweredQuestions.push(index + 1);
            }
        });
        
        if (unansweredQuestions.length > 0) {
            alert('Veuillez répondre à toutes les questions. Questions non répondues : ' + unansweredQuestions.join(', '));
            return;
        }
        
        // Confirmation
        if (!confirm(biaquiz_frontend.strings.confirm_submit)) {
            return;
        }
        
        stopTimer();
        var timeTaken = Math.floor((new Date() - startTime) / 1000);
        
        showLoading($container.find('.biaquiz-submit-btn'));
        
        $.ajax({
            url: biaquiz_frontend.ajax_url,
            type: 'POST',
            data: {
                action: 'biaquiz_submit',
                quiz_id: quizId,
                answers: userAnswers,
                time_taken: timeTaken,
                nonce: biaquiz_frontend.nonce
            },
            success: function(response) {
                if (response.success) {
                    showResults($container, response.data);
                } else {
                    showError($container, response.data || 'Erreur lors de la soumission');
                }
            },
            error: function() {
                showError($container, 'Erreur de connexion');
            },
            complete: function() {
                hideLoading($container.find('.biaquiz-submit-btn'));
            }
        });
    }
    
    /**
     * Afficher les résultats
     */
    function showResults($container, results) {
        // Masquer l'écran de quiz
        $container.find('.biaquiz-quiz-screen').hide();
        
        // Préparer l'écran de résultats
        var $resultsScreen = $container.find('.biaquiz-results-screen');
        
        // Score
        var percentage = results.percentage;
        var scoreAngle = (percentage / 100) * 360;
        $resultsScreen.find('.score-circle').css('--score-angle', scoreAngle + 'deg');
        $resultsScreen.find('.score-text').text(percentage + '%');
        
        // Statistiques
        $resultsScreen.find('.score-value').text(results.score + ' / ' + results.total_questions);
        $resultsScreen.find('.percentage-value').text(percentage + '%');
        $resultsScreen.find('.time-value').text(formatTime(results.time_taken));
        
        // Message de résultat
        var message = '';
        var messageClass = '';
        
        if (results.perfect_score) {
            message = '🎉 Parfait ! Vous avez obtenu un score parfait !';
            messageClass = 'perfect';
        } else if (percentage >= 80) {
            message = '👏 Excellent travail ! Vous maîtrisez bien le sujet.';
            messageClass = 'good';
        } else if (percentage >= 60) {
            message = '👍 Bon travail ! Continuez à vous entraîner.';
            messageClass = 'good';
        } else {
            message = '📚 Continuez à étudier et réessayez. Vous pouvez y arriver !';
            messageClass = 'needs-improvement';
        }
        
        $resultsScreen.find('.results-message').text(message).removeClass().addClass('results-message ' + messageClass);
        
        // Bouton de reprise
        if (results.incorrect_questions && results.incorrect_questions.length > 0 && !retryMode) {
            $resultsScreen.find('.biaquiz-retry-btn').show().data('incorrect-questions', results.incorrect_questions);
        } else {
            $resultsScreen.find('.biaquiz-retry-btn').hide();
        }
        
        // Révision des réponses
        renderReview($container, results);
        
        // Afficher l'écran de résultats
        $resultsScreen.show();
        
        // Scroll vers les résultats
        $('html, body').animate({
            scrollTop: $resultsScreen.offset().top - 100
        }, 500);
    }
    
    /**
     * Rendu de la révision
     */
    function renderReview($container, results) {
        var $reviewContainer = $container.find('.review-questions');
        $reviewContainer.empty();
        
        var questionsToReview = retryMode ? retryQuestions : currentQuiz.questions;
        
        results.results.forEach(function(result, index) {
            var question = questionsToReview.find(function(q, i) {
                return retryMode ? q.originalIndex === result.question_index : i === result.question_index;
            });
            
            if (!question) return;
            
            var $reviewQuestion = $('<div class="review-question"></div>');
            $reviewQuestion.addClass(result.is_correct ? 'correct' : 'incorrect');
            
            // Texte de la question
            $reviewQuestion.append('<div class="review-question-text">' + escapeHtml(question.question_text) + '</div>');
            
            // Réponses
            var $reviewAnswers = $('<div class="review-answers"></div>');
            
            question.answers.forEach(function(answer, answerIndex) {
                var $reviewAnswer = $('<div class="review-answer"></div>');
                var answerLetter = String.fromCharCode(65 + answerIndex);
                
                $reviewAnswer.html('<strong>' + answerLetter + ':</strong> ' + escapeHtml(answer.text));
                
                // Marquer les réponses
                if (answerIndex === result.user_answer) {
                    $reviewAnswer.addClass('user-answer');
                    if (!result.is_correct) {
                        $reviewAnswer.addClass('user-incorrect');
                    }
                }
                
                if (answerIndex === result.correct_answer) {
                    $reviewAnswer.addClass('correct-answer');
                }
                
                $reviewAnswers.append($reviewAnswer);
            });
            
            $reviewQuestion.append($reviewAnswers);
            
            // Explication
            if (result.explanation) {
                $reviewQuestion.append('<div class="review-explanation">' + escapeHtml(result.explanation) + '</div>');
            }
            
            $reviewContainer.append($reviewQuestion);
        });
    }
    
    /**
     * Reprendre les questions incorrectes
     */
    function retryIncorrectQuestions($container, quizId) {
        var incorrectQuestions = $container.find('.biaquiz-retry-btn').data('incorrect-questions');
        
        if (!incorrectQuestions || incorrectQuestions.length === 0) return;
        
        // Préparer les questions à reprendre
        retryQuestions = [];
        incorrectQuestions.forEach(function(questionIndex) {
            var question = currentQuiz.questions[questionIndex];
            if (question) {
                question.originalIndex = questionIndex;
                retryQuestions.push(question);
            }
        });
        
        // Réinitialiser les réponses pour les questions incorrectes
        incorrectQuestions.forEach(function(questionIndex) {
            delete userAnswers[questionIndex];
        });
        
        retryMode = true;
        currentQuestionIndex = 0;
        
        renderQuiz($container);
        showQuizScreen($container);
        startTimer($container);
    }
    
    /**
     * Recommencer le quiz
     */
    function restartQuiz($container, quizId) {
        currentQuestionIndex = 0;
        userAnswers = {};
        retryMode = false;
        retryQuestions = [];
        stopTimer();
        
        $container.find('.biaquiz-results-screen').hide();
        $container.find('.biaquiz-quiz-screen').hide();
        $container.find('.biaquiz-start-screen').show();
    }
    
    /**
     * Afficher une erreur
     */
    function showError($container, message) {
        var $notice = $('<div class="biaquiz-notice error">' + escapeHtml(message) + '</div>');
        $container.prepend($notice);
        
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    /**
     * Afficher le loading
     */
    function showLoading($element) {
        $element.addClass('loading').prop('disabled', true);
    }
    
    /**
     * Masquer le loading
     */
    function hideLoading($element) {
        $element.removeClass('loading').prop('disabled', false);
    }
    
    /**
     * Formater le temps
     */
    function formatTime(seconds) {
        var minutes = Math.floor(seconds / 60);
        var remainingSeconds = seconds % 60;
        
        if (minutes > 0) {
            return minutes + ' min ' + remainingSeconds + ' sec';
        } else {
            return remainingSeconds + ' sec';
        }
    }
    
    /**
     * Échapper le HTML
     */
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Gestion du responsive
    function handleResize() {
        // Ajuster la taille des éléments si nécessaire
        if ($(window).width() < 768) {
            $('.biaquiz-container').addClass('mobile');
        } else {
            $('.biaquiz-container').removeClass('mobile');
        }
    }
    
    $(window).on('resize', handleResize);
    handleResize();
    
    // Prévenir la fermeture accidentelle pendant un quiz
    $(window).on('beforeunload', function(e) {
        if ($('.biaquiz-quiz-screen:visible').length > 0) {
            var message = 'Vous êtes en train de passer un quiz. Êtes-vous sûr de vouloir quitter ?';
            e.returnValue = message;
            return message;
        }
    });
    
    // Exposer les fonctions pour usage externe
    window.BIAQuizFrontend = {
        startQuiz: startQuiz,
        submitQuiz: submitQuiz,
        restartQuiz: restartQuiz,
        formatTime: formatTime
    };
});

