var defaultText = "<h3>Descrivi il tuo torneo</h3>"
    + "<span>Utilizza questo spazio per illustrare le regole e le impostazioni di gioco.<span><br>"
    + "<span>Per esempio ti consigliamo di specificare:<span><br>"
    + "<ul><li>Condizioni di vittoria (<i>standard, conquista, ...</i>);</li>"
    + "<li>Terreno di gioco (<i>mappa casuale di terra, foresta nera, ...</i>);</li>"
    + "<li>Limite di popolazione, risorse, difficoltà;</li>"
    + "<li>Altre regole dettagliate (<i>popolazioni bandite, tempi di pace, ...</i>).</li></ul>"
    + "<p style='text-align: right;'>Buon divertimento dallo staff di AoE2ita.</p>";

document.addEventListener('DOMContentLoaded', function() {
    tinymce.init({
        selector: 'textarea#desc',  // change this value according to your HTML
        plugins: 'lists',
        toolbar: 'undo redo styleselect bold italic | alignleft aligncenter alignright alignjustify bullist numlist table',
        menubar: false,
        statusbar: false,
        branding: false,
        //width: 400,
        height: 250,
        body_class: 'textArea',
        content_css : '/assets/style.css',
        init_instance_callback: function(){this.setContent(defaultText)}
    });

    var mmr_slider = document.getElementById('mmr_slider');
    var team_slider = document.getElementById('team_slider');
    var member_slider = document.getElementById('member_slider');

    noUiSlider.create(mmr_slider, {
        start: [ 1400, 1700 ],
        step: 50,
        margin: 100,
        range: {
            'min': [ 1200 ],
            'max': [ 2400 ]
        },
        format: {
            to: function ( value ) {
                return parseInt(value);
            },
            from: function ( value ) {
                return parseInt(value);
            }
        }
    });
    noUiSlider.create(team_slider, {
        start: [ 4 ],
        //step: 4,
        snap: true,
        range: {
            'min': [ 4 ],
            '33%': [ 8 ],
            '66%': [ 16 ],
            'max': [ 32 ]
        },
        format: {
            to: function ( value ) {
                return parseInt(value);
            },
            from: function ( value ) {
                return parseInt(value);
            }
        }
    });
    noUiSlider.create(member_slider, {
        start: [ 1 ],
        step: 1,
        range: {
            'min': [ 1 ],
            'max': [ 4 ]
        },
        format: {
            to: function ( value ) {
                return parseInt(value);
            },
            from: function ( value ) {
                return parseInt(value);
            }
        }
    });
    var rank_min = document.getElementsByName('rank_min')[0];
    var rank_max = document.getElementsByName('rank_max')[0];
    var n_teams = document.getElementsByName('n_teams')[0];
    var team_members = document.getElementsByName('team_members')[0];
    var teamvalue = document.getElementById('teamvalue');
    var membervalue = document.getElementById('membervalue');
    var minvalue = document.getElementById('minvalue');
    var maxvalue = document.getElementById('maxvalue');
    mmr_slider.noUiSlider.on('update', function(values, handle) {
        minvalue.innerHTML = rank_min.value = parseInt(values[0]);
        maxvalue.innerHTML = rank_max.value = parseInt(values[1]);
    });
    team_slider.noUiSlider.on('update', function(values, handle) {
        teamvalue.innerHTML = n_teams.value = parseInt(values[0]);
    });
    member_slider.noUiSlider.on('update', function(values, handle) {
        membervalue.innerHTML = team_members.value = parseInt(values[0]);
    });
});

function updateTitle(v) {
    document.getElementById('tTitle').innerHTML = v ? v : "Titolo torneo";
}

var form = document.getElementById('createTournamentForm');
function submitForm() {
    if(form['title'].value == ""){
        alert('Il titolo deve essere inserito!');
        return false;
    }
    var button = document.getElementById('createButton');
    button.disabled = true;
    button.innerHTML = "Creando...";
    tinymce.get("desc").save();
    fetch('/tournaments/new', {
        method: 'POST',
        body: new FormData(form),
        credentials: 'same-origin'
    }).then(function(response) {
        //console.log(response)
        if (response.ok) {
          return response.json();
        } else {
          throw new Error(response.statusText);
        }
    }).then(function(data) {
        //console.log(data)
        window.location = '/tournaments/' + data;
    }).catch(function(error) {
        console.log('request failed', error)
    });
}