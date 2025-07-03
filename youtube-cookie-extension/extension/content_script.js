// Optional: define trusted origins to restrict which sites can interact with the extension
const TRUSTED_ORIGINS = ["https://encoder1.site", "https://encoder2.site"];

// Listen for messages coming from the page
window.addEventListener("message", async (event) => {
  // [Security] Only accept messages from trusted sites (optional)
  // if (!TRUSTED_ORIGINS.includes(event.origin)) return;

  const { type } = event.data;

  // Respond to handshake to let the page know the extension is installed
  if (type === "checkExtension") {
    window.postMessage({ type: "extensionReady", status: true }, "*");
  }

  // Respond to a cookie request from the page
  if (type === "getYouTubeCookies") {
    // Ask the background script to fetch the cookies
    chrome.runtime.sendMessage({ type: "getCookies" }, (response) => {
      // Return cookies back to the page via postMessage
      window.postMessage({ type: "youTubeCookies", data: response.cookies }, "*");
    });
  }
});
