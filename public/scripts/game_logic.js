window.onload = function () {
    let dragged;
    const sources = document.querySelectorAll(".choise");
    sources.forEach(source => {
        source.addEventListener("drag", (event) => {
            console.log("dragging");
        });

        source.addEventListener("dragstart", (event) => {
            dragged = event.target;
            event.target.classList.add("dragging");
        });

        source.addEventListener("dragend", (event) => {
            event.target.classList.remove("dragging");
        });
    })

    const tiles = document.querySelectorAll(".tile");
    tiles.forEach(target => {
        target.addEventListener("dragover", (event) => {
            event.preventDefault();
        });

        target.addEventListener("dragenter", (event) => {
            if (event.target.classList.contains("tile")) {
                event.target.classList.add("dragover");
            }
        });

        target.addEventListener("dragleave", (event) => {
            if (event.target.classList.contains("tile")) {
                event.target.classList.remove("dragover");
            }
        });

        target.addEventListener("drop", (event) => {
            event.preventDefault();
            if (event.target.classList.contains("tile")) {
                event.target.classList.remove("dragover");
                event.target.appendChild(dragged);
            }
        });
    });
    const verifyData = async (dati) => {
        const formData = new FormData();
        formData.append('data', {corretti : dati,});
        try {
            let resp = await fetch(window.location.href, {method:'POST', body:formData});
            let data = await resp.json();
            showNotification(data.message);
            if(data.status==='success') { inpUser.dataset.original = val; rowUser.classList.remove('changed'); }
        } catch(e) { showNotification("Errore connessione"); }
    }
}