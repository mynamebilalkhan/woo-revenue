jQuery(document).ready(function($) {
    $('#country').change(function() {
         var country_code = $(this).val();

         $('.pt-states-error').html('');
         $('#state').prop('disabled', false);
         $('#state').empty().trigger('change');

         console.log(country_code);
         console.log(typeof country_code);

         if( country_code.length > 1 ) {
               $('.pt-states-error').html('States not available with multiple countries');
               $('#state').prop('disabled', true);
               $("#state").val(null).trigger('change');

         } else if( country_code.length == 1 ) {
               $.ajax({
                    url: ajax_object.ajax_url,
                    type: 'POST',
                    data: {
                         action: 'get_states_by_country',
                         country_code: country_code,
                         // security: ajax_object.get_states_nonce // Include nonce field
                    },
                    success: function(response) {
                         // Update the states dropdown with the received data
                         var states = response.data;
                         if(states.status == 'error') {
                              $('.pt-states-error').html(states.data);
                              $("#state").val(null).trigger('change');
                              $('#state').prop('disabled', true);
                         } else if (states.status == 'success'){
                              $('#state').html(states.data);
                         }
                         // Modify your DOM to update the states dropdown
                         console.log(states);
                         // $('#state').html(states);
                    },
                    error: function(xhr, status, error) {
                         console.error(error);
                    }
               });
         }

         
    });

    $('.pt-download-csv-btn').on('click', function() {
          var from_date = $('input[name="from_date"]').val();
          var to_date = $('input[name="to_date"]').val();
          var country = $('#country').val();
          var state = $('#state').val();

          $.ajax({
               url: ajax_object.ajax_url,
               type: 'POST',
               data: {
                    action: 'create_and_download_csv',
                    from_date: from_date,
                    to_date: to_date,
                    country: country,
                    state: state,
                    // security: ajax_object.get_states_nonce // Include nonce field
               },
               success: function(response) {
                    // Update the states dropdown with the received data
                    console.log(response);
                    if (response) {
                         // Trigger download using the file URL or data from the response
                         // For example, if the response contains the file URL:
                         $("#csvLink").html('<a href="' + response + '" download>Download CSV File</a>');
                    }
               },
               error: function(xhr, status, error) {
                    console.error(error);
               }
          });
    });
});