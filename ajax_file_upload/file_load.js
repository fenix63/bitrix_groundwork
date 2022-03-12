$(document).ready(function(){
    console.log('ready');

    $('form[name="user_form"]').on('submit', function(e){
        e.preventDefault();
        var file_data = $('form[name="user_form"] input[name="userfile"]').prop("files")[0];

        var form_data = new FormData();
        form_data.append("userfile", file_data);

        console.log('file_data:');
        console.log(file_data);

        console.log('form data:');
        console.log(form_data);

        $.ajax({
            type: 'POST',
            url: 'file_load.php',
            dataType: 'text',
            cache: false,
            contentType: false,
            processData: false,
            data: form_data,
            success: function(msg){
                console.log('success:');
                console.log(msg.trim());
            },
            error: function(msg){
                console.log('error:');
                console.log(msg.trim());
            },
        });
        

    });

});