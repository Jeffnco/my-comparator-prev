<div class="wrap">
    <h1>Champs de comparaison</h1>
    
    <?php if (isset($_GET['message']) && $_GET['message'] == 'field_added'): ?>
        <div class="notice notice-success is-dismissible">
            <p>Champ ajouté avec succès !</p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['message']) && $_GET['message'] == 'field_updated'): ?>
        <div class="notice notice-success is-dismissible">
            <p>Champ modifié avec succès !</p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error']) && $_GET['error'] == 'field_not_updated'): ?>
        <div class="notice notice-error is-dismissible">
            <p>Erreur lors de la modification du champ.</p>
        </div>
    <?php endif; ?>
    
    <div class="wp-comparator-admin-content">
        <!-- Sélecteur de type -->
        <div class="admin-section">
            <h2>Sélectionner un type de comparateur</h2>
            <form method="get" action="">
                <input type="hidden" name="page" value="wp-comparator-fields">
                <select name="type_id" onchange="loadCategories(this.value)">
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
        
        <?php if (isset($selected_type) && $selected_type && isset($categories) && !empty($categories)): ?>
            <!-- Sélecteur de catégorie -->
            <div class="admin-section">
                <h2>Sélectionner une catégorie</h2>
                <form method="get" action="">
                    <input type="hidden" name="page" value="wp-comparator-fields">
                    <input type="hidden" name="type_id" value="<?php echo $selected_type; ?>">
                    <select name="category_id" onchange="this.form.submit()">
                        <option value="">-- Choisir une catégorie --</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category->id; ?>" <?php selected($selected_category, $category->id); ?>>
                                <?php echo esc_html($category->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        <?php endif; ?>
        
        <?php if (isset($selected_category) && $selected_category): ?>
            <?php 
            // Gestion de l'édition d'un champ
            $edit_field_id = isset($_GET['edit_field']) ? intval($_GET['edit_field']) : 0;
            $edit_field = null;
            if ($edit_field_id && isset($fields)) {
                foreach ($fields as $field) {
                    if ($field->id == $edit_field_id) {
                        $edit_field = $field;
                        break;
                    }
                }
            }
            ?>
            
            <!-- Formulaire d'ajout de champ -->
            <div class="admin-section">
                <h2><?php echo $edit_field ? 'Modifier le champ' : 'Ajouter un nouveau champ'; ?></h2>
                <form method="post" action="">
                    <?php wp_nonce_field($edit_field ? 'wp_comparator_update_field' : 'wp_comparator_add_field', '_wpnonce'); ?>
                    <input type="hidden" name="wp_comparator_action" value="<?php echo $edit_field ? 'update_field' : 'add_field'; ?>">
                    <input type="hidden" name="category_id" value="<?php echo $selected_category; ?>">
                    <input type="hidden" name="type_id" value="<?php echo $selected_type; ?>">
                    <?php if ($edit_field): ?>
                        <input type="hidden" name="field_id" value="<?php echo $edit_field->id; ?>">
                    <?php endif; ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="name">Nom du champ</label>
                            </th>
                            <td>
                                <input type="text" id="name" name="name" class="regular-text" value="<?php echo $edit_field ? esc_attr($edit_field->name) : ''; ?>" required>
                                <p class="description">Ex: Délai d'attente maladie, Mode d'indemnisation</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="field_type">Type de champ</label>
                            </th>
                            <td>
                                <select id="field_type" name="field_type" onchange="toggleFieldOptions()">
                                    <option value="text" <?php echo ($edit_field && $edit_field->field_type == 'text') ? 'selected' : ''; ?>>Texte</option>
                                    <option value="textarea" <?php echo ($edit_field && $edit_field->field_type == 'textarea') ? 'selected' : ''; ?>>Zone de texte</option>
                                    <option value="select" <?php echo ($edit_field && $edit_field->field_type == 'select') ? 'selected' : ''; ?>>Liste déroulante</option>
                                    <option value="radio" <?php echo ($edit_field && $edit_field->field_type == 'radio') ? 'selected' : ''; ?>>Boutons radio</option>
                                    <option value="checkbox" <?php echo ($edit_field && $edit_field->field_type == 'checkbox') ? 'selected' : ''; ?>>Case à cocher</option>
                                    <option value="number" <?php echo ($edit_field && $edit_field->field_type == 'number') ? 'selected' : ''; ?>>Nombre</option>
                                    <option value="url" <?php echo ($edit_field && $edit_field->field_type == 'url') ? 'selected' : ''; ?>>URL</option>
                                    <option value="image" <?php echo ($edit_field && $edit_field->field_type == 'image') ? 'selected' : ''; ?>>Image</option>
                                </select>
                            </td>
                        </tr>
                        <tr id="field_options_row" style="display: <?php echo ($edit_field && in_array($edit_field->field_type, ['select', 'radio', 'checkbox'])) ? 'table-row' : 'none'; ?>;">
                            <th scope="row">
                                <label for="field_options">Options du champ</label>
                            </th>
                            <td>
                                <textarea id="field_options" name="field_options" rows="3" class="large-text"><?php echo $edit_field ? esc_textarea($edit_field->field_options) : ''; ?></textarea>
                                <p class="description">Pour select/radio/checkbox, séparez les options par des virgules. Ex: Oui,Non,Partiellement</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="description">Description</label>
                            </th>
                            <td>
                                <textarea id="description" name="description" rows="3" class="large-text"><?php echo $edit_field ? esc_textarea($edit_field->description) : ''; ?></textarea>
                                <p class="description">Description qui apparaîtra dans l'infobulle</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="help_text">Texte d'aide</label>
                            </th>
                            <td>
                                <textarea id="help_text" name="help_text" rows="2" class="large-text"><?php echo $edit_field ? esc_textarea($edit_field->help_text) : ''; ?></textarea>
                                <p class="description">Texte d'aide pour la saisie</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Filtrable</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="is_filterable" value="1" <?php echo ($edit_field && $edit_field->is_filterable) ? 'checked' : ''; ?>>
                                    Ce champ peut être utilisé comme filtre
                                </label>
                            </td>
                        </tr>
                        <tr id="filter_type_row" style="display: <?php echo ($edit_field && $edit_field->is_filterable) ? 'table-row' : 'none'; ?>;">
                            <th scope="row">
                                <label for="filter_type">Type de filtre</label>
                            </th>
                            <td>
                                <select id="filter_type" name="filter_type">
                                    <option value="select" <?php echo ($edit_field && $edit_field->filter_type == 'select') ? 'selected' : ''; ?>>Liste déroulante</option>
                                    <option value="radio" <?php echo ($edit_field && $edit_field->filter_type == 'radio') ? 'selected' : ''; ?>>Boutons radio</option>
                                    <option value="checkbox" <?php echo ($edit_field && $edit_field->filter_type == 'checkbox') ? 'selected' : ''; ?>>Cases à cocher</option>
                                    <option value="range" <?php echo ($edit_field && $edit_field->filter_type == 'range') ? 'selected' : ''; ?>>Plage de valeurs</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="sort_order">Ordre d'affichage</label>
                            </th>
                            <td>
                                <input type="number" id="sort_order" name="sort_order" value="<?php echo $edit_field ? $edit_field->sort_order : '0'; ?>" min="0" class="small-text">
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" class="button-primary" value="<?php echo $edit_field ? 'Modifier le champ' : 'Ajouter le champ'; ?>">
                        <?php if ($edit_field): ?>
                            <a href="?page=wp-comparator-fields&type_id=<?php echo $selected_type; ?>&category_id=<?php echo $selected_category; ?>" class="button">Annuler</a>
                        <?php endif; ?>
                    </p>
                </form>
            </div>
            
            <!-- Liste des champs existants -->
            <div class="admin-section">
                <h2>Champs existants</h2>
                
                <?php if (!isset($fields) || empty($fields)): ?>
                    <p>Aucun champ créé pour cette catégorie.</p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Ordre</th>
                                <th>Nom</th>
                                <th>Type</th>
                                <th>Filtrable</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fields as $field): ?>
                                <tr>
                                    <td><?php echo $field->sort_order; ?></td>
                                    <td><strong><?php echo esc_html($field->name); ?></strong></td>
                                    <td><?php echo esc_html($field->field_type); ?></td>
                                    <td><?php echo $field->is_filterable ? 'Oui' : 'Non'; ?></td>
                                    <td>
                                        <a href="?page=wp-comparator-fields&type_id=<?php echo $selected_type; ?>&category_id=<?php echo $selected_category; ?>&edit_field=<?php echo $field->id; ?>" class="button button-small">
                                            Éditer
                                        </a>
                                        <button class="button button-small button-link-delete" onclick="deleteField(<?php echo $field->id; ?>)">
                                            Supprimer
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleFieldOptions() {
    const fieldType = document.getElementById('field_type').value;
    const optionsRow = document.getElementById('field_options_row');
    
    if (['select', 'radio', 'checkbox'].includes(fieldType)) {
        optionsRow.style.display = 'table-row';
    } else {
        optionsRow.style.display = 'none';
    }
}

function loadCategories(typeId) {
    if (typeId) {
        window.location.href = '?page=wp-comparator-fields&type_id=' + typeId;
    }
}

function deleteField(fieldId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce champ ?')) {
        console.log('Suppression du champ:', fieldId);
    }
}

function editField(fieldId) {
    // Cette fonction n'est plus utilisée car on utilise des liens directs
}

// Gestion du checkbox filtrable
document.addEventListener('DOMContentLoaded', function() {
    const filterableCheckbox = document.querySelector('input[name="is_filterable"]');
    const filterTypeRow = document.getElementById('filter_type_row');
    
    if (filterableCheckbox) {
        filterableCheckbox.addEventListener('change', function() {
            if (this.checked) {
                filterTypeRow.style.display = 'table-row';
            } else {
                filterTypeRow.style.display = 'none';
            }
        });
    }
});
</script>