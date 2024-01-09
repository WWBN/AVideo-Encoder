function setTimezoneCookie() {
    var timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    var existingTimezone = getCookie('timezone');

    if (timezone !== existingTimezone) {
        document.cookie = "timezone=" + timezone + ";path=/";
        window.location.reload(); // Refresh the page
    }
}

function getCookie(name) {
    var cookieArr = document.cookie.split(";");
    for(var i = 0; i < cookieArr.length; i++) {
        var cookiePair = cookieArr[i].split("=");
        if (name == cookiePair[0].trim()) {
            return decodeURIComponent(cookiePair[1]);
        }
    }
    return null;
}

// Call this function on page load
setTimezoneCookie();
