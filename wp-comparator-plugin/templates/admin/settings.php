<div class="wrap">
    <h1>Paramètres WP Comparator</h1>
    
    <div class="wp-comparator-admin-content">
        <div class="admin-section">
            <h2>Paramètres généraux</h2>
            <form method="post" action="options.php">
                <?php settings_fields('wp_comparator_settings'); ?>
                <?php do_settings_sections('wp_comparator_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="default_columns">Nombre de colonnes par défaut</label>
                        </th>
                        <td>
                            <select id="default_columns" name="wp_comparator_default_columns">
                                <option value="2" <?php selected(get_option('wp_comparator_default_columns', '3'), '2'); ?>>2 colonnes</option>
                                <option value="3" <?php selected(get_option('wp_comparator_default_columns', '3'), '3'); ?>>3 colonnes</option>
                                <option value="4" <?php selected(get_option('wp_comparator_default_columns', '3'), '4'); ?>>4 colonnes</option>
                            </select>
                            <p class="description">Nombre de colonnes pour l'affichage en grille</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="show_filters">Afficher les filtres par défaut</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="show_filters" name="wp_comparator_show_filters" value="1" <?php checked(get_option('wp_comparator_show_filters', '1'), '1'); ?>>
                                Afficher les filtres sur les pages de comparaison
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="max_comparison">Nombre maximum d'éléments à comparer</label>
                        </th>
                        <td>
                            <input type="number" id="max_comparison" name="wp_comparator_max_comparison" value="<?php echo esc_attr(get_option('wp_comparator_max_comparison', '2')); ?>" min="2" max="5" class="small-text">
                            <p class="description">Entre 2 et 5 éléments</p>
                        </td>
                    </tr>
                </table>
                
                <h3>Couleurs et apparence</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="primary_color">Couleur principale</label>
                        </th>
                        <td>
                            <input type="color" id="primary_color" name="wp_comparator_primary_color" value="<?php echo esc_attr(get_option('wp_comparator_primary_color', '#0073aa')); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="secondary_color">Couleur secondaire</label>
                        </th>
                        <td>
                            <input type="color" id="secondary_color" name="wp_comparator_secondary_color" value="<?php echo esc_attr(get_option('wp_comparator_secondary_color', '#666666')); ?>">
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        
        <div class="admin-section">
            <h2>Outils</h2>
            
            <h3>Export / Import</h3>
            <p>Exportez ou importez vos données de comparaison.</p>
            
            <p>
                <a href="<?php echo admin_url('admin.php?page=wp-comparator-settings&action=export'); ?>" class="button">
                    Exporter les données
                </a>
            </p>
            
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('wp_comparator_import', '_wpnonce'); ?>
                <input type="hidden" name="wp_comparator_action" value="import">
                <p>
                    <input type="file" name="import_file" accept=".json">
                    <input type="submit" class="button" value="Importer les données">
                </p>
            </form>
            
            <h3>Réinitialisation</h3>
            <p><strong>Attention :</strong> Cette action supprimera toutes les données du comparateur.</p>
            <p>
                <button class="button button-link-delete" onclick="resetData()">
                    Réinitialiser toutes les données
                </button>
            </p>
        </div>
        
        <div class="admin-section">
            <h2>Informations système</h2>
            
            <table class="widefat">
                <tr>
                    <td><strong>Version du plugin</strong></td>
                    <td><?php echo WP_COMPARATOR_VERSION; ?></td>
                </tr>
                <tr>
                    <td><strong>Version WordPress</strong></td>
                    <td><?php echo get_bloginfo('version'); ?></td>
                </tr>
                <tr>
                    <td><strong>Version PHP</strong></td>
                    <td><?php echo PHP_VERSION; ?></td>
                </tr>
                <tr>
                    <td><strong>Base de données</strong></td>
                    <td><?php global $wpdb; echo $wpdb->db_version(); ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<script>
function resetData() {
    if (confirm('Êtes-vous absolument sûr de vouloir supprimer toutes les données ? Cette action est irréversible.')) {
        if (confirm('Dernière confirmation : toutes les données seront perdues !')) {
            // Implémentation de la réinitialisation
            console.log('Réinitialisation des données...');
        }
    }
}
</script>