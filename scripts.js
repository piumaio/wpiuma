function checkRadio() {
  var radios = document.getElementsByName("local_service");

  for (var i = 0, length = radios.length; i < length; i++) {
    if (radios[i].checked) {
      // do whatever you want with the checked radio
      return radios[i].value;
    }
  }
}

function showNotice() {}

function reset() {
  fetch(PIOsettings.pluginsUrl + "reset.php", {
    method: "POST",
    headers: new Headers({
      "Content-Type": "application/x-www-form-urlencoded" // <-- Specifying the Content-Type
    }),

    body:
      `local_service=${checkRadio()}` +
      `$base_remote_url=${
        document.getElementsByName("base_remote_url")[0].value
      }` +
      `&img_resize_height=${
        document.getElementsByName("img_resize_height")[0].value
      }` +
      `&img_resize_width=${
        document.getElementsByName("img_resize_width")[0].value
      }` +
      `&img_resize_quality=${
        document.getElementsByName("img_resize_quality")[0].value
      }`
  })
    .then(
      response => response.text() // .json(), etc.
      // same as function(response) {return response.text();}
    )
    .then(() => {
        document.getElementById("reset-success").classList.add("is-visible");
        setTimeout(() => {
          document
            .getElementById("reset-success")
            .classList.remove("is-visible");
        }, 2000);
      });
}

function update() {
  fetch(PIOsettings.pluginsUrl + "update.php", {
    method: "POST",
    headers: new Headers({
      "Content-Type": "application/x-www-form-urlencoded" // <-- Specifying the Content-Type
    }),

    body:
      `local_service=${checkRadio()}` +
      `&base_remote_url=${
        document.getElementsByName("base_remote_url")[0].value
      }` +
      `&img_resize_height=${
        document.getElementsByName("img_resize_height")[0].value
      }` +
      `&img_resize_width=${
        document.getElementsByName("img_resize_width")[0].value
      }` +
      `&img_resize_quality=${
        document.getElementsByName("img_resize_quality")[0].value
      }`
  })
    .then(
      response => response.text() // .json(), etc.

      // same as function(response) {return response.text();}
    )
    .then(() => {
      document.getElementById("update-success").classList.add("is-visible");
      setTimeout(() => {
        document
          .getElementById("update-success")
          .classList.remove("is-visible");
      }, 2000);
    });
}
