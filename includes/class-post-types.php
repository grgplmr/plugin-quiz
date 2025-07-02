<?php
/**
 * Gestion des Custom Post Types pour BIAQuiz
 */

if (!defined('ABSPATH')) {
    exit;
}

class BIAQuiz_Post_Types {
    
    /**
     * Initialiser les hooks
     */
    public static function init() {
        add_action('init', array(__CLASS__, 'register_post_types'));
        add_action('add_meta_boxes', array(__CLASS__, 'add_meta_boxes'));
        add_action('save_post', array(__CLASS__, 'save_quiz_meta'));
        add_filter('manage_biaquiz_posts_columns', array(__CLASS__, 'quiz_columns'));
        add_action('manage_biaquiz_posts_custom_column', array(__CLASS__, 'quiz_column_content'), 10, 2);
    }
    
    /**
     * Enregistrer les Custom Post Types
     */
    public static function register_post_types() {
        // Post Type Quiz
        $labels = array(
            'name'                  => __('Quiz BIA', 'biaquiz-core'),
            'singular_name'         => __('Quiz', 'biaquiz-core'),
            'menu_name'             => __('Quiz BIA', 'biaquiz-core'),
            'name_admin_bar'        => __('Quiz', 'biaquiz-core'),
            'archives'              => __('Archives des Quiz', 'biaquiz-core'),
            'attributes'            => __('Attributs du Quiz', 'biaquiz-core'),
            'parent_item_colon'     => __('Quiz Parent:', 'biaquiz-core'),
            'all_items'             => __('Tous les Quiz', 'biaquiz-core'),
            'add_new_item'          => __('Ajouter un Nouveau Quiz', 'biaquiz-core'),
            'add_new'               => __('Ajouter Nouveau', 'biaquiz-core'),
            'new_item'              => __('Nouveau Quiz', 'biaquiz-core'),
            'edit_item'             => __('Modifier le Quiz', 'biaquiz-core'),
            'update_item'           => __('Mettre à jour le Quiz', 'biaquiz-core'),
            'view_item'             => __('Voir le Quiz', 'biaquiz-core'),
            'view_items'            => __('Voir les Quiz', 'biaquiz-core'),
            'search_items'          => __('Rechercher un Quiz', 'biaquiz-core'),
            'not_found'             => __('Aucun quiz trouvé', 'biaquiz-core'),
            'not_found_in_trash'    => __('Aucun quiz trouvé dans la corbeille', 'biaquiz-core'),
            'featured_image'        => __('Image du Quiz', 'biaquiz-core'),
            'set_featured_image'    => __('Définir l\'image du quiz', 'biaquiz-core'),
            'remove_featured_image' => __('Supprimer l\'image du quiz', 'biaquiz-core'),
            'use_featured_image'    => __('Utiliser comme image du quiz', 'biaquiz-core'),
            'insert_into_item'      => __('Insérer dans le quiz', 'biaquiz-core'),
            'uploaded_to_this_item' => __('Téléchargé vers ce quiz', 'biaquiz-core'),
            'items_list'            => __('Liste des quiz', 'biaquiz-core'),
            'items_list_navigation' => __('Navigation de la liste des quiz', 'biaquiz-core'),
            'filter_items_list'     => __('Filtrer la liste des quiz', 'biaquiz-core'),
        );
        
        $args = array(
            'label'                 => __('Quiz', 'biaquiz-core'),
            'description'           => __('Quiz BIA pour l\'entraînement', 'biaquiz-core'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'excerpt', 'thumbnail'),
            'taxonomies'            => array('quiz_category'),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 25,
            'menu_icon'             => 'dashicons-welcome-learn-more',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
            'rewrite'               => array('slug' => 'quiz'),
        );
        
        register_post_type('biaquiz', $args);
    }
    
    /**
     * Ajouter les meta boxes
     */
    public static function add_meta_boxes() {
        add_meta_box(
            'biaquiz_questions',
            __('Questions du Quiz', 'biaquiz-core'),
            array(__CLASS__, 'questions_meta_box'),
            'biaquiz',
            'normal',
            'high'
        );
        
        add_meta_box(
            'biaquiz_settings',
            __('Paramètres du Quiz', 'biaquiz-core'),
            array(__CLASS__, 'settings_meta_box'),
            'biaquiz',
            'side',
            'default'
        );
    }
    
