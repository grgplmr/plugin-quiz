/* JavaScript pour l'interface d'import/export BIAQuiz */

jQuery(document).ready(function($) {
    'use strict';
    
    // Variables globales
    var importForm = $('#import-form');
    var exportForm = $('#export-form');
    var importProgress = $('#import-progress');
    var importResults = $('#import-results');
    var exportResults = $('#export-results');
    
    // Import de fichiers
    importForm.on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'biaquiz_import');
        
        // Validation
        var file = $('#import_file')[0].files[0];
        var category = $('#import_category').val();
        
        if (!file) {
            showMessage(importResults, 'error', biaquiz_ajax.strings.error, 'Veuillez sélectionner un fichier.');
            return;
        }
        
        if (!category) {
            showMessage(importResults, 'error', biaquiz_ajax.strings.error, 'Veuillez sélectionner une catégorie.');
            return;
        }
        
        // Vérifier la taille du fichier (10 MB)
        if (file.size > 10 * 1024 * 1024) {
            showMessage(importResults, 'error', biaquiz_ajax.strings.error, 'Le fichier est trop volumineux (max 10 MB).');
            return;
        }
        
        // Vérifier l'extension
        var allowedExtensions = ['csv', 'json'];
        var fileExtension = file.name.split('.').pop().toLowerCase();
        if (allowedExtensions.indexOf(fileExtension) === -1) {
            showMessage(importResults, 'error', biaquiz_ajax.strings.error, 'Format de fichier non supporté. Utilisez CSV ou JSON.');
            return;
        }
        
        // Confirmation
        if (!confirm(biaquiz_ajax.strings.confirm_import)) {
            return;
        }
        
        // Démarrer l'import
        startImport(formData);
    });
    
    // Export de fichiers
    exportForm.on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'biaquiz_export');
        
        startExport(formData);
    });
    
    // Fonction d'import
    function startImport(formData) {
        var submitBtn = importForm.find('button[type="submit"]');
        
        // UI Loading
        submitBtn.addClass('loading').prop('disabled', true);
        importProgress.show();
        importResults.hide();
        updateProgress(0, biaquiz_ajax.strings.importing);
        
        $.ajax({
            url: biaquiz_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 300000, // 5 minutes
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                
                // Progress pour l'upload
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = (evt.loaded / evt.total) * 50; // 50% pour l'upload
                        updateProgress(percentComplete, 'Upload en cours...');
                    }
                }, false);
                
                return xhr;
            },
            success: function(response) {
                updateProgress(100, 'Import terminé');
                
                if (response.success) {
                    var message = response.data.message || biaquiz_ajax.strings.success;
                    var details = '';
                    
                    if (response.data.imported > 0) {
                        details += response.data.imported + ' quiz importés. ';
                    }
                    if (response.data.updated > 0) {
                        details += response.data.updated + ' quiz mis à jour. ';
                    }
                    if (response.data.errors && response.data.errors.length > 0) {
                        details += '<div class="result-errors"><strong>Erreurs :</strong><ul>';
                        response.data.errors.forEach(function(error) {
                            details += '<li>' + escapeHtml(error) + '</li>';
                        });
                        details += '</ul></div>';
                    }
                    
                    var messageType = response.data.errors && response.data.errors.length > 0 ? 'warning' : 'success';
                    showMessage(importResults, messageType, message, details);
                    
                    // Reset form si succès complet
                    if (messageType === 'success') {
                        importForm[0].reset();
                    }
                } else {
                    showMessage(importResults, 'error', biaquiz_ajax.strings.error, response.data || 'Erreur inconnue');
                }
            },
            error: function(xhr, status, error) {
                updateProgress(0, 'Erreur');
                var errorMessage = 'Erreur de connexion';
                
                if (status === 'timeout') {
                    errorMessage = 'Timeout - Le fichier est peut-être trop volumineux';
                } else if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                }
                
                showMessage(importResults, 'error', biaquiz_ajax.strings.error, errorMessage);
            },
            complete: function() {
                submitBtn.removeClass('loading').prop('disabled', false);
                setTimeout(function() {
                    importProgress.hide();
                }, 2000);
            }
        });
    }
    
    // Fonction d'export
    function startExport(formData) {
        var submitBtn = exportForm.find('button[type="submit"]');
        
        // UI Loading
        submitBtn.addClass('loading').prop('disabled', true);
        exportResults.hide();
        
        $.ajax({
            url: biaquiz_ajax.ajax_url,
            type: 'POST',
            data: formData,
            timeout: 120000, // 2 minutes
            success: function(response) {
                if (response.success) {
                    // Créer et télécharger le fichier
                    downloadFile(response.data.content, response.data.filename, response.data.mime_type);
                    showMessage(exportResults, 'success', biaquiz_ajax.strings.success, 'Export terminé avec succès.');
                } else {
                    showMessage(exportResults, 'error', biaquiz_ajax.strings.error, response.data || 'Erreur inconnue');
                }
            },
            error: function(xhr, status, error) {
                var errorMessage = 'Erreur de connexion';
                
                if (status === 'timeout') {
                    errorMessage = 'Timeout - Trop de données à exporter';
                } else if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                }
                
                showMessage(exportResults, 'error', biaquiz_ajax.strings.error, errorMessage);
            },
            complete: function() {
                submitBtn.removeClass('loading').prop('disabled', false);
            }
        });
    }
    
    // Mettre à jour la barre de progression
    function updateProgress(percent, text) {
        importProgress.find('.progress-fill').css('width', percent + '%');
        importProgress.find('.progress-text').text(text);
    }
    
    // Afficher un message
    function showMessage(container, type, title, message) {
        container.removeClass('success error warning info').addClass(type);
        
        var html = '<div class="result-summary">';
        html += '<span class="status-icon ' + type + '"></span>';
        html += '<strong>' + escapeHtml(title) + '</strong>';
        html += '</div>';
        
        if (message) {
            html += '<div class="result-details">' + message + '</div>';
        }
        
        container.html(html).show();
        
        // Scroll vers le message
        $('html, body').animate({
            scrollTop: container.offset().top - 100
        }, 500);
    }
    
    // Télécharger un fichier
    function downloadFile(content, filename, mimeType) {
        var blob = new Blob([content], { type: mimeType });
        var url = window.URL.createObjectURL(blob);
        
        var a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }
    
    // Échapper le HTML
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Drag & Drop pour les fichiers
    var fileInput = $('#import_file');
    var uploadArea = fileInput.closest('td');
    
    // Créer une zone de drop
    if (!uploadArea.find('.file-upload-area').length) {
        var dropArea = $('<div class="file-upload-area">' +
            '<div class="file-upload-icon">📁</div>' +
            '<div class="file-upload-text">Glissez votre fichier ici ou cliquez pour sélectionner</div>' +
            '<div class="file-upload-hint">Formats acceptés : CSV, JSON (max 10 MB)</div>' +
        '</div>');
        
        fileInput.after(dropArea);
        fileInput.hide();
        
        // Events
        dropArea.on('click', function() {
            fileInput.click();
        });
        
        dropArea.on('dragover dragenter', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('dragover');
        });
        
        dropArea.on('dragleave dragend', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragover');
        });
        
        dropArea.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragover');
            
            var files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                fileInput[0].files = files;
                updateFileDisplay(files[0]);
            }
        });
        
        fileInput.on('change', function() {
            if (this.files.length > 0) {
                updateFileDisplay(this.files[0]);
            }
        });
        
        function updateFileDisplay(file) {
            dropArea.find('.file-upload-text').text('Fichier sélectionné : ' + file.name);
            dropArea.find('.file-upload-hint').text('Taille : ' + formatFileSize(file.size));
        }
    }
    
    // Formater la taille de fichier
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Validation en temps réel
    $('#import_category').on('change', function() {
        validateImportForm();
    });
    
    fileInput.on('change', function() {
        validateImportForm();
    });
    
    function validateImportForm() {
        var file = fileInput[0].files[0];
        var category = $('#import_category').val();
        var submitBtn = importForm.find('button[type="submit"]');
        
        if (file && category) {
            submitBtn.prop('disabled', false);
        } else {
            submitBtn.prop('disabled', true);
        }
    }
    
    // Initialiser la validation
    validateImportForm();
    
    // Auto-refresh des templates
    $('.template-downloads a').on('click', function() {
        var $this = $(this);
        $this.addClass('loading');
        
        setTimeout(function() {
            $this.removeClass('loading');
        }, 2000);
    });
});

