/**
 * Script for modal management in admin pages
 * Standardizes modal opening, closing, and interaction
 */

// Generic function to open any modal
function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
    
    // Focus on the first input in the modal for better accessibility
    const firstInput = modal.querySelector('input:not([type="hidden"])');
    if (firstInput) {
      setTimeout(() => firstInput.focus(), 100);
    }
    
    // Trigger an event that can be listened to by other scripts
    document.dispatchEvent(new CustomEvent('modalOpened', { detail: { modalId } }));
  }
}

// Generic function to close any modal
function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.display = 'none';
    document.body.style.overflow = 'auto'; // Restore scrolling
    
    // Trigger an event that can be listened to by other scripts
    document.dispatchEvent(new CustomEvent('modalClosed', { detail: { modalId } }));
    
    // Clear form validation errors when modal is closed
    const form = modal.querySelector('form');
    if (form) {
      const inputs = form.querySelectorAll('input');
      inputs.forEach(input => {
        input.setCustomValidity('');
        const feedbackElement = input.nextElementSibling;
        if (feedbackElement && feedbackElement.classList.contains('invalid-feedback')) {
          feedbackElement.textContent = '';
        }
      });
    }
  }
}

// Admin-specific modal functions
function openEditModal(id, email, nom, prenom, role) {
  document.getElementById('edit_admin_id').value = id;
  document.getElementById('edit_email').value = email;
  document.getElementById('edit_nom').value = nom;
  document.getElementById('edit_prenom').value = prenom;
  document.getElementById('edit_role').value = role;

  // Disable role field if it's the current user
  const currentUserId = parseInt(document.getElementById('current_admin_id').value);
  if (id === currentUserId) {
    document.getElementById('edit_role').disabled = true;
    document.getElementById('edit_role').value = 'superadmin';
  } else {
    document.getElementById('edit_role').disabled = false;
  }

  openModal('editAdminModal');
}

function openResetPasswordModal(id, email) {
  document.getElementById('reset_admin_id').value = id;
  document.getElementById('reset_password_email').textContent = `Réinitialisation du mot de passe pour: ${email}`;
  openModal('resetPasswordModal');
}

function openDeleteModal(id, email) {
  document.getElementById('delete_admin_id').value = id;
  document.getElementById('delete_admin_email').textContent = email;
  openModal('deleteAdminModal');
}

// Initialize modal events and password validation
document.addEventListener('DOMContentLoaded', function() {
  // Close modal when clicking outside
  window.addEventListener('click', function(event) {
    const modals = document.getElementsByClassName('modal');
    for (let i = 0; i < modals.length; i++) {
      if (event.target === modals[i]) {
        modals[i].style.display = 'none';
        document.body.style.overflow = 'auto';
      }
    }
  });
  
  // Close modal when pressing ESC key
  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
      const openModals = document.querySelectorAll('.modal[style*="display: block"]');
      openModals.forEach(modal => {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
      });
    }
  });
  
  // Password validation for add form
  const addPasswordField = document.getElementById('password');
  const addConfirmPasswordField = document.getElementById('confirm_password');

  if (addPasswordField && addConfirmPasswordField) {
    addConfirmPasswordField.addEventListener('input', function() {
      if (this.value === addPasswordField.value) {
        this.setCustomValidity('');
      } else {
        this.setCustomValidity('Les mots de passe ne correspondent pas');
      }
    });

    addPasswordField.addEventListener('input', function() {
      if (addConfirmPasswordField.value && this.value !== addConfirmPasswordField.value) {
        addConfirmPasswordField.setCustomValidity('Les mots de passe ne correspondent pas');
      } else {
        addConfirmPasswordField.setCustomValidity('');
      }
    });
  }

  // Password validation for reset form
  const resetPasswordField = document.getElementById('reset_password');
  const resetConfirmPasswordField = document.getElementById('reset_confirm_password');

  if (resetPasswordField && resetConfirmPasswordField) {
    resetConfirmPasswordField.addEventListener('input', function() {
      if (this.value === resetPasswordField.value) {
        this.setCustomValidity('');
      } else {
        this.setCustomValidity('Les mots de passe ne correspondent pas');
      }
    });

    resetPasswordField.addEventListener('input', function() {
      if (resetConfirmPasswordField.value && this.value !== resetConfirmPasswordField.value) {
        resetConfirmPasswordField.setCustomValidity('Les mots de passe ne correspondent pas');
      } else {
        resetConfirmPasswordField.setCustomValidity('');
      }
    });
  }
  
  // Add keyboard trap inside modal for accessibility
  document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('keydown', function(event) {
      if (event.key === 'Tab') {
        const focusableElements = modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        if (event.shiftKey && document.activeElement === firstElement) {
          event.preventDefault();
          lastElement.focus();
        } else if (!event.shiftKey && document.activeElement === lastElement) {
          event.preventDefault();
          firstElement.focus();
        }
      }
    });
  });
});
