<div class="wrap">
    <h1>Éléments à comparer</h1>
    
    <?php if (isset($_GET['message']) && $_GET['message'] == 'item_added'): ?>
        <div class="notice notice-success is-dismissible">
            <p>Élément ajouté avec succès !</p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['message']) && $_GET['message'] == 'item_data_saved'): ?>
        <div class="notice notice-success is-dismissible">
            <p>Données de l'élément sauvegardées avec succès !</p>
        </div>
    <?php endif; ?>
    
    <div class="wp-comparator-admin-content">
        <!-- Sélecteur de type -->
        <div class="admin-section">
            <h2>Sélectionner un type de comparateur</h2>
            <form method="get" action="">
                <input type="hidden" name="page" value="wp-comparator-items">
                <select name="type_id" onchange="this.form.submit()">
                    <option value="">-- Choisir un type --</option>
                    <?php if (isset($types) && is_array($types)): ?>
                        <?php foreach ($types as $type): ?>
                        <option value="<?php echo $type->id; ?>" <?php selected($selected_type, $type->id); ?>>
                            <?php echo esc_html($type->name); ?>
                        </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </form>
        </div>
        
        <?php if (isset($selected_type) && $selected_type): ?>
            <!-- Formulaire d'ajout d'élément -->
            <div class="admin-section">
                <h2>Ajouter un nouvel élément</h2>
                <form method="post" action="">
                    <?php wp_nonce_field('wp_comparator_add_item', '_wpnonce'); ?>
                    <input type="hidden" name="wp_comparator_action" value="add_item">
                    <input type="hidden" name="type_id" value="<?php echo $selected_type; ?>">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="name">Nom de l'élément</label>
                            </th>
                            <td>
                                <input type="text" id="name" name="name" class="regular-text" required>
                                <p class="description">Ex: Aviva Senseo, April Prévoyance</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="slug">Slug (optionnel)</label>
                            </th>
                            <td>
                                <input type="text" id="slug" name="slug" class="regular-text">
                                <p class="description">Laissez vide pour générer automatiquement</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="description">Description</label>
                            </th>
                            <td>
                                <textarea id="description" name="description" rows="4" class="large-text"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="logo_url">URL du logo</label>
                            </th>
                            <td>
                                <input type="url" id="logo_url" name="logo_url" class="regular-text">
                                <button type="button" class="button" onclick="selectMedia('logo_url')">Choisir une image</button>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="website_url">Site web</label>
                            </th>
                            <td>
                                <input type="url" id="website_url" name="website_url" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="document_url">URL du document (PDF)</label>
                            </th>
                            <td>
                                <input type="url" id="document_url" name="document_url" class="regular-text">
                                <button type="button" class="button" onclick="selectMedia('document_url')">Choisir un fichier</button>
                                <p class="description">Conditions générales, documentation, etc.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Statut</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="is_active" value="1" checked>
                                    Élément actif (visible sur le site)
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="sort_order">Ordre d'affichage</label>
                            </th>
                            <td>
                                <input type="number" id="sort_order" name="sort_order" value="0" min="0" class="small-text">
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" class="button-primary" value="Ajouter l'élément">
                    </p>
                </form>
            </div>
            
            <!-- Liste des éléments existants -->
            <div class="admin-section">
                <h2>Éléments existants</h2>
                
                <?php if (!isset($items) || empty($items)): ?>
                    <p>Aucun élément créé pour ce type.</p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Logo</th>
                                <th>Nom</th>
                                <th>Slug</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <?php if ($item->logo_url): ?>
                                            <img src="<?php echo esc_url($item->logo_url); ?>" alt="" style="max-width: 50px; height: auto;">
                                        <?php else: ?>
                                            <span class="dashicons dashicons-format-image" style="color: #ccc;"></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo esc_html($item->name); ?></strong></td>
                                    <td><code><?php echo esc_html($item->slug); ?></code></td>
                                    <td>
                                        <?php if ($item->is_active): ?>
                                            <span class="dashicons dashicons-yes-alt" style="color: green;"></span> Actif
                                        <?php else: ?>
                                            <span class="dashicons dashicons-dismiss" style="color: red;"></span> Inactif
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="?page=wp-comparator-items&type_id=<?php echo $selected_type; ?>&edit_item=<?php echo $item->id; ?>" class="button button-small">
                                            Saisir les données
                                        </a>
                                        <button class="button button-small button-link-delete" onclick="deleteItem(<?php echo $item->id; ?>)">
                                            Supprimer
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <?php if (isset($_GET['edit_item'])): ?>
                <?php $this->render_item_data_form($selected_type, intval($_GET['edit_item'])); ?>
            <?php endif; ?>
        <?php else: ?>
            <div class="admin-section">
                <p>Veuillez d'abord sélectionner un type de comparateur pour gérer ses éléments.</p>
                <p><a href="<?php echo admin_url('admin.php?page=wp-comparator-types'); ?>" class="button">Créer un nouveau type</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function selectMedia(inputId) {
    const mediaUploader = wp.media({
        title: 'Choisir un fichier',
        button: {
            text: 'Utiliser ce fichier'
        },
        multiple: false
    });

    mediaUploader.on('select', function() {
        const attachment = mediaUploader.state().get('selection').first().toJSON();
        document.getElementById(inputId).value = attachment.url;
    });

    mediaUploader.open();
}

function deleteItem(itemId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet élément ? Toutes les données associées seront perdues.')) {
        console.log('Suppression de l\'élément:', itemId);
    }
}
</script>