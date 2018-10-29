
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Login and Account Registration
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function login(baseUri)
{
	var username = encodeURIComponent(document.getElementById("aventurien-solo-login-username").value);
	var password = encodeURIComponent(document.getElementById("aventurien-solo-login-password").value);
	var remember = document.getElementById("aventurien-solo-login-remember").checked;
	
	var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function () {
        if ((this.readyState == 4) && (this.status == 200)) {
			if (this.responseText.substring(0, 9) == "succeeded") {
				window.location.reload(false);
			} else {
				document.getElementById("aventurien-solo-login-error").innerHTML = this.responseText;
			}
		}
    };
    xhttp.open("POST", baseUri + "/login-user.php", true);
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhttp.send("username=" + username + "&password=" + password + "&remember=" + remember);
    return false;
}

function register(baseUri)
{
	var username = encodeURIComponent(document.getElementById("aventurien-solo-register-username").value);
	var password = encodeURIComponent(document.getElementById("aventurien-solo-register-password").value);
	var email = encodeURIComponent(document.getElementById("aventurien-solo-register-email").value);
	
	var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function () {
        if ((this.readyState == 4) && (this.status == 200)) {
			if (this.responseText.substring(0, 9) == "succeeded") {
				openPage('activation');
			} else {
				document.getElementById("aventurien-solo-register-error").innerHTML = this.responseText;
			}
		}
    };
    xhttp.open("POST", baseUri + "/register-user.php", true);
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhttp.send("username=" + username + "&password=" + password + "&email=" + email);
    return false;
}

