<?php
/**
 * Fichier de désinstallation du plugin WP Comparator
 * Exécuté quand le plugin est supprimé via l'admin WordPress
 */

// Empêcher l'accès direct
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Supprimer TOUTES les tables du plugin (anciennes et nouvelles)
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
$options = array(
    'wp_comparator_version',
    'wp_comparator_tables_created',
    'wp_comparator_settings',
    'wp_comparator_default_columns',
    'wp_comparator_show_filters',
    'wp_comparator_max_comparison',
    'wp_comparator_primary_color',
    'wp_comparator_secondary_color'
);

foreach ($options as $option) {
    delete_option($option);
}

// Supprimer les capacités
$role = get_role('administrator');
if ($role) {
    $role->remove_cap('manage_comparator');
    $role->remove_cap('edit_comparator_items');
    $role->remove_cap('delete_comparator_items');
}

// Nettoyer le cache
wp_cache_flush();