<?php
/**
 * Plugin Name: BIAQuiz Core
 * Plugin URI: https://acme-biaquiz.com
 * Description: Plugin complet pour créer et gérer des quiz BIA (Brevet d'Initiation à l'Aéronautique) avec import/export CSV et JSON.
 * Version: 2.0.0
 * Author: ACME
 * License: GPL v2 or later
 * Text Domain: biaquiz-core
 * Domain Path: /languages
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Définir les constantes du plugin
define('BIAQUIZ_VERSION', '2.0.0');
define('BIAQUIZ_PLUGIN_FILE', __FILE__);
define('BIAQUIZ_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BIAQUIZ_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BIAQUIZ_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Classe principale du plugin BIAQuiz
 */
class BIAQuiz_Core {
    
    /**
     * Instance unique du plugin
     */
    private static $instance = null;
    
    /**
     * Obtenir l'instance unique
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructeur privé
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialiser les hooks WordPress
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'init'));
        add_action('init', array($this, 'load_textdomain'));
        
        // Hooks d'activation/désactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Charger les dépendances
     */
    private function load_dependencies() {
        // Charger les classes principales
        require_once BIAQUIZ_PLUGIN_DIR . 'includes/class-post-types.php';
        require_once BIAQUIZ_PLUGIN_DIR . 'includes/class-taxonomies.php';
        require_once BIAQUIZ_PLUGIN_DIR . 'includes/class-admin.php';
        require_once BIAQUIZ_PLUGIN_DIR . 'includes/class-import-export.php';
        require_once BIAQUIZ_PLUGIN_DIR . 'includes/class-quiz-handler.php';
        require_once BIAQUIZ_PLUGIN_DIR . 'includes/class-frontend.php';
    }
    
    /**
     * Initialiser le plugin
     */
    public function init() {
        // Vérifier les prérequis
        if (!$this->check_requirements()) {
            return;
        }
        
        // Initialiser les composants
        BIAQuiz_Post_Types::init();
        BIAQuiz_Taxonomies::init();
        BIAQuiz_Admin::init();
        BIAQuiz_Import_Export::init();
        BIAQuiz_Quiz_Handler::init();
        BIAQuiz_Frontend::init();
        
        // Hook pour permettre aux autres plugins d'étendre
        do_action('biaquiz_core_loaded');
    }
    
    /**
     * Vérifier les prérequis
     */
    private function check_requirements() {
        // Vérifier la version de WordPress
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                echo '<strong>BIAQuiz Core:</strong> WordPress 5.0 ou supérieur est requis.';
                echo '</p></div>';
            });
            return false;
        }
        
        // Vérifier la version de PHP
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                echo '<strong>BIAQuiz Core:</strong> PHP 7.4 ou supérieur est requis.';
                echo '</p></div>';
            });
            return false;
        }
        
        return true;
    }
    
    /**
     * Charger les traductions
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'biaquiz-core',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }
    
    /**
     * Activation du plugin
     */
    public function activate() {
        // Créer les tables personnalisées si nécessaire
        $this->create_tables();
        
        // Créer les catégories par défaut
        $this->create_default_categories();
        
        // Flush les règles de réécriture
        flush_rewrite_rules();
        
        // Marquer la version
        update_option('biaquiz_core_version', BIAQUIZ_VERSION);
        update_option('biaquiz_core_activated', time());
    }
    
    /**
     * Désactivation du plugin
     */
    public function deactivate() {
        // Flush les règles de réécriture
        flush_rewrite_rules();
    }
    
    /**
     * Créer les tables personnalisées
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table pour les statistiques de quiz
        $table_stats = $wpdb->prefix . 'biaquiz_stats';
        
        $sql = "CREATE TABLE $table_stats (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            quiz_id bigint(20) NOT NULL,
            user_ip varchar(45) NOT NULL,
            score int(11) NOT NULL,
            total_questions int(11) NOT NULL,
            time_taken int(11) NOT NULL,
            completed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY quiz_id (quiz_id),
            KEY completed_at (completed_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Créer les catégories par défaut
     */
    private function create_default_categories() {
        $categories = array(
            'aerodynamique' => 'Aérodynamique et mécanique du vol',
            'aeronefs' => 'Connaissance des aéronefs',
            'meteorologie' => 'Météorologie',
            'navigation' => 'Navigation, règlementation et sécurité des vols',
            'histoire' => 'Histoire de l\'aéronautique et de l\'espace',
            'anglais' => 'Anglais aéronautique'
        );
        
        foreach ($categories as $slug => $name) {
            if (!term_exists($slug, 'quiz_category')) {
                wp_insert_term($name, 'quiz_category', array(
                    'slug' => $slug
                ));
            }
        }
    }
}

// Initialiser le plugin
function biaquiz_core() {
    return BIAQuiz_Core::get_instance();
}

// Démarrer le plugin
biaquiz_core();

