/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you require will output into a single css file (app.css in this case)
require('../css/app.scss');

import Vue from 'vue'
import BootstrapVue from 'bootstrap-vue'
import VGauge from "vgauge";
import Datetime from 'vue-datetime'
import moment from 'moment'
import VueApexCharts from 'vue-apexcharts'
import DataTable from './components/DataTable'
import FormDateTime from './components/FormDateTime'
import FormManipulator from './components/FormManipulator'
import QcInput from './components/QcInput'
import QualityCircleToDoForm from './components/QualityCircleToDoForm'
import QualityCheckSkipButton from './components/QualityCheckSkipButton'
import QualityCheckSubCategory from './components/QualityCheckSubCategory'
import FormSurveyQuestions from './components/FormSurveyQuestions'
import FormSurveyQuestionChoices from './components/FormSurveyQuestionChoices'
import QualityCheckSaveButton from "./components/QualityCheckSaveButton";
import FoodSurvey from "./components/FoodSurvey";
import FoodSurveyPublic from "./components/FoodSurveyPublic";
import QuestionFlags from "./components/QuestionFlags";

// You need a specific loader for CSS files
import 'vue-datetime/dist/vue-datetime.css'

moment.locale('de');
Vue.prototype.moment = moment;
Vue.use(BootstrapVue, {
    BModal: {
        cancelTitle: 'Abbrechen',
    },
})

Vue.use(VueApexCharts);
Vue.component('apexchart', VueApexCharts);

Vue.use(Datetime);

new Vue({
    el: '#app',
    components: {
        DataTable,
        FormDateTime,
        FormManipulator,
        QcInput,
        QualityCircleToDoForm,
        QualityCheckSubCategory,
        QualityCheckSkipButton,
        FormSurveyQuestions,
        FormSurveyQuestionChoices,
        VGauge,
        FoodSurvey,
        FoodSurveyPublic,
        QualityCheckSaveButton,
        QuestionFlags
    },
    created() {
        // Textarea autosize
        var tx = document.querySelectorAll('textarea');
        for (var i = 0; i < tx.length; i++) {
            tx[i].style.height = 'auto';
            tx[i].style.height = (tx[i].scrollHeight + 2) + 'px';
        }
    },
    methods: {
        textareaChange(e) {
            // Textarea autosize
            e.target.style.height = 'auto';
            e.target.style.height = (e.target.scrollHeight + 2) + 'px';
        },
        goto(href) {
            window.location.href = href;
        },
        fallbackCopyTextToClipboard(text) {
            let textArea = document.createElement("textarea");
            textArea.value = text;

            // Avoid scrolling to bottom
            textArea.style.top = "0";
            textArea.style.left = "0";
            textArea.style.position = "fixed";

            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                let successful = document.execCommand('copy');
                let msg = successful ? 'successful' : 'unsuccessful';

            } catch (err) {
                console.error('Fallback: Oops, unable to copy', err);
            }

            document.body.removeChild(textArea);
        },
        copyTextToClipboard(text) {
            if (!navigator.clipboard) {
                this.fallbackCopyTextToClipboard(text);
                return;
            }
            navigator.clipboard.writeText(text).then(function () {
                // Copying successful
            }, function (err) {
                console.error('Async: Could not copy text: ', err);
            });
        },
        getFlagIcon(flag) {
            // Get flag definitions from the server-side QualityCheckService
            const flagDefinitions = window.flagDefinitions || {};
            if (flagDefinitions[flag] && flagDefinitions[flag].icon) {
                return flagDefinitions[flag].icon;
            }
            return 'fas fa-do-not-enter'; // Default icon if not found
        },
        getFlagDescription(flag) {
            // Get flag definitions from the server-side QualityCheckService
            const flagDefinitions = window.flagDefinitions || {};
            if (flagDefinitions[flag] && flagDefinitions[flag].description) {
                return flagDefinitions[flag].description;
            }
            return flag; // Default to flag name if not found
        },
        getFlagColor(flag) {
            // Get flag color from the server-side QualityCheckService
            const flagDefinitions = window.flagDefinitions || {};
            if (flagDefinitions[flag] && flagDefinitions[flag].color) {
                return flagDefinitions[flag].color;
            }
            return ''; // No color if not found
        }
    }
});

// Enter verbieten, um Forms nicht ausversehen abzuschicken (in textareas aber erlauben)
window.addEventListener('keydown', (event) => {
    if (event.key === "Enter" && event.target.localName != "textarea") {
        event.preventDefault();
        return false;
    }
});

if (document.querySelector('.custom-file-input')) {
    document.querySelector('.custom-file-input').addEventListener('change', function (e) {
        var fileName = e.target.files[0].name;
        var nextSibling = e.target.nextElementSibling
        nextSibling.innerText = fileName
    });
}


if (document.getElementById('user_has_school_personType')) {
    document.getElementById('user_has_school_personType').onchange = function () {
        var val = document.getElementById('user_has_school_personType').value;
        var sel = document.getElementById('user_has_school_role');
        let opts = sel.options;
        for (let opt, j = 0; opt = opts[j]; j++) {
            if (opt.label == this.value) {
                sel.selectedIndex = j;
                break;
            }
        }
    }
}

