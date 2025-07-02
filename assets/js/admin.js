/* JavaScript pour l'interface d'administration BIAQuiz */

jQuery(document).ready(function($) {
    'use strict';
    
    // Initialiser les color pickers
    if ($.fn.wpColorPicker) {
        $('.color-picker').wpColorPicker();
    }
    
    // Gestion des questions dans les meta boxes
    initQuestionManagement();
    
    // Confirmation de suppression
    $('.remove-question, .delete-link').on('click', function(e) {
        if (!confirm(biaquiz_admin.strings.confirm_delete)) {
            e.preventDefault();
            return false;
        }
    });
    
    // Auto-save des brouillons
    if ($('#post_type').val() === 'biaquiz') {
        initAutoSave();
    }
    
    // Validation des formulaires
    initFormValidation();
    
    // Dashboard interactions
    initDashboard();
    
    /**
     * Gestion des questions
     */
    function initQuestionManagement() {
        var questionIndex = $('#biaquiz-questions-list .question-item').length;
        
        // Ajouter une question
        $(document).on('click', '#add-question', function(e) {
            e.preventDefault();
            
            var template = $('#question-template').html();
            if (!template) return;
            
            template = template.replace(/\{\{INDEX\}\}/g, questionIndex);
            $('#biaquiz-questions-list').append(template);
            
            // Initialiser les événements pour la nouvelle question
            initQuestionEvents($('#biaquiz-questions-list .question-item').last());
            
            questionIndex++;
            
            // Scroll vers la nouvelle question
            $('html, body').animate({
                scrollTop: $('#biaquiz-questions-list .question-item').last().offset().top - 100
            }, 500);
        });
        
        // Supprimer une question
        $(document).on('click', '.remove-question', function(e) {
            e.preventDefault();
            
            if (confirm('Êtes-vous sûr de vouloir supprimer cette question ?')) {
                $(this).closest('.question-item').fadeOut(300, function() {
                    $(this).remove();
                    updateQuestionNumbers();
                });
            }
        });
        
        // Réponse correcte
        $(document).on('change', '.correct-answer', function() {
            var questionContainer = $(this).closest('.question-item');
            
            // Retirer la classe correct de toutes les réponses
            questionContainer.find('.answer-item').removeClass('correct');
            questionContainer.find('.correct-answer').prop('checked', false);
            
            // Ajouter la classe correct à la réponse sélectionnée
            $(this).prop('checked', true);
            $(this).closest('.answer-item').addClass('correct');
        });
        
        // Initialiser les événements pour les questions existantes
        $('#biaquiz-questions-list .question-item').each(function() {
            initQuestionEvents($(this));
        });
    }
    
    /**
     * Initialiser les événements pour une question
     */
    function initQuestionEvents($question) {
        // Validation en temps réel
        $question.find('textarea, input[type="text"]').on('blur', function() {
            validateQuestion($question);
        });
        
        // Auto-resize des textareas
        $question.find('textarea').on('input', function() {
            autoResizeTextarea(this);
        });
        
        // Initialiser l'auto-resize
        $question.find('textarea').each(function() {
            autoResizeTextarea(this);
        });
    }
    
    /**
     * Valider une question
     */
    function validateQuestion($question) {
        var isValid = true;
        var errors = [];
        
        // Vérifier le texte de la question
        var questionText = $question.find('textarea[name*="[question_text]"]').val().trim();
        if (!questionText) {
            errors.push('Le texte de la question est requis');
            isValid = false;
        }
        
        // Vérifier les réponses
        var answers = $question.find('input[name*="[answers]"]');
        var hasCorrectAnswer = $question.find('.correct-answer:checked').length > 0;
        var emptyAnswers = 0;
        
        answers.each(function() {
            if (!$(this).val().trim()) {
                emptyAnswers++;
            }
        });
        
        if (emptyAnswers > 0) {
            errors.push(emptyAnswers + ' réponse(s) vide(s)');
            isValid = false;
        }
        
        if (!hasCorrectAnswer) {
            errors.push('Aucune réponse correcte sélectionnée');
            isValid = false;
        }
        
        // Afficher les erreurs
        var $errorContainer = $question.find('.question-errors');
        if ($errorContainer.length === 0) {
            $errorContainer = $('<div class="question-errors"></div>');
            $question.find('.question-header').after($errorContainer);
        }
        
        if (errors.length > 0) {
            $errorContainer.html('<div class="notice notice-error inline"><p>' + errors.join(', ') + '</p></div>').show();
            $question.addClass('has-errors');
        } else {
            $errorContainer.hide();
            $question.removeClass('has-errors');
        }
        
        return isValid;
    }
    
    /**
     * Mettre à jour les numéros de questions
     */
    function updateQuestionNumbers() {
        $('#biaquiz-questions-list .question-item').each(function(index) {
            $(this).find('.question-title').text('Question ' + (index + 1));
        });
    }
    
    /**
     * Auto-resize des textareas
     */
    function autoResizeTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = (textarea.scrollHeight) + 'px';
    }
    
    /**
     * Auto-save
     */
    function initAutoSave() {
        var saveTimer;
        var $form = $('#post');
        
        $form.on('input change', 'input, textarea, select', function() {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(function() {
                if ($('#post_status').val() === 'auto-draft' || $('#post_status').val() === 'draft') {
                    // Déclencher l'auto-save de WordPress
                    if (typeof autosave === 'function') {
                        autosave();
                    }
                }
            }, 30000); // 30 secondes
        });
    }
    
    /**
     * Validation des formulaires
     */
    function initFormValidation() {
        // Validation avant soumission
        $('#post').on('submit', function(e) {
            var isValid = true;
            var errors = [];
            
            // Valider le titre
            var title = $('#title').val().trim();
            if (!title) {
                errors.push('Le titre du quiz est requis');
                isValid = false;
            }
            
            // Valider les questions
            var questionCount = 0;
            $('#biaquiz-questions-list .question-item').each(function() {
                if (validateQuestion($(this))) {
                    questionCount++;
                } else {
                    isValid = false;
                }
            });
            
            if (questionCount === 0) {
                errors.push('Le quiz doit contenir au moins une question valide');
                isValid = false;
            }
            
            // Afficher les erreurs
            if (!isValid) {
                var errorHtml = '<div class="notice notice-error is-dismissible"><p><strong>Erreurs de validation :</strong><ul>';
                errors.forEach(function(error) {
                    errorHtml += '<li>' + error + '</li>';
                });
                errorHtml += '</ul></p></div>';
                
                $('.wrap h1').after(errorHtml);
                
                $('html, body').animate({
                    scrollTop: $('.wrap').offset().top
                }, 500);
                
                e.preventDefault();
                return false;
            }
        });
    }
    
    /**
     * Dashboard
     */
    function initDashboard() {
        // Animation des statistiques
        $('.stat-number').each(function() {
            var $this = $(this);
            var finalValue = parseInt($this.text());
            
            if (finalValue > 0) {
                $this.text('0');
                
                $({ value: 0 }).animate({ value: finalValue }, {
                    duration: 1500,
                    easing: 'swing',
                    step: function() {
                        $this.text(Math.floor(this.value));
                    },
                    complete: function() {
                        $this.text(finalValue);
                    }
                });
            }
        });
        
        // Graphiques simples pour les catégories
        if ($('.category-stats').length) {
            createCategoryChart();
        }
    }
    
    /**
     * Créer un graphique simple pour les catégories
     */
    function createCategoryChart() {
        var $container = $('.category-stats');
        var categories = [];
        var maxCount = 0;
        
        $container.find('.category-stat-item').each(function() {
            var count = parseInt($(this).find('.category-count').text());
            var name = $(this).find('.category-name').text();
            var color = $(this).find('.category-color').css('background-color');
            
            categories.push({ name: name, count: count, color: color });
            maxCount = Math.max(maxCount, count);
        });
        
        // Ajouter des barres de progression
        $container.find('.category-stat-item').each(function(index) {
            var $item = $(this);
            var category = categories[index];
            var percentage = maxCount > 0 ? (category.count / maxCount) * 100 : 0;
            
            if (!$item.find('.category-progress').length) {
                var $progress = $('<div class="category-progress"><div class="category-progress-bar"></div></div>');
                $item.append($progress);
                
                setTimeout(function() {
                    $progress.find('.category-progress-bar').css({
                        'width': percentage + '%',
                        'background-color': category.color
                    });
                }, 100 + (index * 100));
            }
        });
    }
    
    /**
     * Utilitaires
     */
    
    // Debounce function
    function debounce(func, wait, immediate) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            var later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }
    
    // Escape HTML
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
    
    // Notifications
    function showNotification(message, type) {
        type = type || 'success';
        
        var $notice = $('<div class="notice notice-' + type + ' is-dismissible biaquiz-notice"><p>' + escapeHtml(message) + '</p></div>');
        $('.wrap h1').after($notice);
        
        // Auto-dismiss après 5 secondes
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Exposer les fonctions utilitaires
    window.BIAQuizAdmin = {
        showNotification: showNotification,
        validateQuestion: validateQuestion,
        debounce: debounce
    };
});

/* CSS pour les barres de progression des catégories */
jQuery(document).ready(function($) {
    if (!$('#biaquiz-category-progress-css').length) {
        $('<style id="biaquiz-category-progress-css">' +
            '.category-progress { margin-top: 8px; height: 4px; background: #f0f0f0; border-radius: 2px; overflow: hidden; } ' +
            '.category-progress-bar { height: 100%; width: 0%; transition: width 1s ease-out; border-radius: 2px; } ' +
            '.question-errors { margin: 10px 0; } ' +
            '.question-item.has-errors { border-color: #dc3545; background: #fff5f5; }' +
        '</style>').appendTo('head');
    }
});

