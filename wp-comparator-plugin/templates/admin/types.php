<div class="wrap">
    <h1>Types de comparateurs</h1>
    
    <?php if (isset($_GET['message']) && $_GET['message'] == 'type_added'): ?>
        <div class="notice notice-success is-dismissible">
            <p>Type ajouté avec succès !</p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['message']) && $_GET['message'] == 'type_updated'): ?>
        <div class="notice notice-success is-dismissible">
            <p>Type modifié avec succès !</p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error']) && $_GET['error'] == 'type_not_added'): ?>
        <div class="notice notice-error is-dismissible">
            <p>Erreur lors de l'ajout du type.</p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error']) && $_GET['error'] == 'table_not_exists'): ?>
        <div class="notice notice-error is-dismissible">
            <p>Erreur : Les tables de base de données n'existent pas. Veuillez désactiver puis réactiver le plugin.</p>
        </div>
    <?php endif; ?>
    
    <div class="wp-comparator-admin-content">
        <?php 
        // Gestion de l'édition d'un type
        $edit_type_id = isset($_GET['edit_type']) ? intval($_GET['edit_type']) : 0;
        $edit_type = null;
        if ($edit_type_id && isset($types)) {
            foreach ($types as $type) {
                if ($type->id == $edit_type_id) {
                    $edit_type = $type;
                    break;
                }
            }
        }
        ?>
        
        <div class="admin-section">
            <h2><?php echo $edit_type ? 'Modifier le type' : 'Ajouter un nouveau type'; ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field($edit_type ? 'wp_comparator_update_type' : 'wp_comparator_add_type', '_wpnonce'); ?>
                <input type="hidden" name="wp_comparator_action" value="<?php echo $edit_type ? 'update_type' : 'add_type'; ?>">
                <?php if ($edit_type): ?>
                    <input type="hidden" name="type_id" value="<?php echo $edit_type->id; ?>">
                <?php endif; ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="name">Nom du type</label>
                        </th>
                        <td>
                            <input type="text" id="name" name="name" class="regular-text" value="<?php echo $edit_type ? esc_attr($edit_type->name) : ''; ?>" required>
                            <p class="description">Ex: Assurance Prévoyance, Produits Tech, Services Web</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="slug">Slug (optionnel)</label>
                        </th>
                        <td>
                            <input type="text" id="slug" name="slug" class="regular-text" value="<?php echo $edit_type ? esc_attr($edit_type->slug) : ''; ?>">
                            <p class="description">Laissez vide pour générer automatiquement</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="description">Description</label>
                        </th>
                        <td>
                            <textarea id="description" name="description" rows="3" class="large-text"><?php echo $edit_type ? esc_textarea($edit_type->description) : ''; ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="url_prefix">Préfixe URL complet</label>
                        </th>
                        <td>
                            <input type="text" id="url_prefix" name="url_prefix" class="regular-text" value="<?php echo $edit_type ? esc_attr($edit_type->url_prefix) : ''; ?>" placeholder="comparez-les-prevoyances">
                            <p class="description">
                                Préfixe complet pour les URLs de comparaison.<br>
                                Ex: "comparez-les-prevoyances" donnera "comparez-les-prevoyances-item1-et-item2"<br>
                                Si vide, utilisera "comparez-<?php echo $edit_type ? esc_attr($edit_type->slug) : 'slug-du-type'; ?>"
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="intro_text">Texte d'introduction pour la comparaison</label>
                        </th>
                        <td>
                            <textarea id="intro_text" name="intro_text" rows="4" class="large-text" placeholder="Ex: Consultez ci-dessous les garanties du contrat &quot;{contrat1}&quot; de &quot;{assureur1}&quot; et &quot;{contrat2}&quot; de &quot;{assureur2}&quot;."><?php echo $edit_type ? esc_textarea($edit_type->intro_text) : ''; ?></textarea>
                            <p class="description">
                                Variables disponibles : {contrat1}, {assureur1}, {contrat2}, {assureur2}<br>
                                Ce texte s'affichera en haut des pages de comparaison.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="custom_title">Titre personnalisé de la page de comparaison</label>
                        </th>
                        <td>
                            <textarea id="custom_title" name="custom_title" rows="2" class="large-text" placeholder="Ex: Comparaison {contrat1} vs {contrat2} - {assureur1} et {assureur2}"><?php echo ($edit_type && isset($edit_type->custom_title)) ? esc_textarea($edit_type->custom_title) : ''; ?></textarea>
                            <p class="description">
                                Variables disponibles : {contrat1}, {contrat2}, {assureur1}, {assureur2}, {name1}, {name2}, {version1}, {version2}<br>
                                Laissez vide pour utiliser le titre par défaut.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="meta_title">Meta Title (SEO)</label>
                        </th>
                        <td>
                            <textarea id="meta_title" name="meta_title" rows="2" class="large-text" placeholder="Ex: {contrat1} vs {contrat2} : Comparaison détaillée | Votre Site"><?php echo ($edit_type && isset($edit_type->meta_title)) ? esc_textarea($edit_type->meta_title) : ''; ?></textarea>
                            <p class="description">
                                Balise &lt;title&gt; pour le SEO. Variables disponibles : {contrat1}, {contrat2}, {assureur1}, {assureur2}, etc.<br>
                                Laissez vide pour utiliser le titre de la page.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="meta_description">Meta Description (SEO)</label>
                        </th>
                        <td>
                            <textarea id="meta_description" name="meta_description" rows="3" class="large-text" placeholder="Ex: Comparez en détail les contrats {contrat1} de {assureur1} et {contrat2} de {assureur2}. Analyse complète des garanties et conditions."><?php echo ($edit_type && isset($edit_type->meta_description)) ? esc_textarea($edit_type->meta_description) : ''; ?></textarea>
                            <p class="description">
                                Description pour les moteurs de recherche (155-160 caractères recommandés).<br>
                                Variables disponibles : {contrat1}, {contrat2}, {assureur1}, {assureur2}, etc.
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php echo $edit_type ? 'Modifier le type' : 'Ajouter le type'; ?>">
                    <?php if ($edit_type): ?>
                        <a href="?page=wp-comparator-types" class="button">Annuler</a>
                    <?php endif; ?>
                </p>
            </form>
            
            <div style="margin-top: 20px; padding: 15px; background: #f0f0f1; border-left: 4px solid #72aee6;">
                <h4>🔧 Debug Info</h4>
                <p><strong>Plugin activé :</strong> 
                <?php echo get_option('wp_comparator_tables_created') ? '✅ Oui' : '❌ Non'; ?>
                </p>
                <p><strong>Table types existe :</strong> 
                <?php 
                global $wpdb;
                $table_types = $wpdb->prefix . 'comparator_types';
                echo ($wpdb->get_var("SHOW TABLES LIKE '$table_types'") == $table_types) ? '✅ Oui' : '❌ Non';
                ?>
                </p>
                <p><strong>Dernière erreur MySQL :</strong> 
                <?php echo $wpdb->last_error ? $wpdb->last_error : 'Aucune'; ?>
                </p>
                <p><strong>Nombre de types :</strong> 
                <?php 
                if ($wpdb->get_var("SHOW TABLES LIKE '$table_types'") == $table_types) {
                    echo $wpdb->get_var("SELECT COUNT(*) FROM $table_types");
                } else {
                    echo 'Table inexistante';
                }
                ?>
                </p>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=wp-comparator-types&force_create_tables=1'); ?>" class="button">
                        🔧 Forcer la création des tables
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=wp-comparator-types&check_tables=1'); ?>" class="button">
                        🔍 Vérifier les tables
                    </a>
                </p>
            </div>
        </div>
        
        <div class="admin-section">
            <h2>Types existants</h2>
            
            <?php if (!isset($types) || empty($types)): ?>
                <p>Aucun type de comparateur créé pour le moment.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Slug</th>
                            <th>Description</th>
                            <th>Date de création</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($types as $type): ?>
                            <tr>
                                <td><strong><?php echo esc_html($type->name); ?></strong></td>
                                <td><code><?php echo esc_html($type->slug); ?></code></td>
                                <td><?php echo esc_html($type->description); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($type->created_at)); ?></td>
                                <td>
                                    <a href="?page=wp-comparator-types&edit_type=<?php echo $type->id; ?>" class="button button-small">
                                        Modifier
                                    </a>
                                    <a href="<?php echo admin_url('admin.php?page=wp-comparator-categories&type_id=' . $type->id); ?>" class="button button-small">
                                        Catégories
                                    </a>
                                    <a href="<?php echo admin_url('admin.php?page=wp-comparator-items&type_id=' . $type->id); ?>" class="button button-small">
                                        Éléments
                                    </a>
                                    <button class="button button-small button-link-delete" onclick="deleteType(<?php echo $type->id; ?>)">
                                        Supprimer
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.wp-comparator-admin-content {
    margin-top: 20px;
}

.admin-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.admin-section h2 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 18px;
}

.form-table th {
    width: 200px;
}

.button-small {
    margin-right: 5px;
}
</style>

<script>
function deleteType(typeId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce type ? Toutes les données associées seront perdues.')) {
        // Implémentation de la suppression via AJAX
        console.log('Suppression du type:', typeId);
    }
}
</script>