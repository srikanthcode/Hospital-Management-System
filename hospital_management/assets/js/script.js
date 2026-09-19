function toggleMenu(){
    var menu = document.getElementById("navbar");
    menu.classList.toggle("active");
}

function toggleReadMore(){

    var more = document.getElementById("moreContent");
    var btn = document.getElementById("readBtn");

    if(more.style.display === "none" || more.style.display === ""){

        more.style.display = "block";
        btn.innerHTML = "Read Less";

    } else {

        more.style.display = "none";
        btn.innerHTML = "Read More";

    }
}
function toggleServices(){

    var content = document.getElementById("moreServices");
    var btn = document.getElementById("serviceBtn");

    if(content.style.display === "none" || content.style.display === ""){

        content.style.display = "block";
        btn.innerHTML = "Read Less";

    } else {

        content.style.display = "none";
        btn.innerHTML = "Read More";

    }
}

/* ================= MASKED HEADING ================= */
(function() {
    var root = document.getElementById('maskedHeading');
    if (!root) return;

    var measure = root.querySelector('.mh__measure');
    var layer = root.querySelector('.mh__reveal');
    var media = root.querySelector('.mh__media');
    var glyphs = root.querySelectorAll('.mh__glyph');
    var words = root.querySelectorAll('.mh__word');

    var fillScale = 1.2;
    var parallax = 20;
    var drift = 12;

    var off = { x: 0, y: 0, tx: 0, ty: 0 };
    var clock = 0;
    var revealed = false;

    function clamp(v, a, b) { return v < a ? a : (v > b ? b : v); }

    function place() {
        if (!media) return;
        var W = root.clientWidth;
        var H = root.clientHeight;
        var maxX = Math.max(0, ((fillScale - 1) / 2) * W);
        var maxY = Math.max(0, ((fillScale - 1) / 2) * H);
        var cx = clamp(off.x, -maxX, maxX);
        var cy = clamp(off.y, -maxY, maxY);
        media.style.transform = 'translate3d(' + cx.toFixed(2) + 'px,' + cy.toFixed(2) + 'px,0) scale(' + fillScale + ')';
    }

    function sync() {
        if (!measure) return;
        root.style.fontSize = clamp(root.clientWidth * 0.055, 20, 54).toFixed(1) + 'px';

        var cs = window.getComputedStyle(measure);
        for (var i = 0; i < words.length; i++) {
            var box = words[i];
            var glyph = glyphs[i];
            if (!box || !glyph) continue;
            glyph.setAttribute('x', box.offsetLeft);
            glyph.setAttribute('y', parseFloat(cs.fontSize) * 0.85);
            glyph.style.fontFamily = cs.fontFamily;
            glyph.style.fontSize = cs.fontSize;
            glyph.style.fontWeight = cs.fontWeight;
            glyph.style.letterSpacing = cs.letterSpacing;
        }
        place();
    }

    function animate() {
        clock += 0.016;
        var dx = Math.sin(clock * 0.21) * drift;
        var dy = Math.cos(clock * 0.17) * drift * 0.6;
        var ease = 1 - Math.exp(-0.016 / 0.18);
        off.x += (off.tx + dx - off.x) * ease;
        off.y += (off.ty + dy - off.y) * ease;
        place();
        requestAnimationFrame(animate);
    }

    root.addEventListener('pointermove', function(e) {
        var r = root.getBoundingClientRect();
        var nx = ((e.clientX - r.left) / (r.width || 1)) * 2 - 1;
        var ny = ((e.clientY - r.top) / (r.height || 1)) * 2 - 1;
        off.tx = clamp(nx, -1, 1) * -parallax;
        off.ty = clamp(ny, -1, 1) * -parallax;
    });

    root.addEventListener('pointerleave', function() {
        off.tx = 0;
        off.ty = 0;
    });

    window.addEventListener('resize', sync);

    /* Rise reveal animation */
    function reveal() {
        if (revealed) return;
        revealed = true;
        var riseDistance = (parseFloat(window.getComputedStyle(root).fontSize) || 48) * 1.15;

        for (var i = 0; i < glyphs.length; i++) {
            glyphs[i].style.transition = 'none';
            glyphs[i].style.transform = 'translateY(' + riseDistance + 'px)';
        }

        layer.style.opacity = '1';
        layer.style.clipPath = 'inset(0% 0% 0% 0%)';

        var start = performance.now();
        var duration = 900;
        var stagger = 80;

        function step(now) {
            var elapsed = now - start;
            for (var i = 0; i < glyphs.length; i++) {
                var t = (elapsed - i * stagger) / duration;
                if (t < 0) t = 0;
                if (t > 1) t = 1;
                var eased = 1 - Math.pow(1 - t, 3);
                glyphs[i].style.transform = 'translateY(' + (riseDistance * (1 - eased)) + 'px)';
            }
            if (elapsed < duration + (glyphs.length - 1) * stagger) {
                requestAnimationFrame(step);
            }
        }
        requestAnimationFrame(step);
    }

    sync();
    setTimeout(reveal, 200);
    requestAnimationFrame(animate);
})();

/* ================= TEXT TYPE EFFECT ================= */
(function() {
    var el = document.getElementById('typingText');
    if (!el) return;

    var texts = [
        "Compassionate Care.",
        "Advanced Healthcare.",
        "Better Lives."
    ];

    var textIndex = 0;
    var charIndex = 0;
    var isDeleting = false;
    var currentText = '';

    var cursor = document.createElement('span');
    cursor.className = 'typing-cursor';
    el.appendChild(cursor);

    function type() {
        var fullText = texts[textIndex];

        if (isDeleting) {
            currentText = fullText.substring(0, charIndex - 1);
            charIndex--;
        } else {
            currentText = fullText.substring(0, charIndex + 1);
            charIndex++;
        }

        el.innerHTML = '';
        el.textContent = currentText;
        el.appendChild(cursor);

        var speed = isDeleting ? 25 : 50;

        if (!isDeleting && charIndex === fullText.length) {
            speed = 1200;
            isDeleting = true;
        } else if (isDeleting && charIndex === 0) {
            isDeleting = false;
            textIndex = (textIndex + 1) % texts.length;
            speed = 300;
        }

        setTimeout(type, speed);
    }

    setTimeout(type, 500);
})();