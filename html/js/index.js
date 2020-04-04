function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition();
    } else {
        document.getElementById("close_feedback").innerHTML =
            "<p>Geolocation is not supported by this browser.</p>";
    }
}