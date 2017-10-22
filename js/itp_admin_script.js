jQuery(document).ready(function ($) {
    function readURL(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function (e) {
                $('#img_' + input.id).attr('src', e.target.result);
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    $(".load").change(function () {
        readURL(this);
    });

    $("#add_btn").click(function () {
        $("#add").show();
        $("#add_btn").hide();
    });

    $(".delete_btn").click(function () {  
        var id = $(event.target).attr('name');  
        $.ajax({
            url: ajaxurl,
            type: "POST",
            data: {id: id, action: 'itp_delete'},
            success: function (data) {
                $('div.artist[name='+id+']').remove();
            },
            error: function () {
                console.log('Error');
            }
        });
    });



});
