// jQuery news slider on Hompage:default

$(function () {
    var slider = $('#slider');
    var para = slider.find('p');
    var img = slider.find('img');
    var paraHeight = para.height();
    
    var options = {
        speed: 100,
        plusHeight: 10
    };

    para.bind('mouseover', function () {
        var src = $(this).attr('data-src');

        if (img.attr('src') !== src) {
            img.not(":animated").animate({opacity: 0}, options.speed, function () {
                img.attr('src', src);
                img.animate({opacity: 1}, options.speed);
            });
        }
    });

    para.bind('mouseenter', function () {
        var item = $(this).not(":animated");
        item.animate({height: paraHeight + options.plusHeight}, options.speed);

    }).bind('mouseleave', function () {
        $(this).animate({height: paraHeight}, options.speed);
    });

});