    /**
     * Meta box pour les questions
     */
    public static function questions_meta_box($post) {
        wp_nonce_field('biaquiz_questions_nonce', 'biaquiz_questions_nonce');
        
        $questions = get_post_meta($post->ID, '_biaquiz_questions', true);
        if (!is_array($questions)) {
            $questions = array();
        }
        
        ?>
        <div id="biaquiz-questions-container">
            <div id="biaquiz-questions-list">
                <?php foreach ($questions as $index => $question): ?>
                    <?php self::render_question_form($index, $question); ?>
                <?php endforeach; ?>
            </div>
            
            <button type="button" id="add-question" class="button button-secondary">
                <?php _e('Ajouter une Question', 'biaquiz-core'); ?>
            </button>
        </div>
        
        <script type="text/template" id="question-template">
            <?php self::render_question_form('{{INDEX}}', array()); ?>
        </script>
        
        <style>
        .question-item {
            border: 1px solid #ddd;
            margin: 10px 0;
            padding: 15px;
            background: #f9f9f9;
        }
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .question-title {
            font-weight: bold;
            font-size: 14px;
        }
        .remove-question {
            color: #a00;
            text-decoration: none;
        }
        .answer-item {
            margin: 5px 0;
            padding: 5px;
            border-left: 3px solid #ddd;
            padding-left: 10px;
        }
        .answer-item.correct {
            border-left-color: #46b450;
            background: #f0fff0;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            var questionIndex = <?php echo count($questions); ?>;
            
            $('#add-question').click(function() {
                var template = $('#question-template').html();
                template = template.replace(/\{\{INDEX\}\}/g, questionIndex);
                $('#biaquiz-questions-list').append(template);
                questionIndex++;
            });
            
            $(document).on('click', '.remove-question', function(e) {
                e.preventDefault();
                if (confirm('<?php _e('Êtes-vous sûr de vouloir supprimer cette question ?', 'biaquiz-core'); ?>')) {
                    $(this).closest('.question-item').remove();
                }
            });
            
            $(document).on('change', '.correct-answer', function() {
                var questionContainer = $(this).closest('.question-item');
                questionContainer.find('.answer-item').removeClass('correct');
                questionContainer.find('.correct-answer').prop('checked', false);
                $(this).prop('checked', true);
                $(this).closest('.answer-item').addClass('correct');
            });
        });
        </script>
        <?php
    }
    
    /**
     * Rendu du formulaire de question
     */
    private static function render_question_form($index, $question = array()) {
        $question = wp_parse_args($question, array(
            'question_text' => '',
            'explanation' => '',
            'answers' => array(
                array('text' => '', 'correct' => false),
                array('text' => '', 'correct' => false),
                array('text' => '', 'correct' => false),
                array('text' => '', 'correct' => false)
            )
        ));
        
        // S'assurer qu'on a 4 réponses
        while (count($question['answers']) < 4) {
            $question['answers'][] = array('text' => '', 'correct' => false);
        }
        
        ?>
        <div class="question-item">
            <div class="question-header">
                <span class="question-title"><?php printf(__('Question %s', 'biaquiz-core'), $index + 1); ?></span>
                <a href="#" class="remove-question"><?php _e('Supprimer', 'biaquiz-core'); ?></a>
            </div>
            
            <p>
                <label><strong><?php _e('Texte de la question:', 'biaquiz-core'); ?></strong></label><br>
                <textarea name="questions[<?php echo $index; ?>][question_text]" rows="3" style="width: 100%;"><?php echo esc_textarea($question['question_text']); ?></textarea>
            </p>
            
            <p>
                <label><strong><?php _e('Réponses:', 'biaquiz-core'); ?></strong></label>
            </p>
            
            <?php foreach ($question['answers'] as $answer_index => $answer): ?>
                <div class="answer-item <?php echo $answer['correct'] ? 'correct' : ''; ?>">
                    <label>
                        <input type="radio" 
                               name="questions[<?php echo $index; ?>][correct_answer]" 
                               value="<?php echo $answer_index; ?>"
                               class="correct-answer"
                               <?php checked($answer['correct']); ?>>
                        <?php printf(__('Réponse %s:', 'biaquiz-core'), chr(65 + $answer_index)); ?>
                    </label>
                    <input type="text" 
                           name="questions[<?php echo $index; ?>][answers][<?php echo $answer_index; ?>]" 
                           value="<?php echo esc_attr($answer['text']); ?>" 
                           style="width: 80%; margin-left: 10px;">
                </div>
            <?php endforeach; ?>
            
            <p>
                <label><strong><?php _e('Explication (optionnel):', 'biaquiz-core'); ?></strong></label><br>
                <textarea name="questions[<?php echo $index; ?>][explanation]" rows="2" style="width: 100%;"><?php echo esc_textarea($question['explanation']); ?></textarea>
            </p>
        </div>
        <?php
    }
    
    /**
     * Meta box pour les paramètres
     */
    public static function settings_meta_box($post) {
        wp_nonce_field('biaquiz_settings_nonce', 'biaquiz_settings_nonce');
        
        $difficulty = get_post_meta($post->ID, '_biaquiz_difficulty', true);
        $quiz_number = get_post_meta($post->ID, '_biaquiz_number', true);
        $active = get_post_meta($post->ID, '_biaquiz_active', true);
        
        ?>
        <p>
            <label for="biaquiz_difficulty"><strong><?php _e('Difficulté:', 'biaquiz-core'); ?></strong></label><br>
            <select name="biaquiz_difficulty" id="biaquiz_difficulty" style="width: 100%;">
                <option value="facile" <?php selected($difficulty, 'facile'); ?>><?php _e('Facile', 'biaquiz-core'); ?></option>
                <option value="moyen" <?php selected($difficulty, 'moyen'); ?>><?php _e('Moyen', 'biaquiz-core'); ?></option>
                <option value="difficile" <?php selected($difficulty, 'difficile'); ?>><?php _e('Difficile', 'biaquiz-core'); ?></option>
            </select>
        </p>
        
        <p>
            <label for="biaquiz_number"><strong><?php _e('Numéro du Quiz:', 'biaquiz-core'); ?></strong></label><br>
            <input type="number" name="biaquiz_number" id="biaquiz_number" value="<?php echo esc_attr($quiz_number); ?>" style="width: 100%;" min="1">
        </p>
        
        <p>
            <label>
                <input type="checkbox" name="biaquiz_active" value="1" <?php checked($active, '1'); ?>>
                <strong><?php _e('Quiz actif', 'biaquiz-core'); ?></strong>
            </label>
        </p>
        <?php
    }
    
    /**
     * Sauvegarder les métadonnées du quiz
     */
    public static function save_quiz_meta($post_id) {
        // Vérifier les nonces
        if (!isset($_POST['biaquiz_questions_nonce']) || !wp_verify_nonce($_POST['biaquiz_questions_nonce'], 'biaquiz_questions_nonce')) {
            return;
        }
        
        if (!isset($_POST['biaquiz_settings_nonce']) || !wp_verify_nonce($_POST['biaquiz_settings_nonce'], 'biaquiz_settings_nonce')) {
            return;
        }
        
        // Vérifier les permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Éviter la sauvegarde automatique
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Sauvegarder les questions
        if (isset($_POST['questions'])) {
            $questions = array();
            foreach ($_POST['questions'] as $index => $question_data) {
                $question = array(
                    'question_text' => sanitize_textarea_field($question_data['question_text']),
                    'explanation' => sanitize_textarea_field($question_data['explanation']),
                    'answers' => array()
                );
                
                // Traiter les réponses
                if (isset($question_data['answers'])) {
                    foreach ($question_data['answers'] as $answer_index => $answer_text) {
                        $is_correct = isset($question_data['correct_answer']) && $question_data['correct_answer'] == $answer_index;
                        $question['answers'][] = array(
                            'text' => sanitize_text_field($answer_text),
                            'correct' => $is_correct
                        );
                    }
                }
                
                $questions[] = $question;
            }
            
            update_post_meta($post_id, '_biaquiz_questions', $questions);
        }
        
        // Sauvegarder les paramètres
        if (isset($_POST['biaquiz_difficulty'])) {
            update_post_meta($post_id, '_biaquiz_difficulty', sanitize_text_field($_POST['biaquiz_difficulty']));
        }
        
        if (isset($_POST['biaquiz_number'])) {
            update_post_meta($post_id, '_biaquiz_number', intval($_POST['biaquiz_number']));
        }
        
        $active = isset($_POST['biaquiz_active']) ? '1' : '0';
        update_post_meta($post_id, '_biaquiz_active', $active);
    }
    
    /**
     * Colonnes personnalisées pour la liste des quiz
     */
    public static function quiz_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['quiz_number'] = __('Numéro', 'biaquiz-core');
        $new_columns['quiz_category'] = __('Catégorie', 'biaquiz-core');
        $new_columns['quiz_difficulty'] = __('Difficulté', 'biaquiz-core');
        $new_columns['quiz_questions'] = __('Questions', 'biaquiz-core');
        $new_columns['quiz_active'] = __('Actif', 'biaquiz-core');
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }
    
    /**
     * Contenu des colonnes personnalisées
     */
    public static function quiz_column_content($column, $post_id) {
        switch ($column) {
            case 'quiz_number':
                $number = get_post_meta($post_id, '_biaquiz_number', true);
                echo $number ? $number : '-';
                break;
                
            case 'quiz_category':
                $terms = get_the_terms($post_id, 'quiz_category');
                if ($terms && !is_wp_error($terms)) {
                    $category_names = wp_list_pluck($terms, 'name');
                    echo implode(', ', $category_names);
                } else {
                    echo '-';
                }
                break;
                
            case 'quiz_difficulty':
                $difficulty = get_post_meta($post_id, '_biaquiz_difficulty', true);
                $difficulties = array(
                    'facile' => __('Facile', 'biaquiz-core'),
                    'moyen' => __('Moyen', 'biaquiz-core'),
                    'difficile' => __('Difficile', 'biaquiz-core')
                );
                echo isset($difficulties[$difficulty]) ? $difficulties[$difficulty] : '-';
                break;
                
            case 'quiz_questions':
                $questions = get_post_meta($post_id, '_biaquiz_questions', true);
                echo is_array($questions) ? count($questions) : '0';
                break;
                
            case 'quiz_active':
                $active = get_post_meta($post_id, '_biaquiz_active', true);
                if ($active === '1') {
                    echo '<span style="color: green;">✓ ' . __('Oui', 'biaquiz-core') . '</span>';
                } else {
                    echo '<span style="color: red;">✗ ' . __('Non', 'biaquiz-core') . '</span>';
                }
                break;
        }
    }
}

