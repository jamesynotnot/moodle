if (navigator.userAgent.includes('iLearnBrowser')) {

    var ssobuttons = document.getElementsByClassName('potentialidplist');

    if (ssobuttons.length > 0) {

        ssobuttons[0].previousElementSibling.style.display = "none";

        for (var i = 0; i < ssobuttons.length; i++) {
            ssobuttons[i].style.display = "none";
        }
    }

}