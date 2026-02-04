var fieldset = document.querySelector('fieldset');
var radios = document.querySelectorAll('input[type=radio]');

for (radio in radios) {
    radios[radio].onclick = function () {
        var value = this.value;

        if (value === 'needed') {
            fieldset.style.display = "block";
        } else if (value === 'not_needed') {
            fieldset.style.display = "none";
        }
    }
}

