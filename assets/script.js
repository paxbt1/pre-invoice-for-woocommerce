jQuery(document).ready(function($){

// $('.invoice-btn').click(function(event){
// event.preventDefault();
// console.log($(this).id);

// });


jQuery('.invoice-btn').on('click', function(e) {
    e.preventDefault();
    console.log($(this).attr('id'));
    let spinner=$(this).parent().find('.generate-pdf-spinner');
    let svg=$(this).find('svg');
    
    spinner.show();
    svg.hide();
    $.ajax({
        type: 'POST',
        url: ajax_object.ajax_url,
        data: {
            action: 'generate_pdf_invoice',
            order_id: $(this).attr('id'),
            nonce:ajax_object.nonce
        },
        success: function(response) {
            // Convert base64-encoded content to Blob
            var byteCharacters = atob(response);
            var byteNumbers = new Array(byteCharacters.length);
            for (var i = 0; i < byteCharacters.length; i++) {
                byteNumbers[i] = byteCharacters.charCodeAt(i);
            }
            var byteArray = new Uint8Array(byteNumbers);
            var pdfBlob = new Blob([byteArray], {
                type: 'application/pdf'
            });

            // Create a temporary anchor element to trigger download
            var link = document.createElement('a');
            link.href = window.URL.createObjectURL(pdfBlob);
            link.download = 'invoice.pdf';
            link.click();

            // Re-enable the button and hide the spinner
            $('.generate-pdf-invoice').prop('disabled', false);
            spinner.hide();
            svg.show();
            
        },
        error: function() {
            // Re-enable the button and hide the spinner in case of an error
            $('.generate-pdf-invoice').prop('disabled', false);
            spinner.hide();
            svg.show();
        }
    });

});





});
