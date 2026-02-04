<template>
    <div>
        <slot></slot>
        <div v-if="choiceCount<60" class="d-flex">
            <a v-if="surveyState === 0" @click="add" href="javascript:void(0);" class="btn btn-secondary">Antwort hinzufügen</a>
        </div>
    </div>
</template>

<script>
    import DataTable from "./DataTable";

    export default {
        name: "form-survey-question-choices",
        props: [
            'surveyState'
        ],
        components: {
            DataTable
        },
        data() {
            return {
                currentInput: null,
                choiceCount: 0,
                selectedQuestions: {},
                selectedQuestionsCount: 0
            }
        },
        mounted() {
            this.$el.firstChild.childNodes.forEach((node, i) => {
                this.addButtons(node);
                this.choiceCount++;
            });
        },
        methods: {
            add: function () {
                  let newIndex = this.getHighestIndex() + 1;
                  let template = this.createChild(this.$el.firstChild.dataset.prototype.replace(/__name__/g, newIndex));
                  this.addButtons(template);
                  this.$el.firstChild.appendChild(template);
                  this.choiceCount++;
            },
            createChild(html) {
                var child = document.createElement('div');
                child.innerHTML = html;
                return child.firstChild;
            },
            addButtons(el) {
              if(this.surveyState === 0) {
                var container = this.createChild('<div class="text-right"></div>');
                var delButton = this.createChild('<a href="javascript:void(0);" class="btn btn-danger"><i class="fas fa-trash-alt"></i></a>');
                delButton.addEventListener('click', () => {
                  el.remove();
                  this.choiceCount--;
                });
                container.appendChild(delButton);
                el.appendChild(container);
                this.currentInput = el.querySelector('input[id$="_choice"]')
              }
            },
            getHighestIndex() {
                var highestIndex = 0;
                this.$el.firstChild.childNodes.forEach((node, i) => {
                    var found = parseInt(node.id.toString().match(/\d+/)[0]);
                    if (found > highestIndex) {
                        highestIndex = found;
                    }
                });
                return highestIndex;
            },
        }
    }
</script>

<style scoped>

</style>
