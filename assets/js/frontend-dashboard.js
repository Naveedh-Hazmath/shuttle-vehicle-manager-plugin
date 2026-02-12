jQuery(document).ready(function($) {
    // Auth forms tab switching
    $('.svm-auth-tabs a').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        
        // Activate tab
        $('.svm-auth-tabs a').removeClass('active');
        $(this).addClass('active');
        
        // Show tab content
        $('.auth-form').removeClass('active');
        $(target).addClass('active');
    });
    
    // Initialize date pickers
    $('.date-picker').flatpickr({
        dateFormat: 'Y-m-d'
    });
    
    // Check profile completion when loading vehicles tab
    if (window.location.href.indexOf('tab=vehicles') > -1) {
        checkProfileCompletion();
    }
    
    function checkProfileCompletion() {
        $.ajax({
            url: shuttle_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'shuttle_check_profile_completion',
                nonce: shuttle_ajax.nonce
            },
            success: function(response) {
                if (response.success && !response.data.complete) {
                    var missingFields = response.data.missing_fields.join(', ');
                    $('.svm-dashboard-content').prepend(
                        '<div class="svm-notice svm-notice-warning">' +
                        '<p>' + 'Please complete your profile before adding vehicles.' + '</p>' +
                        '<p>' + 'Missing fields: ' + missingFields + '</p>' +
                        '<a href="?tab=profile" class="svm-button">' + 'Complete Profile' + '</a>' +
                        '</div>'
                    );
                }
            }
        });
    }
    
    // Login form submission
    $('#shuttle-login-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'shuttle_login_user');
        
        $.ajax({
            url: shuttle_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#shuttle-login-form .form-message').html('<p class="loading">Logging in...</p>');
            },
            success: function(response) {
                if (response.success) {
                    $('#shuttle-login-form .form-message').html('<p class="success">' + response.data.message + '</p>');
                    window.location.href = response.data.redirect;
                } else {
                    $('#shuttle-login-form .form-message').html('<p class="error">' + response.data.message + '</p>');
                }
            },
            error: function() {
                $('#shuttle-login-form .form-message').html('<p class="error">An error occurred. Please try again.</p>');
            }
        });
    });
    
    // Registration form submission
    $('#shuttle-register-form').on('submit', function(e) {
        e.preventDefault();

        var password = $('#register-password').val();
        var confirm_password = $('#register-confirm-password').val();

        if (password !== confirm_password) {
            $('#shuttle-register-form .form-message').html('<p class="error">Passwords do not match.</p>');
            return;
        }

        var formData = new FormData(this);
        formData.append('action', 'shuttle_register_user');

        $.ajax({
            url: shuttle_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#shuttle-register-form .form-message').html('<p class="loading">Registering...</p>');
            },
            success: function(response) {
                if (response.success) {
                    $('#shuttle-register-form .form-message').html('<p class="success">' + response.data.message + '</p>');
                    window.location.href = response.data.redirect;
                } else {
                    $('#shuttle-register-form .form-message').html('<p class="error">' + response.data.message + '</p>');
                }
            },
            error: function() {
                $('#shuttle-register-form .form-message').html('<p class="error">An error occurred. Please try again.</p>');
            }
        });
    });
    
    // Profile form submission
    $('#shuttle-profile-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'shuttle_save_profile');
        
        $.ajax({
            url: shuttle_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#shuttle-profile-form .form-message').html('<p class="loading">Saving profile...</p>');
            },
            success: function(response) {
                if (response.success) {
                    $('#shuttle-profile-form .form-message').html('<p class="success">' + response.data.message + '</p>');
                    setTimeout(function() {
                        window.location.href = response.data.redirect;
                    }, 1000);
                } else {
                    $('#shuttle-profile-form .form-message').html('<p class="error">' + response.data.message + '</p>');
                }
            },
            error: function() {
                $('#shuttle-profile-form .form-message').html('<p class="error">An error occurred. Please try again.</p>');
            }
        });
    });
    
    // Add vehicle form submission
    $('#shuttle-add-vehicle-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'shuttle_save_vehicle');
        
        $.ajax({
            url: shuttle_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#shuttle-add-vehicle-form .form-message').html('<p class="loading">Registering vehicle...</p>');
            },
            success: function(response) {
                if (response.success) {
                    $('#shuttle-add-vehicle-form .form-message').html('<p class="success">' + response.data.message + '</p>');
                    setTimeout(function() {
                        window.location.href = response.data.redirect;
                    }, 1000);
                } else {
                    $('#shuttle-add-vehicle-form .form-message').html('<p class="error">' + response.data.message + '</p>');
                }
            },
            error: function() {
                $('#shuttle-add-vehicle-form .form-message').html('<p class="error">An error occurred. Please try again.</p>');
            }
        });
    });
    
    // Edit vehicle form submission
    $('#shuttle-edit-vehicle-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'shuttle_save_vehicle');
        
        $.ajax({
            url: shuttle_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#shuttle-edit-vehicle-form .form-message').html('<p class="loading">Updating vehicle...</p>');
            },
            success: function(response) {
                if (response.success) {
                    $('#shuttle-edit-vehicle-form .form-message').html('<p class="success">' + response.data.message + '</p>');
                    setTimeout(function() {
                        window.location.href = response.data.redirect;
                    }, 1000);
                } else {
                    $('#shuttle-edit-vehicle-form .form-message').html('<p class="error">' + response.data.message + '</p>');
                }
            },
            error: function() {
                $('#shuttle-edit-vehicle-form .form-message').html('<p class="error">An error occurred. Please try again.</p>');
            }
        });
    });

    // Delete vehicle
    $('.delete-vehicle').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to delete this vehicle? This action cannot be undone.')) {
            return;
        }
        
        var vehicleId = $(this).data('id');
        
        $.ajax({
            url: shuttle_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'shuttle_delete_vehicle',
                vehicle_id: vehicleId,
                nonce: shuttle_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    window.location.href = response.data.redirect || shuttle_ajax.redirect_url;
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    });

    // Clear availability calendar - removed old code
    // Save availability - removed old code as it's now inline in the PHP
});