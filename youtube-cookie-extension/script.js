function checkIfExtensionInstalled() {
    return new Promise((resolve) => {
      const listener = (event) => {
        if (event.data.type === "extensionReady") {
          resolve(true);
          window.removeEventListener("message", listener);
        }
      };
      window.addEventListener("message", listener);
      window.postMessage({ type: "checkExtension" }, "*");
      setTimeout(() => {
        window.removeEventListener("message", listener);
        resolve(false);
      }, 1000);
    });
  }

  function requestYouTubeCookies() {
    return new Promise((resolve) => {
      const listener = (event) => {
        if (event.data.type === "youTubeCookies") {
          resolve(event.data.data);
          window.removeEventListener("message", listener);
        }
      };
      window.addEventListener("message", listener);
      window.postMessage({ type: "getYouTubeCookies" }, "*");
    });
  }

  function updateStatus(id, state) {
    const el = $('#' + id);
    el.removeClass('label-success label-danger label-default');
    el.empty(); // clear content

    if (state === 'ok') {
      el.addClass('label-success').html('<i class="fa fa-check"></i> Available');
    } else if (state === 'fail') {
      el.addClass('label-danger').html('<i class="fa fa-times"></i> Unavailable');
    } else {
      el.addClass('label-default').html('<i class="fa fa-spinner fa-spin"></i> Checking...');
    }
  }

  async function runStatusCheck() {
    updateStatus('extensionStatus', 'loading');
    updateStatus('cookieStatus', 'loading');
    $('#youtubeCookie').val('');

    const installed = await checkIfExtensionInstalled();
    if (!installed) {
      updateStatus('extensionStatus', 'fail');
      updateStatus('cookieStatus', 'fail');
      return;
    }

    updateStatus('extensionStatus', 'ok');

    const cookies = await requestYouTubeCookies();
    const hasCookies = Array.isArray(cookies) && cookies.length > 0;

    if (hasCookies) {
      updateStatus('cookieStatus', 'ok');
      $('#youtubeCookie').val(JSON.stringify(cookies));
    } else {
      updateStatus('cookieStatus', 'fail');
    }
  }

  runStatusCheck();
  setInterval(runStatusCheck, 10000);
