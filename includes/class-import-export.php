<?php
/**
 * Système d'import/export pour BIAQuiz - Version Fonctionnelle
 */

if (!defined('ABSPATH')) {
    exit;
}

class BIAQuiz_Import_Export {
    
    /**
     * Initialiser les hooks
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('wp_ajax_biaquiz_import', array(__CLASS__, 'ajax_import'));
        add_action('wp_ajax_biaquiz_export', array(__CLASS__, 'ajax_export'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
    }
    
    /**
     * Ajouter le menu d'administration
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=biaquiz',
            __('Import/Export', 'biaquiz-core'),
            __('Import/Export', 'biaquiz-core'),
            'manage_options',
            'biaquiz-import-export',
            array(__CLASS__, 'admin_page')
        );
    }
    
    /**
     * Enregistrer les scripts et styles
     */
    public static function enqueue_scripts($hook) {
        if (strpos($hook, 'biaquiz-import-export') === false) {
            return;
        }
        
        wp_enqueue_script(
            'biaquiz-import-export',
            BIAQUIZ_PLUGIN_URL . 'assets/js/import-export.js',
            array('jquery'),
            BIAQUIZ_VERSION,
            true
        );
        
        wp_enqueue_style(
            'biaquiz-import-export',
            BIAQUIZ_PLUGIN_URL . 'assets/css/import-export.css',
            array(),
            BIAQUIZ_VERSION
        );
        
        wp_localize_script('biaquiz-import-export', 'biaquiz_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('biaquiz_import_export'),
            'strings' => array(
                'importing' => __('Import en cours...', 'biaquiz-core'),
                'exporting' => __('Export en cours...', 'biaquiz-core'),
                'success' => __('Opération réussie !', 'biaquiz-core'),
                'error' => __('Une erreur est survenue.', 'biaquiz-core'),
                'confirm_import' => __('Êtes-vous sûr de vouloir importer ce fichier ?', 'biaquiz-core')
            )
        ));
    }
    
    /**
     * Page d'administration
     */
    public static function admin_page() {
        $categories = get_terms(array(
            'taxonomy' => 'quiz_category',
            'hide_empty' => false
        ));
        ?>
        <div class="wrap">
            <h1><?php _e('Import/Export de Quiz', 'biaquiz-core'); ?></h1>
            
            <div class="biaquiz-import-export-container">
                <!-- Section Import -->
                <div class="postbox">
                    <h2 class="hndle"><?php _e('Importer des Quiz', 'biaquiz-core'); ?></h2>
                    <div class="inside">
                        <form id="import-form" enctype="multipart/form-data">
                            <?php wp_nonce_field('biaquiz_import_export', 'biaquiz_nonce'); ?>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="import_file"><?php _e('Fichier à importer', 'biaquiz-core'); ?></label>
                                    </th>
                                    <td>
                                        <input type="file" id="import_file" name="import_file" accept=".csv,.json" required>
                                        <p class="description">
                                            <?php _e('Formats acceptés : CSV, JSON (max 10 MB)', 'biaquiz-core'); ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="import_category"><?php _e('Catégorie', 'biaquiz-core'); ?></label>
                                    </th>
                                    <td>
                                        <select id="import_category" name="import_category" required>
                                            <option value=""><?php _e('Sélectionner une catégorie', 'biaquiz-core'); ?></option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo esc_attr($category->slug); ?>">
                                                    <?php echo esc_html($category->name); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Options', 'biaquiz-core'); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="update_existing" value="1">
                                            <?php _e('Mettre à jour les quiz existants', 'biaquiz-core'); ?>
                                        </label>
                                        <br>
                                        <label>
                                            <input type="checkbox" name="auto_publish" value="1" checked>
                                            <?php _e('Publier automatiquement', 'biaquiz-core'); ?>
                                        </label>
                                    </td>
                                </tr>
                            </table>
                            
                            <p class="submit">
                                <button type="submit" class="button button-primary">
                                    <?php _e('Importer', 'biaquiz-core'); ?>
                                </button>
                            </p>
                        </form>
                        
                        <div id="import-progress" style="display: none;">
                            <div class="progress-bar">
                                <div class="progress-fill"></div>
                            </div>
                            <p class="progress-text"></p>
                        </div>
                        
                        <div id="import-results"></div>
                    </div>
                </div>
                
                <!-- Section Export -->
                <div class="postbox">
                    <h2 class="hndle"><?php _e('Exporter des Quiz', 'biaquiz-core'); ?></h2>
                    <div class="inside">
                        <form id="export-form">
                            <?php wp_nonce_field('biaquiz_import_export', 'biaquiz_export_nonce'); ?>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="export_category"><?php _e('Catégorie', 'biaquiz-core'); ?></label>
                                    </th>
                                    <td>
                                        <select id="export_category" name="export_category">
                                            <option value=""><?php _e('Toutes les catégories', 'biaquiz-core'); ?></option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo esc_attr($category->slug); ?>">
                                                    <?php echo esc_html($category->name); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="export_format"><?php _e('Format', 'biaquiz-core'); ?></label>
                                    </th>
                                    <td>
                                        <select id="export_format" name="export_format">
                                            <option value="json"><?php _e('JSON', 'biaquiz-core'); ?></option>
                                            <option value="csv"><?php _e('CSV', 'biaquiz-core'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                            
                            <p class="submit">
                                <button type="submit" class="button button-primary">
                                    <?php _e('Exporter', 'biaquiz-core'); ?>
                                </button>
                            </p>
                        </form>
                    </div>
                </div>
                
                <!-- Section Templates -->
                <div class="postbox">
                    <h2 class="hndle"><?php _e('Templates et Documentation', 'biaquiz-core'); ?></h2>
                    <div class="inside">
                        <h3><?php _e('Format JSON', 'biaquiz-core'); ?></h3>
                        <p><?php _e('Structure du fichier JSON :', 'biaquiz-core'); ?></p>
                        <pre><code>{
  "title": "Titre du quiz",
  "description": "Description du quiz",
  "difficulty": "facile|moyen|difficile",
  "questions": [
    {
      "question_text": "Texte de la question",
      "explanation": "Explication (optionnel)",
      "answers": [
        {"text": "Réponse A", "correct": false},
        {"text": "Réponse B", "correct": true},
        {"text": "Réponse C", "correct": false},
        {"text": "Réponse D", "correct": false}
      ]
    }
  ]
}</code></pre>
                        
                        <h3><?php _e('Format CSV', 'biaquiz-core'); ?></h3>
                        <p><?php _e('Colonnes requises :', 'biaquiz-core'); ?></p>
                        <ul>
                            <li><code>title</code> - Titre du quiz</li>
                            <li><code>description</code> - Description du quiz</li>
                            <li><code>difficulty</code> - Difficulté (facile/moyen/difficile)</li>
                            <li><code>question_1</code> à <code>question_20</code> - Texte des questions</li>
                            <li><code>answer_1_a</code> à <code>answer_20_d</code> - Réponses A, B, C, D</li>
                            <li><code>correct_1</code> à <code>correct_20</code> - Lettre de la bonne réponse (a, b, c, d)</li>
                            <li><code>explanation_1</code> à <code>explanation_20</code> - Explications (optionnel)</li>
                        </ul>
                        
                        <p>
                            <a href="<?php echo admin_url('admin.php?page=biaquiz-import-export&action=download_template&format=json'); ?>" 
                               class="button"><?php _e('Télécharger Template JSON', 'biaquiz-core'); ?></a>
                            <a href="<?php echo admin_url('admin.php?page=biaquiz-import-export&action=download_template&format=csv'); ?>" 
                               class="button"><?php _e('Télécharger Template CSV', 'biaquiz-core'); ?></a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .biaquiz-import-export-container .postbox {
            margin-bottom: 20px;
        }
        .progress-bar {
            width: 100%;
            height: 20px;
            background-color: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-fill {
            height: 100%;
            background-color: #0073aa;
            width: 0%;
            transition: width 0.3s ease;
        }
        .progress-text {
            text-align: center;
            margin: 10px 0;
        }
        #import-results, #export-results {
            margin-top: 15px;
            padding: 10px;
            border-radius: 4px;
        }
        #import-results.success, #export-results.success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        #import-results.error, #export-results.error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        </style>
        <?php
        
        // Gérer le téléchargement des templates
        if (isset($_GET['action']) && $_GET['action'] === 'download_template') {
            self::download_template($_GET['format']);
        }
    }
    
    /**
     * AJAX Import
     */
    public static function ajax_import() {
        // Vérifier les permissions et le nonce
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['biaquiz_nonce'], 'biaquiz_import_export')) {
            wp_send_json_error(__('Permissions insuffisantes', 'biaquiz-core'));
        }
        
        // Augmenter les limites
        @ini_set('memory_limit', '256M');
        @ini_set('max_execution_time', 300);
        
        try {
            // Vérifier le fichier
            if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception(__('Erreur lors de l\'upload du fichier', 'biaquiz-core'));
            }
            
            $file = $_FILES['import_file'];
            $category_slug = sanitize_text_field($_POST['import_category']);
            $update_existing = !empty($_POST['update_existing']);
            $auto_publish = !empty($_POST['auto_publish']);
            
            // Vérifier la taille (10 MB max)
            if ($file['size'] > 10 * 1024 * 1024) {
                throw new Exception(__('Fichier trop volumineux (max 10 MB)', 'biaquiz-core'));
            }
            
            // Vérifier la catégorie
            $category = get_term_by('slug', $category_slug, 'quiz_category');
            if (!$category) {
                throw new Exception(__('Catégorie invalide', 'biaquiz-core'));
            }
            
            // Déterminer le format
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if ($extension === 'json') {
                $result = self::import_json($file['tmp_name'], $category, $update_existing, $auto_publish);
            } elseif ($extension === 'csv') {
                $result = self::import_csv($file['tmp_name'], $category, $update_existing, $auto_publish);
            } else {
                throw new Exception(__('Format non supporté. Utilisez CSV ou JSON.', 'biaquiz-core'));
            }
            
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Import JSON
     */
    private static function import_json($file_path, $category, $update_existing, $auto_publish) {
        $content = file_get_contents($file_path);
        if ($content === false) {
            throw new Exception(__('Impossible de lire le fichier', 'biaquiz-core'));
        }
        
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(__('Fichier JSON invalide: ', 'biaquiz-core') . json_last_error_msg());
        }
        
        // Si c'est un seul quiz, le convertir en tableau
        if (isset($data['title']) && isset($data['questions'])) {
            $data = array($data);
        }
        
        $imported = 0;
        $updated = 0;
        $errors = array();
        
        foreach ($data as $index => $quiz_data) {
            try {
                $result = self::create_quiz_from_data($quiz_data, $category, $update_existing, $auto_publish);
                if ($result['updated']) {
                    $updated++;
                } else {
                    $imported++;
                }
            } catch (Exception $e) {
                $errors[] = sprintf(__('Quiz %d: %s', 'biaquiz-core'), $index + 1, $e->getMessage());
            }
        }
        
        return array(
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors,
            'message' => sprintf(
                __('%d quiz importés, %d mis à jour', 'biaquiz-core'),
                $imported,
                $updated
            )
        );
    }
    
    /**
     * Import CSV
     */
    private static function import_csv($file_path, $category, $update_existing, $auto_publish) {
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            throw new Exception(__('Impossible de lire le fichier CSV', 'biaquiz-core'));
        }
        
        // Lire les en-têtes
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            throw new Exception(__('Fichier CSV vide ou en-têtes manquants', 'biaquiz-core'));
        }
        
        $imported = 0;
        $updated = 0;
        $errors = array();
        $line_number = 1;
        
        while (($row = fgetcsv($handle)) !== false) {
            $line_number++;
            
            if (empty(array_filter($row))) {
                continue; // Ignorer les lignes vides
            }
            
            try {
                $data = array_combine($headers, $row);
                if ($data === false) {
                    throw new Exception(__('Nombre de colonnes incorrect', 'biaquiz-core'));
                }
                
                $quiz_data = self::parse_csv_row($data);
                $result = self::create_quiz_from_data($quiz_data, $category, $update_existing, $auto_publish);
                
                if ($result['updated']) {
                    $updated++;
                } else {
                    $imported++;
                }
                
            } catch (Exception $e) {
                $errors[] = sprintf(__('Ligne %d: %s', 'biaquiz-core'), $line_number, $e->getMessage());
            }
        }
        
        fclose($handle);
        
        return array(
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors,
            'message' => sprintf(
                __('%d quiz importés, %d mis à jour', 'biaquiz-core'),
                $imported,
                $updated
            )
        );
    }
    
    /**
     * Parser une ligne CSV
     */
    private static function parse_csv_row($data) {
        $quiz_data = array(
            'title' => trim($data['title'] ?? ''),
            'description' => trim($data['description'] ?? ''),
            'difficulty' => trim($data['difficulty'] ?? 'moyen'),
            'questions' => array()
        );
        
        if (empty($quiz_data['title'])) {
            throw new Exception(__('Titre du quiz requis', 'biaquiz-core'));
        }
        
        // Parser les questions (jusqu'à 20)
        for ($i = 1; $i <= 20; $i++) {
            $question_key = "question_$i";
            $correct_key = "correct_$i";
            $explanation_key = "explanation_$i";
            
            if (empty($data[$question_key])) {
                continue;
            }
            
            $answers = array();
            $correct_answer = strtolower(trim($data[$correct_key] ?? ''));
            
            foreach (array('a', 'b', 'c', 'd') as $letter) {
                $answer_key = "answer_{$i}_{$letter}";
                $answer_text = trim($data[$answer_key] ?? '');
                
                if (!empty($answer_text)) {
                    $answers[] = array(
                        'text' => $answer_text,
                        'correct' => $correct_answer === $letter
                    );
                }
            }
            
            if (count($answers) !== 4) {
                throw new Exception(sprintf(__('Question %d: 4 réponses requises', 'biaquiz-core'), $i));
            }
            
            $quiz_data['questions'][] = array(
                'question_text' => trim($data[$question_key]),
                'explanation' => trim($data[$explanation_key] ?? ''),
                'answers' => $answers
            );
        }
        
        return $quiz_data;
    }
    
    /**
     * Créer un quiz à partir des données
     */
    private static function create_quiz_from_data($quiz_data, $category, $update_existing, $auto_publish) {
        // Valider les données
        if (empty($quiz_data['title'])) {
            throw new Exception(__('Titre du quiz requis', 'biaquiz-core'));
        }
        
        if (empty($quiz_data['questions'])) {
            throw new Exception(__('Le quiz doit contenir au moins une question', 'biaquiz-core'));
        }
        
        // Nettoyer les données
        $title = sanitize_text_field($quiz_data['title']);
        $description = sanitize_textarea_field($quiz_data['description'] ?? '');
        $difficulty = sanitize_text_field($quiz_data['difficulty'] ?? 'moyen');
        
        // Valider la difficulté
        if (!in_array($difficulty, array('facile', 'moyen', 'difficile'))) {
            $difficulty = 'moyen';
        }
        
        // Vérifier si le quiz existe
        $existing_quiz = null;
        if ($update_existing) {
            $existing_posts = get_posts(array(
                'post_type' => 'biaquiz',
                'title' => $title,
                'post_status' => 'any',
                'numberposts' => 1,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'quiz_category',
                        'field' => 'term_id',
                        'terms' => $category->term_id
                    )
                )
            ));
            
            if (!empty($existing_posts)) {
                $existing_quiz = $existing_posts[0];
            }
        }
        
        // Créer ou mettre à jour le quiz
        $post_data = array(
            'post_title' => $title,
            'post_content' => '',
            'post_excerpt' => $description,
            'post_type' => 'biaquiz',
            'post_status' => $auto_publish ? 'publish' : 'draft'
        );
        
        if ($existing_quiz) {
            $post_data['ID'] = $existing_quiz->ID;
            $quiz_id = wp_update_post($post_data);
            $updated = true;
        } else {
            $quiz_id = wp_insert_post($post_data);
            $updated = false;
        }
        
        if (is_wp_error($quiz_id) || !$quiz_id) {
            throw new Exception(__('Erreur lors de la création du quiz', 'biaquiz-core'));
        }
        
        // Assigner la catégorie
        wp_set_post_terms($quiz_id, array($category->term_id), 'quiz_category');
        
        // Sauvegarder les métadonnées
        update_post_meta($quiz_id, '_biaquiz_difficulty', $difficulty);
        update_post_meta($quiz_id, '_biaquiz_active', '1');
        
        // Obtenir le prochain numéro
        $quiz_number = self::get_next_quiz_number($category->slug);
        update_post_meta($quiz_id, '_biaquiz_number', $quiz_number);
        
        // Sauvegarder les questions
        $questions = array();
        foreach ($quiz_data['questions'] as $question) {
            $questions[] = array(
                'question_text' => sanitize_textarea_field($question['question_text']),
                'explanation' => sanitize_textarea_field($question['explanation'] ?? ''),
                'answers' => array_map(function($answer) {
                    return array(
                        'text' => sanitize_text_field($answer['text']),
                        'correct' => (bool) $answer['correct']
                    );
                }, $question['answers'])
            );
        }
        
        update_post_meta($quiz_id, '_biaquiz_questions', $questions);
        
        return array(
            'quiz_id' => $quiz_id,
            'updated' => $updated
        );
    }
    
    /**
     * Obtenir le prochain numéro de quiz
     */
    private static function get_next_quiz_number($category_slug) {
        global $wpdb;
        
        $max_number = $wpdb->get_var($wpdb->prepare("
            SELECT MAX(CAST(pm.meta_value AS UNSIGNED))
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
            WHERE pm.meta_key = '_biaquiz_number'
            AND p.post_type = 'biaquiz'
            AND t.slug = %s
        ", $category_slug));
        
        return ($max_number ? intval($max_number) + 1 : 1);
    }
    
    /**
     * AJAX Export
     */
    public static function ajax_export() {
        // Vérifier les permissions et le nonce
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['biaquiz_export_nonce'], 'biaquiz_import_export')) {
            wp_send_json_error(__('Permissions insuffisantes', 'biaquiz-core'));
        }
        
        try {
            $category_slug = sanitize_text_field($_POST['export_category'] ?? '');
            $format = sanitize_text_field($_POST['export_format'] ?? 'json');
            
            // Obtenir les quiz
            $args = array(
                'post_type' => 'biaquiz',
                'posts_per_page' => -1,
                'post_status' => array('publish', 'draft')
            );
            
            if ($category_slug) {
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'quiz_category',
                        'field' => 'slug',
                        'terms' => $category_slug
                    )
                );
            }
            
            $quizzes = get_posts($args);
            
            if (empty($quizzes)) {
                throw new Exception(__('Aucun quiz à exporter', 'biaquiz-core'));
            }
            
            if ($format === 'json') {
                $result = self::export_json($quizzes);
            } else {
                $result = self::export_csv($quizzes);
            }
            
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Export JSON
     */
    private static function export_json($quizzes) {
        $export_data = array();
        
        foreach ($quizzes as $quiz) {
            $questions = get_post_meta($quiz->ID, '_biaquiz_questions', true);
            
            $quiz_data = array(
                'title' => $quiz->post_title,
                'description' => $quiz->post_excerpt,
                'difficulty' => get_post_meta($quiz->ID, '_biaquiz_difficulty', true) ?: 'moyen',
                'questions' => $questions ?: array()
            );
            
            $export_data[] = $quiz_data;
        }
        
        $filename = 'biaquiz-export-' . date('Y-m-d-H-i-s') . '.json';
        $content = json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        return array(
            'filename' => $filename,
            'content' => $content,
            'mime_type' => 'application/json'
        );
    }
    
    /**
     * Export CSV
     */
    private static function export_csv($quizzes) {
        $csv_data = array();
        
        // En-têtes
        $headers = array('title', 'description', 'difficulty');
        for ($i = 1; $i <= 20; $i++) {
            $headers[] = "question_$i";
            $headers[] = "answer_{$i}_a";
            $headers[] = "answer_{$i}_b";
            $headers[] = "answer_{$i}_c";
            $headers[] = "answer_{$i}_d";
            $headers[] = "correct_$i";
            $headers[] = "explanation_$i";
        }
        $csv_data[] = $headers;
        
        foreach ($quizzes as $quiz) {
            $questions = get_post_meta($quiz->ID, '_biaquiz_questions', true) ?: array();
            
            $row = array(
                $quiz->post_title,
                $quiz->post_excerpt,
                get_post_meta($quiz->ID, '_biaquiz_difficulty', true) ?: 'moyen'
            );
            
            // Ajouter les questions (jusqu'à 20)
            for ($i = 0; $i < 20; $i++) {
                if (isset($questions[$i])) {
                    $question = $questions[$i];
                    $answers = $question['answers'] ?: array();
                    
                    $row[] = $question['question_text'] ?? '';
                    
                    $correct_letter = '';
                    for ($j = 0; $j < 4; $j++) {
                        if (isset($answers[$j])) {
                            $row[] = $answers[$j]['text'] ?? '';
                            if (!empty($answers[$j]['correct'])) {
                                $correct_letter = chr(97 + $j); // a, b, c, d
                            }
                        } else {
                            $row[] = '';
                        }
                    }
                    
                    $row[] = $correct_letter;
                    $row[] = $question['explanation'] ?? '';
                } else {
                    // Question vide
                    $row = array_merge($row, array_fill(0, 7, ''));
                }
            }
            
            $csv_data[] = $row;
        }
        
        // Générer le CSV
        $output = fopen('php://temp', 'r+');
        foreach ($csv_data as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);
        
        $filename = 'biaquiz-export-' . date('Y-m-d-H-i-s') . '.csv';
        
        return array(
            'filename' => $filename,
            'content' => $content,
            'mime_type' => 'text/csv'
        );
    }
    
    /**
     * Télécharger un template
     */
    private static function download_template($format) {
        if ($format === 'json') {
            $template = array(
                'title' => 'Exemple de Quiz BIA',
                'description' => 'Description du quiz d\'exemple',
                'difficulty' => 'moyen',
                'questions' => array(
                    array(
                        'question_text' => 'Quelle est la force qui s\'oppose au mouvement d\'un avion dans l\'air ?',
                        'explanation' => 'La traînée est la force qui s\'oppose au mouvement.',
                        'answers' => array(
                            array('text' => 'La portance', 'correct' => false),
                            array('text' => 'La traînée', 'correct' => true),
                            array('text' => 'Le poids', 'correct' => false),
                            array('text' => 'La poussée', 'correct' => false)
                        )
                    )
                )
            );
            
            $content = json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $filename = 'template-quiz-bia.json';
            $mime_type = 'application/json';
        } else {
            $content = "title,description,difficulty,question_1,answer_1_a,answer_1_b,answer_1_c,answer_1_d,correct_1,explanation_1\n";
            $content .= '"Exemple de Quiz BIA","Description du quiz d\'exemple","moyen","Quelle est la force qui s\'oppose au mouvement d\'un avion dans l\'air ?","La portance","La traînée","Le poids","La poussée","b","La traînée est la force qui s\'oppose au mouvement."';
            
            $filename = 'template-quiz-bia.csv';
            $mime_type = 'text/csv';
        }
        
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }
}

