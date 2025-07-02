# 📚 DOCUMENTATION COMPLÈTE - BIAQuiz Core v2.0.0

## 🎯 **Vue d'Ensemble**

BIAQuiz Core est un plugin WordPress professionnel spécialement conçu pour l'entraînement au Brevet d'Initiation à l'Aéronautique (BIA). Il offre une solution complète pour créer, gérer et diffuser des quiz thématiques interactifs.

### **Caractéristiques Principales**
- ✅ **Quiz interactifs** avec correction immédiate
- ✅ **Système de répétition** des questions incorrectes
- ✅ **Import/Export** CSV et JSON fonctionnels
- ✅ **Interface responsive** (mobile, tablette, desktop)
- ✅ **6 catégories thématiques** du BIA
- ✅ **Aucune inscription** requise pour les utilisateurs
- ✅ **Statistiques** et analytics intégrés
- ✅ **Design aéronautique** moderne et épuré

---

## 🚀 **Installation et Configuration**

### **Prérequis Techniques**
- **WordPress** : 5.0 ou supérieur
- **PHP** : 7.4 ou supérieur  
- **MySQL** : 5.6 ou supérieur
- **Mémoire PHP** : 128 MB minimum (256 MB recommandé)

### **Installation**

1. **Télécharger** le plugin `biaquiz-core.zip`
2. **Aller** dans WordPress Admin → Extensions → Ajouter
3. **Cliquer** sur "Téléverser une extension"
4. **Sélectionner** le fichier zip et installer
5. **Activer** le plugin

### **Configuration Initiale**

1. **Aller** dans "Quiz" → "Paramètres"
2. **Configurer** :
   - Nombre de quiz par page : 10
   - Afficher les explications : Oui
   - Autoriser la reprise : Oui
3. **Sauvegarder** les paramètres

---

## 📋 **Gestion des Quiz**

### **Créer un Quiz Manuellement**

1. **Aller** dans "Quiz" → "Ajouter"
2. **Remplir** :
   - Titre du quiz
   - Description (optionnel)
   - Catégorie
   - Difficulté (Facile/Moyen/Difficile)
   - Numéro du quiz (auto-généré)
3. **Ajouter des questions** :
   - Texte de la question
   - 4 réponses possibles
   - Sélectionner la bonne réponse
   - Explication (optionnel)
4. **Publier** le quiz

### **Import de Quiz**

#### **Format CSV**
```csv
title,category,difficulty,question_text,answer_1,answer_2,answer_3,answer_4,correct_answer,explanation
"Quiz Aéro","aerodynamique","facile","Question ?","Rép 1","Rép 2","Rép 3","Rép 4",2,"Explication"
```

#### **Format JSON**
```json
{
  "title": "Quiz Test",
  "category": "aerodynamique",
  "difficulty": "facile",
  "description": "Description du quiz",
  "questions": [
    {
      "question_text": "Question ?",
      "answers": [
        {"text": "Réponse 1", "correct": false},
        {"text": "Réponse 2", "correct": true},
        {"text": "Réponse 3", "correct": false},
        {"text": "Réponse 4", "correct": false}
      ],
      "explanation": "Explication de la réponse"
    }
  ]
}
```

#### **Procédure d'Import**
1. **Aller** dans "Quiz" → "Import/Export"
2. **Sélectionner** le fichier CSV ou JSON
3. **Choisir** la catégorie de destination
4. **Configurer** les options d'import
5. **Lancer** l'import

### **Export de Quiz**

1. **Aller** dans "Quiz" → "Import/Export"
2. **Sélectionner** :
   - Format (CSV ou JSON)
   - Catégorie (ou toutes)
   - Quiz spécifiques (optionnel)
3. **Télécharger** le fichier généré

---

## 🎨 **Catégories Thématiques**

### **Les 6 Catégories du BIA**

