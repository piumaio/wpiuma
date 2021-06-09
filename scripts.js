var base_remote_url = document.getElementsByName("base_remote_url");
var img_resize_quality = document.getElementsByName("img_resize_quality");
var img_resize_quality_adaptive = document.getElementsByName("img_resize_quality_adaptive");
var img_convert = document.getElementsByName("img_convert");
function reset() {
    fetch(PIOsettings.pluginsUrl + "reset.php", {
        method: "POST",
        headers: new Headers({
            "Content-Type": "application/x-www-form-urlencoded" // <-- Specifying the Content-Type
        }),

        body:
            `base_remote_url=${base_remote_url[0].value}` +
            `&img_resize_quality=${img_resize_quality[0].value}` +
            `&img_convert=${img_convert[0].value}` +
            (img_resize_quality_adaptive[0].checked ? `&img_resize_quality_adaptive=1` : '')
    })
        .then(handleErrors)
        .then(() => {
            showMessage('reset-success', 2000)
            base_remote_url[0].value = img_resize_quality[0].value = null;
        })
        .catch(error => {
            showMessage('generic-error', 2000)
        });
}

function update() {
    if (validURL(base_remote_url[0].value) === true) {
        fetch(PIOsettings.pluginsUrl + "update.php", {
            method: "POST",
            headers: new Headers({
                "Content-Type": "application/x-www-form-urlencoded" // <-- Specifying the Content-Type
            }),

            body:
                `base_remote_url=${base_remote_url[0].value}` +
                `&img_resize_quality=${img_resize_quality[0].value}` +
                `&img_convert=${img_convert[0].value}`+
                (img_resize_quality_adaptive[0].checked ? `&img_resize_quality_adaptive=1` : '')
        })
            .then(handleErrors)
            .then(response => {
                showMessage('update-success', 2000)
            })
            .catch(error => {
                showMessage('generic-error', 2000)
            });
    } else {
        showMessage('url-error', 2000)
    }
}

function showMessage(el, timeout) {
    document.getElementById(el).classList.add("is-visible");
    setTimeout(() => {
        document
            .getElementById(el)
            .classList.remove("is-visible");
    }, timeout);
}

function loadExtensions() {
    fetch(base_remote_url[0].value).then(r => r.json()).then(json => {
        document.querySelectorAll('.remote-convert-to-option').forEach(e => e.remove());
        img_convert[0].disabled = false;
        for (const [key, value] of Object.entries(json.extensions)) {
            option = document.createElement('option');
            option.value = option.text = value;
            option.classList.add('remote-convert-to-option')
            img_convert[0].add(option)
        }

        for (const option of document.querySelectorAll('#img_convert option')) {
            if (option.value == img_convert[0].dataset.lastValue) {
                option.selected = true;
                break;
            }
        }
    }).catch(() => {
        img_convert[0].value = 'default';
        img_convert[0].disabled = true;
    });
}

function handleErrors(response) {
    if (!response.ok) {
        throw Error(response.statusText);
    }
    return response;
}

function addTrailing(el) {
    if (el.value.substr(-1) != "/") el.value += "/";
}

function validURL(str) {
    var regexp = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
    return regexp.test(str);
}

window.addEventListener("load", () => loadExtensions());
