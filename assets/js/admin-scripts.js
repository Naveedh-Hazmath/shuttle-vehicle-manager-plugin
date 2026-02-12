jQuery(document).ready(function($) {
    // Initialize date pickers
    $('.date-picker').flatpickr({
        dateFormat: 'Y-m-d'
    });
    
    // Handle vehicle verification
    $('.verify-vehicle-button').on('click', function(e) {
        e.preventDefault();
        
        var vehicleId = $(this).data('id');
        var button = $(this);
        
        if (confirm('Are you sure you want to verify this vehicle?')) {
            $.ajax({
                url: shuttle_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'verify_vehicle',
                    vehicle_id: vehicleId,
                    nonce: shuttle_ajax.nonce
                },
                beforeSend: function() {
                    button.text('Verifying...').prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                        button.text('Verify').prop('disabled', false);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                    button.text('Verify').prop('disabled', false);
                }
            });
        }
    });
    
    // Handle owner verification
    $('.verify-owner-button').on('click', function(e) {
        e.preventDefault();
        
        var ownerId = $(this).data('id');
        var button = $(this);
        
        if (confirm('Are you sure you want to verify this owner?')) {
            $.ajax({
                url: shuttle_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'verify_owner',
                    owner_id: ownerId,
                    nonce: shuttle_ajax.nonce
                },
                beforeSend: function() {
                    button.text('Verifying...').prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                        button.text('Verify').prop('disabled', false);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                    button.text('Verify').prop('disabled', false);
                }
            });
        }
    });

    // Add this to your admin JavaScript
    jQuery(document).ready(function($) {
        // When showing the modal
        $(document).on('click', '.admin-date', function() {
            // Force remove any conflicting classes
            $('#admin-vehicles-modal').removeClass().addClass('svm-modal');
            
            // Ensure modal is properly styled
            setTimeout(function() {
                $('#admin-vehicles-modal').css({
                    'display': 'flex',
                    'align-items': 'center',
                    'justify-content': 'center'
                });
            }, 10);
        });
    });
});

