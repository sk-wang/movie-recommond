var i = 0;
var moving = 0;
var img = document.getElementsByClassName('slider-img');
var width = 0;
var slider = document.getElementById('img-slider');
var init = {
	x: 5,
	y: 5,
	sx: 0,
	sy: 0,
	ex: 0,
	ey: 0
};
var sTime = 0,
	eTime = 0;
var a = 0;
for (j = 0; j < img.length; j++) {
	width += img[j].offsetWidth + 10;
}
slider.style.width = width + 'px';

function move(a) {
	i = i + 1;
	var max = Math.abs(0.5 * a * changetime * changetime) / 4;
	if (Math.abs(a) > 2) {
		max = 100;
	}
	if (a < 0 && i < max && Math.abs(slider.offsetLeft) < width - img[0].offsetWidth && moving == 1) {
		slider.style.marginLeft = (slider.offsetLeft + (a / Math.abs(a))) + 'px';
		setTimeout(move(a), 100);
	} else if (a > 0 && i < max && Math.abs(slider.offsetLeft) >= 20) {
		slider.style.marginLeft = (slider.offsetLeft + (a / Math.abs(a))) + 'px';
		setTimeout(move(a), 1);
	} else {
		moving = 0;
		i = 0;
	}
}
slider.addEventListener("touchstart", function() {
	sTime = new Date().getTime();
	moving = 0;
	init.sx = event.targetTouches[0].pageX;
	init.sy = event.targetTouches[0].pageY;
	init.ex = init.sx;
	init.ey = init.sy;
}, false);

slider.addEventListener("touchmove", function() {
	eTime = new Date().getTime();
	init.ex = event.targetTouches[0].pageX;
	init.ey = event.targetTouches[0].pageY;
	changex = Math.abs(init.ex - init.sx);
	changey = Math.abs(init.ey - init.sy);
	direction = (init.ex - init.sx) / changex;
	changetime = eTime - sTime;
	a = (init.ex - init.sx) / (changetime * changetime);
	if (Math.abs(init.ex - init.sx) > Math.abs(init.ey - init.sy) && moving == 0) {
		moving = 1;
		move(a);
		sTime = new Date().getTime();
		init.ex = init.sx;
		init.ey = init.sy;
	}
}, false);