function joinTeam(tId, teamId) {
    var buttons = document.getElementsByTagName('button');
    for (var i = 0; i < buttons.length; i++) {
        buttons[i].disabled = true; //da vedere quando grafichiamo se rimane un button
    }
  fetch('/tournaments/'+tId+'/team/'+teamId, {
    method: 'PUT',
    //body: new FormData(form),
    credentials: 'same-origin'
  }).then(function(response) {
    //console.log(response)
    if (response.ok) {
      location.reload();
    } else {
      throw new Error(response.statusText);
    }
  }).catch(function(error) {
    console.log('request failed', error)
  })
}

function joinRandomTeam(tId) {
  fetch('/tournaments/'+tId+'/randomteam', {
    method: 'PUT',
    //body: new FormData(form),
    credentials: 'same-origin'
  }).then(function(response) {
    //console.log(response)
    if (response.ok) {
      location.reload();
    } else {
      throw new Error(response.statusText);
    }
  }).catch(function(error) {
    console.log('request failed', error)
  })
}

function leaveTeam(tId, mmr) {
  fetch('/tournaments/leave/'+tId+'/'+mmr, {
    method: 'POST',
    //body: new FormData(form),
    credentials: 'same-origin'
  }).then(function(response) {
    //console.log(response)
    if (response.ok) {
      location.reload();
    } else {
      throw new Error(response.statusText);
    }
  }).catch(function(error) {
    console.log('request failed', error)
  })
}

function startTournament(tId) {
  fetch('/tournaments/'+tId+'/start', {
    method: 'POST',
    //body: new FormData(form),
    credentials: 'same-origin'
  }).then(function(response) {
    //console.log(response)
    if (response.ok) {
      location.reload();
    } else {
      throw new Error(response.statusText);
    }
  }).catch(function(error) {
    console.log('request failed', error)
  })
}

function matchScore(tId, matchId, winnerId, scoreOne, scoreTwo) {
    var data = new FormData();
    data.append('teamId', winnerId);
    data.append('one', scoreOne);
    data.append('two', scoreTwo);
  fetch('/tournaments/'+tId+'/match/'+matchId, {
    method: 'POST',
    body: data,
    credentials: 'same-origin'
  }).then(function(response) {
    //console.log(response)
    if (response.ok) {
      location.reload();
    } else {
      throw new Error(response.statusText);
    }
  }).catch(function(error) {
    console.log('request failed', error)
  })
}

function deleteTournament(tId) {
  fetch('/tournaments/'+tId+'/delete', {
    method: 'DELETE',
    //body: new FormData(form),
    credentials: 'same-origin'
  }).then(function(response) {
    //console.log(response)
    if (response.ok) {
      window.location = '/tournaments';
    } else {
      throw new Error(response.statusText);
    }
  }).catch(function(error) {
    console.log('request failed', error)
  })
}

function closeTournament(tId, firstId, secondId, thirdId) {
    var data = new FormData();
    data.append('first', firstId);
    data.append('second', secondId);
    data.append('third', thirdId);
  fetch('/tournaments/'+tId+'/close', {
    method: 'POST',
    body: data,
    credentials: 'same-origin'
  }).then(function(response) {
    //console.log(response)
    if (response.ok) {
      location.reload();
    } else {
      throw new Error(response.statusText);
    }
  }).catch(function(error) {
    console.log('request failed', error)
  })
}

function renderSteamStatus(state, id) {
    if (state != 0) {
        document.getElementById('stText-'+id).innerHTML = 'online';
        document.getElementById('light-'+id).classList.add('on');
    }
}

function steamStatus(el) {
    var expTime = 300; //5 min
    var name = el.querySelector('.name a');
    var nameLength = name.innerHTML.length;
    if (nameLength > 24) {
        name.style.breaKAll = 'break-all';
    } else if (nameLength > 21) {
        name.style.fontSize = '50%';
    } else if (nameLength > 18) {
        name.style.fontSize = '55%';
    } else if (nameLength > 15) {
        name.style.fontSize = '60%';
    } else if (nameLength > 12) {
        name.style.fontSize = '70%';
    } else if (nameLength > 9) {
        name.style.fontSize = '80%';
    } 

    var id = el.getElementsByClassName('userCard')[0].getAttribute('data-steam');
    var cached = localStorage.getItem(id);
    var tsCached = localStorage.getItem(id + ':ts');
    if (cached !== null && tsCached !== null) {
        var age = (Date.now() - tsCached) / 1000;
        if (age < expTime) {
            renderSteamStatus(cached, id);
            return false;
        } else {
            localStorage.removeItem(id);
            localStorage.removeItem(id + ':ts');
        }
    }
        
    fetch('/steam/status/'+id, {
        method: 'GET',
        credentials: 'same-origin'
    }).then(function(response) {
        //console.log(response)
        if (response.ok) {
            response.clone().text().then(content => {
                localStorage.setItem(id, content)
                localStorage.setItem(id + ':ts', Date.now())
            });
            return response.json();
        } else {
            throw new Error(response.statusText);
        }
    }).then(function(data) {
        renderSteamStatus(data, id);
    }).catch(function(error) {
        console.log('request failed', error)
    })
}

document.addEventListener('DOMContentLoaded', function() {
    var tooltip = new Drooltip({"element" : ".userTooltip"});
});

function reloadPage() {
    location.reload();
}

function openDescriptionModal() {
    uglipop({
        class:'descriptionModalTheme',
        source:'div',
        content:'descriptionModal'
    });
}