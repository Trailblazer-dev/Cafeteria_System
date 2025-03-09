/**
 * Main JavaScript file for the Cafeteria Management System
 */

// Auto-hide messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        const successMessage = document.getElementById('successMessage');
        const errorMessage = document.getElementById('errorMessage');
        
        if (successMessage) successMessage.style.display = 'none';
        if (errorMessage) errorMessage.style.display = 'none';
    }, 5000);
});

// Confirm delete action
function confirmDelete(id, name, formId = 'deleteForm') {
    if (confirm('Are you sure you want to delete "' + name + '"? This action cannot be undone.')) {
        document.getElementById('delete_id').value = id;
        document.getElementById(formId).submit();
    }
}

// Toggle modal visibility
function toggleModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal.classList.contains('hidden')) {
        modal.classList.remove('hidden');
    } else {
        modal.classList.add('hidden');
    }
}

// Show edit modal with data
function showEditModal(modalId, data) {
    // Fill form fields with data
    for (const key in data) {
        const field = document.getElementById('edit_' + key);
        if (field) {
            field.value = data[key];
        }
    }
    
    // Show the modal
    toggleModal(modalId);
}

// Toggle debug info visibility
function toggleDebugInfo() {
    const debugInfo = document.getElementById('debugInfo');
    if (debugInfo) {
        debugInfo.classList.toggle('hidden');
    }
}
