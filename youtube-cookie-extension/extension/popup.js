document.getElementById('send').addEventListener('click', async () => {
    console.log('Button clicked, sending message to background script');
  const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
  chrome.runtime.sendMessage({ type: "extractAndSendCookies" });
});
