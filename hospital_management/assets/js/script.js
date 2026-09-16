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