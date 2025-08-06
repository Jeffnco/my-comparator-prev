<?php
/**
 * Plugin Name: WP Comparator
 * Plugin URI: https://example.com
 * Description: Plugin générique pour créer des comparateurs de produits/services avec interface d'administration complète
 * Version: 1.0.0
 * Author: Votre Nom
 * License: GPL v2 or later
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Définir les constantes du plugin
define('WP_COMPARATOR_VERSION', '1.0.0');
define('WP_COMPARATOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_COMPARATOR_PLUGIN_URL', plugin_dir_url(__FILE__));

// Classe principale du plugin
class WP_Comparator {
    
    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('WP_Comparator', 'uninstall'));
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    public function init() {
        // Charger les classes
        $this->load_dependencies();
        
        // Initialiser les composants
        if (class_exists('WP_Comparator_Database')) {
            new WP_Comparator_Database();
        }
        if (class_exists('WP_Comparator_Admin')) {
            new WP_Comparator_Admin();
        }
        if (class_exists('WP_Comparator_Frontend')) {
            new WP_Comparator_Frontend();
        }
        if (class_exists('WP_Comparator_Ajax')) {
            new WP_Comparator_Ajax();
        }
        if (class_exists('WP_Comparator_Pages')) {
            new WP_Comparator_Pages();
        }
    }
    
    private function load_dependencies() {
        $files = array(
            'includes/class-database.php',
            'includes/class-admin.php',
            'includes/class-frontend.php',
            'includes/class-ajax.php',
            'includes/class-pages.php',
            'includes/functions.php'
        );
        
        foreach ($files as $file) {
            $file_path = WP_COMPARATOR_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
    
    public function activate() {
        // Charger les dépendances d'abord
        $this->load_dependencies();
        
        // Ajouter les capacités par défaut
        $this->add_capabilities();
        
        // Créer les tables de base de données
        if (class_exists('WP_Comparator_Database')) {
            $database = new WP_Comparator_Database();
            $database->create_tables();
        }
        
        // Ajouter les règles de réécriture
        if (class_exists('WP_Comparator_Pages')) {
            $pages = new WP_Comparator_Pages();
            $pages->add_rewrite_rules();
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Marquer pour flush des règles de réécriture
        update_option('wp_comparator_flush_rewrite_rules', true);
    }
    
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private function add_capabilities() {
        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('manage_comparator');
            $role->add_cap('edit_comparator_items');
            $role->add_cap('delete_comparator_items');
        }
    }
    
    /**
     * Nettoyage complet lors de la désinstallation
     */
    public static function uninstall() {
        global $wpdb;
        
        // Supprimer TOUTES les tables du plugin
        $tables = array(
            $wpdb->prefix . 'comparator_field_descriptions',
            $wpdb->prefix . 'comparator_filter_values',
            $wpdb->prefix . 'comparator_values',
            $wpdb->prefix . 'comparator_items',
            $wpdb->prefix . 'comparator_filters',
            $wpdb->prefix . 'comparator_fields',
            $wpdb->prefix . 'comparator_categories', // Ancienne table
            $wpdb->prefix . 'comparator_types'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Supprimer toutes les options du plugin
        delete_option('wp_comparator_version');
        delete_option('wp_comparator_tables_created');
        delete_option('wp_comparator_settings');
        delete_option('wp_comparator_default_columns');
        delete_option('wp_comparator_show_filters');
        delete_option('wp_comparator_max_comparison');
        delete_option('wp_comparator_primary_color');
        delete_option('wp_comparator_secondary_color');
        
        // Supprimer les capacités
        $role = get_role('administrator');
        if ($role) {
            $role->remove_cap('manage_comparator');
            $role->remove_cap('edit_comparator_items');
            $role->remove_cap('delete_comparator_items');
        }
        
        // Nettoyer le cache
        wp_cache_flush();
    }
}

// Initialiser le plugin
new WP_Comparator();