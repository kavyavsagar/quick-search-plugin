jQuery( function( $ ) {

    var opt = '',
        brandClass = AjaxDemo.democlass + 'brand',
        modelClass = AjaxDemo.democlass + 'model',
        // insert AJAX result into the shortcode element
        updateModelDemo = function( response ){  
            opt = '<option value="">--All Models--</option>';

            response = JSON.parse(response);

            $.each(response, function (index, value) {
                opt += '<option value="'+value.term_id+'">'+value.name+'</option>';
            });     
            $( '.' + AjaxDemo.democlass ).find( 'select' ).html( opt );
        },
        updateYearDemo = function( response ){  
            opt = '';
            opt = '<option value="">--All Years--</option>';

            response = JSON.parse(response);

            $.each(response, function (index, value) {
                opt += '<option value="'+value.term_id+'">'+value.name+'</option>';
            });     
            $( '.' + AjaxDemo.democlass+'year' ).find( 'select' ).html( opt );
        },
        // fetch AJAX data
        loadModelDemo = function() {           
            $.post( AjaxDemo.ajaxUrl, { action: AjaxDemo.action, brandid: $(this).val() }, updateModelDemo );
        };

        loadYearDemo = function() {            
            $.post( AjaxDemo.ajaxUrl, { action: AjaxDemo.action, modelid: $(this).val() }, updateYearDemo );
        };


    // assign the clock handler to the button
    $( '.' + brandClass ).change( loadModelDemo );
    $( '.' + modelClass ).change( loadYearDemo );
});

