var base_remote_url = document.getElementsByName("base_remote_url");
var img_resize_height = document.getElementsByName("img_resize_height");
var img_resize_width = document.getElementsByName("img_resize_width");
var img_resize_quality = document.getElementsByName("img_resize_quality");
function reset() {
  fetch(PIOsettings.pluginsUrl + "reset.php", {
    method: "POST",
    headers: new Headers({
      "Content-Type": "application/x-www-form-urlencoded" // <-- Specifying the Content-Type
    }),

    body:
      `base_remote_url=${base_remote_url[0].value}` +
      `&img_resize_quality=${img_resize_quality[0].value}`
  })
    .then(handleErrors)
    .then(() => {
      showMessage('reset-success',2000)
      base_remote_url[0].value = img_resize_height[0].value = img_resize_width[0].value = img_resize_quality[0].value = null;
    })
    .catch(error => {
      showMessage('generic-error',2000)
    });
}

function update() {
  if(validURL(base_remote_url[0].value) === true){
  fetch(PIOsettings.pluginsUrl + "update.php", {
    method: "POST",
    headers: new Headers({
      "Content-Type": "application/x-www-form-urlencoded" // <-- Specifying the Content-Type
    }),

    body:
      `base_remote_url=${base_remote_url[0].value}` +
      `&img_resize_quality=${img_resize_quality[0].value}`
  })
    .then(handleErrors)
    .then(response => {
      showMessage('update-success',2000)
    })
    .catch(error => {
      showMessage('generic-error',2000)
    });
  }else{
    showMessage('url-error',2000)
  }
}

function showMessage(el, timeout){
  document.getElementById(el).classList.add("is-visible");
  setTimeout(() => {
    document
      .getElementById(el)
      .classList.remove("is-visible");
  }, timeout);
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
