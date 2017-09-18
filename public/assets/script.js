 document.addEventListener('DOMContentLoaded', function() {
        var params = (new URL(document.location)).searchParams;
        if (params.get("auth") === 'false') {
            uglipop({
                class:'modalTheme',
                source:'div',
                content:'loginPop'
            });
        }
}, false);

function feedbackModal() {
    uglipop({
        class:'modalTheme',
        source:'div',
        content:'feedbackModal'
    });
}

function submitFeedback() {
    var feedbackForm = document.querySelector("#uglipop_popbox #feedbackForm");
    if(feedbackForm['feedbackText'].value == ""){
        alert('Non vorrai mandarci mica un testo vuoto?');
        return false;
    }
    var button = document.querySelector('#uglipop_popbox #feedbackModalButton');
    button.disabled = true;
    button.innerHTML = "Inviando...";
    fetch('/feedback', {
        method: 'POST',
        body: new FormData(feedbackForm),
        credentials: 'same-origin'
    }).then(function(response) {
        //console.log(response)
        if (response.ok) {
            document.getElementById('uglipop_overlay_wrapper').style.display = 'none';
            document.getElementById('uglipop_overlay').style.display = 'none';
            document.getElementById('uglipop_content_fixed').style.display = 'none';
        } else {
            throw new Error(response.statusText);
        }
    }).catch(function(error) {
        console.log('request failed', error)
    });
}

function loadTorunament(id){
    window.location.href = '/tournaments/' + id;
}