function openPage(page)
{
	document.getElementById("aventurien-window-solo-login").className = "aventurien-window-solo-" + page;
	return false;
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Hero Selection
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function showCharacterWindowContent(className)
{
	var characterWindow = document.getElementById("aventurien-character-window");
	characterWindow.className = "aventurien-character-window-" + className;
	return false;
}

function selectArchetype(element)
{
	var list = document.getElementById("aventurien-character-archetypes-list");
	var prev_id = list.dataset.selected;
	var prev_item = document.getElementById("aventurien-character-archetype-" + prev_id);
	if (prev_item != null) prev_item.className = "aventurien-character-archetype";
	
	var next_id = element.dataset.id;
	list.dataset.selected = next_id;
	var next_item = document.getElementById("aventurien-character-archetype-" + next_id);
	if (next_item != null) next_item.className = "aventurien-character-archetype selected";
	
	var create_button = document.getElementById("aventurien-character-creation-from-archetype");
	if (create_button != null) create_button.disabled = (next_item == null);
	
	return false;
}

function createCharacterFromArchetype(baseUri, module)
{
	var list = document.getElementById("aventurien-character-archetypes-list");
	var archetype = list.dataset.selected;
	if (!archetype)
		return;
	
	var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function () {
        if ((this.readyState == 4) && (this.status == 200)) {
			if (this.responseText.substring(0, 9) == "succeeded") {
				window.location.reload(false);
			} else {
				document.getElementById("aventurien-character-archetypes-error").innerHTML = this.responseText;
			}
		}
    };

    xhttp.open("POST", baseUri + "/create-character-from-archetype.php", true);
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhttp.send("module=" + module + "&archetype=" + archetype);
    return false;
}

function validateCharacter(e)
{
	var upload = document.getElementById("aventurien-character-creation-upload");
	var xmlFile = document.getElementById("aventurien-character-creation-import-file");
	var imgFile = document.getElementById("aventurien-character-creation-import-portrait");

	if ((xmlFile.files.length == 0) || (imgFile.files.length == 0))
	{
		upload.disabled = true;
		return true;
	}
	
	var xmlFileName = xmlFile.files[0].name;
	var ext = xmlFileName.substr(xmlFileName.lastIndexOf('.') + 1);
	if (ext != "xml")
	{
		alert("Die ausgewählte Datei ist keine XML Datei. Bitte wählen sie einen exportieren Helden aus.");
		
		xmlFile.value = "";
		xmlFile.files.length = 0;
		upload.disabled = true;
		return false;
	}
	
	upload.disabled = false;
	return true;
}

function uploadCharacter(baseUri, module)
{
	var xmlFile = document.getElementById("aventurien-character-creation-import-file");
	if (xmlFile.files.length == 0)
		return;
	
	var imgFile = document.getElementById("aventurien-character-creation-import-portrait");
	if (imgFile.files.length == 0)
		return;
	
	var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function () {
        if ((this.readyState == 4) && (this.status == 200)) {
			if (this.responseText.substring(0, 9) == "succeeded") {
				window.location.reload(false);
			} else {
				document.getElementById("aventurien-character-creation-error").innerHTML = this.responseText;
			}
		}
    };
	
	var data = new FormData();
	data.append('module', module);
	data.append('xmlFile', xmlFile.files[0]);
	data.append('imgFile', imgFile.files[0]);
	
    xhttp.open("POST", baseUri + "/upload-character.php", true);
	xhttp.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhttp.send(data);

	return false;
}

function selectPreviousCharacter(baseUri, module)
{
	var sel = document.getElementById("aventurien-solo-character-selector");
	var items = sel.getElementsByClassName("aventurien-solo-character-selector-item");
	for (i = 0; i < items.length; i++)
	{
		var item = items[i];
		if (item.style.display == "inline-block")
		{
			var prev = item.previousElementSibling;
			if (prev == null)
				prev = item.parentNode.lastElementChild;
			
			if (prev.dataset.hero != null)
				selectCharacter(baseUri, module, prev.dataset.hero);
		}
	}
}

function selectNextCharacter(baseUri, module)
{
	var sel = document.getElementById("aventurien-solo-character-selector");
	var items = sel.getElementsByClassName("aventurien-solo-character-selector-item");
	var selectedItem = null;
	for (i = 0; i < items.length; i++)
	{
		var item = items[i];
		if (item.style.display == "inline-block")
		{
			selectedItem = item;
		}
	}
	if (selectedItem == null)
	{
		selectedItem = items[0];
	}
	
	var next = selectedItem.nextElementSibling;
	if (next == null)
		next = selectedItem.parentNode.firstElementChild;
	
	if (next.dataset.hero != null)
		selectCharacter(baseUri, module, next.dataset.hero);
}

function selectCharacter(baseUri, module, hero)
{
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function () {
        if ((this.readyState == 4) && (this.status == 200)) {
            window.location.reload(false);
        }
    };

    xhttp.open("POST", baseUri + "/select-character.php", true);
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhttp.send("module=" + module + "&hero=" + hero);
    return false;
}

function deleteCharacter(baseUri, module, hero)
{
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function () {
        if ((this.readyState == 4) && (this.status == 200)) {
            window.location.reload(false);
        }
    };

    xhttp.open("POST", baseUri + "/delete-character.php", true);
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhttp.send("module=" + module + "&hero=" + hero);
    return false;
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Solo Navigation
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function select(baseUri, module, pid, name)
{
    var debug = document.getElementById('debug');
    var debug_visible = (debug.style.display == "block");

    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function () {
        if (this.readyState == 4 && this.status == 200) {
			if (pid <= 2)
			{
				window.location.reload(false);
			}
			else
			{
				removeNode("aventurien-solo-menu-character-selection");
				removeNode("aventurien-solo-character-selector");
				var div = document.getElementById("aventurien-solo-module-" + module);
				div.innerHTML = this.responseText;
				div.scrollIntoView(true);
				window.scrollBy(0, -50);
			}
        }
    };
    xhttp.open("POST", baseUri + "/Solo.php?module=" + module, true);
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhttp.send("module=" + module + "&pid=" + pid + "&passage=" + name + "&debug=" + debug_visible);
    return false;
}

function removeNode(id)
{
	var node = document.getElementById(id);
	if (node != null)
		node.parentNode.removeChild(node);
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Menu
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function showMenu(item, x)
{
	var parentItem = item.parentNode;
	while ((parentItem != null) && (parentItem.className != "aventurien-solo-menu"))
	{
		parentItem = parentItem.parentNode;
	}
	if (parentItem == null)
		return false;

	var div = parentItem.getElementsByClassName("aventurien-solo-menu-quicklinks")[0];
	if (div.style.visibility == "visible")
	{
		div.style.visibility = "hidden";
	}
	else
	{
		div.style.visibility = "visible";
	}

	return false;
}

function logout()
{
	document.cookie = 'wp-sonnenstrasse-solo-username=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
	document.cookie = 'wp-sonnenstrasse-solo-password=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
	document.cookie = 'wp-sonnenstrasse-solo-remember=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
	window.location.reload(false);
	return false;
}

function restart(baseUri, module)
{
    if (confirm("Sind sie sicher, dass sie das Abenteuer neu start wollen?"))
    {
        select(baseUri, module, -1, "Cover");
    }
    return false;
}

function start(baseUri, module)
{
    select(baseUri, module, -1, "Start");
    return false;
}

function show_debugger()
{
    var debug = document.getElementById('debug');
    var visible = (debug.style.display == "block");
    debug.style.display = (visible ? "none" : "block");
    return false;
}

window.onload = function (e) {

    if (self != top) {
        document.body.className = "framed";
    }
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Character Sheet
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function selectCharacterSheetTab(tabName)
{
	var characterSheet = document.getElementById("aventurien-character-sheet-container");
	characterSheet.className = "aventurien-character-sheet-tabs-selected-" + tabName;
	return false;
}