1. **Aérodynamique et mécanique du vol**
   - Slug : `aerodynamique`
   - Couleur : Bleu (#0073aa)
   - Icône : dashicons-airplane

2. **Connaissance des aéronefs**
   - Slug : `aeronefs`
   - Couleur : Vert (#28a745)
   - Icône : dashicons-admin-tools

3. **Météorologie**
   - Slug : `meteorologie`
   - Couleur : Orange (#fd7e14)
   - Icône : dashicons-cloud

4. **Navigation, règlementation et sécurité**
   - Slug : `navigation`
   - Couleur : Rouge (#dc3545)
   - Icône : dashicons-location-alt

5. **Histoire de l'aéronautique et de l'espace**
   - Slug : `histoire`
   - Couleur : Violet (#6f42c1)
   - Icône : dashicons-book-alt

6. **Anglais aéronautique**
   - Slug : `anglais`
   - Couleur : Indigo (#6610f2)
   - Icône : dashicons-translation

### **Gestion des Catégories**

1. **Aller** dans "Quiz" → "Catégories"
2. **Modifier** les propriétés :
   - Nom et description
   - Couleur d'affichage
   - Icône Dashicons
   - Ordre d'affichage
3. **Sauvegarder** les modifications

---

## 🖥️ **Affichage Frontend**

### **Shortcodes Disponibles**

#### **Liste des Quiz**
```php
[biaquiz_list]
[biaquiz_list category="aerodynamique" limit="5" difficulty="facile"]
```

**Paramètres :**
- `category` : Slug de la catégorie
- `limit` : Nombre de quiz (défaut: 10)
- `difficulty` : Niveau de difficulté
- `show_description` : Afficher la description (yes/no)

#### **Liste des Catégories**
```php
[biaquiz_categories]
[biaquiz_categories show_count="yes" show_description="yes"]
```

**Paramètres :**
- `show_count` : Afficher le nombre de quiz (yes/no)
- `show_description` : Afficher les descriptions (yes/no)

### **Templates Personnalisés**

Le plugin recherche les templates dans votre thème :
- `single-biaquiz.php` : Page d'un quiz
- `taxonomy-quiz_category.php` : Page d'une catégorie
- `archive-biaquiz.php` : Archive des quiz

### **Classes CSS Principales**

```css
.biaquiz-container          /* Container principal */
.biaquiz-header            /* En-tête du quiz */
.biaquiz-question          /* Une question */
.answer-option             /* Une réponse */
.answer-option.selected    /* Réponse sélectionnée */
.answer-option.correct     /* Bonne réponse */
.answer-option.incorrect   /* Mauvaise réponse */
.biaquiz-results-screen    /* Écran de résultats */
.difficulty-facile         /* Difficulté facile */
.difficulty-moyen          /* Difficulté moyenne */
.difficulty-difficile      /* Difficulté difficile */
```

---

## 📊 **Statistiques et Analytics**

### **Tableau de Bord**

Le tableau de bord affiche :
- **Nombre total** de quiz publiés
- **Nombre de brouillons**
- **Répartition par catégorie**
- **Quiz les plus populaires**
- **Statistiques de performance**

### **Données Collectées**

Pour chaque tentative de quiz :
- ID du quiz
- IP de l'utilisateur (anonymisée)
- Score obtenu
- Temps passé
- Date et heure
- Questions incorrectes

### **Rapports Disponibles**

1. **Statistiques globales**
2. **Performance par quiz**
3. **Analyse par catégorie**
4. **Tendances temporelles**

---

## 🔧 **Personnalisation Avancée**

### **Hooks et Filtres**

#### **Actions**
```php
// Avant l'affichage d'un quiz
do_action('biaquiz_before_quiz_display', $quiz_id);

// Après soumission d'un quiz
do_action('biaquiz_after_quiz_submit', $quiz_id, $results);

// Import réussi
do_action('biaquiz_import_success', $imported_count);
```

#### **Filtres**
```php
// Modifier les résultats d'un quiz
apply_filters('biaquiz_quiz_results', $results, $quiz_id);

// Personnaliser les options d'export
apply_filters('biaquiz_export_options', $options);

// Modifier les paramètres par défaut
apply_filters('biaquiz_default_settings', $settings);
```

### **Fonctions Utilitaires**

```php
// Obtenir un quiz
$quiz = BIAQuiz_Quiz_Handler::get_quiz($quiz_id);

// Obtenir les statistiques
$stats = BIAQuiz_Quiz_Handler::get_quiz_stats($quiz_id);

// Valider des réponses
$results = BIAQuiz_Quiz_Handler::validate_quiz_answers($quiz_id, $answers);
```

---

## 🛠️ **Maintenance et Dépannage**

### **Problèmes Courants**

#### **Import qui échoue**
- Vérifier le format du fichier
- Contrôler la taille (max 10 MB)
- Valider la structure des données
- Vérifier les permissions de fichiers

#### **Quiz qui ne s'affiche pas**
- Vérifier que le quiz est publié
- Contrôler les paramètres de visibilité
- Vérifier les shortcodes utilisés
- Contrôler les conflits de thème

#### **Erreurs JavaScript**
- Vérifier la console du navigateur
- Contrôler les conflits de plugins
- Vérifier la version de jQuery
- Tester en mode debug

### **Mode Debug**

Ajouter dans `wp-config.php` :
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('BIAQUIZ_DEBUG', true);
```

### **Logs**

Les logs sont disponibles dans :
- WordPress : `/wp-content/debug.log`
- Plugin : `/wp-content/uploads/biaquiz-logs/`

### **Optimisation Performance**

1. **Cache** : Utiliser un plugin de cache
2. **Images** : Optimiser les images
3. **Base de données** : Nettoyer régulièrement
4. **Plugins** : Désactiver les plugins inutiles

---

## 🔒 **Sécurité**

### **Mesures Implémentées**

- ✅ **Validation** de toutes les entrées utilisateur
- ✅ **Échappement** de toutes les sorties
- ✅ **Nonces** pour les actions AJAX
- ✅ **Permissions** utilisateur vérifiées
- ✅ **Sanitisation** des données d'import
- ✅ **Protection** contre les injections SQL
- ✅ **Limitation** de taille des fichiers

### **Bonnes Pratiques**

1. **Maintenir** WordPress à jour
2. **Utiliser** des mots de passe forts
3. **Limiter** les tentatives de connexion
4. **Sauvegarder** régulièrement
5. **Surveiller** les logs d'erreur

---

## 📞 **Support et Assistance**

### **Documentation**
- Guide d'installation
- Tutoriels vidéo
- FAQ complète
- Exemples de code

### **Support Technique**
- Email : support@acme-biaquiz.com
- Forum : forum.acme-biaquiz.com
- Documentation : docs.acme-biaquiz.com

### **Mises à Jour**
- Notifications automatiques
- Changelog détaillé
- Migration assistée
- Sauvegarde automatique

---

## 📝 **Licence et Crédits**

### **Licence**
Ce plugin est distribué sous licence GPL v2 ou ultérieure.

### **Crédits**
- Développé par ACME
- Icônes : Dashicons (WordPress)
- Polices : System fonts
- Framework : WordPress Plugin API

### **Remerciements**
Merci à la communauté WordPress et aux contributeurs du projet BIA pour leurs retours et suggestions.

---

**Version** : 2.0.0  
**Dernière mise à jour** : 2025  
**Compatibilité** : WordPress 5.0+ / PHP 7.4+

