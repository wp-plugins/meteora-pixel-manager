(function(win, doc, op) {
	var authUrl = "https://meteora.us/oneClick?redirect=",
		frm = doc.getElementById('meteoraOptions'),
		isChild = (!frm && !!op && !!op.meteora && 'setAdvertiserId' in op.meteora),
		idRe = /\?id=([^&=]*)/;

	function setAdvertiserId(id) {
		var aid = frm.querySelector('#aid'),
			submit = frm.querySelector('#submit');
		if(!aid || !submit) {
			return console.error('invalid call');
		}
		aid.value = id;
		return submit.click();
	}

	function signIn(redirUrl) {
		var left = (screen.width / 2) - (600 / 2),
			top = (screen.height / 2) - (400 / 2),
			signinWin = window.open(authUrl + redirUrl, "SignIn", "width=600,height=400,toolbar=0,scrollbars=0,status=0,resizable=0,location=0,menuBar=0,left=" + left + ",top=" + top);
		return false;
	}

	function signOut() {
		return setAdvertiserId('');
	}

	function getId() {
		var m = location.href.match(idRe);
		return(m !== null && m.length == 2) ? m[1] : '';
	}

	function updateParent() {
		if(!isChild) {
			return console.error('invalid call');
		}
		op.meteora.setAdvertiserId(getId());
		win.close();
	}
	if(!isChild) {
		win.meteora = {
			setAdvertiserId: setAdvertiserId,
			signIn: signIn,
			signOut: signOut,
		};
	} else {
		updateParent();
	}

})(window, document, window.opener);
