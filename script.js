const darkModeToggle = document.getElementById('dark-mode-toggle');
const languageToggleEn = document.getElementById('language-toggle-en');
const languageToggleDe = document.getElementById('language-toggle-de');
const body = document.body;
const icon = darkModeToggle.querySelector('i');
const formInputs = document.querySelectorAll('#contactForm input, #contactForm textarea');
const contactForm = document.getElementById('contactForm');
const formResponse = document.getElementById('formResponse');
const languageField = contactForm.querySelector('input[name="lang"]');

darkModeToggle.addEventListener('click', () => {
    body.classList.toggle('dark-mode');
    if (body.classList.contains('dark-mode')) {
        icon.classList.remove('fa-sun');
        icon.classList.add('fa-moon');
    } else {
        icon.classList.remove('fa-moon');
        icon.classList.add('fa-sun');
    }
});

const videoToggle = document.getElementById('video-toggle');
const videoBackground = document.getElementById('video-background');

videoToggle.addEventListener('click', function() {
    if (videoBackground.style.display !== 'none') {
        videoBackground.style.display = 'none';
		document.body.classList.add('dark-mode')
		icon.classList.remove('fa-sun');
        icon.classList.add('fa-moon');
        videoToggle.innerHTML = '<i class="fas fa-video-slash"></i>';
        if (!document.body.classList.contains('dark-mode')) {
            document.body.classList.add('dark-mode');
            icon.classList.remove('fa-sun');
            icon.classList.add('fa-moon');
        }
    } else {
        videoBackground.style.display = 'block';
        videoToggle.innerHTML = '<i class="fas fa-video"></i>';
        if (document.body.classList.contains('dark-mode')) {
            document.body.style.color = 'white'; // Change color back to white when video is turned on
        }
    }
});


languageToggleEn.addEventListener('click', () => {
    if (!languageToggleEn.classList.contains('active')) {
        document.documentElement.lang = 'en';
        languageField.value = 'en';
        document.querySelector('header h1').innerText = 'Nicolai Schmidt, Cuxhaven';
        document.querySelector('.contact-form h2').innerText = 'Contact form';
        document.querySelector('.placeholder-text').innerText = 'This page is currently under construction.';
        document.getElementById('name').placeholder = 'John Doe';
        document.getElementById('email').placeholder = 'john.doe@examplemail.com';
        document.getElementById('message').placeholder = 'Message - The provided information transferred here will be processed by me to handle the request.';
		document.getElementById('name-label').innerText = 'Name';
        document.getElementById('email-label').innerText = 'Email';
        document.getElementById('message-label').innerText = 'Message';
        document.querySelector('.contact-form button').innerText = 'Send Message';
        setCustomValidityMessages('en');
        formResponse.textContent = ''; // Clear form response message
        languageToggleEn.classList.add('active');
        languageToggleDe.classList.remove('active');
    }
});

languageToggleDe.addEventListener('click', () => {
    if (!languageToggleDe.classList.contains('active')) {
        document.documentElement.lang = 'de'
        languageField.value = 'de';
        document.querySelector('header h1').innerText = 'Nicolai Schmidt, Cuxhaven';
        document.querySelector('.contact-form h2').innerText = 'Kontaktformular';
        document.querySelector('.placeholder-text').innerText = 'Diese Seite befindet sich derzeit in Bearbeitung.';
        document.getElementById('name').placeholder = 'Max Mustermann';
        document.getElementById('email').placeholder = 'max.mustermann@mustermail.de';
        document.getElementById('message').placeholder = 'Nachricht - Die hier versandten Angaben werden von mir zur Bearbeitung der Anfrage verarbeitet.';
		document.getElementById('name-label').innerText = 'Name';
        document.getElementById('email-label').innerText = 'E-Mail';
        document.getElementById('message-label').innerText = 'Nachricht';
        document.querySelector('.contact-form button').innerText = 'Nachricht senden';
        setCustomValidityMessages('de');
		formResponse.textContent = ''; // Clear form response message
        languageToggleDe.classList.add('active');
        languageToggleEn.classList.remove('active');
    }
});

function setCustomValidityMessages(lang) {
    formInputs.forEach(input => {
        input.oninvalid = function () {
            this.setCustomValidity(
                lang === 'de' ? 'Bitte füllen Sie dieses Feld aus.' : 'Please fill out this field.'
            );
        };
        input.oninput = function () {
            this.setCustomValidity('');
        };
    });
}

// Set initial custom validity messages on page load
document.addEventListener('DOMContentLoaded', () => {
    setCustomValidityMessages(document.documentElement.lang);
});

// Add an event listener to each input field for invalid red border
document.querySelectorAll('.contact-form input, .contact-form textarea').forEach(input => {
  input.addEventListener('focus', () => {
    input.classList.add('interacted');
  });
});


// Handle form submission
contactForm.addEventListener('submit', (event) => {
    event.preventDefault();
    const formData = new FormData(contactForm);
    const lang = document.documentElement.lang;
	formData.append('lang', lang); // Ensure the language is appended to the form data
    fetch(contactForm.action, {
        method: contactForm.method,
        body: formData,
        headers: {
            'Accept': 'application/json'
        }
    }).then(response => {
        if (response.ok) {
            formResponse.textContent = lang === 'de' ? 'Ihre Nachricht wurde gesendet.' : 'Your message has been sent.';
            formResponse.style.color = 'green';
            contactForm.reset();
            // Remove invalid highlight1
            contactForm.querySelectorAll('input, textarea').forEach(input  =>  {
              input.classList.remove('interacted',  'was-blurred');
            });			
        } else {
            response.text().then(text => {
                formResponse.textContent = text;
                formResponse.style.color = 'red';
            });
        }
    }).catch(error => {
        formResponse.textContent = lang === 'de' ? 'Es gab ein Problem beim Versenden Ihrer Nachricht. Bitte versuchen Sie es später erneut.' : 'There was a problem sending your message. Please try again later.';
        formResponse.style.color = 'red';
    });
});