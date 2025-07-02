<?php
/**
 * Gestionnaire de quiz pour BIAQuiz
 */

if (!defined('ABSPATH')) {
    exit;
}

class BIAQuiz_Quiz_Handler {
    
    /**
     * Initialiser les hooks
     */
    public static function init() {
        add_action('wp_ajax_biaquiz_submit', array(__CLASS__, 'ajax_submit_quiz'));
        add_action('wp_ajax_nopriv_biaquiz_submit', array(__CLASS__, 'ajax_submit_quiz'));
        add_action('wp_ajax_biaquiz_get_quiz', array(__CLASS__, 'ajax_get_quiz'));
        add_action('wp_ajax_nopriv_biaquiz_get_quiz', array(__CLASS__, 'ajax_get_quiz'));
    }
    
    /**
     * AJAX - Obtenir un quiz
     */
    public static function ajax_get_quiz() {
        $quiz_id = intval($_POST['quiz_id'] ?? 0);
        
        if (!$quiz_id) {
            wp_send_json_error(__('ID de quiz invalide', 'biaquiz-core'));
        }
        
        $quiz = get_post($quiz_id);
        if (!$quiz || $quiz->post_type !== 'biaquiz' || $quiz->post_status !== 'publish') {
            wp_send_json_error(__('Quiz non trouvé', 'biaquiz-core'));
        }
        
        // Vérifier si le quiz est actif
        $active = get_post_meta($quiz_id, '_biaquiz_active', true);
        if ($active !== '1') {
            wp_send_json_error(__('Quiz non disponible', 'biaquiz-core'));
        }
        
        $questions = get_post_meta($quiz_id, '_biaquiz_questions', true);
        if (!is_array($questions) || empty($questions)) {
            wp_send_json_error(__('Aucune question trouvée', 'biaquiz-core'));
        }
        
        // Préparer les questions (sans les bonnes réponses)
        $quiz_questions = array();
        foreach ($questions as $index => $question) {
            $answers = array();
            foreach ($question['answers'] as $answer) {
                $answers[] = array(
                    'text' => $answer['text']
                    // Ne pas inclure 'correct' côté client
                );
            }
            
            $quiz_questions[] = array(
                'question_text' => $question['question_text'],
                'answers' => $answers
            );
        }
        
        $quiz_data = array(
            'id' => $quiz_id,
            'title' => $quiz->post_title,
            'description' => $quiz->post_excerpt,
            'difficulty' => get_post_meta($quiz_id, '_biaquiz_difficulty', true),
            'questions' => $quiz_questions,
            'total_questions' => count($quiz_questions)
        );
        
        wp_send_json_success($quiz_data);
    }
    
