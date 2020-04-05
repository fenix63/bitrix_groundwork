$(document).ready(function(){
    console.log('Это script.js');
    //Переменная-флаг для отслеживания того, происходит ли в данный момент ajax-запрос. В самом начале даём ей значение false, т.е. запрос не в процессе выполнения
    var inProgress = false;

    //С какого товара надо делать выборку при ajax-запросе
    var startFrom = 2;

    $(window).scroll(function(){
        console.log('scroll event');

        console.log('window.scrollTop: ' + $(window).scrollTop());
        console.log('window.height: ' + $(window).height());
        console.log('document.height: ' + $(document).height());

        if($(window).scrollTop() + $(window).height() >= $(document).height() - 700 && !inProgress){

            console.log('TRUE');

            $.ajax({
               url: 'http://localhost:8080/test/processing.php',
               method: 'POST',
               data: {'startFrom': startFrom},
               beforeSend: function () {
                   inProgress = true;
               }
            }).done(function(message){
                my_data = jQuery.parseJSON(message);

                console.log('Полученные данные: ',my_data);

                if(my_data.length>0) {
                    $.each(my_data, function(index,data){
                        $('#list').append('' +
                            '<div class="list-item">' +
                                '<div class="list-item__photo">' +
                                    '<img src="'+data.PHOTO+'" alt="" />'+
                                '</div>' +
                                '<div class="list-item__name">'+
                                    data.NAME+
                                '</div>' +
                            '</div>'
                        );
                    });
                }

                inProgress = false;
                startFrom+=1;
            });
        }
    });

});
