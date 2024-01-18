function setTimezoneCookie() {
    var timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    var existingTimezone = getCookie('timezone');
    var url = new URL(window.location.href);
    var urlTimezone = url.searchParams.get("timezone");

    if (timezone !== existingTimezone) {
        document.cookie = "timezone=" + timezone + ";path=/";

        // Only reload if the timezone parameter is not in the URL or different
        if (timezone !== urlTimezone) {
            // Add timezone to URL parameters
            url.searchParams.set("timezone", timezone);
            window.location.href = url.toString();
        }
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