    /**
     * AJAX - Soumettre un quiz
     */
    public static function ajax_submit_quiz() {
        $quiz_id = intval($_POST['quiz_id'] ?? 0);
        $answers = $_POST['answers'] ?? array();
        $time_taken = intval($_POST['time_taken'] ?? 0);
        
        if (!$quiz_id) {
            wp_send_json_error(__('ID de quiz invalide', 'biaquiz-core'));
        }
        
        $quiz = get_post($quiz_id);
        if (!$quiz || $quiz->post_type !== 'biaquiz') {
            wp_send_json_error(__('Quiz non trouvé', 'biaquiz-core'));
        }
        
        $questions = get_post_meta($quiz_id, '_biaquiz_questions', true);
        if (!is_array($questions) || empty($questions)) {
            wp_send_json_error(__('Aucune question trouvée', 'biaquiz-core'));
        }
        
        // Calculer le score
        $score = 0;
        $total_questions = count($questions);
        $results = array();
        
        foreach ($questions as $index => $question) {
            $user_answer = intval($answers[$index] ?? -1);
            $correct_answer = -1;
            
            // Trouver la bonne réponse
            foreach ($question['answers'] as $answer_index => $answer) {
                if (!empty($answer['correct'])) {
                    $correct_answer = $answer_index;
                    break;
                }
            }
            
            $is_correct = ($user_answer === $correct_answer);
            if ($is_correct) {
                $score++;
            }
            
            $results[] = array(
                'question_index' => $index,
                'user_answer' => $user_answer,
                'correct_answer' => $correct_answer,
                'is_correct' => $is_correct,
                'explanation' => $question['explanation'] ?? ''
            );
        }
        
        $percentage = round(($score / $total_questions) * 100, 1);
        
        // Sauvegarder les statistiques
        self::save_quiz_stats($quiz_id, $score, $total_questions, $time_taken);
        
        // Déterminer les questions incorrectes pour la répétition
        $incorrect_questions = array();
        foreach ($results as $result) {
            if (!$result['is_correct']) {
                $incorrect_questions[] = $result['question_index'];
            }
        }
        
        $response = array(
            'score' => $score,
            'total_questions' => $total_questions,
            'percentage' => $percentage,
            'time_taken' => $time_taken,
            'results' => $results,
            'incorrect_questions' => $incorrect_questions,
            'perfect_score' => ($score === $total_questions)
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Sauvegarder les statistiques de quiz
     */
    private static function save_quiz_stats($quiz_id, $score, $total_questions, $time_taken) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'biaquiz_stats';
        $user_ip = self::get_user_ip();
        
        $wpdb->insert(
            $table_name,
            array(
                'quiz_id' => $quiz_id,
                'user_ip' => $user_ip,
                'score' => $score,
                'total_questions' => $total_questions,
                'time_taken' => $time_taken,
                'completed_at' => current_time('mysql')
            ),
            array('%d', '%s', '%d', '%d', '%d', '%s')
        );
    }
    
    /**
     * Obtenir l'IP de l'utilisateur
     */
    private static function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwarded = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($forwarded[0]);
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '';
        }
    }
    
    /**
     * Obtenir les statistiques d'un quiz
     */
    public static function get_quiz_stats($quiz_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'biaquiz_stats';
        
        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_attempts,
                AVG(score) as avg_score,
                AVG(percentage) as avg_percentage,
                AVG(time_taken) as avg_time,
                MAX(score) as best_score,
                MIN(score) as worst_score
            FROM (
                SELECT 
                    score,
                    (score * 100.0 / total_questions) as percentage,
                    time_taken
                FROM $table_name 
                WHERE quiz_id = %d
            ) as quiz_stats
        ", $quiz_id));
        
        return $stats;
    }
    
    /**
     * Obtenir les statistiques globales
     */
    public static function get_global_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'biaquiz_stats';
        
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_attempts,
                COUNT(DISTINCT quiz_id) as unique_quizzes,
                AVG(score * 100.0 / total_questions) as avg_percentage,
                AVG(time_taken) as avg_time
            FROM $table_name
        ");
        
        return $stats;
    }
    
    /**
     * Obtenir les quiz les plus populaires
     */
    public static function get_popular_quizzes($limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'biaquiz_stats';
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                s.quiz_id,
                p.post_title,
                COUNT(*) as attempt_count,
                AVG(s.score * 100.0 / s.total_questions) as avg_percentage
            FROM $table_name s
            INNER JOIN {$wpdb->posts} p ON s.quiz_id = p.ID
            WHERE p.post_status = 'publish'
            GROUP BY s.quiz_id, p.post_title
            ORDER BY attempt_count DESC
            LIMIT %d
        ", $limit));
        
        return $results;
    }
    
    /**
     * Créer un quiz de répétition avec les questions incorrectes
     */
    public static function create_retry_quiz($quiz_id, $incorrect_questions) {
        if (empty($incorrect_questions)) {
            return false;
        }
        
        $questions = get_post_meta($quiz_id, '_biaquiz_questions', true);
        if (!is_array($questions)) {
            return false;
        }
        
        $retry_questions = array();
        foreach ($incorrect_questions as $index) {
            if (isset($questions[$index])) {
                $retry_questions[] = $questions[$index];
            }
        }
        
        return $retry_questions;
    }
    
    /**
     * Valider les réponses d'un quiz
     */
    public static function validate_quiz_answers($quiz_id, $user_answers) {
        $questions = get_post_meta($quiz_id, '_biaquiz_questions', true);
        if (!is_array($questions)) {
            return false;
        }
        
        $validation_results = array();
        
        foreach ($questions as $index => $question) {
            $user_answer = $user_answers[$index] ?? null;
            $correct_answer = null;
            
            // Trouver la bonne réponse
            foreach ($question['answers'] as $answer_index => $answer) {
                if (!empty($answer['correct'])) {
                    $correct_answer = $answer_index;
                    break;
                }
            }
            
            $validation_results[] = array(
                'question_index' => $index,
                'user_answer' => $user_answer,
                'correct_answer' => $correct_answer,
                'is_correct' => ($user_answer === $correct_answer),
                'question_text' => $question['question_text'],
                'explanation' => $question['explanation'] ?? ''
            );
        }
        
        return $validation_results;
    }
}

