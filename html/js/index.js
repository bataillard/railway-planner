function $(id) {
    return document.getElementById(id);
}

function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(setLocation, locationError);
    } else {
        $("close_feedback").innerHTML = "<p>Geolocation is not supported by this browser.</p>";
    }
}

function setLocation(position) {
    console.log("HELLO");
    $("close_lat").value = position.coords.latitude;
    $("close_lon").value = position.coords.longitude;
}

function locationError(geoError) {
    $("close_feedback").innerHTML = geoError.message + " (Code " + geoError.code +")";
}

function setSubmitAction(action) {
    $("submit-action").value = action;
}