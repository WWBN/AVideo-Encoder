chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
  if (message.type === "getCookies") {
    chrome.cookies.getAll({ domain: ".youtube.com" }, (cookies) => {
      sendResponse({ cookies });
    });
    return true; // necessário para resposta assíncrona
  }
});
